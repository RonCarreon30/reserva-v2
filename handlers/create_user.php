<?php
// create_user.php

// Validate input data (e.g., ensure required fields are present)
if (!isset($_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['idNumber'], $_POST['department'], $_POST['role'], $_POST['password'])) {
    // Handle missing fields
    echo json_encode(array('success' => false, 'message' => 'Missing required fields.'));
    exit();
}

// Sanitize input data to prevent SQL injection and other attacks
$firstName = htmlspecialchars($_POST['firstName']);
$lastName = htmlspecialchars($_POST['lastName']);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$idNumber = filter_var($_POST['idNumber']);
$department = htmlspecialchars($_POST['department']);
$userRole = htmlspecialchars($_POST['role']);
$userPassword = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password for security

// Connect to the database
include '../database/config.php';
// Check if the email already exists
$emailCheckStmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$emailCheckStmt->bind_param("s", $email);
$emailCheckStmt->execute();
$emailCheckResult = $emailCheckStmt->get_result();

if ($emailCheckResult->num_rows > 0) {
    // Email already exists
    echo json_encode(array('success' => false, 'message' => 'A user with this email already exists.'));
    $emailCheckStmt->close();
    $conn->close();
    exit();
}

// Close email check statement
$emailCheckStmt->close();

// Prepare SQL statement to insert a new user into the database
$stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, id_number, department_id, userRole, userPassword) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssiss", $firstName, $lastName, $email, $idNumber, $department, $userRole, $userPassword);

// Execute SQL statement to insert a new user
if ($stmt->execute()) {
    echo json_encode(array('success' => true, 'message' => 'User created successfully!'));
} else {
    echo json_encode(array('success' => false, 'message' => 'Error creating user: ' . $stmt->error));
}

// Close prepared statement and database connection
$stmt->close();
$conn->close();
?>
