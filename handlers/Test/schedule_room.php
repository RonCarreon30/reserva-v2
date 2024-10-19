<?php
// schedule_room.php
session_start();

// Check if the user is logged in and has the required role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dept. Head') {
    header("Location: index.html");
    exit();
}

// Connect to the database
$servername = "localhost";
$username = "root";
$db_password = "";
$dbname = "reservadb";

$conn = new mysqli($servername, $username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get data from the request
$room_id = $_POST['room_id'];
$schedule_id = $_POST['schedule_id'];

// Begin transaction
$conn->begin_transaction();

try {
    // Insert into room_schedules
    $stmt = $conn->prepare("INSERT INTO room_schedules (room_id, schedules_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $room_id, $schedule_id);
    $stmt->execute();

    // Update is_scheduled in schedules_tbl
    $stmt = $conn->prepare("UPDATE schedules_tbl SET is_scheduled = TRUE WHERE schedules_id = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>
