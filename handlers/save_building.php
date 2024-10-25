<?php
include '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $building_name = $_POST['building_name'];
    $building_desc = $_POST['building_desc'];

    // Validate inputs
    if (empty($building_name) || empty($building_desc)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Insert data into database
    $stmt = $conn->prepare("INSERT INTO buildings_tbl (building_name, building_desc) VALUES (?, ?)");
    $stmt->bind_param("ss", $building_name, $building_desc);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Building added successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding building to database']);
    }

    $stmt->close();
}
?>
