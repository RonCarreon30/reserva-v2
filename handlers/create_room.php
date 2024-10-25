<?php
// Start the session
session_start();

// Check if the user is logged in and has the required role
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if not logged in
    header("Location: index.html");
    exit();
}

// Check if the user has the required role
if (!in_array($_SESSION['role'], ['Registrar', 'Admin'])) {
    // Redirect to a page indicating unauthorized access
    header("Location: index.html");
    exit();
}

require_once '../database/config.php'; // Include your database configuration file

// Initialize variables for storing feedback
$response = [
    'status' => 'error',
    'message' => ''
];

// Check if the form data is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data and sanitize it
    $buildingId = isset($_POST['building']) ? intval($_POST['building']) : 0;
    $roomName = isset($_POST['roomName']) ? trim($_POST['roomName']) : '';
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $roomStatus = isset($_POST['roomStatus']) ? trim($_POST['roomStatus']) : '';

    // Validate inputs
    if (empty($buildingId) || empty($roomName) || empty($type) || empty($roomStatus)) {
        $response['message'] = 'All fields are required.';
    } else {
        // Check for duplicate room entries
        $dup_check_stmt = $conn->prepare("SELECT COUNT(*) FROM rooms_tbl WHERE building_id = ? AND room_name = ?");
        $dup_check_stmt->bind_param("is", $buildingId, $roomName);
        $dup_check_stmt->execute();
        $dup_check_stmt->bind_result($count);
        $dup_check_stmt->fetch();
        $dup_check_stmt->close();

        if ($count > 0) {
            $response['message'] = 'This room already exists in the selected building.';
        } else {
            // Prepare the SQL statement to insert the new room
            $stmt = $conn->prepare("INSERT INTO rooms_tbl (building_id, room_name, room_type, room_status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $buildingId, $roomName, $type, $roomStatus);

            // Execute the statement
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Room created successfully.';
            } else {
                $response['message'] = 'Error creating room: ' . $stmt->error; // Use $stmt->error for more accurate error info
            }

            // Close the statement
            $stmt->close();
        }
    }
}

// Close the database connection
$conn->close();

// Send a JSON response back to the client
header('Content-Type: application/json');
echo json_encode($response);
?>
