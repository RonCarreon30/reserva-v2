<?php
session_start(); // Start the session

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}
$userId = $_SESSION['user_id'];

include '../database/config.php';

$data = json_decode($_POST['schedules'], true);
$aySemester = $_POST['aySemester'];
$departmentId = $_POST['departmentId'];

// Prepare the insert statement with modified status option
$insertSchedule = $conn->prepare("
    INSERT INTO schedules (user_id, subject_code, subject, section, instructor, start_time, end_time, days, class_type, ay_semester, department_id, sched_status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

// Prepare a statement to check for duplicates of the entire row
$checkDuplicate = $conn->prepare("
    SELECT COUNT(*) FROM schedules 
    WHERE subject_code = ? 
    AND subject = ? 
    AND section = ? 
    AND instructor = ? 
    AND start_time = ? 
    AND end_time = ? 
    AND days = ? 
    AND class_type = ? 
    AND ay_semester = ? 
");

// Prepare a statement to check if the subject is already assigned to the same section (regardless of instructor)
$checkSameSubjectSection = $conn->prepare("
    SELECT COUNT(*) FROM schedules 
    WHERE subject_code = ? 
    AND subject = ? 
    AND section = ? 
    AND ay_semester = ?
");

// Initialize arrays to hold successful saves, duplicates, and other statuses
$savedSchedules = [];
$duplicateSchedules = [];
$sameSubjectSectionConflict = [];
$invalidDurationSchedules = [];

// Process each schedule entry
foreach ($data as $schedule) {
    $subjectCode = $schedule['subjectCode'];
    $subject = $schedule['subject'];
    $section = $schedule['section'];
    $instructor = $schedule['instructor'];
    
    // Parse start and end times
    $startTimeObj = DateTime::createFromFormat('h:i A', $schedule['startTime']);
    $endTimeObj = DateTime::createFromFormat('h:i A', $schedule['endTime']);
    
    // Validate time duration
    if (!$startTimeObj || !$endTimeObj) {
        // Invalid time format
        $invalidDurationSchedules[] = [
            'subjectCode' => $subjectCode,
            'subject' => $subject,
            'section' => $section,
            'instructor' => $instructor,
            'startTime' => $schedule['startTime'],
            'endTime' => $schedule['endTime'],
            'error' => 'Invalid time format'
        ];
        continue;
    }
    
    // Convert to 24-hour format
    $startTime = $startTimeObj->format('H:i');
    $endTime = $endTimeObj->format('H:i');
    
    // Calculate time difference
    $interval = $startTimeObj->diff($endTimeObj);
    $durationHours = $interval->h + ($interval->i / 60);
    
    // Determine schedule status based on duration and other conflicts
    $schedStatus = 'pending';
    
    // Check if duration is less than 2 hours
    if ($durationHours < 2) {
        $schedStatus = 'conflicted';
        
        $invalidDurationSchedules[] = [
            'subjectCode' => $subjectCode,
            'subject' => $subject,
            'section' => $section,
            'instructor' => $instructor,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'duration' => $durationHours,
            'error' => 'Schedule duration must be at least 2 hours'
        ];
    }
    
    $days = explode(',', $schedule['days']);
    $classType = $schedule['classType'];

    foreach ($days as $day) {
        $day = trim($day);

        // Check if same subject is already assigned to the same section (conflict regardless of instructor)
        $checkSameSubjectSection->bind_param("sssi", $subjectCode, $subject, $section, $aySemester);
        $checkSameSubjectSection->execute();
        $checkSameSubjectSection->store_result();
        $checkSameSubjectSection->bind_result($sameSubjectSectionCount);
        $checkSameSubjectSection->fetch();

        if ($sameSubjectSectionCount > 0) {
            // Mark as conflicted if same subject is already in the section
            $schedStatus = 'conflicted';
            
            // Track the conflict
            $sameSubjectSectionConflict[] = [
                'subjectCode' => $subjectCode,
                'subject' => $subject,
                'section' => $section,
                'instructor' => $instructor,
                'day' => $day,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'classType' => $classType
            ];
        }

        // Check for exact duplicate in the entire row
        $checkDuplicate->bind_param("ssssssssi", $subjectCode, $subject, $section, $instructor, $startTime, $endTime, $day, $classType, $aySemester);
        $checkDuplicate->execute();
        $checkDuplicate->store_result();
        $checkDuplicate->bind_result($duplicateCount);
        $checkDuplicate->fetch();

        if ($duplicateCount == 0) {
            // Bind parameters including the determined schedule status
            $insertSchedule->bind_param("isssssssssis", 
                $userId, 
                $subjectCode, 
                $subject, 
                $section, 
                $instructor, 
                $startTime, 
                $endTime, 
                $day, 
                $classType, 
                $aySemester, 
                $departmentId,
                $schedStatus
            );
            $insertSchedule->execute();

            // Get the last inserted schedule ID
            $scheduleId = $conn->insert_id;

            // Track successful saves with the schedule ID and status
            $savedSchedules[] = [
                'scheduleId' => $scheduleId,
                'subjectCode' => $subjectCode,
                'subject' => $subject,
                'section' => $section,
                'instructor' => $instructor,
                'day' => $day,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'classType' => $classType,
                'schedStatus' => $schedStatus
            ];
        } else {
            // Track duplicates
            $duplicateSchedules[] = [
                'subjectCode' => $subjectCode,
                'subject' => $subject,
                'section' => $section,
                'instructor' => $instructor,
                'day' => $day,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'classType' => $classType
            ];
        }
        $checkDuplicate->free_result();
    }
}

// Determine success message
if (count($savedSchedules) > 0) {
    $successMessage = 'Schedules processed. Some may be marked as conflicted.';
} else if (count($duplicateSchedules) > 0 || count($sameSubjectSectionConflict) > 0 || count($invalidDurationSchedules) > 0) {
    $successMessage = 'No schedules saved due to conflicts.';
} else {
    $successMessage = 'No new schedules were saved and no conflicts detected.';
}

// Return success response with saved schedules, duplicates, conflicts, and invalid durations
echo json_encode([
    'success' => count($savedSchedules) > 0,
    'message' => $successMessage,
    'savedSchedules' => $savedSchedules,
    'duplicates' => $duplicateSchedules,
    'conflicts' => $sameSubjectSectionConflict,
    'invalidDurations' => $invalidDurationSchedules
]);

$checkDuplicate->close();
$checkSameSubjectSection->close();
$insertSchedule->close();
$conn->close();