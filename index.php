<?php
session_start();

// ── Already logged in → redirect to their dashboard ──
if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];

    if ($role == "Admin") {
        header("Location: admin/dashboard.php");
    } elseif ($role == "Manager") {
        header("Location: manager/manager.php");
    } elseif ($role == "Driver") {
        header("Location: driver/driver.php");
    } else {
        header("Location: user/user.php");
    }

    exit();
}

// ── Error handling ──
$errorMsg = "";

if (isset($_GET['error'])) {
    if ($_GET['error'] == "invalid_password") {
        $errorMsg = "⚠ Incorrect password. Please try again.";
    } elseif ($_GET['error'] == "user_not_found") {
        $errorMsg = "⚠ Email not found.";
    } else {
        $errorMsg = "⚠ Login failed. Please try again.";
    }
}

// Preserve email input
$emailValue = htmlspecialchars($_GET['email'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenGrow · Login</title>

    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Fraunces:opsz,wght@9..144,300;9..144,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/style.css">

    <style>
        .error-msg {
            display: none;
            align-items: center;
            gap: 8px;
            background: #fee2e2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 9px 13px;
            font-size: .75rem;
            color: #b91c1c;
            margin-bottom: 16px;
        }
        .error-msg.show {
            display: flex;
            animation: shake .3s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
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

    <!-- Error Message -->
    <?php if (!empty($errorMsg)): ?>
        <div class="error-msg show"><?= $errorMsg ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">

        <div class="field">
            <label>Email</label>
            <div class="input-wrap">
                <span class="icon">👤</span>
                <input type="text" name="email"
                       placeholder="Enter your email"
                       value="<?= $emailValue ?>"
                       autocomplete="username"
                       required>
            </div>
        </div>

        <div class="field">
            <label>Password</label>
            <div class="input-wrap">
                <span class="icon">🔒</span>
                <input type="password" name="password"
                       placeholder="Enter your password"
                       autocomplete="current-password"
                       required>
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