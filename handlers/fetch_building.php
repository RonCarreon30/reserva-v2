    <?php
    include '../database/config.php';

    $buildingId = $_GET['id'];

    $query = "SELECT building_name , building_desc FROM buildings_tbl WHERE building_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $buildingId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc(); // Fetch the data as an associative array

    echo json_encode($data); // Return the data as JSON
    ?>
