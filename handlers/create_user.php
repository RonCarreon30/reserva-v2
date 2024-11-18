<?php
// create_user.php

// Start session if needed
session_start();

// Connect to the database
include '../database/config.php';

// Initialize variables for storing feedback
$response = [
    'status' => 'error',
    'message' => ''
];

// Check if required fields are present
if (!isset($_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['idNumber'], $_POST['department'], $_POST['role'], $_POST['password'], $_POST['confirmPassword'])) {
    $response['message'] = 'All fields are required.';
    echo json_encode($response);
    exit();
}

// Check if the password and confirm password match
if ($_POST['password'] !== $_POST['confirmPassword']) {
    $response['message'] = 'Passwords do not match.';
    echo json_encode($response);
    exit();
}

// Sanitize input data
$firstName = htmlspecialchars($_POST['firstName']);
$lastName = htmlspecialchars($_POST['lastName']);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$idNumber = htmlspecialchars($_POST['idNumber']);
$department = htmlspecialchars($_POST['department']);
$userRole = htmlspecialchars($_POST['role']);
$userPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Check if the email already exists
$emailCheckStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
$emailCheckStmt->bind_param("s", $email);
$emailCheckStmt->execute();
$emailCheckStmt->bind_result($emailCount);
$emailCheckStmt->fetch();
$emailCheckStmt->close();

if ($emailCount > 0) {
    $response['message'] = 'A user with this email already exists.';
} else {
    // Check if the idNumber already exists
    $idCheckStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE id_number = ?");
    $idCheckStmt->bind_param("s", $idNumber);
    $idCheckStmt->execute();
    $idCheckStmt->bind_result($idCount);
    $idCheckStmt->fetch();
    $idCheckStmt->close();

    if ($idCount > 0) {
        $response['message'] = 'A user with this ID number already exists.';
    } else {
        // Prepare SQL statement to insert a new user into the database
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, id_number, department_id, userRole, userPassword) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiss", $firstName, $lastName, $email, $idNumber, $department, $userRole, $userPassword);

        // Execute SQL statement to insert a new user
        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'User created successfully!';

            // Send email to the new user
            $subject = "Welcome to RESERVA!";
            $message = "
            <html>
            <body>
                <p>Dear $firstName $lastName,</p>
                <p>Welcome to RESERVA! Your account has been successfully created. You can log in using the following credentials:</p>
                <ul>
                    <li><strong>Email:</strong> $email</li>
                    <li><strong>Password:</strong> Your last name and ID number, formatted as <code>$lastName$idNumber</code>. For example, if your last name is <strong>Doe</strong> and your ID number is <strong>12-3456</strong>, your password would be <code>Doe12-3456</code>.</li>
                </ul>
                <p><a href='https://your-reserva-site.com/login'>Click here to log in</a>.</p>
                <p>For security, please log in and change your password as soon as possible.</p>
                <p>Thank you for joining us!</p>
            </body>
            </html>
            ";


            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: no-reply@reserva.com\r\n";

            // Send the email
            if (mail($email, $subject, $message, $headers)) {
                $response['emailStatus'] = 'Email sent successfully!';
            } else {
                $response['emailStatus'] = 'Failed to send the email.';
            }
        } else {
            $response['message'] = 'Error creating user: ' . $stmt->error;
        }

        // Close the prepared statement
        $stmt->close();
    }
}

// Close the database connection
$conn->close();

// Send a JSON response back to the client
header('Content-Type: application/json');
echo json_encode($response);
?>
