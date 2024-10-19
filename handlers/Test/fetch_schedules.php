<?php
session_start();  // This must be called at the start of the script
require '../database/config.php';

$pdo = new PDO('mysql:host=localhost;dbname=reservadb', 'root', '');

// Assuming user_department is stored in the session
$user_department = isset($_SESSION['department']) ? $_SESSION['department'] : 'Unknown';

// Fetch the current term_id
$currentTermStmt = $pdo->query("SELECT term_id FROM terms_tbl WHERE term_status = 'Current'");
$currentTerm = $currentTermStmt->fetch(PDO::FETCH_ASSOC); // Fetch the result as an associative array

// Check if a current term exists
if ($currentTerm) {
    $currentTermId = $currentTerm['term_id']; // Extract the term_id

    // Prepared statement to fetch schedules
    $query = "SELECT * FROM schedules_tbl WHERE user_dept = :user_department AND schedule_status = 'pending' AND term_id = :current_term_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_department', $user_department, PDO::PARAM_STR);
    $stmt->bindParam(':current_term_id', $currentTermId, PDO::PARAM_INT); // Bind the current term ID
    $stmt->execute();

    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'schedules' => $schedules]);
} else {
    echo json_encode(['success' => false, 'message' => 'Current term not found.']);
}
?>