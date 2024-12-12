<?php
include '../database/config.php';

header('Content-Type: application/json');

// Initialize the response
$response = [
    'status' => 'error',
    'message' => ''
];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input from the request body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['room_id'])) {
        $room_id = $input['room_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Check for existing references in room_assignments_tbl
            $check_stmt = $conn->prepare("
                SELECT COUNT(*) as ref_count 
                FROM room_assignments_tbl 
                WHERE room_id = ?
            ");
            $check_stmt->bind_param("i", $room_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['ref_count'] > 0) {
                throw new Exception("Cannot delete room as it is referenced in schedules/assignments. Please remove all schedules first.");
            }
            
            // If no references found, proceed with deletion
            $delete_stmt = $conn->prepare("DELETE FROM rooms_tbl WHERE room_id = ?");
            $delete_stmt->bind_param("i", $room_id);
            
            if (!$delete_stmt->execute()) {
                throw new Exception("Failed to delete the room.");
            }
            
            // If we got here, everything worked
            $conn->commit();
            $response['status'] = 'success';
            $response['message'] = 'Room deleted successfully.';
            $response['post_data'] = ['room_id' => $room_id];
            
        } catch (Exception $e) {
            // Roll back the transaction on any error
            $conn->rollback();
            $response['message'] = $e->getMessage();
        }
        
        // Close all statements
        if (isset($check_stmt)) $check_stmt->close();
        if (isset($delete_stmt)) $delete_stmt->close();
        
    } else {
        $response['message'] = 'Room ID not provided.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$conn->close();

// Send JSON response
echo json_encode($response);
?>