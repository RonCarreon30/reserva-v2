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
    
    // Check if room_id is provided in the JSON input
    if (isset($input['room_id'])) {
        $room_id = $input['room_id'];
        
        // Prepare and execute the deletion query
        $stmt = $conn->prepare("DELETE FROM rooms_tbl WHERE room_id = ?");
        $stmt->bind_param("i", $room_id);

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Room deleted successfully.';
            $response['post_data'] = ['room_id' => $room_id];
        } else {
            $response['message'] = 'Failed to delete the room.';
        }

        $stmt->close();
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
