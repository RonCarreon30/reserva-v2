    <?php
    header('Content-Type: application/json');

    // Debug: Log the received input
    $data = file_get_contents('php://input');
    error_log("Received data: " . $data);

    // Decode the input JSON
    $assignments = json_decode($data, true);

    // Check if JSON data is valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data: ' . json_last_error_msg()]);
        exit;
    }

    // Check if the assignments array is valid
    if (!$assignments || !is_array($assignments) || empty($assignments)) {
        echo json_encode(['success' => false, 'error' => 'No schedules data found']);
        exit;
    }

    session_start();  // This must be called at the start of the script

    // Set up the PDO connection
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=reservadb', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Prepare an insert statement for the assigned rooms
        $stmt = $pdo->prepare("INSERT INTO assigned_rooms_tbl (schedule_id, room_id, day, start_time, end_time) VALUES (:schedule_id, :room_id, :day, :start_time, :end_time)");

        // Prepare an update statement for the schedule status
        $updateStatusStmt = $pdo->prepare("UPDATE schedules_tbl SET schedule_status = 'Scheduled' WHERE schedule_id = :schedule_id");

        // Iterate through the assignments and insert them
        foreach ($assignments as $assignment) {
            // Ensure day is a valid ENUM value
            $day = $assignment['days'];
            if (!in_array($day, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])) {
                throw new Exception('Invalid day value: ' . $day);
            }

            // Check if room_id is set and not null
            if (!isset($assignment['room_id']) || empty($assignment['room_id'])) {
                throw new Exception('Missing room_id for schedule_id: ' . $assignment['schedule_id']);
            }

            // Insert into the assigned_rooms_tbl
            $stmt->execute([
                ':schedule_id' => $assignment['schedule_id'],
                ':room_id' => $assignment['room_id'],
                ':day' => $day,
                ':start_time' => date('H:i:s', strtotime($assignment['start_time'])), // Convert to military time
                ':end_time' => date('H:i:s', strtotime($assignment['end_time'])) // Convert to military time
            ]);

            // Update the schedule status in schedules_tbl
            $updateStatusStmt->execute([
                ':schedule_id' => $assignment['schedule_id']
            ]);
        }


        // Commit the transaction
        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    } catch (PDOException $e) {
        // Rollback the transaction on PDO error
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    ?>