<?php
    // Establish connection to the MySQL database
    $servername = "localhost"; // Change this to your database server name
    $username = "root"; // Change this to your database username
    $db_password = ""; // Change this to your database password
    $dbname = "reservadb"; // Change this to your database name

    // Create connection
    $conn = new mysqli($servername, $username, $db_password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
?>