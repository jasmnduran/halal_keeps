<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_ADMIN]);

$page_title = 'Role Approvals';
$page_heading = 'Role Approvals';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Admin Dashboard', 'url' => BASE_URL . 'dashboard/admin/'], ['label' => 'Role Approvals']];

$error = '';
$success = '';

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    $target_user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if (in_array($action, ['approve', 'reject'])) {
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';
        
        $stmt = $conn->prepare("UPDATE users SET role_status = ? WHERE id = ? AND role_status = 'pending'");
        $stmt->bind_param("si", $new_status, $target_user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success = "User role has been successfully {$new_status}.";
            
            // Log Admin Activity
            $admin_id = $_SESSION['user_id'];
            $log_action = ucfirst($new_status) . " user role";
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $log = $conn->prepare("INSERT INTO admin_activity_logs (admin_id, action, target_type, target_id, ip_address) VALUES (?, ?, 'user', ?, ?)");
            $log->bind_param("isis", $admin_id, $log_action, $target_user_id, $ip);
            $log->execute();
            
            // Create notification for the user
            $notif_title = ($action === 'approve') ? 'Role Approved' : 'Role Rejected';
            $notif_msg = ($action === 'approve') ? 'Your role application has been approved. Please log in again to access your dashboard.' : 'Your role application has been rejected by the administrator.';
            createNotification($conn, $target_user_id, $notif_title, $notif_msg);
        } else {
            $error = "Failed to update role status. The user might not exist or is no longer pending.";
        }
    }
}

// Get pending users
$query = "
    SELECT u.*, r.role_name 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    WHERE u.role_status = 'pending' 
    ORDER BY u.created_at ASC
";
$result = $conn->query($query);
$pending_users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= $error ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-user-clock" style="color: var(--primary-600); margin-right: 8px;"></i> Pending Role Applications</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($pending_users)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-check-double"></i></div>
                <h3>All Caught Up!</h3>
                <p>There are no pending role applications to review.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User Details</th>
                            <th>Requested Role</th>
                            <th>Applied On</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_users as $user): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($user['full_name']) ?></strong><br>
                                <span style="font-size: 0.85rem; color: var(--neutral-500);"><?= htmlspecialchars($user['email']) ?></span>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?= htmlspecialchars($user['role_name'] ?? 'Unknown Role') ?></span>
                            </td>
                            <td style="color: var(--neutral-600); font-size: 0.9rem;">
                                <?= formatDateTime($user['created_at']) ?>
                            </td>
                            <td style="text-align: right;">
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this role?');">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" style="display: inline-block; margin-left: 4px;">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this role?');">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
