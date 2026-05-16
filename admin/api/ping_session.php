<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../admin_functions.php';
if (!isLoggedIn()) { http_response_code(401); exit(); }
enforceSessionTimeout();
$_SESSION['last_activity'] = time();

// Keep last_seen fresh in the DB so the admin can see who's online
$uid = intval($_SESSION['user_id'] ?? 0);
if ($uid) {
    // Add column if it doesn't exist yet (idempotent)
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_seen TIMESTAMP NULL DEFAULT NULL");
    $conn->query("UPDATE users SET last_seen = NOW() WHERE id = $uid");
}

header('Content-Type: application/json');
echo json_encode(['ok' => true]);
