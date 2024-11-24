<?php
session_start();

// Include database configuration
require_once '../database/config.php';

// Get parameters from the query
$roomId = isset($_GET['roomId']) ? $_GET['roomId'] : null;
$ayId = isset($_GET['ayId']) ? $_GET['ayId'] : null;
$buildingId = isset($_GET['buildingId']) ? $_GET['buildingId'] : null;
$section = isset($_GET['section']) ? $_GET['section'] : null;
$instructor = isset($_GET['instructor']) ? $_GET['instructor'] : null;

// Start the base query
$query = "
    SELECT 
        schedules.*, 
        room_assignments_tbl.assignment_id, 
        rooms_tbl.room_name, 
        rooms_tbl.room_type, 
        rooms_tbl.building_id, 
        buildings_tbl.building_name, 
        buildings_tbl.building_desc
    FROM schedules
    LEFT JOIN room_assignments_tbl ON schedules.schedule_id = room_assignments_tbl.schedule_id
    LEFT JOIN rooms_tbl ON room_assignments_tbl.room_id = rooms_tbl.room_id
    LEFT JOIN buildings_tbl ON rooms_tbl.building_id = buildings_tbl.building_id
    WHERE 1=1
";

// Add conditions based on the received parameters
$params = [];
$types = "";

// Add ayId condition (always required)
if ($ayId) {
    $query .= " AND schedules.ay_semester = ?";
    $params[] = $ayId;
    $types .= "i";
}

// Handle different filter types
if ($section) {
    // Filter by section
    $query .= " AND schedules.section = ?";
    $params[] = $section;
    $types .= "s";
} else if ($instructor) {
    // Filter by instructor
    $query .= " AND schedules.instructor = ?";
    $params[] = $instructor;
    $types .= "s";
} else if ($roomId && $buildingId) {
    // Filter by room and building
    $query .= " AND rooms_tbl.room_id = ? AND rooms_tbl.building_id = ?";
    $params[] = $roomId;
    $params[] = $buildingId;
    $types .= "ii";
}

// Prepare and execute the query
$stmt = $conn->prepare($query);

// Only bind params if there are any
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$events = [];
$daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Get the current date and set the start date to 1 week ago
$today = new DateTime(); 
$startDate = (clone $today)->modify('-1 week');

while ($row = $result->fetch_assoc()) {
    // Split the days into an array
    $daysArray = explode(',', $row['days']);

    foreach ($daysArray as $day) {
        $day = trim($day);
        $dayIndex = array_search($day, $daysOfWeek);

        if ($dayIndex !== false) {
            for ($i = 0; $i < 8; $i++) {
                $nextDate = clone $startDate;
                $nextDate->modify("+$i week");

                if ($nextDate->format('l') !== $day) {
                    $nextDate->modify("next " . $day);
                }

                $startDateTime = $nextDate->format('Y-m-d') . ' ' . $row['start_time'];
                $endDateTime = $nextDate->format('Y-m-d') . ' ' . $row['end_time'];

                $events[] = [
                    'title' => $row['subject_code'],
                    'start' => $startDateTime,
                    'end' => $endDateTime,
                    'days' => $row['days'],
                    'section' => $row['section'],
                    'instructor' => $row['instructor'],
                    'extendedProps' => [
                        'room' => $row['room_name'] ?? 'No Room',
                        'building' => $row['building_name'] ?? 'No Building',
                        'section' => $row['section'],
                        'instructor' => $row['instructor']
                    ]
                ];
            }
        }
    }
}

header('Content-Type: application/json');
echo json_encode($events);

$stmt->close();
$conn->close();
?>