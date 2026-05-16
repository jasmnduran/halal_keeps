<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_DECISION_COMMITTEE]);

$page_title = 'Decision History';
$page_heading = 'Decision History';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/decision_committee/'], ['label' => 'History']];

$decisions = $conn->query("SELECT fd.*, loi.company_name, u.full_name as decided_by FROM final_decisions fd JOIN hdp_applications ha ON fd.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id JOIN users u ON fd.committee_member_id = u.id ORDER BY fd.decided_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-history" style="color: var(--primary-600); margin-right: 8px;"></i> All Final Decisions</h3></div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($decisions)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-history"></i></div><h3>No Decisions Yet</h3></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Company</th><th>Decision</th><th>Decided By</th><th>Notes</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($decisions as $d): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($d['company_name']) ?></strong></td>
                            <td><?= getStatusBadge($d['decision']) ?></td>
                            <td><?= htmlspecialchars($d['decided_by']) ?></td>
                            <td style="max-width:300px;font-size:0.85rem;color:var(--neutral-600)"><?= htmlspecialchars(substr($d['decision_notes'] ?? '-', 0, 80)) ?></td>
                            <td style="font-size:0.85rem;color:var(--neutral-500)"><?= $d['decided_at'] ? formatDateTime($d['decided_at']) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
