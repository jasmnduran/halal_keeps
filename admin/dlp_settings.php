<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/admin_functions.php';
requireAdmin();
enforceSessionTimeout();

$admin_page_title = 'DLP Settings';
$success = '';

// DLP settings stored in system_settings
$dlp_keys = [
    'dlp_session_timeout'       => ['label' => 'Session Timeout (seconds)',    'default' => '1800', 'type' => 'number'],
    'dlp_max_login_attempts'    => ['label' => 'Max Failed Login Attempts',    'default' => '5',    'type' => 'number'],
    'dlp_lockout_duration'      => ['label' => 'Account Lockout Duration (s)', 'default' => '900',  'type' => 'number'],
    'dlp_screenshot_block'      => ['label' => 'Block Screenshot (PrintScreen)','default' => '1',   'type' => 'toggle'],
    'dlp_print_block'           => ['label' => 'Block Print (Ctrl+P)',         'default' => '1',    'type' => 'toggle'],
    'dlp_copy_warn_threshold'   => ['label' => 'Copy Warning Threshold (chars)','default' => '50',  'type' => 'number'],
    'dlp_export_restrict'       => ['label' => 'Restrict Data Export',         'default' => '1',    'type' => 'toggle'],
    'dlp_watermark_enabled'     => ['label' => 'Enable Print Watermark',       'default' => '1',    'type' => 'toggle'],
    'dlp_right_click_block'     => ['label' => 'Block Right-Click on Sensitive Tables', 'default' => '1', 'type' => 'toggle'],
    'dlp_session_warning_secs'  => ['label' => 'Session Warning Before Expiry (s)', 'default' => '60', 'type' => 'number'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($dlp_keys as $key => $cfg) {
        $val = sanitize($_POST[$key] ?? ($cfg['type'] === 'toggle' ? '0' : $cfg['default']));
        $stmt = $conn->prepare(
            "INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
             VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)"
        );
        $desc = $cfg['label'];
        $type = $cfg['type'] === 'toggle' ? 'boolean' : 'number';
        $stmt->bind_param("ssss", $key, $val, $type, $desc);
        $stmt->execute();
    }
    logAdminActivity($conn, $_SESSION['user_id'], 'Updated DLP Settings', 'dlp');
    $success = 'DLP settings saved successfully.';
}

// Load current values
$current = [];
$res = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'dlp_%'");
while ($row = $res->fetch_assoc()) {
    $current[$row['setting_key']] = $row['setting_value'];
}
foreach ($dlp_keys as $key => $cfg) {
    if (!isset($current[$key])) $current[$key] = $cfg['default'];
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:22px">

    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-lock" style="color:#6366f1;margin-right:8px"></i>Data Loss Prevention Settings</h3>
        </div>
        <div class="admin-card-body">
            <form method="POST">
                <?php foreach ($dlp_keys as $key => $cfg): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #f1f5f9">
                    <div>
                        <div style="font-weight:600;font-size:.88rem;color:#1e293b"><?= htmlspecialchars($cfg['label']) ?></div>
                        <div style="font-size:.75rem;color:#94a3b8;margin-top:2px">Key: <code><?= $key ?></code></div>
                    </div>
                    <?php if ($cfg['type'] === 'toggle'): ?>
                    <label class="toggle-switch">
                        <input type="checkbox" name="<?= $key ?>" value="1" <?= $current[$key] ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <?php else: ?>
                    <input type="number" name="<?= $key ?>" value="<?= htmlspecialchars($current[$key]) ?>"
                           class="form-control" style="max-width:120px;text-align:center" min="0">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <div style="margin-top:20px">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save DLP Settings
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DLP Info Panel -->
    <div>
        <div class="admin-card" style="margin-bottom:18px">
            <div class="admin-card-header">
                <h3><i class="fas fa-info-circle" style="color:#6366f1;margin-right:8px"></i>Active DLP Controls</h3>
            </div>
            <div class="admin-card-body" style="padding:16px">
                <?php
                $controls = [
                    ['icon' => 'fa-clock',        'label' => 'Session Timeout',      'active' => true],
                    ['icon' => 'fa-camera-slash',  'label' => 'Screenshot Blocking',  'active' => (bool)$current['dlp_screenshot_block']],
                    ['icon' => 'fa-print',         'label' => 'Print Restriction',    'active' => (bool)$current['dlp_print_block']],
                    ['icon' => 'fa-copy',          'label' => 'Copy Monitoring',      'active' => true],
                    ['icon' => 'fa-mouse-pointer', 'label' => 'Right-Click Block',    'active' => (bool)$current['dlp_right_click_block']],
                    ['icon' => 'fa-file-export',   'label' => 'Export Restriction',   'active' => (bool)$current['dlp_export_restrict']],
                    ['icon' => 'fa-tint',          'label' => 'Print Watermark',      'active' => (bool)$current['dlp_watermark_enabled']],
                    ['icon' => 'fa-user-lock',     'label' => 'Account Lockout',      'active' => true],
                    ['icon' => 'fa-history',       'label' => 'Full Audit Logging',   'active' => true],
                    ['icon' => 'fa-mask',          'label' => 'Data Masking (UI)',     'active' => true],
                ];
                foreach ($controls as $c):
                ?>
                <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f8fafc">
                    <div style="width:28px;height:28px;border-radius:6px;background:<?= $c['active'] ? '#dcfce7' : '#fee2e2' ?>;color:<?= $c['active'] ? '#16a34a' : '#dc2626' ?>;display:flex;align-items:center;justify-content:center;font-size:.75rem;flex-shrink:0">
                        <i class="fas <?= $c['icon'] ?>"></i>
                    </div>
                    <span style="font-size:.82rem;font-weight:500;color:#1e293b"><?= $c['label'] ?></span>
                    <span style="margin-left:auto;font-size:.7rem;font-weight:700;color:<?= $c['active'] ? '#16a34a' : '#dc2626' ?>">
                        <?= $c['active'] ? 'ON' : 'OFF' ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-chart-bar" style="color:#6366f1;margin-right:8px"></i>DLP Events (Today)</h3>
            </div>
            <div class="admin-card-body" style="padding:16px">
                <?php
                $dlp_events = $conn->query(
                    "SELECT action, COUNT(*) as c FROM admin_activity_log
                     WHERE action LIKE 'DLP Event%' AND DATE(created_at)=CURDATE()
                     GROUP BY action ORDER BY c DESC LIMIT 5"
                )->fetch_all(MYSQLI_ASSOC);
                ?>
                <?php if (empty($dlp_events)): ?>
                <p style="text-align:center;color:#94a3b8;font-size:.82rem;padding:10px 0">No DLP events today</p>
                <?php else: ?>
                <?php foreach ($dlp_events as $e): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f8fafc">
                    <span style="font-size:.8rem;color:#1e293b"><?= htmlspecialchars(str_replace('DLP Event: ','',$e['action'])) ?></span>
                    <span style="font-weight:700;color:#6366f1;font-size:.85rem"><?= $e['c'] ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
