<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "tomato_greengrow";

$conn = new mysqli($host,$user,$password,$database);

if($conn->connect_error){
    die("Connection failed: ".$conn->connect_error);
}

?>