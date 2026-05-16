<?php
require_once __DIR__ . '/../config/app.php';

// Handle Google OAuth callback
if (!isset($_GET['code'])) {
    // No code received, redirect to login
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$code = $_GET['code'];

try {
    // Exchange code for access token
    $token_data = getGoogleAccessToken($code);
    
    if (isset($token_data['error'])) {
        throw new Exception('Failed to get access token: ' . ($token_data['error_description'] ?? $token_data['error']));
    }
    
    $access_token = $token_data['access_token'];
    
    // Get user info from Google
    $user_info = getGoogleUserInfo($access_token);
    
    if (!isset($user_info['email'])) {
        throw new Exception('Failed to get user information from Google.');
    }
    
    $google_id = $user_info['sub'];
    $email = $user_info['email'];
    $full_name = $user_info['name'] ?? '';
    $first_name = $user_info['given_name'] ?? '';
    $last_name = $user_info['family_name'] ?? '';
    $avatar = $user_info['picture'] ?? '';
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.google_id = ? OR u.email = ?");
    $stmt->bind_param("ss", $google_id, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        // Existing user - update Google ID and avatar if needed
        $update = $conn->prepare("UPDATE users SET google_id = ?, avatar = ?, last_login = NOW(), email_verified = 1 WHERE id = ?");
        $update->bind_param("ssi", $google_id, $avatar, $user['id']);
        $update->execute();
        
        // Refresh user data
        $stmt2 = $conn->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt2->bind_param("i", $user['id']);
        $stmt2->execute();
        $user = $stmt2->get_result()->fetch_assoc();
        
        createUserSession($user);
        logActivity($conn, $user['id'], 'Google Login', 'User logged in via Google OAuth', 'auth');
        
    } else {
        // New user - create account
        $stmt = $conn->prepare("INSERT INTO users (google_id, email, full_name, first_name, last_name, avatar, email_verified, role_status, last_login) VALUES (?, ?, ?, ?, ?, ?, 1, 'none', NOW())");
        $stmt->bind_param("ssssss", $google_id, $email, $full_name, $first_name, $last_name, $avatar);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create user account.');
        }
        
        $new_user_id = $stmt->insert_id;
        
        // Fetch the new user
        $stmt2 = $conn->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $stmt2->bind_param("i", $new_user_id);
        $stmt2->execute();
        $user = $stmt2->get_result()->fetch_assoc();
        
        createUserSession($user);
        logActivity($conn, $new_user_id, 'Google Registration', 'New user registered via Google OAuth', 'auth');
    }
    
    // Redirect based on role status
    if ($user['role_status'] === 'approved' && $user['role_id']) {
        header('Location: ' . getDashboardUrl($user['role_id']));
    } else {
        header('Location: ' . BASE_URL . 'auth/apply_role.php');
    }
    exit();
    
} catch (Exception $e) {
    $_SESSION['flash_message'] = 'Google sign-in failed: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}
?>
