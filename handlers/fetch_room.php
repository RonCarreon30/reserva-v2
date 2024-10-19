<?php
include '../database/config.php'; // Assuming you have a DB connection file

$room_id = $_GET['id']; // Assuming reservation ID is passed via GET

$query = "SELECT * FROM rooms WHERE room_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $room = $result->fetch_assoc();

    echo json_encode($room);
} else {
    echo json_encode(['error' => 'Facility not found']);
}
?>
