<?php
require_once __DIR__ . '/../config/app.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (!empty($_SESSION['is_admin'])) {
        header('Location: ' . BASE_URL . 'admin/');
    } elseif (hasApprovedRole()) {
        header('Location: ' . getDashboardUrl($_SESSION['role_id']));
    } else {
        header('Location: ' . BASE_URL . 'auth/apply_role.php');
    }
    exit();
}

$error = '';

// Handle traditional login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // Check if account is locked
            if (!empty($user['account_locked'])) {
                $error = 'Your account has been locked. Please contact the administrator.';
                // Log failed attempt
                $stmt_log = $conn->prepare("INSERT INTO login_attempts (email, ip_address, user_agent, success) VALUES (?,?,?,0)");
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
                $stmt_log->bind_param("sss", $email, $ip, $ua);
                $stmt_log->execute();
            } elseif (!empty($user['password']) && password_verify($password, $user['password'])) {
                // Successful login — reset failed attempts
                $conn->query("UPDATE users SET failed_login_attempts=0, account_locked=0 WHERE id=" . intval($user['id']));
                createUserSession($user);
                
                // Log success
                $stmt_log = $conn->prepare("INSERT INTO login_attempts (email, ip_address, user_agent, success) VALUES (?,?,?,1)");
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
                $stmt_log->bind_param("sss", $email, $ip, $ua);
                $stmt_log->execute();
                
                // Update last login
                $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update->bind_param("i", $user['id']);
                $update->execute();
                
                logActivity($conn, $user['id'], 'Login', 'User logged in via email/password', 'auth');
                
                // Admins go directly to the admin panel
                if (!empty($user['is_admin'])) {
                    header('Location: ' . BASE_URL . 'admin/');
                } elseif ($user['role_status'] === 'approved' && $user['role_id']) {
                    header('Location: ' . getDashboardUrl($user['role_id']));
                } else {
                    header('Location: ' . BASE_URL . 'auth/apply_role.php');
                }
                exit();
            } else {
                // Failed login — increment counter
                $new_attempts = intval($user['failed_login_attempts'] ?? 0) + 1;
                $lock = $new_attempts >= MAX_LOGIN_ATTEMPTS ? 1 : 0;
                $conn->query("UPDATE users SET failed_login_attempts=$new_attempts, account_locked=$lock WHERE id=" . intval($user['id']));
                
                // Log failure
                $stmt_log = $conn->prepare("INSERT INTO login_attempts (email, ip_address, user_agent, success) VALUES (?,?,?,0)");
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
                $stmt_log->bind_param("sss", $email, $ip, $ua);
                $stmt_log->execute();
                
                if ($lock) {
                    $error = 'Too many failed attempts. Your account has been locked. Contact the administrator.';
                } else {
                    $remaining = MAX_LOGIN_ATTEMPTS - $new_attempts;
                    $error = 'Invalid email or password. ' . $remaining . ' attempt(s) remaining before lockout.';
                }
            }
        } else {
            $error = 'No account found with this email. Please sign up first.';
        }
    }
}

// Generate Google OAuth URL
$google_auth_url = getGoogleAuthUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Halal Keeps</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<div class="auth-page">
    <!-- Visual Side -->
    <div class="auth-visual">
        <div class="auth-visual-content">
            <div class="auth-logo">
                <i class="fas fa-certificate"></i>
            </div>
            <h2>Halal Keeps</h2>
            <p>Your trusted partner in halal certification. Sign in to access the halal certification portal, manage applications, and more.</p>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 40px;">
                <div style="background: rgba(255,255,255,0.1); border-radius: 12px; padding: 20px; text-align: center;">
                    <div style="font-family: var(--font-display); font-size: 1.8rem; font-weight: 800; color: var(--accent-300);">500+</div>
                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.6); margin-top: 4px;">Businesses Certified</div>
                </div>
                <div style="background: rgba(255,255,255,0.1); border-radius: 12px; padding: 20px; text-align: center;">
                    <div style="font-family: var(--font-display); font-size: 1.8rem; font-weight: 800; color: var(--accent-300);">10K+</div>
                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.6); margin-top: 4px;">Products Verified</div>
                </div>
                <div style="background: rgba(255,255,255,0.1); border-radius: 12px; padding: 20px; text-align: center;">
                    <div style="font-family: var(--font-display); font-size: 1.8rem; font-weight: 800; color: var(--accent-300);">99%</div>
                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.6); margin-top: 4px;">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Form Side -->
    <div class="auth-form-section">
        <div class="auth-form-container">
            <a href="<?= BASE_URL ?>" style="display: inline-flex; align-items: center; gap: 6px; color: var(--primary-600); font-weight: 500; font-size: 0.9rem; margin-bottom: 32px;">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            
            <h2>Welcome Back</h2>
            <p class="auth-subtitle">Sign in to your account to continue</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                    <button class="close-alert"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>Account created successfully! Please sign in.</span>
                    <button class="close-alert"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span>You have been logged out successfully.</span>
                    <button class="close-alert"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            
            <!-- Google Sign In Button -->
            <a href="<?= $google_auth_url ?>" class="btn btn-google btn-block" id="googleSignIn">
                <svg width="24" height="24" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Sign in with Google
            </a>
            
            <div class="auth-divider">or sign in with email</div>
            
            <!-- Email/Password Form -->
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" value="<?= htmlspecialchars($email ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 8px;">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <p style="text-align: center; margin-top: 24px; color: var(--neutral-500); font-size: 0.9rem;">
                Don't have an account? 
                <a href="<?= BASE_URL ?>auth/register.php" style="color: var(--primary-600); font-weight: 600;">Create Account</a>
            </p>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
