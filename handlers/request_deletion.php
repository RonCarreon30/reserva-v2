<?php
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
    
    // Get user details for the email
    $user_sql = "SELECT * FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    
    // Get admin and registrar emails
    $admin_sql = "SELECT email FROM users WHERE userRole IN ('Admin', 'Registrar')";
    $admin_result = $conn->query($admin_sql);
    $notification_emails = [];
    while ($row = $admin_result->fetch_assoc()) {
        $notification_emails[] = $row['email'];
    }
    
    // Insert the deletion request
    $sql = "INSERT INTO deletion_requests (user_id, request_date, status) VALUES (?, NOW(), 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Create HTML email
        $subject = "New Account Deletion Request";
        
        // HTML version of the email
        $html_message = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333333;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background-color: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    border-radius: 5px;
                }
                .content {
                    background-color: #ffffff;
                    padding: 20px;
                    margin-top: 20px;
                    border: 1px solid #dee2e6;
                    border-radius: 5px;
                }
                .user-details {
                    background-color: #f8f9fa;
                    padding: 15px;
                    margin: 15px 0;
                    border-radius: 5px;
                }
                .footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 0.9em;
                    color: #6c757d;
                }
                .button {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #007bff;
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-top: 15px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>⚠️ New Account Deletion Request</h2>
                </div>
                
                <div class='content'>
                    <p>A new account deletion request has been submitted and requires your attention.</p>
                    
                    <div class='user-details'>
                        <h3>User Details</h3>
                        <p><strong>ID Number:</strong> " . htmlspecialchars($user_data['id_number']) . "</p>
                        <p><strong>Name:</strong> " . htmlspecialchars($user_data['first_name']) .' '. htmlspecialchars($user_data['last_name']) . "</p>
                        <p><strong>Email:</strong> " . htmlspecialchars($user_data['email']) . "</p>
                        <p><strong>Request Date:</strong> " . date('Y-m-d H:i:s') . "</p>
                    </div>
                    
                    <center>
                        <a href='https://red-reindeer-688169.hostingersite.com/reserva/' class='button'>Review Request</a>
                    </center>
                </div>
                
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>© " . date('Y') . " Your Organization Name. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Plain text version of the email (for email clients that don't support HTML)
        $plain_message = "New Account Deletion Request\n\n" .
                        "A new account deletion request has been submitted.\n\n" .
                        "User Details:\n" .
                        "Name: " . htmlspecialchars($user_data['first_name']) .' '. htmlspecialchars($user_data['last_name']) . "\n" .
                        "Email: " . $user_data['email'] . "\n" .
                        "Request Date: " . date('Y-m-d H:i:s') . "\n\n" .
                        "Please login to the admin panel to process this request: https://red-reindeer-688169.hostingersite.com/reserva/\n\n" .
                        "This is an automated message. Please do not reply to this email.";
        
        // Email headers for HTML email with plain text fallback
        $headers = "From: RESERVA <no-reply@reserva.com>\r\n";
        $headers .= "Reply-To: no-reply@reserva.com\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"boundary\"\r\n";
        
        // Construct the multipart email body
        $message = "--boundary\r\n";
        $message .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
        $message .= $plain_message;
        $message .= "\r\n\r\n--boundary\r\n";
        $message .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
        $message .= $html_message;
        $message .= "\r\n--boundary--";
        
        // Send to all admin and registrar emails
        foreach ($notification_emails as $email) {
            mail($email, $subject, $message, $headers);
        }
        
        echo json_encode(['success' => true, 'message' => 'Account deletion request has been submitted. An administrator will review your request.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to submit deletion request. Please try again.']);
    }
    
    $stmt->close();
    $user_stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
}

$conn->close();
?>