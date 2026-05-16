<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/admin_functions.php';
requireAdmin();
enforceSessionTimeout();
secureSessionRegenerate();

$admin_page_title = 'Security Dashboard';
logAdminActivity($conn, $_SESSION['user_id'], 'Viewed Admin Dashboard', 'admin');

$stats = getAdminStats($conn);

// Recent login attempts
$recent_logins = $conn->query(
    "SELECT la.*, u.full_name FROM login_attempts la
     LEFT JOIN users u ON la.email = u.email
     ORDER BY la.attempted_at DESC LIMIT 10"
)->fetch_all(MYSQLI_ASSOC);

// Recent admin actions
$recent_admin = $conn->query(
    "SELECT aal.*, u.full_name FROM admin_activity_log aal
     JOIN users u ON aal.admin_id = u.id
     ORDER BY aal.created_at DESC LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

// Failed logins by IP (top 5)
$top_failed_ips = $conn->query(
    "SELECT ip_address, COUNT(*) as attempts
     FROM login_attempts WHERE success=0
     AND attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
     GROUP BY ip_address ORDER BY attempts DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

// Users online in the last 5 minutes
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_seen TIMESTAMP NULL DEFAULT NULL");
$online_users = $conn->query("
    SELECT u.id, u.full_name, u.last_seen, r.role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    WHERE u.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ORDER BY u.last_seen DESC
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Stats -->
<div class="admin-stats">
    <div class="admin-stat-card indigo">
        <div class="admin-stat-icon indigo"><i class="fas fa-users"></i></div>
        <div class="admin-stat-value"><?= $stats['total_users'] ?></div>
        <div class="admin-stat-label">Total Users</div>
    </div>
    <div class="admin-stat-card green">
        <div class="admin-stat-icon green"><i class="fas fa-user-check"></i></div>
        <div class="admin-stat-value"><?= $stats['active_users'] ?></div>
        <div class="admin-stat-label">Active Users</div>
    </div>
    <div class="admin-stat-card" style="border-top:3px solid #10b981">
        <div class="admin-stat-icon" style="background:#d1fae5;color:#059669"><i class="fas fa-circle" style="font-size:.65rem;vertical-align:middle;"></i></div>
        <div class="admin-stat-value" style="color:#059669"><?= $stats['online_users'] ?></div>
        <div class="admin-stat-label">Online Now</div>
    </div>
    <div class="admin-stat-card red">
        <div class="admin-stat-icon red"><i class="fas fa-user-lock"></i></div>
        <div class="admin-stat-value"><?= $stats['locked_users'] ?></div>
        <div class="admin-stat-label">Locked Accounts</div>
    </div>
    <div class="admin-stat-card amber">
        <div class="admin-stat-icon amber"><i class="fas fa-sign-in-alt"></i></div>
        <div class="admin-stat-value"><?= $stats['login_attempts_today'] ?></div>
        <div class="admin-stat-label">Login Attempts Today</div>
    </div>
    <div class="admin-stat-card blue">
        <div class="admin-stat-icon blue"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="admin-stat-value"><?= $stats['failed_logins_today'] ?></div>
        <div class="admin-stat-label">Failed Logins Today</div>
    </div>
    <div class="admin-stat-card purple">
        <div class="admin-stat-icon purple"><i class="fas fa-history"></i></div>
        <div class="admin-stat-value"><?= $stats['admin_actions_today'] ?></div>
        <div class="admin-stat-label">Admin Actions Today</div>
    </div>
</div>

<!-- Online Users -->
<div class="admin-card" style="margin-bottom:22px">
    <div class="admin-card-header">
        <h3>
            <span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:#10b981;margin-right:8px;box-shadow:0 0 0 3px #d1fae5;animation:pulse-green 2s infinite;"></span>
            Online Now
            <span style="font-size:.78rem;font-weight:400;color:#64748b;margin-left:8px;">active in the last 5 minutes</span>
        </h3>
        <span style="font-size:.82rem;color:#64748b"><?= count($online_users) ?> user<?= count($online_users) !== 1 ? 's' : '' ?></span>
    </div>
    <div class="admin-card-body" style="padding:16px 20px">
        <?php if (empty($online_users)): ?>
            <p style="color:#94a3b8;font-size:.85rem;margin:0">No users are currently online.</p>
        <?php else: ?>
            <div style="display:flex;flex-wrap:wrap;gap:10px">
                <?php foreach ($online_users as $ou):
                    $initials = strtoupper(substr($ou['full_name'], 0, 1));
                    $seconds_ago = time() - strtotime($ou['last_seen']);
                    if ($seconds_ago < 60)       $seen_label = 'Just now';
                    elseif ($seconds_ago < 120)  $seen_label = '1 min ago';
                    else                         $seen_label = floor($seconds_ago / 60) . ' mins ago';
                    $is_me = ($ou['id'] == $_SESSION['user_id']);
                ?>
                <div style="display:flex;align-items:center;gap:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;min-width:200px;position:relative">
                    <div style="position:relative;flex-shrink:0">
                        <div style="width:36px;height:36px;border-radius:9px;background:#eef2ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem">
                            <?= $initials ?>
                        </div>
                        <span style="position:absolute;bottom:-2px;right:-2px;width:11px;height:11px;border-radius:50%;background:#10b981;border:2px solid #fff;"></span>
                    </div>
                    <div style="min-width:0">
                        <div style="font-weight:600;font-size:.84rem;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px">
                            <?= htmlspecialchars($ou['full_name']) ?>
                            <?php if ($is_me): ?>
                                <span style="font-size:.7rem;color:#6366f1;font-weight:500;margin-left:4px">(you)</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:.74rem;color:#94a3b8;margin-top:1px">
                            <?= htmlspecialchars($ou['role_name'] ?? 'No role') ?>
                        </div>
                        <div style="font-size:.72rem;color:#10b981;margin-top:1px">
                            <?= $seen_label ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<style>
@keyframes pulse-green {
    0%,100% { box-shadow: 0 0 0 3px #d1fae5; }
    50%      { box-shadow: 0 0 0 6px #a7f3d0; }
}
</style>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:22px">

    <!-- Recent Login Attempts -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-sign-in-alt" style="color:#6366f1;margin-right:8px"></i>Recent Login Attempts</h3>
            <a href="<?= BASE_URL ?>admin/login_logs.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div style="padding:0">
            <table class="table admin-table sensitive-table">
                <thead><tr><th>Email</th><th>IP</th><th>Result</th><th>Time</th></tr></thead>
                <tbody>
                <?php foreach ($recent_logins as $l): ?>
                <tr>
                    <td style="font-size:.82rem"><?= htmlspecialchars(maskSensitive($l['email'])) ?></td>
                    <td style="font-size:.82rem;font-family:monospace"><?= htmlspecialchars($l['ip_address']) ?></td>
                    <td>
                        <?php if ($l['success']): ?>
                            <span class="badge badge-success">Success</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Failed</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.78rem;color:#64748b"><?= timeAgo($l['attempted_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recent_logins)): ?>
                <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px">No login attempts yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Admin Activity -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-history" style="color:#6366f1;margin-right:8px"></i>Admin Activity</h3>
            <a href="<?= BASE_URL ?>admin/activity_logs.php" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div style="padding:16px">
            <?php foreach ($recent_admin as $a): ?>
            <div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9">
                <div style="width:32px;height:32px;border-radius:8px;background:#eef2ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0">
                    <i class="fas fa-bolt"></i>
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:.83rem;font-weight:600;color:#1e293b"><?= htmlspecialchars($a['action']) ?></div>
                    <div style="font-size:.75rem;color:#64748b"><?= htmlspecialchars($a['full_name']) ?> · <?= timeAgo($a['created_at']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recent_admin)): ?>
            <p style="text-align:center;color:#94a3b8;padding:20px 0">No admin activity yet</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Top Failed IPs -->
<?php if (!empty($top_failed_ips)): ?>
<div class="admin-card" style="margin-top:22px">
    <div class="admin-card-header">
        <h3><i class="fas fa-ban" style="color:#ef4444;margin-right:8px"></i>Top Failed Login IPs (Last 24h)</h3>
    </div>
    <div class="admin-card-body" style="display:flex;gap:16px;flex-wrap:wrap">
        <?php foreach ($top_failed_ips as $ip): ?>
        <div style="background:#fee2e2;border-radius:10px;padding:14px 20px;text-align:center;min-width:140px">
            <div style="font-family:monospace;font-weight:700;color:#991b1b;font-size:.9rem"><?= htmlspecialchars($ip['ip_address']) ?></div>
            <div style="font-size:.75rem;color:#dc2626;margin-top:4px"><?= $ip['attempts'] ?> failed attempts</div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
