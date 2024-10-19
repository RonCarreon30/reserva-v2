<?php
    // login_process.php

    session_start();

    if (!isset($_POST['email'], $_POST['password'])) {
        echo json_encode(array('success' => false, 'message' => 'Please enter email and password.'));
        exit();
    }

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    $servername = "localhost";
    $username = "root";
    $db_password = ""; // The database password
    $dbname = "reservadb";

    $conn = new mysqli($servername, $username, $db_password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT id, email, userPassword, userRole, department FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['userPassword'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['userRole'];
            $_SESSION['department'] = $user['department']; // Store department in session
            
            // Debugging: Output department information            
            switch ($user['userRole']) {
                case 'Admin':
                    $redirect = 'dashboard-admin.php';
                    break;
                case 'Registrar':
                    $redirect = 'dashboard-reg.php';
                    break;
                case 'Facility Head':
                    $redirect = 'dashboard-gso.php';
                    break;
                case 'Dept. Head':
                    $redirect = 'dashboard-deptHead.php';
                    break;
                case 'Student Rep':
                    $redirect = 'dashboard-student.php';
                    break;
                default:
                    $redirect = 'login.php'; // Redirect to login page if role not found
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
