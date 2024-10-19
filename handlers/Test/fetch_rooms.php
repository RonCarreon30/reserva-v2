<?php
session_start();
// Assuming you have a connection to the database
include '../database/config.php';

if (isset($_GET['department'])) {
    $department = $_GET['department'];

    // SQL query to fetch rooms where room's building matches department's building
    $query = "
        SELECT rooms.*
        FROM rooms
        JOIN dept_tbl ON rooms.building = dept_tbl.dept_building
        WHERE dept_tbl.dept_id = ?
    ";

    // Prepare the statement
    if ($stmt = $conn->prepare($query)) {
        // Bind the department as parameter (assuming department ID is passed)
        $stmt->bind_param("s", $department);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch rooms into an array
        $rooms = [];
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }

        // Return the data as JSON
        echo json_encode(['rooms' => $rooms]);

    } else {
        echo json_encode(['error' => 'Error preparing SQL query']);
    }
} else {
    echo json_encode(['error' => 'No department specified']);
}
?>
