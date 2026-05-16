<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_CUSTOMER]);

$page_title = 'Customer Dashboard';
$page_heading = 'Dashboard';
$is_dashboard = true;
$user_id = $_SESSION['user_id'];

$order_count = $conn->prepare("SELECT COUNT(*) as c FROM orders WHERE customer_id = ?");
$order_count->bind_param("i", $user_id);
$order_count->execute();
$total_orders = $order_count->get_result()->fetch_assoc()['c'];

$review_count = $conn->prepare("SELECT COUNT(*) as c FROM reviews WHERE customer_id = ?");
$review_count->bind_param("i", $user_id);
$review_count->execute();
$total_reviews = $review_count->get_result()->fetch_assoc()['c'];

$restaurant_count = $conn->query("SELECT COUNT(*) as c FROM halal_restaurants WHERE is_active = 1")->fetch_assoc()['c'];

$pending_orders = $conn->prepare("SELECT COUNT(*) as c FROM orders WHERE customer_id = ? AND order_status NOT IN ('delivered','cancelled')");
$pending_orders->bind_param("i", $user_id);
$pending_orders->execute();
$active_orders = $pending_orders->get_result()->fetch_assoc()['c'];

// Featured restaurants
$featured = $conn->query("SELECT hr.*, hc.certificate_number FROM halal_restaurants hr LEFT JOIN halal_certificates hc ON hr.certificate_id = hc.id WHERE hr.is_active = 1 ORDER BY hr.avg_rating DESC LIMIT 6")->fetch_all(MYSQLI_ASSOC);

// Recent orders
$recent_orders = $conn->prepare("SELECT o.*, hr.restaurant_name FROM orders o JOIN halal_restaurants hr ON o.restaurant_id = hr.id WHERE o.customer_id = ? ORDER BY o.created_at DESC LIMIT 5");
$recent_orders->bind_param("i", $user_id);
$recent_orders->execute();
$orders = $recent_orders->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="stats-grid">
    <div class="card card-stat">
        <div class="stat-icon green"><i class="fas fa-utensils"></i></div>
        <div class="stat-value"><?= $restaurant_count ?></div>
        <div class="stat-label">Halal Restaurants</div>
    </div>
    <div class="card card-stat accent">
        <div class="stat-icon gold"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-value"><?= $total_orders ?></div>
        <div class="stat-label">My Orders</div>
    </div>
    <div class="card card-stat info">
        <div class="stat-icon blue"><i class="fas fa-truck"></i></div>
        <div class="stat-value"><?= $active_orders ?></div>
        <div class="stat-label">Active Orders</div>
    </div>
    <div class="card card-stat">
        <div class="stat-icon red"><i class="fas fa-star"></i></div>
        <div class="stat-value"><?= $total_reviews ?></div>
        <div class="stat-label">My Reviews</div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="display: flex; gap: 12px;">
        <a href="<?= BASE_URL ?>dashboard/customer/restaurants.php" class="btn btn-primary"><i class="fas fa-utensils"></i> Browse Restaurants</a>
        <a href="<?= BASE_URL ?>dashboard/customer/orders.php" class="btn btn-outline"><i class="fas fa-shopping-bag"></i> My Orders</a>
        <a href="<?= BASE_URL ?>dashboard/customer/reviews.php" class="btn btn-outline"><i class="fas fa-star"></i> My Reviews</a>
    </div>
</div>

<!-- Featured Restaurants -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3><i class="fas fa-fire" style="color: var(--accent-500); margin-right: 8px;"></i> Halal Certified Restaurants</h3>
        <a href="<?= BASE_URL ?>dashboard/customer/restaurants.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($featured)): ?>
            <div class="empty-state" style="padding: 30px;">
                <div class="empty-icon"><i class="fas fa-store"></i></div>
                <h3>No Restaurants Yet</h3>
                <p>Halal certified restaurants will appear here.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                <?php foreach ($featured as $r): ?>
                <div class="card restaurant-card" style="border: 1px solid var(--neutral-200);">
                    <div style="height: 140px; background: linear-gradient(135deg, var(--primary-100), var(--accent-50)); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-store" style="font-size: 3rem; color: var(--primary-300);"></i>
                    </div>
                    <div class="restaurant-info">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <div class="restaurant-name"><?= htmlspecialchars($r['restaurant_name']) ?></div>
                            <?php if ($r['certificate_number']): ?>
                                <span class="halal-badge"><i class="fas fa-check-circle"></i> Halal</span>
                            <?php endif; ?>
                        </div>
                        <div class="restaurant-meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($r['city'] ?? 'N/A') ?></span>
                            <?php if ($r['cuisine_type']): ?>
                                <span><i class="fas fa-utensils"></i> <?= htmlspecialchars($r['cuisine_type']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div class="restaurant-rating">
                                <i class="fas fa-star"></i>
                                <span><?= number_format($r['avg_rating'], 1) ?></span>
                                <span style="color: var(--neutral-400); font-weight: 400;">(<?= $r['total_reviews'] ?>)</span>
                            </div>
                            <a href="<?= BASE_URL ?>dashboard/customer/restaurants.php?action=view&id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">View Menu</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Orders -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-clock" style="color: var(--primary-600); margin-right: 8px;"></i> Recent Orders</h3>
        <a href="<?= BASE_URL ?>dashboard/customer/orders.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($orders)): ?>
            <div class="empty-state" style="padding: 30px;"><div class="empty-icon"><i class="fas fa-shopping-bag"></i></div><h3>No Orders Yet</h3><p>Start ordering from halal certified restaurants!</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Order #</th><th>Restaurant</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
                            <td><?= htmlspecialchars($o['restaurant_name']) ?></td>
                            <td><?= formatCurrency($o['total_amount']) ?></td>
                            <td><?= getStatusBadge($o['order_status']) ?></td>
                            <td style="font-size:0.85rem;color:var(--neutral-500)"><?= timeAgo($o['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
