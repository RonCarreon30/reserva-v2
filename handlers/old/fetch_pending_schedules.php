<?php
require '../database/config.php';

session_start();

$user_department = $_SESSION['department'] ?? 'Unknown';

// Query to fetch pending schedules
$sql = "SELECT subject_code, section, instructor, start_time, end_time, days, schedule_status 
        FROM schedules_tbl
        WHERE user_dept = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_department);
$stmt->execute();
$result = $stmt->get_result();

// Prepare data for output
$pending_schedules = [];
while ($row = $result->fetch_assoc()) {
    // Concatenate time_start and time_end as "time"
    $row['time'] = date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']));
    $pending_schedules[] = $row;
}

$stmt->close();
$conn->close();

// Output the schedules as JSON
header('Content-Type: application/json');
echo json_encode($pending_schedules);
?>
