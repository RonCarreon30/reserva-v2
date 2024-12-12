<?php
header('Content-Type: application/json');

// Database connection (adjust credentials as needed)
include "../database/config.php";


function assignRoom($scheduleId) {
    global $conn;
    
    // First, check if schedule already has a room assignment
    $checkAssignmentQuery = "SELECT * FROM room_assignments_tbl WHERE schedule_id = ?";
    $stmt = $conn->prepare($checkAssignmentQuery);
    $stmt->bind_param("i", $scheduleId);
    $stmt->execute();
    $assignmentResult = $stmt->get_result();
    
    if ($assignmentResult->num_rows > 0) {
        return ['status' => 'error', 'message' => 'Schedule already has a room assignment'];
    }
    
    // Check schedule status and get schedule details
    $checkStatusQuery = "SELECT s.*, t.term_status 
                        FROM schedules s
                        JOIN terms_tbl t ON s.ay_semester = t.term_id 
                        WHERE s.schedule_id = ?";
    $stmt = $conn->prepare($checkStatusQuery);
    $stmt->bind_param("i", $scheduleId);
    $stmt->execute();
    $statusResult = $stmt->get_result();
    
    if ($statusResult->num_rows === 0) {
        return ['status' => 'error', 'message' => 'Schedule not found'];
    }
    
    $schedule = $statusResult->fetch_assoc();
    
    // Verify the schedule is for the current term
    if ($schedule['term_status'] !== 'Current') {
        return ['status' => 'error', 'message' => 'Can only assign rooms for the current term'];
    }
    
    if ($schedule['sched_status'] === 'assigned') {
        return ['status' => 'error', 'message' => 'Schedule is already marked as assigned'];
    }
    
    // Adjust time if invalid
    $adjustedTimes = adjustInvalidTime($schedule['start_time'], $schedule['end_time']);
    $schedule['start_time'] = $adjustedTimes['start'];
    $schedule['end_time'] = $adjustedTimes['end'];
    
    // Get department's building_id
    $deptQuery = "SELECT building_id FROM dept_tbl WHERE dept_id = ?";
    $stmt = $conn->prepare($deptQuery);
    $stmt->bind_param("i", $schedule['department_id']);
    $stmt->execute();
    $buildingResult = $stmt->get_result();
    $buildingData = $buildingResult->fetch_assoc();
    
    // Get matching rooms (first attempt - with building restriction)
    $roomsQuery = "SELECT * FROM rooms_tbl 
                   WHERE room_type = ? 
                   AND room_status = 'available'
                   AND building_id = ?";
    
    $stmt = $conn->prepare($roomsQuery);
    $stmt->bind_param("si", $schedule['class_type'], $buildingData['building_id']);
    $stmt->execute();
    $roomsResult = $stmt->get_result();
    
    $availableRoom = findAvailableRoom($roomsResult, $schedule);
    
    // If no room found, try again without building restriction
    if (!$availableRoom) {
        $roomsQuery = "SELECT * FROM rooms_tbl 
                       WHERE room_type = ? 
                       AND room_status = 'available'";
        
        $stmt = $conn->prepare($roomsQuery);
        $stmt->bind_param("s", $schedule['class_type']);
        $stmt->execute();
        $roomsResult = $stmt->get_result();
        
        $availableRoom = findAvailableRoom($roomsResult, $schedule);
    }
    
    if (!$availableRoom) {
        return ['status' => 'error', 'message' => 'No available rooms found'];
    }
    
    // Start transaction for atomic operations
    $conn->begin_transaction();
    
    try {
        // Check for section conflicts and adjust time if needed
        while (hasSectionConflict($schedule['section'], 
                                 $scheduleId, 
                                 $availableRoom['adjusted_start'], 
                                 $availableRoom['adjusted_end'], 
                                 $schedule['days'],
                                 $schedule['ay_semester'])) {
            // Try to find a new time slot
            $adjustedTimes = findNextAvailableTime(
                $availableRoom['adjusted_start'],
                $availableRoom['adjusted_end'],
                $schedule
            );
            
            if (!$adjustedTimes) {
                $conn->rollback();
                return ['status' => 'error', 'message' => 'Unable to find suitable time slot'];
            }
            
            $availableRoom['adjusted_start'] = $adjustedTimes['start'];
            $availableRoom['adjusted_end'] = $adjustedTimes['end'];
        }
        
        // Insert room assignment
        $assignQuery = "INSERT INTO room_assignments_tbl (room_id, schedule_id) VALUES (?, ?)";
        $stmt = $conn->prepare($assignQuery);
        $stmt->bind_param("ii", $availableRoom['room_id'], $scheduleId);
        $stmt->execute();
        
        // Update schedule with adjusted times
        $updateQuery = "UPDATE schedules 
                       SET start_time = ?, end_time = ?, sched_status = 'assigned'
                       WHERE schedule_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssi", 
            $availableRoom['adjusted_start'],
            $availableRoom['adjusted_end'],
            $scheduleId
        );
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        return [
            'status' => 'success',
            'message' => 'Room assigned successfully',
            'data' => [
                'schedule' => $schedule,
                'room' => $availableRoom,
                'adjusted_times' => [
                    'start' => $availableRoom['adjusted_start'],
                    'end' => $availableRoom['adjusted_end']
                ]
            ]
        ];
    } catch (Exception $e) {
        // Rollback the transaction if any error occurs
        $conn->rollback();
        return ['status' => 'error', 'message' => 'Failed to assign room: ' . $e->getMessage()];
    }
}

