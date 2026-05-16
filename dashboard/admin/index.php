<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_ADMIN]);

$page_title = 'Admin Dashboard';
$page_heading = 'Security & Administration';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Admin Dashboard']];

// Get Stats
$pending_roles = $conn->query("SELECT COUNT(*) as c FROM users WHERE role_status = 'pending'")->fetch_assoc()['c'];
$failed_logins = $conn->query("SELECT COUNT(*) as c FROM login_attempts WHERE success = 0 AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['c'];
$active_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE role_status = 'approved'")->fetch_assoc()['c'];
$lockouts = $conn->query("SELECT COUNT(*) as c FROM users WHERE account_locked = 1")->fetch_assoc()['c'];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="stats-grid" style="margin-bottom: 24px;">
    <div class="card card-stat <?= $pending_roles > 0 ? 'accent' : '' ?>">
        <div class="stat-icon <?= $pending_roles > 0 ? 'gold' : 'blue' ?>"><i class="fas fa-user-clock"></i></div>
        <div class="stat-value"><?= $pending_roles ?></div>
        <div class="stat-label">Pending Role Approvals</div>
    </div>
    <div class="card card-stat <?= $failed_logins > 5 ? 'accent' : '' ?>">
        <div class="stat-icon <?= $failed_logins > 5 ? 'red' : 'green' ?>"><i class="fas fa-shield-alt"></i></div>
        <div class="stat-value"><?= $failed_logins ?></div>
        <div class="stat-label">Failed Logins (24h)</div>
    </div>
    <div class="card card-stat info">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="stat-value"><?= $active_users ?></div>
        <div class="stat-label">Active Users</div>
    </div>
    <div class="card card-stat <?= $lockouts > 0 ? 'accent' : '' ?>">
        <div class="stat-icon <?= $lockouts > 0 ? 'red' : 'green' ?>"><i class="fas fa-lock"></i></div>
        <div class="stat-value"><?= $lockouts ?></div>
        <div class="stat-label">Locked Accounts</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
    
    <!-- Role Approvals Module -->
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid var(--neutral-200); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 1.1rem; color: var(--neutral-800);"><i class="fas fa-user-shield" style="color: var(--primary-600); margin-right: 8px;"></i> Role Management</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <p style="font-size: 0.9rem; color: var(--neutral-600); margin-bottom: 16px;">Approve or reject users applying for system roles.</p>
            <a href="<?= BASE_URL ?>dashboard/admin/approve_roles.php" class="btn btn-primary" style="width: 100%;">View Pending Approvals <?= $pending_roles > 0 ? '<span class="badge badge-light" style="margin-left:8px;">'.$pending_roles.'</span>' : '' ?></a>
        </div>
    </div>

    <!-- Authorization Module -->
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid var(--neutral-200); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 1.1rem; color: var(--neutral-800);"><i class="fas fa-key" style="color: var(--primary-600); margin-right: 8px;"></i> Authorization (RBAC)</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <p style="font-size: 0.9rem; color: var(--neutral-600); margin-bottom: 16px;">View Role-Based Access Control matrix and API endpoint permission checks.</p>
            <a href="<?= BASE_URL ?>dashboard/admin/authorization.php" class="btn btn-outline" style="width: 100%;">View RBAC Matrix</a>
        </div>
    </div>

    <!-- Secure Data Storage -->
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid var(--neutral-200); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 1.1rem; color: var(--neutral-800);"><i class="fas fa-lock" style="color: var(--primary-600); margin-right: 8px;"></i> Secure Data Storage</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <p style="font-size: 0.9rem; color: var(--neutral-600); margin-bottom: 16px;">Manage encrypted local storage policies and password hashing standards.</p>
            <a href="<?= BASE_URL ?>dashboard/admin/secure_data.php" class="btn btn-outline" style="width: 100%;">Manage Encryption</a>
        </div>
    </div>

    <!-- Logging & Monitoring -->
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid var(--neutral-200); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 1.1rem; color: var(--neutral-800);"><i class="fas fa-list-alt" style="color: var(--primary-600); margin-right: 8px;"></i> Logging & Monitoring</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <p style="font-size: 0.9rem; color: var(--neutral-600); margin-bottom: 16px;">Audit system events, admin activity logs, and failed login attempts.</p>
            <a href="<?= BASE_URL ?>dashboard/admin/logging_monitoring.php" class="btn btn-outline" style="width: 100%;">View System Logs</a>
        </div>
    </div>

    <!-- DLP Features -->
    <div class="card">
        <div class="card-header" style="border-bottom: 1px solid var(--neutral-200); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 1.1rem; color: var(--neutral-800);"><i class="fas fa-user-secret" style="color: var(--primary-600); margin-right: 8px;"></i> DLP Features</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <p style="font-size: 0.9rem; color: var(--neutral-600); margin-bottom: 16px;">Configure session timeouts, data classifications, and prevent data loss.</p>
            <a href="<?= BASE_URL ?>dashboard/admin/dlp_features.php" class="btn btn-outline" style="width: 100%;">Configure DLP</a>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
