<?php
include '../database/config.php';

header('Content-Type: application/json');

$user_id = $_GET['id']; 

// Prepare and execute the deletion query
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}

$stmt->close();
$conn->close();
?>
