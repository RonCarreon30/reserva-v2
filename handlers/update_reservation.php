<?php
include '../database/config.php'; // Include your database connection

header('Content-Type: application/json');

// Decode the JSON data received from the fetch request
$data = json_decode(file_get_contents('php://input'), true);

// Check required fields
if (!isset($data['reservationId'], $data['facilityId'], $data['reservationDate'], $data['startTime'], $data['endTime'], $data['facultyInCharge'], $data['purpose'], $data['additionalInfo'], $data['reservationStatus'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

$reservationId = $data['reservationId'];
$facilityId = $data['facilityId'];
$reservationDate = $data['reservationDate'];
$startTime = $data['startTime'];
$endTime = $data['endTime'];
$facultyInCharge = $data['facultyInCharge'];
$purpose = $data['purpose'];
$additionalInfo = $data['additionalInfo'];
$reservationStatus = $data['reservationStatus'];

// Convert start and end times to 24-hour format
$startTime = DateTime::createFromFormat('h:i A', $startTime)->format('H:i:s');
$endTime = DateTime::createFromFormat('h:i A', $endTime)->format('H:i:s');

// Verify facilityId exists in the facilities table
$facilityCheckQuery = "SELECT * FROM facilities WHERE facility_id = ?";
$facilityCheckStmt = $conn->prepare($facilityCheckQuery);
$facilityCheckStmt->bind_param("i", $facilityId);
$facilityCheckStmt->execute();
$facilityCheckResult = $facilityCheckStmt->get_result();

if ($facilityCheckResult->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Invalid Facility ID."]);
    exit;
}

// Display received data for debugging
$receivedData = [
    "reservationId" => $reservationId,
    "facilityId" => $facilityId,
    "reservationDate" => $reservationDate,
    "startTime" => $startTime,
    "endTime" => $endTime,
    "facultyInCharge" => $facultyInCharge,
    "purpose" => $purpose,
    "additionalInfo" => $additionalInfo,
    "reservationStatus" => $reservationStatus
];

// Query to check for overlapping reservations
$query = "
    SELECT * FROM reservations
    WHERE facility_id = ? 
      AND reservation_date = ? 
      AND id != ? 
      AND (
          (end_time > ? AND start_time < ?)
      )
";
$stmt = $conn->prepare($query);
$stmt->bind_param(
    "issss",
    $facilityId,
    $reservationDate,
    $reservationId,
    $startTime,
    $endTime
);
$stmt->execute();
$result = $stmt->get_result();

// Check for overlaps and display results
$overlapData = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $overlapData[] = $row;
    }
    echo json_encode([
        "success" => false,
        "message" => "The selected time overlaps with an existing reservation.",
        "receivedData" => $receivedData,
        "overlapData" => $overlapData
    ]);
} else {
    // No overlap, proceed with update
    $updateQuery = "
        UPDATE reservations 
        SET 
            facility_id = ?, 
            reservation_date = ?, 
            start_time = ?, 
            end_time = ?, 
            facultyInCharge = ?, 
            purpose = ?, 
            additional_info = ?, 
            reservation_status = ?
        WHERE 
            id = ?
    ";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param(
        "isssssssi",
        $facilityId,
        $reservationDate,
        $startTime,
        $endTime,
        $facultyInCharge,
        $purpose,
        $additionalInfo,
        $reservationStatus,
        $reservationId
    );

    if ($updateStmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Reservation updated successfully.",
            "receivedData" => $receivedData
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to update reservation.",
            "receivedData" => $receivedData
        ]);
    }
    $updateStmt->close();
}

$stmt->close();
$conn->close();
?>
