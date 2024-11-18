<?php
// handlers/request_deletion.php
session_start();
require_once '../database/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // First, check if there's already a pending deletion request
    $check_sql = "SELECT id FROM deletion_requests WHERE user_id = ? AND status = 'pending'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending deletion request']);
        exit();
    }
    
    // Insert the deletion request
    $sql = "INSERT INTO deletion_requests (user_id, request_date, status) VALUES (?, NOW(), 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Account deletion request has been submitted. An administrator will review your request.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit deletion request. Please try again.']);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
}

$conn->close();
?>