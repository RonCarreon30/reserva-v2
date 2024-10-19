<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data === null) {
        $errorMsg = 'Invalid JSON data: ' . json_last_error_msg();
        error_log($errorMsg);
        echo json_encode(['success' => false, 'error' => $errorMsg]);
        exit();
    }

    // Debugging output
    file_put_contents('debug.log', print_r($data, true)); // Save data to a log file

    $servername = "localhost";
    $username = "root";
    $db_password = "";
    $dbname = "reservadb";

    $conn = new mysqli($servername, $username, $db_password, $dbname);

    if ($conn->connect_error) {
        $errorMsg = 'Database connection failed: ' . $conn->connect_error;
        error_log($errorMsg);
        echo json_encode(['success' => false, 'error' => $errorMsg]);
        exit();
    }

    $stmtInsert = $conn->prepare("INSERT INTO schedules_tbl (subject_code, subject, course, days, start_time, end_time, instructor) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM schedules_tbl WHERE subject_code = ? AND subject = ? AND course = ? AND days = ? AND start_time = ? AND end_time = ? AND instructor = ?");

    if (!$stmtInsert || !$stmtCheck) {
        $errorMsg = 'Failed to prepare database statements: ' . $conn->error;
        error_log($errorMsg);
        echo json_encode(['success' => false, 'error' => $errorMsg]);
        exit();
    }

    function convertTo24Hour($time) {
        $time = trim($time);
        $parts = explode(' ', $time);
        if (count($parts) === 2) {
            list($timePart, $modifier) = $parts;
            list($hours, $minutes) = explode(':', $timePart);
            $hours = (int)$hours;
            $minutes = (int)$minutes;
            if ($modifier === 'PM' && $hours !== 12) $hours += 12;
            if ($modifier === 'AM' && $hours === 12) $hours = 0;
            return sprintf('%02d:%02d:00', $hours, $minutes);
        }
        return $time; // Return as is if no modifier is present
    }

    $inserted = [];
    $duplicates = [];

    foreach ($data as $index => $schedule) {
        $daysArray = explode(", ", $schedule['days']);

        foreach ($daysArray as $day) {
            // Convert times to 24-hour format
            $startTime = convertTo24Hour($schedule['startTime']);
            $endTime = convertTo24Hour($schedule['endTime']);

            // Check for existing record
            $stmtCheck->bind_param("sssssss", $schedule['subjectCode'], $schedule['subject'], $schedule['course'], $day, $startTime, $endTime, $schedule['instructor']);
            if (!$stmtCheck->execute()) {
                $errorMsg = 'Failed to execute check statement: ' . $stmtCheck->error;
                error_log($errorMsg);
                echo json_encode(['success' => false, 'error' => $errorMsg]);
                exit();
            }
            $stmtCheck->bind_result($count);
            $stmtCheck->fetch();
            $stmtCheck->free_result();

            if ($count > 0) {
                $duplicates[] = $schedule;
                continue 2;
            }

            // Insert new record
            $stmtInsert->bind_param("sssssss", $schedule['subjectCode'], $schedule['subject'], $schedule['course'], $day, $startTime, $endTime, $schedule['instructor']);
            if (!$stmtInsert->execute()) {
                $errorMsg = 'Failed to insert schedule: ' . $stmtInsert->error;
                error_log($errorMsg);
                echo json_encode(['success' => false, 'error' => $errorMsg]);
                exit();
            }

            $inserted[] = $schedule;
        }
    }

    $stmtInsert->close();
    $stmtCheck->close();
    $conn->close();

    echo json_encode([
        'success' => !empty($inserted),
        'inserted' => $inserted,
        'duplicates' => $duplicates
    ]);
} else {
    $errorMsg = 'Invalid request method';
    error_log($errorMsg);
    echo json_encode(['success' => false, 'error' => $errorMsg]);
}
?>
