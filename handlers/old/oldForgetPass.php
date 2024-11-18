@ -0,0 +1,140 @@
<?php
date_default_timezone_set('Asia/Manila');

// Include necessary files
require_once '../database/config.php'; // Adjust the path if needed
require '../vendor/autoload.php'; // Include Composer's autoloader

header('Content-Type: application/json');
// Initialize response
$response = [
    'status' => 'error',
    'message' => ''
];
// Get the email from POST request
$email = isset($_POST['email']) ? $_POST['email'] : '';

// Check if the email is provided and not empty
if (!empty($email)) {
    // Check if the email exists in the database
    $query = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $user_id = $user['id'];

        // Check if a non-expired reset request already exists
        $query = "SELECT * FROM password_resets WHERE user_id = ? AND expires_at >= NOW()";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            // A valid token already exists
            $response = [
                'success' => false,
                'message' => "A password reset link has already been sent. Please wait until the current request expires before requesting a new one."
            ];
        } else {
            // Generate a new token and expiry time
            $token = bin2hex(random_bytes(16));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Insert the token into the password_resets table
            $query = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iss', $user_id, $token, $expires_at);
            $stmt->execute();

            // Generate the reset password link
            $resetLink = "https://reserva.infinityfreeapp.com/reserva/reset-password?token=" . $token;

            // Set up PHPMailer
            $mail = new PHPMailer\PHPMailer\PHPMailer();
        try {
            // Convert to Month date, Year HH:MM AM/PM format
            $date = new DateTime($expires_at);
            $formatted_date = $date->format('F j, Y h:i A'); // Format: Month date, Year HH:MM AM/PM
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Use your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'infinityfree.reserva@gmail.com'; // Your SMTP username (e.g., Gmail)
            $mail->Password = 'bxzi apyp cvtn kkpj'; // Your SMTP password (use app-specific password for Gmail)
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('no-reply@reserva.com', 'RESERVA');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "
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
                        <p>This will expire on: $formatted_date</p>
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

            // Send the email
            $mail->send();

                $response = [
                    'success' => true,
                    'message' => "If an account with the provided email exists, we've sent a password reset link. Please check your inbox."
                ];
            } catch (Exception $e) {
                $response = [
                    'success' => false,
                    'message' => "There was an error sending the email. Please try again later.",
                    'error' => $mail->ErrorInfo
                ];
            }
        }
    } else {
        // Email does not exist
        $response = [
            'success' => false,
            'message' => "No account found with this email address."
        ];
    }
} else {
    // Email not provided
    $response = [
        'success' => false,
        'message' => "Please provide a valid email address."
    ];
}

echo json_encode($response);
?>