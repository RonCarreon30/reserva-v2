<?php
// Include database connection
require_once('../database/config.php');

// Get the schedule ID from the request
$data = json_decode(file_get_contents('php://input'), true);
$scheduleId = $data['scheduleId'];

// Check if the schedule ID is provided
if (isset($scheduleId) && is_numeric($scheduleId)) {
    // Start a transaction to ensure both deletions are done together
    $conn->begin_transaction();

    try {
        // Prepare SQL query to delete the schedule from the schedules table
        $scheduleQuery = "DELETE FROM schedules WHERE schedule_id = ?";
        if ($stmt = $conn->prepare($scheduleQuery)) {
            $stmt->bind_param("i", $scheduleId); // Bind the schedule ID as an integer

            // Execute the schedule deletion query
            if (!$stmt->execute()) {
                throw new Exception('Error deleting the schedule.');
            }
            $stmt->close();
        }

        // Prepare SQL query to delete the room assignment from the room_schedules table
        $roomAssignmentQuery = "DELETE FROM room_assignments_tbl WHERE schedule_id = ?";
        if ($stmt = $conn->prepare($roomAssignmentQuery)) {
            $stmt->bind_param("i", $scheduleId); // Bind the schedule ID as an integer

            // Execute the room assignment deletion query
            if (!$stmt->execute()) {
                throw new Exception('Error deleting the room assignment.');
            }
            $stmt->close();
        }

        // Commit the transaction if both deletions are successful
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Schedule and room assignment deleted successfully.']);
    } catch (Exception $e) {
        // Rollback the transaction if there is any error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid schedule ID.']);
}

// Close the database connection
$conn->close();
?>
