<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/admin_functions.php';
requireAdmin();
enforceSessionTimeout();

$admin_page_title = 'API Permissions';
logAdminActivity($conn, $_SESSION['user_id'], 'Viewed API Permissions', 'admin');

// API endpoints and their required roles
$api_endpoints = [
    ['endpoint' => '/api/notifications.php',          'method' => 'GET',  'roles' => 'All authenticated', 'auth' => true,  'description' => 'Fetch user notifications'],
    ['endpoint' => '/admin/api/check_permission.php', 'method' => 'GET',  'roles' => 'All authenticated', 'auth' => true,  'description' => 'Check user permission'],
    ['endpoint' => '/admin/api/ping_session.php',     'method' => 'POST', 'roles' => 'All authenticated', 'auth' => true,  'description' => 'Keep session alive'],
    ['endpoint' => '/admin/api/log_dlp.php',          'method' => 'POST', 'roles' => 'All authenticated', 'auth' => true,  'description' => 'Log DLP events'],
    ['endpoint' => '/admin/users.php',                'method' => 'POST', 'roles' => 'Admin only',        'auth' => true,  'description' => 'Manage user accounts'],
    ['endpoint' => '/admin/data_classification.php',  'method' => 'POST', 'roles' => 'Admin only',        'auth' => true,  'description' => 'Update data classifications'],
    ['endpoint' => '/admin/dlp_settings.php',         'method' => 'POST', 'roles' => 'Admin only',        'auth' => true,  'description' => 'Update DLP settings'],
    ['endpoint' => '/dashboard/president/*',          'method' => 'ANY',  'roles' => 'President (8)',     'auth' => true,  'description' => 'President dashboard'],
    ['endpoint' => '/dashboard/evaluator/*',          'method' => 'ANY',  'roles' => 'Evaluator (3)',     'auth' => true,  'description' => 'Evaluator dashboard'],
    ['endpoint' => '/dashboard/auditor/*',            'method' => 'ANY',  'roles' => 'Auditor (4,5)',     'auth' => true,  'description' => 'Auditor dashboard'],
    ['endpoint' => '/auth/register.php',              'method' => 'POST', 'roles' => 'Public',            'auth' => false, 'description' => 'User registration'],
    ['endpoint' => '/auth/login.php',                 'method' => 'POST', 'roles' => 'Public',            'auth' => false, 'description' => 'User login'],
];

// Recent permission check logs
$perm_logs = $conn->query(
    "SELECT aal.*, u.full_name FROM admin_activity_log aal
     JOIN users u ON aal.admin_id=u.id
     WHERE aal.action LIKE 'Permission Check%'
     ORDER BY aal.created_at DESC LIMIT 15"
)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/includes/admin_header.php';
?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:22px">

    <!-- API Endpoints -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-plug" style="color:#6366f1;margin-right:8px"></i>API Endpoints & Access Control</h3>
        </div>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr><th>Endpoint</th><th>Method</th><th>Required Role</th><th>Auth</th><th>Description</th></tr>
                </thead>
                <tbody>
                <?php foreach ($api_endpoints as $ep): ?>
                <tr>
                    <td style="font-family:monospace;font-size:.78rem;color:#4f46e5"><?= htmlspecialchars($ep['endpoint']) ?></td>
                    <td>
                        <span style="background:<?= $ep['method']==='GET' ? '#dbeafe' : ($ep['method']==='POST' ? '#dcfce7' : '#f3e8ff') ?>;color:<?= $ep['method']==='GET' ? '#1e40af' : ($ep['method']==='POST' ? '#166534' : '#6d28d9') ?>;padding:2px 8px;border-radius:4px;font-size:.72rem;font-weight:700">
                            <?= $ep['method'] ?>
                        </span>
                    </td>
                    <td style="font-size:.8rem"><?= htmlspecialchars($ep['roles']) ?></td>
                    <td>
                        <?php if ($ep['auth']): ?>
                            <span class="badge badge-success" style="font-size:.68rem"><i class="fas fa-lock" style="font-size:.6rem"></i> Required</span>
                        <?php else: ?>
                            <span class="badge badge-secondary" style="font-size:.68rem"><i class="fas fa-globe" style="font-size:.6rem"></i> Public</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.78rem;color:#64748b"><?= htmlspecialchars($ep['description']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Permission Checks -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-key" style="color:#6366f1;margin-right:8px"></i>Recent Permission Checks</h3>
        </div>
        <div style="padding:12px">
            <?php foreach ($perm_logs as $l): ?>
            <div style="padding:10px;border-radius:8px;background:#f8fafc;margin-bottom:6px">
                <div style="font-size:.8rem;font-weight:600;color:#1e293b"><?= htmlspecialchars($l['action']) ?></div>
                <div style="font-size:.72rem;color:#64748b;margin-top:2px">
                    <?= htmlspecialchars($l['full_name']) ?> · <?= timeAgo($l['created_at']) ?>
                </div>
                <?php if ($l['details']): ?>
                <div style="margin-top:4px">
                    <?php if (str_contains($l['details'], 'GRANTED')): ?>
                        <span class="badge badge-success" style="font-size:.65rem">GRANTED</span>
                    <?php else: ?>
                        <span class="badge badge-danger" style="font-size:.65rem">DENIED</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (empty($perm_logs)): ?>
            <p style="text-align:center;color:#94a3b8;padding:20px 0;font-size:.85rem">No permission checks logged yet</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- How it works -->
<div class="admin-card" style="margin-top:22px">
    <div class="admin-card-header">
        <h3><i class="fas fa-code" style="color:#6366f1;margin-right:8px"></i>How to Use the Permission API</h3>
    </div>
    <div class="admin-card-body">
        <p style="font-size:.85rem;color:#64748b;margin-bottom:16px">
            Call <code style="background:#f1f5f9;padding:2px 6px;border-radius:4px">/halal_final/admin/api/check_permission.php?permission=manage_users</code> from any frontend JS to verify access before performing sensitive operations.
        </p>
        <pre style="background:#0f172a;color:#e2e8f0;padding:18px;border-radius:10px;font-size:.8rem;overflow-x:auto">fetch('/halal_final/admin/api/check_permission.php?permission=manage_users')
  .then(r => r.json())
  .then(data => {
    if (data.allowed) {
      // proceed with action
    } else {
      alert('Access denied');
    }
  });</pre>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
