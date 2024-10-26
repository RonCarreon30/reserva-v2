<?php
// Start the session
session_start();

$user_id = $_SESSION['user_id'];
echo $user_id;
include '../database/config.php';

// Step 1: Retrieve pending schedules and their department details
$sqlPendingSchedules = "
    SELECT s.*, d.building_id, d.dept_name
    FROM schedules s
    JOIN dept_tbl d ON s.department_id = d.dept_id
    WHERE s.sched_status = 'pending' AND s.user_id = $user_id;
";

$resultPendingSchedules = $conn->query($sqlPendingSchedules);
$pendingSchedules = [];

if ($resultPendingSchedules->num_rows > 0) {
    while ($row = $resultPendingSchedules->fetch_assoc()) {
        $pendingSchedules[] = $row;
    }
}

foreach ($pendingSchedules as $schedule) {
    $scheduleId = $schedule['schedule_id'];
    $classType = $schedule['class_type'];
    $buildingId = $schedule['building_id'];
    $days = explode(',', $schedule['days']);
    $startTime = $schedule['start_time'];
    $endTime = $schedule['end_time'];

    // Step 2: Get rooms that match the schedule's class_type and building
    $sqlMatchingRooms = "
        SELECT r.*
        FROM rooms_tbl r
        WHERE r.room_type = '$classType'
        AND r.building_id = '$buildingId'
        AND r.room_status = 'Available';
    ";

    $resultMatchingRooms = $conn->query($sqlMatchingRooms);
    $rooms = [];

    if ($resultMatchingRooms->num_rows > 0) {
        while ($room = $resultMatchingRooms->fetch_assoc()) {
            $rooms[] = $room;
        }
    }

    $roomAssigned = false; // Flag to track if the schedule has been assigned

    // Step 3: Check room assignments and assign if no conflicts
    foreach ($rooms as $room) {
        $roomId = $room['room_id'];

        // Check if the room is already assigned for the same time period
        $sqlCheckAssignment = "
            SELECT ra.*
            FROM room_assignments_tbl ra
            JOIN schedules s ON ra.schedule_id = s.schedule_id
            WHERE ra.room_id = '$roomId'
            AND s.days LIKE '%" . implode("%' OR s.days LIKE '%", $days) . "%'
            AND ('$startTime' < s.end_time AND '$endTime' > s.start_time);
        ";

        $resultCheckAssignment = $conn->query($sqlCheckAssignment);

        if ($resultCheckAssignment->num_rows == 0) {
            // No overlap, assign the schedule to this room
            $sqlAssignRoom = "
                INSERT INTO room_assignments_tbl (room_id, schedule_id)
                VALUES ('$roomId', '$scheduleId');
            ";
            if ($conn->query($sqlAssignRoom)) {
                $roomAssigned = true;
                break; // Exit loop once room is assigned
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
        $alternateRooms = [];

        if ($resultAlternateRooms->num_rows > 0) {
            while ($room = $resultAlternateRooms->fetch_assoc()) {
                $alternateRooms[] = $room;
            }
        }

        // Step 5: Check if alternate rooms have assignments or overlaps
        foreach ($alternateRooms as $room) {
            $roomId = $room['room_id'];

            // Check for overlapping schedules in alternate rooms
            $sqlCheckAlternateAssignment = "
                SELECT ra.*
                FROM room_assignments_tbl ra
                JOIN schedules s ON ra.schedule_id = s.schedule_id
                WHERE ra.room_id = '$roomId'
                AND s.days LIKE '%" . implode("%' OR s.days LIKE '%", $days) . "%'
                AND ('$startTime' < s.end_time AND '$endTime' > s.start_time);
            ";

            $resultCheckAlternateAssignment = $conn->query($sqlCheckAlternateAssignment);

            if ($resultCheckAlternateAssignment->num_rows == 0) {
                // No overlap, assign the schedule to this alternate room
                $sqlAssignAlternateRoom = "
                    INSERT INTO room_assignments_tbl (room_id, schedule_id)
                    VALUES ('$roomId', '$scheduleId');
                ";
                if ($conn->query($sqlAssignAlternateRoom)) {
                    $roomAssigned = true;
                    break; // Exit loop once alternate room is assigned
                }
            }
        }
    }
    
    // Step 6: Update sched_status to 'assigned' when a room is assigned
    if ($roomAssigned) {
        // Update sched_status to 'assigned'
        $sqlUpdateStatus = "
            UPDATE schedules 
            SET sched_status = 'assigned' 
            WHERE schedule_id = '$scheduleId';
        ";
        
        if ($conn->query($sqlUpdateStatus)) {
            echo "Schedule ID: $scheduleId successfully assigned and status updated.<br>";
        } else {
            echo "Failed to update status for Schedule ID: $scheduleId.<br>";
        }
    } else {
        echo "No suitable room found for Schedule ID: $scheduleId.<br>";
    }

}

// Close connection
$conn->close();
?>
