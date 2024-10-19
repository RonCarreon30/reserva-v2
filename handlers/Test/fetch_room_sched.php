<?php
// get_all_schedules.php
header('Content-Type: application/json');

// Database credentials
$servername = "localhost";
$username = "root";
$db_password = "";
$dbname = "reservadb";

// Create connection
$conn = new mysqli($servername, $username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to get all schedules that are marked as scheduled
$query = "
    SELECT subject, start_time, end_time
    FROM schedules_tbl
    WHERE is_scheduled = 1
";

// Execute the query
$result = $conn->query($query);

// Check if the query was successful
if (!$result) {
    die("Query failed: " . $conn->error);
}

$schedules = [];
while ($row = $result->fetch_assoc()) {
    $schedules[] = [
        'subject' => $row['subject'],
        'start' => $row['start_time'], // No conversion, directly from database
        'end' => $row['end_time'] // No conversion, directly from database
    ];
}

// Return schedules in JSON format
echo json_encode(['schedules' => $schedules]);

// Close the connection
$conn->close();
?>
