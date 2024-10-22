<?php
include '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_name = $_POST['departmentName'];
    $building_id = $_POST['building'];

    // Validate inputs
    if (empty($dept_name) || empty($building_id)) {  // Corrected variable names here
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Insert data into database
    $stmt = $conn->prepare("INSERT INTO dept_tbl (dept_name, building_id) VALUES (?, ?)");
    $stmt->bind_param("si", $dept_name, $building_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Department added successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding department to database']);
    }

    $stmt->close();
}
?>
