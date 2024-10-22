<?php
session_start();

// Connect to the database
$servername = "localhost";
$username = "root";
$db_password = "";
$dbname = "reservadb";

$conn = new mysqli($servername, $username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get parameters from the query
$roomId = isset($_GET['roomId']) ? $_GET['roomId'] : null;
$ayId = isset($_GET['ayId']) ? $_GET['ayId'] : null; // New parameter for academic year
$buildingId = isset($_GET['buildingId']) ? $_GET['buildingId'] : null; // New parameter for building

// Start the base query
$query = "
    SELECT 
        s.days, 
        s.start_time, 
        s.end_time, 
        s.subject_code,
        s.section,          /* Column for section */
        s.instructor        /* Column for instructor */
    FROM assigned_rooms_tbl ar
    JOIN schedules_tbl s ON ar.schedule_id = s.schedule_id 
    JOIN rooms_tbl r ON ar.room_id = r.room_id
    WHERE ar.room_id = ? 
    AND s.schedule_status = 'Scheduled'
";

// Add conditions based on the received parameters
$params = [];
$types = "i"; // For room_id, which is an integer
$params[] = $roomId;

if ($ayId) {
    $query .= " AND s.term_id = ?"; // Adjust the column name based on your actual DB schema
    $params[] = $ayId;
    $types .= "i"; // Assuming academic_year_id is an integer
}

if ($buildingId) {
    $query .= " AND r.building_id = ?"; // Adjust the column name based on your actual DB schema
    $params[] = $buildingId;
    $types .= "i"; // Assuming building_id is an integer
}

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
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
                    'section' => $row['section'],     // Course section
                    'instructor' => $row['instructor'] // Instructor name
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
