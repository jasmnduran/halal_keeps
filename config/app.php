<?php
// Main application bootstrap
session_start();

// Load environment variables from project root .env file
$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath) && is_readable($envPath)) {
    $envLines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $envLine) {
        $trimmedLine = trim($envLine);
        if ($trimmedLine === '' || strpos($trimmedLine, '#') === 0) {
            continue;
        }

        $separatorPos = strpos($trimmedLine, '=');
        if ($separatorPos === false) {
            continue;
        }

        $name = trim(substr($trimmedLine, 0, $separatorPos));
        $value = trim(substr($trimmedLine, $separatorPos + 1));

        if ($name === '') {
            continue;
        }

        if (
            strlen($value) >= 2 &&
            (
                ($value[0] === '"' && substr($value, -1) === '"') ||
                ($value[0] === "'" && substr($value, -1) === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// Load configuration
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../includes/functions.php';

// DLP Feature: Enforce Global Session Timeout
if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
    // Get timeout setting from DB
    $timeout_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'session_timeout_minutes'");
    $timeout_minutes = $timeout_query && $timeout_query->num_rows > 0 ? intval($timeout_query->fetch_assoc()['setting_value']) : 30;
    
    if (time() - $_SESSION['last_activity'] > ($timeout_minutes * 60)) {
        // Session expired
        session_unset();
        session_destroy();
        header("Location: " . BASE_URL . "auth/login.php?error=" . urlencode("Your session has expired due to inactivity."));
        exit();
    }
}
// Update last activity time
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
}

// Create upload directories if they don't exist
$upload_dirs = [
    UPLOAD_DIR,
    UPLOAD_DIR . 'avatars/',
    UPLOAD_DIR . 'documents/',
    UPLOAD_DIR . 'certificates/',
    UPLOAD_DIR . 'laboratory/',
    UPLOAD_DIR . 'role_applications/',
    UPLOAD_DIR . 'restaurant_images/',
    UPLOAD_DIR . 'menu_images/',
    UPLOAD_DIR . 'receipts/',
];

foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>
