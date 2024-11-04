<?php
include '../database/config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $term_id = $data['id'];

    $stmt = $conn->prepare("DELETE FROM terms_tbl WHERE term_id = ?");
    $stmt->bind_param("i", $term_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Academic Year & Semester deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error updating']);
    }

    $stmt->close();
}
$conn->close();
?>
