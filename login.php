<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Get user by email ONLY
    $sql = "SELECT * FROM users WHERE email='$email' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {

        $row = $result->fetch_assoc();

        // Verify hashed password
        if (password_verify($password, $row['password'])) {

            $_SESSION['id']    = $row['id'];
            $_SESSION['name']  = $row['name'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role']  = $row['role'];

            // Redirect based on role
            if ($row['role'] == "Admin") {
                header("Location: admin/dashboard.php");
            } elseif ($row['role'] == "Manager") {
                header("Location: manager/manager.php");
            } elseif ($row['role'] == "Driver") {
                header("Location: driver/driver.php");
            } else {
                header("Location: user/user.php");
            }

            exit();

        } else {
            // Wrong password
            header("Location: index.php?error=invalid_password");
            exit();
        }

    } else {
        // Email not found
        header("Location: index.php?error=user_not_found");
        exit();
    }
}
?>