<?php
session_start();
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Check if reservation ID and status are provided
    if (!isset($data['id']) || !isset($data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing parameters']);
        exit();
    }

    // Get reservation ID and status from the request
    $reservationId = intval($data['id']);
    $status = $data['status'];
    $rejectionReason = isset($data['reason']) ? $data['reason'] : '';

    require '../database/config.php';

    // Fetch reservation details
    $query = "SELECT r.reservation_date, r.start_time, r.end_time, r.purpose, r.facultyInCharge, u.first_name, u.last_name, u.email, f.facility_name 
              FROM reservations r
              JOIN users u ON r.user_id = u.id
              JOIN facilities f ON r.facility_id = f.facility_id
              WHERE r.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $reservationId);
    $stmt->execute();
    $stmt->bind_result($reservation_date, $start_time, $end_time, $purpose, $facultyInCharge, $first_name, $last_name, $email, $facility_name);

    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Reservation not found']);
        exit();
    }
    $stmt->close();

    // Format date and time
    $formatted_date = (new DateTime($reservation_date))->format('m/d/Y');
    $formatted_start_time = (new DateTime($start_time))->format('h:i A');
    $formatted_end_time = (new DateTime($end_time))->format('h:i A');

    // Update reservation status and rejection reason in the database
    $updateStmt = $conn->prepare("UPDATE reservations SET reservation_status = ?, rejection_reason = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $status, $rejectionReason, $reservationId);

    if ($updateStmt->execute()) {
        // Send email notification if approved
        if ($status === 'Approved') {
            $user_name = "$first_name $last_name";
            $subject = "Reservation Approved: $facility_name";
            $message = "
            Dear $user_name,

            Your reservation request for the following has been approved:

            Reservation Details:
            - Facility: $facility_name
            - Date: $formatted_date
            - Time: $formatted_start_time - $formatted_end_time
            - Purpose: $purpose
            - Faculty In-Charge: $facultyInCharge

            Thank you for using RESERVA.

            Best regards,
            RESERVA Team
            ";

            // Send email using PHP mail() function
            $headers = "From: no-reply@reserva.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            if (!mail($email, $subject, $message, $headers)) {
                error_log("Failed to send approval email to $email");
            }
        } else if ($status === 'Declined') {
            $user_name = "$first_name $last_name";
            $subject = "Reservation Declined: $facility_name";
            $message = "
            Dear $user_name,

            We regret to inform you that your reservation request for the following has been declined:

            Reservation Details:
            - Facility: $facility_name
            - Date: $formatted_date
            - Time: $formatted_start_time - $formatted_end_time
            - Purpose: $purpose
            - Faculty In-Charge: $facultyInCharge

            Reason for Decline:
            $rejectionReason

            Best regards,
            RESERVA Team
            ";

            // Send email using PHP mail() function
            $headers = "From: no-reply@reserva.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            if (!mail($email, $subject, $message, $headers)) {
                error_log("Failed to send decline email to $email");
            }
        }

        http_response_code(200);
        echo json_encode(['success' => 'Reservation status updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Error updating reservation status: ' . $conn->error]);
    }

    $updateStmt->close();
    $conn->close();
} else {
    // Handle other request methods if necessary
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
?>
