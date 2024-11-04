<?php
include '../database/config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $dept_id = $data['id'];

    // Retrieve the department name before deleting
    $stmt = $conn->prepare("SELECT dept_name FROM dept_tbl WHERE dept_id = ?");
    $stmt->bind_param("i", $dept_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $dept_name = "";

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $dept_name = $row['dept_name'];
    }
    $stmt->close();

    // Proceed to delete the department
    $stmt = $conn->prepare("DELETE FROM dept_tbl WHERE dept_id = ?");
    $stmt->bind_param("i", $dept_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => $dept_name . ' department deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error deleting department']);
    }

    $stmt->close();
}

$conn->close();
?>

