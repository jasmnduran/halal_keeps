<?php
if (!function_exists('envValue')) {
    function envValue($key, $default = '') {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return $value === false ? $default : $value;
    }
}

// System Constants
define('SITE_NAME', 'Halal Institute of Development Philippines');
define('SITE_TAGLINE', 'Halal Certification Body');
define('BASE_URL', '/halal_final/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '329994807347-7oqrqvds3rhj7jfdiis9t0o2p7s2iicd.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', envValue('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI', 'http://localhost/halal_final/auth/google_callback.php');

// PayMongo test API keys
define('PAYMONGO_SECRET_KEY', envValue('PAYMONGO_SECRET_KEY'));
define('PAYMONGO_PUBLIC_KEY', envValue('PAYMONGO_PUBLIC_KEY'));

// Role IDs (matching database)
define('ROLE_CUSTOMER', 1);
define('ROLE_BUSINESS_OWNER', 2);
define('ROLE_EVALUATOR', 3);
define('ROLE_AUDITOR_TECHNICAL', 4);
define('ROLE_AUDITOR_SHARIAH', 5);
define('ROLE_IMPARTIAL_COMMITTEE', 6);
define('ROLE_DECISION_COMMITTEE', 7);
define('ROLE_PRESIDENT', 8);
define('ROLE_RECEIVING_OFFICER', 9);
define('ROLE_LABORATORY_ANALYST', 10);
define('ROLE_LAB_ANALYST', 10); // alias

// Application Status Constants
define('STATUS_DRAFT', 'draft');
define('STATUS_SUBMITTED', 'submitted');
define('STATUS_UNDER_REVIEW', 'under_review');
define('STATUS_VERIFIED', 'verified');
define('STATUS_RETURNED', 'returned');
define('STATUS_REJECTED', 'rejected');
define('STATUS_APPROVED', 'approved');

// Allowed file types
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Admin role
define('ROLE_ADMIN', 11);

// Security: session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Security: max failed login attempts before lockout
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes in seconds

// Encryption key for sensitive fields (change in production!)
define('FIELD_ENCRYPT_KEY', envValue('FIELD_ENCRYPT_KEY'));

// Data classification levels
define('DATA_PUBLIC',       'public');
define('DATA_INTERNAL',     'internal');
define('DATA_CONFIDENTIAL', 'confidential');
define('DATA_RESTRICTED',   'restricted');
?>
