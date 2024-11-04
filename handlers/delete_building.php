<?php
include '../database/config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $building_id = $data['id'];

    $stmt = $conn->prepare("DELETE FROM buildings_tbl WHERE building_id = ?");
    $stmt->bind_param("i", $building_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Building deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating']);
    }

    $stmt->close();
}
$conn->close();
?>
