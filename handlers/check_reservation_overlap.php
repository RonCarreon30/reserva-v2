<?php
header('Content-Type: application/json');

include '../database/config.php';

// Get reservation ID from query parameters
$reservationId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($reservationId <= 0) {
    echo json_encode(["error" => "Invalid reservation ID"]);
    exit();
}

// Fetch reservation details to check overlap
$sql = "SELECT * FROM reservations WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$result = $stmt->get_result();
$reservation = $result->fetch_assoc();

if (!$reservation) {
    echo json_encode(["error" => "Reservation not found"]);
    exit();
}

$facility_id = $reservation['facility_id'];
$reservation_date = $reservation['reservation_date'];
$start_time = $reservation['start_time'];
$end_time = $reservation['end_time'];

// Check for overlapping reservations
$sql = "
    SELECT COUNT(*) as overlap 
    FROM reservations 
    WHERE id != ? 
    AND facility_id = ? 
    AND reservation_date = ? 
    AND (
        (start_time < ? AND end_time > ?) 
        OR (start_time >= ? AND start_time < ?) 
        OR (end_time > ? AND end_time <= ?)
    )
    AND reservation_status = 'Reserved'
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "issssssss", 
    $reservationId, 
    $facility_id, 
    $reservation_date, 
    $end_time, 
    $start_time, 
    $start_time, 
    $end_time, 
    $start_time, 
    $end_time
);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['overlap'] > 0) {
    echo json_encode(["overlap" => true]);
} else {
    echo json_encode(["overlap" => false]);
}

$stmt->close();
$conn->close();
?>
