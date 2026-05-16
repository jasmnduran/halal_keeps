<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_PRESIDENT]);

$page_title = 'President Dashboard';
$page_heading = 'Dashboard';
$is_dashboard = true;
$user_id = $_SESSION['user_id'];

$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$pending_roles = $conn->query("SELECT COUNT(*) as c FROM role_applications WHERE status = 'pending'")->fetch_assoc()['c'];
$active_certs = $conn->query("SELECT COUNT(*) as c FROM halal_certificates WHERE status IN ('active','awarded')")->fetch_assoc()['c'];
$awaiting_award = $conn->query("SELECT COUNT(*) as c FROM final_decisions fd WHERE fd.decision = 'approved' AND fd.application_id NOT IN (SELECT application_id FROM halal_certificates)")->fetch_assoc()['c'];
$total_apps = $conn->query("SELECT COUNT(*) as c FROM hdp_applications")->fetch_assoc()['c'];
$total_restaurants = $conn->query("SELECT COUNT(*) as c FROM halal_restaurants WHERE is_active = 1")->fetch_assoc()['c'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="stats-grid">
    <div class="card card-stat">
        <div class="stat-icon green"><i class="fas fa-users"></i></div>
        <div class="stat-value"><?= $total_users ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="card card-stat accent">
        <div class="stat-icon gold"><i class="fas fa-user-clock"></i></div>
        <div class="stat-value"><?= $pending_roles ?></div>
        <div class="stat-label">Pending Role Apps</div>
    </div>
    <div class="card card-stat info">
        <div class="stat-icon blue"><i class="fas fa-certificate"></i></div>
        <div class="stat-value"><?= $active_certs ?></div>
        <div class="stat-label">Active Certificates</div>
    </div>
    <div class="card card-stat">
        <div class="stat-icon red"><i class="fas fa-award"></i></div>
        <div class="stat-value"><?= $awaiting_award ?></div>
        <div class="stat-label">Awaiting Award</div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>dashboard/president/role_applications.php" class="btn btn-primary"><i class="fas fa-user-check"></i> Role Applications (<?= $pending_roles ?>)</a>
        <a href="<?= BASE_URL ?>dashboard/president/award_certificates.php" class="btn btn-accent"><i class="fas fa-award"></i> Award Certificates (<?= $awaiting_award ?>)</a>
        <a href="<?= BASE_URL ?>dashboard/president/manage_users.php" class="btn btn-outline"><i class="fas fa-users"></i> Manage Users</a>
        <a href="<?= BASE_URL ?>dashboard/president/all_certificates.php" class="btn btn-outline"><i class="fas fa-certificate"></i> All Certificates</a>
        <a href="<?= BASE_URL ?>dashboard/president/reports.php" class="btn btn-outline"><i class="fas fa-chart-bar"></i> Reports</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-history" style="color: var(--primary-600); margin-right: 8px;"></i> Recent Activity</h3></div>
        <div class="card-body" style="padding: 0;">
            <?php
            $activity = $conn->query("SELECT al.*, u.full_name FROM activity_log al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
            ?>
            <?php if (empty($activity)): ?>
                <div class="empty-state" style="padding:30px"><p>No recent activity.</p></div>
            <?php else: ?>
                <div style="padding: 16px;">
                    <?php foreach ($activity as $a): ?>
                    <div style="display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--neutral-100);">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary-50); color: var(--primary-600); display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0;">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.85rem; font-weight: 600; color: var(--neutral-800);"><?= htmlspecialchars($a['action']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--neutral-500);"><?= htmlspecialchars($a['full_name'] ?? 'System') ?> • <?= timeAgo($a['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- System Overview -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chart-pie" style="color: var(--accent-500); margin-right: 8px;"></i> System Overview</h3></div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div style="background: var(--primary-50); padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary-700); font-family: var(--font-display);"><?= $total_apps ?></div>
                    <div style="font-size: 0.8rem; color: var(--primary-600); margin-top: 4px;">Total Applications</div>
                </div>
                <div style="background: var(--accent-50); padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--accent-700); font-family: var(--font-display);"><?= $total_restaurants ?></div>
                    <div style="font-size: 0.8rem; color: var(--accent-600); margin-top: 4px;">Active Restaurants</div>
                </div>
                <div style="background: #dbeafe; padding: 20px; border-radius: 12px; text-align: center;">
                    <div style="font-size: 1.5rem; font-weight: 800; color: #1d4ed8; font-family: var(--font-display);"><?= $active_certs ?></div>
                    <div style="font-size: 0.8rem; color: #2563eb; margin-top: 4px;">Active Certificates</div>
                </div>
                <div style="background: #ede9fe; padding: 20px; border-radius: 12px; text-align: center;">
                    <?php $loi_count = $conn->query("SELECT COUNT(*) as c FROM letter_of_intent")->fetch_assoc()['c']; ?>
                    <div style="font-size: 1.5rem; font-weight: 800; color: #6d28d9; font-family: var(--font-display);"><?= $loi_count ?></div>
                    <div style="font-size: 0.8rem; color: #7c3aed; margin-top: 4px;">Total LOIs</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
