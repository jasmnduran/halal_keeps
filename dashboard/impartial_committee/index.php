<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_IMPARTIAL_COMMITTEE]);

$page_title = 'Impartial Committee Dashboard';
$page_heading = 'Dashboard';
$is_dashboard = true;
$user_id = $_SESSION['user_id'];

$pending_reviews = $conn->query("SELECT COUNT(*) as c FROM inspections i WHERE i.status = 'completed' AND i.application_id NOT IN (SELECT application_id FROM potential_decisions)")->fetch_assoc()['c'];
$my_decisions = $conn->prepare("SELECT COUNT(*) as c FROM potential_decisions WHERE committee_member_id = ?");
$my_decisions->bind_param("i", $user_id);
$my_decisions->execute();
$total_decisions = $my_decisions->get_result()->fetch_assoc()['c'];
$recommend_approve = $conn->query("SELECT COUNT(*) as c FROM potential_decisions WHERE decision = 'recommend_approve'")->fetch_assoc()['c'];
$recommend_reject = $conn->query("SELECT COUNT(*) as c FROM potential_decisions WHERE decision = 'recommend_reject'")->fetch_assoc()['c'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="stats-grid">
    <div class="card card-stat">
        <div class="stat-icon gold"><i class="fas fa-clock"></i></div>
        <div class="stat-value"><?= $pending_reviews ?></div>
        <div class="stat-label">Pending Reviews</div>
    </div>
    <div class="card card-stat accent">
        <div class="stat-icon blue"><i class="fas fa-gavel"></i></div>
        <div class="stat-value"><?= $total_decisions ?></div>
        <div class="stat-label">My Reviews</div>
    </div>
    <div class="card card-stat info">
        <div class="stat-icon green"><i class="fas fa-thumbs-up"></i></div>
        <div class="stat-value"><?= $recommend_approve ?></div>
        <div class="stat-label">Recommended Approval</div>
    </div>
    <div class="card card-stat">
        <div class="stat-icon red"><i class="fas fa-thumbs-down"></i></div>
        <div class="stat-value"><?= $recommend_reject ?></div>
        <div class="stat-label">Recommended Rejection</div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="display: flex; gap: 12px;">
        <a href="<?= BASE_URL ?>dashboard/impartial_committee/review_evidence.php" class="btn btn-primary"><i class="fas fa-search"></i> Review Evidence (<?= $pending_reviews ?>)</a>
        <a href="<?= BASE_URL ?>dashboard/impartial_committee/potential_decisions.php" class="btn btn-outline"><i class="fas fa-tasks"></i> My Decisions</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
