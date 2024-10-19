<?php
include '../database/config.php';

header('Content-Type: application/json');

$facility_id = $_GET['id']; 

// Prepare and execute the deletion query
$stmt = $conn->prepare("DELETE FROM facilities WHERE id = ?");
$stmt->bind_param("i", $facility_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
?>