function adjustInvalidTime($start, $end) {
    $startMinutes = timeToMinutes($start);
    $endMinutes = timeToMinutes($end);
    $schoolStartMinutes = timeToMinutes('07:00');
    $schoolEndMinutes = timeToMinutes('22:00');
    
    // Adjust start time if before school hours
    if ($startMinutes < $schoolStartMinutes) {
        $startMinutes = $schoolStartMinutes;
    }
    
    // Adjust end time if after school hours
    if ($endMinutes > $schoolEndMinutes) {
        $endMinutes = $schoolEndMinutes;
    }
    
    // If start time is after or equal to end time, adjust end time
    if ($startMinutes >= $endMinutes) {
        // Default to 1.5 hours duration if times are invalid
        $endMinutes = $startMinutes + 90;
        if ($endMinutes > $schoolEndMinutes) {
            // If pushing end time forward would exceed school hours,
            // move both times earlier
            $endMinutes = $schoolEndMinutes;
            $startMinutes = $endMinutes - 90;
        }
    }
    
    return [
        'start' => minutesToTime($startMinutes),
        'end' => minutesToTime($endMinutes)
    ];
}

function findNextAvailableTime($currentStart, $currentEnd, $schedule) {
    $duration = timeToMinutes($currentEnd) - timeToMinutes($currentStart);
    $startMinutes = timeToMinutes($currentStart) + 30; // Try 30 minutes later
    
    // If pushing forward 30 minutes would exceed school hours,
    // try starting at 7 AM the next available day
    if ($startMinutes + $duration > timeToMinutes('22:00')) {
        $startMinutes = timeToMinutes('07:00');
    }
    
    return [
        'start' => minutesToTime($startMinutes),
        'end' => minutesToTime($startMinutes + $duration)
    ];
}


function hasSectionConflict($section, $currentScheduleId, $proposedStart, $proposedEnd, $days, $termId) {
    global $conn;
    
    // Get all schedules for this section in the current term
    $query = "SELECT s.* FROM schedules s
              JOIN terms_tbl t ON s.ay_semester = t.term_id
              WHERE s.section = ? 
              AND s.schedule_id != ?
              AND t.term_status = 'Current'
              AND s.ay_semester = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $section, $currentScheduleId, $termId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $proposedStartMins = timeToMinutes($proposedStart);
    $proposedEndMins = timeToMinutes($proposedEnd);
    
    while ($existingSchedule = $result->fetch_assoc()) {
        // Check if schedules share any days
        $existingDays = str_split($existingSchedule['days']);
        $proposedDays = str_split($days);
        $sharedDays = array_intersect($existingDays, $proposedDays);
        
        if (!empty($sharedDays)) {
            // Convert times to minutes for comparison
            $existingStartMins = timeToMinutes($existingSchedule['start_time']);
            $existingEndMins = timeToMinutes($existingSchedule['end_time']);
            
            // Check for overlap
            if (!($proposedEndMins <= $existingStartMins || $proposedStartMins >= $existingEndMins)) {
                return true; // Conflict found
            }
        }
    }
    
    return false; // No conflicts found
}

