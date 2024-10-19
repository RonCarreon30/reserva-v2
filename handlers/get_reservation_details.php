<?php
$servername = "localhost";
$username = "root";
$db_password = "";
$dbname = "reservadb";
$conn = new mysqli($servername, $username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$reservationId = $_GET['id'];
$sql = "SELECT * FROM reservations WHERE id = $reservationId";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Format the start_time and end_time to match your select options (e.g., '08:30 AM')
    $row['start_time'] = date('h:i A', strtotime($row['start_time']));
    $row['end_time'] = date('h:i A', strtotime($row['end_time']));

    echo json_encode($row);
} else {
    echo json_encode(['error' => 'No reservation found']);
}

$conn->close();
?>
