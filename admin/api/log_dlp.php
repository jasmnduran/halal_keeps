<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../admin_functions.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['ok' => false]); exit(); }

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$type   = substr(sanitize($body['type']   ?? 'unknown'), 0, 100);
$detail = substr(sanitize($body['detail'] ?? ''),        0, 500);

logAdminActivity($conn, $_SESSION['user_id'], 'DLP Event: ' . $type, 'dlp', null, $detail);
echo json_encode(['ok' => true]);