function findAvailableRoom($roomsResult, $schedule) {
    global $conn;
    
    $bestRoom = null;
    $bestScore = -1;
    $bestSlot = null;
    
    // Convert original schedule times to minutes
    $originalStart = timeToMinutes($schedule['start_time']);
    $originalEnd = timeToMinutes($schedule['end_time']);
    
    // First pass: Find rooms available EXACTLY in the original time slot
    $exactMatchRooms = [];
    
    // Reset the result pointer to beginning
    $roomsResult->data_seek(0);
    
    while ($room = $roomsResult->fetch_assoc()) {
        $assignmentsQuery = "SELECT s.* 
                            FROM room_assignments_tbl ra
                            JOIN schedules s ON ra.schedule_id = s.schedule_id
                            WHERE ra.room_id = ? AND s.days LIKE ?";
        
        $stmt = $conn->prepare($assignmentsQuery);
        $dayPattern = "%{$schedule['days']}%";
        $stmt->bind_param("is", $room['room_id'], $dayPattern);
        $stmt->execute();
        $assignmentsResult = $stmt->get_result();
        
        $isOriginalTimeAvailable = true;
        while ($assignment = $assignmentsResult->fetch_assoc()) {
            $existingStart = timeToMinutes($assignment['start_time']);
            $existingEnd = timeToMinutes($assignment['end_time']);
            
            if (!($originalEnd <= $existingStart || $originalStart >= $existingEnd)) {
                $isOriginalTimeAvailable = false;
                break;
            }
        }
        
        if ($isOriginalTimeAvailable) {
            $exactMatchRooms[] = $room;
        }
    }
    
    // If rooms are available in the exact time slot, return the first one
    if (!empty($exactMatchRooms)) {
        $bestRoom = $exactMatchRooms[0];
        return [
            'room_id' => $bestRoom['room_id'],
            'room_name' => $bestRoom['room_name'],
            'adjusted_start' => $schedule['start_time'],
            'adjusted_end' => $schedule['end_time'],
            'original_duration' => getTimeDuration($schedule['start_time'], $schedule['end_time'])
        ];
    }
    
    // If no rooms available in exact time, fall back to existing time-finding logic
    // Reset the result pointer to beginning again
    $roomsResult->data_seek(0);
    
    while ($room = $roomsResult->fetch_assoc()) {
        // Get existing assignments for this room
        $assignmentsQuery = "SELECT s.* 
                            FROM room_assignments_tbl ra
                            JOIN schedules s ON ra.schedule_id = s.schedule_id
                            WHERE ra.room_id = ? AND s.days LIKE ?";
        
        $stmt = $conn->prepare($assignmentsQuery);
        $dayPattern = "%{$schedule['days']}%";
        $stmt->bind_param("is", $room['room_id'], $dayPattern);
        $stmt->execute();
        $assignmentsResult = $stmt->get_result();
        
        $existingSlots = [];
        while ($assignment = $assignmentsResult->fetch_assoc()) {
            $existingSlots[] = [
                'start' => timeToMinutes($assignment['start_time']),
                'end' => timeToMinutes($assignment['end_time'])
            ];
        }
        
        // Sort existing slots by start time
        usort($existingSlots, function($a, $b) {
            return $a['start'] - $b['start'];
        });
        
        // First, check if original time slot is available
        $isOriginalTimeAvailable = true;
        foreach ($existingSlots as $slot) {
            if (!($originalEnd <= $slot['start'] || $originalStart >= $slot['end'])) {
                $isOriginalTimeAvailable = false;
                break;
            }
        }
        
        if ($isOriginalTimeAvailable) {
            // If original time is available, use it with a very high score
            $result = [
                'slot' => [
                    'start' => $originalStart,
                    'end' => $originalEnd
                ],
                'score' => 1000 + scoreTimeSlot($originalStart, $originalEnd, 
                    getPreviousSlotEnd($existingSlots, $originalStart),
                    getNextSlotStart($existingSlots, $originalEnd))
            ];
        } else {
            // If original time isn't available, find the next best slot
            $result = findOptimalTimeSlot($existingSlots, 
                                        getTimeDuration($schedule['start_time'], $schedule['end_time']),
                                        $originalStart);
        }
        
        if ($result && $result['score'] > $bestScore) {
            $bestScore = $result['score'];
            $bestRoom = $room;
            $bestSlot = $result['slot'];
        }
    }
    
    if ($bestRoom && $bestSlot) {
        return [
            'room_id' => $bestRoom['room_id'],
            'room_name' => $bestRoom['room_name'],
            'adjusted_start' => minutesToTime($bestSlot['start']),
            'adjusted_end' => minutesToTime($bestSlot['end']),
            'original_duration' => getTimeDuration($schedule['start_time'], $schedule['end_time'])
        ];
    }
    
    return null;
}

