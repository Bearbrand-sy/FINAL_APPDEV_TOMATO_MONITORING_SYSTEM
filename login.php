<?php

session_start();
include "db.php";

$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE email='$email' AND password='$password'";
$result = $conn->query($sql);

if($result->num_rows == 1){

    $row = $result->fetch_assoc();

    $_SESSION['name'] = $row['name'];
    $_SESSION['email'] = $row['email'];
    $_SESSION['role'] = $row['role'];

    if($row['role'] == "Admin"){
        header("Location: admin/dashboard.php");
    }
    elseif($row['role'] == "Manager"){
        header("Location: manager/manager.php");
    }
    else{
        header("Location: user/user.php");
    }

}else{

    header("Location: index.php?error=1");

}

?>