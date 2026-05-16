<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/admin_functions.php';
requireAdmin();
enforceSessionTimeout();

$admin_page_title = 'Encrypted Fields';
logAdminActivity($conn, $_SESSION['user_id'], 'Viewed Encrypted Fields', 'admin');

// Demo: show which fields are encrypted and test the encryption functions
$test_result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_value'])) {
    $plain     = sanitize($_POST['test_value']);
    $encrypted = encryptField($plain);
    $decrypted = decryptField($encrypted);
    $test_result = [
        'plain'     => $plain,
        'encrypted' => $encrypted,
        'decrypted' => $decrypted,
        'match'     => $plain === $decrypted,
    ];
    logAdminActivity($conn, $_SESSION['user_id'], 'Tested Field Encryption', 'security');
}

// Fields that should be encrypted (restricted classification)
$restricted = $conn->query(
    "SELECT * FROM data_classifications WHERE classification='restricted' ORDER BY table_name, column_name"
)->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/includes/admin_header.php';
?>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:22px">

    <!-- Restricted fields -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3><i class="fas fa-database" style="color:#6366f1;margin-right:8px"></i>Restricted / Encrypted Fields</h3>
        </div>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead><tr><th>Table</th><th>Column</th><th>Classification</th><th>Description</th><th>Storage</th></tr></thead>
                <tbody>
                <?php foreach ($restricted as $f): ?>
                <tr>
                    <td style="font-family:monospace;font-size:.82rem;font-weight:600"><?= htmlspecialchars($f['table_name']) ?></td>
                    <td style="font-family:monospace;font-size:.82rem"><?= htmlspecialchars($f['column_name']) ?></td>
                    <td><?= getClassificationBadge($f['classification']) ?></td>
                    <td style="font-size:.78rem;color:#64748b"><?= htmlspecialchars($f['description'] ?? '') ?></td>
                    <td>
                        <?php if ($f['column_name'] === 'password'): ?>
                            <span style="background:#dcfce7;color:#166534;padding:3px 8px;border-radius:6px;font-size:.72rem;font-weight:700">
                                <i class="fas fa-lock"></i> bcrypt hashed
                            </span>
                        <?php else: ?>
                            <span style="background:#fef9c3;color:#854d0e;padding:3px 8px;border-radius:6px;font-size:.72rem;font-weight:700">
                                <i class="fas fa-shield-alt"></i> AES-256-CBC
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($restricted)): ?>
                <tr><td colspan="5" style="text-align:center;padding:20px;color:#94a3b8">No restricted fields defined</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Encryption test tool -->
    <div>
        <div class="admin-card" style="margin-bottom:18px">
            <div class="admin-card-header">
                <h3><i class="fas fa-flask" style="color:#6366f1;margin-right:8px"></i>Encryption Test Tool</h3>
            </div>
            <div class="admin-card-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Test Value</label>
                        <input type="text" name="test_value" class="form-control" placeholder="Enter a value to encrypt…" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-lock"></i> Test Encryption
                    </button>
                </form>

                <?php if ($test_result): ?>
                <div style="margin-top:16px;background:#f8fafc;border-radius:10px;padding:14px">
                    <div style="margin-bottom:10px">
                        <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:3px">Original</div>
                        <code style="font-size:.82rem;color:#1e293b"><?= htmlspecialchars($test_result['plain']) ?></code>
                    </div>
                    <div style="margin-bottom:10px">
                        <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:3px">Encrypted (AES-256-CBC)</div>
                        <code style="font-size:.72rem;color:#6366f1;word-break:break-all"><?= htmlspecialchars(substr($test_result['encrypted'],0,60)) ?>…</code>
                    </div>
                    <div style="margin-bottom:10px">
                        <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:3px">Decrypted</div>
                        <code style="font-size:.82rem;color:#1e293b"><?= htmlspecialchars($test_result['decrypted']) ?></code>
                    </div>
                    <div style="margin-top:8px">
                        <?php if ($test_result['match']): ?>
                        <span class="badge badge-success"><i class="fas fa-check"></i> Round-trip successful</span>
                        <?php else: ?>
                        <span class="badge badge-danger"><i class="fas fa-times"></i> Mismatch — check key</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Password hashing info -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3><i class="fas fa-key" style="color:#6366f1;margin-right:8px"></i>Password Storage</h3>
            </div>
            <div class="admin-card-body" style="padding:16px">
                <div style="display:flex;flex-direction:column;gap:10px">
                    <div style="background:#dcfce7;border-radius:8px;padding:12px">
                        <div style="font-weight:700;font-size:.82rem;color:#166534"><i class="fas fa-check-circle" style="margin-right:6px"></i>Algorithm: bcrypt (PASSWORD_DEFAULT)</div>
                        <div style="font-size:.75rem;color:#16a34a;margin-top:4px">Cost factor: 10 (PHP default)</div>
                    </div>
                    <div style="background:#dbeafe;border-radius:8px;padding:12px">
                        <div style="font-weight:700;font-size:.82rem;color:#1e40af"><i class="fas fa-info-circle" style="margin-right:6px"></i>Verification: password_verify()</div>
                        <div style="font-size:.75rem;color:#2563eb;margin-top:4px">Timing-safe comparison, no plain-text storage</div>
                    </div>
                    <div style="background:#fef9c3;border-radius:8px;padding:12px">
                        <div style="font-weight:700;font-size:.82rem;color:#854d0e"><i class="fas fa-exclamation-triangle" style="margin-right:6px"></i>Sensitive Fields</div>
                        <div style="font-size:.75rem;color:#92400e;margin-top:4px">Use encryptField() / decryptField() for PII fields before DB storage</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
