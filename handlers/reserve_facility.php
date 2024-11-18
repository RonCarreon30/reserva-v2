<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST['reservationDate']) || empty($_POST['startTime']) || empty($_POST['endTime']) || empty($_POST['facilityName']) || empty($_POST['department']) || empty($_POST['purpose']) || empty($_POST['facultyInCharge'])) {
        echo json_encode(array("success" => false, "error" => "All required fields must be filled."));
        exit();
    }

    include '../database/config.php';

    $facility_id = $_POST['facilityId'];
    $facility_name = $_POST['facilityName'];
    $reservation_date = $_POST['reservationDate'];
    $start_time = DateTime::createFromFormat('h:i A', $_POST['startTime'])->format('H:i');
    $end_time = DateTime::createFromFormat('h:i A', $_POST['endTime'])->format('H:i');
    $user_id = $_SESSION['user_id'];
    $facultyInCharge = $_POST['facultyInCharge'];
    $purpose = $_POST['purpose'];
    $additional_info = isset($_POST['additionalInfo']) ? $_POST['additionalInfo'] : '';

    $startDateTime = new DateTime("$reservation_date $start_time");
    $endDateTime = new DateTime("$reservation_date $end_time");

    if ($endDateTime <= $startDateTime) {
        echo json_encode(array("success" => false, "error" => "End time must be later than start time."));
        exit();
    }

    // Fetch user details
    $userQuery = "SELECT first_name, last_name, email FROM users WHERE id = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $userStmt->bind_result($first_name, $last_name, $user_email);
    $userStmt->fetch();
    $userStmt->close();

    if (empty($first_name) || empty($last_name) || empty($user_email)) {
        echo json_encode(array("success" => false, "error" => "Failed to fetch user details."));
        exit();
    }

    $user_name = "$first_name $last_name";

    // Fetch GSO email
    $gsoQuery = "SELECT email FROM users WHERE userRole = 'Facility Head' LIMIT 1";
    $gsoStmt = $conn->prepare($gsoQuery);
    $gsoStmt->execute();
    $gsoStmt->bind_result($gso_email);
    $gsoStmt->fetch();
    $gsoStmt->close();

    if (empty($gso_email)) {
        echo json_encode(array("success" => false, "error" => "Failed to fetch GSO email."));
        exit();
    }

    $user_role = $_SESSION['role'];
    $reservation_status = ($user_role === 'Student Rep' || $user_role === 'Dept. Head') ? 'In Review' : 'Approved';

    // Check for overlapping reservations
    $overlapCheckSQL = "SELECT reservation_date, start_time, end_time FROM reservations 
        WHERE facility_id = ? 
        AND reservation_date = ? 
        AND (
            (start_time < ? AND end_time > ?) OR
            (start_time < ? AND end_time > ?) OR
            (start_time >= ? AND start_time < ?)
        )";

    $overlapStmt = $conn->prepare($overlapCheckSQL);
    $overlapStmt->bind_param("isssssss", $facility_id, $reservation_date, $end_time, $start_time, $start_time, $end_time, $start_time, $end_time);
    $overlapStmt->execute();
    $overlapStmt->store_result();

    if ($overlapStmt->num_rows > 0) {
        echo json_encode(array("success" => false, "error" => "The selected time overlaps with a reservation. Please select a different time slot!"));
        exit();
    }
    $overlapStmt->close();

    // Prepare and execute SQL statement for inserting reservation
    $insertSQL = "INSERT INTO reservations (user_id, facility_id, reservation_date, start_time, end_time, purpose, additional_info, reservation_status, facultyInCharge) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSQL);
    $insertStmt->bind_param("iisssssss", $user_id, $facility_id, $reservation_date, $start_time, $end_time, $purpose, $additional_info, $reservation_status, $facultyInCharge);

    if ($insertStmt->execute()) {
        // Send email notifications using mail()

        // Email to requestor (User)
        $subject = "Reservation Request Confirmation";
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
            <p>Your reservation request has been successfully submitted and is currently <strong>awaiting approval</strong>.</p>
            <div class='details'>
                <p><strong>Reservation Details:</strong></p>
                <ul>
                    <li><strong>Facility:</strong> $facility_name</li>
                    <li><strong>Date:</strong> $reservation_date</li>
                    <li><strong>Time:</strong> {$_POST['startTime']} - {$_POST['endTime']}</li>
                    <li><strong>Purpose:</strong> $purpose</li>
                </ul>
            </div>
            <p>Thank you for using RESERVA. If you have any questions, feel free to contact us.</p>
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

        if (mail($user_email, $subject, $message, $headers)) {
            // Email to GSO
            $subject_gso = "New Reservation Request from $user_name";
            $message_gso = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .header { font-size: 18px; font-weight: bold; margin-bottom: 20px; }
                    .details { margin-top: 20px; }
                    .footer { margin-top: 30px; font-size: 14px; color: #555; }
                    a { color: #1a73e8; text-decoration: none; }
                </style>
            </head>
            <body>
                <p class='header'>Dear GSO Team,</p>
                <p>A new reservation request has been submitted by <strong>$user_name</strong>.</p>
                <div class='details'>
                    <p><strong>Reservation Details:</strong></p>
                    <ul>
                        <li><strong>Facility:</strong> $facility_name</li>
                        <li><strong>Date:</strong> $reservation_date</li>
                        <li><strong>Time:</strong> {$_POST['startTime']} - {$_POST['endTime']}</li>
                        <li><strong>Purpose:</strong> $purpose</li>
                    </ul>
                </div>
                <p>To review the request, please <a href='http://localhost/reserva-v2/facilityReservations.php'>click here</a>.</p>
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
        } else {
            echo json_encode(array("success" => false, "error" => "Failed to send confirmation email."));
            exit();
        }

        $success_message = ($user_role === 'Student Rep' || $user_role === 'Dept. Head') ? "Reservation Sent for Approval" : "Reservation Successfully Made!";
        echo json_encode(array("success" => true, "message" => $success_message));
    } else {
        echo json_encode(array("success" => false, "error" => "Failed to make reservation."));
    }

    $insertStmt->close();
    $conn->close();
}
?>
