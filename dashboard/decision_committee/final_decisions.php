<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_DECISION_COMMITTEE]);

$page_title = 'Final Decisions';
$page_heading = 'Final Decisions';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/decision_committee/'], ['label' => 'Final Decisions']];
$user_id = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_id = intval($_POST['application_id']);
    $pd_id = intval($_POST['potential_decision_id']);
    $decision = sanitize($_POST['decision']);
    $notes = sanitize($_POST['decision_notes'] ?? '');
    
    $stmt = $conn->prepare("INSERT INTO final_decisions (application_id, potential_decision_id, committee_member_id, decision, decision_notes, decided_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiiss", $app_id, $pd_id, $user_id, $decision, $notes);
    
    if ($stmt->execute()) {
        $new_status = $decision === 'approved' ? 'approved' : 'rejected';
        $conn->query("UPDATE hdp_applications SET status = '$new_status' WHERE id = $app_id");
        
        if ($decision === 'approved') {
            // Notify president
            $pres = $conn->query("SELECT id FROM users WHERE role_id = " . ROLE_PRESIDENT . " AND role_status = 'approved'");
            while ($p = $pres->fetch_assoc()) {
                createNotification($conn, $p['id'], 'Application Approved - Awaiting Certificate',
                    'An application has been approved and is ready for certificate preparation.',
                    'action_required', BASE_URL . 'dashboard/president/award_certificates.php');
            }
        }
        
        $owner = $conn->query("SELECT business_owner_id FROM hdp_applications WHERE id = $app_id")->fetch_assoc();
        if ($owner) {
            $msg = $decision === 'approved' ? 'Congratulations! Your halal certification has been approved!' : 'Your halal certification application has been ' . $decision . '.';
            createNotification($conn, $owner['business_owner_id'], 'Final Decision: ' . ucfirst($decision), $msg,
                $decision === 'approved' ? 'success' : 'error',
                BASE_URL . 'dashboard/business_owner/applications.php');
        }
        
        logActivity($conn, $user_id, 'Final Decision', 'Decision: ' . $decision . ' for App #' . $app_id, 'committee');
        $success = 'Decision recorded: ' . ucfirst($decision);
    }
}

// Get applications with impartial committee recommendation awaiting final decision
$awaiting = $conn->query("
    SELECT pd.*, loi.company_name, ha.id as app_id, u.full_name as reviewer_name
    FROM potential_decisions pd
    JOIN hdp_applications ha ON pd.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    JOIN users u ON pd.committee_member_id = u.id
    WHERE pd.application_id NOT IN (SELECT application_id FROM final_decisions)
    ORDER BY pd.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<?php if (empty($awaiting)): ?>
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-check-circle" style="color: var(--success);"></i></div>
            <h3>No Pending Decisions</h3>
            <p>All applications have been decided upon.</p>
        </div>
    </div>
</div>
<?php else: ?>
<?php foreach ($awaiting as $pd): ?>
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header" style="background: var(--neutral-50);">
        <h3><?= htmlspecialchars($pd['company_name']) ?></h3>
        <?= getStatusBadge($pd['decision']) ?>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase">Impartial Committee Review</label>
                <div style="background:var(--neutral-50);padding:16px;border-radius:10px;margin-top:6px">
                    <p><strong>Reviewed by:</strong> <?= htmlspecialchars($pd['reviewer_name']) ?></p>
                    <p><strong>Recommendation:</strong> <?= getStatusBadge($pd['decision']) ?></p>
                    <p><strong>Date:</strong> <?= $pd['reviewed_at'] ? formatDateTime($pd['reviewed_at']) : 'N/A' ?></p>
                </div>
            </div>
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase">Evidence Summary</label>
                <div style="background:var(--neutral-50);padding:16px;border-radius:10px;margin-top:6px;max-height:150px;overflow-y:auto">
                    <?= nl2br(htmlspecialchars($pd['evidence_summary'])) ?>
                </div>
            </div>
        </div>
        
        <?php if ($pd['review_notes']): ?>
        <div style="margin-bottom: 20px;">
            <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase">Review Notes</label>
            <div style="background:var(--neutral-50);padding:16px;border-radius:10px;margin-top:6px"><?= nl2br(htmlspecialchars($pd['review_notes'])) ?></div>
        </div>
        <?php endif; ?>
        
        <div style="border-top: 2px solid var(--neutral-100); padding-top: 24px;">
            <h4 style="font-family: var(--font-display); font-weight: 700; margin-bottom: 16px;"><i class="fas fa-check-double" style="color: var(--primary-600);"></i> Make Final Decision</h4>
            <form method="POST">
                <input type="hidden" name="application_id" value="<?= $pd['app_id'] ?>">
                <input type="hidden" name="potential_decision_id" value="<?= $pd['id'] ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Final Decision <span class="required">*</span></label>
                        <select name="decision" class="form-control" required>
                            <option value="">Select...</option>
                            <option value="approved">✅ Approve - Grant Certification</option>
                            <option value="rejected">❌ Reject - Deny Certification</option>
                            <option value="deferred">⏸️ Defer - Request More Info</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Decision Notes</label>
                        <textarea name="decision_notes" class="form-control" rows="3" placeholder="Provide reasoning for the decision..."></textarea>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-gavel"></i> Finalize Decision</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
