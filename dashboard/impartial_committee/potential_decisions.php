<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_IMPARTIAL_COMMITTEE]);

$page_title = 'Potential Decisions';
$page_heading = 'My Potential Decisions';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/impartial_committee/'], ['label' => 'Decisions']];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT pd.*, loi.company_name FROM potential_decisions pd JOIN hdp_applications ha ON pd.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id WHERE pd.committee_member_id = ? ORDER BY pd.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$decisions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-tasks" style="color: var(--primary-600); margin-right: 8px;"></i> My Review History</h3></div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($decisions)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-tasks"></i></div><h3>No Reviews Yet</h3></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Company</th><th>Decision</th><th>Evidence Summary</th><th>Reviewed</th></tr></thead>
                    <tbody>
                        <?php foreach ($decisions as $d): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($d['company_name']) ?></strong></td>
                            <td><?= getStatusBadge($d['decision']) ?></td>
                            <td style="max-width: 300px; font-size: 0.85rem; color: var(--neutral-600);"><?= htmlspecialchars(substr($d['evidence_summary'], 0, 100)) ?>...</td>
                            <td style="font-size: 0.85rem; color: var(--neutral-500);"><?= $d['reviewed_at'] ? formatDateTime($d['reviewed_at']) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
