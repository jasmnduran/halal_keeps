<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/admin_functions.php';
requireAdmin();
enforceSessionTimeout();

$admin_page_title = 'User Management';
$success = $error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = sanitize($_POST['action'] ?? '');
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($action === 'toggle_lock') {
        $user = $conn->query("SELECT account_locked, full_name FROM users WHERE id=$user_id")->fetch_assoc();
        if ($user) {
            $new = $user['account_locked'] ? 0 : 1;
            $conn->query("UPDATE users SET account_locked=$new, failed_login_attempts=0 WHERE id=$user_id");
            $verb = $new ? 'Locked' : 'Unlocked';
            logAdminActivity($conn, $_SESSION['user_id'], "$verb User Account", 'user', $user_id, $user['full_name']);
            $success = "Account {$verb}: " . htmlspecialchars($user['full_name']);
        }
    } elseif ($action === 'toggle_active') {
        $user = $conn->query("SELECT is_active, full_name FROM users WHERE id=$user_id")->fetch_assoc();
        if ($user) {
            $new = $user['is_active'] ? 0 : 1;
            $conn->query("UPDATE users SET is_active=$new WHERE id=$user_id");
            $verb = $new ? 'Activated' : 'Deactivated';
            logAdminActivity($conn, $_SESSION['user_id'], "$verb User", 'user', $user_id, $user['full_name']);
            $success = "User {$verb}: " . htmlspecialchars($user['full_name']);
        }
    } elseif ($action === 'toggle_admin') {
        if ($user_id === $_SESSION['user_id']) {
            $error = 'You cannot change your own admin status.';
        } else {
            $user = $conn->query("SELECT is_admin, full_name FROM users WHERE id=$user_id")->fetch_assoc();
            if ($user) {
                $new = $user['is_admin'] ? 0 : 1;
                $conn->query("UPDATE users SET is_admin=$new WHERE id=$user_id");
                $verb = $new ? 'Granted admin to' : 'Revoked admin from';
                logAdminActivity($conn, $_SESSION['user_id'], $verb . ' user', 'user', $user_id, $user['full_name']);
                $success = ucfirst($verb) . ': ' . htmlspecialchars($user['full_name']);
            }
        }
    } elseif ($action === 'reset_password') {
        $new_pass = $_POST['new_password'] ?? '';
        if (strlen($new_pass) < 8 || !preg_match('/[A-Z]/', $new_pass) ||
            !preg_match('/[a-z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass) ||
            !preg_match('/[\W_]/', $new_pass)) {
            $error = 'New password does not meet the policy requirements.';
        } else {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt   = $conn->prepare("UPDATE users SET password=?, failed_login_attempts=0, account_locked=0 WHERE id=?");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();
            $user = $conn->query("SELECT full_name FROM users WHERE id=$user_id")->fetch_assoc();
            logAdminActivity($conn, $_SESSION['user_id'], 'Admin Password Reset', 'user', $user_id,
                ($user['full_name'] ?? '') . ' — password reset by admin');
            $success = 'Password reset successfully for ' . htmlspecialchars($user['full_name'] ?? '');
        }
    }
}

// Filters
$search    = sanitize($_GET['search'] ?? '');
$filter    = sanitize($_GET['filter'] ?? 'all');
$where     = "WHERE 1=1";
if ($search) $where .= " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%')";
if ($filter === 'locked')   $where .= " AND u.account_locked=1";
if ($filter === 'inactive') $where .= " AND u.is_active=0";
if ($filter === 'admin')    $where .= " AND u.is_admin=1";
if ($filter === 'online')   $where .= " AND u.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)";

// Ensure last_seen column exists
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_seen TIMESTAMP NULL DEFAULT NULL");

