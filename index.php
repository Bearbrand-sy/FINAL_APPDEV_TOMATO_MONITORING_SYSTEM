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
    } elseif ($_GET['error'] == "google_auth_failed") {
        $errorMsg = "⚠ Google sign-in failed. Please try again.";
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
        
        /* Google button styles */
        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            background: white;
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 10px 12px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 500;
            color: #1f2e1b;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            text-decoration: none;
            margin-top: 12px;
        }
        .google-btn:hover {
            background: #f8faf5;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border-color: #bdc7b0;
        }
        .google-icon {
            width: 20px;
            height: 20px;
            display: inline-block;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><path fill="%23FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z" /><path fill="%233E2723" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z" /><path fill="%234CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z" /><path fill="%231976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z" /></svg>') no-repeat center;
            background-size: contain;
        }
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0 12px;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8cf;
        }
        .divider span {
            margin: 0 12px;
            font-size: 0.7rem;
            font-weight: 500;
            color: #7a8f6c;
            background: white;
            padding: 0 6px;
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

    <!-- Divider "OR" -->
    <div class="divider">
        <span>or</span>
    </div>

    <!-- Google Login Button -->
    <a href="oauth2callback.php" class="google-btn">
        <div class="google-icon"></div>
        <span>Sign in with Google</span>
    </a>

    <div class="card-footer">
        GreenGrow © 2026 · All rights reserved
    </div>

</div>

</body>
</html>