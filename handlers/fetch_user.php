<?php
include '../database/config.php'; // Assuming you have a DB connection file

// Validate user_id passed via GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = $_GET['id'];

    // SQL Query to fetch user and department details
    $query = "SELECT users.*, dept_tbl.*
              FROM users 
              LEFT JOIN dept_tbl ON users.department_id = dept_tbl.dept_id 
              WHERE users.id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id); // Bind the user_id to the query
    $stmt->execute();
    
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode($user); // Return the user data as JSON
    } else {
        echo json_encode(['error' => 'User not found']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid or missing user ID']);
}
?>
