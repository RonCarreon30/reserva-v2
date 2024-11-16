<?php
include '../database/config.php';

header('Content-Type: application/json');

// Initialize response
$response = [
    'status' => 'error',
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_name = $_POST['departmentName']; // Convert to uppercase and trim whitespace
    $building_id = $_POST['buildingId'];

    // Validate inputs
    if (empty($dept_name) || empty($building_id)) {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }

    // Check if department already exists
    $check_existing_stmt = $conn->prepare("SELECT COUNT(*) FROM dept_tbl WHERE dept_name = ?");
    $check_existing_stmt->bind_param("s", $dept_name);
    $check_existing_stmt->execute();
    $check_existing_stmt->bind_result($existing_count);
    $check_existing_stmt->fetch();
    $check_existing_stmt->close();

    if ($existing_count > 0) {
        // If department name already exists
        $response['message'] = 'The department name already exists.';
        echo json_encode($response);
        exit;
    }

    // Insert data into the database if validation passed
    $stmt = $conn->prepare("INSERT INTO dept_tbl (dept_name, building_id) VALUES (?, ?)");
    $stmt->bind_param("si", $dept_name, $building_id);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Department added successfully.';
    } else {
        $response['message'] = 'Error adding department to the database.';
    }

    $stmt->close();
}

// Send JSON response
echo json_encode($response);
?>
