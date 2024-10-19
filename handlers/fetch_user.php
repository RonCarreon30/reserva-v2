<?php
include '../database/config.php'; // Assuming you have a DB connection file

$user_id = $_GET['id']; // Assuming reservation ID is passed via GET

$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    echo json_encode($user);
} else {
    echo json_encode(['error' => 'Reservation not found']);
}
?>
