<?php
session_start();
include '../database/config.php';

$response = [
    'success' => true,
    'message' => [],
    'assignedSchedules' => [],
    'noRoomSchedules' => []
];

$scheduleIds = json_decode($_POST['scheduleIds'], true);

if (empty($scheduleIds)) {
    $response['success'] = false;
    $response['message'][] = "No schedules provided for assignment.";
    echo json_encode($response);
    exit;
}

// Fetch schedules based on the provided IDs
$ids = implode(',', array_map('intval', $scheduleIds));
$sqlFetchSchedules = "
    SELECT s.*, d.building_id, d.dept_name
    FROM schedules s
    JOIN dept_tbl d ON s.department_id = d.dept_id
    WHERE s.schedule_id IN ($ids) AND sched_status = 'pending';
";

$resultFetchSchedules = $conn->query($sqlFetchSchedules);

if (!$resultFetchSchedules) {
    $response['success'] = false;
    $response['message'][] = "Error fetching schedules: " . $conn->error;
    echo json_encode($response);
    exit;
}

$schedules = [];
while ($row = $resultFetchSchedules->fetch_assoc()) {
    $schedules[] = $row;
}

// Function to check for section schedule conflicts
function checkSectionConflict($conn, $section, $days, $startTime, $endTime, $scheduleId) {
    $sqlCheck = "
        SELECT s.*
        FROM schedules s
        WHERE s.section = '$section'
        AND s.schedule_id != '$scheduleId'
        AND s.sched_status != 'cancelled'
        AND s.days REGEXP '(" . implode("|", explode(',', $days)) . ")'
        AND ('$startTime' < s.end_time AND '$endTime' > s.start_time)
    ";
    $result = $conn->query($sqlCheck);
    return $result->num_rows > 0;
}

// Function to check for instructor schedule conflicts
function checkInstructorConflict($conn, $instructor, $days, $startTime, $endTime, $scheduleId) {
    $sqlCheck = "
        SELECT s.*
        FROM schedules s
        WHERE s.instructor = '$instructor'
        AND s.schedule_id != '$scheduleId'
        AND s.sched_status != 'cancelled'
        AND s.days REGEXP '(" . implode("|", explode(',', $days)) . ")'
        AND ('$startTime' < s.end_time AND '$endTime' > s.start_time)
    ";
    $result = $conn->query($sqlCheck);
    return $result->num_rows > 0;
}

// Function to find optimal room time slot
function findOptimalTimeSlot($conn, $roomId, $startTime, $endTime, $days) {
    // Get existing assignments for the room
    $sqlExisting = "
        SELECT s.start_time, s.end_time
        FROM room_assignments_tbl ra
        JOIN schedules s ON ra.schedule_id = s.schedule_id
        WHERE ra.room_id = '$roomId'
        AND s.days REGEXP '(" . implode("|", explode(',', $days)) . ")'
        ORDER BY s.start_time
    ";
    
    $result = $conn->query($sqlExisting);
    $existingSlots = $result->fetch_all(MYSQLI_ASSOC);
    
    // Calculate gap between proposed time and existing slots
    $minGap = 7200; // Minimum acceptable gap in seconds (2 hours)
    $optimalGap = 0; // No gap is optimal
    
    foreach ($existingSlots as $slot) {
        $slotStart = strtotime($slot['start_time']);
        $slotEnd = strtotime($slot['end_time']);
        $proposedStart = strtotime($startTime);
        $proposedEnd = strtotime($endTime);
        
        $gap = $slotStart - $proposedEnd;
        if ($gap > 0 && $gap < $minGap) {
            return false; // Gap is too small
        }
    }
    
    return true;
}

