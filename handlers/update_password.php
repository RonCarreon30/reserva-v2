<?php
// Start session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get the logged-in user ID
$user_id = $_SESSION['user_id'];

// Get the current and new passwords from POST data
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];

// Validate that the passwords are not empty
if (empty($current_password) || empty($new_password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

// Connect to the database
require_once '../database/config.php';

// Fetch the user from the database
$sql = "SELECT userPassword FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($stored_password);
$stmt->fetch();
$stmt->close();

// Check if the current password matches the stored one
if (password_verify($current_password, $stored_password)) {
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    $sql = "UPDATE users SET userPassword = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
}

// Close the database connection
$conn->close();
?>
