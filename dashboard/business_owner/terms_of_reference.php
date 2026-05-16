<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$page_title = 'Terms of Reference';
$page_heading = 'Terms of Reference';
$is_dashboard = true;
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/business_owner/'],
    ['label' => 'Terms of Reference'],
];
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle TOR response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tor_response'])) {
    $tor_id         = intval($_POST['tor_id']);
    $response       = sanitize($_POST['response_action']);
    $remarks        = sanitize($_POST['business_remarks'] ?? '');

    if (!in_array($response, ['accepted', 'rejected'])) {
        $error = 'Invalid response.';
    } elseif ($response === 'rejected' && empty(trim($remarks))) {
        $error = 'You must provide a remark when rejecting the Terms of Reference.';
    } else {
        $upd = $conn->prepare("UPDATE terms_of_reference SET business_response=?, business_remarks=?, responded_at=NOW() WHERE id=? AND application_id IN (SELECT id FROM hdp_applications WHERE business_owner_id=?)");
        $upd->bind_param("ssii", $response, $remarks, $tor_id, $user_id);
        if ($upd->execute()) {
            // Notify evaluator
            $eval_q = $conn->prepare("SELECT evaluator_id FROM terms_of_reference WHERE id=?");
            $eval_q->bind_param("i", $tor_id);
            $eval_q->execute();
            $eval_row = $eval_q->get_result()->fetch_assoc();
            if ($eval_row) {
                $msg = $response === 'accepted'
                    ? $_SESSION['full_name'] . ' has accepted the Terms of Reference.'
                    : $_SESSION['full_name'] . ' has rejected the Terms of Reference and left remarks.';
                createNotification($conn, $eval_row['evaluator_id'],
                    'TOR Response: ' . ucfirst($response), $msg,
                    $response === 'accepted' ? 'success' : 'warning',
                    BASE_URL . 'dashboard/evaluator/terms_of_reference.php?action=view&id=' . $tor_id);
            }
            $success = 'Your response has been submitted.';
        } else {
            $error = 'Failed to save response.';
        }
    }
}

