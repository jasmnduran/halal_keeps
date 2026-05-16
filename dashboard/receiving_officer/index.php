<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_RECEIVING_OFFICER]);

$page_title = 'Receiving Officer Dashboard';
$page_heading = 'Dashboard';
$is_dashboard = true;
$user_id = $_SESSION['user_id'];

$pending = $conn->query("SELECT COUNT(*) as c FROM laboratory_requests WHERE status = 'submitted'")->fetch_assoc()['c'];
$received = $conn->query("SELECT COUNT(*) as c FROM laboratory_requests WHERE status = 'received'")->fetch_assoc()['c'];
$in_testing = $conn->query("SELECT COUNT(*) as c FROM laboratory_requests WHERE status = 'testing'")->fetch_assoc()['c'];
$completed = $conn->query("SELECT COUNT(*) as c FROM laboratory_requests WHERE status = 'completed'")->fetch_assoc()['c'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="stats-grid">
    <div class="card card-stat">
        <div class="stat-icon gold"><i class="fas fa-inbox"></i></div>
        <div class="stat-value"><?= $pending ?></div>
        <div class="stat-label">Pending Samples</div>
    </div>
    <div class="card card-stat accent">
        <div class="stat-icon blue"><i class="fas fa-box-open"></i></div>
        <div class="stat-value"><?= $received ?></div>
        <div class="stat-label">Received</div>
    </div>
    <div class="card card-stat info">
        <div class="stat-icon green"><i class="fas fa-flask"></i></div>
        <div class="stat-value"><?= $in_testing ?></div>
        <div class="stat-label">In Testing</div>
    </div>
    <div class="card card-stat">
        <div class="stat-icon red"><i class="fas fa-check-double"></i></div>
        <div class="stat-value"><?= $completed ?></div>
        <div class="stat-label">Completed</div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="display: flex; gap: 12px;">
        <a href="<?= BASE_URL ?>dashboard/receiving_officer/receive_samples.php" class="btn btn-primary"><i class="fas fa-inbox"></i> Request Forms (<?= $pending ?>)</a>
        <a href="<?= BASE_URL ?>dashboard/receiving_officer/sample_tracking.php" class="btn btn-outline"><i class="fas fa-search"></i> Track Samples</a>
    </div>
</div>

<?php
$recent = $conn->query("SELECT lr.*, loi.company_name FROM laboratory_requests lr JOIN hdp_applications ha ON lr.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id ORDER BY lr.created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
?>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-history" style="color: var(--primary-600); margin-right: 8px;"></i> Recent Laboratory Requests</h3></div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($recent)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-flask"></i></div><h3>No Requests</h3></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Request #</th><th>Company</th><th>Sample</th><th>Payment</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent as $lr): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($lr['request_number'] ?? 'LR-'.$lr['id']) ?></strong></td>
                            <td><?= htmlspecialchars($lr['company_name']) ?></td>
                            <td style="font-size:0.85rem"><?= htmlspecialchars(substr($lr['sample_description'], 0, 40)) ?></td>
                            <td><?= getStatusBadge($lr['payment_status']) ?></td>
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
