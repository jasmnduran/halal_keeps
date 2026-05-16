<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_LAB_ANALYST]);

$page_title = 'Lab Reports';
$page_heading = 'Laboratory Reports';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/lab_analyst/'], ['label' => 'Reports']];

$reports = $conn->query("SELECT lr.*, loi.company_name, u.full_name as analyst_name FROM laboratory_reports lr JOIN hdp_applications ha ON lr.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id JOIN users u ON lr.analyst_id = u.id ORDER BY lr.created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-file-medical-alt" style="color: var(--primary-600); margin-right: 8px;"></i> All Lab Reports</h3></div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($reports)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-file-alt"></i></div><h3>No Reports</h3></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Report #</th><th>Company</th><th>Analyst</th><th>Halal Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($reports as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['report_number']) ?></strong></td>
                            <td><?= htmlspecialchars($r['company_name']) ?></td>
                            <td><?= htmlspecialchars($r['analyst_name']) ?></td>
                            <td>
                                <?php if ($r['halal_status'] === 'halal'): ?>
                                    <span class="badge badge-success"><i class="fas fa-check-circle"></i> Halal</span>
                                <?php elseif ($r['halal_status'] === 'haram'): ?>
                                    <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Haram</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><i class="fas fa-question-circle"></i> Mushbooh</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.85rem;color:var(--neutral-500)"><?= formatDateTime($r['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
