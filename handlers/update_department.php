<?php
include '../database/config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_id = $_POST['id'];
    $dept_name = $_POST['departmentName'];
    $building_id = $_POST['buildingId']; // Assuming building_id is being sent from the form

    $stmt = $conn->prepare("UPDATE dept_tbl SET dept_name = ?, building_id = ? WHERE dept_id = ?");
    $stmt->bind_param("sii", $dept_name, $building_id, $dept_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => $dept_name . ' department updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error updating']);
}

    $stmt->close();
}
$conn->close();
?>
