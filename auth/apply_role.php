<?php
require_once __DIR__ . '/../config/app.php';
requireLogin();

// Admins go straight to the admin panel
if (!empty($_SESSION['is_admin'])) {
    header('Location: ' . BASE_URL . 'admin/');
    exit();
}

// If user already has an approved role, redirect to dashboard
if (hasApprovedRole()) {
    header('Location: ' . getDashboardUrl($_SESSION['role_id']));
    exit();
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Check if there's a pending application
$pending_app = null;
$stmt = $conn->prepare("SELECT ra.*, r.role_name FROM role_applications ra JOIN roles r ON ra.role_id = r.id WHERE ra.user_id = ? ORDER BY ra.created_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_result = $stmt->get_result();
if ($row = $pending_result->fetch_assoc()) {
    $pending_app = $row;
}

// Get all available roles
$roles_result = $conn->query("SELECT * FROM roles ORDER BY id");
$roles = $roles_result->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_id = intval($_POST['role_id'] ?? 0);
    $application_letter = sanitize($_POST['application_letter'] ?? '');
    $business_name = sanitize($_POST['business_name'] ?? '');
    $business_address = sanitize($_POST['business_address'] ?? '');
    $business_type = sanitize($_POST['business_type'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    
    if ($role_id === 0) {
        $error = 'Please select a role.';
    } else {
        // Upload resume if provided
        $resume_path = '';
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['resume'], 'role_applications');
            if ($upload['success']) {
                $resume_path = $upload['path'];
            }
        }
        
        // Upload credentials if provided
        $credentials_path = '';
        if (isset($_FILES['credentials']) && $_FILES['credentials']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadFile($_FILES['credentials'], 'role_applications');
            if ($upload['success']) {
                $credentials_path = $upload['path'];
            }
        }
        
        // Check the role
        $role_check = $conn->prepare("SELECT * FROM roles WHERE id = ?");
        $role_check->bind_param("i", $role_id);
        $role_check->execute();
        $role = $role_check->get_result()->fetch_assoc();
        
        if (!$role) {
            $error = 'Invalid role selected.';
        } else {
            // If role doesn't require approval (Customer), approve immediately
            if ($role['requires_approval'] == 0) {
                $update_user = $conn->prepare("UPDATE users SET role_id = ?, role_status = 'approved', phone = COALESCE(NULLIF(?, ''), phone), address = COALESCE(NULLIF(?, ''), address) WHERE id = ?");
                $update_user->bind_param("issi", $role_id, $phone, $address, $user_id);
                $update_user->execute();
                
                // Update session
                $_SESSION['role_id'] = $role_id;
                $_SESSION['role_status'] = 'approved';
                $_SESSION['role_name'] = $role['role_name'];
                
                logActivity($conn, $user_id, 'Role Applied', 'User applied as ' . $role['role_name'] . ' (auto-approved)', 'auth');
                
                header('Location: ' . getDashboardUrl($role_id));
                exit();
            } else {
                // Approve immediately and redirect to dashboard
                $update_user = $conn->prepare("UPDATE users SET role_id = ?, role_status = 'approved', phone = COALESCE(NULLIF(?, ''), phone), address = COALESCE(NULLIF(?, ''), address) WHERE id = ?");
                $update_user->bind_param("issi", $role_id, $phone, $address, $user_id);
                $update_user->execute();

                // Update session
                $_SESSION['role_id'] = $role_id;
                $_SESSION['role_status'] = 'approved';
                $_SESSION['role_name'] = $role['role_name'];

                logActivity($conn, $user_id, 'Role Applied', 'User applied as ' . $role['role_name'] . ' (auto-approved)', 'auth');

                header('Location: ' . getDashboardUrl($role_id));
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Role - Halal Institute of Development Philippines</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        .apply-page {
            min-height: 100vh;
            background: var(--neutral-50);
            padding: 40px 20px;
        }
        .apply-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .apply-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .apply-header .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--primary-800);
            margin-bottom: 24px;
        }
        .apply-header .brand .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: var(--primary-100);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-700);
            font-size: 1.1rem;
        }
        .pending-card {
            background: linear-gradient(135deg, #fef9c3, #fef3c7);
            border: 2px solid #fde68a;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
        }
        .pending-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: var(--accent-600);
            box-shadow: 0 8px 25px rgba(245,158,11,0.2);
        }
        .rejected-card {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border: 2px solid #fca5a5;
        }
        .rejected-card .pending-icon {
            color: var(--danger);
            box-shadow: 0 8px 25px rgba(239,68,68,0.2);
        }
        .role-requirements {
            margin-top: 12px;
            padding: 16px;
            background: var(--neutral-50);
            border-radius: 12px;
            font-size: 0.85rem;
            color: var(--neutral-600);
        }
    </style>
</head>
<body>

<div class="apply-page">
    <div class="apply-container">
        <div class="apply-header">
            <a href="<?= BASE_URL ?>" class="brand">
                <div class="brand-icon">
                    <i class="fas fa-certificate"></i>
                </div>
                HID Philippines
            </a>
            <h1 style="font-family: var(--font-display); font-size: 2rem; font-weight: 800; color: var(--neutral-900); margin-bottom: 8px;">
                <?= $pending_app ? 'Application Status' : 'Choose Your Role' ?>
            </h1>
            <p style="color: var(--neutral-500); font-size: 1rem;">
                Welcome, <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>! 
                <?= $pending_app ? 'Here is the status of your role application.' : 'Select a role to get started with the platform.' ?>
            </p>
            
            <div style="margin-top: 12px;">
                <a href="<?= BASE_URL ?>auth/logout.php" style="color: var(--neutral-400); font-size: 0.85rem;">
                    <i class="fas fa-sign-out-alt"></i> Sign Out
                </a>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= $error ?></span>
                <button class="close-alert"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= $success ?></span>
                <button class="close-alert"><i class="fas fa-times"></i></button>
            </div>
        <?php endif; ?>
        
        <?php if ($pending_app && $pending_app['status'] === 'pending'): ?>
        <!-- Pending Application Status -->
        <div class="pending-card animate-fade-in-up">
            <div class="pending-icon">
                <i class="fas fa-clock"></i>
            </div>
            <h2 style="font-family: var(--font-display); font-weight: 800; font-size: 1.5rem; color: var(--neutral-900); margin-bottom: 8px;">
                Application Under Review
            </h2>
            <p style="color: var(--neutral-600); max-width: 500px; margin: 0 auto 24px; line-height: 1.7;">
                Your application for <strong><?= htmlspecialchars($pending_app['role_name']) ?></strong> 
                has been submitted and is currently being reviewed. You will be notified once a decision is made.
            </p>
            <div style="display: inline-flex; gap: 12px; align-items: center; background: white; padding: 14px 24px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <span style="font-weight: 600; color: var(--neutral-700);">Role Applied:</span>
                <span class="badge badge-warning" style="font-size: 0.85rem; padding: 6px 16px;"><?= htmlspecialchars($pending_app['role_name']) ?></span>
            </div>
            <p style="margin-top: 16px; font-size: 0.85rem; color: var(--neutral-400);">
                Submitted on <?= formatDateTime($pending_app['created_at']) ?>
            </p>
        </div>
        
        <?php elseif ($pending_app && $pending_app['status'] === 'rejected'): ?>
        <!-- Rejected Application -->
        <div class="pending-card rejected-card animate-fade-in-up">
            <div class="pending-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <h2 style="font-family: var(--font-display); font-weight: 800; font-size: 1.5rem; color: var(--neutral-900); margin-bottom: 8px;">
                Application Declined
            </h2>
            <p style="color: var(--neutral-600); max-width: 500px; margin: 0 auto 16px; line-height: 1.7;">
                Your application for <strong><?= htmlspecialchars($pending_app['role_name']) ?></strong> 
                has been declined.
            </p>
            <?php if ($pending_app['review_notes']): ?>
            <div style="background: white; padding: 16px 24px; border-radius: 12px; text-align: left; max-width: 500px; margin: 0 auto 20px;">
                <strong style="font-size: 0.85rem; color: var(--neutral-600);">Reviewer's Notes:</strong>
                <p style="color: var(--neutral-700); margin-top: 6px; font-size: 0.9rem;"><?= htmlspecialchars($pending_app['review_notes']) ?></p>
            </div>
            <?php endif; ?>
            <p style="font-size: 0.9rem; color: var(--neutral-600); margin-bottom: 20px;">
                You can apply again with the correct requirements:
            </p>
            <a href="<?= BASE_URL ?>auth/apply_role.php?reapply=1" class="btn btn-primary">
                <i class="fas fa-redo"></i> Apply Again
            </a>
        </div>
        
        <?php else: ?>
        <!-- Role Selection -->
        <form method="POST" action="" enctype="multipart/form-data" id="roleApplicationForm">
            <input type="hidden" name="role_id" id="selected_role" value="">
            
            <div class="card" style="margin-bottom: 32px;">
                <div class="card-header">
                    <h3><i class="fas fa-user-tag" style="color: var(--primary-600); margin-right: 8px;"></i> Select Your Role</h3>
                </div>
                <div class="card-body">
                    <div class="role-grid">
                        <?php foreach ($roles as $role): 
                            $icon_map = [
                                1 => ['fas fa-user', 'green', 'customer'],
                                2 => ['fas fa-store', 'gold', 'business'],
                                3 => ['fas fa-clipboard-check', 'blue', 'hcb'],
                                4 => ['fas fa-cogs', 'purple', 'hcb'],
                                5 => ['fas fa-book-quran', 'teal', 'hcb'],
                                6 => ['fas fa-balance-scale', 'blue', 'hcb'],
                                7 => ['fas fa-gavel', 'red', 'hcb'],
                                8 => ['fas fa-crown', 'gold', 'hcb'],
                                9 => ['fas fa-inbox', 'blue', 'lab'],
                                10 => ['fas fa-microscope', 'purple', 'lab'],
                            ];
                            $icon = $icon_map[$role['id']] ?? ['fas fa-user', 'green', 'customer'];
                            $category_labels = [
                                'customer' => 'Customer',
                                'business' => 'Business',
                                'hcb_employee' => 'HCB Staff',
                                'lab_employee' => 'Laboratory',
                            ];
                        ?>
                        <div class="role-card" data-role-id="<?= $role['id'] ?>" onclick="selectRole(this)">
                            <span class="role-category <?= $icon[2] ?>"><?= $category_labels[$role['role_category']] ?? $role['role_category'] ?></span>
                            <div class="role-icon <?= $icon[1] ?>">
                                <i class="<?= $icon[0] ?>"></i>
                            </div>
                            <h4><?= htmlspecialchars($role['role_name']) ?></h4>
                            <p><?= htmlspecialchars($role['description']) ?></p>
                                <div class="role-requirements" style="background: var(--primary-50); color: var(--primary-700);">
                                    <i class="fas fa-check-circle"></i> Instant access
                                </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Application Details (shown after role selection) -->
            <div class="card" id="applicationDetails" style="display: none;">
                <div class="card-header">
                    <h3><i class="fas fa-file-alt" style="color: var(--primary-600); margin-right: 8px;"></i> Application Details</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" class="form-control" placeholder="09123456789" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" class="form-control" placeholder="Your address">
                        </div>
                    </div>
                    
                    <!-- Business Owner Fields -->
                    <div class="role-fields" id="role-fields-2" style="display: none;">
                        <h4 style="font-family: var(--font-display); font-weight: 700; margin: 20px 0 16px; color: var(--neutral-700);">
                            <i class="fas fa-building" style="color: var(--accent-500);"></i> Business Information
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label for="business_name">Business Name <span class="required">*</span></label>
                                <input type="text" id="business_name" name="business_name" class="form-control" placeholder="Your business name">
                            </div>
                            <div class="form-group">
                                <label for="business_type">Business Type</label>
                                <select id="business_type" name="business_type" class="form-control">
                                    <option value="">Select type...</option>
                                    <option value="restaurant">Restaurant</option>
                                    <option value="food_processing">Food Processing</option>
                                    <option value="catering">Catering</option>
                                    <option value="food_stall">Food Stall</option>
                                    <option value="bakery">Bakery</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="business_address">Business Address <span class="required">*</span></label>
                            <textarea id="business_address" name="business_address" class="form-control" rows="3" placeholder="Complete business address"></textarea>
                        </div>
                    </div>
                    
                    <!-- HCB Employee Fields -->
                    <div class="role-fields" id="role-fields-hcb" style="display: none;">
                        <h4 style="font-family: var(--font-display); font-weight: 700; margin: 20px 0 16px; color: var(--neutral-700);">
                            <i class="fas fa-id-card" style="color: var(--info);"></i> Professional Credentials
                        </h4>
                        <div class="form-group">
                            <label for="application_letter">Application Letter / Statement of Intent <span class="required">*</span></label>
                            <textarea id="application_letter" name="application_letter" class="form-control" rows="4" placeholder="Why do you want this role? Describe your qualifications and experience..."></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label>Resume / CV</label>
                                <div class="file-upload">
                                    <input type="file" name="resume" accept=".pdf,.doc,.docx">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p class="file-label">Click or drag to upload resume (PDF, DOC)</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Credentials / Certificates</label>
                                <div class="file-upload">
                                    <input type="file" name="credentials" accept=".pdf,.doc,.docx,.jpg,.png">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p class="file-label">Click or drag to upload credentials</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                        <a href="<?= BASE_URL ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                            <i class="fas fa-paper-plane"></i> Submit Application
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script>
function selectRole(card) {
    // Remove selected from all
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    
    const roleId = card.getAttribute('data-role-id');
    document.getElementById('selected_role').value = roleId;
    
    // Show application details
    document.getElementById('applicationDetails').style.display = 'block';
    document.getElementById('submitBtn').disabled = false;
    
    // Hide all role-specific fields
    document.querySelectorAll('.role-fields').forEach(f => f.style.display = 'none');
    
    // Show role-specific fields
    if (roleId === '2') {
        document.getElementById('role-fields-2').style.display = 'block';
    } else if (['3','4','5','6','7','8','9','10'].includes(roleId)) {
        document.getElementById('role-fields-hcb').style.display = 'block';
    }
    
    // Smooth scroll to details
    document.getElementById('applicationDetails').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Form validation
document.getElementById('roleApplicationForm')?.addEventListener('submit', function(e) {
    const roleId = document.getElementById('selected_role').value;
    if (!roleId) {
        e.preventDefault();
        showToast('Please select a role first.', 'warning');
    }
});
</script>
</body>
</html>
