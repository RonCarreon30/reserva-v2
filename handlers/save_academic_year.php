<?php
include '../database/config.php'; // include your database connection script

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $academic_year = $_POST['academicYear'];
    $semester = $_POST['semester'];
    $term_status = $_POST['ayStatus'];

    // Validate inputs
    if (empty($academic_year) || empty($semester) || empty($term_status)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    // Validate term status
    $valid_statuses = ['Current', 'Upcoming', 'Expired'];
    if (!in_array($term_status, $valid_statuses)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid term status.']);
        exit;
    }

    // Insert data into database
    $stmt = $conn->prepare("INSERT INTO terms_tbl (academic_year, semester, term_status) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $academic_year, $semester, $term_status);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Academic Year & Semester added successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error adding data to database']);
    }
    
    $stmt->close();
}
?>
