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
    $reservation_date = $_POST['reservationDate'];
    $start_time = DateTime::createFromFormat('h:i A', $_POST['startTime'])->format('H:i');
    $end_time = DateTime::createFromFormat('h:i A', $_POST['endTime'])->format('H:i');
    $user_id = $_SESSION['user_id'];
    $facultyInCharge = $_POST['facultyInCharge'];
    $purpose = $_POST['purpose'];
    $additional_info = isset($_POST['additionalInfo']) ? $_POST['additionalInfo'] : '';

    // Convert times for checking overlapping reservations
    $startDateTime = new DateTime("$reservation_date $start_time");
    $endDateTime = new DateTime("$reservation_date $end_time");

    if ($endDateTime <= $startDateTime) {
        echo json_encode(array("success" => false, "error" => "End time must be later than start time."));
        exit();
    }

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

    include '../database/config.php';

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
$overlapStmt->bind_result($conflicting_date, $conflicting_start, $conflicting_end);
$overlapStmt->store_result();

if ($overlapStmt->num_rows > 0) {
    // Fetch all conflicting reservations
    $conflicting_reservations = [];
    while ($overlapStmt->fetch()) {
        $conflicting_reservations[] = [
            'date' => htmlspecialchars($conflicting_date),
            'start' => htmlspecialchars($conflicting_start),
            'end' => htmlspecialchars($conflicting_end)
        ];
    }

// Create the error message
$errorMessage = "The selected time overlaps with a reservation.<br>";
foreach ($conflicting_reservations as $reservation) {
    // Convert start and end times to 12-hour format with AM/PM
    $start_time_12hr = (new DateTime($reservation['start']))->format('h:i A');
    $end_time_12hr = (new DateTime($reservation['end']))->format('h:i A');
    $conflicting_date = $reservation['date']; // Date is already in the correct format

    $errorMessage .= "(" . $conflicting_date . " from " . $start_time_12hr . " to " . $end_time_12hr . ")<br>";
}
$errorMessage .= " Please select a different time slot!";

echo json_encode(array(
    "success" => false,
    "error" => $errorMessage
));
exit();

}

$overlapStmt->close();


    // Check for duplicate reservations
    $duplicateCheckSQL = "SELECT id FROM reservations WHERE facility_id = ? AND reservation_date = ? AND start_time = ? AND end_time = ?";
    $duplicateStmt = $conn->prepare($duplicateCheckSQL);
    $duplicateStmt->bind_param("isss", $facility_id, $reservation_date, $start_time, $end_time);
    $duplicateStmt->execute();
    $duplicateStmt->store_result();

    if ($duplicateStmt->num_rows > 0) {
        echo json_encode(array("success" => false, "error" => "Duplicate reservation exists"));
        exit();
    }
    $duplicateStmt->close();

    // Prepare and execute SQL statement for inserting reservation
    $insertSQL = "INSERT INTO reservations (user_id, facility_id, reservation_date, start_time, end_time, purpose, additional_info, reservation_status, facultyInCharge) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSQL);
    $insertStmt->bind_param("iisssssss", $user_id, $facility_id, $reservation_date, $start_time, $end_time, $purpose, $additional_info, $reservation_status, $facultyInCharge);

    if ($insertStmt->execute()) {
        $success_message = ($user_role === 'Student Rep' || $user_role === 'Dept. Head') ? "Reservation Sent for Approval" : "Reservation Successfully Made!";
        echo json_encode(array("success" => true, "message" => $success_message));
    } else {
        echo json_encode(array("success" => false, "error" => "Error: " . $conn->error));
    }

    $insertStmt->close();
    $conn->close();
} else {
    echo json_encode(array("success" => false, "error" => "Invalid request method"));
}
?>
