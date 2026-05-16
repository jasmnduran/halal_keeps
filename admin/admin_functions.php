<?php
/**
 * Admin-specific helper functions
 * Security, logging, encryption, DLP, RBAC
 */

// ── RBAC ─────────────────────────────────────────────────────────────────────

/** Require the current user to be an admin; redirect otherwise */
function requireAdmin() {
    requireLogin();
    if (empty($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        logAdminActivity($GLOBALS['conn'] ?? null, $_SESSION['user_id'] ?? 0,
            'Unauthorized Admin Access', 'admin', null,
            'Attempted to access admin panel without privileges');
        header('Location: ' . BASE_URL . 'auth/login.php?error=unauthorized');
        exit();
    }
}

/** Check if the logged-in user has a specific permission (role-based) */
function adminCan(string $permission): bool {
    // All admins currently have full access; extend here for granular RBAC
    return !empty($_SESSION['is_admin']);
}

// ── Logging ───────────────────────────────────────────────────────────────────

/** Log an admin action to admin_activity_log */
function logAdminActivity($conn, int $adminId, string $action,
    string $targetType = '', ?int $targetId = null, string $details = ''): void {
    if (!$conn) return;
    $ip   = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conn->prepare(
        "INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, details, ip_address)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ississ", $adminId, $action, $targetType, $targetId, $details, $ip);
    $stmt->execute();
}

/** Log a login attempt (success or failure) */
function logLoginAttempt($conn, string $email, bool $success): void {
    if (!$conn) return;
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $ok  = $success ? 1 : 0;
    $stmt = $conn->prepare(
        "INSERT INTO login_attempts (email, ip_address, user_agent, success) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("sssi", $email, $ip, $ua, $ok);
    $stmt->execute();
}

/** Count recent failed login attempts for an email (within last 15 min) */
function countRecentFailedAttempts($conn, string $email): int {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) as c FROM login_attempts
         WHERE email = ? AND success = 0
         AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)"
    );
    $dur = LOCKOUT_DURATION;
    $stmt->bind_param("si", $email, $dur);
    $stmt->execute();
    return (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
}

// ── Encryption ────────────────────────────────────────────────────────────────

/**
 * Encrypt a sensitive string using AES-256-CBC.
 * Returns base64-encoded "iv:ciphertext".
 */
function encryptField(string $plaintext): string {
    $key = hash('sha256', FIELD_ENCRYPT_KEY, true);
    $iv  = random_bytes(16);
    $enc = openssl_encrypt($plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $enc);
}

/**
 * Decrypt a value previously encrypted with encryptField().
 * Returns the original plaintext, or empty string on failure.
 */
function decryptField(string $encoded): string {
    try {
        $key  = hash('sha256', FIELD_ENCRYPT_KEY, true);
        $raw  = base64_decode($encoded);
        if (strlen($raw) < 17) return '';
        $iv   = substr($raw, 0, 16);
        $enc  = substr($raw, 16);
        $dec  = openssl_decrypt($enc, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $dec !== false ? $dec : '';
    } catch (Exception $e) {
        return '';
    }
}

// ── Session Security ──────────────────────────────────────────────────────────

/** Enforce session timeout; call on every protected page load */
function enforceSessionTimeout(): void {
    if (!isLoggedIn()) return;
    $now = time();
    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/login.php?timeout=1');
        exit();
    }
    $_SESSION['last_activity'] = $now;

    // Keep last_seen fresh in the DB (throttled: update at most once per 60s)
    $uid = intval($_SESSION['user_id'] ?? 0);
    if ($uid && (!isset($_SESSION['_last_seen_written']) || ($now - $_SESSION['_last_seen_written']) >= 60)) {
        $conn = $GLOBALS['conn'] ?? null;
        if ($conn) {
            $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_seen TIMESTAMP NULL DEFAULT NULL");
            $conn->query("UPDATE users SET last_seen = NOW() WHERE id = $uid");
            $_SESSION['_last_seen_written'] = $now;
        }
    }
}

/** Regenerate session ID to prevent fixation */
function secureSessionRegenerate(): void {
    if (!isset($_SESSION['_regenerated'])) {
        session_regenerate_id(true);
        $_SESSION['_regenerated'] = time();
    } elseif ((time() - $_SESSION['_regenerated']) > 300) {
        session_regenerate_id(true);
        $_SESSION['_regenerated'] = time();
    }
}

// ── Data Classification ───────────────────────────────────────────────────────

/** Get all data classifications from DB */
function getDataClassifications($conn): array {
    $result = $conn->query("SELECT * FROM data_classifications ORDER BY table_name, column_name");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/** Get classification badge HTML */
function getClassificationBadge(string $level): string {
    $map = [
        'public'       => ['bg' => '#dcfce7', 'color' => '#166534', 'icon' => 'fa-globe'],
        'internal'     => ['bg' => '#dbeafe', 'color' => '#1e40af', 'icon' => 'fa-building'],
        'confidential' => ['bg' => '#fef9c3', 'color' => '#854d0e', 'icon' => 'fa-lock'],
        'restricted'   => ['bg' => '#fee2e2', 'color' => '#991b1b', 'icon' => 'fa-shield-alt'],
    ];
    $cfg = $map[$level] ?? $map['internal'];
    return sprintf(
        '<span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;text-transform:uppercase;background:%s;color:%s"><i class="fas %s" style="font-size:0.65rem"></i>%s</span>',
        $cfg['bg'], $cfg['color'], $cfg['icon'], ucfirst($level)
    );
}

// ── Utility ───────────────────────────────────────────────────────────────────

/** Mask a sensitive string for display (e.g. email → j***@gmail.com) */
function maskSensitive(string $value, string $type = 'email'): string {
    if ($type === 'email') {
        $parts = explode('@', $value);
        if (count($parts) === 2) {
            return substr($parts[0], 0, 1) . '***@' . $parts[1];
        }
    }
    if ($type === 'phone') {
        return substr($value, 0, 3) . '****' . substr($value, -3);
    }
    return substr($value, 0, 2) . str_repeat('*', max(0, strlen($value) - 4)) . substr($value, -2);
}

/** Get admin dashboard stats */
function getAdminStats($conn): array {
    return [
        'total_users'       => (int)$conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'],
        'active_users'      => (int)$conn->query("SELECT COUNT(*) as c FROM users WHERE is_active=1")->fetch_assoc()['c'],
        'locked_users'      => (int)$conn->query("SELECT COUNT(*) as c FROM users WHERE account_locked=1")->fetch_assoc()['c'],
        'admin_users'       => (int)$conn->query("SELECT COUNT(*) as c FROM users WHERE is_admin=1")->fetch_assoc()['c'],
        'online_users'      => (int)$conn->query("SELECT COUNT(*) as c FROM users WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetch_assoc()['c'],
        'login_attempts_today' => (int)$conn->query("SELECT COUNT(*) as c FROM login_attempts WHERE DATE(attempted_at)=CURDATE()")->fetch_assoc()['c'],
        'failed_logins_today'  => (int)$conn->query("SELECT COUNT(*) as c FROM login_attempts WHERE DATE(attempted_at)=CURDATE() AND success=0")->fetch_assoc()['c'],
        'admin_actions_today'  => (int)$conn->query("SELECT COUNT(*) as c FROM admin_activity_log WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'],
        'total_roles'       => (int)$conn->query("SELECT COUNT(*) as c FROM roles")->fetch_assoc()['c'],
    ];
}
