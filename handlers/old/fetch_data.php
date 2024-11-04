<?php
header('Content-Type: application/json');

include '../database/config.php';
session_start(); // Start the session to access user data

// Assuming user_department is stored in the session
$user_department = isset($_SESSION['department']) ? $_SESSION['department'] : 'Unknown';

// Fetch the current term_id
$currentTermStmt = $conn->query("SELECT term_id FROM terms_tbl WHERE term_status = 'Current'");
$currentTerm = $currentTermStmt->fetch_assoc(); // Fetch the result as an associative array

// Initialize the schedules array
$schedules = [];

// Check if a current term exists
if ($currentTerm) {
    $currentTermId = $currentTerm['term_id']; // Extract the term_id

    // Prepared statement to fetch schedules
    $stmt = $conn->prepare("SELECT * FROM schedules_tbl WHERE user_dept = ? AND schedule_status = 'pending' AND term_id = ?");
    $stmt->bind_param("si", $user_department, $currentTermId); // "si" means string, integer
    $stmt->execute();
    $schedulesResult = $stmt->get_result();
    $schedules = $schedulesResult->fetch_all(MYSQLI_ASSOC);
}

// Fetch assigned rooms
$assignedRoomsResult = $conn->query("SELECT * FROM assigned_rooms_tbl");
$assignedRooms = $assignedRoomsResult->fetch_all(MYSQLI_ASSOC);

// Fetch rooms
$roomsResult = $conn->query("SELECT * FROM rooms_tbl");
$rooms = $roomsResult->fetch_all(MYSQLI_ASSOC);

// Fetch departments
$departmentsResult = $conn->query("SELECT * FROM dept_tbl");
$departments = $departmentsResult->fetch_all(MYSQLI_ASSOC);

// Prepare response
$response = [
    "assignedRooms" => $assignedRooms,
    "schedules" => $schedules,
    "rooms" => $rooms,
    "departments" => $departments // Include departments in the response
];

echo json_encode($response);
$conn->close();
?>
