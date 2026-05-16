<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/admin_functions.php';
requireAdmin();
enforceSessionTimeout();

$admin_page_title = 'Login Attempt Logs';
logAdminActivity($conn, $_SESSION['user_id'], 'Viewed Login Logs', 'admin');

// Filters
$filter_result = sanitize($_GET['result'] ?? 'all');
$filter_date   = sanitize($_GET['date']   ?? '');
$search_email  = sanitize($_GET['email']  ?? '');

$where = "WHERE 1=1";
if ($filter_result === 'success') $where .= " AND success=1";
if ($filter_result === 'failed')  $where .= " AND success=0";
if ($filter_date)  $where .= " AND DATE(attempted_at)='" . $conn->real_escape_string($filter_date) . "'";
if ($search_email) $where .= " AND email LIKE '%" . $conn->real_escape_string($search_email) . "%'";

$page     = max(1, intval($_GET['page'] ?? 1));
$per_page = 25;
$offset   = ($page - 1) * $per_page;

$total = (int)$conn->query("SELECT COUNT(*) as c FROM login_attempts $where")->fetch_assoc()['c'];
$logs  = $conn->query(
    "SELECT * FROM login_attempts $where ORDER BY attempted_at DESC LIMIT $per_page OFFSET $offset"
)->fetch_all(MYSQLI_ASSOC);

$total_pages = ceil($total / $per_page);

// Summary stats
$today_total   = (int)$conn->query("SELECT COUNT(*) as c FROM login_attempts WHERE DATE(attempted_at)=CURDATE()")->fetch_assoc()['c'];
$today_failed  = (int)$conn->query("SELECT COUNT(*) as c FROM login_attempts WHERE DATE(attempted_at)=CURDATE() AND success=0")->fetch_assoc()['c'];
$today_success = $today_total - $today_failed;

require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Summary -->
<div class="admin-stats" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
    <div class="admin-stat-card blue">
        <div class="admin-stat-icon blue"><i class="fas fa-sign-in-alt"></i></div>
        <div class="admin-stat-value"><?= $today_total ?></div>
        <div class="admin-stat-label">Total Attempts Today</div>
    </div>
    <div class="admin-stat-card green">
        <div class="admin-stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="admin-stat-value"><?= $today_success ?></div>
        <div class="admin-stat-label">Successful Today</div>
    </div>
    <div class="admin-stat-card red">
        <div class="admin-stat-icon red"><i class="fas fa-times-circle"></i></div>
        <div class="admin-stat-value"><?= $today_failed ?></div>
        <div class="admin-stat-label">Failed Today</div>
    </div>
</div>

<!-- Filters -->
<div class="admin-card" style="margin-bottom:18px">
    <div class="admin-card-body" style="padding:14px 20px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <input type="text" name="email" class="form-control" placeholder="Filter by email…"
                   value="<?= htmlspecialchars($search_email) ?>" style="max-width:220px">
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>" style="max-width:160px">
            <select name="result" class="form-control" style="max-width:140px">
                <option value="all"     <?= $filter_result==='all'     ? 'selected':'' ?>>All Results</option>
                <option value="success" <?= $filter_result==='success' ? 'selected':'' ?>>Success Only</option>
                <option value="failed"  <?= $filter_result==='failed'  ? 'selected':'' ?>>Failed Only</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
            <a href="login_logs.php" class="btn btn-secondary btn-sm">Reset</a>
            <span style="margin-left:auto;font-size:.8rem;color:#64748b"><?= number_format($total) ?> records</span>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-sign-in-alt" style="color:#6366f1;margin-right:8px"></i>Login Attempt Logs</h3>
    </div>
    <div class="table-responsive sensitive-table">
        <table class="table admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Email</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                    <th>Result</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $l): ?>
            <tr>
                <td style="font-size:.78rem;color:#94a3b8"><?= $l['id'] ?></td>
                <td style="font-size:.82rem"><?= htmlspecialchars($l['email']) ?></td>
                <td style="font-family:monospace;font-size:.82rem"><?= htmlspecialchars($l['ip_address']) ?></td>
                <td style="font-size:.75rem;color:#64748b;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                    title="<?= htmlspecialchars($l['user_agent'] ?? '') ?>">
                    <?= htmlspecialchars(substr($l['user_agent'] ?? '', 0, 60)) ?>
                </td>
                <td>
                    <?php if ($l['success']): ?>
                        <span class="badge badge-success"><i class="fas fa-check" style="font-size:.6rem"></i> Success</span>
                    <?php else: ?>
                        <span class="badge badge-danger"><i class="fas fa-times" style="font-size:.6rem"></i> Failed</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:.8rem;color:#64748b;white-space:nowrap">
                    <?= formatDateTime($l['attempted_at']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
            <tr><td colspan="6" style="text-align:center;padding:30px;color:#94a3b8">No records found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div style="padding:16px 22px;border-top:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between">
        <span style="font-size:.8rem;color:#64748b">Page <?= $page ?> of <?= $total_pages ?></span>
        <div style="display:flex;gap:4px">
            <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
            <a href="?page=<?= $i ?>&result=<?= $filter_result ?>&date=<?= $filter_date ?>&email=<?= urlencode($search_email) ?>"
               style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:6px;font-size:.82rem;font-weight:600;<?= $i===$page ? 'background:#6366f1;color:#fff' : 'background:#f1f5f9;color:#475569' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
