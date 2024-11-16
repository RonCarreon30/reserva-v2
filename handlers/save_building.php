<?php
include '../database/config.php';

header('Content-Type: application/json');

// Initialize response
$response = [
    'status' => 'error',
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $building_name = $_POST['building_name'];
    $building_desc = $_POST['building_desc'];

    // Validate inputs
    if (empty($building_name) || empty($building_desc)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Check if department already exists
    $check_existing_stmt = $conn->prepare("SELECT COUNT(*) FROM buildings_tbl WHERE building_name = ?");
    $check_existing_stmt->bind_param("s", $building_name);
    $check_existing_stmt->execute();
    $check_existing_stmt->bind_result($existing_count);
    $check_existing_stmt->fetch();
    $check_existing_stmt->close();

    if ($existing_count > 0) {
        // If department name already exists
        $response['message'] = 'The building name already exists.';
        echo json_encode($response);
        exit;
    }

    // Insert data into database
    $stmt = $conn->prepare("INSERT INTO buildings_tbl (building_name, building_desc) VALUES (?, ?)");
    $stmt->bind_param("ss", $building_name, $building_desc);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Building added successfully.';
    } else {
        $response['message'] = 'Error adding building to the database.';
    }

    $stmt->close();
}

// Send JSON response
echo json_encode($response);
?>
