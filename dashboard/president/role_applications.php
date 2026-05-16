<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_PRESIDENT]);

$page_title = 'Role Applications';
$page_heading = 'Role Applications';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/president/'], ['label' => 'Role Applications']];
$user_id = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_id = intval($_POST['app_id']);
    $action = sanitize($_POST['action']);
    $notes = sanitize($_POST['review_notes'] ?? '');
    
    $stmt = $conn->prepare("UPDATE role_applications SET status = ?, reviewed_by = ?, review_notes = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sisi", $action, $user_id, $notes, $app_id);
    
    if ($stmt->execute()) {
        $ra = $conn->query("SELECT user_id, role_id FROM role_applications WHERE id = $app_id")->fetch_assoc();
        if ($ra) {
            $new_status = ($action === 'approved') ? 'approved' : 'rejected';
            $conn->query("UPDATE users SET role_status = '$new_status' WHERE id = " . $ra['user_id']);
            
            $role_name = getRoleName($ra['role_id'], $conn);
            $msg = ($action === 'approved') 
                ? "Your application for the $role_name role has been approved! You now have access to your dashboard."
                : "Your application for the $role_name role has been declined. $notes";
            
            createNotification($conn, $ra['user_id'], 'Role Application ' . ucfirst($action), $msg,
                $action === 'approved' ? 'success' : 'error',
                $action === 'approved' ? getDashboardUrl($ra['role_id']) : BASE_URL . 'auth/apply_role.php');
        }
        $success = 'Application ' . $action . ' successfully.';
    }
}

$filter = $_GET['filter'] ?? 'pending';
$where = $filter === 'all' ? '' : "WHERE ra.status = '$filter'";
$apps = $conn->query("SELECT ra.*, u.full_name, u.email, u.avatar, r.role_name, r.role_category, rev.full_name as reviewer_name FROM role_applications ra JOIN users u ON ra.user_id = u.id JOIN roles r ON ra.role_id = r.id LEFT JOIN users rev ON ra.reviewed_by = rev.id $where ORDER BY ra.created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="display: flex; gap: 8px;">
        <a href="?filter=pending" class="btn btn-sm <?= $filter === 'pending' ? 'btn-primary' : 'btn-secondary' ?>">Pending</a>
        <a href="?filter=approved" class="btn btn-sm <?= $filter === 'approved' ? 'btn-primary' : 'btn-secondary' ?>">Approved</a>
        <a href="?filter=rejected" class="btn btn-sm <?= $filter === 'rejected' ? 'btn-primary' : 'btn-secondary' ?>">Rejected</a>
        <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>Role Applications</h3></div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($apps)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-user-check"></i></div><h3>No Applications</h3></div>
        <?php else: ?>
            <?php foreach ($apps as $app): ?>
            <div style="display: flex; align-items: center; gap: 16px; padding: 20px 24px; border-bottom: 1px solid var(--neutral-100);">
                <?php if ($app['avatar']): ?>
                    <img src="<?= htmlspecialchars($app['avatar']) ?>" style="width:48px;height:48px;border-radius:12px;object-fit:cover">
                <?php else: ?>
                    <div style="width:48px;height:48px;border-radius:12px;background:var(--primary-100);color:var(--primary-700);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem"><?= strtoupper(substr($app['full_name'],0,1)) ?></div>
                <?php endif; ?>
                
                <div style="flex:1;min-width:0">
                    <div style="font-weight:600;color:var(--neutral-800)"><?= htmlspecialchars($app['full_name']) ?></div>
                    <div style="font-size:0.85rem;color:var(--neutral-500)"><?= htmlspecialchars($app['email']) ?></div>
                </div>
                
                <div style="text-align:center;min-width:120px">
                    <span class="badge badge-primary"><?= htmlspecialchars($app['role_name']) ?></span>
                    <div style="font-size:0.75rem;color:var(--neutral-400);margin-top:4px"><?= ucfirst($app['role_category']) ?></div>
                </div>
                
                <div style="text-align:center;min-width:80px">
                    <?= getStatusBadge($app['status']) ?>
                </div>
                
                <div style="font-size:0.85rem;color:var(--neutral-500);min-width:100px;text-align:center">
                    <?= formatDate($app['created_at']) ?>
                </div>
                
                <?php if ($app['status'] === 'pending'): ?>
                <div style="display:flex;gap:6px">
                    <form method="POST" style="display:inline"><input type="hidden" name="app_id" value="<?= $app['id'] ?>"><input type="hidden" name="action" value="approved"><button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button></form>
                    <button class="btn btn-sm btn-danger" data-modal="rejectModal<?= $app['id'] ?>"><i class="fas fa-times"></i></button>
                </div>
                
                <div class="modal-overlay" id="rejectModal<?= $app['id'] ?>">
                    <div class="modal" style="max-width:500px">
                        <div class="modal-header"><h3>Reject Application</h3><button class="modal-close"><i class="fas fa-times"></i></button></div>
                        <div class="modal-body">
                            <form method="POST">
                                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                <input type="hidden" name="action" value="rejected">
                                <div class="form-group">
                                    <label>Reason for Rejection</label>
                                    <textarea name="review_notes" class="form-control" rows="3" placeholder="Provide a reason..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger btn-block"><i class="fas fa-times"></i> Reject Application</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div style="min-width:80px"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
