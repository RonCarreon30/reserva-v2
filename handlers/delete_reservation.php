<?php
session_start();
header('Content-Type: application/json');
include '../database/config.php';

$data = json_decode(file_get_contents('php://input'), true);
$reservationId = $data['id'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role']; // Assuming the role is stored in session
// Fetch reservation details with facility name
$reservationQuery = "
    SELECT r.id, r.user_id, r.facility_id, r.reservation_date, r.start_time, r.end_time, r.purpose, f.facility_name
    FROM reservations r
    JOIN facilities f ON r.facility_id = f.facility_id
    WHERE r.id = ?
";
$reservationStmt = $conn->prepare($reservationQuery);
$reservationStmt->bind_param("i", $reservationId);
$reservationStmt->execute();
$reservationStmt->bind_result($reservation_id, $res_user_id, $facility_id, $reservation_date, $start_time, $end_time, $purpose, $facility_name);
$reservationStmt->fetch();
$reservationStmt->close();

// Check if the user trying to cancel is the same as the user who made the reservation (unless they are Admin or Facility Head)
if ($user_id != $res_user_id && !in_array($user_role, ['Facility Head', 'Admin'])) {
    echo json_encode(array("success" => false, "message" => "Only the admins and the user who made the reservation has permission to delete this reservation."));
    exit();
}

// Prepare and execute the deletion query
$stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
$stmt->bind_param("i", $reservationId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
?>