foreach ($schedules as $schedule) {
    $scheduleId = $schedule['schedule_id'];
    $classType = $schedule['class_type'];
    $buildingId = $schedule['building_id'];
    $days = $schedule['days'];
    $startTime = $schedule['start_time'];
    $endTime = $schedule['end_time'];
    $section = $schedule['section'];
    $instructor = $schedule['instructor'];
    $roomAssigned = false;

    // Check for section conflicts
    if (checkSectionConflict($conn, $section, $days, $startTime, $endTime, $scheduleId)) {
        $response['success'] = false;
        $response['message'][] = "Section schedule conflict detected for schedule ID: $scheduleId";
        continue;
    }

    // Check for instructor conflicts
    if (checkInstructorConflict($conn, $instructor, $days, $startTime, $endTime, $scheduleId)) {
        $response['success'] = false;
        $response['message'][] = "Instructor schedule conflict detected for schedule ID: $scheduleId";
        continue;
    }

    // Get matching rooms
    $sqlMatchingRooms = "
        SELECT r.*
        FROM rooms_tbl r
        WHERE r.room_type = '$classType'
        AND r.building_id = '$buildingId'
        AND r.room_status = 'Available'
        ORDER BY r.room_name
    ";
    $resultMatchingRooms = $conn->query($sqlMatchingRooms);

    if (!$resultMatchingRooms) {
        $response['success'] = false;
        $response['message'][] = "Error fetching matching rooms: " . $conn->error;
        continue;
    }

    $rooms = $resultMatchingRooms->fetch_all(MYSQLI_ASSOC);

    // Try to find optimal room assignment
    foreach ($rooms as $room) {
        $roomId = $room['room_id'];
        
        // Check if room is available at this time
        $sqlCheckRoom = "
            SELECT ra.*
            FROM room_assignments_tbl ra
            JOIN schedules s ON ra.schedule_id = s.schedule_id
            WHERE ra.room_id = '$roomId'
            AND s.days REGEXP '(" . implode("|", explode(',', $days)) . ")'
            AND ('$startTime' < s.end_time AND '$endTime' > s.start_time)
        ";
        $resultCheckRoom = $conn->query($sqlCheckRoom);

        if ($resultCheckRoom && $resultCheckRoom->num_rows == 0) {
            // Check if this creates an optimal time slot
            if (findOptimalTimeSlot($conn, $roomId, $startTime, $endTime, $days)) {
                $sqlAssignRoom = "
                    INSERT INTO room_assignments_tbl (room_id, schedule_id)
                    VALUES ('$roomId', '$scheduleId')
                ";
                if ($conn->query($sqlAssignRoom)) {
                    $roomAssigned = true;
                    $response['assignedSchedules'][] = $scheduleId;
                    break;
                }
            }
        }
    }

    // If no optimal room found in preferred building, search other buildings
    if (!$roomAssigned) {
        $sqlAlternateRooms = "
            SELECT r.*
            FROM rooms_tbl r
            WHERE r.room_type = '$classType'
            AND r.building_id != '$buildingId'
            AND r.room_status = 'Available'
            ORDER BY r.building_id
        ";
        $resultAlternateRooms = $conn->query($sqlAlternateRooms);

        if ($resultAlternateRooms) {
            $alternateRooms = $resultAlternateRooms->fetch_all(MYSQLI_ASSOC);

            foreach ($alternateRooms as $room) {
                $roomId = $room['room_id'];
                
                $sqlCheckRoom = "
                    SELECT ra.*
                    FROM room_assignments_tbl ra
                    JOIN schedules s ON ra.schedule_id = s.schedule_id
                    WHERE ra.room_id = '$roomId'
                    AND s.days REGEXP '(" . implode("|", explode(',', $days)) . ")'
                    AND ('$startTime' < s.end_time AND '$endTime' > s.start_time)
                ";
                $resultCheckRoom = $conn->query($sqlCheckRoom);

                if ($resultCheckRoom && $resultCheckRoom->num_rows == 0) {
                    if (findOptimalTimeSlot($conn, $roomId, $startTime, $endTime, $days)) {
                        $sqlAssignRoom = "
                            INSERT INTO room_assignments_tbl (room_id, schedule_id)
                            VALUES ('$roomId', '$scheduleId')
                        ";
                        if ($conn->query($sqlAssignRoom)) {
                            $roomAssigned = true;
                            $response['assignedSchedules'][] = $scheduleId;
                            break;
                        }
                    }
                }
            }
        }
    }

    // Update schedule status
    if ($roomAssigned) {
        $sqlUpdateStatus = "
            UPDATE schedules 
            SET sched_status = 'assigned' 
            WHERE schedule_id = '$scheduleId'
        ";
        $conn->query($sqlUpdateStatus);
    } else {
        $sqlMarkConflicted = "
            UPDATE schedules 
            SET sched_status = 'conflicted' 
            WHERE schedule_id = '$scheduleId'
        ";
        $conn->query($sqlMarkConflicted);
        $response['noRoomSchedules'][] = $scheduleId;
    }
}

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>