<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_PRESIDENT]);

$page_title = 'Manage Users';
$page_heading = 'Manage Users';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/president/'], ['label' => 'Users']];

$users = $conn->query("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-users" style="color: var(--primary-600); margin-right: 8px;"></i> All Users</h3></div>
    <div class="card-body" style="padding: 0;">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="display:flex;align-items:center;gap:10px">
                            <?php if ($u['avatar']): ?>
                                <img src="<?= htmlspecialchars($u['avatar']) ?>" style="width:32px;height:32px;border-radius:8px;object-fit:cover">
                            <?php else: ?>
                                <div style="width:32px;height:32px;border-radius:8px;background:var(--primary-100);color:var(--primary-700);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem"><?= strtoupper(substr($u['full_name'],0,1)) ?></div>
                            <?php endif; ?>
                            <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                        </td>
                        <td style="font-size:0.85rem"><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge badge-primary"><?= htmlspecialchars($u['role_name'] ?? 'None') ?></span></td>
                        <td><?= getStatusBadge($u['role_status']) ?></td>
                        <td style="font-size:0.85rem;color:var(--neutral-500)"><?= formatDate($u['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
