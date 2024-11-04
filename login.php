<?php
    // login_process.php

    session_start();

    if (!isset($_POST['email'], $_POST['password'])) {
        echo json_encode(array('success' => false, 'message' => 'Please enter email and password.'));
        exit();
    }

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    include 'database/config.php';

    $stmt = $conn->prepare("SELECT users.*, dept_tbl.* FROM users LEFT JOIN dept_tbl ON users.department_id = dept_tbl.dept_id  WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['userPassword'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['userRole'];
            $_SESSION['department'] = $user['dept_name']; // Store department in session
            
            // Debugging: Output department information            
            switch ($user['userRole']) {
                case 'Admin':
                    $redirect = 'dashboard-admin';
                    break;
                case 'Registrar':
                    $redirect = 'dashboard-reg';
                    break;
                case 'Facility Head':
                    $redirect = 'dashboard-gso';
                    break;
                case 'Dept. Head':
                    $redirect = 'dashboard-deptHead';
                    break;
                case 'Student Rep':
                    $redirect = 'dashboard-student';
                    break;
                default:
                    $redirect = 'index'; // Redirect to login page if role not found
                    break;
            }
            
            echo json_encode(array('success' => true, 'redirect' => $redirect));
        } else {
            // Wrong password
            echo json_encode(array('success' => false, 'message' => 'Wrong username or password.'));
        }
    } else {
        // No user found
        echo json_encode(array('success' => false, 'message' => 'No user found.'));
    }

    $stmt->close();
    $conn->close();
?>
