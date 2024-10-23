<?php
header('Content-Type: application/json');

session_start();

// Get user department from session
$user_department = $_SESSION['department'] ?? 'Unknown';

// Read JSON data from the POST request
$data = json_decode(file_get_contents('php://input'), true);

// Debug: Check the received data
error_log(print_r($data, true));

// Validate received data
if (!isset($data['schedules']) || !is_array($data['schedules'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$schedules = $data['schedules'];
$selectedDepartmentId = $data['selectedDepartmentId']; // Get department ID

// Database connection details
$dsn = 'mysql:host=localhost;dbname=reservadb';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();

    // Fetch the current term ID
    $term_id = fetchCurrentTermId($pdo);
    if ($term_id === null) {
        echo json_encode(['success' => false, 'error' => 'No current term found']);
        exit;
    }

    // Prepare insert statement
    $stmtInsert = $pdo->prepare('
        INSERT INTO schedules_tbl (subject_code, subject, section, instructor, start_time, end_time, days, type, term_id, schedule_status, user_dept, pref_dept)
        VALUES (:subject_code, :subject, :section, :instructor, :start_time, :end_time, :days, :type, :term_id, :schedule_status, :user_dept, :pref_dept)
    ');

    $schedulesSaved = false;

    foreach ($schedules as $schedule) {
        // Convert times and check for duplicates
        $start_time_24 = convertTo24Hour(trim($schedule['start_time']));
        $end_time_24 = convertTo24Hour(trim($schedule['end_time']));

        if (!checkDuplicateSchedule($pdo, $schedule, $start_time_24, $end_time_24, $term_id, $user_department)) {
            $stmtInsert->execute([
                ':subject_code' => trim($schedule['subject_code']),
                ':subject' => trim($schedule['subject']),
                ':section' => trim($schedule['section']),
                ':instructor' => trim($schedule['instructor']),
                ':start_time' => $start_time_24,
                ':end_time' => $end_time_24,
                ':days' => trim($schedule['days']),
                ':type' => trim($schedule['type']),
                ':term_id' => $term_id,
                ':schedule_status' => 'pending',
                ':user_dept' => $user_department,
                ':pref_dept' => $selectedDepartmentId // Update this line
            ]);
            $schedulesSaved = true;
        }
    }

    $pdo->commit();

    echo json_encode($schedulesSaved
        ? ['success' => true, 'message' => 'Schedules saved successfully', 'schedules' => $data['schedules'], 'user_department' => $user_department]
        : ['success' => false, 'error' => 'No new schedules were saved (possibly duplicates)']);

} catch (Exception $e) {
    if ($pdo) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Function to fetch the current term ID
function fetchCurrentTermId($pdo) {
    $stmt = $pdo->query("SELECT term_id FROM terms_tbl WHERE term_status = 'Current'");
    $term = $stmt->fetch(PDO::FETCH_ASSOC);
    return $term ? $term['term_id'] : null;
}

// Function to convert 12-hour format to 24-hour format
function convertTo24Hour($time) {
    $dateTime = DateTime::createFromFormat('h:i A', $time);
    return $dateTime ? $dateTime->format('H:i') : null;
}

// Function to check for duplicate schedules
function checkDuplicateSchedule($pdo, $schedule, $start_time_24, $end_time_24, $term_id, $user_department) {
    $stmtCheck = $pdo->prepare('
        SELECT COUNT(*) FROM schedules_tbl
        WHERE subject_code = :subject_code
        AND subject = :subject
        AND section = :section
        AND instructor = :instructor
        AND start_time = :start_time
        AND end_time = :end_time
        AND days = :days
        AND type = :type
        AND term_id = :term_id
        AND user_dept = :user_dept
    ');

    $stmtCheck->execute([
        ':subject_code' => trim($schedule['subject_code']),
        ':subject' => trim($schedule['subject']),
        ':section' => trim($schedule['section']),
        ':instructor' => trim($schedule['instructor']),
        ':start_time' => $start_time_24,
        ':end_time' => $end_time_24,
        ':days' => trim($schedule['days']),
        ':type' => trim($schedule['type']),
        ':term_id' => $term_id,
        ':user_dept' => $user_department
    ]);

    return $stmtCheck->fetchColumn() > 0;
}
?>
