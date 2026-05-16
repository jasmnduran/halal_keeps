<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_ADMIN]);

$page_title = 'Logging & Monitoring';
$page_heading = 'System Logs';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Admin Dashboard', 'url' => BASE_URL . 'dashboard/admin/'], ['label' => 'Logs']];

// Fetch recent login attempts
$login_query = "
    SELECT la.*, u.full_name, u.email as user_email
    FROM login_attempts la
    LEFT JOIN users u ON la.email = u.email
    ORDER BY la.attempt_time DESC
    LIMIT 50
";
$login_logs = $conn->query($login_query)->fetch_all(MYSQLI_ASSOC);

// Fetch admin activity logs
$admin_query = "
    SELECT aal.*, u.full_name as admin_name
    FROM admin_activity_logs aal
    LEFT JOIN users u ON aal.admin_id = u.id
    ORDER BY aal.created_at DESC
    LIMIT 50
";
$admin_logs = $conn->query($admin_query)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr; gap: 24px;">

    <!-- Admin Activity Logs -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-user-shield" style="color: var(--primary-600); margin-right: 8px;"></i> Admin Activity Logs</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($admin_logs)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-history"></i></div>
                    <p>No admin activity recorded yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table" style="font-size: 0.85rem;">
                        <thead style="position: sticky; top: 0; background: white; z-index: 1;">
                            <tr>
                                <th>Timestamp</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Target</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admin_logs as $log): ?>
                            <tr>
                                <td style="color: var(--neutral-500);"><?= formatDateTime($log['created_at']) ?></td>
                                <td><strong><?= htmlspecialchars($log['admin_name']) ?></strong></td>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                                <td><?= htmlspecialchars($log['target_type'] . ':' . $log['target_id']) ?></td>
                                <td style="font-family: monospace; color: var(--neutral-600);"><?= htmlspecialchars($log['ip_address']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Login Attempts Log -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-sign-in-alt" style="color: var(--primary-600); margin-right: 8px;"></i> Recent Login Attempts</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($login_logs)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-history"></i></div>
                    <p>No login attempts recorded yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table" style="font-size: 0.85rem;">
                        <thead style="position: sticky; top: 0; background: white; z-index: 1;">
                            <tr>
                                <th>Timestamp</th>
                                <th>Email / User</th>
                                <th>Status</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($login_logs as $log): ?>
                            <tr>
                                <td style="color: var(--neutral-500);"><?= formatDateTime($log['attempt_time']) ?></td>
                                <td>
                                    <?= htmlspecialchars($log['email']) ?><br>
                                    <span style="font-size: 0.75rem; color: var(--neutral-400);"><?= htmlspecialchars($log['full_name'] ?? 'Unknown User') ?></span>
                                </td>
                                <td>
                                    <?php if ($log['success']): ?>
                                        <span class="badge badge-success">Success</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-family: monospace; color: var(--neutral-600);"><?= htmlspecialchars($log['ip_address']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
