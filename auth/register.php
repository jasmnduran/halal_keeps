<?php
require_once __DIR__ . '/../config/app.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'auth/apply_role.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif (!preg_match('/[\W_]/', $password)) {
        $error = 'Password must contain at least one special character (e.g. !@#$%^&*).';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists. Try signing in instead.';
        } else {
            $full_name = $first_name . ' ' . $last_name;
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (email, full_name, first_name, last_name, phone, password, email_verified, role_status) VALUES (?, ?, ?, ?, ?, ?, 1, 'none')");
            $stmt->bind_param("ssssss", $email, $full_name, $first_name, $last_name, $phone, $hashed_password);
            
            if ($stmt->execute()) {
                logActivity($conn, $stmt->insert_id, 'Registration', 'User registered via email/password', 'auth');
                header('Location: ' . BASE_URL . 'auth/login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$google_auth_url = getGoogleAuthUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Halal Institute of Development Philippines</title>
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
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Join Our Platform</h2>
            <p>Create your account and apply for your role. Whether you're a business owner seeking certification or an industry professional, we have a place for you.</p>
            
            <div style="margin-top: 40px; text-align: left;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; color: rgba(255,255,255,0.85);">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-check" style="font-size: 0.7rem;"></i>
                    </div>
                    <span>Sign up with Google for quick access</span>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; color: rgba(255,255,255,0.85);">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-check" style="font-size: 0.7rem;"></i>
                    </div>
                    <span>Choose your role after registration</span>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; color: rgba(255,255,255,0.85);">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-check" style="font-size: 0.7rem;"></i>
                    </div>
                    <span>Submit requirements for role approval</span>
                </div>
                <div style="display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.85);">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-check" style="font-size: 0.7rem;"></i>
                    </div>
                    <span>Access your personalized dashboard</span>
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
            
            <h2>Create Your Account</h2>
            <p class="auth-subtitle">Sign up to get started with the halal certification portal</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= $error ?></span>
                    <button class="close-alert"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            
            <!-- Google Sign Up Button -->
            <a href="<?= $google_auth_url ?>" class="btn btn-google btn-block">
                <svg width="24" height="24" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Sign up with Google
            </a>
            
            <div class="auth-divider">or create account with email</div>
            
            <form method="POST" action="" id="registerForm">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" class="form-control" placeholder="Juan" value="<?= htmlspecialchars($first_name ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" class="form-control" placeholder="Dela Cruz" value="<?= htmlspecialchars($last_name ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address <span class="required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="juan@example.com" value="<?= htmlspecialchars($email ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="form-control" placeholder="09123456789" value="<?= htmlspecialchars($phone ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Min. 8 chars with uppercase, number &amp; symbol" required minlength="8" autocomplete="new-password">
                        <button type="button" id="togglePassword" onclick="togglePasswordVisibility('password','togglePassword')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--neutral-400);padding:0;">
                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>

                    <!-- Password strength bar -->
                    <div style="margin-top: 8px;">
                        <div style="display:flex;gap:4px;margin-bottom:6px;">
                            <div class="strength-bar" id="bar1" style="height:4px;flex:1;border-radius:2px;background:#e5e7eb;transition:background .3s;"></div>
                            <div class="strength-bar" id="bar2" style="height:4px;flex:1;border-radius:2px;background:#e5e7eb;transition:background .3s;"></div>
                            <div class="strength-bar" id="bar3" style="height:4px;flex:1;border-radius:2px;background:#e5e7eb;transition:background .3s;"></div>
                            <div class="strength-bar" id="bar4" style="height:4px;flex:1;border-radius:2px;background:#e5e7eb;transition:background .3s;"></div>
                        </div>
                        <p id="strengthLabel" style="font-size:0.78rem;color:var(--neutral-400);margin:0 0 8px;"></p>
                    </div>

                    <!-- Policy checklist -->
                    <ul id="passwordChecklist" style="list-style:none;padding:0;margin:0;display:grid;grid-template-columns:1fr 1fr;gap:4px 12px;">
                        <li id="check-length"  style="font-size:0.8rem;color:#9ca3af;display:flex;align-items:center;gap:6px;"><i class="fas fa-circle" style="font-size:0.45rem;"></i> At least 8 characters</li>
                        <li id="check-upper"   style="font-size:0.8rem;color:#9ca3af;display:flex;align-items:center;gap:6px;"><i class="fas fa-circle" style="font-size:0.45rem;"></i> One uppercase letter</li>
                        <li id="check-lower"   style="font-size:0.8rem;color:#9ca3af;display:flex;align-items:center;gap:6px;"><i class="fas fa-circle" style="font-size:0.45rem;"></i> One lowercase letter</li>
                        <li id="check-number"  style="font-size:0.8rem;color:#9ca3af;display:flex;align-items:center;gap:6px;"><i class="fas fa-circle" style="font-size:0.45rem;"></i> One number</li>
                        <li id="check-special" style="font-size:0.8rem;color:#9ca3af;display:flex;align-items:center;gap:6px;"><i class="fas fa-circle" style="font-size:0.45rem;"></i> One special character</li>
                    </ul>
                </div>
                
                <div class="form-group" style="margin-top:16px;">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <div style="position: relative;">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repeat your password" required autocomplete="new-password">
                        <button type="button" onclick="togglePasswordVisibility('confirm_password','toggleConfirmIcon')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--neutral-400);padding:0;">
                            <i class="fas fa-eye" id="toggleConfirmIcon"></i>
                        </button>
                    </div>
                    <p id="matchMsg" style="font-size:0.8rem;margin:5px 0 0;display:none;"></p>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 8px;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <p style="text-align: center; margin-top: 24px; color: var(--neutral-500); font-size: 0.9rem;">
                Already have an account? 
                <a href="<?= BASE_URL ?>auth/login.php" style="color: var(--primary-600); font-weight: 600;">Sign In</a>
            </p>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script>
// ── Password visibility toggle ──────────────────────────────────────────────
function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// ── Password strength & policy checklist ────────────────────────────────────
const passwordInput  = document.getElementById('password');
const confirmInput   = document.getElementById('confirm_password');
const bars           = [document.getElementById('bar1'), document.getElementById('bar2'),
                        document.getElementById('bar3'), document.getElementById('bar4')];
const strengthLabel  = document.getElementById('strengthLabel');
const matchMsg       = document.getElementById('matchMsg');

const checks = {
    length:  { el: document.getElementById('check-length'),  test: p => p.length >= 8 },
    upper:   { el: document.getElementById('check-upper'),   test: p => /[A-Z]/.test(p) },
    lower:   { el: document.getElementById('check-lower'),   test: p => /[a-z]/.test(p) },
    number:  { el: document.getElementById('check-number'),  test: p => /[0-9]/.test(p) },
    special: { el: document.getElementById('check-special'), test: p => /[\W_]/.test(p) },
};

const strengthConfig = [
    { label: '',         color: '#e5e7eb' },
    { label: 'Weak',     color: '#ef4444' },
    { label: 'Fair',     color: '#f97316' },
    { label: 'Good',     color: '#eab308' },
    { label: 'Strong',   color: '#22c55e' },
];

function updateChecklist(password) {
    let passed = 0;
    Object.values(checks).forEach(({ el, test }) => {
        const ok = test(password);
        if (ok) {
            passed++;
            el.style.color = '#16a34a';
            el.querySelector('i').className = 'fas fa-check-circle';
            el.querySelector('i').style.fontSize = '0.75rem';
        } else {
            el.style.color = '#9ca3af';
            el.querySelector('i').className = 'fas fa-circle';
            el.querySelector('i').style.fontSize = '0.45rem';
        }
    });
    return passed; // 0–5
}

function updateStrengthBars(score) {
    // score 0–5 → map to 0–4 bar levels
    const level = score === 0 ? 0 : score <= 1 ? 1 : score <= 2 ? 2 : score <= 4 ? 3 : 4;
    const cfg   = strengthConfig[level];
    bars.forEach((bar, i) => {
        bar.style.background = i < level ? cfg.color : '#e5e7eb';
    });
    strengthLabel.textContent = password.value.length > 0 ? cfg.label : '';
    strengthLabel.style.color = cfg.color;
}

passwordInput.addEventListener('input', function () {
    const password = this.value;
    const score    = updateChecklist(password);
    updateStrengthBars(score);
    if (confirmInput.value.length > 0) checkMatch();
});

// ── Confirm password match indicator ────────────────────────────────────────
function checkMatch() {
    const match = passwordInput.value === confirmInput.value;
    matchMsg.style.display = 'block';
    if (match && confirmInput.value.length > 0) {
        matchMsg.textContent = '✓ Passwords match';
        matchMsg.style.color = '#16a34a';
        confirmInput.style.borderColor = '#16a34a';
    } else {
        matchMsg.textContent = '✗ Passwords do not match';
        matchMsg.style.color = '#ef4444';
        confirmInput.style.borderColor = '#ef4444';
    }
}

confirmInput.addEventListener('input', checkMatch);

// ── Block submit if policy not met ──────────────────────────────────────────
document.getElementById('registerForm').addEventListener('submit', function (e) {
    const p = passwordInput.value;
    const allPassed = Object.values(checks).every(({ test }) => test(p));
    if (!allPassed) {
        e.preventDefault();
        passwordInput.focus();
        // Highlight failed checks
        Object.values(checks).forEach(({ el, test }) => {
            if (!test(p)) el.style.color = '#ef4444';
        });
    }
    if (p !== confirmInput.value) {
        e.preventDefault();
        checkMatch();
        confirmInput.focus();
    }
});
</script>
</body>
</html>
