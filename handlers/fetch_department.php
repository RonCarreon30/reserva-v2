<?php
// Database connection (update with your own connection details)
include '../database/config.php';

if (isset($_GET['id'])) {
    $departmentId = $_GET['id'];

    // Prepare and execute the SQL query to get department details by ID
    $stmt = $conn->prepare("SELECT dept_name, building_id FROM dept_tbl WHERE dept_id = ?");
    $stmt->bind_param("i", $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $department = $result->fetch_assoc();

        // Fetch building name based on building_id if needed
        $buildingStmt = $conn->prepare("SELECT building_name FROM buildings_tbl WHERE building_id = ?");
        $buildingStmt->bind_param("i", $department['building_id']);
        $buildingStmt->execute();
        $buildingResult = $buildingStmt->get_result();
        $building = $buildingResult->fetch_assoc();

        // Add building name to the response if needed
        $department['building_name'] = $building['building_name'];

        echo json_encode($department);
    } else {
        echo json_encode(['error' => 'Department not found']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'No department ID provided']);
}
?>
