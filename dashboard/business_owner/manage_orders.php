<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$page_title = 'Manage Orders';
$page_heading = 'Manage Orders';
$is_dashboard = true;
$user_id = $_SESSION['user_id'];

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $new_status = sanitize($_POST['new_status']);
    $order_id = intval($_POST['order_id']);
    $stmt = $conn->prepare("UPDATE orders o JOIN halal_restaurants hr ON o.restaurant_id = hr.id SET o.order_status = ? WHERE o.id = ? AND hr.business_owner_id = ?");
    $stmt->bind_param("sii", $new_status, $order_id, $user_id);
    $stmt->execute();
}

$stmt = $conn->prepare("SELECT o.*, u.full_name as customer_name FROM orders o JOIN halal_restaurants hr ON o.restaurant_id = hr.id JOIN users u ON o.customer_id = u.id WHERE hr.business_owner_id = ? ORDER BY o.created_at DESC LIMIT 50");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-shopping-bag" style="color: var(--accent-500); margin-right: 8px;"></i> Customer Orders</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-shopping-bag"></i></div>
                <h3>No Orders Yet</h3>
                <p>Customer orders will appear here once your restaurant starts receiving orders.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Order #</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td><?= formatCurrency($order['total_amount']) ?></td>
                            <td><?= getStatusBadge($order['payment_status']) ?></td>
                            <td><?= getStatusBadge($order['order_status']) ?></td>
                            <td style="font-size: 0.85rem; color: var(--neutral-500);"><?= timeAgo($order['created_at']) ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="new_status" onchange="this.form.submit()" class="form-control" style="width: auto; padding: 4px 8px; font-size: 0.8rem;">
                                        <?php foreach (['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $order['order_status'] === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $s)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
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
