<?php
// Include your database connection
require '../database/config.php';

header('Content-Type: application/json');

// Initialize response
$response = [
    'status' => 'error',
    'message' => ''
];

// Get the raw POST data from the form submission
$first_name = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
$last_name = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$id_number = isset($_POST['IdNumber']) ? trim($_POST['IdNumber']) : '';
$department = isset($_POST['department']) ? intval($_POST['department']) : 0;
$role = isset($_POST['role']) ? trim($_POST['role']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$confirm_password = isset($_POST['confirmPassword']) ? trim($_POST['confirmPassword']) : '';
$user_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

// Include the POST data in the response for debugging
$response['post_data'] = [
    'first_name' => $first_name,
    'last_name' => $last_name,
    'email' => $email,
    'id_number' => $id_number,
    'department' => $department,
    'role' => $role,
    'password' => $password,
    'confirm_password' => $confirm_password,
    'user_id' => $user_id
];

// Check if any required fields are empty
if (empty($first_name) || empty($last_name) || empty($email) || empty($id_number) || empty($department) || empty($role)) {
    $response['message'] = 'All fields are required.';
    echo json_encode($response);
    exit;
}

// Duplicate check: Check if the email or ID number already exists (excluding the current user)
$dup_check_stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE (email = ? OR id_number = ?) AND id != ?");
$dup_check_stmt->bind_param("ssi", $email, $id_number, $user_id);
$dup_check_stmt->execute();
$dup_check_stmt->bind_result($count);
$dup_check_stmt->fetch();
$dup_check_stmt->close();

if ($count > 0) {
    // Duplicate found
    $response['message'] = 'Email or ID Number already exists. Please use a different one.';
    echo json_encode($response);
    exit;
}

// Check if password is provided and match
if (!empty($password)) {
    if ($password !== $confirm_password) {
        $response['message'] = 'Passwords do not match. Please try again.';
        echo json_encode($response);
        exit;
    }

    // Hash the password
    $password = password_hash($password, PASSWORD_BCRYPT);
    $update_password_query = "userPassword = ?";
} else {
    // If no password is provided, skip password update
    $update_password_query = "";
}

// Prepare the SQL query to update the user
$sql = "UPDATE users 
        SET first_name = ?, last_name = ?, email = ?, id_number = ?, department_id = ?, userRole = ?" . 
        ($update_password_query ? ", " . $update_password_query : "") . " 
        WHERE id = ?";

// Prepare statement
$stmt = $conn->prepare($sql);

// Bind parameters
if (!empty($password)) {
    $stmt->bind_param('ssssissi', $first_name, $last_name, $email, $id_number, $department, $role, $password, $user_id);
} else {
    $stmt->bind_param('ssssisi', $first_name, $last_name, $email, $id_number, $department, $role, $user_id);
}

// Execute the query and prepare a response based on success or failure
if ($stmt->execute()) {
    $response['status'] = 'success';
    $response['message'] = 'User updated successfully.';
} else {
    $response['message'] = 'Failed to update user: ' . $stmt->error;
}

// Close the statement and connection
$stmt->close();
$conn->close();

// Send JSON response
echo json_encode($response);
?>
