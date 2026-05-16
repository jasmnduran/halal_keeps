<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_PRESIDENT]);

$page_title = 'All Certificates';
$page_heading = 'All Halal Certificates';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/president/'], ['label' => 'Certificates']];

$certs = $conn->query("SELECT hc.*, u.full_name as awarded_by_name FROM halal_certificates hc LEFT JOIN users u ON hc.awarded_by = u.id ORDER BY hc.created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-certificate" style="color: var(--accent-500); margin-right: 8px;"></i> Halal Certificates</h3></div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($certs)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-certificate"></i></div><h3>No Certificates Issued</h3></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Certificate #</th><th>Business</th><th>Issue Date</th><th>Expiry</th><th>Awarded By</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($certs as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['certificate_number']) ?></strong></td>
                            <td><?= htmlspecialchars($c['business_name']) ?><br><small style="color:var(--neutral-500)"><?= htmlspecialchars(substr($c['business_address'] ?? '', 0, 40)) ?></small></td>
                            <td><?= formatDate($c['issue_date']) ?></td>
                            <td><?= formatDate($c['expiry_date']) ?></td>
                            <td><?= htmlspecialchars($c['awarded_by_name'] ?? 'N/A') ?></td>
                            <td><?= getStatusBadge($c['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
