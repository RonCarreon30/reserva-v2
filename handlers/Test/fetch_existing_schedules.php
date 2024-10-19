<?php
header('Content-Type: application/json');

include '../database/config.php';
// SQL to fetch existing schedules with room information
$sql = "
SELECT s.schedule_id, s.start_time, s.end_time, s.days, r.room_id, r.room_number
FROM assigned_rooms_tbl ar
JOIN schedules_tbl s ON ar.schedule_id = s.schedule_id
JOIN rooms r ON ar.room_id = r.room_id
";

$result = $conn->query($sql);

$existingSchedules = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $existingSchedules[] = [
            'schedule_id' => $row['schedule_id'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'days' => $row['days'],
            'room_id' => $row['room_id'],
            'room_number' => $row['room_number'],
        ];
    }
}

$conn->close();

echo json_encode([
    'success' => true,
    'schedules' => $existingSchedules,
]);
?>
