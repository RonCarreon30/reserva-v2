<?php
include '../database/config.php';

header('Content-Type: application/json');

// Initialize the response
$response = [
    'status' => 'error',
    'message' => ''
];
// Fetch the user ID from the session data
$session_user_id = $_SESSION['user_id'];
// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input from the request body
    $input = json_decode(file_get_contents('php://input'), true);

    // Check if user_id is provided in the JSON input
    if (isset($input['user_id'])) {
        $user_id = $input['user_id'];
        
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // First check if there's a pending deletion request
            $check_request = $conn->prepare("SELECT id, status FROM deletion_requests WHERE user_id = ? ORDER BY request_date DESC LIMIT 1");
            $check_request->bind_param("i", $user_id);
            $check_request->execute();
            $request_result = $check_request->get_result();
            
            // Prepare email content
            $subject = "Account Deletion Confirmation";
            
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
                    .footer {
                        margin-top: 20px;
                        text-align: center;
                        font-size: 0.9em;
                        color: #6c757d;
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Account Deletion Confirmation</h2>
                    </div>
                    
                    <div class='content'>
                        <p>Dear " . htmlspecialchars($user_data['username']) . ",</p>
                        
                        <p>This email confirms that your account has been successfully deleted from our system.</p>
                        
                        <p>Important Details:</p>
                        <ul>
                            <li>Username: " . htmlspecialchars($user_data['username']) . "</li>
                            <li>Deletion Date: " . date('Y-m-d H:i:s') . "</li>
                        </ul>
                        
                        <p>If you believe this was done in error, please contact our support team immediately.</p>
                    </div>
                    
                    <div class='footer'>
                        <p>This is an automated message. Please do not reply to this email.</p>
                        <p>© " . date('Y') . " Your Organization Name. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";
            
            // Plain text version
            $plain_message = "Account Deletion Confirmation\n\n" .
                           "Dear " . $user_data['username'] . ",\n\n" .
                           "This email confirms that your account has been successfully deleted from our system.\n\n" .
                           "Important Details:\n" .
                           "- Username: " . $user_data['username'] . "\n" .
                           "- Deletion Date: " . date('Y-m-d H:i:s') . "\n\n" .
                           "If you believe this was done in error, please contact our support team immediately.\n\n" .
                           "This is an automated message. Please do not reply to this email.";
            
            // Email headers
            $headers = "From: Your Organization <no-reply@yourdomain.com>\r\n";
            $headers .= "Reply-To: no-reply@yourdomain.com\r\n";
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
            // If there's a deletion request
            if ($request_result->num_rows > 0) {
                $request_data = $request_result->fetch_assoc();
                
                // Only proceed if the request status is 'pending'
                if ($request_data['status'] === 'pending') {
                    // Delete the user
                    $delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $delete_user->bind_param("i", $user_id);
                    
                    if ($delete_user->execute()) {
                        // Update the deletion request status
                        $update_request = $conn->prepare("UPDATE deletion_requests SET status = 'deleted', processed_date = NOW(), processed_by = $session_user_id WHERE id = ?");
                        $update_request->bind_param("i", $request_data['id']);
                        
                        if ($update_request->execute()) {
                            $conn->commit();
                            $response['status'] = 'success';
                            $response['message'] = 'User deleted successfully with approved request.';
                            $response['post_data'] = ['user_id' => $user_id];
                        } else {
                            throw new Exception('Failed to update deletion request status.');
                        }
                        
                        $update_request->close();
                    } else {
                        throw new Exception('Failed to delete the user.');
                    }
                    
                    $delete_user->close();
                } else {
                    $conn->rollback();
                    $response['message'] = 'Deletion request status is not pending.';
                }
            } else {
                // No deletion request found, proceed with regular deletion
                $delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
                $delete_user->bind_param("i", $user_id);
                
                if ($delete_user->execute()) {
                    $conn->commit();
                    $response['status'] = 'success';
                    $response['message'] = 'User deleted successfully without request.';
                    $response['post_data'] = ['user_id' => $user_id];
                } else {
                    throw new Exception('Failed to delete the user.');
                }
                
                $delete_user->close();
            }
            
            $check_request->close();
            
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = 'Error: ' . $e->getMessage();
        }
        
    } else {
        $response['message'] = 'User ID not received.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

$conn->close();

// Send JSON response
echo json_encode($response);
?>