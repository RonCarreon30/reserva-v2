<?php
include '../database/config.php'; // Include your database connection

header('Content-Type: application/json');

// Initialize response
$response = [
    'status' => 'error',
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $building_id = $_POST['id'];
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

    $stmt = $conn->prepare("UPDATE buildings_tbl SET building_name = ?, building_desc = ? WHERE building_id = ?");
    $stmt->bind_param("ssi", $building_name, $building_desc, $building_id);
    

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = $building_name . ' building updated successfully.';
    } else {
        $response['message'] = 'Error updating building in the database.';
    }

    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>
