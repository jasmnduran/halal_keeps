<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$page_title = 'My Restaurant';
$page_heading = 'My Restaurant';
$is_dashboard = true;
$user_id = $_SESSION['user_id'];
$error = ''; $success = '';

// Get restaurant
$stmt = $conn->prepare("SELECT hr.*, hc.certificate_number, hc.status as cert_status FROM halal_restaurants hr LEFT JOIN halal_certificates hc ON hr.certificate_id = hc.id WHERE hr.business_owner_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();

// Handle create/update restaurant
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['restaurant_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $province = sanitize($_POST['province'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $hours = sanitize($_POST['operating_hours'] ?? '');
    $cuisine = sanitize($_POST['cuisine_type'] ?? '');
    
    if ($restaurant) {
        $update = $conn->prepare("UPDATE halal_restaurants SET restaurant_name=?, description=?, address=?, city=?, province=?, phone=?, email=?, operating_hours=?, cuisine_type=? WHERE id=?");
        $update->bind_param("sssssssssi", $name, $description, $address, $city, $province, $phone, $email, $hours, $cuisine, $restaurant['id']);
        if ($update->execute()) {
            $success = 'Restaurant updated successfully!';
            $stmt->execute();
            $restaurant = $stmt->get_result()->fetch_assoc();
        }
    } else {
        $insert = $conn->prepare("INSERT INTO halal_restaurants (business_owner_id, restaurant_name, description, address, city, province, phone, email, operating_hours, cuisine_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert->bind_param("isssssssss", $user_id, $name, $description, $address, $city, $province, $phone, $email, $hours, $cuisine);
        if ($insert->execute()) {
            $success = 'Restaurant created!';
            $stmt->execute();
            $restaurant = $stmt->get_result()->fetch_assoc();
        }
    }
}

// Get menu items
$menu_items = [];
if ($restaurant) {
    $mi = $conn->prepare("SELECT * FROM menu_items WHERE restaurant_id = ? ORDER BY category, name");
    $mi->bind_param("i", $restaurant['id']);
    $mi->execute();
    $menu_items = $mi->get_result()->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-store" style="color: var(--primary-600); margin-right: 8px;"></i> <?= $restaurant ? 'Edit' : 'Register' ?> Restaurant</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Restaurant Name <span class="required">*</span></label>
                    <input type="text" name="restaurant_name" class="form-control" value="<?= htmlspecialchars($restaurant['restaurant_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Cuisine Type</label>
                    <select name="cuisine_type" class="form-control">
                        <option value="">Select...</option>
                        <?php foreach (['Filipino', 'Middle Eastern', 'Indian', 'Malaysian', 'Indonesian', 'Turkish', 'Mediterranean', 'Asian Fusion', 'Other'] as $type): ?>
                            <option value="<?= $type ?>" <?= ($restaurant['cuisine_type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($restaurant['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label>Address <span class="required">*</span></label>
                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($restaurant['address'] ?? '') ?>" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($restaurant['city'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Province</label>
                    <input type="text" name="province" class="form-control" value="<?= htmlspecialchars($restaurant['province'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($restaurant['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($restaurant['email'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Operating Hours</label>
                <input type="text" name="operating_hours" class="form-control" value="<?= htmlspecialchars($restaurant['operating_hours'] ?? '') ?>" placeholder="Mon-Fri: 8AM-10PM, Sat-Sun: 9AM-11PM">
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $restaurant ? 'Update' : 'Create' ?> Restaurant</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
