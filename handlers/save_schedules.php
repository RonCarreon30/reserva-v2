<?php
session_start(); // Start the session

$data = json_decode($_POST['schedules'], true);
error_log(print_r($data, true)); // Log the incoming data for debugging

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}
$userId = $_SESSION['user_id'];

include '../database/config.php';

$data = json_decode($_POST['schedules'], true);
$aySemester = $_POST['aySemester'];
$departmentId = $_POST['departmentId'];

// Prepare the insert statement
$insertSchedule = $conn->prepare("
    INSERT INTO schedules (user_id, subject_code, subject, section, instructor, start_time, end_time, days, class_type, ay_semester, department_id, sched_status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
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

// Prepare a statement to check if the subject is already assigned to the section (regardless of instructor)
$checkSameSubjectSection = $conn->prepare("
    SELECT COUNT(*) FROM schedules 
    WHERE subject_code = ? 
    AND subject = ? 
    AND section = ? 
    AND ay_semester = ?
");

// Initialize arrays to hold successful saves and duplicates
$savedSchedules = [];
$duplicateSchedules = [];
$sameSubjectSectionConflict = [];

// Process each schedule entry
foreach ($data as $schedule) {
    $subjectCode = $schedule['subjectCode'];
    $subject = $schedule['subject'];
    $section = $schedule['section'];
    $instructor = $schedule['instructor'];
    $startTime = DateTime::createFromFormat('h:i A', $schedule['startTime'])->format('H:i');
    $endTime = DateTime::createFromFormat('h:i A', $schedule['endTime'])->format('H:i');
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
            // Conflict found, track it in the conflict array
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
            continue; // Skip saving this schedule
        }

        // Check for exact duplicate in the entire row
        $checkDuplicate->bind_param("ssssssssi", $subjectCode, $subject, $section, $instructor, $startTime, $endTime, $day, $classType, $aySemester);
        $checkDuplicate->execute();
        $checkDuplicate->store_result();
        $checkDuplicate->bind_result($duplicateCount);
        $checkDuplicate->fetch();

        if ($duplicateCount == 0) {
            $insertSchedule->bind_param("issssssssii", $userId, $subjectCode, $subject, $section, $instructor, $startTime, $endTime, $day, $classType, $aySemester, $departmentId);
            $insertSchedule->execute();

            // Get the last inserted schedule ID
            $scheduleId = $conn->insert_id;

            // Track successful saves with the schedule ID
            $savedSchedules[] = [
                'scheduleId' => $scheduleId, // Include the ID here
                'subjectCode' => $subjectCode,
                'subject' => $subject,
                'section' => $section,
                'instructor' => $instructor,
                'day' => $day,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'classType' => $classType
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
    $successMessage = 'Schedules processed successfully.';
} else if (count($duplicateSchedules) > 0 || count($sameSubjectSectionConflict) > 0) {
    $successMessage = 'Schedules not saved due to conflicts.';
} else {
    $successMessage = 'No new schedules were saved and no conflicts detected.';
}

// Return success response with saved schedules, duplicates, and conflicts
echo json_encode([
    'success' => count($savedSchedules) > 0,
    'message' => $successMessage,
    'savedSchedules' => $savedSchedules,
    'duplicates' => $duplicateSchedules,
    'conflicts' => $sameSubjectSectionConflict // Return specific subject-section conflicts
]);

$checkDuplicate->close();
$checkSameSubjectSection->close();
$insertSchedule->close();
$conn->close();
