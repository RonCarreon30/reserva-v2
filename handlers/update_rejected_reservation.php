<?php
header('Content-Type: application/json');

// Check if reservation ID and status are provided
if (!isset($_GET['id']) || !isset($_GET['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

// Get reservation ID and status from the request
$reservationId = intval($_GET['id']);
$status = $_GET['status'];

// Fetch the rejection reason from the request
$rejectionReason = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    $rejectionReason = isset($data['reason']) ? $data['reason'] : '';
}

// Connect to the database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reservadb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

// Update reservation status and rejection reason in the database
$stmt = $conn->prepare("UPDATE reservations SET reservation_status = ?, rejection_reason = ? WHERE id = ?");
$stmt->bind_param("ssi", $status, $rejectionReason, $reservationId);

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode(['success' => 'Reservation status updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Error updating reservation status: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>