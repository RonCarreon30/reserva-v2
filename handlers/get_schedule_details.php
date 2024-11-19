<?php
require '../database/config.php';

if (isset($_GET['schedule_id'])) {
    $scheduleId = intval($_GET['schedule_id']);

    $query = "
        SELECT 
            schedules.schedule_id, 
            schedules.subject_code, 
            schedules.section,
            schedules.instructor, 
            schedules.start_time, 
            schedules.end_time, 
            schedules.days, 
            room_assignments_tbl.assignment_id,  -- Include assignment_id
            rooms_tbl.room_name, 
            buildings_tbl.building_name
        FROM schedules
        LEFT JOIN room_assignments_tbl ON schedules.schedule_id = room_assignments_tbl.schedule_id
        LEFT JOIN rooms_tbl ON room_assignments_tbl.room_id = rooms_tbl.room_id
        LEFT JOIN buildings_tbl ON rooms_tbl.building_id = buildings_tbl.building_id
        WHERE schedules.schedule_id = ?
    ";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $scheduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        if ($data) {
            echo json_encode($data);
        } else {
            echo json_encode(['error' => 'No schedule found']);
        }
    } else {
        echo json_encode(['error' => 'Failed to prepare statement']);
    }
} else {
    echo json_encode(['error' => 'No schedule ID provided']);
}
?>
