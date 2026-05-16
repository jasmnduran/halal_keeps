<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_CUSTOMER]);

$page_title = 'Halal Restaurants';
$page_heading = 'Halal Restaurants';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/customer/'], ['label' => 'Restaurants']];
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$success = '';

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $restaurant_id = intval($_POST['restaurant_id']);
    $items = sanitize($_POST['items_json'] ?? '[]');
    $total = floatval($_POST['total_amount'] ?? 0);
    $delivery_address = sanitize($_POST['delivery_address'] ?? '');
    $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
    $notes = sanitize($_POST['notes'] ?? '');
    $order_number = generateReference('ORD');
    
    $stmt = $conn->prepare("INSERT INTO orders (order_number, customer_id, restaurant_id, items_json, subtotal, total_amount, delivery_address, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisddsss", $order_number, $user_id, $restaurant_id, $items, $total, $total, $delivery_address, $payment_method, $notes);
    
    if ($stmt->execute()) {
        // Notify restaurant owner
        $owner = $conn->query("SELECT business_owner_id FROM halal_restaurants WHERE id = $restaurant_id")->fetch_assoc();
        if ($owner) {
            createNotification($conn, $owner['business_owner_id'], 'New Order: ' . $order_number,
                'You have a new order from ' . $_SESSION['full_name'] . '!', 'action_required',
                BASE_URL . 'dashboard/business_owner/manage_orders.php');
        }
        $success = 'Order ' . $order_number . ' placed successfully!';
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $restaurant_id = intval($_POST['restaurant_id']);
    $rating = intval($_POST['rating']);
    $food_rating = intval($_POST['food_rating'] ?? $rating);
    $service_rating = intval($_POST['service_rating'] ?? $rating);
    $comment = sanitize($_POST['comment'] ?? '');
    
    $stmt = $conn->prepare("INSERT INTO reviews (customer_id, restaurant_id, rating, food_rating, service_rating, comment) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiis", $user_id, $restaurant_id, $rating, $food_rating, $service_rating, $comment);
    if ($stmt->execute()) {
        // Update restaurant avg rating
        $conn->query("UPDATE halal_restaurants SET avg_rating = (SELECT AVG(rating) FROM reviews WHERE restaurant_id = $restaurant_id), total_reviews = (SELECT COUNT(*) FROM reviews WHERE restaurant_id = $restaurant_id) WHERE id = $restaurant_id");
        $success = 'Review submitted! Thank you for your feedback.';
    }
}

$search = sanitize($_GET['search'] ?? '');
$cuisine = sanitize($_GET['cuisine'] ?? '');

$where = "WHERE hr.is_active = 1";
if ($search) $where .= " AND (hr.restaurant_name LIKE '%$search%' OR hr.city LIKE '%$search%')";
if ($cuisine) $where .= " AND hr.cuisine_type = '$cuisine'";

