<?php
/**
 * Helper functions for the Halal Certification System
 */

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Require login - redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit();
    }
}

// Require specific role
function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) $roles = [$roles];
    if (!in_array($_SESSION['role_id'] ?? 0, $roles)) {
        header('Location: ' . BASE_URL . 'auth/apply_role.php');
        exit();
    }
}

// Check if user has completed role application
function hasApprovedRole() {
    return isset($_SESSION['role_id']) && $_SESSION['role_status'] === 'approved';
}

// Get user role name
function getRoleName($roleId, $conn) {
    $stmt = $conn->prepare("SELECT role_name FROM roles WHERE id = ?");
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['role_name'];
    }
    return 'Unassigned';
}

// Sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Generate unique reference number
function generateReference($prefix = 'HDP') {
    return $prefix . '-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

// Upload file
function uploadFile($file, $directory, $allowedTypes = null) {
    if ($allowedTypes === null) {
        $allowedTypes = ALLOWED_FILE_TYPES;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large (max 10MB)'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $targetPath = UPLOAD_DIR . $directory . '/' . $filename;
    
    if (!is_dir(UPLOAD_DIR . $directory)) {
        mkdir(UPLOAD_DIR . $directory, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $directory . '/' . $filename];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

// Create notification
function createNotification($conn, $userId, $title, $message, $type = 'info', $link = '') {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $title, $message, $type, $link);
    return $stmt->execute();
}

// Log activity
function logActivity($conn, $userId, $action, $details = '', $module = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details, ip_address, module) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $action, $details, $ip, $module);
    return $stmt->execute();
}

// Get unread notification count
function getUnreadNotificationCount($conn, $userId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Get user's notifications
function getNotifications($conn, $userId, $limit = 10) {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Format date
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Format date time
function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    return date($format, strtotime($datetime));
}

// Format currency (Philippine Peso)
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function getEnterpriseStandards() {
    return [
        'micro' => [
            'label' => 'Micro Enterprise',
            'technical' => 1,
            'shariah' => 1,
            'amount_each' => 500.00,
        ],
        'small' => [
            'label' => 'Small Enterprise',
            'technical' => 1,
            'shariah' => 1,
            'amount_each' => 700.00,
        ],
        'medium' => [
            'label' => 'Medium Enterprise',
            'technical' => 1,
            'shariah' => 2,
            'amount_each' => 900.00,
        ],
    ];
}

function normalizeEnterpriseType($type) {
    $type = strtolower(trim((string)$type));
    return array_key_exists($type, getEnterpriseStandards()) ? $type : '';
}

function getEnterpriseStandard($type) {
    $type = normalizeEnterpriseType($type);
    $standards = getEnterpriseStandards();
    return $type ? $standards[$type] : null;
}

function buildDefaultAuditorAssignments($enterpriseType) {
    $standard = getEnterpriseStandard($enterpriseType);
    if (!$standard) return [];

    $assignments = [];
    for ($i = 0; $i < $standard['shariah']; $i++) {
        $assignments[] = ['role' => 'shariah', 'auditor_id' => 0, 'amount' => $standard['amount_each']];
    }
    for ($i = 0; $i < $standard['technical']; $i++) {
        $assignments[] = ['role' => 'technical', 'auditor_id' => 0, 'amount' => $standard['amount_each']];
    }
    return $assignments;
}

function getAuditorAssignmentTotal($assignments) {
    $total = 0;
    foreach ($assignments as $assignment) {
        $total += floatval($assignment['amount'] ?? 0);
    }
    return $total;
}

function getEnterpriseStandardSummary($enterpriseType) {
    $standard = getEnterpriseStandard($enterpriseType);
    if (!$standard) return 'Not classified';

    $count = intval($standard['technical']) + intval($standard['shariah']);
    return $standard['label'] . ': ' . $count . ' auditor' . ($count === 1 ? '' : 's') .
        ' (' . intval($standard['shariah']) . ' Shariah, ' . intval($standard['technical']) . ' Technical) at ' .
        formatCurrency($standard['amount_each']) . ' each';
}

function getPayMongoSecretKey() {
    return defined('PAYMONGO_SECRET_KEY') ? PAYMONGO_SECRET_KEY : '';
}

function getPayMongoPublicKey() {
    return defined('PAYMONGO_PUBLIC_KEY') ? PAYMONGO_PUBLIC_KEY : '';
}

function payMongoRequest($method, $endpoint, $payload = null) {
    $secretKey = getPayMongoSecretKey();
    if (empty($secretKey)) {
        return ['success' => false, 'message' => 'PayMongo secret key is not configured.'];
    }

    if (!function_exists('curl_init')) {
        return ['success' => false, 'message' => 'PHP cURL extension is required for PayMongo payments.'];
    }

    $ch = curl_init('https://api.paymongo.com' . $endpoint);
    $headers = [
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($secretKey . ':'),
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return ['success' => false, 'message' => 'PayMongo request failed: ' . $curlError];
    }

    $data = json_decode($raw, true);
    if ($statusCode < 200 || $statusCode >= 300) {
        $message = $data['errors'][0]['detail'] ?? $data['errors'][0]['title'] ?? 'PayMongo returned an error.';
        return ['success' => false, 'message' => $message, 'response' => $data, 'status_code' => $statusCode];
    }

    return ['success' => true, 'data' => $data, 'status_code' => $statusCode];
}

function createPayMongoCheckoutSession($amount, $description, $successUrl, $cancelUrl, $metadata = []) {
    $amountCentavos = max(0, intval(round(floatval($amount) * 100)));
    if ($amountCentavos <= 0) {
        return ['success' => false, 'message' => 'Payment amount must be greater than zero.'];
    }

    $payload = [
        'data' => [
            'attributes' => [
                'billing' => [
                    'name' => $_SESSION['full_name'] ?? '',
                    'email' => $_SESSION['email'] ?? '',
                ],
                'description' => $description,
                'line_items' => [[
                    'currency' => 'PHP',
                    'amount' => $amountCentavos,
                    'name' => $description,
                    'quantity' => 1,
                ]],
                'payment_method_types' => ['card', 'gcash', 'paymaya'],
                'send_email_receipt' => true,
                'show_description' => true,
                'show_line_items' => true,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => $metadata,
            ],
        ],
    ];

    return payMongoRequest('POST', '/v1/checkout_sessions', $payload);
}

function retrievePayMongoCheckoutSession($checkoutSessionId) {
    $checkoutSessionId = trim((string)$checkoutSessionId);
    if ($checkoutSessionId === '') {
        return ['success' => false, 'message' => 'Missing PayMongo checkout session id.'];
    }
    return payMongoRequest('GET', '/v1/checkout_sessions/' . rawurlencode($checkoutSessionId));
}

function isPayMongoCheckoutPaid($checkoutSession) {
    $attributes = $checkoutSession['data']['attributes'] ?? [];

    foreach (($attributes['payments'] ?? []) as $payment) {
        if (($payment['attributes']['status'] ?? '') === 'paid') {
            return true;
        }
    }

    return in_array($attributes['payment_intent']['attributes']['status'] ?? '', ['succeeded', 'paid'], true);
}

// Get status badge HTML
function getStatusBadge($status) {
    $badges = [
        'draft' => '<span class="badge badge-secondary">Draft</span>',
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'submitted' => '<span class="badge badge-info">Submitted</span>',
        'under_review' => '<span class="badge badge-primary">Under Review</span>',
        'verified' => '<span class="badge badge-success">Verified</span>',
        'approved' => '<span class="badge badge-success">Approved</span>',
        'active' => '<span class="badge badge-success">Active</span>',
        'returned' => '<span class="badge badge-warning">Returned</span>',
        'rejected' => '<span class="badge badge-danger">Rejected</span>',
        'incomplete' => '<span class="badge badge-warning">Incomplete</span>',
        'in_progress' => '<span class="badge badge-primary">In Progress</span>',
        'completed' => '<span class="badge badge-success">Completed</span>',
        'cancelled' => '<span class="badge badge-danger">Cancelled</span>',
        'scheduled' => '<span class="badge badge-info">Scheduled</span>',
        'confirmed' => '<span class="badge badge-primary">Confirmed</span>',
        'conforming' => '<span class="badge badge-success">Conforming</span>',
        'non_conforming' => '<span class="badge badge-danger">Non-Conforming</span>',
        'partial' => '<span class="badge badge-warning">Partial</span>',
        'open' => '<span class="badge badge-danger">Open</span>',
        'resolved' => '<span class="badge badge-success">Resolved</span>',
        'closed' => '<span class="badge badge-secondary">Closed</span>',
        'paid' => '<span class="badge badge-success">Paid</span>',
        'unpaid' => '<span class="badge badge-danger">Unpaid</span>',
        'expired' => '<span class="badge badge-danger">Expired</span>',
        'revoked' => '<span class="badge badge-danger">Revoked</span>',
        'suspended' => '<span class="badge badge-warning">Suspended</span>',
        'awarded' => '<span class="badge badge-success">Awarded</span>',
        'decorated' => '<span class="badge badge-info">Decorated</span>',
        'prepared' => '<span class="badge badge-secondary">Prepared</span>',
        'recommend_approve' => '<span class="badge badge-success">Recommend Approval</span>',
        'recommend_reject' => '<span class="badge badge-danger">Recommend Rejection</span>',
        'need_more_info' => '<span class="badge badge-warning">Need More Info</span>',
        'deferred' => '<span class="badge badge-secondary">Deferred</span>',
        'none' => '<span class="badge badge-secondary">No Role</span>',
    ];
    return $badges[strtolower($status)] ?? '<span class="badge badge-secondary">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}

// Get dashboard URL based on role
function getDashboardUrl($roleId) {
    $dashboards = [
        ROLE_CUSTOMER => BASE_URL . 'dashboard/customer/',
        ROLE_BUSINESS_OWNER => BASE_URL . 'dashboard/business_owner/',
        ROLE_EVALUATOR => BASE_URL . 'dashboard/evaluator/',
        ROLE_AUDITOR_TECHNICAL => BASE_URL . 'dashboard/auditor/',
        ROLE_AUDITOR_SHARIAH => BASE_URL . 'dashboard/auditor/',
        ROLE_IMPARTIAL_COMMITTEE => BASE_URL . 'dashboard/impartial_committee/',
        ROLE_DECISION_COMMITTEE => BASE_URL . 'dashboard/decision_committee/',
        ROLE_PRESIDENT => BASE_URL . 'dashboard/president/',
        ROLE_RECEIVING_OFFICER => BASE_URL . 'dashboard/receiving_officer/',
        ROLE_LABORATORY_ANALYST => BASE_URL . 'dashboard/lab_analyst/',
        ROLE_ADMIN => BASE_URL . 'dashboard/admin/',
    ];
    return $dashboards[$roleId] ?? BASE_URL . 'auth/apply_role.php';
}

// Get sidebar menu items based on role
function getSidebarMenu($roleId) {
    $menus = [
        ROLE_CUSTOMER => [
            ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/customer/'],
            ['icon' => 'fas fa-utensils', 'label' => 'Halal Restaurants', 'url' => BASE_URL . 'dashboard/customer/restaurants.php'],
            ['icon' => 'fas fa-shopping-bag', 'label' => 'My Orders', 'url' => BASE_URL . 'dashboard/customer/orders.php'],
            ['icon' => 'fas fa-star', 'label' => 'My Reviews', 'url' => BASE_URL . 'dashboard/customer/reviews.php'],
        ],
        ROLE_BUSINESS_OWNER => [
            ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/business_owner/'],
            ['icon' => 'fas fa-file-alt', 'label' => 'Letter of Intent', 'url' => BASE_URL . 'dashboard/business_owner/letter_of_intent.php'],
            ['icon' => 'fas fa-clipboard-list', 'label' => 'Applications', 'url' => BASE_URL . 'dashboard/business_owner/applications.php'],
            ['icon' => 'fas fa-file-contract', 'label' => 'Terms of Reference', 'url' => BASE_URL . 'dashboard/business_owner/terms_of_reference.php'],
            ['icon' => 'fas fa-calendar-alt', 'label' => 'Inspection Schedules', 'url' => BASE_URL . 'dashboard/business_owner/inspection_schedules.php'],
            ['icon' => 'fas fa-clipboard-check', 'label' => 'Audit Findings', 'url' => BASE_URL . 'dashboard/business_owner/audit_findings.php'],
            ['icon' => 'fas fa-flask', 'label' => 'Laboratory', 'url' => BASE_URL . 'dashboard/business_owner/laboratory.php'],
            ['icon' => 'fas fa-certificate', 'label' => 'Certificates', 'url' => BASE_URL . 'dashboard/business_owner/certificates.php'],
            ['icon' => 'fas fa-store', 'label' => 'My Restaurant', 'url' => BASE_URL . 'dashboard/business_owner/restaurant.php'],
            ['icon' => 'fas fa-shopping-bag', 'label' => 'Orders', 'url' => BASE_URL . 'dashboard/business_owner/manage_orders.php'],
        ],
        ROLE_EVALUATOR => [
            ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/evaluator/'],
            ['icon' => 'fas fa-file-alt', 'label' => 'Verify LOI', 'url' => BASE_URL . 'dashboard/evaluator/verify_loi.php'],
            ['icon' => 'fas fa-clipboard-check', 'label' => 'Verify Applications', 'url' => BASE_URL . 'dashboard/evaluator/verify_applications.php'],
            ['icon' => 'fas fa-file-contract', 'label' => 'Terms of Reference', 'url' => BASE_URL . 'dashboard/evaluator/terms_of_reference.php'],
            ['icon' => 'fas fa-calendar-alt', 'label' => 'Inspection Schedules', 'url' => BASE_URL . 'dashboard/evaluator/inspection_schedules.php'],
        ],
        ROLE_AUDITOR_TECHNICAL => [
            ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/auditor/'],
            ['icon' => 'fas fa-search', 'label' => 'Inspections', 'url' => BASE_URL . 'dashboard/auditor/inspections.php'],
            ['icon' => 'fas fa-exclamation-triangle', 'label' => 'NCR Reports', 'url' => BASE_URL . 'dashboard/auditor/ncr_reports.php'],
            ['icon' => 'fas fa-clipboard', 'label' => 'Audit Findings', 'url' => BASE_URL . 'dashboard/auditor/audit_findings.php'],
        ],
        ROLE_AUDITOR_SHARIAH => [
            ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/auditor/'],
            ['icon' => 'fas fa-search', 'label' => 'Inspections', 'url' => BASE_URL . 'dashboard/auditor/inspections.php'],
            ['icon' => 'fas fa-exclamation-triangle', 'label' => 'NCR Reports', 'url' => BASE_URL . 'dashboard/auditor/ncr_reports.php'],
            ['icon' => 'fas fa-clipboard', 'label' => 'Audit Findings', 'url' => BASE_URL . 'dashboard/auditor/audit_findings.php'],
        ],
        ROLE_IMPARTIAL_COMMITTEE => [
            ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/impartial_committee/'],
            ['icon' => 'fas fa-gavel', 'label' => 'Review Evidence', 'url' => BASE_URL . 'dashboard/impartial_committee/review_evidence.php'],
            ['icon' => 'fas fa-tasks', 'label' => 'Potential Decisions', 'url' => BASE_URL . 'dashboard/impartial_committee/potential_decisions.php'],
        ],
        ROLE_DECISION_COMMITTEE => [
            ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/decision_committee/'],
            ['icon' => 'fas fa-check-double', 'label' => 'Final Decisions', 'url' => BASE_URL . 'dashboard/decision_committee/final_decisions.php'],
            ['icon' => 'fas fa-history', 'label' => 'Decision History', 'url' => BASE_URL . 'dashboard/decision_committee/history.php'],
        ],
        ROLE_PRESIDENT => [
            ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/president/'],
            ['icon' => 'fas fa-award', 'label' => 'Award Certificates', 'url' => BASE_URL . 'dashboard/president/award_certificates.php'],
            ['icon' => 'fas fa-certificate', 'label' => 'All Certificates', 'url' => BASE_URL . 'dashboard/president/all_certificates.php'],
            ['icon' => 'fas fa-users', 'label' => 'Manage Users', 'url' => BASE_URL . 'dashboard/president/manage_users.php'],
            ['icon' => 'fas fa-user-check', 'label' => 'Role Applications', 'url' => BASE_URL . 'dashboard/president/role_applications.php'],
            ['icon' => 'fas fa-chart-bar', 'label' => 'Reports', 'url' => BASE_URL . 'dashboard/president/reports.php'],
            ['icon' => 'fas fa-shield-alt', 'label' => 'Admin Panel', 'url' => BASE_URL . 'admin/'],
        ],
        ROLE_RECEIVING_OFFICER => [
            ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/receiving_officer/'],
            ['icon' => 'fas fa-inbox', 'label' => 'Request Form', 'url' => BASE_URL . 'dashboard/receiving_officer/receive_samples.php'],
            ['icon' => 'fas fa-search', 'label' => 'Sample Tracking', 'url' => BASE_URL . 'dashboard/receiving_officer/sample_tracking.php'],
        ],
        ROLE_LABORATORY_ANALYST => [
            ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/lab_analyst/'],
            ['icon' => 'fas fa-microscope', 'label' => 'Analyze Samples', 'url' => BASE_URL . 'dashboard/lab_analyst/analyze_samples.php'],
            ['icon' => 'fas fa-file-medical-alt', 'label' => 'Lab Reports', 'url' => BASE_URL . 'dashboard/lab_analyst/lab_reports.php'],
        ],
        ROLE_ADMIN => [
            ['icon' => 'fas fa-shield-alt', 'label' => 'Admin Dashboard', 'url' => BASE_URL . 'dashboard/admin/'],
            ['icon' => 'fas fa-user-shield', 'label' => 'Role Approvals', 'url' => BASE_URL . 'dashboard/admin/approve_roles.php'],
            ['icon' => 'fas fa-key', 'label' => 'Authorization (RBAC)', 'url' => BASE_URL . 'dashboard/admin/authorization.php'],
            ['icon' => 'fas fa-lock', 'label' => 'Secure Data', 'url' => BASE_URL . 'dashboard/admin/secure_data.php'],
            ['icon' => 'fas fa-list-alt', 'label' => 'Logs & Monitor', 'url' => BASE_URL . 'dashboard/admin/logging_monitoring.php'],
            ['icon' => 'fas fa-user-secret', 'label' => 'DLP Features', 'url' => BASE_URL . 'dashboard/admin/dlp_features.php'],
        ],
    ];
    return $menus[$roleId] ?? [];
}

// Create user session from database
function createUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['first_name'] = $user['first_name'] ?? '';
    $_SESSION['last_name'] = $user['last_name'] ?? '';
    $_SESSION['avatar'] = $user['avatar'] ?? '';
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_status'] = $user['role_status'];
    $_SESSION['role_name'] = $user['role_name'] ?? 'Unassigned';
    $_SESSION['is_admin'] = !empty($user['is_admin']) ? true : false;
    $_SESSION['last_activity'] = time();
}

// Get the Google OAuth URL
function getGoogleAuthUrl() {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'online',
        'prompt' => 'consent',
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

// Exchange Google auth code for token
function getGoogleAccessToken($code) {
    $url = 'https://oauth2.googleapis.com/token';
    $data = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code',
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Get Google user info
function getGoogleUserInfo($accessToken) {
    $url = 'https://www.googleapis.com/oauth2/v3/userinfo';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// Get application progress percentage
function getApplicationProgress($conn, $applicationId) {
    $steps = [
        'loi_submitted' => false,
        'loi_verified' => false,
        'application_submitted' => false,
        'application_verified' => false,
        'tor_created' => false,
        'inspection_scheduled' => false,
        'inspection_completed' => false,
        'lab_completed' => false,
        'impartial_reviewed' => false,
        'decision_made' => false,
        'certificate_awarded' => false,
    ];
    
    // Check each step
    $stmt = $conn->prepare("SELECT status FROM hdp_applications WHERE id = ?");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($app = $result->fetch_assoc()) {
        $steps['application_submitted'] = in_array($app['status'], ['submitted', 'under_review', 'verified', 'approved']);
    }
    
    $completed = array_sum(array_map('intval', $steps));
    $total = count($steps);
    
    return round(($completed / $total) * 100);
}

// Time ago function
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
?>
