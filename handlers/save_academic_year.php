<?php
include '../database/config.php'; // include your database connection script

header('Content-Type: application/json');

// Initialize response
$response = [
    'status' => 'error',
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $academic_year = $_POST['academicYear'];
    $semester = $_POST['semester'];
    $term_status = $_POST['ayStatus'];

    // Validate inputs
    if (empty($academic_year) || empty($semester) || empty($term_status)) {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }

    // Validate term status
    $valid_statuses = ['Current', 'Upcoming', 'Expired'];
    if (!in_array($term_status, $valid_statuses)) {
        $response['message'] = 'Invalid term status.';
        echo json_encode($response);
        exit;
    }

    // Check if the academic year and semester combination already exists
    $check_existing_stmt = $conn->prepare("SELECT COUNT(*) FROM terms_tbl WHERE academic_year = ? AND semester = ?");
    $check_existing_stmt->bind_param("ss", $academic_year, $semester);
    $check_existing_stmt->execute();
    $check_existing_stmt->bind_result($existing_count);
    $check_existing_stmt->fetch();
    $check_existing_stmt->close();

    if ($existing_count > 0) {
        // If record exists with the same academic year and semester
        $response['message'] = 'The academic year and semester combination already exists.';
        echo json_encode($response);
        exit;
    }

    // If term status is 'Current', check if there is already an existing 'Current' status
    if ($term_status === 'Current') {
        $check_current_stmt = $conn->prepare("SELECT COUNT(*) FROM terms_tbl WHERE term_status = 'Current'");
        $check_current_stmt->execute();
        $check_current_stmt->bind_result($current_count);
        $check_current_stmt->fetch();
        $check_current_stmt->close();

        if ($current_count > 0) {
            // If a record with 'Current' status already exists
            $response['message'] = "There is already a term with 'Current' status.";
            echo json_encode($response);
            exit;
        }
    }

    // Insert data into the database if validation passed
    $stmt = $conn->prepare("INSERT INTO terms_tbl (academic_year, semester, term_status) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $academic_year, $semester, $term_status);

    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Academic Year & Semester added successfully';
    } else {
        $response['message'] = 'Error adding data to database';
    }
    
    $stmt->close();
}

// Send JSON response
echo json_encode($response);
?>
