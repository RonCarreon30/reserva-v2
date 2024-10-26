<?php
// Include your database connection
require '../database/config.php'; 


// Get the raw POST data
$data = json_decode(file_get_contents("php://input"), true);

// Extract the data
$reservationDate = $data['reservationDate'];
$startTime = $data['startTime'];
$endTime = $data['endTime'];
$facultyInCharge = $data['facultyInCharge'];
$purpose = $data['purpose'];
$additionalInfo = $data['additionalInfo'];
$rejectionReason = $data['rejectionReason'];
$reservationStatus = 'In Review'; // This will be 'In Review'
$reservationId = $data['reservationId']; // Ensure that this ID is passed to identify the reservation

// Prepare the SQL query to update the reservation
$sql = "UPDATE reservations 
        SET reservation_date = ?, start_time = ?, end_time = ?, facultyInCharge = ?, purpose = ?, additional_info = ?, rejection_reason = ?, reservation_status = ? 
        WHERE id = ?";

// Prepare the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssssssssi', $reservationDate, $startTime, $endTime, $facultyInCharge, $purpose, $additionalInfo, $rejectionReason, $reservationStatus, $reservationId);

// Execute the query
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update reservation.']);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>