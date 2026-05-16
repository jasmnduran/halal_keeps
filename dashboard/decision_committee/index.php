<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_DECISION_COMMITTEE]);

$page_title = 'Decision Committee Dashboard';
$page_heading = 'Dashboard';
$is_dashboard = true;
$user_id = $_SESSION['user_id'];

$pending = $conn->query("SELECT COUNT(*) as c FROM potential_decisions pd WHERE pd.decision = 'recommend_approve' AND pd.application_id NOT IN (SELECT application_id FROM final_decisions)")->fetch_assoc()['c'];
$approved = $conn->query("SELECT COUNT(*) as c FROM final_decisions WHERE decision = 'approved'")->fetch_assoc()['c'];
$rejected = $conn->query("SELECT COUNT(*) as c FROM final_decisions WHERE decision = 'rejected'")->fetch_assoc()['c'];
$total = $conn->query("SELECT COUNT(*) as c FROM final_decisions")->fetch_assoc()['c'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="stats-grid">
    <div class="card card-stat">
        <div class="stat-icon gold"><i class="fas fa-clock"></i></div>
        <div class="stat-value"><?= $pending ?></div>
        <div class="stat-label">Awaiting Decision</div>
    </div>
    <div class="card card-stat info">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?= $approved ?></div>
        <div class="stat-label">Approved</div>
    </div>
    <div class="card card-stat">
        <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
        <div class="stat-value"><?= $rejected ?></div>
        <div class="stat-label">Rejected</div>
    </div>
    <div class="card card-stat accent">
        <div class="stat-icon blue"><i class="fas fa-gavel"></i></div>
        <div class="stat-value"><?= $total ?></div>
        <div class="stat-label">Total Decisions</div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="display: flex; gap: 12px;">
        <a href="<?= BASE_URL ?>dashboard/decision_committee/final_decisions.php" class="btn btn-primary"><i class="fas fa-gavel"></i> Make Decisions (<?= $pending ?>)</a>
        <a href="<?= BASE_URL ?>dashboard/decision_committee/history.php" class="btn btn-outline"><i class="fas fa-history"></i> Decision History</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
