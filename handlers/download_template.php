<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page
    header("Location: index");
    exit();
}

// Path to the Excel template
$templatePath = '../templates/schedule_template.xlsx';

// Check if the file exists
if (file_exists($templatePath)) {
    // Set headers to trigger a download
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . basename($templatePath) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($templatePath));

    // Read the file and send it to the output buffer
    readfile($templatePath);
    exit;
} else {
    echo "Template file not found.";
}
?>
