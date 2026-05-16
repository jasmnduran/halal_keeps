<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/admin_functions.php';
requireAdmin();
enforceSessionTimeout();

$admin_page_title = 'Admin Activity Logs';
logAdminActivity($conn, $_SESSION['user_id'], 'Viewed Admin Activity Logs', 'admin');

$search = sanitize($_GET['search'] ?? '');
$date   = sanitize($_GET['date']   ?? '');
$where  = "WHERE 1=1";
if ($search) $where .= " AND (aal.action LIKE '%" . $conn->real_escape_string($search) . "%' OR u.full_name LIKE '%" . $conn->real_escape_string($search) . "%')";
if ($date)   $where .= " AND DATE(aal.created_at)='" . $conn->real_escape_string($date) . "'";

$page     = max(1, intval($_GET['page'] ?? 1));
$per_page = 30;
$offset   = ($page - 1) * $per_page;

$total = (int)$conn->query("SELECT COUNT(*) as c FROM admin_activity_log aal JOIN users u ON aal.admin_id=u.id $where")->fetch_assoc()['c'];
$logs  = $conn->query(
    "SELECT aal.*, u.full_name, u.email FROM admin_activity_log aal
     JOIN users u ON aal.admin_id=u.id
     $where ORDER BY aal.created_at DESC LIMIT $per_page OFFSET $offset"
)->fetch_all(MYSQLI_ASSOC);

$total_pages = ceil($total / $per_page);

require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Filters -->
<div class="admin-card" style="margin-bottom:18px">
    <div class="admin-card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <input type="text" name="search" class="form-control" placeholder="Search action or admin name…"
                   value="<?= htmlspecialchars($search) ?>" style="max-width:280px">
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>" style="max-width:160px">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
            <a href="activity_logs.php" class="btn btn-secondary btn-sm">Reset</a>
            <span style="margin-left:auto;font-size:.8rem;color:#64748b"><?= number_format($total) ?> records</span>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-history" style="color:#6366f1;margin-right:8px"></i>Admin Activity Logs</h3>
    </div>
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
                <tr><th>#</th><th>Admin</th><th>Action</th><th>Target</th><th>Details</th><th>IP</th><th>Time</th></tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $l): ?>
            <tr>
                <td style="font-size:.75rem;color:#94a3b8"><?= $l['id'] ?></td>
                <td>
                    <div style="font-size:.83rem;font-weight:600"><?= htmlspecialchars($l['full_name']) ?></div>
                    <div style="font-size:.72rem;color:#94a3b8"><?= htmlspecialchars(maskSensitive($l['email'])) ?></div>
                </td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:5px;background:#eef2ff;color:#4f46e5;padding:3px 10px;border-radius:6px;font-size:.78rem;font-weight:600">
                        <?= htmlspecialchars($l['action']) ?>
                    </span>
                </td>
                <td style="font-size:.8rem;color:#64748b">
                    <?php if ($l['target_type']): ?>
                        <span class="badge badge-secondary"><?= htmlspecialchars($l['target_type']) ?></span>
                        <?php if ($l['target_id']): ?> #<?= $l['target_id'] ?><?php endif; ?>
                    <?php endif; ?>
                </td>
                <td style="font-size:.78rem;color:#64748b;max-width:220px">
                    <?= htmlspecialchars(substr($l['details'] ?? '', 0, 80)) ?>
                </td>
                <td style="font-family:monospace;font-size:.78rem"><?= htmlspecialchars($l['ip_address'] ?? '') ?></td>
                <td style="font-size:.78rem;color:#64748b;white-space:nowrap"><?= formatDateTime($l['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
            <tr><td colspan="7" style="text-align:center;padding:30px;color:#94a3b8">No records found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($total_pages > 1): ?>
    <div style="padding:16px 22px;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between">
        <span style="font-size:.8rem;color:#64748b">Page <?= $page ?> of <?= $total_pages ?></span>
        <div style="display:flex;gap:4px">
            <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&date=<?= $date ?>"
               style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:6px;font-size:.82rem;font-weight:600;<?= $i===$page ? 'background:#6366f1;color:#fff' : 'background:#f1f5f9;color:#475569' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
