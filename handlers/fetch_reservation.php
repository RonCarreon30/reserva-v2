<?php
include '../database/config.php'; // Assuming you have a DB connection file

$reservation_id = $_GET['id']; // Assuming reservation ID is passed via GET

$query = "SELECT * FROM reservations WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $reservation = $result->fetch_assoc();
    // Format the start_time and end_time to match your select options (e.g., '08:30 AM')
    $reservation['start_time'] = date('h:i A', strtotime($reservation['start_time']));
    $reservation['end_time'] = date('h:i A', strtotime($reservation['end_time']));

    echo json_encode($reservation);
} else {
    echo json_encode(['error' => 'Reservation not found']);
}
?>
