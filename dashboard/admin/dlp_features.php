<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_ADMIN]);

$page_title = 'DLP Features';
$page_heading = 'Data Loss Prevention';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Admin Dashboard', 'url' => BASE_URL . 'dashboard/admin/'], ['label' => 'DLP Features']];

$success = '';
$error = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_dlp') {
    $timeout = intval($_POST['session_timeout']);
    
    if ($timeout >= 5 && $timeout <= 1440) {
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'session_timeout_minutes'");
        $stmt->bind_param("s", $timeout);
        if ($stmt->execute()) {
            $success = "DLP Settings updated successfully.";
            
            // Log Admin Activity
            $admin_id = $_SESSION['user_id'];
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $log = $conn->prepare("INSERT INTO admin_activity_logs (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, 'Update DLP Policy', 'system', 'session_timeout', ?, ?)");
            $details = "Updated session timeout to {$timeout} minutes";
            $log->bind_param("iss", $admin_id, $details, $ip);
            $log->execute();
        } else {
            $error = "Failed to update settings.";
        }
    } else {
        $error = "Session timeout must be between 5 and 1440 minutes.";
    }
}

// Fetch current setting
$timeout_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'session_timeout_minutes'");
$current_timeout = $timeout_query ? intval($timeout_query->fetch_assoc()['setting_value']) : 30;

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= $error ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

    <!-- Session Timeout Settings -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock" style="color: var(--primary-600); margin-right: 8px;"></i> Session Management</h3>
        </div>
        <div class="card-body">
            <p style="font-size: 0.9rem; color: var(--neutral-600); margin-bottom: 20px;">Automatically terminate idle user sessions to prevent unauthorized access to sensitive application data.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_dlp">
                <div class="form-group">
                    <label style="font-weight: 600;">Global Inactive Session Timeout (Minutes)</label>
                    <div style="display: flex; align-items: center; gap: 12px; margin-top: 8px;">
                        <input type="number" name="session_timeout" class="form-control" value="<?= $current_timeout ?>" min="5" max="1440" style="width: 120px;" required>
                        <span style="font-size: 0.85rem; color: var(--neutral-500);">minutes</span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 12px;">Save Policy</button>
            </form>
        </div>
    </div>

    <!-- Data Classification -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-tags" style="color: var(--primary-600); margin-right: 8px;"></i> Data Classification Tags</h3>
        </div>
        <div class="card-body">
            <p style="font-size: 0.9rem; color: var(--neutral-600); margin-bottom: 20px;">Tag application documents and data fields with classification levels to enforce proper handling.</p>
            
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="border: 1px solid var(--neutral-200); border-radius: 8px; padding: 12px; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <span class="badge badge-danger">Confidential</span>
                        <p style="font-size: 0.8rem; color: var(--neutral-500); margin: 4px 0 0 0;">Highest protection. Encrypted at rest. E.g. Lab Results, Passwords.</p>
                    </div>
                    <span style="color: var(--success); font-size: 0.85rem; font-weight: 600;"><i class="fas fa-check"></i> Enforced</span>
                </div>
                
                <div style="border: 1px solid var(--neutral-200); border-radius: 8px; padding: 12px; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <span class="badge badge-warning">Internal Only</span>
                        <p style="font-size: 0.8rem; color: var(--neutral-500); margin: 4px 0 0 0;">Accessible to authorized roles only. E.g. Letters of Intent, Application details.</p>
                    </div>
                    <span style="color: var(--success); font-size: 0.85rem; font-weight: 600;"><i class="fas fa-check"></i> Enforced</span>
                </div>

                <div style="border: 1px solid var(--neutral-200); border-radius: 8px; padding: 12px; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <span class="badge badge-info">Public</span>
                        <p style="font-size: 0.8rem; color: var(--neutral-500); margin: 4px 0 0 0;">No restriction. E.g. Awarded Certificates, General company info.</p>
                    </div>
                    <span style="color: var(--success); font-size: 0.85rem; font-weight: 600;"><i class="fas fa-check"></i> Enforced</span>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
