<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$user_id = $_SESSION['user_id'];
$request_id = intval($_GET['id'] ?? 0);

// Get laboratory request
$stmt = $conn->prepare("
    SELECT lr.*, loi.company_name, ha.id as app_id
    FROM laboratory_requests lr
    JOIN hdp_applications ha ON lr.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    WHERE lr.id = ? AND lr.business_owner_id = ? AND lr.status = 'pending_payment'
");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

if (!$request) {
    $_SESSION['error'] = 'Laboratory request not found or not available for payment.';
    header('Location: ' . BASE_URL . 'dashboard/business_owner/laboratory.php');
    exit;
}

// Check if payment already exists
$existing_payment = $conn->query("SELECT * FROM payments WHERE reference_type = 'laboratory' AND reference_id = $request_id AND status != 'rejected'")->fetch_assoc();

if ($existing_payment) {
    // Payment already exists, redirect to check status
    header('Location: ' . BASE_URL . 'dashboard/business_owner/laboratory_payment_status.php?id=' . $request_id);
    exit;
}

// Create PayMongo checkout session
$amount = $request['payment_testing_fee'];
$description = 'Laboratory Testing Fee - ' . $request['company_name'];
$baseHttp = 'http://' . $_SERVER['HTTP_HOST'];
$successUrl = $baseHttp . BASE_URL . 'dashboard/business_owner/laboratory_payment_status.php?id=' . $request_id . '&status=success';
$cancelUrl  = $baseHttp . BASE_URL . 'dashboard/business_owner/laboratory_payment_status.php?id=' . $request_id . '&status=cancelled';

$metadata = [
    'user_id'      => (string) $user_id,
    'request_id'   => (string) $request_id,
    'company_name' => $request['company_name'],
    'type'         => 'laboratory_testing'
];

$paymongoResult = createPayMongoCheckoutSession($amount, $description, $successUrl, $cancelUrl, $metadata);

if (!$paymongoResult['success']) {
    $_SESSION['error'] = 'Failed to create payment session: ' . $paymongoResult['message'];
    header('Location: ' . BASE_URL . 'dashboard/business_owner/laboratory.php');
    exit;
}

// Save payment record
$checkout_data        = $paymongoResult['data']['data'] ?? [];
$checkout_session_id  = $checkout_data['id'] ?? '';
$checkout_url         = $checkout_data['attributes']['checkout_url'] ?? '';

if (empty($checkout_session_id) || empty($checkout_url)) {
    $_SESSION['error'] = 'PayMongo did not return a valid checkout URL. Please try again.';
    header('Location: ' . BASE_URL . 'dashboard/business_owner/laboratory.php');
    exit;
}

$raw_response = json_encode($paymongoResult['data']);

$stmt = $conn->prepare("
    INSERT INTO payments 
        (user_id, reference_type, reference_id, amount, payment_method, transaction_reference, 
         paymongo_checkout_url, paymongo_raw_response, status, created_at)
    VALUES (?, 'laboratory', ?, ?, 'paymongo', ?, ?, ?, 'pending', NOW())
");
$stmt->bind_param("iidsss", $user_id, $request_id, $amount, $checkout_session_id, $checkout_url, $raw_response);
$stmt->execute();

logActivity($conn, $user_id, 'Lab Payment Initiated', 'Payment session created for lab request #' . $request_id, 'payment');

// Redirect to PayMongo checkout
header('Location: ' . $checkout_url);
exit;
