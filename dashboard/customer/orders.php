<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_CUSTOMER]);

$page_title = 'My Orders';
$page_heading = 'My Orders';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/customer/'], ['label' => 'Orders']];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT o.*, hr.restaurant_name FROM orders o JOIN halal_restaurants hr ON o.restaurant_id = hr.id WHERE o.customer_id = ? ORDER BY o.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-shopping-bag" style="color: var(--primary-600); margin-right: 8px;"></i> Order History</h3></div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($orders)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-shopping-bag"></i></div><h3>No Orders</h3><p>Browse restaurants to place your first order!</p><a href="<?= BASE_URL ?>dashboard/customer/restaurants.php" class="btn btn-primary"><i class="fas fa-utensils"></i> Browse Restaurants</a></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Order #</th><th>Restaurant</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
                            <td><?= htmlspecialchars($o['restaurant_name']) ?></td>
                            <td><?= formatCurrency($o['total_amount']) ?></td>
                            <td><?= getStatusBadge($o['payment_status']) ?></td>
                            <td><?= getStatusBadge($o['order_status']) ?></td>
                            <td style="font-size:0.85rem;color:var(--neutral-500)"><?= formatDateTime($o['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
