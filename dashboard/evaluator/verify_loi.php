<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_EVALUATOR]);

$page_title = 'Verify Letters of Intent';
$page_heading = 'Verify Letters of Intent';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/evaluator/'], ['label' => 'Verify LOI']];
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$error = ''; $success = '';

// Handle verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loi_id = intval($_POST['loi_id']);
    $verification_status = sanitize($_POST['verification_status']);
    $feedback = sanitize($_POST['feedback'] ?? '');

    if ($loi_id <= 0 || !in_array($verification_status, ['verified', 'returned'])) {
        $error = 'Invalid submission.';
    } elseif ($verification_status === 'returned' && empty($feedback)) {
        $error = 'Remarks are required when rejecting a Letter of Intent.';
    } else {
        $stmt = $conn->prepare("INSERT INTO loi_verification (loi_id, evaluator_id, verification_status, feedback, verified_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE verification_status=VALUES(verification_status), feedback=VALUES(feedback), verified_at=NOW()");
        $stmt->bind_param("iiss", $loi_id, $user_id, $verification_status, $feedback);

        if ($stmt->execute()) {
            $new_status = $verification_status === 'verified' ? 'verified' : 'returned';
            $update = $conn->prepare("UPDATE letter_of_intent SET status = ?, remarks = ? WHERE id = ?");
            $update->bind_param("ssi", $new_status, $feedback, $loi_id);
            $update->execute();

            $owner_stmt = $conn->prepare("SELECT business_owner_id FROM letter_of_intent WHERE id = ?");
            $owner_stmt->bind_param("i", $loi_id);
            $owner_stmt->execute();
            $owner = $owner_stmt->get_result()->fetch_assoc();

            if ($owner && $owner['business_owner_id']) {
                if ($verification_status === 'verified') {
                    // Auto-save requirements on approval
                    $default_req = "1. Administrative & Legal Documentation\n1.1 Letter of Intent\n1.2 Company Profile\n1.3 SEC Registration / DTI License / Mayor's or Business Permit\n1.4 Barangay Permit and Sanitary Permit\n1.5 Fire Clearance Certificate and DENR Environment Certificate\n1.6 FDA License to Operate (LTO) and Certificate of Product Registration (CPR) for all products\n1.7 Previous Halal Certificate from HDIP (if applicable/optional)\n2. Technical & Quality Management\n2.1 GMP, HACCP, SSOP, GHP, TQM, ISO Certificates (if available/optional)\n2.2 Halal Assurance System (HAS) Manual\n2.3 Waste Disposal Management Plan\n2.4 Pest Control Program\n2.5 Kitchen Layout\n2.6 Flow Chart of Product Processing\n3. Product & Raw Material Specifications\n3.1 Full List of Products / Menu with Corresponding Ingredients\n3.2 Raw Materials / Ingredients Matrix with Sources (Local or Imported)\n3.3 Packaging Materials List with Corresponding Halal Certificates\n4. Halal Compliance & Logistics\n4.1 Halal Certificates for All Raw Materials (especially meat products)\n4.2 Appointment of at Least 2 Muslim Cooks and 2 Muslim Crew Members\n4.3 Proof of Dedicated Halal Prayer Room\n4.4 Alcohol and Liquor Prohibition Compliance in the Kitchen\n4.5 Warehouse Halal Certificate and Storage System Description\n4.6 Transportation Details for Halal Products";
                    $req_stmt = $conn->prepare("INSERT INTO loi_requirements (loi_id, evaluator_id, requirements) VALUES (?, ?, ?)");
                    $req_stmt->bind_param("iis", $loi_id, $user_id, $default_req);
                    $req_stmt->execute();

                    createNotification($conn, $owner['business_owner_id'],
                        'LOI Approved — Requirements Sent',
                        'Your Letter of Intent has been approved. The list of requirements you need to submit has been sent. Please review and proceed with your application.',
                        'success',
                        BASE_URL . 'dashboard/business_owner/letter_of_intent.php?action=view&id=' . $loi_id);
                } else {
                    createNotification($conn, $owner['business_owner_id'],
                        'LOI Returned',
                        'Your Letter of Intent has been returned. Please check the feedback and resubmit.',
                        'warning',
                        BASE_URL . 'dashboard/business_owner/letter_of_intent.php?action=view&id=' . $loi_id);
                }
            }

            logActivity($conn, $user_id, 'LOI Verified', 'LOI #' . $loi_id . ' ' . $verification_status, 'evaluation');
            $success = 'Letter of Intent has been ' . $verification_status . '.';
            $action = 'list';
        } else {
            $error = 'Failed to save verification: ' . $conn->error;
        }
    }
}