$users = $conn->query(
    "SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id=r.id $where ORDER BY u.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/includes/admin_header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= $error ?></span></div>
<?php endif; ?>

<!-- Filters -->
<div class="admin-card" style="margin-bottom:18px">
    <div class="admin-card-body" style="padding:16px 20px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
            <input type="text" name="search" class="form-control" placeholder="Search name or email…"
                   value="<?= htmlspecialchars($search) ?>" style="max-width:260px">
            <select name="filter" class="form-control" style="max-width:160px" onchange="this.form.submit()">
                <option value="all"      <?= $filter==='all'      ? 'selected':'' ?>>All Users</option>
                <option value="online"   <?= $filter==='online'   ? 'selected':'' ?>>🟢 Online Now</option>
                <option value="locked"   <?= $filter==='locked'   ? 'selected':'' ?>>Locked</option>
                <option value="inactive" <?= $filter==='inactive' ? 'selected':'' ?>>Inactive</option>
                <option value="admin"    <?= $filter==='admin'    ? 'selected':'' ?>>Admins</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Search</button>
            <a href="users.php" class="btn btn-secondary btn-sm">Reset</a>
            <span style="margin-left:auto;font-size:.82rem;color:#64748b"><?= count($users) ?> users found</span>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h3><i class="fas fa-users" style="color:#6366f1;margin-right:8px"></i>User Accounts</h3>
    </div>
    <div class="table-responsive sensitive-table">
        <table class="table admin-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Admin</th>
                    <th>Locked</th>
                    <th>Last Login</th>
                    <th>Last Seen</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u):
                $is_online = !empty($u['last_seen']) && (time() - strtotime($u['last_seen'])) < 300;
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="position:relative;flex-shrink:0">
                            <div style="width:32px;height:32px;border-radius:8px;background:#eef2ff;color:#6366f1;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem">
                                <?= strtoupper(substr($u['full_name'],0,1)) ?>
                            </div>
                            <?php if ($is_online): ?>
                            <span style="position:absolute;bottom:-2px;right:-2px;width:10px;height:10px;border-radius:50%;background:#10b981;border:2px solid #fff;" title="Online now"></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($u['full_name']) ?></div>
                            <div style="font-size:.75rem;color:#94a3b8">#<?= $u['id'] ?></div>
                        </div>
                    </div>
                </td>
                <td style="font-size:.82rem"><?= htmlspecialchars(maskSensitive($u['email'])) ?></td>
                <td><span class="badge badge-primary" style="font-size:.7rem"><?= htmlspecialchars($u['role_name'] ?? 'None') ?></span></td>
                <td>
                    <?php if ($u['is_active']): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="toggle_admin">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <label class="toggle-switch" title="Toggle admin">
                            <input type="checkbox" <?= $u['is_admin'] ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span class="toggle-slider"></span>
                        </label>
                    </form>
                </td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="toggle_lock">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <label class="toggle-switch" title="Lock/Unlock account">
                            <input type="checkbox" <?= $u['account_locked'] ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span class="toggle-slider" style="<?= $u['account_locked'] ? 'background:#ef4444' : '' ?>"></span>
                        </label>
                    </form>
                </td>
                <td style="font-size:.78rem;color:#64748b">
                    <?= $u['last_login'] ? timeAgo($u['last_login']) : 'Never' ?>
                </td>
                <td style="font-size:.78rem">
                    <?php if ($is_online): ?>
                        <span style="display:inline-flex;align-items:center;gap:5px;color:#059669;font-weight:600;">
                            <span style="width:8px;height:8px;border-radius:50%;background:#10b981;display:inline-block;"></span>
                            Online now
                        </span>
                    <?php elseif (!empty($u['last_seen'])): ?>
                        <span style="color:#94a3b8"><?= timeAgo($u['last_seen']) ?></span>
                    <?php else: ?>
                        <span style="color:#cbd5e1">Never</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display:flex;gap:4px">
                        <!-- Toggle active -->
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-secondary' : 'btn-success' ?>"
                                    title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                <i class="fas fa-<?= $u['is_active'] ? 'ban' : 'check' ?>"></i>
                            </button>
                        </form>
                        <!-- Reset password -->
                        <button class="btn btn-sm btn-outline" onclick="openResetModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['full_name'])) ?>')"
                                title="Reset Password">
                            <i class="fas fa-key"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
            <tr><td colspan="8" style="text-align:center;padding:30px;color:#94a3b8">No users found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal-overlay" id="resetModal">
    <div class="modal" style="max-width:460px">
        <div class="modal-header">
            <h3><i class="fas fa-key" style="color:#6366f1;margin-right:8px"></i>Reset Password</h3>
            <button class="modal-close" onclick="document.getElementById('resetModal').classList.remove('active')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p style="font-size:.85rem;color:#64748b;margin-bottom:16px">
                Resetting password for: <strong id="resetUserName"></strong>
            </p>
            <form method="POST" id="resetForm">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="resetUserId">
                <div class="form-group">
                    <label>New Password <span class="required">*</span></label>
                    <input type="password" name="new_password" id="resetPassword" class="form-control"
                           placeholder="Must meet password policy" required autocomplete="new-password">
                    <ul style="list-style:none;padding:0;margin:8px 0 0;display:grid;grid-template-columns:1fr 1fr;gap:3px 10px" id="resetChecklist">
                        <li id="rc-length"  style="font-size:.75rem;color:#9ca3af;display:flex;align-items:center;gap:5px"><i class="fas fa-circle" style="font-size:.4rem"></i> 8+ characters</li>
                        <li id="rc-upper"   style="font-size:.75rem;color:#9ca3af;display:flex;align-items:center;gap:5px"><i class="fas fa-circle" style="font-size:.4rem"></i> Uppercase</li>
                        <li id="rc-lower"   style="font-size:.75rem;color:#9ca3af;display:flex;align-items:center;gap:5px"><i class="fas fa-circle" style="font-size:.4rem"></i> Lowercase</li>
                        <li id="rc-number"  style="font-size:.75rem;color:#9ca3af;display:flex;align-items:center;gap:5px"><i class="fas fa-circle" style="font-size:.4rem"></i> Number</li>
                        <li id="rc-special" style="font-size:.75rem;color:#9ca3af;display:flex;align-items:center;gap:5px"><i class="fas fa-circle" style="font-size:.4rem"></i> Special char</li>
                    </ul>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-key"></i> Reset Password
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openResetModal(id, name) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetUserName').textContent = name;
    document.getElementById('resetModal').classList.add('active');
}
document.querySelector('#resetModal .modal-close')?.addEventListener('click', () => {
    document.getElementById('resetModal').classList.remove('active');
});

// Password policy checker for reset modal
const rp = document.getElementById('resetPassword');
const rcChecks = {
    'rc-length':  p => p.length >= 8,
    'rc-upper':   p => /[A-Z]/.test(p),
    'rc-lower':   p => /[a-z]/.test(p),
    'rc-number':  p => /[0-9]/.test(p),
    'rc-special': p => /[\W_]/.test(p),
};
rp?.addEventListener('input', function () {
    Object.entries(rcChecks).forEach(([id, test]) => {
        const el = document.getElementById(id);
        if (!el) return;
        if (test(this.value)) {
            el.style.color = '#16a34a';
            el.querySelector('i').className = 'fas fa-check-circle';
        } else {
            el.style.color = '#9ca3af';
            el.querySelector('i').className = 'fas fa-circle';
            el.querySelector('i').style.fontSize = '.4rem';
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
