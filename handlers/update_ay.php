<?php
include('../database/config.php');

// Get POST data
$id = $_POST['id'];
$academicYear = $_POST['academicYear'];
$semester = $_POST['semester'];
$status = $_POST['ayStatus'];

// Initialize response
$response = [
    'status' => 'error',
    'message' => ''
];

// Check if the academic year and semester combination already exists
$check_existing_stmt = $conn->prepare("SELECT COUNT(*) FROM terms_tbl WHERE academic_year = ? AND semester = ? AND term_id != ?");
$check_existing_stmt->bind_param("ssi", $academicYear, $semester, $id);
$check_existing_stmt->execute();
$check_existing_stmt->bind_result($existing_count);
$check_existing_stmt->fetch();
$check_existing_stmt->close();

if ($existing_count > 0) {
    $response['message'] = 'The academic year and semester combination already exists.';
    echo json_encode($response);
    exit;
}

// If the term status is 'Current', check if another term already has 'Current' status
if ($status === 'Current') {
    $check_current_stmt = $conn->prepare("SELECT COUNT(*) FROM terms_tbl WHERE term_status = 'Current' AND term_id != ?");
    $check_current_stmt->bind_param("i", $id);
    $check_current_stmt->execute();
    $check_current_stmt->bind_result($current_count);
    $check_current_stmt->fetch();
    $check_current_stmt->close();

    if ($current_count > 0) {
        $response['message'] = "There is already a term with 'Current' status.";
        echo json_encode($response);
        exit;
    }
}

// Update the academic year and semester in the database
$query = "UPDATE terms_tbl SET academic_year = ?, semester = ?, term_status = ? WHERE term_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssi", $academicYear, $semester, $status, $id);

if ($stmt->execute()) {
    $response['status'] = 'success';
    $response['message'] = 'Academic Year & Semester updated successfully';
} else {
    $response['message'] = 'Error updating';
}

$stmt->close();

// Send the response back to the client
echo json_encode($response);
?>
