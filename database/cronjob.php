<?php
include_once '../database/config.php';

// SQL query to update expired reservations
$sql = "
    UPDATE reservations
    SET reservation_status = 'Expired'
    WHERE reservation_date < CURDATE()
      AND reservation_status <> 'Expired'
";

// Execute the query and check for errors
if ($conn->query($sql) === TRUE) {
    echo "Reservations updated successfully.";
} else {
    // Log errors if the query fails
    error_log("Error updating reservations: " . $conn->error);
    echo "An error occurred. Check logs for details.";
}

// Close the database connection
$conn->close();
?>
