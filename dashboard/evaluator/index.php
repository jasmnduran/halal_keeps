<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_EVALUATOR]);

$page_title = 'Evaluator Dashboard';
$page_heading = 'Dashboard';
$is_dashboard = true;
$user_id = $_SESSION['user_id'];

// Stats
$pending_lois = $conn->query("SELECT COUNT(*) as c FROM letter_of_intent WHERE status = 'submitted'")->fetch_assoc()['c'];
$pending_apps = $conn->query("SELECT COUNT(*) as c FROM hdp_applications WHERE status = 'submitted'")->fetch_assoc()['c'];
$total_schedules = $conn->query("SELECT COUNT(*) as c FROM inspection_schedules")->fetch_assoc()['c'];
$stmt = $conn->prepare("SELECT COUNT(*) as c FROM loi_verification WHERE evaluator_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$verified_count = $stmt->get_result()->fetch_assoc()['c'];

// Recent submitted LOIs
$recent_lois = $conn->query("SELECT loi.*, u.full_name as owner_name FROM letter_of_intent loi JOIN users u ON loi.business_owner_id = u.id WHERE loi.status = 'submitted' ORDER BY loi.submitted_at DESC LIMIT 5");
$lois = $recent_lois->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="stats-grid">
    <div class="card card-stat">
        <div class="stat-icon gold"><i class="fas fa-file-alt"></i></div>
        <div class="stat-value"><?= $pending_lois ?></div>
        <div class="stat-label">Pending LOIs</div>
    </div>
    <div class="card card-stat accent">
        <div class="stat-icon blue"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-value"><?= $pending_apps ?></div>
        <div class="stat-label">Pending Applications</div>
    </div>
    <div class="card card-stat info">
        <div class="stat-icon green"><i class="fas fa-calendar-alt"></i></div>
        <div class="stat-value"><?= $total_schedules ?></div>
        <div class="stat-label">Inspection Schedules</div>
    </div>
    <div class="card card-stat">
        <div class="stat-icon red"><i class="fas fa-check-double"></i></div>
        <div class="stat-value"><?= $verified_count ?></div>
        <div class="stat-label">My Verifications</div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>dashboard/evaluator/verify_loi.php" class="btn btn-primary"><i class="fas fa-file-alt"></i> Verify LOIs (<?= $pending_lois ?>)</a>
        <a href="<?= BASE_URL ?>dashboard/evaluator/verify_applications.php" class="btn btn-outline"><i class="fas fa-clipboard-check"></i> Verify Applications (<?= $pending_apps ?>)</a>
        <a href="<?= BASE_URL ?>dashboard/evaluator/terms_of_reference.php" class="btn btn-outline"><i class="fas fa-file-contract"></i> Terms of Reference</a>
        <a href="<?= BASE_URL ?>dashboard/evaluator/inspection_schedules.php" class="btn btn-outline"><i class="fas fa-calendar-alt"></i> Inspection Schedules</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-clock" style="color: var(--accent-500); margin-right: 8px;"></i> Pending Letters of Intent</h3>
        <a href="<?= BASE_URL ?>dashboard/evaluator/verify_loi.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($lois)): ?>
            <div class="empty-state" style="padding: 40px;">
                <div class="empty-icon"><i class="fas fa-check-circle" style="color: var(--success);"></i></div>
                <h3>All Caught Up!</h3>
                <p>No pending Letters of Intent to review.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Company</th><th>Submitted By</th><th>Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lois as $loi): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($loi['company_name']) ?></strong></td>
                            <td><?= htmlspecialchars($loi['owner_name']) ?></td>
                            <td style="font-size: 0.85rem; color: var(--neutral-500);"><?= formatDate($loi['submitted_at']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>dashboard/evaluator/verify_loi.php?action=review&id=<?= $loi['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> Review</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
