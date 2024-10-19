<?php
include '../database/config.php';

header('Content-Type: application/json');

$room_id = $_GET['id']; 

// Prepare and execute the deletion query
$stmt = $conn->prepare("DELETE FROM rooms WHERE room_id = ?");
$stmt->bind_param("i", $room_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
?>
