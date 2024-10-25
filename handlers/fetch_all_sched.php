<?php
session_start();

// Include database configuration
require_once '../database/config.php';

// Get parameters from the query
$roomId = isset($_GET['roomId']) ? $_GET['roomId'] : null;
$ayId = isset($_GET['ayId']) ? $_GET['ayId'] : null; // New parameter for academic year
$buildingId = isset($_GET['buildingId']) ? $_GET['buildingId'] : null; // New parameter for building

// Start the base query
$query = "
    SELECT 
        schedules.*, 
        room_assignments_tbl.assignment_id, 
        rooms_tbl.room_number, 
        rooms_tbl.room_type, 
        rooms_tbl.building_id, 
        buildings_tbl.building_name, 
        buildings_tbl.building_desc
    FROM schedules
    LEFT JOIN room_assignments_tbl ON schedules.schedule_id = room_assignments_tbl.schedule_id
    LEFT JOIN rooms_tbl ON room_assignments_tbl.room_id = rooms_tbl.room_id
    LEFT JOIN buildings_tbl ON rooms_tbl.building_id = buildings_tbl.building_id
    WHERE 1=1
"; // Added 'WHERE 1=1' for easier appending of conditions

// Add conditions based on the received parameters
$params = [];
$types = "";

// Only add roomId to query if it's not null
if ($roomId) {
    $query .= " AND rooms_tbl.room_id = ?";
    $params[] = $roomId;
    $types .= "i"; // Assuming room_id is an integer
}

// Add ayId condition
if ($ayId) {
    $query .= " AND schedules.ay_semester = ?";
    $params[] = $ayId;
    $types .= "i"; // Assuming academic_year_id is an integer
}

// Add buildingId condition
if ($buildingId) {
    $query .= " AND rooms_tbl.building_id = ?";
    $params[] = $buildingId;
    $types .= "i"; // Assuming building_id is an integer
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
$startDate = (clone $today)->modify('-1 week'); // Change to '-1 week' if you want it to show only a week before

while ($row = $result->fetch_assoc()) {
    // Split the days into an array
    $daysArray = explode(',', $row['days']);

    foreach ($daysArray as $day) {
        $day = trim($day); // Remove any whitespace
        $dayIndex = array_search($day, $daysOfWeek);

        if ($dayIndex !== false) {
            // Now calculate occurrences from the start date (past and future)
            for ($i = 0; $i < 8; $i++) { // Loop through 8 weeks (4 weeks back and 4 forward)
                // Calculate the date by starting from $startDate and moving through weeks
                $nextDate = clone $startDate;
                $nextDate->modify("+$i week");

                // Find the next occurrence of the day
                if ($nextDate->format('l') !== $day) {
                    $nextDate->modify("next " . $day);
                }

                $startDateTime = $nextDate->format('Y-m-d') . ' ' . $row['start_time'];
                $endDateTime = $nextDate->format('Y-m-d') . ' ' . $row['end_time'];

                // Add instructor and section to the event details
                $events[] = [
                    'title' => $row['subject_code'], // Subject code
                    'start' => $startDateTime,        // Start datetime
                    'end' => $endDateTime,            // End datetime
                    'days' => $row['days'],
                    'section' => $row['section'],     // Course section
                    'instructor' => $row['instructor'], // Instructor name
                    'extendedProps' => [
                        'room' => $row['room_number'],
                        'building' => $row['building_name']
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
