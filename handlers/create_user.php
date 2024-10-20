<?php
// create_user.php

// Validate input data (e.g., ensure required fields are present)
if (!isset($_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['contactNumber'], $_POST['department'], $_POST['role'], $_POST['password'])) {
    // Handle missing fields
    echo json_encode(array('success' => false, 'message' => 'Missing required fields.'));
    exit();
}

// Sanitize input data to prevent SQL injection and other attacks
$firstName = htmlspecialchars($_POST['firstName']);
$lastName = htmlspecialchars($_POST['lastName']);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$contactNumber = filter_var($_POST['contactNumber'], FILTER_SANITIZE_STRING);
$department = htmlspecialchars($_POST['department']);
$userRole = htmlspecialchars($_POST['role']);
$userPassword = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password for security

// Connect to the database
$servername = "localhost";
$username = "root";
$db_password = ""; // Change this if you have set a password for your database
$dbname = "reservadb";

// Create connection
$conn = new mysqli($servername, $username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
$stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, contact_number, department, userRole, userPassword) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $firstName, $lastName, $email, $contactNumber, $department, $userRole, $userPassword);

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
