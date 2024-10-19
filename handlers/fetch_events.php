<?php
// Start session and include the necessary database configuration
session_start();

// Include database configuration
require_once '../database/config.php';


// Query to fetch reservations for the department
$reservation_sql = "SELECT * FROM reservations
 WHERE reservation_status = 'Approved'";
$stmt = $conn->prepare($reservation_sql);
$stmt->execute();
$result = $stmt->get_result();

// Create an array to hold events
$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = [
        'title' => $row['purpose'], // Purpose as the event title
        'start' => $row['reservation_date'] . 'T' . $row['start_time'],
        'end'   => $row['reservation_date'] . 'T' . $row['end_time'],
        'description' => $row['additional_info'], // Additional info
        'status' => $row['reservation_status'],   // Reservation status
        'facility_name' => $row['facility_name'], // Facility name
        'FacultyInCharge' => $row['facultyInCharge'], // Faculty in charge
    ];
}

// Output JSON for FullCalendar
header('Content-Type: application/json');
echo json_encode($events);

?>
