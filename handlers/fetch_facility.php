<?php
include '../database/config.php'; // Assuming you have a DB connection file

$facility_id = $_GET['facility_id']; // Assuming reservation ID is passed via GET

$query = "SELECT * FROM facilities WHERE facility_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $facility_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $reservation = $result->fetch_assoc();

    echo json_encode($reservation);
} else {
    echo json_encode(['error' => 'Facility not found']);
}
?>
