<?php
// create_room.php

// Validate input data (e.g., ensure required fields are present)
if (!isset($_POST['roomNumber'], $_POST['building'], $_POST['type'], $_POST['roomStatus'])) {
    // Handle missing fields
    echo json_encode(array('success' => false, 'message' => 'Missing required fields.'));
    exit();
}

// Sanitize input data to prevent SQL injection and other attacks
$roomNumber = htmlspecialchars($_POST['roomNumber']);
$building = htmlspecialchars($_POST['building']);
$type = htmlspecialchars($_POST['type']);
$roomStatus = htmlspecialchars($_POST['roomStatus']);


// Connect to the database
require_once "../database/config.php";

// Create connection
$conn = new mysqli($servername, $username, $db_password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the room already exists in the database
$checkStmt = $conn->prepare("SELECT COUNT(*) FROM rooms WHERE room_number = ? AND building = ?");
$checkStmt->bind_param("ss", $roomNumber, $building);
$checkStmt->execute();
$checkStmt->bind_result($count);
$checkStmt->fetch();
$checkStmt->close();

if ($count > 0) {
    // Redirect back to the referring page with error parameter
    $referer = $_SERVER['HTTP_REFERER'];
    header("Location: $referer?success=false&duplicate=true");
    // Close database connection
    $conn->close();
    exit();
}

// Prepare SQL statement to insert a new facility into the database
$stmt = $conn->prepare("INSERT INTO rooms (room_number, building, room_type, room_status) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $roomNumber, $building, $type, $roomStatus);

// Execute SQL statement to insert a new facility
if ($stmt->execute()) {
    // Close prepared statement
    $stmt->close();

    // Close database connection
    $conn->close();

    // Return success message as JSON response
    echo json_encode(array('success' => true, 'message' => 'Room added successfully.'));

    // Redirect back to the referring page with success parameter
    $referer = $_SERVER['HTTP_REFERER'];
    header("Location: $referer?success=true");
    exit();
} else {
    // Return error message as JSON response
    echo json_encode(array('success' => false, 'message' => 'Error adding room.'));
    // Close prepared statement
    $stmt->close();

    // Close database connection
    $conn->close();

    // Redirect back to the referring page with error parameter
    $referer = $_SERVER['HTTP_REFERER'];
    header("Location: $referer?success=false&error=true");
    exit();
}
?>
