<?php
/**
 * API endpoint: check if the current user has a given permission.
 * GET/POST ?permission=manage_users
 * Returns JSON { allowed: true|false }
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../admin_functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['allowed' => false, 'reason' => 'Not authenticated']);
    exit();
}

$permission = sanitize($_REQUEST['permission'] ?? '');
$allowed    = adminCan($permission);

// Log the permission check
logAdminActivity($conn, $_SESSION['user_id'],
    'Permission Check: ' . $permission, 'api', null,
    $allowed ? 'GRANTED' : 'DENIED');

echo json_encode([
    'allowed'    => $allowed,
    'user_id'    => $_SESSION['user_id'],
    'permission' => $permission,
    'is_admin'   => !empty($_SESSION['is_admin']),
]);
