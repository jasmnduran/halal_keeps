<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_PRESIDENT]);

$page_title = 'Award Certificates';
$page_heading = 'Award Halal Certificates';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/president/'], ['label' => 'Award Certificates']];
$user_id = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_id = intval($_POST['application_id']);
    $fd_id = intval($_POST['final_decision_id']);
    $cert_number = generateReference('HALAL');
    
    // Get company info
    $company = $conn->query("SELECT loi.company_name, loi.company_address FROM hdp_applications ha JOIN letter_of_intent loi ON ha.loi_id = loi.id WHERE ha.id = $app_id")->fetch_assoc();
    
    $issue_date = date('Y-m-d');
    $expiry_date = date('Y-m-d', strtotime('+2 years'));
    $business_name = $company['company_name'] ?? '';
    $business_address = $company['company_address'] ?? '';
    
    $stmt = $conn->prepare("INSERT INTO halal_certificates (application_id, final_decision_id, certificate_number, business_name, business_address, issue_date, expiry_date, awarded_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'awarded')");
    $stmt->bind_param("iisssssi", $app_id, $fd_id, $cert_number, $business_name, $business_address, $issue_date, $expiry_date, $user_id);
    
    if ($stmt->execute()) {
        // Update application status
        $conn->query("UPDATE hdp_applications SET status = 'approved' WHERE id = $app_id");
        
        // Notify business owner
        $owner = $conn->query("SELECT business_owner_id FROM hdp_applications WHERE id = $app_id")->fetch_assoc();
        if ($owner) {
            createNotification($conn, $owner['business_owner_id'], 
                '🎉 Halal Certificate Awarded!',
                'Your Halal Certificate #' . $cert_number . ' has been awarded! Valid until ' . formatDate($expiry_date) . '.',
                'success', BASE_URL . 'dashboard/business_owner/certificates.php');
            
            // Update restaurant certificate
            $conn->query("UPDATE halal_restaurants SET certificate_id = " . $conn->insert_id . " WHERE business_owner_id = " . $owner['business_owner_id']);
        }
        
        logActivity($conn, $user_id, 'Certificate Awarded', 'Certificate #' . $cert_number . ' for ' . $business_name, 'certification');
        $success = 'Halal Certificate #' . $cert_number . ' has been awarded to ' . $business_name . '!';
    }
}

// Get approved applications awaiting certificate
$awaiting = $conn->query("
    SELECT fd.*, ha.id as app_id, loi.company_name, loi.company_address, u.full_name as decided_by
    FROM final_decisions fd
    JOIN hdp_applications ha ON fd.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    JOIN users u ON fd.committee_member_id = u.id
    WHERE fd.decision = 'approved'
    AND fd.application_id NOT IN (SELECT application_id FROM halal_certificates)
    ORDER BY fd.decided_at ASC
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success" style="background: linear-gradient(135deg, #dcfce7, #bbf7d0); border: 2px solid #86efac;">
        <i class="fas fa-award" style="font-size: 1.2rem;"></i>
        <span style="font-weight: 600;"><?= $success ?></span>
        <button class="close-alert"><i class="fas fa-times"></i></button>
    </div>
<?php endif; ?>

<?php if (empty($awaiting)): ?>
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <div class="empty-icon" style="background: var(--primary-50);"><i class="fas fa-award" style="color: var(--primary-500);"></i></div>
            <h3>No Certificates to Award</h3>
            <p>All approved applications have been awarded their certificates.</p>
        </div>
    </div>
</div>
<?php else: ?>
<?php foreach ($awaiting as $fd): ?>
<div class="card" style="margin-bottom: 24px; border: 2px solid var(--primary-100);">
    <div class="card-header" style="background: linear-gradient(135deg, var(--primary-50), var(--accent-50));">
        <h3><i class="fas fa-award" style="color: var(--accent-500); margin-right: 8px;"></i> <?= htmlspecialchars($fd['company_name']) ?></h3>
        <span class="badge badge-success">Approved for Certification</span>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 24px;">
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase">Company</label>
                <p style="font-weight:600"><?= htmlspecialchars($fd['company_name']) ?></p>
            </div>
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase">Address</label>
                <p><?= htmlspecialchars($fd['company_address']) ?></p>
            </div>
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase">Approved By</label>
                <p><?= htmlspecialchars($fd['decided_by']) ?> on <?= formatDate($fd['decided_at']) ?></p>
            </div>
        </div>
        
        <div style="background: linear-gradient(135deg, var(--primary-50), var(--accent-50)); padding: 24px; border-radius: 12px; text-align: center;">
            <i class="fas fa-certificate" style="font-size: 3rem; color: var(--accent-500); margin-bottom: 12px;"></i>
            <h4 style="font-family: var(--font-display); font-weight: 700; margin-bottom: 8px;">Ready to Award Halal Certificate</h4>
            <p style="color: var(--neutral-600); font-size: 0.9rem; margin-bottom: 20px;">
                Certificate will be valid for 2 years from date of issuance.
            </p>
            <form method="POST">
                <input type="hidden" name="application_id" value="<?= $fd['app_id'] ?>">
                <input type="hidden" name="final_decision_id" value="<?= $fd['id'] ?>">
                <button type="submit" class="btn btn-accent btn-lg" onclick="return confirm('Award Halal Certificate to <?= htmlspecialchars($fd['company_name']) ?>?')">
                    <i class="fas fa-award"></i> Award Halal Certificate & Logo
                </button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