function getPreviousSlotEnd($existingSlots, $currentStart) {
    $prevEnd = null;
    foreach ($existingSlots as $slot) {
        if ($slot['end'] <= $currentStart) {
            $prevEnd = $slot['end'];
        }
    }
    return $prevEnd;
}

function getNextSlotStart($existingSlots, $currentEnd) {
    foreach ($existingSlots as $slot) {
        if ($slot['start'] >= $currentEnd) {
            return $slot['start'];
        }
    }
    return null;
}


function isValidTimeRange($start, $end) {
    $startMinutes = timeToMinutes($start);
    $endMinutes = timeToMinutes($end);
    
    // Check if start time is before end time
    if ($startMinutes >= $endMinutes) {
        return false;
    }
    
    // Check if times are within school hours (7 AM to 10 PM)
    $schoolStartMinutes = timeToMinutes('07:00');
    $schoolEndMinutes = timeToMinutes('22:00');
    
    return $startMinutes >= $schoolStartMinutes && 
           $endMinutes <= $schoolEndMinutes;
}

// Modify the findOptimalTimeSlot function to include stricter validation
function findOptimalTimeSlot($existingSlots, $requiredDuration, $originalStart) {
    $ORIGINAL_TIME_PENALTY = 10000; 
    $dayStart = timeToMinutes('07:00');  // 7 AM
    $dayEnd = timeToMinutes('22:00');    // 10 PM
    $minUsableGap = timeToMinutes('02:00'); // Minimum useful gap (2 hours)
    
    $candidates = [];
    
    // Validate the required duration
    if ($requiredDuration <= 0 || $requiredDuration > ($dayEnd - $dayStart)) {
        return null;
    }
    
    // Check slot before first assignment
    if (empty($existingSlots)) {
        $proposedEnd = $dayStart + $requiredDuration;
        if ($proposedEnd <= $dayEnd) {
            return [
                'slot' => ['start' => $dayStart, 'end' => $proposedEnd],
                'score' => scoreTimeSlot($dayStart, $proposedEnd, null, null, $originalStart)
            ];
        }
    }
    
    // Check start of day slot
    if ($existingSlots[0]['start'] - $dayStart >= $requiredDuration) {
        $proposedEnd = $dayStart + $requiredDuration;
        if ($proposedEnd <= $dayEnd) {
            $candidates[] = [
                'slot' => ['start' => $dayStart, 'end' => $proposedEnd],
                'score' => scoreTimeSlot($dayStart, $proposedEnd, null, $existingSlots[0]['start'], $originalStart)
            ];
        }
    }
    
    // Check slots between assignments
    for ($i = 0; $i < count($existingSlots) - 1; $i++) {
        $gapStart = $existingSlots[$i]['end'];
        $gapEnd = $existingSlots[$i + 1]['start'];
        
        // Ensure gap start and end are within school hours
        $gapStart = max($gapStart, $dayStart);
        $gapEnd = min($gapEnd, $dayEnd);
        
        if ($gapEnd - $gapStart >= $requiredDuration) {
            // Try placing at start of gap
            $proposedEnd = $gapStart + $requiredDuration;
            if ($proposedEnd <= $dayEnd) {
                $candidates[] = [
                    'slot' => ['start' => $gapStart, 'end' => $proposedEnd],
                    'score' => scoreTimeSlot($gapStart, $proposedEnd, 
                                           $existingSlots[$i]['end'], $existingSlots[$i + 1]['start'], 
                                           $originalStart)
                ];
            }
            
            // Try placing at end of gap
            $endAlignedStart = $gapEnd - $requiredDuration;
            if ($endAlignedStart >= $dayStart && $endAlignedStart > $gapStart) {
                $candidates[] = [
                    'slot' => ['start' => $endAlignedStart, 'end' => $gapEnd],
                    'score' => scoreTimeSlot($endAlignedStart, $gapEnd,
                                           $existingSlots[$i]['end'], $existingSlots[$i + 1]['start'],
                                           $originalStart)
                ];
            }
        }
    }
    
    // Check end of day slot
    if (!empty($existingSlots)) {
        $lastEnd = max(end($existingSlots)['end'], $dayStart);
        $proposedEnd = $lastEnd + $requiredDuration;
        if ($proposedEnd <= $dayEnd) {
            $candidates[] = [
                'slot' => ['start' => $lastEnd, 'end' => $proposedEnd],
                'score' => scoreTimeSlot($lastEnd, $proposedEnd, 
                                       $lastEnd, null, $originalStart)
            ];
        }
    }
    
    // Find the candidate with the highest score
    $bestCandidate = null;
    $bestScore = PHP_INT_MIN;
    
    foreach ($candidates as $candidate) {
        // Double check that the slot is valid
        if (isValidTimeRange(
            minutesToTime($candidate['slot']['start']), 
            minutesToTime($candidate['slot']['end'])
        )) {
            if ($candidate['score'] > $bestScore) {
                $bestScore = $candidate['score'];
                $bestCandidate = $candidate;
            }
        }
    }
    
    return $bestCandidate;
}

