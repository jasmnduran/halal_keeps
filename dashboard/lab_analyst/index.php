<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_LAB_ANALYST]);

$page_title = 'Lab Analyst Dashboard';
$page_heading = 'Dashboard';
$is_dashboard = true;
$user_id = $_SESSION['user_id'];

$awaiting = $conn->query("SELECT COUNT(*) as c FROM laboratory_requests WHERE status = 'received'")->fetch_assoc()['c'];
$testing = $conn->query("SELECT COUNT(*) as c FROM laboratory_requests WHERE status = 'testing'")->fetch_assoc()['c'];
$completed = $conn->query("SELECT COUNT(*) as c FROM laboratory_requests WHERE status = 'completed'")->fetch_assoc()['c'];
$reports = $conn->query("SELECT COUNT(*) as c FROM laboratory_reports")->fetch_assoc()['c'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="stats-grid">
    <div class="card card-stat">
        <div class="stat-icon gold"><i class="fas fa-vial"></i></div>
        <div class="stat-value"><?= $awaiting ?></div>
        <div class="stat-label">Awaiting Analysis</div>
    </div>
    <div class="card card-stat accent">
        <div class="stat-icon blue"><i class="fas fa-flask"></i></div>
        <div class="stat-value"><?= $testing ?></div>
        <div class="stat-label">In Testing</div>
    </div>
    <div class="card card-stat info">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?= $completed ?></div>
        <div class="stat-label">Completed</div>
    </div>
    <div class="card card-stat">
        <div class="stat-icon red"><i class="fas fa-file-alt"></i></div>
        <div class="stat-value"><?= $reports ?></div>
        <div class="stat-label">Reports Generated</div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="display: flex; gap: 12px;">
        <a href="<?= BASE_URL ?>dashboard/lab_analyst/analyze_samples.php" class="btn btn-primary"><i class="fas fa-microscope"></i> Analyze Samples (<?= $awaiting ?>)</a>
        <a href="<?= BASE_URL ?>dashboard/lab_analyst/lab_reports.php" class="btn btn-outline"><i class="fas fa-file-alt"></i> Lab Reports</a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
