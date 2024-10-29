<?php
include '../database/config.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $term_id = $_POST['term_id'];
    $academic_year = $_POST['academic_year'];
    $semester = $_POST['semester'];
    $term_status = $_POST['term_status'];

    $stmt = $conn->prepare("UPDATE terms_tbl SET academic_year = ?, semester = ?, term_status = ? WHERE term_id = ?");
    $stmt->bind_param("sssi", $academic_year, $semester, $term_status, $term_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
}
$conn->close();
?>
