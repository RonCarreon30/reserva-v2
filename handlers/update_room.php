<?php
// Include your database connection
require '../database/config.php'; 

header('Content-Type: application/json');

// Initialize response
$response = [
    'status' => 'error',
    'message' => ''
];

// Get the raw POST data from the form submission (multipart/form-data)
$room_name = isset($_POST['room_name']) ? trim($_POST['room_name']) : '';
$building_id = isset($_POST['building']) ? intval($_POST['building']) : 0;
$room_type = isset($_POST['room_type']) ? trim($_POST['room_type']) : '';
$room_status = isset($_POST['room_status']) ? trim($_POST['room_status']) : '';
$roomId = isset($_POST['id']) ? intval($_POST['id']) : 0;

// Include the POST data in the response for debugging
$response['post_data'] = [
    'room_name' => $room_name,
    'building' => $building_id,
    'room_type' => $room_type,
    'room_status' => $room_status,
    'id' => $roomId
];

// Check if any required fields are empty
if (empty($building_id) || empty($room_name) || empty($room_type) || empty($room_status)) {
    $response['message'] = 'All fields are required.';
    echo json_encode($response);
    exit;
}

// Duplicate check: Check if the room name already exists in the building (excluding the current room)
$dup_check_stmt = $conn->prepare("SELECT COUNT(*) FROM rooms_tbl WHERE building_id = ? AND room_name = ? AND room_id != ?");
$dup_check_stmt->bind_param("isi", $building_id, $room_name, $roomId);
$dup_check_stmt->execute();
$dup_check_stmt->bind_result($count);
$dup_check_stmt->fetch();
$dup_check_stmt->close();

if ($count > 0) {
    // Duplicate found
    $response['message'] = 'This room already exists in the selected building.';
    echo json_encode($response);
    exit;
}

// Prepare the SQL query to update the room
$sql = "UPDATE rooms_tbl 
        SET room_name = ?, building_id = ?, room_type = ?, room_status = ?
        WHERE room_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sissi', $room_name, $building_id, $room_type, $room_status, $roomId);

// Execute the query and prepare a response based on success or failure
if ($stmt->execute()) {
    $response['status'] = 'success';
    $response['message'] = 'Room updated successfully.';
} else {
    $response['message'] = 'Failed to update room: ' . $stmt->error;
}

// Close the statement and connection
$stmt->close();
$conn->close();

// Send JSON response
echo json_encode($response);
?>
