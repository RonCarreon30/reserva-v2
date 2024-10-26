<?php
// Include your database connection
require '../database/config.php'; 


// Get the raw POST data
$data = json_decode(file_get_contents("php://input"), true);

// Extract the data
$facilityName = $data['facility_name'];
$building = $data['building'];
$status = $data['status'];
$descri = $data['descri'];
$facilityId = $data['id']; // Ensure that this ID is passed to identify the reservation

// Prepare the SQL query to update the reservation
$sql = "UPDATE facilities 
        SET facility_name = ?, building = ?, status = ?, descri = ?
        WHERE facility_id = ?";

// Prepare the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssssi', $facilityName, $building, $status, $descri, $facilityId);

// Execute the query
if ($stmt->execute()) {
    echo json_encode(['success' => 'Facility updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update Facility.']);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>