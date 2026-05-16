<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/admin_functions.php';
requireAdmin();
enforceSessionTimeout();

$admin_page_title = 'System Settings';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['site_name','site_tagline','certification_validity_years','inspection_base_fee','laboratory_base_fee','certification_fee'];
    foreach ($keys as $k) {
        $val  = sanitize($_POST[$k] ?? '');
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value=? WHERE setting_key=?");
        $stmt->bind_param("ss", $val, $k);
        $stmt->execute();
    }
    logAdminActivity($conn, $_SESSION['user_id'], 'Updated System Settings', 'settings');
    $success = 'System settings saved.';
}

$settings = [];
$res = $conn->query("SELECT setting_key, setting_value, description FROM system_settings ORDER BY id");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row;
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:22px">

    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-cog" style="color:#6366f1;margin-right:8px"></i>General Settings</h3>
        </div>
        <div class="admin-card-body">
            <form method="POST">
                <?php
                $editable = [
                    'site_name'                    => ['label' => 'Site Name',                    'type' => 'text'],
                    'site_tagline'                 => ['label' => 'Site Tagline',                 'type' => 'text'],
                    'certification_validity_years' => ['label' => 'Certificate Validity (years)', 'type' => 'number'],
                    'inspection_base_fee'          => ['label' => 'Inspection Base Fee (₱)',      'type' => 'number'],
                    'laboratory_base_fee'          => ['label' => 'Laboratory Base Fee (₱)',      'type' => 'number'],
                    'certification_fee'            => ['label' => 'Certification Fee (₱)',        'type' => 'number'],
                ];
                foreach ($editable as $key => $cfg):
                    $val = $settings[$key]['setting_value'] ?? '';
                ?>
                <div class="form-group">
                    <label><?= $cfg['label'] ?></label>
                    <input type="<?= $cfg['type'] ?>" name="<?= $key ?>" class="form-control"
                           value="<?= htmlspecialchars($val) ?>" <?= $cfg['type']==='number' ? 'min="0" step="0.01"' : '' ?>>
                    <?php if (!empty($settings[$key]['description'])): ?>
                    <div class="form-text"><?= htmlspecialchars($settings[$key]['description']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
            </form>
        </div>
    </div>

    <!-- Security summary -->
    <div class="admin-card" style="height:fit-content">
        <div class="admin-card-header">
            <h3><i class="fas fa-shield-alt" style="color:#6366f1;margin-right:8px"></i>Security Summary</h3>
        </div>
        <div class="admin-card-body" style="padding:16px">
            <?php
            $checks = [
                ['label' => 'Password Policy Enforced',    'ok' => true],
                ['label' => 'bcrypt Password Hashing',     'ok' => true],
                ['label' => 'Session Timeout Active',      'ok' => true],
                ['label' => 'Login Attempt Logging',       'ok' => true],
                ['label' => 'Admin Activity Logging',      'ok' => true],
                ['label' => 'DLP Screenshot Block',        'ok' => true],
                ['label' => 'RBAC Role Enforcement',       'ok' => true],
                ['label' => 'AES-256 Field Encryption',    'ok' => true],
                ['label' => 'Data Classification Labels',  'ok' => true],
                ['label' => 'HTTPS (configure in Apache)', 'ok' => false],
            ];
            foreach ($checks as $c):
            ?>
            <div style="display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid #f8fafc">
                <i class="fas fa-<?= $c['ok'] ? 'check-circle' : 'exclamation-circle' ?>"
                   style="color:<?= $c['ok'] ? '#22c55e' : '#f59e0b' ?>;font-size:.85rem"></i>
                <span style="font-size:.82rem;color:#1e293b"><?= $c['label'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
