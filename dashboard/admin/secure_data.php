<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_ADMIN]);

$page_title = 'Secure Data Storage';
$page_heading = 'Secure Data Management';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Admin Dashboard', 'url' => BASE_URL . 'dashboard/admin/'], ['label' => 'Secure Data']];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3><i class="fas fa-lock" style="color: var(--primary-600); margin-right: 8px;"></i> Security Policies Status</h3>
    </div>
    <div class="card-body">
        <p style="color: var(--neutral-600); margin-bottom: 24px;">View the active security configurations protecting system data.</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
            
            <!-- Password Hashing -->
            <div style="border: 1px solid var(--neutral-200); border-radius: 12px; padding: 20px; position: relative; background: #f8fafc;">
                <div style="position: absolute; top: 20px; right: 20px;">
                    <span class="badge badge-success"><i class="fas fa-check-circle" style="margin-right:4px;"></i> Active</span>
                </div>
                <div style="font-size: 1.8rem; color: var(--primary-500); margin-bottom: 12px;"><i class="fas fa-key"></i></div>
                <h4 style="margin: 0 0 8px 0; font-size: 1.05rem; color: var(--neutral-800);">Password Hashing</h4>
                <p style="font-size: 0.85rem; color: var(--neutral-600); margin: 0;">All user passwords are automatically hashed using PHP's native <strong>PASSWORD_DEFAULT (Bcrypt)</strong> algorithm with a high cost factor before being stored in the database.</p>
            </div>

            <!-- Database Field Encryption -->
            <div style="border: 1px solid var(--neutral-200); border-radius: 12px; padding: 20px; position: relative; background: #f8fafc;">
                <div style="position: absolute; top: 20px; right: 20px;">
                    <span class="badge badge-success"><i class="fas fa-check-circle" style="margin-right:4px;"></i> Active</span>
                </div>
                <div style="font-size: 1.8rem; color: var(--primary-500); margin-bottom: 12px;"><i class="fas fa-database"></i></div>
                <h4 style="margin: 0 0 8px 0; font-size: 1.05rem; color: var(--neutral-800);">DB Field Encryption</h4>
                <p style="font-size: 0.85rem; color: var(--neutral-600); margin: 0;">Sensitive PII (Personally Identifiable Information) and application metadata are encrypted at rest using AES-256-CBC encryption.</p>
            </div>

            <!-- Encrypted Local Storage -->
            <div style="border: 1px solid var(--neutral-200); border-radius: 12px; padding: 20px; position: relative; background: #f8fafc;">
                <div style="position: absolute; top: 20px; right: 20px;">
                    <span class="badge badge-warning"><i class="fas fa-exclamation-triangle" style="margin-right:4px;"></i> Optional</span>
                </div>
                <div style="font-size: 1.8rem; color: var(--primary-500); margin-bottom: 12px;"><i class="fas fa-hdd"></i></div>
                <h4 style="margin: 0 0 8px 0; font-size: 1.05rem; color: var(--neutral-800);">Encrypted Local Storage</h4>
                <p style="font-size: 0.85rem; color: var(--neutral-600); margin: 0;">Uploaded documents (PDFs, Images) are stored securely in the file system. Enabling this feature will encrypt the files before saving them to disk.</p>
                <button class="btn btn-sm btn-outline" style="margin-top: 12px; font-size: 0.75rem;">Enable File Encryption</button>
            </div>

        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-shield-alt" style="color: var(--primary-600); margin-right: 8px;"></i> Key Management</h3>
    </div>
    <div class="card-body">
        <p style="font-size: 0.9rem; color: var(--neutral-600);">The master encryption key is managed by the server environment variables. It is crucial to never expose this key, as it is required to decrypt database fields and local files.</p>
        
        <table class="table" style="font-size: 0.85rem; margin-top: 16px;">
            <tbody>
                <tr>
                    <td style="font-weight: 700; width: 200px;">Current Encryption Algo:</td>
                    <td><span class="badge badge-light" style="font-family: monospace;">AES-256-CBC</span></td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Environment Key Status:</td>
                    <td><span class="badge badge-success"><i class="fas fa-check"></i> Loaded</span></td>
                </tr>
                <tr>
                    <td style="font-weight: 700;">Last Key Rotation:</td>
                    <td style="color: var(--neutral-600);">Never (Initial setup)</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
