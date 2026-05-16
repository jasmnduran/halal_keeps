<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/admin_functions.php';
requireAdmin();
enforceSessionTimeout();

$admin_page_title = 'Roles & Permissions';
logAdminActivity($conn, $_SESSION['user_id'], 'Viewed Roles & Permissions', 'admin');

// Roles with user counts
$roles = $conn->query(
    "SELECT r.*, COUNT(u.id) as user_count
     FROM roles r LEFT JOIN users u ON r.id=u.role_id AND u.role_status='approved'
     GROUP BY r.id ORDER BY r.id"
)->fetch_all(MYSQLI_ASSOC);

// Permission matrix (which roles can access which modules)
$permission_matrix = [
    'View Dashboard'        => [1,2,3,4,5,6,7,8,9,10,11],
    'Submit LOI'            => [2],
    'Verify LOI'            => [3],
    'Submit Application'    => [2],
    'Verify Application'    => [3],
    'Schedule Inspection'   => [3],
    'Conduct Inspection'    => [4,5],
    'Submit NCR Report'     => [4,5],
    'Review Evidence'       => [6],
    'Make Final Decision'   => [7],
    'Award Certificate'     => [8],
    'Manage Users'          => [8,11],
    'View All Reports'      => [8,11],
    'Receive Lab Samples'   => [9],
    'Analyze Samples'       => [10],
    'Admin Panel Access'    => [11],
    'Manage Roles'          => [11],
    'View Security Logs'    => [11],
];

require_once __DIR__ . '/includes/admin_header.php';
?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:22px">

    <!-- Roles List -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-user-tag" style="color:#6366f1;margin-right:8px"></i>System Roles</h3>
        </div>
        <div style="padding:0">
            <?php foreach ($roles as $r): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid #f1f5f9">
                <div style="width:36px;height:36px;border-radius:8px;background:#eef2ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;flex-shrink:0">
                    <?= $r['id'] ?>
                </div>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:.88rem;color:#1e293b"><?= htmlspecialchars($r['role_name']) ?></div>
                    <div style="font-size:.75rem;color:#94a3b8"><?= ucfirst(str_replace('_',' ',$r['role_category'])) ?></div>
                </div>
                <div style="text-align:right">
                    <div style="font-weight:700;font-size:1rem;color:#6366f1"><?= $r['user_count'] ?></div>
                    <div style="font-size:.7rem;color:#94a3b8">users</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Permission Matrix -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-table" style="color:#6366f1;margin-right:8px"></i>Permission Matrix</h3>
            <span style="font-size:.75rem;color:#94a3b8">Read-only — modify via code</span>
        </div>
        <div class="table-responsive" style="max-height:520px;overflow-y:auto">
            <table class="table admin-table" style="font-size:.75rem">
                <thead>
                    <tr>
                        <th style="position:sticky;top:0;background:#f8fafc;z-index:1">Permission</th>
                        <?php foreach ($roles as $r): ?>
                        <th style="position:sticky;top:0;background:#f8fafc;z-index:1;text-align:center;white-space:nowrap;font-size:.65rem">
                            <?= htmlspecialchars(substr($r['role_name'],0,10)) ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($permission_matrix as $perm => $allowed_roles): ?>
                <tr>
                    <td style="font-weight:500;white-space:nowrap"><?= htmlspecialchars($perm) ?></td>
                    <?php foreach ($roles as $r): ?>
                    <td style="text-align:center">
                        <?php if (in_array($r['id'], $allowed_roles)): ?>
                            <i class="fas fa-check-circle" style="color:#22c55e;font-size:.85rem"></i>
                        <?php else: ?>
                            <i class="fas fa-times-circle" style="color:#e2e8f0;font-size:.85rem"></i>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- RBAC Info -->
<div class="admin-card" style="margin-top:22px">
    <div class="admin-card-header">
        <h3><i class="fas fa-info-circle" style="color:#6366f1;margin-right:8px"></i>RBAC Implementation Notes</h3>
    </div>
    <div class="admin-card-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
            <div style="background:#f8fafc;border-radius:10px;padding:16px">
                <div style="font-weight:700;font-size:.85rem;color:#1e293b;margin-bottom:6px"><i class="fas fa-code" style="color:#6366f1;margin-right:6px"></i>PHP Enforcement</div>
                <p style="font-size:.8rem;color:#64748b;line-height:1.5">Every dashboard page calls <code>requireRole([ROLE_X])</code> which checks <code>$_SESSION['role_id']</code> and redirects unauthorized users.</p>
            </div>
            <div style="background:#f8fafc;border-radius:10px;padding:16px">
                <div style="font-weight:700;font-size:.85rem;color:#1e293b;margin-bottom:6px"><i class="fas fa-key" style="color:#6366f1;margin-right:6px"></i>API Permission Check</div>
                <p style="font-size:.8rem;color:#64748b;line-height:1.5">The <code>/admin/api/check_permission.php</code> endpoint validates permissions for AJAX calls and logs every check.</p>
            </div>
            <div style="background:#f8fafc;border-radius:10px;padding:16px">
                <div style="font-weight:700;font-size:.85rem;color:#1e293b;margin-bottom:6px"><i class="fas fa-shield-alt" style="color:#6366f1;margin-right:6px"></i>Admin Guard</div>
                <p style="font-size:.8rem;color:#64748b;line-height:1.5">Admin pages call <code>requireAdmin()</code> which checks <code>$_SESSION['is_admin']</code> and logs unauthorized access attempts.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
