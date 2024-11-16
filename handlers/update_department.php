<?php
include '../database/config.php'; // Include your database connection

header('Content-Type: application/json');

// Initialize response
$response = [
    'status' => 'error',
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept_id = $_POST['id'];
    $dept_name = trim($_POST['departmentName']); // Convert to uppercase and trim whitespace
    $building_id = $_POST['buildingId']; // Assuming building_id is being sent from the form

    // Validate inputs
    if (empty($dept_id) || empty($dept_name) || empty($building_id)) {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }

    // Check if department name already exists, excluding the current department
    $check_existing_stmt = $conn->prepare("SELECT COUNT(*) FROM dept_tbl WHERE dept_name = ? AND dept_id != ?");
    $check_existing_stmt->bind_param("si", $dept_name, $dept_id);
    $check_existing_stmt->execute();
    $check_existing_stmt->bind_result($existing_count);
    $check_existing_stmt->fetch();
    $check_existing_stmt->close();

    if ($existing_count > 0) {
        // If another department with the same name exists
        $response['message'] = 'The department name already exists.';
        echo json_encode($response);
        exit;
    }

    // Update the department in the database
    $stmt = $conn->prepare("UPDATE dept_tbl SET dept_name = ?, building_id = ? WHERE dept_id = ?");
    $stmt->bind_param("sii", $dept_name, $building_id, $dept_id);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = $dept_name . ' department updated successfully.';
    } else {
        $response['message'] = 'Error updating department in the database.';
    }

    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>
