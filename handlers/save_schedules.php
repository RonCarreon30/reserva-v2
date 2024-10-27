<?php
session_start(); // Start the session

$data = json_decode($_POST['schedules'], true);
error_log(print_r($data, true)); // Log the incoming data for debugging

// Assuming you have already set user_id in session during user login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}
$userId = $_SESSION['user_id']; // Get user ID from session

// Database connection
include '../database/config.php';

// Retrieve incoming data
$data = json_decode($_POST['schedules'], true);
$aySemester = $_POST['aySemester'];
$departmentId = $_POST['departmentId'];

// Prepare the insert statement
$insertSchedule = $conn->prepare("
    INSERT INTO schedules (user_id, subject_code, subject, section, instructor, start_time, end_time, days, class_type, ay_semester, department_id, sched_status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
");

// Prepare a statement to check for duplicates (compare the entire row)
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

// Initialize arrays to hold successful saves and duplicates
$savedSchedules = []; // To hold successful saves for response
$duplicateSchedules = []; // To hold duplicates for response

// Process each schedule entry
foreach ($data as $schedule) {
    // Extract schedule fields
    $subjectCode = $schedule['subjectCode'];
    $subject = $schedule['subject'];
    $section = $schedule['section'];
    $instructor = $schedule['instructor'];

    // Convert startTime and endTime to military time
    $startTime = DateTime::createFromFormat('h:i A', $schedule['startTime'])->format('H:i');
    $endTime = DateTime::createFromFormat('h:i A', $schedule['endTime'])->format('H:i');

    $days = explode(',', $schedule['days']); // Split days by comma
    $classType = $schedule['classType'];

    foreach ($days as $day) {
        $day = trim($day); // Trim whitespace

        // Check for duplicates using the entire row
        $checkDuplicate->bind_param("ssssssssi", $subjectCode, $subject, $section, $instructor, $startTime, $endTime, $day, $classType, $aySemester);
        $checkDuplicate->execute();
        $checkDuplicate->store_result(); // Store result to avoid out-of-sync errors
        $checkDuplicate->bind_result($duplicateCount);
        $checkDuplicate->fetch();
        
        if ($duplicateCount == 0) {
            // Bind parameters and execute the insert statement
            $insertSchedule->bind_param("issssssssii", $userId, $subjectCode, $subject, $section, $instructor, $startTime, $endTime, $day, $classType, $aySemester, $departmentId);
            $insertSchedule->execute();

            // Track successful saves
            $savedSchedules[] = [
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
        $checkDuplicate->free_result(); // Free result before next iteration
    }
}


// Determine success message
if (count($savedSchedules) > 0) {
    $successMessage = 'Schedules processed successfully.';
} else if (count($duplicateSchedules) > 0) {
    $successMessage = 'No new schedules were saved. Some duplicates were detected.';
} else {
    $successMessage = 'No new schedules were saved and no duplicates were detected.';
}

// Return success response with saved schedules and duplicates
echo json_encode([
    'success' => count($savedSchedules) > 0, // Set success based on whether any schedules were saved
    'message' => $successMessage,
    'savedSchedules' => $savedSchedules,
    'duplicates' => $duplicateSchedules // Return details of duplicates
]);

$checkDuplicate->close();
$insertSchedule->close();
$conn->close();
