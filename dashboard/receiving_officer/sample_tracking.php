<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_RECEIVING_OFFICER]);

$page_title = 'Sample Tracking';
$page_heading = 'Sample Tracking';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/receiving_officer/'], ['label' => 'Tracking']];

$requests = $conn->query("SELECT lr.*, loi.company_name, u.full_name as received_by_name FROM laboratory_requests lr JOIN hdp_applications ha ON lr.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id LEFT JOIN users u ON lr.received_by = u.id ORDER BY lr.created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-search" style="color: var(--primary-600); margin-right: 8px;"></i> All Samples</h3></div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($requests)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><h3>No Samples</h3></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Company</th><th>Sample</th><th>Received By</th><th>Received At</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($requests as $lr): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($lr['company_name']) ?></strong></td>
                            <td style="font-size:0.85rem"><?= htmlspecialchars(substr($lr['sample_description'], 0, 50)) ?></td>
                            <td><?= htmlspecialchars($lr['received_by_name'] ?? 'Pending') ?></td>
                            <td style="font-size:0.85rem;color:var(--neutral-500)"><?= $lr['received_at'] ? formatDateTime($lr['received_at']) : 'Not yet' ?></td>
                            <td><?= getStatusBadge($lr['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