// Get LOIs
$filter = $_GET['filter'] ?? 'submitted';
$where = $filter === 'all' ? '' : "WHERE loi.status = '$filter'";
$lois = $conn->query("
    SELECT loi.*, u.full_name as owner_name, u.email as owner_email,
        (SELECT COUNT(*) FROM loi_verification lv WHERE lv.loi_id = loi.id AND lv.verification_status = 'returned') as was_returned
    FROM letter_of_intent loi
    JOIN users u ON loi.business_owner_id = u.id
    $where
    ORDER BY loi.submitted_at DESC
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="display: flex; gap: 8px;">
        <a href="?filter=submitted" class="btn btn-sm <?= $filter === 'submitted' ? 'btn-primary' : 'btn-secondary' ?>">Pending</a>
        <a href="?filter=verified" class="btn btn-sm <?= $filter === 'verified' ? 'btn-primary' : 'btn-secondary' ?>">Verified</a>
        <a href="?filter=returned" class="btn btn-sm <?= $filter === 'returned' ? 'btn-primary' : 'btn-secondary' ?>">Returned</a>
        <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Letters of Intent</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($lois)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-check-circle"></i></div><h3>No LOIs Found</h3><p>No letters of intent matching this filter.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Company</th><th>Submitted By</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($lois as $loi): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($loi['company_name']) ?></strong><br><small style="color:var(--neutral-500)"><?= htmlspecialchars(substr($loi['company_address'] ?? '', 0, 40)) ?></small></td>
                            <td><?= htmlspecialchars($loi['owner_name']) ?><br><small style="color:var(--neutral-500)"><?= htmlspecialchars($loi['owner_email']) ?></small></td>
                            <td>
                                <?= getStatusBadge($loi['status']) ?>
                                <?php if ($loi['status'] === 'submitted' && $loi['was_returned'] > 0): ?>
                                    <span class="badge badge-info" style="margin-left:4px;font-size:0.72rem;">
                                        <i class="fas fa-redo"></i> Revised
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.85rem;color:var(--neutral-500)"><?= $loi['submitted_at'] ? formatDate($loi['submitted_at']) : '-' ?></td>
                            <td><a href="?action=review&id=<?= $loi['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> Review</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'review' && isset($_GET['id'])): ?>
<?php
    $stmt = $conn->prepare("SELECT loi.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone FROM letter_of_intent loi JOIN users u ON loi.business_owner_id = u.id WHERE loi.id = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $loi = $stmt->get_result()->fetch_assoc();

    // Check if this LOI was previously returned (revision)
    $was_returned = false;
    if ($loi) {
        $rv = $conn->prepare("SELECT id FROM loi_verification WHERE loi_id = ? AND verification_status = 'returned' LIMIT 1");
        $rv->bind_param("i", $loi['id']);
        $rv->execute();
        $was_returned = (bool) $rv->get_result()->fetch_assoc();
    }
?>
<?php if ($loi): ?>
<a href="?action=list" class="btn btn-outline btn-sm" style="margin-bottom: 20px;"><i class="fas fa-arrow-left"></i> Back</a>

<?php if ($was_returned && $loi['status'] === 'submitted'): ?>
<div class="alert alert-info" style="margin-bottom: 20px;">
    <i class="fas fa-redo"></i>
    <div>
        <strong>Revised & Resubmitted</strong>
        <p style="margin-top: 2px; font-size: 0.9rem;">The business owner has revised this Letter of Intent based on your previous feedback and resubmitted it for review.</p>
    </div>
</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    <!-- Formal Letter -->
    <div class="card">
        <div class="card-body" style="padding: 48px 56px; font-family: 'Times New Roman', Times, serif; font-size: 1rem; line-height: 1.8; color: #111;">
            <p style="margin-bottom: 24px;">
                <?= $loi['date_of_intent'] ? date('d F Y', strtotime($loi['date_of_intent'])) : '[Day/Month/Year]' ?>
            </p>
            <p style="margin-bottom: 24px;">
                <strong>HADJI ABDULATIF S. SANGCUPAN</strong><br>
                President/CEO<br>
                Halal Development Institute of the Philippines (HDIP)<br>
                4th Flr. Unit 401, Central Bldg. 37 Arayat Cor. Malabito St.<br>
                Cubao, Quezon City
            </p>
            <p style="margin-bottom: 24px;">
                Subject: Letter of Intent for Halal Certification — <strong><?= htmlspecialchars($loi['company_name']) ?></strong>
                <?php if (!empty($loi['enterprise_type'])): ?>
                    (<em><?= htmlspecialchars($loi['enterprise_type']) ?></em>)
                <?php endif; ?>
            </p>
            <p style="margin-bottom: 24px;">Dear Sir/Madam,</p>
            <p style="margin-bottom: 24px;">
                I am writing to formally express the intent of <strong><?= htmlspecialchars($loi['company_name']) ?></strong><?php if (!empty($loi['enterprise_type'])): ?>, a <strong><?= htmlspecialchars($loi['enterprise_type']) ?></strong>,<?php endif; ?> to apply for <?= !empty($loi['application_type']) ? '<strong>' . htmlspecialchars($loi['application_type']) . '</strong> of ' : '' ?>Halal Certification from the Halal Development Institute of the Philippines (HDIP) for our products/services.
            </p>
            <p style="margin-bottom: 24px;">
                Our company, located at <strong><?= htmlspecialchars($loi['company_address']) ?></strong>, produces/serves
                <?php
                    $items = array_filter(explode("\n", $loi['menu_list'] ?? ''));
                    echo $items ? '<strong>' . htmlspecialchars(implode(', ', array_map('trim', $items))) . '</strong>' : '<strong>[Specific Product/Menu Item List]</strong>';
                ?>.
                We are committed to complying with all the requirements and standards set forth by the HDIP, including adherence to Shariah law, GMP (Good Manufacturing Practices), and hygiene standards.
            </p>
            <p style="margin-bottom: 24px;">Thank you for your assistance.</p>
            <p>Sincerely,</p>
            <p style="margin-top: 32px;">
                <strong><?= htmlspecialchars($loi['contact_person'] ?? $loi['owner_name']) ?></strong><br>
                <?= htmlspecialchars($loi['contact_phone'] ?? '[Phone Number]') ?><br>
                <?= htmlspecialchars($loi['owner_email']) ?>
            </p>
            <?php if (!empty($loi['signature_data'])): ?>
            <div style="margin-top: 24px; border-top: 1px solid #ddd; padding-top: 16px;">
                <div style="font-size: 0.8rem; color: #888; margin-bottom: 6px; font-family: Arial, sans-serif;">Applicant's Digital Signature:</div>
                <img src="<?= htmlspecialchars($loi['signature_data']) ?>"
                     alt="Digital Signature"
                     style="max-width: 280px; height: auto; border-bottom: 1px solid #333; display: block;">
                <div style="font-size: 0.75rem; color: #888; margin-top: 4px; font-family: Arial, sans-serif;">
                    <?= htmlspecialchars($loi['contact_person'] ?? $loi['owner_name']) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Panel -->
    <div class="card" style="height: fit-content; position: sticky; top: 20px;">
        <div class="card-header">
            <h3><i class="fas fa-clipboard-check" style="color: var(--primary-600); margin-right: 8px;"></i> Verification</h3>
        </div>
        <div class="card-body">
            <?php if (in_array($loi['status'], ['submitted', 'under_review'])): ?>
            <p style="color: var(--neutral-500); font-size: 0.9rem; margin-bottom: 20px;">Review the letter and choose an action.</p>
            <form method="POST" action="?action=list" id="verifyForm">
                <input type="hidden" name="loi_id" value="<?= $loi['id'] ?>">
                <input type="hidden" name="verification_status" id="verificationStatus">
                <div class="form-group">
                    <label>Feedback / Remarks</label>
                    <textarea name="feedback" id="loiFeedback" class="form-control" rows="4" placeholder="Required if rejecting — optional for approval..."><?= htmlspecialchars($loi['remarks'] ?? '') ?></textarea>
                </div>
                <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 8px;">
                    <button type="button" class="btn btn-success" onclick="submitVerification('verified')">
                        <i class="fas fa-check-circle"></i> Approve and Send Requirements
                    </button>
                    <button type="button" class="btn btn-danger" onclick="submitVerificationWithRemarks('returned')">
                        <i class="fas fa-times-circle"></i> Reject / Return
                    </button>
                </div>
            </form>
            <?php else: ?>
            <div style="text-align: center; padding: 8px 0 16px;">
                <?= getStatusBadge($loi['status']) ?>
                <?php if ($loi['remarks']): ?>
                <p style="margin-top: 12px; font-size: 0.9rem; color: var(--neutral-600); text-align: left;"><?= nl2br(htmlspecialchars($loi['remarks'])) ?></p>
                <?php endif; ?>
            </div>
            <?php if ($loi['status'] === 'verified'): ?>
            <?php
                $req_stmt = $conn->prepare("SELECT * FROM loi_requirements WHERE loi_id = ? ORDER BY sent_at DESC LIMIT 1");
                $req_stmt->bind_param("i", $loi['id']);
                $req_stmt->execute();
                $sent_req = $req_stmt->get_result()->fetch_assoc();
            ?>
            <?php if ($sent_req): ?>
            <div style="margin-top: 8px;">
                <p style="font-size: 0.8rem; font-weight: 600; color: var(--neutral-500); text-transform: uppercase; margin-bottom: 8px;">Requirements Sent</p>
                <div style="background: var(--neutral-50); border-radius: 10px; padding: 14px; font-size: 0.85rem; color: var(--neutral-700); max-height: 300px; overflow-y: auto;">
                    <?php foreach (array_filter(explode("\n", $sent_req['requirements'])) as $r): ?>
                        <div style="padding: 3px 0; border-bottom: 1px solid var(--neutral-100);"><?= htmlspecialchars(trim($r)) ?></div>
                    <?php endforeach; ?>
                </div>
                <p style="font-size: 0.75rem; color: var(--neutral-400); margin-top: 6px;">Sent on <?= formatDateTime($sent_req['sent_at']) ?></p>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
function submitVerification(status) {
    document.getElementById('verificationStatus').value = status;
    document.getElementById('verifyForm').submit();
}

function submitVerificationWithRemarks(status) {
    const feedback = document.getElementById('loiFeedback');
    if (!feedback || feedback.value.trim() === '') {
        feedback.style.borderColor = 'var(--danger)';
        feedback.focus();
        feedback.placeholder = 'Remarks are required when rejecting.';
        // Show inline message
        let msg = document.getElementById('feedbackError');
        if (!msg) {
            msg = document.createElement('p');
            msg.id = 'feedbackError';
            msg.style.cssText = 'color:var(--danger);font-size:0.82rem;margin-top:4px;';
            msg.textContent = 'Please provide remarks explaining why the LOI is being rejected.';
            feedback.parentNode.appendChild(msg);
        }
        return;
    }
    document.getElementById('verificationStatus').value = status;
    document.getElementById('verifyForm').submit();
}
</script>
