<?php
date_default_timezone_set('Asia/Manila');

// Include necessary files
require_once '../database/config.php'; // Adjust the path if needed

header('Content-Type: application/json');

// Initialize response
$response = [
    'status' => 'error',
    'message' => ''
];

// Get the email from POST request
$email = isset($_POST['email']) ? $_POST['email'] : '';

// Check if the email is provided and not empty
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($email)) {
        // Check if the email exists in the database
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            // Email exists in the system, generate token and send the email
            $user_id = $user['id'];

            // Check if a non-expired reset request already exists
            $query = "SELECT * FROM password_resets WHERE user_id = ? AND expires_at >= NOW()";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                // A valid token already exists
                $response['message'] = "A password reset link has already been sent. Please wait until the current request expires before requesting a new one.";
            } else {
                // Generate a new token and expiry time
                $token = bin2hex(random_bytes(16));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Insert the token into the password_resets table
                $query = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iss', $user_id, $token, $expires_at);

                if ($stmt->execute()) {
                    // Generate the reset password link
                    $resetLink = "http://localhost/reserva-v2/reset-password?token=" . $token;

                    // Prepare the email content
                    $subject = "Password Reset Request";
                    $message = "
                    <html>
                    <head>
                        <title>Password Reset Request</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
                            .email-container { background-color: #f7f7f7; padding: 20px; }
                            .email-content { background-color: #ffffff; border-radius: 8px; padding: 20px; }
                            .button { background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
                            .footer { font-size: 12px; color: #888; margin-top: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class='email-container'>
                            <div class='email-content'>
                                <h2>Password Reset Request</h2>
                                <p>Hello,</p>
                                <p>We received a request to reset the password for your account. If you made this request, please click the button below to reset your password:</p>
                                <p><a href='" . $resetLink . "' class='button'>Reset Your Password</a></p>
                                <p>This will expire on: $expires_at</p>
                                <p>If you didn't request this, you can safely ignore this email.</p>
                                <div class='footer'>
                                    <p>Best regards,<br>RESERVA</p>
                                    <p>If you have any issues, please contact us at infinityfree.reserva@gmail.com.</p>
                                </div>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";

                    // Set the email headers
                    $headers = "MIME-Version: 1.0" . "\r\n";
                    $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
                    $headers .= "From: no-reply@reserva.com" . "\r\n";
                    $headers .= "Reply-To: no-reply@reserva.com" . "\r\n";

                    // Send the email using PHP mail()
                    if (mail($email, $subject, $message, $headers)) {
                        // Always display the same success message to the user
                        $response['status'] = 'success';
                        $response['message'] = "If an account with the provided email exists, we've sent a password reset link. Please check your inbox.";
                    } else {
                        $response['message'] = "There was an error sending the email. Please try again later.";
                    }
                } else {
                    $response['message'] = 'Error saving the password reset request to the database.';
                }
            }
        } else {
            // If email was not found in the database, still return the same message
            $response['message'] = "If an account with the provided email exists, we've sent a password reset link. Please check your inbox.";
        }
    } else {
        $response['message'] = "Please provide a valid email address.";
    }
} else {
    $response['message'] = "Invalid request method.";
}

// Send JSON response
echo json_encode($response);
?>
