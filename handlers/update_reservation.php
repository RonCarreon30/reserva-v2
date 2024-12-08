<?php
session_start();
header('Content-Type: application/json');
include '../database/config.php';

$data = json_decode(file_get_contents('php://input'), true);

// Check required fields
if (!isset($data['reservationId'], $data['facilityId'], $data['reservationDate'], $data['startTime'], $data['endTime'], $data['facultyInCharge'], $data['purpose'], $data['additionalInfo'], $data['reservationStatus'], $_SESSION['role'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

$reservationId = $data['reservationId'];
$facilityId = $data['facilityId'];
$reservationDate = $data['reservationDate'];
$startTime = DateTime::createFromFormat('h:i A', $data['startTime'])->format('H:i:s');
$endTime = DateTime::createFromFormat('h:i A', $data['endTime'])->format('H:i:s');
$facultyInCharge = $data['facultyInCharge'];
$purpose = $data['purpose'];
$additionalInfo = $data['additionalInfo'];
$reservationStatus = $data['reservationStatus'];
$userRole = $_SESSION['role']; // User role of the person editing

// Function to check if the reservation date is in the past
function isDateInPast($reservationDate) {
    // Create DateTime objects for today and the reservation date
    $today = new DateTime('today');
    $reservationDateTime = DateTime::createFromFormat('Y-m-d', $reservationDate);

    // If date creation fails, consider it invalid
    if ($reservationDateTime === false) {
        return true;
    }

    // Compare dates
    return $reservationDateTime < $today;
}

// Check for past date after the existing overlap and duplicate checks
if (isDateInPast($reservationDate)) {
    echo json_encode([
        "success" => false, 
        "message" => "Reservation date cannot be in the past."
    ]);
    exit;
}

// Function to check for reservation overlaps
function checkReservationOverlap($conn, $facilityId, $reservationDate, $startTime, $endTime, $currentReservationId = null) {
    // Check for overlapping reservations
    $overlapQuery = "SELECT id FROM reservations 
                     WHERE facility_id = ? 
                     AND reservation_date = ? 
                     AND reservation_status NOT IN ('Declined', 'Cancelled')
                     AND (
                         (? < end_time AND ? > start_time) OR 
                         (? <= end_time AND ? >= start_time) OR
                         (start_time >= ? AND start_time < ?) OR
                         (end_time > ? AND end_time <= ?)
                     )";
    
    if ($currentReservationId !== null) {
        $overlapQuery .= " AND id != ?";
    }
    
    $overlapStmt = $conn->prepare($overlapQuery);
    
    if ($currentReservationId !== null) {
        $overlapStmt->bind_param("isssssssssi", 
            $facilityId, 
            $reservationDate, 
            $startTime, $startTime,
            $endTime, $endTime,
            $startTime, $endTime,
            $startTime, $endTime,
            $currentReservationId
        );
    } else {
        $overlapStmt->bind_param("isssssssss", 
            $facilityId, 
            $reservationDate, 
            $startTime, $startTime,
            $endTime, $endTime,
            $startTime, $endTime,
            $startTime, $endTime
        );
    }
    
    $overlapStmt->execute();
    $overlapResult = $overlapStmt->get_result();
    
    return $overlapResult->num_rows > 0;
}

// Function to check for duplicate reservations
function checkDuplicateReservation($conn, $facilityId, $reservationDate, $startTime, $endTime, $facultyInCharge, $purpose, $currentReservationId = null) {
    $duplicateQuery = "SELECT id FROM reservations 
                       WHERE facility_id = ? 
                       AND reservation_date = ? 
                       AND start_time = ? 
                       AND end_time = ? 
                       AND facultyInCharge = ? 
                       AND purpose = ?
                       AND reservation_status NOT IN ('Declined', 'Cancelled')";
    
    if ($currentReservationId !== null) {
        $duplicateQuery .= " AND id != ?";
    }
    
    $duplicateStmt = $conn->prepare($duplicateQuery);
    
    if ($currentReservationId !== null) {
        $duplicateStmt->bind_param("isssssi", 
            $facilityId, 
            $reservationDate, 
            $startTime, 
            $endTime, 
            $facultyInCharge, 
            $purpose,
            $currentReservationId
        );
    } else {
        $duplicateStmt->bind_param("isssss", 
            $facilityId, 
            $reservationDate, 
            $startTime, 
            $endTime, 
            $facultyInCharge, 
            $purpose
        );
    }
    
    $duplicateStmt->execute();
    $duplicateResult = $duplicateStmt->get_result();
    
    return $duplicateResult->num_rows > 0;
}

// Check for overlapping reservations
if (checkReservationOverlap($conn, $facilityId, $reservationDate, $startTime, $endTime, $reservationId)) {
    echo json_encode([
        "success" => false, 
        "message" => "This reservation conflicts with an existing reservation for the same facility and time."
    ]);
    exit;
}

// Check for duplicate reservations
if (checkDuplicateReservation($conn, $facilityId, $reservationDate, $startTime, $endTime, $facultyInCharge, $purpose, $reservationId)) {
    echo json_encode([
        "success" => false, 
        "message" => "An identical reservation already exists."
    ]);
    exit;
}

// Fetch reservation and user details
$userQuery = "SELECT u.first_name, u.last_name, u.email AS user_email, r.facility_id, r.user_id 
              FROM reservations r 
              JOIN users u ON r.user_id = u.id 
              WHERE r.id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $reservationId);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Reservation not found."]);
    exit;
}

$userData = $userResult->fetch_assoc();
$userName = $userData['first_name'] . ' ' . $userData['last_name'];
$userEmail = $userData['user_email'];

// Fetch GSO email
$gsoQuery = "SELECT email FROM users WHERE userRole = 'Facility Head' LIMIT 1";
$gsoStmt = $conn->prepare($gsoQuery);
$gsoStmt->execute();
$gsoStmt->bind_result($gsoEmail);
$gsoStmt->fetch();
$gsoStmt->close();

// Update reservation
$updateQuery = "UPDATE reservations 
                SET facility_id = ?, reservation_date = ?, start_time = ?, end_time = ?, facultyInCharge = ?, purpose = ?, additional_info = ?, reservation_status = ? 
                WHERE id = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param(
    "isssssssi",
    $facilityId,
    $reservationDate,
    $startTime,
    $endTime,
    $facultyInCharge,
    $purpose,
    $additionalInfo,
    $reservationStatus,
    $reservationId
);

if ($updateStmt->execute()) {
    // Determine email notification logic
    if (in_array($userRole, ['Facility Head', 'Admin'])) {
        // Send email to Requester only
        $subject = "Reservation Updated by GSO";
        $message = "
        <html>
        <body>
            <p>Dear $userName,</p>
            <p>Your reservation has been updated by GSO. Don't worry, no additional steps for approval are required!</p>
            <p><strong>Reservation Details:</strong></p>
            <ul>
                <li>Date: $reservationDate</li>
                <li>Time: " . $data['startTime'] . " - " . $data['endTime'] . "</li>
                <li>Purpose: $purpose</li>
            </ul>
            <p>Thank you for using RESERVA.</p>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: no-reply@reserva.com\r\n";

        mail($userEmail, $subject, $message, $headers);

    } else {
        // Send emails to both GSO and Requester
        // Email to Requester
        $subject = "Reservation Update Confirmation";
        $message = "
        <html>
        <body>
            <p>Dear $userName,</p>
            <p>Your reservation has been updated and resubmitted for approval.</p>
            <p><strong>Reservation Details:</strong></p>
            <ul>
                <li>Date: $reservationDate</li>
                <li>Time: " . $data['startTime'] . " - " . $data['endTime'] . "</li>
                <li>Purpose: $purpose</li>
            </ul>
            <p>Thank you for using RESERVA.</p>
        </body>
        </html>";

        mail($userEmail, $subject, $message, $headers);

        // Email to GSO
        $subjectGso = "Reservation Resubmission for Approval";
        $messageGso = "
        <html>
        <body>
            <p>Dear GSO Team,</p>
            <p>A reservation has been updated and resubmitted for approval.</p>
            <p><strong>Reservation Details:</strong></p>
            <ul>
                <li>Submitted by: $userName</li>
                <li>Date: $reservationDate</li>
                <li>Time: " . $data['startTime'] . " - " . $data['endTime'] . "</li>
                <li>Purpose: $purpose</li>
            </ul>
        </body>
        </html>";

        mail($gsoEmail, $subjectGso, $messageGso, $headers);
    }

    echo json_encode(["success" => true, "message" => "Reservation updated successfully. Emails sent."]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update reservation."]);
}

$updateStmt->close();
$conn->close();
?>
