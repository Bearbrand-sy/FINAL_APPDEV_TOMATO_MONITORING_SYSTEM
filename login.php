<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Get user by email
    $sql = "SELECT * FROM users WHERE email='$email' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {

        $row = $result->fetch_assoc();

        // Verify hashed password
        if (password_verify($password, $row['password'])) {

            $_SESSION['id'] = $row['id'];
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['name'] = $row['name'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
            
            // Update last login time
            $update_sql = "UPDATE users SET last_login = NOW() WHERE id = " . $row['id'];
            $conn->query($update_sql);

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
            header("Location: index.php?error=invalid_password&email=" . urlencode($email));
            exit();
        }

    } else {
        // Email not found
        header("Location: index.php?error=user_not_found&email=" . urlencode($email));
        exit();
    }
} else {
    // If someone tries to access login.php directly without POST
    header("Location: index.php");
    exit();
}
?>