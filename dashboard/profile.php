<?php
require_once __DIR__ . '/../config/app.php';
requireLogin();

$page_title = 'My Profile';
$page_heading = 'My Profile';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Profile']];

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user data
$stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $full_name = $first_name . ' ' . $last_name;
    
    $update = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, full_name = ?, phone = ?, address = ? WHERE id = ?");
    $update->bind_param("sssssi", $first_name, $last_name, $full_name, $phone, $address, $user_id);
    
    if ($update->execute()) {
        $_SESSION['full_name'] = $full_name;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $success = 'Profile updated successfully.';
        
        // Refresh user data
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    } else {
        $error = 'Failed to update profile.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px;">
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span><?= $success ?></span>
            <button class="close-alert"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= $error ?></span>
            <button class="close-alert"><i class="fas fa-times"></i></button>
        </div>
    <?php endif; ?>
    
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-body" style="display: flex; align-items: center; gap: 24px; padding: 32px;">
            <?php if (!empty($user['avatar'])): ?>
                <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar" style="width: 80px; height: 80px; border-radius: 16px; object-fit: cover;">
            <?php else: ?>
                <div style="width: 80px; height: 80px; border-radius: 16px; background: var(--primary-100); color: var(--primary-700); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; font-family: var(--font-display);">
                    <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            <div>
                <h2 style="font-family: var(--font-display); font-weight: 800; font-size: 1.5rem; margin-bottom: 4px;">
                    <?= htmlspecialchars($user['full_name']) ?>
                </h2>
                <p style="color: var(--neutral-500); margin-bottom: 8px;"><?= htmlspecialchars($user['email']) ?></p>
                <div style="display: flex; gap: 8px;">
                    <?= getStatusBadge($user['role_status']) ?>
                    <span class="badge badge-primary"><?= htmlspecialchars($user['role_name'] ?? 'No Role') ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-edit" style="color: var(--primary-600); margin-right: 8px;"></i> Edit Profile</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    <p class="form-text">Email cannot be changed.</p>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="09123456789">
                    </div>
                    <div class="form-group">
                        <label>Member Since</label>
                        <input type="text" class="form-control" value="<?= formatDate($user['created_at']) ?>" disabled>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 8px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
