<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate required fields
    $missingFields = [];

    // Define required fields
    $requiredFields = [
        'reservationDate' => 'Reservation Date',
        'startTime' => 'Start Time',
        'endTime' => 'End Time', 
        'facilityName' => 'Facility Name',
        'department' => 'Department',
        'purpose' => 'Purpose',
        'facultyInCharge' => 'Faculty in Charge'
    ];

    // Check each required field
    foreach ($requiredFields as $field => $fieldLabel) {
        // Trim white space and check if field is empty
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $missingFields[] = $fieldLabel;
        }
    }

    // If any fields are missing, return error details
    if (!empty($missingFields)) {
        $response = [
            "success" => false,
            "error" => "Some required fields are missing.",
            "missingFields" => $missingFields
        ];
        
        // Optional: Log the missing fields for debugging
        var_dump("Missing fields: " . implode(", ", $missingFields));
        
        echo json_encode($response);
        exit();
    
    }

    include '../database/config.php';

    // Begin transaction to prevent race conditions
    $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

    try {
        // Sanitize and prepare input data
        $facility_id = intval($_POST['facilityId']);
        $facility_name = $conn->real_escape_string($_POST['facilityName']);
        $reservation_date = $conn->real_escape_string($_POST['reservationDate']);
        
        // Convert times to 24-hour format
        $start_time = DateTime::createFromFormat('h:i A', $_POST['startTime'])->format('H:i');
        $end_time = DateTime::createFromFormat('h:i A', $_POST['endTime'])->format('H:i');
        
        $user_id = intval($_SESSION['user_id']);
        $facultyInCharge = $conn->real_escape_string($_POST['facultyInCharge']);
        $purpose = $conn->real_escape_string($_POST['purpose']);
        $additional_info = isset($_POST['additionalInfo']) ? $conn->real_escape_string($_POST['additionalInfo']) : '';

        // Validate time range
        $startDateTime = new DateTime("$reservation_date $start_time");
        $endDateTime = new DateTime("$reservation_date $end_time");

        if ($endDateTime <= $startDateTime) {
            throw new Exception("End time must be later than start time.");
        }

        // Fetch user details with prepared statement
        $userQuery = "SELECT first_name, last_name, email FROM users WHERE id = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param("i", $user_id);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult->num_rows === 0) {
            throw new Exception("Failed to fetch user details.");
        }
        
        $userData = $userResult->fetch_assoc();
        $user_name = $userData['first_name'] . ' ' . $userData['last_name'];
        $user_email = $userData['email'];
        $userStmt->close();

        // Fetch GSO email
        $gsoQuery = "SELECT email FROM users WHERE userRole = 'Facility Head' LIMIT 1";
        $gsoStmt = $conn->prepare($gsoQuery);
        $gsoStmt->execute();
        $gsoResult = $gsoStmt->get_result();
        
        if ($gsoResult->num_rows === 0) {
            throw new Exception("Failed to fetch GSO email.");
        }
        
        $gsoData = $gsoResult->fetch_assoc();
        $gso_email = $gsoData['email'];
        $gsoStmt->close();

        // Determine reservation status based on user role
        $user_role = $_SESSION['role'];
        $reservation_status = (in_array($user_role, ['Admin', 'Facility Head'])) 
            ? 'Approved' 
            : 'In Review';

// Comprehensive duplicate and overlap check with row-level locking
$duplicateCheckSQL = "SELECT id FROM reservations 
    WHERE facility_id = ? 
    AND reservation_date = ? 
    AND start_time = ? 
    AND end_time = ? 
    AND (
        reservation_status = 'Approved' OR 
        reservation_status = 'In Review'
    )
    FOR UPDATE";

$duplicateStmt = $conn->prepare($duplicateCheckSQL);
$duplicateStmt->bind_param("isss", 
    $facility_id, 
    $reservation_date, 
    $start_time, 
    $end_time
);
$duplicateStmt->execute();
$duplicateResult = $duplicateStmt->get_result();

if ($duplicateResult->num_rows > 0) {
    throw new Exception("An identical reservation already exists or is pending approval.");
}
$duplicateStmt->close();

// Keep the existing overlap check as well
$overlapCheckSQL = "SELECT id FROM reservations 
    WHERE facility_id = ? 
    AND reservation_status = 'Approved'
    AND reservation_date = ? 
    AND (
        (start_time < ? AND end_time > ?) OR  -- New reservation overlaps existing start
        (start_time >= ? AND start_time < ?) OR  -- New reservation starts during existing
        (end_time > ? AND end_time <= ?) OR  -- New reservation ends during existing
        (start_time = ? AND end_time = ?)  -- Exact same time slot
    ) 
    FOR UPDATE";  // Lock rows to prevent concurrent modifications

$overlapStmt = $conn->prepare($overlapCheckSQL);
$overlapStmt->bind_param("isssssssss", 
    $facility_id, $reservation_date, 
    $end_time, $start_time, 
    $start_time, $end_time, 
    $start_time, $end_time,
    $start_time, $end_time
);
$overlapStmt->execute();
$overlapResult = $overlapStmt->get_result();

if ($overlapResult->num_rows > 0) {
    throw new Exception("The selected time overlaps with an existing approved reservation. Please select a different time slot!");
}
$overlapStmt->close();

        // Prepare and execute reservation insertion
        $insertSQL = "INSERT INTO reservations (
            user_id, facility_id, reservation_date, start_time, end_time, 
            purpose, additional_info, reservation_status, facultyInCharge
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSQL);
        $insertStmt->bind_param(
            "iisssssss", 
            $user_id, $facility_id, $reservation_date, $start_time, $end_time, 
            $purpose, $additional_info, $reservation_status, $facultyInCharge
        );

        if (!$insertStmt->execute()) {
            throw new Exception("Failed to make reservation: " . $insertStmt->error);
        }
        $insertStmt->close();

        // Prepare email headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: no-reply@reserva.com\r\n";
        $headers .= "Reply-To: no-reply@reserva.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Email to requestor
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

        // Send user confirmation email
        if (!mail($user_email, $subject, $message, $headers)) {
            throw new Exception("Failed to send confirmation email to user.");
        }

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

        // Send GSO notification email
        if (!mail($gso_email, $subject_gso, $message_gso, $headers)) {
            throw new Exception("Failed to send email to GSO.");
        }

        // Commit the transaction
        $conn->commit();

        // Prepare success response
        $success_message = ($user_role === 'Student Rep' || $user_role === 'Dept. Head') 
            ? "Reservation Sent for Approval" 
            : "Reservation Successfully Made!";
        
        echo json_encode(array("success" => true, "message" => $success_message));

    } catch (Exception $e) {
        // Rollback the transaction in case of any error
        $conn->rollback();
        
        // Log the error (recommend implementing proper error logging)
        error_log("Reservation Error: " . $e->getMessage());
        
        // Return error to client
        echo json_encode(array("success" => false, "error" => $e->getMessage()));
    } finally {
        // Ensure auto-commit is restored
        $conn->autocommit(true);
        
        // Close database connection
        $conn->close();
    }
    exit();
}
?>