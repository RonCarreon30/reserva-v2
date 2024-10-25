<?php
require '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

// Example of starting a session to fetch user department (adjust as needed)
session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file']['tmp_name'];
        try {
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Remove header row
            array_shift($rows);

            $schedules = [];
            foreach ($rows as $row) {
                $days = explode(',', $row[6]); // Assuming days are comma-separated
                foreach ($days as $day) {
                    $schedules[] = [
                        'subject_code' => $row[0],
                        'subject' => $row[1],
                        'section' => $row[2],
                        'instructor' => $row[3],
                        'start_time' => trim($row[4]),
                        'end_time' => trim($row[5]),
                        'days' => trim($day),
                        'type' => trim($row[7]),
                    ];
                }
            }

            // Output the JSON response
            echo json_encode(['success' => true, 'schedules' => $schedules]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'File upload error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
