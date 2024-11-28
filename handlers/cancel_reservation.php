<?php
session_start();
require_once '../database/config.php'; // Include database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$reservationId = $data['id'];

if (!$reservationId) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation ID.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role']; // Assuming the role is stored in session

// Fetch user details
$userQuery = "SELECT first_name, last_name, email FROM users WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userStmt->bind_result($first_name, $last_name, $user_email);
$userStmt->fetch();
$userStmt->close();

if (empty($first_name) || empty($last_name) || empty($user_email)) {
    echo json_encode(array("success" => false, "message" => "Failed to fetch user details."));
    exit();
}

$user_name = "$first_name $last_name";

// Fetch GSO email (assuming Facility Head or Admin is considered GSO)
$gsoQuery = "SELECT email FROM users WHERE userRole = 'Facility Head' LIMIT 1";
$gsoStmt = $conn->prepare($gsoQuery);
$gsoStmt->execute();
$gsoStmt->bind_result($gso_email);
$gsoStmt->fetch();
$gsoStmt->close();

if (empty($gso_email)) {
    echo json_encode(array("success" => false, "message" => "Failed to fetch GSO email."));
    exit();
}

try {
    // Fetch reservation details with facility name
    $reservationQuery = "
        SELECT r.id, r.user_id, r.facility_id, r.reservation_date, r.start_time, r.end_time, r.purpose, f.facility_name
        FROM reservations r
        JOIN facilities f ON r.facility_id = f.facility_id
        WHERE r.id = ?
    ";
    $reservationStmt = $conn->prepare($reservationQuery);
    $reservationStmt->bind_param("i", $reservationId);
    $reservationStmt->execute();
    $reservationStmt->bind_result($reservation_id, $res_user_id, $facility_id, $reservation_date, $start_time, $end_time, $purpose, $facility_name);
    $reservationStmt->fetch();
    $reservationStmt->close();

    if (empty($facility_name) || empty($reservation_date) || empty($start_time) || empty($end_time) || empty($purpose)) {
        echo json_encode(array("success" => false, "error" => "Failed to fetch reservation details."));
        exit();
    }

    // Check if the user trying to cancel is the same as the user who made the reservation (unless they are Admin or Facility Head)
    if ($user_id != $res_user_id && !in_array($user_role, ['Facility Head', 'Admin'])) {
        echo json_encode(array("success" => false, "message" => "You do not have permission to cancel this reservation. Only the admins and the user who made the reservation can perform the cancellation."));
        exit();
    }

    // Update reservation status to 'Cancelled'
    $stmt = $conn->prepare("UPDATE reservations SET reservation_status = 'Cancelled' WHERE id = ?");
    $stmt->bind_param("i", $reservationId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        // Send email notifications using mail()

        // Email to requestor (User)
        $subject = "Reservation Cancelled";
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { font-size: 18px; font-weight: bold; margin-bottom: 20px; }
                .details { margin-top: 20px; }
                .footer { margin-top: 30px; font-size: 14px; color: #555; }
            </style>
        </head>
        <body>
            <p class='header'>Dear $user_name,</p>
            <p>Your reservation for the facility <strong>$facility_name</strong> has been cancelled by an Administrator or Facility Head.</p>
            <div class='details'>
                <p><strong>Reservation Details:</strong></p>
                <ul>
                    <li><strong>Facility:</strong> $facility_name</li>
                    <li><strong>Date:</strong> $reservation_date</li>
                    <li><strong>Time:</strong> $start_time - $end_time</li>
                    <li><strong>Purpose:</strong> $purpose</li>
                </ul>
            </div>
            <p>If you have any questions, feel free to contact us.</p>
            <div class='footer'>
                <p>Best regards,</p>
                <p>RESERVA Team</p>
            </div>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: no-reply@reserva.com\r\n";
        $headers .= "Reply-To: no-reply@reserva.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        if (!mail($user_email, $subject, $message, $headers)) {
            echo json_encode(array("success" => false, "error" => "Failed to send cancellation email to user."));
            exit();
        }

        // If the user is not an Admin/Facility Head, also send email to GSO
        if (!in_array($user_role, ['Facility Head', 'Admin'])) {
            $subject_gso = "Reservation Cancellation Notification";
            $message_gso = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .header { font-size: 18px; font-weight: bold; margin-bottom: 20px; }
                    .details { margin-top: 20px; }
                    .footer { margin-top: 30px; font-size: 14px; color: #555; }
                </style>
            </head>
            <body>
                <p class='header'>Dear GSO Team,</p>
                <p>The reservation for the facility <strong>$facility_name</strong> made by <strong>$user_name</strong> has been cancelled.</p>
                <div class='details'>
                    <p><strong>Reservation Details:</strong></p>
                    <ul>
                        <li><strong>Facility:</strong> $facility_name</li>
                        <li><strong>Date:</strong> $reservation_date</li>
                        <li><strong>Time:</strong> $start_time - $end_time</li>
                        <li><strong>Purpose:</strong> $purpose</li>
                    </ul>
                </div>
                <p>For more details, please contact the user directly.</p>
                <div class='footer'>
                    <p>Best regards,</p>
                    <p>RESERVA Team</p>
                </div>
            </body>
            </html>";

            if (!mail($gso_email, $subject_gso, $message_gso, $headers)) {
                echo json_encode(array("success" => false, "error" => "Failed to send email to GSO."));
                exit();
            }
        }

        echo json_encode(['success' => true, 'message' => 'Reservation Cancelled']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Reservation not found or already cancelled.']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
