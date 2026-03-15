<?php
include "db.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $transport_id = $_POST['transport_id'];
    $product = $_POST['product'];
    $quantity = $_POST['quantity'];
    $delivery_date = $_POST['delivery_date'];
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $driver = $_POST['driver'];
    $vehicle_plate = $_POST['vehicle_plate'];
    $priority = $_POST['priority'];
    $notes = $_POST['notes'];

    $sql = "INSERT INTO deliveries 
            (transport_id, product, quantity, delivery_date, origin, destination, driver, vehicle_plate, priority, notes)
            VALUES 
            ('$transport_id','$product','$quantity','$delivery_date','$origin','$destination','$driver','$vehicle_plate','$priority','$notes')";

    if($conn->query($sql) === TRUE){
        header("Location: deliveries.php?success=1");
    } else {
        echo "Error: " . $conn->error;
    }

}

?>