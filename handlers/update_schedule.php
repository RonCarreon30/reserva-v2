<?php
// Include database configuration
require_once('../database/config.php');

// Get the raw POST data from the request
$data = json_decode(file_get_contents('php://input'), true);

// Extract values from the request
$scheduleId = $data['scheduleId'];
$subjectCode = $data['subjectCode'];
$section = $data['section'];
$instructor = $data['instructor'];
$days = $data['days'];
$startTime = $data['startTime'];
$endTime = $data['endTime'];
$assignmentId = $data['assignmentId'];
$roomName = $data['roomName'];
$buildingName = $data['buildingName'];

// Query to fetch the current schedule details from the database
$getScheduleQuery = "
    SELECT subject_code, section, instructor, days, start_time, end_time
    FROM schedules
    WHERE schedule_id = ?;
";

$stmt = $conn->prepare($getScheduleQuery);
$stmt->bind_param('i', $scheduleId);
$stmt->execute();
$result = $stmt->get_result();
$currentSchedule = $result->fetch_assoc();
$stmt->close();

// Check if there are any changes in the schedule
$changesMade = (
    $currentSchedule['subject_code'] !== $subjectCode ||
    $currentSchedule['section'] !== $section ||
    $currentSchedule['instructor'] !== $instructor ||
    $currentSchedule['days'] !== $days ||
    $currentSchedule['start_time'] !== $startTime ||
    $currentSchedule['end_time'] !== $endTime
);

// If there's no assignmentId, update the schedule directly
if (empty($assignmentId)) {
    // Update query for schedule details
    if ($changesMade) {
        $updateScheduleQuery = "
            UPDATE schedules
            SET
                subject_code = ?,
                section = ?,
                instructor = ?,
                days = ?,
                start_time = ?,
                end_time = ?
            WHERE schedule_id = ?;
        ";



        $stmt = $conn->prepare($updateScheduleQuery);
        $stmt->bind_param('ssssssi', $subjectCode, $section, $instructor, $days, $startTime, $endTime, $scheduleId);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Schedule updated successfully.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'No Changes Made.']);
    }
} else {
    // If assignmentId exists, check if roomName and buildingName are empty
    $roomAssignmentDeleted = false;
    if (empty($roomName) && empty($buildingName)) {
        // Delete the existing room assignment
        $deleteAssignmentQuery = "DELETE FROM room_assignments_tbl WHERE assignment_id = ?";
        $stmt = $conn->prepare($deleteAssignmentQuery);
        $stmt->bind_param('i', $assignmentId);
        $stmt->execute();
        $stmt->close();
        
        // Mark that the room assignment was deleted
        $roomAssignmentDeleted = true;
    }

    // If there are changes in the schedule, update it
    if ($changesMade || $roomAssignmentDeleted) {
        // Update the schedule details
        $updateScheduleQuery = "
            UPDATE schedules
            SET
                subject_code = ?,
                section = ?,
                instructor = ?,
                days = ?,
                start_time = ?,
                end_time = ?,
                sched_status = 'pending'
            WHERE schedule_id = ?;
        ";

        $stmt = $conn->prepare($updateScheduleQuery);
        $stmt->bind_param('ssssssi', $subjectCode, $section, $instructor, $days, $startTime, $endTime, $scheduleId);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => 'Schedule updated successfully.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'No Changes Made.']);
    }
}

// Close the connection
$conn->close();
?>