// Get the business owner's application and TOR
$app_stmt = $conn->prepare("
    SELECT ha.*, loi.company_name
    FROM hdp_applications ha
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    WHERE ha.business_owner_id = ?
    ORDER BY ha.created_at DESC LIMIT 1
");
$app_stmt->bind_param("i", $user_id);
$app_stmt->execute();
$application = $app_stmt->get_result()->fetch_assoc();

$tor = null;
if ($application) {
    $tor_q = $conn->prepare("SELECT * FROM terms_of_reference WHERE application_id = ? ORDER BY created_at DESC LIMIT 1");
    $tor_q->bind_param("i", $application['id']);
    $tor_q->execute();
    $tor = $tor_q->get_result()->fetch_assoc();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= $error ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<?php if (!$tor): ?>
<div class="empty-state">
    <div class="empty-icon"><i class="fas fa-file-contract"></i></div>
    <h3>No Terms of Reference Yet</h3>
    <p>The evaluator has not sent your Terms of Reference yet. Please check back after your application has been reviewed.</p>
</div>

<?php else:
    $resp  = $tor['business_response'] ?? 'pending';
    $badge = ['pending' => 'badge-warning', 'accepted' => 'badge-success', 'rejected' => 'badge-danger'];
    $ext   = !empty($tor['tor_file_path']) ? strtolower(pathinfo($tor['tor_file_path'], PATHINFO_EXTENSION)) : '';
    $file_url = !empty($tor['tor_file_path']) ? BASE_URL . 'uploads/' . htmlspecialchars($tor['tor_file_path']) : '';
    $auditor_assignments = json_decode($tor['auditor_assignments'] ?? '[]', true);
    if (!is_array($auditor_assignments)) $auditor_assignments = [];
?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    <!-- TOR Content / File Preview -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-file-contract" style="color:var(--primary-600);margin-right:8px;"></i>
                Terms of Reference — <?= htmlspecialchars($application['company_name']) ?>
            </h3>
            <span class="badge <?= $badge[$resp] ?>">Your response: <?= ucfirst($resp) ?></span>
        </div>

        <?php if ($file_url): ?>
            <?php if ($ext === 'pdf'): ?>
                <iframe src="<?= $file_url ?>" style="width:100%;height:650px;border:none;display:block;"></iframe>
            <?php elseif (in_array($ext, ['jpg','jpeg','png'])): ?>
                <div style="padding:24px;text-align:center;">
                    <img src="<?= $file_url ?>" alt="TOR Document"
                         style="max-width:100%;height:auto;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.1);">
                </div>
            <?php else: ?>
                <div style="padding:48px;text-align:center;color:var(--neutral-500);">
                    <i class="fas fa-file-alt" style="font-size:3rem;margin-bottom:12px;display:block;"></i>
                    <p>Preview not available for this file type.</p>
                    <a href="<?= $file_url ?>" target="_blank" class="btn btn-outline" style="margin-top:8px;">
                        <i class="fas fa-external-link-alt"></i> Open File
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="padding:32px 40px;white-space:pre-wrap;font-family:'Times New Roman',serif;font-size:0.95rem;line-height:1.8;color:#111;max-height:650px;overflow-y:auto;">
                <?= htmlspecialchars($tor['tor_content']) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Response Panel -->
    <div class="card" style="position:sticky;top:20px;">
        <div class="card-header">
            <h3><i class="fas fa-reply" style="color:var(--primary-600);margin-right:8px;"></i> Your Response</h3>
        </div>
        <div class="card-body">

            <!-- Details -->
            <div style="margin-bottom:14px;">
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Company</div>
                <div style="font-weight:600;"><?= htmlspecialchars($application['company_name']) ?></div>
            </div>
            <div style="margin-bottom:14px;">
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Inspection Fee</div>
                <div style="font-weight:700;font-size:1.05rem;"><?= formatCurrency($tor['inspection_fees']) ?></div>
            </div>
            <?php if (!empty($tor['enterprise_type'])): ?>
            <div style="margin-bottom:14px;">
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Enterprise Standard</div>
                <div style="font-size:0.9rem;"><?= htmlspecialchars(getEnterpriseStandardSummary($tor['enterprise_type'])) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($auditor_assignments)): ?>
            <div style="margin-bottom:14px;">
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Auditors</div>
                <?php foreach ($auditor_assignments as $assignment): ?>
                <div style="font-size:0.86rem;color:var(--neutral-700);margin-bottom:4px;">
                    <?= ucfirst(htmlspecialchars($assignment['role'] ?? 'auditor')) ?>:
                    <?= htmlspecialchars($assignment['auditor_name'] ?: 'To be assigned') ?>
                    <span style="color:var(--neutral-500);">(<?= formatCurrency($assignment['amount'] ?? 0) ?>)</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div style="margin-bottom:20px;">
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Sent On</div>
                <div><?= formatDate($tor['created_at']) ?></div>
            </div>

            <?php if ($file_url): ?>
            <div style="margin-bottom:20px;">
                <a href="<?= $file_url ?>" target="_blank" download
                   class="btn btn-sm btn-outline" style="width:100%;text-align:center;">
                    <i class="fas fa-file-download"></i> Download TOR File
                </a>
            </div>
            <?php endif; ?>

            <hr style="margin:16px 0;border-color:var(--neutral-100);">

            <?php if ($resp === 'pending'): ?>
            <p style="font-size:0.85rem;color:var(--neutral-500);margin-bottom:14px;">
                Please review the Terms of Reference and respond below.
            </p>
            <form method="POST">
                <input type="hidden" name="tor_response" value="1">
                <input type="hidden" name="tor_id" value="<?= $tor['id'] ?>">
                <div class="form-group">
                    <label>Remarks <small style="color:var(--neutral-400);">(required if rejecting)</small></label>
                    <textarea name="business_remarks" class="form-control" rows="4"
                        placeholder="If you disagree or want to negotiate any terms, explain here..." required></textarea>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;margin-top:8px;">
                    <button type="submit" name="response_action" value="accepted" class="btn btn-success"
                        onclick="document.querySelector('[name=business_remarks]').removeAttribute('required'); return confirm('Confirm you agree to the Terms of Reference?')">
                        <i class="fas fa-check-circle"></i> I Agree / Accept
                    </button>
                    <button type="submit" name="response_action" value="rejected" class="btn btn-danger">
                        <i class="fas fa-times-circle"></i> I Disagree / Send Remarks
                    </button>
                </div>
            </form>

            <?php elseif ($resp === 'accepted'): ?>
            <div class="alert alert-success" style="margin:0;">
                <i class="fas fa-check-circle"></i>
                <span>You accepted the Terms of Reference on <?= formatDate($tor['responded_at']) ?>.</span>
            </div>

            <?php else: ?>
            <div class="alert alert-warning" style="margin:0 0 12px;">
                <i class="fas fa-exclamation-triangle"></i>
                <span>You submitted remarks on <?= formatDate($tor['responded_at']) ?>. Awaiting evaluator response.</span>
            </div>
            <?php if ($tor['business_remarks']): ?>
            <div style="background:var(--neutral-50);padding:12px;border-radius:8px;font-size:0.85rem;color:var(--neutral-700);">
                <strong>Your remarks:</strong><br><?= nl2br(htmlspecialchars($tor['business_remarks'])) ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>

</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
