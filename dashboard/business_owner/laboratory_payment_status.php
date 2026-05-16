<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$page_title = 'Laboratory Payment Status';
$page_heading = 'Laboratory Payment Status';
$is_dashboard = true;
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/business_owner/'],
    ['label' => 'Laboratory', 'url' => BASE_URL . 'dashboard/business_owner/laboratory.php'],
    ['label' => 'Payment Status']
];

$user_id = $_SESSION['user_id'];
$request_id = intval($_GET['id'] ?? 0);
$status_param = $_GET['status'] ?? '';

// Get laboratory request
$stmt = $conn->prepare("
    SELECT lr.*, loi.company_name
    FROM laboratory_requests lr
    JOIN hdp_applications ha ON lr.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    WHERE lr.id = ? AND lr.business_owner_id = ?
");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

if (!$request) {
    $_SESSION['error'] = 'Laboratory request not found.';
    header('Location: ' . BASE_URL . 'dashboard/business_owner/laboratory.php');
    exit;
}

// Get payment record
$payment = $conn->query("SELECT * FROM payments WHERE reference_type = 'laboratory' AND reference_id = $request_id ORDER BY created_at DESC LIMIT 1")->fetch_assoc();

$payment_verified = false;
$message = '';
$message_type = 'info';

if ($payment && $payment['status'] === 'pending' && !empty($payment['transaction_reference'])) {
    // Check PayMongo status
    $checkoutResult = retrievePayMongoCheckoutSession($payment['transaction_reference']);
    
    if ($checkoutResult['success'] && isPayMongoCheckoutPaid($checkoutResult['data'])) {
        // Payment successful - update records
        $paid_at = date('Y-m-d H:i:s');
        
        // Update payment record
        $conn->query("UPDATE payments SET status = 'verified', paid_at = '$paid_at' WHERE id = {$payment['id']}");
        
        // Update laboratory request
        $conn->query("UPDATE laboratory_requests SET payment_status = 'paid', status = 'received' WHERE id = $request_id");
        
        // Notify lab analysts
        $analysts = $conn->query("SELECT id FROM users WHERE role_id = " . ROLE_LABORATORY_ANALYST . " AND role_status = 'approved'");
        while ($analyst = $analysts->fetch_assoc()) {
            createNotification(
                $conn,
                $analyst['id'],
                'Laboratory Payment Received',
                'Payment received for laboratory request from ' . $request['company_name'] . '. Ready for analysis.',
                'action_required',
                BASE_URL . 'dashboard/lab_analyst/analyze_samples.php'
            );
        }
        
        logActivity($conn, $user_id, 'Lab Payment Completed', 'Payment verified for lab request #' . $request_id, 'payment');
        
        $payment_verified = true;
        $message = 'Payment successful! Your laboratory request is now ready for analysis.';
        $message_type = 'success';
        
        // Refresh data
        $payment = $conn->query("SELECT * FROM payments WHERE id = {$payment['id']}")->fetch_assoc();
        $request = $conn->query("SELECT lr.*, loi.company_name FROM laboratory_requests lr JOIN hdp_applications ha ON lr.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id WHERE lr.id = $request_id")->fetch_assoc();
    } elseif ($status_param === 'cancelled') {
        $message = 'Payment was cancelled. You can try again when ready.';
        $message_type = 'warning';
    } else {
        $message = 'Payment is being processed. Please wait...';
        $message_type = 'info';
    }
} elseif ($payment && $payment['status'] === 'verified') {
    $payment_verified = true;
    $message = 'Payment has been verified. Your laboratory request is being processed.';
    $message_type = 'success';
} elseif ($request['status'] === 'pending_payment') {
    $message = 'Payment is required to proceed with laboratory testing.';
    $message_type = 'warning';
}

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?>">
        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
        <span><?= $message ?></span>
        <button class="close-alert"><i class="fas fa-times"></i></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-receipt" style="color:var(--primary-600);margin-right:8px;"></i> Payment Details</h3>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase;display:block;margin-bottom:4px;">Company</label>
                <p style="font-size:1rem;font-weight:600;"><?= htmlspecialchars($request['company_name']) ?></p>
            </div>
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase;display:block;margin-bottom:4px;">Amount</label>
                <p style="font-size:1.2rem;font-weight:700;color:var(--primary-600);"><?= formatCurrency($request['payment_testing_fee']) ?></p>
            </div>
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase;display:block;margin-bottom:4px;">Payment Status</label>
                <p><?= getStatusBadge($request['payment_status']) ?></p>
            </div>
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase;display:block;margin-bottom:4px;">Request Status</label>
                <p><?= getStatusBadge($request['status']) ?></p>
            </div>
        </div>
        
        <?php if ($payment): ?>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-bottom:20px;">
            <h4 style="font-weight:700;margin-bottom:12px;">Transaction Information</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label style="font-size:0.8rem;color:var(--neutral-500);">Payment Method</label>
                    <p style="font-weight:600;"><?= ucfirst($payment['payment_method']) ?></p>
                </div>
                <div>
                    <label style="font-size:0.8rem;color:var(--neutral-500);">Transaction Reference</label>
                    <p style="font-weight:600;font-family:monospace;font-size:0.85rem;"><?= htmlspecialchars($payment['transaction_reference']) ?></p>
                </div>
                <?php if ($payment['paid_at']): ?>
                <div>
                    <label style="font-size:0.8rem;color:var(--neutral-500);">Paid At</label>
                    <p style="font-weight:600;"><?= formatDateTime($payment['paid_at']) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!$payment_verified && $request['status'] === 'pending_payment'): ?>
        <div style="display:flex;justify-content:flex-end;gap:12px;">
            <a href="<?= BASE_URL ?>dashboard/business_owner/laboratory.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Laboratory
            </a>
            <a href="<?= BASE_URL ?>dashboard/business_owner/pay_laboratory.php?id=<?= $request_id ?>" class="btn btn-primary">
                <i class="fas fa-credit-card"></i> Proceed to Payment
            </a>
        </div>
        <?php else: ?>
        <div style="display:flex;justify-content:flex-end;">
            <a href="<?= BASE_URL ?>dashboard/business_owner/laboratory.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Laboratory
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($payment_verified): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-check-circle" style="color:var(--success);margin-right:8px;"></i> Next Steps</h3>
    </div>
    <div class="card-body">
        <div style="background:var(--success-50);border:1px solid var(--success-200);border-radius:8px;padding:16px;">
            <p style="margin:0 0 12px 0;font-weight:600;">Your payment has been confirmed!</p>
            <ul style="margin:0;padding-left:20px;line-height:1.8;">
                <li>Your laboratory request has been forwarded to the laboratory analyst</li>
                <li>Testing will begin shortly</li>
                <li>You will be notified once the results are available</li>
                <li>Results will be delivered via: <strong><?= ucfirst($request['delivery_method'] ?? 'pickup') ?></strong></li>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
