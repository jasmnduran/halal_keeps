<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_PRESIDENT]);

$page_title = 'Reports';
$page_heading = 'System Reports';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/president/'], ['label' => 'Reports']];

$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_apps = $conn->query("SELECT COUNT(*) as c FROM hdp_applications")->fetch_assoc()['c'];
$apps_approved = $conn->query("SELECT COUNT(*) as c FROM hdp_applications WHERE status = 'approved'")->fetch_assoc()['c'];
$apps_pending = $conn->query("SELECT COUNT(*) as c FROM hdp_applications WHERE status IN ('submitted','under_review','verified')")->fetch_assoc()['c'];
$certs_active = $conn->query("SELECT COUNT(*) as c FROM halal_certificates WHERE status IN ('active','awarded')")->fetch_assoc()['c'];
$inspections_done = $conn->query("SELECT COUNT(*) as c FROM inspections WHERE status = 'completed'")->fetch_assoc()['c'];

// Users by role
$role_dist = $conn->query("SELECT r.role_name, COUNT(u.id) as count FROM roles r LEFT JOIN users u ON r.id = u.role_id AND u.role_status = 'approved' GROUP BY r.id ORDER BY r.id")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="stats-grid" style="margin-bottom: 32px;">
    <div class="card card-stat"><div class="stat-icon green"><i class="fas fa-users"></i></div><div class="stat-value"><?= $total_users ?></div><div class="stat-label">Total Users</div></div>
    <div class="card card-stat accent"><div class="stat-icon gold"><i class="fas fa-clipboard-list"></i></div><div class="stat-value"><?= $total_apps ?></div><div class="stat-label">Total Applications</div></div>
    <div class="card card-stat info"><div class="stat-icon blue"><i class="fas fa-check-circle"></i></div><div class="stat-value"><?= $apps_approved ?></div><div class="stat-label">Approved Apps</div></div>
    <div class="card card-stat"><div class="stat-icon red"><i class="fas fa-search"></i></div><div class="stat-value"><?= $inspections_done ?></div><div class="stat-label">Inspections Done</div></div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card">
        <div class="card-header"><h3>Users by Role</h3></div>
        <div class="card-body">
            <?php foreach ($role_dist as $rd): ?>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                <div style="flex:1;font-size:0.9rem;font-weight:500"><?= htmlspecialchars($rd['role_name']) ?></div>
                <div style="width:200px;height:8px;background:var(--neutral-100);border-radius:8px;overflow:hidden">
                    <div style="height:100%;background:var(--gradient-primary);border-radius:8px;width:<?= $total_users > 0 ? min(($rd['count']/$total_users)*100, 100) : 0 ?>%"></div>
                </div>
                <div style="font-weight:700;font-size:0.9rem;color:var(--primary-700);min-width:30px;text-align:right"><?= $rd['count'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header"><h3>Application Pipeline</h3></div>
        <div class="card-body">
            <?php
            $pipeline = [
                ['label' => 'LOIs Submitted', 'count' => $conn->query("SELECT COUNT(*) as c FROM letter_of_intent")->fetch_assoc()['c'], 'color' => '#3b82f6'],
                ['label' => 'Applications', 'count' => $total_apps, 'color' => '#8b5cf6'],
                ['label' => 'Inspections', 'count' => $inspections_done, 'color' => '#f59e0b'],
                ['label' => 'Approved', 'count' => $apps_approved, 'color' => '#10b981'],
                ['label' => 'Certificates', 'count' => $certs_active, 'color' => '#059669'],
            ];
            foreach ($pipeline as $p):
            ?>
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                <div style="width:12px;height:12px;border-radius:50%;background:<?= $p['color'] ?>;flex-shrink:0"></div>
                <div style="flex:1;font-size:0.9rem"><?= $p['label'] ?></div>
                <div style="font-weight:800;font-size:1.1rem;color:var(--neutral-800);font-family:var(--font-display)"><?= $p['count'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