$restaurants = $conn->query("SELECT hr.*, hc.certificate_number, hc.status as cert_status FROM halal_restaurants hr LEFT JOIN halal_certificates hc ON hr.certificate_id = hc.id $where ORDER BY hr.avg_rating DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<!-- Search & Filter -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap">
            <div class="form-group" style="flex:1;min-width:200px;margin-bottom:0">
                <label>Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search restaurants, city..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group" style="min-width:180px;margin-bottom:0">
                <label>Cuisine</label>
                <select name="cuisine" class="form-control">
                    <option value="">All Cuisines</option>
                    <?php foreach (['Filipino','Middle Eastern','Indian','Malaysian','Indonesian','Turkish','Mediterranean','Asian Fusion'] as $c): ?>
                    <option value="<?= $c ?>" <?= $cuisine === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="height:42px"><i class="fas fa-search"></i> Search</button>
        </form>
    </div>
</div>

<!-- Restaurant Grid -->
<?php if (empty($restaurants)): ?>
    <div class="card"><div class="card-body"><div class="empty-state"><div class="empty-icon"><i class="fas fa-search"></i></div><h3>No Restaurants Found</h3><p>Try adjusting your search criteria.</p></div></div></div>
<?php else: ?>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
    <?php foreach ($restaurants as $r): ?>
    <div class="card restaurant-card">
        <div style="height: 160px; background: linear-gradient(135deg, var(--primary-100), var(--accent-50)); display: flex; align-items: center; justify-content: center; position: relative;">
            <i class="fas fa-store" style="font-size: 3rem; color: var(--primary-300);"></i>
            <?php if ($r['certificate_number']): ?>
                <div style="position:absolute;top:12px;right:12px" class="halal-badge"><i class="fas fa-check-circle"></i> Halal Certified</div>
            <?php endif; ?>
        </div>
        <div class="restaurant-info">
            <div class="restaurant-name"><?= htmlspecialchars($r['restaurant_name']) ?></div>
            <p style="font-size:0.85rem;color:var(--neutral-500);margin-bottom:8px"><?= htmlspecialchars(substr($r['description'] ?? 'Halal certified restaurant', 0, 80)) ?></p>
            <div class="restaurant-meta" style="margin-bottom: 12px;">
                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($r['city'] ?? $r['address'] ?? 'N/A') ?></span>
                <?php if ($r['cuisine_type']): ?><span><i class="fas fa-utensils"></i> <?= htmlspecialchars($r['cuisine_type']) ?></span><?php endif; ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between">
                <div class="restaurant-rating">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i class="fas fa-star" style="color:<?= $s <= round($r['avg_rating']) ? 'var(--accent-400)' : 'var(--neutral-200)' ?>;font-size:0.85rem"></i>
                    <?php endfor; ?>
                    <span style="margin-left:4px"><?= number_format($r['avg_rating'], 1) ?></span>
                </div>
                <a href="?action=view&id=<?= $r['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> View</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php elseif ($action === 'view' && isset($_GET['id'])): ?>
<?php
    $rid = intval($_GET['id']);
    $r = $conn->query("SELECT hr.*, hc.certificate_number, u.full_name as owner_name FROM halal_restaurants hr LEFT JOIN halal_certificates hc ON hr.certificate_id = hc.id JOIN users u ON hr.business_owner_id = u.id WHERE hr.id = $rid")->fetch_assoc();
    $menu = $conn->query("SELECT * FROM menu_items WHERE restaurant_id = $rid AND is_available = 1 ORDER BY category, name")->fetch_all(MYSQLI_ASSOC);
    $reviews = $conn->query("SELECT rv.*, u.full_name, u.avatar FROM reviews rv JOIN users u ON rv.customer_id = u.id WHERE rv.restaurant_id = $rid ORDER BY rv.created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);
    
    // Check if user already reviewed
    $already_reviewed = $conn->prepare("SELECT id FROM reviews WHERE customer_id = ? AND restaurant_id = ?");
    $already_reviewed->bind_param("ii", $user_id, $rid);
    $already_reviewed->execute();
    $has_review = $already_reviewed->get_result()->num_rows > 0;
?>
<?php if ($r): ?>
<a href="?action=list" class="btn btn-outline btn-sm" style="margin-bottom:20px"><i class="fas fa-arrow-left"></i> Back</a>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <div>
        <!-- Restaurant Info -->
        <div class="card" style="margin-bottom: 24px;">
            <div style="height:200px;background:linear-gradient(135deg,var(--primary-100),var(--accent-50));display:flex;align-items:center;justify-content:center;position:relative">
                <i class="fas fa-store" style="font-size:4rem;color:var(--primary-300)"></i>
                <?php if ($r['certificate_number']): ?>
                <div style="position:absolute;top:16px;right:16px;background:white;padding:8px 16px;border-radius:20px;display:flex;align-items:center;gap:8px;font-weight:600;color:var(--primary-700);box-shadow:var(--shadow-md)">
                    <i class="fas fa-certificate" style="color:var(--accent-500)"></i> Halal Certified
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <h2 style="font-family:var(--font-display);font-weight:800;font-size:1.5rem;margin-bottom:8px"><?= htmlspecialchars($r['restaurant_name']) ?></h2>
                <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px;font-size:0.9rem;color:var(--neutral-500)">
                    <span><i class="fas fa-map-marker-alt" style="color:var(--primary-500)"></i> <?= htmlspecialchars($r['address']) ?></span>
                    <?php if ($r['phone']): ?><span><i class="fas fa-phone" style="color:var(--primary-500)"></i> <?= htmlspecialchars($r['phone']) ?></span><?php endif; ?>
                    <?php if ($r['cuisine_type']): ?><span><i class="fas fa-utensils" style="color:var(--primary-500)"></i> <?= htmlspecialchars($r['cuisine_type']) ?></span><?php endif; ?>
                    <?php if ($r['operating_hours']): ?><span><i class="fas fa-clock" style="color:var(--primary-500)"></i> <?= htmlspecialchars($r['operating_hours']) ?></span><?php endif; ?>
                </div>
                <?php if ($r['description']): ?><p style="color:var(--neutral-600);line-height:1.7"><?= nl2br(htmlspecialchars($r['description'])) ?></p><?php endif; ?>
            </div>
        </div>
        
        <!-- Menu -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header"><h3><i class="fas fa-book-open" style="color:var(--accent-500);margin-right:8px"></i> Menu</h3></div>
            <div class="card-body">
                <?php if (empty($menu)): ?>
                    <p style="color:var(--neutral-500);text-align:center;padding:20px">Menu items coming soon!</p>
                <?php else: ?>
                    <?php
                    $categories = [];
                    foreach ($menu as $item) $categories[$item['category'] ?? 'General'][] = $item;
                    foreach ($categories as $cat => $items):
                    ?>
                    <h4 style="font-family:var(--font-display);font-weight:700;margin:16px 0 12px;color:var(--neutral-700)"><?= htmlspecialchars($cat) ?></h4>
                    <?php foreach ($items as $item): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--neutral-100)">
                        <div>
                            <div style="font-weight:600"><?= htmlspecialchars($item['name']) ?></div>
                            <?php if ($item['description']): ?><div style="font-size:0.85rem;color:var(--neutral-500)"><?= htmlspecialchars($item['description']) ?></div><?php endif; ?>
                        </div>
                        <div style="font-weight:700;color:var(--primary-700)"><?= formatCurrency($item['price']) ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Reviews -->
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-star" style="color:var(--accent-500);margin-right:8px"></i> Reviews (<?= count($reviews) ?>)</h3></div>
            <div class="card-body">
                <?php if (empty($reviews)): ?>
                    <p style="color:var(--neutral-500);text-align:center;padding:20px">No reviews yet. Be the first to review!</p>
                <?php else: ?>
                    <?php foreach ($reviews as $rv): ?>
                    <div style="padding:16px 0;border-bottom:1px solid var(--neutral-100)">
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
                            <div style="width:36px;height:36px;border-radius:50%;background:var(--primary-100);color:var(--primary-700);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem"><?= strtoupper(substr($rv['full_name'],0,1)) ?></div>
                            <div>
                                <div style="font-weight:600;font-size:0.9rem"><?= htmlspecialchars($rv['full_name']) ?></div>
                                <div style="font-size:0.75rem;color:var(--neutral-400)"><?= timeAgo($rv['created_at']) ?></div>
                            </div>
                            <div style="margin-left:auto">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="fas fa-star" style="color:<?= $s <= $rv['rating'] ? 'var(--accent-400)' : 'var(--neutral-200)' ?>;font-size:0.8rem"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if ($rv['comment']): ?><p style="font-size:0.9rem;color:var(--neutral-600);line-height:1.6"><?= nl2br(htmlspecialchars($rv['comment'])) ?></p><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar: Order & Review -->
    <div>
        <!-- Quick Order -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header"><h3><i class="fas fa-cart-plus" style="color:var(--primary-600);margin-right:8px"></i> Place Order</h3></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="place_order" value="1">
                    <input type="hidden" name="restaurant_id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="items_json" value="[]">
                    
                    <div class="form-group">
                        <label>Order Details</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Describe your order (items, quantity, special requests)..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Total Amount (₱)</label>
                        <input type="number" name="total_amount" class="form-control" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Delivery Address</label>
                        <textarea name="delivery_address" class="form-control" rows="2" placeholder="Your delivery address..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">Cash on Delivery</option>
                            <option value="gcash">GCash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-shopping-cart"></i> Place Order</button>
                </form>
            </div>
        </div>
        
        <!-- Write Review -->
        <?php if (!$has_review): ?>
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-pen" style="color:var(--accent-500);margin-right:8px"></i> Write Review</h3></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="submit_review" value="1">
                    <input type="hidden" name="restaurant_id" value="<?= $r['id'] ?>">
                    
                    <div class="form-group">
                        <label>Overall Rating <span class="required">*</span></label>
                        <select name="rating" class="form-control" required>
                            <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                            <option value="4">⭐⭐⭐⭐ Very Good</option>
                            <option value="3">⭐⭐⭐ Good</option>
                            <option value="2">⭐⭐ Fair</option>
                            <option value="1">⭐ Poor</option>
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="form-group">
                            <label>Food</label>
                            <select name="food_rating" class="form-control">
                                <option value="5">5 - Excellent</option>
                                <option value="4">4 - Very Good</option>
                                <option value="3">3 - Good</option>
                                <option value="2">2 - Fair</option>
                                <option value="1">1 - Poor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Service</label>
                            <select name="service_rating" class="form-control">
                                <option value="5">5 - Excellent</option>
                                <option value="4">4 - Very Good</option>
                                <option value="3">3 - Good</option>
                                <option value="2">2 - Fair</option>
                                <option value="1">1 - Poor</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Comment</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="Share your experience..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-accent btn-block"><i class="fas fa-paper-plane"></i> Submit Review</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
