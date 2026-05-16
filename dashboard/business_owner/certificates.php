<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$page_title = 'Certificates';
$page_heading = 'My Certificates';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/business_owner/'], ['label' => 'Certificates']];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT hc.*, ha.id as app_id, loi.company_name FROM halal_certificates hc JOIN hdp_applications ha ON hc.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id WHERE ha.business_owner_id = ? ORDER BY hc.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$certificates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-certificate" style="color: var(--accent-500); margin-right: 8px;"></i> My Halal Certificates</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($certificates)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-certificate"></i></div>
                <h3>No Certificates Yet</h3>
                <p>Your halal certificates will appear here once your application is approved and the certificate is awarded.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Certificate #</th><th>Business</th><th>Issue Date</th><th>Expiry Date</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificates as $cert): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cert['certificate_number']) ?></strong></td>
                            <td><?= htmlspecialchars($cert['company_name']) ?></td>
                            <td><?= formatDate($cert['issue_date']) ?></td>
                            <td><?= formatDate($cert['expiry_date']) ?></td>
                            <td><?= getStatusBadge($cert['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
