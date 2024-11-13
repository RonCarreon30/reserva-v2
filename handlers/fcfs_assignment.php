<?php
session_start();
$user_id = $_SESSION['user_id'];
include '../database/config.php';

$response = [
    'success' => true,
    'message' => [],
    'assignedSchedules' => [],
    'noRoomSchedules' => []
];

// Step 1: Retrieve pending schedules and their department details
$sqlPendingSchedules = "
    SELECT s.*, d.building_id, d.dept_name
    FROM schedules s
    JOIN dept_tbl d ON s.department_id = d.dept_id
    WHERE s.sched_status = 'pending' AND s.user_id = $user_id;
";
$resultPendingSchedules = $conn->query($sqlPendingSchedules);

if (!$resultPendingSchedules) {
    $response['success'] = false;
    $response['message'][] = "Error fetching pending schedules: " . $conn->error;
    echo json_encode($response);
    exit;
}

$pendingSchedules = [];
while ($row = $resultPendingSchedules->fetch_assoc()) {
    $pendingSchedules[] = $row;
}

foreach ($pendingSchedules as $schedule) {
    $scheduleId = $schedule['schedule_id'];
    $classType = $schedule['class_type'];
    $buildingId = $schedule['building_id'];
    $days = explode(',', $schedule['days']);
    $startTime = $schedule['start_time'];
    $endTime = $schedule['end_time'];
    $roomAssigned = false;

    // Step 2: Get rooms that match the schedule's class_type and building
    $sqlMatchingRooms = "
        SELECT r.*
        FROM rooms_tbl r
        WHERE r.room_type = '$classType'
        AND r.building_id = '$buildingId'
        AND r.room_status = 'Available';
    ";
    $resultMatchingRooms = $conn->query($sqlMatchingRooms);

    if (!$resultMatchingRooms) {
        $response['success'] = false;
        $response['message'][] = "Error fetching matching rooms: " . $conn->error;
        echo json_encode($response);
        exit;
    }

    $rooms = $resultMatchingRooms->fetch_all(MYSQLI_ASSOC);

    // Step 3: Check room assignments and assign if no conflicts
    foreach ($rooms as $room) {
        $roomId = $room['room_id'];
        $sqlCheckAssignment = "
            SELECT ra.*
            FROM room_assignments_tbl ra
            JOIN schedules s ON ra.schedule_id = s.schedule_id
            WHERE ra.room_id = '$roomId'
            AND s.days LIKE '%" . implode("%' OR s.days LIKE '%", $days) . "%'
            AND ('$startTime' < s.end_time AND '$endTime' > s.start_time);
        ";
        $resultCheckAssignment = $conn->query($sqlCheckAssignment);

        if ($resultCheckAssignment && $resultCheckAssignment->num_rows == 0) {
            $sqlAssignRoom = "
                INSERT INTO room_assignments_tbl (room_id, schedule_id)
                VALUES ('$roomId', '$scheduleId');
            ";
            if ($conn->query($sqlAssignRoom)) {
                $roomAssigned = true;
                $response['assignedSchedules'][] = $scheduleId;
                break;
            }
        }
    }

    // Step 4: If no room found, search for rooms in other buildings
    if (!$roomAssigned) {
        $sqlAlternateRooms = "
            SELECT r.*
            FROM rooms_tbl r
            WHERE r.room_type = '$classType'
            AND r.building_id != '$buildingId'
            AND r.room_status = 'Available';
        ";
        $resultAlternateRooms = $conn->query($sqlAlternateRooms);

        if (!$resultAlternateRooms) {
            $response['success'] = false;
            $response['message'][] = "Error fetching alternate rooms: " . $conn->error;
            echo json_encode($response);
            exit;
        }

        $alternateRooms = $resultAlternateRooms->fetch_all(MYSQLI_ASSOC);

        foreach ($alternateRooms as $room) {
            $roomId = $room['room_id'];
            $sqlCheckAlternateAssignment = "
                SELECT ra.*
                FROM room_assignments_tbl ra
                JOIN schedules s ON ra.schedule_id = s.schedule_id
                WHERE ra.room_id = '$roomId'
                AND s.days LIKE '%" . implode("%' OR s.days LIKE '%", $days) . "%'
                AND ('$startTime' < s.end_time AND '$endTime' > s.start_time);
            ";
            $resultCheckAlternateAssignment = $conn->query($sqlCheckAlternateAssignment);

            if ($resultCheckAlternateAssignment && $resultCheckAlternateAssignment->num_rows == 0) {
                $sqlAssignAlternateRoom = "
                    INSERT INTO room_assignments_tbl (room_id, schedule_id)
                    VALUES ('$roomId', '$scheduleId');
                ";
                if ($conn->query($sqlAssignAlternateRoom)) {
                    $roomAssigned = true;
                    $response['assignedSchedules'][] = $scheduleId;
                    break;
                }
            }
        }
    }

    // Update sched_status to 'assigned' when a room is assigned
    if ($roomAssigned) {
        $sqlUpdateStatus = "
            UPDATE schedules 
            SET sched_status = 'assigned' 
            WHERE schedule_id = '$scheduleId';
        ";
        $conn->query($sqlUpdateStatus);
    } else {
        $response['noRoomSchedules'][] = $scheduleId;
    }
}

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>
