<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: ../index.html");
    exit();
}

// Include database configuration
require_once '../database/config.php';

// Retrieve the selected room, building, and academic year from the request
$roomId = isset($_GET['roomId']) ? $_GET['roomId'] : '';
$buildingId = isset($_GET['buildingId']) ? $_GET['buildingId'] : '';
$ayId = isset($_GET['ayId']) ? $_GET['ayId'] : '';

if (empty($roomId) || empty($buildingId) || empty($ayId)) {
    echo json_encode([]);
    exit();
}

// Fetch schedules based on selected filters
$query = "
    SELECT 
        schedules.*, 
        room_assignments_tbl.assignment_id, 
        rooms_tbl.room_number, 
        rooms_tbl.room_type, 
        rooms_tbl.building_id, 
        buildings_tbl.building_name, 
        buildings_tbl.building_desc,
        terms_tbl.academic_year,
        terms_tbl.semester
    FROM schedules
    LEFT JOIN room_assignments_tbl ON schedules.schedule_id = room_assignments_tbl.schedule_id
    LEFT JOIN rooms_tbl ON room_assignments_tbl.room_id = rooms_tbl.room_id
    LEFT JOIN buildings_tbl ON rooms_tbl.building_id = buildings_tbl.building_id
    LEFT JOIN terms_tbl ON schedules.ay_semester = terms_tbl.term_id
    WHERE room_assignments_tbl.room_id = ? 
    AND rooms_tbl.building_id = ? 
    AND schedules.ay_semester = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('iii', $roomId, $buildingId, $ayId);
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];

while ($row = $result->fetch_assoc()) {
    // Convert start_time and end_time to 12-hour format with AM/PM
    $startTime = new DateTime($row['start_time']);
    $endTime = new DateTime($row['end_time']);
    
    $schedules[] = [
        'id' => $row['schedule_id'],
        'title' => $row['subject_code'],
        'start' => $startTime->format('h:i A'), // Format to 12-hour with AM/PM
        'end' => $endTime->format('h:i A'), // Format to 12-hour with AM/PM
        'days' => $row['days'],
        'instructor' => $row['instructor'],
        'section' => $row['section'],
        'extendedProps' => [
            'room' => $row['room_number'],
            'building' => $row['building_name'],
            'AcademicYear' =>  $row['academic_year'],
            'semester' =>  $row['semester']
        ]
    ];
}

// Send the data as JSON to be used by FullCalendar
echo json_encode($schedules);
?>
