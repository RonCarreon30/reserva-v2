<?php
include('../database/config.php');

$id = $_POST['id'];
$academicYear = $_POST['academicYear'];
$semester = $_POST['semester'];
$status = $_POST['ayStatus'];

$query = "UPDATE terms_tbl SET academic_year = ?, semester = ?, term_status = ? WHERE term_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssi", $academicYear, $semester, $status, $id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Academic Year & Semester updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error updating']);
}

$stmt->close();
?>
