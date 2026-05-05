<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load environment variables first
require_once __DIR__ . '/loadenv.php';

session_start();
require_once __DIR__ . '/db.php';

// Check if user is already logged in
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

// Get Google OAuth credentials from .env file
$google_oauth_client_id = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
$google_oauth_client_secret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? '';
$google_oauth_redirect_uri = $_ENV['GOOGLE_REDIRECT_URI'] ?? '';
$google_oauth_version = 'v3';

// If no client ID, show error
if (empty($google_oauth_client_id) || empty($google_oauth_client_secret)) {
    die("Google OAuth credentials not configured. Please check your .env file.");
}

// CHECK: If there's NO code parameter, redirect to Google login page
if (!isset($_GET['code']) || empty($_GET['code'])) {
    // No code - first time visiting this page, redirect to Google
    $params = [
        'response_type' => 'code',
        'client_id' => $google_oauth_client_id,
        'redirect_uri' => $google_oauth_redirect_uri,
        'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
        'access_type' => 'offline',
        'prompt' => 'consent'
    ];
    $google_login_url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    header('Location: ' . $google_login_url);
    exit();
}

// If we have a code parameter, process the Google login
if (isset($_GET['code']) && !empty($_GET['code'])) {
    // Execute cURL request to retrieve the access token
    $params = [
        'code' => $_GET['code'],
        'client_id' => $google_oauth_client_id,
        'client_secret' => $google_oauth_client_secret,
        'redirect_uri' => $google_oauth_redirect_uri,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Only for local development
    $response = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($response, true);
    
    // Make sure access token is valid
    if (isset($response['access_token']) && !empty($response['access_token'])) {
        // Execute cURL request to retrieve the user info
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/' . $google_oauth_version . '/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $response['access_token']]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for local development
        $response = curl_exec($ch);
        curl_close($ch);
        $profile = json_decode($response, true);
        
        // Make sure the profile data exists
        if (isset($profile['email'])) {
            $google_name_parts = [];
            $google_name_parts[] = isset($profile['given_name']) ? preg_replace('/[^a-zA-Z0-9\s]/', '', $profile['given_name']) : '';
            $google_name_parts[] = isset($profile['family_name']) ? preg_replace('/[^a-zA-Z0-9\s]/', '', $profile['family_name']) : '';
            
            // Use the existing $conn from db.php
            global $conn;
            
            $email = $profile['email'];
            $name = trim(implode(' ', $google_name_parts));
            if (empty($name)) {
                $name = explode('@', $email)[0];
            }
            $google_id = $profile['id'] ?? '';
            
            // Check if user exists in your database
            $sql = "SELECT * FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // User exists - log them in
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['google_loggedin'] = TRUE;
                $_SESSION['google_email'] = $profile['email'];
                $_SESSION['google_name'] = $name;
                $_SESSION['google_picture'] = isset($profile['picture']) ? $profile['picture'] : '';
                
                // Update last login and google_id
                $update_sql = "UPDATE users SET last_login = NOW(), google_id = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("si", $google_id, $user['id']);
                $update_stmt->execute();
                
                // Redirect based on role
                if ($user['role'] == "Admin") {
                    header("Location: admin/dashboard.php");
                } elseif ($user['role'] == "Manager") {
                    header("Location: manager/manager.php");
                } elseif ($user['role'] == "Driver") {
                    header("Location: driver/driver.php");
                } else {
                    header("Location: user/user.php");
                }
            } else {
                // User doesn't exist - auto-register with Google
                $default_role = 'User';
                $insert_sql = "INSERT INTO users (name, email, google_id, role, last_login) VALUES (?, ?, ?, ?, NOW())";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("ssss", $name, $email, $google_id, $default_role);
                
                if ($insert_stmt->execute()) {
                    $new_user_id = $conn->insert_id;
                    $_SESSION['user_id'] = $new_user_id;
                    $_SESSION['id'] = $new_user_id;
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = $default_role;
                    $_SESSION['name'] = $name;
                    $_SESSION['google_loggedin'] = TRUE;
                    $_SESSION['google_email'] = $email;
                    $_SESSION['google_name'] = $name;
                    $_SESSION['google_picture'] = isset($profile['picture']) ? $profile['picture'] : '';
                    
                    header("Location: user/user.php");
                } else {
                    header("Location: index.php?error=google_auth_failed");
                }
            }
            
            $conn->close();
            exit;
        } else {
            header("Location: index.php?error=google_auth_failed");
            exit;
        }
    } else {
        header("Location: index.php?error=google_auth_failed");
        exit;
    }
} else {
    // Something went wrong
    header("Location: index.php?error=google_auth_failed");
    exit;
}
?>