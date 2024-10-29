<?php
include '../database/config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_id = $_POST['dept_id'];
    $dept_name = $_POST['dept_name'];
    $building_id = $_POST['building_id']; // Assuming building_id is being sent from the form

    $stmt = $conn->prepare("UPDATE dept_tbl SET dept_name = ?, building_id = ? WHERE dept_id = ?");
    $stmt->bind_param("sii", $dept_name, $building_id, $dept_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
}
$conn->close();
?>
