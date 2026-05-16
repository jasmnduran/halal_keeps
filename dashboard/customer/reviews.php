<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_CUSTOMER]);

$page_title = 'My Reviews';
$page_heading = 'My Reviews';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/customer/'], ['label' => 'Reviews']];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT rv.*, hr.restaurant_name FROM reviews rv JOIN halal_restaurants hr ON rv.restaurant_id = hr.id WHERE rv.customer_id = ? ORDER BY rv.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header"><h3><i class="fas fa-star" style="color: var(--accent-500); margin-right: 8px;"></i> My Reviews</h3></div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($reviews)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-star"></i></div><h3>No Reviews</h3><p>You haven't reviewed any restaurants yet.</p></div>
        <?php else: ?>
            <?php foreach ($reviews as $rv): ?>
            <div style="padding: 20px 24px; border-bottom: 1px solid var(--neutral-100);">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <div>
                        <strong style="font-size: 1rem;"><?= htmlspecialchars($rv['restaurant_name']) ?></strong>
                        <div style="font-size: 0.8rem; color: var(--neutral-400); margin-top: 2px;"><?= formatDateTime($rv['created_at']) ?></div>
                    </div>
                    <div>
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <i class="fas fa-star" style="color: <?= $s <= $rv['rating'] ? 'var(--accent-400)' : 'var(--neutral-200)' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <div style="display: flex; gap: 16px; margin-bottom: 8px; font-size: 0.85rem; color: var(--neutral-500);">
                    <span>Food: <?= $rv['food_rating'] ?>/5</span>
                    <span>Service: <?= $rv['service_rating'] ?>/5</span>
                </div>
                <?php if ($rv['comment']): ?>
                    <p style="color: var(--neutral-600); line-height: 1.6;"><?= nl2br(htmlspecialchars($rv['comment'])) ?></p>
                <?php endif; ?>
                <?php if ($rv['response']): ?>
                    <div style="background: var(--primary-50); padding: 12px 16px; border-radius: 8px; margin-top: 12px; font-size: 0.9rem;">
                        <strong style="color: var(--primary-700);">Owner Reply:</strong>
                        <p style="margin-top: 4px; color: var(--neutral-700);"><?= nl2br(htmlspecialchars($rv['response'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
