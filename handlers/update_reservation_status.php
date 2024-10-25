<?php
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Check if reservation ID and status are provided
    if (!isset($data['id']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters']);
        exit();
    }

    // Get reservation ID and status from the request
    $reservationId = intval($data['id']);
    $status = $data['status'];

    // Fetch the rejection reason from the request
    $rejectionReason = isset($data['reason']) ? $data['reason'] : '';

    require '../database/config.php';

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
} else {
    // Handle other request methods if necessary
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
