<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/admin_functions.php';
requireAdmin();
enforceSessionTimeout();

$admin_page_title = 'Data Classification';
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'update') {
        $id    = intval($_POST['id']);
        $level = sanitize($_POST['classification']);
        $desc  = sanitize($_POST['description'] ?? '');
        $valid = ['public','internal','confidential','restricted'];
        if (!in_array($level, $valid)) {
            $error = 'Invalid classification level.';
        } else {
            $stmt = $conn->prepare("UPDATE data_classifications SET classification=?, description=?, updated_by=? WHERE id=?");
            $stmt->bind_param("ssii", $level, $desc, $_SESSION['user_id'], $id);
            $stmt->execute();
            logAdminActivity($conn, $_SESSION['user_id'], 'Updated Data Classification', 'data_classification', $id,
                "Set to $level");
            $success = 'Classification updated.';
        }
    } elseif ($action === 'add') {
        $table  = sanitize($_POST['table_name']);
        $col    = sanitize($_POST['column_name']);
        $level  = sanitize($_POST['classification']);
        $desc   = sanitize($_POST['description'] ?? '');
        $stmt   = $conn->prepare(
            "INSERT INTO data_classifications (table_name, column_name, classification, description, updated_by)
             VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE classification=VALUES(classification), description=VALUES(description)"
        );
        $stmt->bind_param("ssssi", $table, $col, $level, $desc, $_SESSION['user_id']);
        $stmt->execute();
        logAdminActivity($conn, $_SESSION['user_id'], 'Added Data Classification', 'data_classification', null,
            "$table.$col → $level");
        $success = 'Classification added.';
    }
}

$classifications = getDataClassifications($conn);

// Group by table
$by_table = [];
foreach ($classifications as $c) {
    $by_table[$c['table_name']][] = $c;
}

require_once __DIR__ . '/includes/admin_header.php';
?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= $error ?></span></div>
<?php endif; ?>

<!-- Legend -->
<div class="admin-card" style="margin-bottom:20px">
    <div class="admin-card-body" style="padding:16px 20px">
        <div style="display:flex;gap:16px;flex-wrap:wrap;align-items:center">
            <span style="font-size:.82rem;font-weight:600;color:#1e293b">Classification Levels:</span>
            <?= getClassificationBadge('public') ?>
            <span style="font-size:.75rem;color:#64748b">— Freely shareable</span>
            <?= getClassificationBadge('internal') ?>
            <span style="font-size:.75rem;color:#64748b">— Internal use only</span>
            <?= getClassificationBadge('confidential') ?>
            <span style="font-size:.75rem;color:#64748b">— Restricted to authorized roles</span>
            <?= getClassificationBadge('restricted') ?>
            <span style="font-size:.75rem;color:#64748b">— Highest sensitivity, encrypted</span>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:22px">

    <!-- Classifications by table -->
    <div>
        <?php foreach ($by_table as $table => $cols): ?>
        <div class="admin-card" style="margin-bottom:18px">
            <div class="admin-card-header">
                <h3><i class="fas fa-table" style="color:#6366f1;margin-right:8px"></i><?= htmlspecialchars($table) ?></h3>
                <span style="font-size:.75rem;color:#94a3b8"><?= count($cols) ?> columns</span>
            </div>
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead><tr><th>Column</th><th>Classification</th><th>Description</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($cols as $c): ?>
                    <tr>
                        <td style="font-family:monospace;font-size:.82rem;font-weight:600"><?= htmlspecialchars($c['column_name']) ?></td>
                        <td><?= getClassificationBadge($c['classification']) ?></td>
                        <td style="font-size:.78rem;color:#64748b"><?= htmlspecialchars($c['description'] ?? '') ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline"
                                    onclick="openEditModal(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['table_name'])) ?>', '<?= htmlspecialchars(addslashes($c['column_name'])) ?>', '<?= $c['classification'] ?>', '<?= htmlspecialchars(addslashes($c['description'] ?? '')) ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Add new classification -->
    <div class="admin-card" style="height:fit-content">
        <div class="admin-card-header">
            <h3><i class="fas fa-plus" style="color:#6366f1;margin-right:8px"></i>Add Classification</h3>
        </div>
        <div class="admin-card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Table Name <span class="required">*</span></label>
                    <input type="text" name="table_name" class="form-control" placeholder="e.g. users" required>
                </div>
                <div class="form-group">
                    <label>Column Name <span class="required">*</span></label>
                    <input type="text" name="column_name" class="form-control" placeholder="e.g. phone" required>
                </div>
                <div class="form-group">
                    <label>Classification <span class="required">*</span></label>
                    <select name="classification" class="form-control" required>
                        <option value="public">Public</option>
                        <option value="internal" selected>Internal</option>
                        <option value="confidential">Confidential</option>
                        <option value="restricted">Restricted</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Brief description…"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-plus"></i> Add Classification
                </button>
            </form>
        </div>
    </div>

</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <h3>Edit Classification</h3>
            <button class="modal-close" onclick="document.getElementById('editModal').classList.remove('active')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                <p style="font-size:.82rem;color:#64748b;margin-bottom:14px">
                    <strong id="editTableCol"></strong>
                </p>
                <div class="form-group">
                    <label>Classification</label>
                    <select name="classification" id="editLevel" class="form-control">
                        <option value="public">Public</option>
                        <option value="internal">Internal</option>
                        <option value="confidential">Confidential</option>
                        <option value="restricted">Restricted</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="editDesc" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(id, table, col, level, desc) {
    document.getElementById('editId').value      = id;
    document.getElementById('editTableCol').textContent = table + '.' + col;
    document.getElementById('editLevel').value   = level;
    document.getElementById('editDesc').value    = desc;
    document.getElementById('editModal').classList.add('active');
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
