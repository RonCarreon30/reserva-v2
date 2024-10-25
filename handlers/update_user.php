<?php
include '../database/config.php'; // Database connection

// Get the raw POST data
$data = file_get_contents("php://input");
$decodedData = json_decode($data, true); // Decode the JSON data

// Retrieve user data from the decoded array
$user_id = $decodedData['id'] ?? null; // Use null coalescing to avoid undefined index errors
$firstName = $decodedData['first_name'] ?? null;
$lastName = $decodedData['last_name'] ?? null;
$email = $decodedData['email'] ?? null;
$idNumber = $decodedData['idNumber'] ?? null;
$department_id = $decodedData['department'] ?? null;
$role = $decodedData['role'] ?? null;
$password = $decodedData['password'] ?? null;

// Start a transaction to ensure data integrity
$conn->begin_transaction();

try {
    $changesMade = false; // Flag to check if any changes were made

    // If a new password is provided, hash it and update it
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Update the user password in the database
        $query = "UPDATE users SET userPassword = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $hashedPassword, $user_id);
        $stmt->execute();

        // Check for errors
        if ($stmt->affected_rows > 0) {
            $changesMade = true; // Mark that changes were made
        }
    }

    // Update the other user fields only if they are provided
    if (!empty($firstName) || !empty($lastName) || !empty($email) || !empty($idNumber) || !empty($department_id) || !empty($role)) {
        $query = "UPDATE users SET 
                    first_name = IFNULL(NULLIF(?, ''), first_name), 
                    last_name = IFNULL(NULLIF(?, ''), last_name), 
                    email = IFNULL(NULLIF(?, ''), email), 
                    id_number = IFNULL(NULLIF(?, ''), id_number), 
                    department_id = IFNULL(NULLIF(?, ''), department_id), 
                    userRole = IFNULL(NULLIF(?, ''), userRole) 
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssi", $firstName, $lastName, $email, $idNumber, $department_id, $role, $user_id);
        $stmt->execute();

        // Check for errors
        if ($stmt->affected_rows > 0) {
            $changesMade = true; // Mark that changes were made
        }
    }

    // Commit or rollback based on changes made
    if ($changesMade) {
        $conn->commit();
        echo json_encode(['success' => 'User updated successfully']);
    } else {
        throw new Exception('No changes made to user information.');
    }
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    // Close the statement if it was created
    if (isset($stmt)) {
        $stmt->close();
    }
    // Close the database connection if necessary
    $conn->close();
}
?>