// Separate scoring function to calculate time slot score
function scoreTimeSlot($start, $end, $prevEnd, $nextStart, $originalStart = null) {
    $score = 0;
    $ORIGINAL_TIME_PENALTY = 10000;
    $minUsableGap = timeToMinutes('02:00'); // Minimum useful gap (2 hours)
    
    // HUGE penalty if not using original time
    if ($originalStart !== null) {
        $timeDeviation = abs($start - $originalStart);
        $score -= $ORIGINAL_TIME_PENALTY; // Hard penalty for changing time
        $score -= ($timeDeviation / 15) * 50; // Progressive penalty for time deviation
    }
    
    // Score based on adjacency to existing schedules
    if ($prevEnd !== null && $start - $prevEnd < 15) { // Within 15 minutes
        $score += 100; // High score for being adjacent to previous schedule
    }
    
    if ($nextStart !== null && $nextStart - $end < 15) { // Within 15 minutes
        $score += 100; // High score for being adjacent to next schedule
    }
    
    // Penalize creating small unusable gaps
    if ($prevEnd !== null) {
        $gapBefore = $start - $prevEnd;
        if ($gapBefore > 0 && $gapBefore < $minUsableGap) {
            $score -= (50 * ($minUsableGap - $gapBefore) / 60); // Penalty proportional to gap size
        }
    }
    
    if ($nextStart !== null) {
        $gapAfter = $nextStart - $end;
        if ($gapAfter > 0 && $gapAfter < $minUsableGap) {
            $score -= (50 * ($minUsableGap - $gapAfter) / 60); // Penalty proportional to gap size
        }
    }
    
    // Slight preference for earlier times if all else is equal
    $score -= ($start - timeToMinutes('07:00')) / 100;
    
    return $score;
}

function timeToMinutes($time) {
    $parts = explode(':', $time);
    return $parts[0] * 60 + $parts[1];
}

function minutesToTime($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf("%02d:%02d", $hours, $mins);
}

function getTimeDuration($start, $end) {
    return timeToMinutes($end) - timeToMinutes($start);
}

// Handle the incoming request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduleId = isset($_POST['scheduleId']) ? intval($_POST['scheduleId']) : null;
    
    if (!$scheduleId) {
        echo json_encode(['status' => 'error', 'message' => 'Schedule ID is required']);
        exit;
    }
    
    $result = assignRoom($scheduleId);
    echo json_encode($result);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?> 