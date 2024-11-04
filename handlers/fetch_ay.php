<?php
    // fetch_ay.php
    include '../database/config.php'; // Include your database connection file

    $id = $_GET['id']; // Get the ID from the URL parameter

    $query = "SELECT academic_year, semester, term_status FROM terms_tbl WHERE term_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id); // Bind the ID parameter
    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result->fetch_assoc(); // Fetch the data as an associative array

    echo json_encode($data); // Return the data as JSON

?>