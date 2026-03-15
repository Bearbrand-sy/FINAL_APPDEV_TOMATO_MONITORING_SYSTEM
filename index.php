<?php
session_start();

if(isset($_SESSION['role'])){

    if($_SESSION['role'] == "Admin"){
        header("Location: admin/dashboard.php");
    }
    elseif($_SESSION['role'] == "Manager"){
        header("Location: manager/manager.php");
    }
    else{
        header("Location: user/user.php");
    }

    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenGrow · Login</title>

<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Fraunces:opsz,wght@9..144,300;9..144,600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="./assets/style.css">

</head>

<body>

<div class="card">

<div class="brand">
<span class="brand-logo">🌿 GreenGrow</span>
<span class="brand-sub">Farm Management</span>
<div class="brand-divider"></div>
</div>

<h1>Welcome back</h1>
<p class="subtitle">Sign in to your account</p>

<?php
if(isset($_GET['error'])){
echo '<div class="error-msg show">⚠ Invalid username or password</div>';
}
?>

<form action="login.php" method="POST">

<div class="field">
<label>Username</label>

<div class="input-wrap">
<span class="icon">👤</span>
<input type="text" name="email" placeholder="Enter your username" required>
</div>

</div>

<div class="field">

<label>Password</label>

<div class="input-wrap">
<span class="icon">🔒</span>
<input type="password" name="password" placeholder="Enter your password" required>
</div>

</div>

<button class="btn-login" type="submit">Sign In</button>

</form>

<div class="card-footer">
GreenGrow © 2026 · All rights reserved
</div>

</div>

</body>
</html>