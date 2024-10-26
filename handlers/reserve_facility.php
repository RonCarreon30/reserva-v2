<?php
session_start();
error_log(print_r($_POST, true)); // Log all POST data

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form data
    if (empty($_POST['reservationDate']) || empty($_POST['startTime']) || empty($_POST['endTime']) || empty($_POST['facilityName']) || empty($_POST['department']) || empty($_POST['purpose']) || empty($_POST['facultyInCharge'])) {
        echo json_encode(array("success" => false, "error" => "All required fields must be filled."));
        exit();
    }
    $facility_id = $_POST['facilityId'];
    // Use $facilityId in your SQL INSERT statement for the reservations table.

    $reservation_date = $_POST['reservationDate'];
    // Convert start and end times to military format (24-hour format)
    $start_time = DateTime::createFromFormat('h:i A', $_POST['startTime'])->format('H:i');
    $end_time = DateTime::createFromFormat('h:i A', $_POST['endTime'])->format('H:i');

    // Convert times for checking overlapping reservations
    $startDateTime = new DateTime("$reservation_date $start_time");
    $endDateTime = new DateTime("$reservation_date $end_time");

    if ($endDateTime <= $startDateTime) {
        echo json_encode(array("success" => false, "error" => "End time must be later than start time."));
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $facultyInCharge = $_POST['facultyInCharge'];
    $purpose = $_POST['purpose'];
    $additional_info = isset($_POST['additionalInfo']) ? $_POST['additionalInfo'] : '';

    // Set reservation status based on user role
    $user_role = $_SESSION['role'];
    if ($user_role === 'Student Rep' || $user_role === 'Dept. Head') {
        $reservation_status = 'In Review';
    } elseif ($user_role === 'Admin' || $user_role === 'Facility Head') {
        $reservation_status = 'Approved';
    } else {
        echo json_encode(array("success" => false, "error" => "Unauthorized role."));
        exit();
    }


    if ($facility_id !== false) {
        include '../database/config.php';

        // Check for overlapping reservations
        $sql = "SELECT facility_id FROM reservations 
        WHERE facility_id = ? 
        AND reservation_date = ? 
        AND (
            (start_time < ? AND end_time > ?) OR
            (start_time < ? AND end_time > ?) OR
            (start_time > ? AND start_time < ?)
        )";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssss", $facilityId, $reservation_date, $start_time, $end_time, $start_time, $end_time, $start_time, $end_time);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            echo json_encode(array("success" => false, "error" => "Overlapping reservation exists"));
            exit();
        }
        $stmt->close();


        // Check for duplicate reservations
        $sql = "SELECT id FROM reservations WHERE facility_id = ? AND reservation_date = ? AND start_time = ? AND end_time = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $facility_id, $reservation_date, $start_time, $end_time);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            echo json_encode(array("success" => false, "error" => "Duplicate reservation exists"));
            exit();
        }
        $stmt->close();

        // Prepare and execute SQL statement for inserting reservation
        $sql = "INSERT INTO reservations (user_id, facility_id, reservation_date, start_time, end_time, purpose, additional_info, reservation_status, facultyInCharge) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisssssss", $user_id, $facility_id, $reservation_date, $start_time, $end_time, $purpose, $additional_info, $reservation_status, $facultyInCharge);

        if ($stmt->execute()) {
            // Prepare a dynamic success message based on user role
            $success_message = "";
            if ($user_role === 'Student Rep' || $user_role === 'Dept. Head') {
                $success_message = "Reservation Sent for Approval";
            } elseif ($user_role === 'Admin' || $user_role === 'Facility Head') {
                $success_message = "Reservation Successfully Made!";
            }

            echo json_encode(array("success" => true, "message" => $success_message));
        } else {
            echo json_encode(array("success" => false, "error" => "Error: " . $conn->error));
        }


        $stmt->close();
        $conn->close();
    } else {
        echo json_encode(array("success" => false, "error" => "Facility not found"));
    }
} else {
    echo json_encode(array("success" => false, "error" => "Invalid request method"));
}
?>
