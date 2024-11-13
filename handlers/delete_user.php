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
    if (isset($input['user_id'])) {
        $user_id = $input['user_id'];

        // Prepare and execute the deletion query
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'User deleted successfully.';
            $response['post_data'] = ['user_id' => $user_id];
        } else {
            $response['message'] = 'Failed to delete the user.';
        }

        $stmt->close();
    } else {
        $response['message'] = 'User ID not received.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$conn->close();

// Send JSON response
echo json_encode($response);
?>
