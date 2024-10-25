<?php
include 'database/config.php';

// Query to fetch schedules, room assignments, room data, and building data, ordered by created_at
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
    ORDER BY schedules.created_at DESC
";

$result = $conn->query($query);

if ($result) {
    // Check if there are rows and fetch the result as an associative array
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Output each row in a readable format
            echo "<pre>";
            print_r($row);
            echo "</pre>";
        }
    } else {
        echo "No records found.";
    }
} else {
    // Output error if the query fails
    echo "Error: " . $conn->error;
}
?>
