<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$page_title = 'Business Owner Dashboard';
$page_heading = 'Dashboard';
$is_dashboard = true;
$user_id = $_SESSION['user_id'];

// Get stats
$loi_count = $conn->prepare("SELECT COUNT(*) as c FROM letter_of_intent WHERE business_owner_id = ?");
$loi_count->bind_param("i", $user_id);
$loi_count->execute();
$total_lois = $loi_count->get_result()->fetch_assoc()['c'];

$app_count = $conn->prepare("SELECT COUNT(*) as c FROM hdp_applications WHERE business_owner_id = ?");
$app_count->bind_param("i", $user_id);
$app_count->execute();
$total_apps = $app_count->get_result()->fetch_assoc()['c'];

$cert_count = $conn->prepare("SELECT COUNT(*) as c FROM halal_certificates hc JOIN hdp_applications ha ON hc.application_id = ha.id WHERE ha.business_owner_id = ? AND hc.status = 'awarded'");
$cert_count->bind_param("i", $user_id);
$cert_count->execute();
$total_certs = $cert_count->get_result()->fetch_assoc()['c'];

$order_count = $conn->prepare("SELECT COUNT(*) as c FROM orders o JOIN halal_restaurants hr ON o.restaurant_id = hr.id WHERE hr.business_owner_id = ?");
$order_count->bind_param("i", $user_id);
$order_count->execute();
$total_orders = $order_count->get_result()->fetch_assoc()['c'];

// Get recent LOIs
$recent_lois = $conn->prepare("SELECT * FROM letter_of_intent WHERE business_owner_id = ? ORDER BY created_at DESC LIMIT 5");
$recent_lois->bind_param("i", $user_id);
$recent_lois->execute();
$lois = $recent_lois->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent applications
$recent_apps = $conn->prepare("SELECT ha.*, loi.company_name FROM hdp_applications ha JOIN letter_of_intent loi ON ha.loi_id = loi.id WHERE ha.business_owner_id = ? ORDER BY ha.created_at DESC LIMIT 5");
$recent_apps->bind_param("i", $user_id);
$recent_apps->execute();
$apps = $recent_apps->get_result()->fetch_all(MYSQLI_ASSOC);

// Get notifications
$notifications = getNotifications($conn, $user_id, 5);

// Check if owner can create a new LOI
$can_create_loi = ($total_lois === 0);
if (!$can_create_loi) {
    $cert_q = $conn->prepare("SELECT hc.id FROM halal_certificates hc JOIN hdp_applications ha ON hc.application_id = ha.id WHERE ha.business_owner_id = ? AND hc.status IN ('active','awarded') LIMIT 1");
    $cert_q->bind_param("i", $user_id);
    $cert_q->execute();
    $can_create_loi = (bool) $cert_q->get_result()->fetch_assoc();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="card card-stat">
        <div class="stat-icon green"><i class="fas fa-file-alt"></i></div>
        <div class="stat-value"><?= $total_lois ?></div>
        <div class="stat-label">Letters of Intent</div>
    </div>
    <div class="card card-stat accent">
        <div class="stat-icon gold"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-value"><?= $total_apps ?></div>
        <div class="stat-label">Applications</div>
    </div>
    <div class="card card-stat info">
        <div class="stat-icon blue"><i class="fas fa-certificate"></i></div>
        <div class="stat-value"><?= $total_certs ?></div>
        <div class="stat-label">Certificates</div>
    </div>
    <div class="card card-stat">
        <div class="stat-icon red"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-value"><?= $total_orders ?></div>
        <div class="stat-label">Orders Received</div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="display: flex; gap: 12px; flex-wrap: wrap;">
        <?php if ($can_create_loi): ?>
        <a href="<?= BASE_URL ?>dashboard/business_owner/letter_of_intent.php?action=select_body" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Letter of Intent
        </a>
        <?php else: ?>
        <a href="<?= BASE_URL ?>dashboard/business_owner/letter_of_intent.php" class="btn btn-outline">
            <i class="fas fa-file-alt"></i> View Letter of Intent
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>dashboard/business_owner/applications.php" class="btn btn-outline">
            <i class="fas fa-clipboard-list"></i> View Applications
        </a>
        <a href="<?= BASE_URL ?>dashboard/business_owner/certificates.php" class="btn btn-outline">
            <i class="fas fa-certificate"></i> View Certificates
        </a>
        <a href="<?= BASE_URL ?>dashboard/business_owner/audit_findings.php" class="btn btn-outline">
            <i class="fas fa-clipboard-check"></i> Audit Findings
        </a>
        <a href="<?= BASE_URL ?>dashboard/business_owner/restaurant.php" class="btn btn-outline">
            <i class="fas fa-store"></i> Manage Restaurant
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Recent LOIs -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-file-alt" style="color: var(--primary-600); margin-right: 8px;"></i> Recent Letters of Intent</h3>
            <a href="<?= BASE_URL ?>dashboard/business_owner/letter_of_intent.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($lois)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-file-alt"></i></div>
                    <h3>No Letters of Intent Yet</h3>
                    <p>Start your halal certification journey by creating a Letter of Intent.</p>
                    <?php if ($can_create_loi): ?>
                    <a href="<?= BASE_URL ?>dashboard/business_owner/letter_of_intent.php?action=select_body" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create LOI
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lois as $loi): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($loi['company_name']) ?></strong>
                                </td>
                                <td><?= getStatusBadge($loi['status']) ?></td>
                                <td style="color: var(--neutral-500); font-size: 0.85rem;">
                                    <?= formatDate($loi['created_at']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Activity Feed -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-bell" style="color: var(--accent-500); margin-right: 8px;"></i> Recent Activity</h3>
        </div>
        <div class="card-body" style="padding: 16px;">
            <?php if (empty($notifications)): ?>
                <div class="empty-state" style="padding: 30px 10px;">
                    <div class="empty-icon" style="width: 50px; height: 50px; font-size: 1.2rem;"><i class="fas fa-bell-slash"></i></div>
                    <p style="font-size: 0.85rem;">No recent activity</p>
                </div>
            <?php else: ?>
                <div class="workflow-timeline" style="padding-left: 30px;">
                    <?php foreach ($notifications as $notif): ?>
                    <div class="timeline-item <?= $notif['is_read'] ? '' : 'active' ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content" style="padding: 12px;">
                            <div class="timeline-title" style="font-size: 0.85rem;"><?= htmlspecialchars($notif['title']) ?></div>
                            <div class="timeline-description" style="font-size: 0.8rem;"><?= htmlspecialchars($notif['message']) ?></div>
                            <div class="timeline-date"><?= timeAgo($notif['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
