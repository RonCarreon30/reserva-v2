    <?php
    // Include your database connection
    require '../database/config.php'; 


    // Get the raw POST data
    $data = json_decode(file_get_contents("php://input"), true);

    // Extract the data
    $room_number = $data['room_number'];
    $building = $data['building'];
    $room_type = $data['room_type'];
    $room_status = $data['room_status'];
    $roomId = $data['id']; // Ensure that this ID is passed to identify the reservation

    // Prepare the SQL query to update the reservation
    $sql = "UPDATE rooms 
            SET room_number = ?, building = ?, room_type = ?, room_status = ?
            WHERE room_id = ?";

    // Prepare the statement
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssi', $room_number, $building, $room_type, $room_status, $roomId);

    // Execute the query
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Facility updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update Facility.']);
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
    ?>