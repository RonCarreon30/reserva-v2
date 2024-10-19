<?php
include '../database/config.php'; // Include your database connection

$roomId = $_GET['room_id'];
$response = [];

// Fetch assigned schedules for the specified room, including instructor and section
$query = "
    SELECT 
        s.days, 
        TIME_FORMAT(s.start_time, '%h:%i %p') AS start_time,  /* Convert to 12-hour format with AM/PM */
        TIME_FORMAT(s.end_time, '%h:%i %p') AS end_time,      /* Convert to 12-hour format */
        s.subject_code,
        s.section,          /* Column for section */
        s.instructor        /* Column for instructor */
    FROM assigned_rooms_tbl ar
    JOIN schedules_tbl s ON ar.schedule_id = s.schedule_id 
    WHERE ar.room_id = ? 
    AND s.schedule_status = 'Scheduled'
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $roomId); // Assuming room_id is an integer
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }
} else {
    $response['error'] = "No schedules found for the selected room.";
}

// Close connection
$stmt->close();
$conn->close();

echo json_encode($response);
?>
