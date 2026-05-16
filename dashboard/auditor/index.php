<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_AUDITOR_TECHNICAL, ROLE_AUDITOR_SHARIAH]);

$page_title = 'Auditor Dashboard';
$page_heading = 'Dashboard';
$is_dashboard = true;
$user_id = $_SESSION['user_id'];

$pending = $conn->query("SELECT COUNT(*) as c FROM inspections WHERE status = 'pending'")->fetch_assoc()['c'];
$in_progress = $conn->query("SELECT COUNT(*) as c FROM inspections WHERE status = 'in_progress'")->fetch_assoc()['c'];
$completed = $conn->query("SELECT COUNT(*) as c FROM inspections WHERE status = 'completed'")->fetch_assoc()['c'];
$ncr_open = $conn->query("SELECT COUNT(*) as c FROM ncr_reports WHERE status = 'open'")->fetch_assoc()['c'];

$upcoming = $conn->query("SELECT s.*, loi.company_name, loi.company_address FROM inspection_schedules s JOIN hdp_applications ha ON s.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id WHERE s.status IN ('scheduled','confirmed') AND s.schedule_date >= CURDATE() ORDER BY s.schedule_date ASC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="stats-grid">
    <div class="card card-stat">
        <div class="stat-icon gold"><i class="fas fa-clock"></i></div>
        <div class="stat-value"><?= $pending ?></div>
        <div class="stat-label">Pending Inspections</div>
    </div>
    <div class="card card-stat accent">
        <div class="stat-icon blue"><i class="fas fa-spinner"></i></div>
        <div class="stat-value"><?= $in_progress ?></div>
        <div class="stat-label">In Progress</div>
    </div>
    <div class="card card-stat info">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?= $completed ?></div>
        <div class="stat-label">Completed</div>
    </div>
    <div class="card card-stat">
        <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-value"><?= $ncr_open ?></div>
        <div class="stat-label">Open NCRs</div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>dashboard/auditor/inspections.php" class="btn btn-primary"><i class="fas fa-search"></i> View Inspections</a>
        <a href="<?= BASE_URL ?>dashboard/auditor/ncr_reports.php" class="btn btn-outline"><i class="fas fa-exclamation-triangle"></i> NCR Reports</a>
        <a href="<?= BASE_URL ?>dashboard/auditor/audit_findings.php" class="btn btn-outline"><i class="fas fa-clipboard"></i> Audit Findings</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-calendar" style="color: var(--primary-600); margin-right: 8px;"></i> Upcoming Inspections</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($upcoming)): ?>
            <div class="empty-state" style="padding: 40px;"><div class="empty-icon"><i class="fas fa-calendar-check"></i></div><h3>No Upcoming Inspections</h3><p>No inspections scheduled.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Company</th><th>Location</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($upcoming as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['company_name']) ?></strong></td>
                            <td style="font-size:0.85rem"><?= htmlspecialchars($s['location'] ?? $s['company_address'] ?? '-') ?></td>
                            <td><?= formatDate($s['schedule_date']) ?> <?= $s['schedule_time'] ?? '' ?></td>
                            <td><?= getStatusBadge($s['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
