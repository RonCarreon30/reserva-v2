<?php
// Include your database connection
require '../database/config.php'; 

// Get the raw POST data
$data = json_decode(file_get_contents("php://input"), true);

// Extract the data
$room_number = $data['room_number'];
$building_id = $data['building']; // Change to building_id
$room_type = $data['room_type'];
$room_status = $data['room_status'];
$roomId = $data['id']; // ID to identify the room

// Prepare the SQL query to update the room
$sql = "UPDATE rooms_tbl 
        SET room_number = ?, building_id = ?, room_type = ?, room_status = ?
        WHERE room_id = ?";

// Prepare the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param('sssii', $room_number, $building_id, $room_type, $room_status, $roomId);

// Execute the query
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Room updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update room.']);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>
