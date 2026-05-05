<?php
// db.php - Database connection

require_once __DIR__ . '/loadenv.php';

$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'u442411629_dev_kamatis';
$password = $_ENV['DB_PASSWORD'] ?? 'Qr33)3?Ia;r8';
$database = $_ENV['DB_NAME'] ?? 'u442411629_kamatis';

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Function to get connection (for compatibility)
function getConnection() {
    global $conn;
    return $conn;
}
?>