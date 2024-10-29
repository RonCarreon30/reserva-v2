<?php
include '../database/config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $building_id = $_POST['building_id'];
    $building_name = $_POST['building_name'];
    $building_desc = $_POST['building_desc'];

    $stmt = $conn->prepare("UPDATE buildings_tbl SET building_name = ?, building_desc = ? WHERE building_id = ?");
    $stmt->bind_param("ssi", $building_name, $building_desc, $building_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
}
$conn->close();
?>
