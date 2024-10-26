<?php
// Start session and include the necessary database configuration
session_start();

// Include database configuration
require_once '../database/config.php';

// Query to fetch reservations for the department
$reservation_sql = "SELECT 
    r.*,
    f.building,
    f.facility_name
FROM
    reservations r
JOIN
    facilities f ON r.facility_id = f.facility_id
WHERE reservation_status = 'Approved'";
$stmt = $conn->prepare($reservation_sql);
$stmt->execute();
$result = $stmt->get_result();

// Create an array to hold events
$events = [];

while ($row = $result->fetch_assoc()) {
    // Convert start and end times to 12-hour format with AM/PM
    $startTime = new DateTime($row['start_time']);
    $formattedStartTime = $startTime->format('g:i A');

    $endTime = new DateTime($row['end_time']);
    $formattedEndTime = $endTime->format('g:i A');

    $events[] = [
        'title' => $row['purpose'] . ' @'. $row['facility_name'], // Purpose as the event title
        'start' => $row['reservation_date'] . 'T' . $row['start_time'],
        'end'   => $row['reservation_date'] . 'T' . $row['end_time'],
        'sTime' => $formattedStartTime,
        'eTime' => $formattedEndTime,
        'additional_info' => $row['additional_info'], // Additional info
        'status' => $row['reservation_status'],   // Reservation status
        'facility_name' => $row['facility_name'], // Facility name
        'purpose' => $row['purpose'],
        'FacultyInCharge' => $row['facultyInCharge'], // Faculty in charge
    ];
}

// Output JSON for FullCalendar
header('Content-Type: application/json');
echo json_encode($events);
?>
