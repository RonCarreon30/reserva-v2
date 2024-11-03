<?php
include '../database/config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $building_id = $_POST['id'];
    $building_name = $_POST['building_name'];
    $building_desc = $_POST['building_desc'];

    $stmt = $conn->prepare("UPDATE buildings_tbl SET building_name = ?, building_desc = ? WHERE building_id = ?");
    $stmt->bind_param("ssi", $building_name, $building_desc, $building_id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Building updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating']);
    }

    $stmt->close();
}
$conn->close();
?>
