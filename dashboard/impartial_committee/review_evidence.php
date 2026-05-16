<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_IMPARTIAL_COMMITTEE]);

$page_title = 'Review Evidence';
$page_heading = 'Review Evidence & Potential Decisions';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/impartial_committee/'], ['label' => 'Review Evidence']];
$user_id = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_id = intval($_POST['application_id']);
    $evidence = sanitize($_POST['evidence_summary']);
    $notes = sanitize($_POST['review_notes'] ?? '');
    $decision = sanitize($_POST['decision']);
    $car_id = !empty($_POST['corrective_action_id']) ? intval($_POST['corrective_action_id']) : null;
    
    $stmt = $conn->prepare("INSERT INTO potential_decisions (application_id, corrective_action_id, committee_member_id, evidence_summary, review_notes, decision, reviewed_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiisss", $app_id, $car_id, $user_id, $evidence, $notes, $decision);
    
    if ($stmt->execute()) {
        if ($decision === 'recommend_approve') {
            $dc = $conn->query("SELECT id FROM users WHERE role_id = " . ROLE_DECISION_COMMITTEE . " AND role_status = 'approved'");
            while ($d = $dc->fetch_assoc()) {
                createNotification($conn, $d['id'], 'Review Complete - Recommended for Approval',
                    'An application has been recommended for approval by the Impartial Committee.', 'action_required',
                    BASE_URL . 'dashboard/decision_committee/final_decisions.php');
            }
        }
        $owner = $conn->query("SELECT business_owner_id FROM hdp_applications WHERE id = $app_id")->fetch_assoc();
        if ($owner) {
            createNotification($conn, $owner['business_owner_id'], 'Application Review Update',
                'Your application has been reviewed by the Impartial Committee.', 'info',
                BASE_URL . 'dashboard/business_owner/applications.php');
        }
        logActivity($conn, $user_id, 'Potential Decision', 'Decision: ' . $decision . ' for App #' . $app_id, 'committee');
        $success = 'Review submitted successfully!';
    }
}

// Get completed inspections awaiting review
$awaiting = $conn->query("
    SELECT ha.id as app_id, loi.company_name, loi.company_address, i.audit_findings, i.conformity_status, i.remarks, i.inspection_date,
           ut.full_name as tech_auditor, us.full_name as shariah_auditor,
           lr.report_content as lab_report,
           car.report_content as corrective_report, car.id as car_id
    FROM hdp_applications ha
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    JOIN inspections i ON i.application_id = ha.id AND i.status = 'completed'
    LEFT JOIN users ut ON i.auditor_technical_id = ut.id
    LEFT JOIN users us ON i.auditor_shariah_id = us.id
    LEFT JOIN laboratory_reports lr ON lr.application_id = ha.id
    LEFT JOIN corrective_action_reports car ON car.application_id = ha.id
    WHERE ha.id NOT IN (SELECT application_id FROM potential_decisions)
    ORDER BY i.created_at ASC
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
            <h3>All Caught Up!</h3>
            <p>No applications pending your review at this time.</p>
        </div>
    </div>
</div>
<?php else: ?>
<?php foreach ($awaiting as $app): ?>
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header" style="background: var(--neutral-50);">
        <h3><i class="fas fa-building" style="color: var(--primary-600); margin-right: 8px;"></i> <?= htmlspecialchars($app['company_name']) ?></h3>
        <?= getStatusBadge($app['conformity_status']) ?>
    </div>
    <div class="card-body">
        <!-- Evidence Summary -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
            <div>
                <h4 style="font-family: var(--font-display); font-weight: 700; margin-bottom: 8px; font-size: 0.95rem;">
                    <i class="fas fa-search" style="color: var(--primary-500);"></i> Inspection Details
                </h4>
                <div style="background: var(--neutral-50); padding: 16px; border-radius: 10px;">
                    <p><strong>Date:</strong> <?= $app['inspection_date'] ? formatDate($app['inspection_date']) : 'N/A' ?></p>
                    <p><strong>Technical Auditor:</strong> <?= htmlspecialchars($app['tech_auditor'] ?? 'N/A') ?></p>
                    <p><strong>Shariah Auditor:</strong> <?= htmlspecialchars($app['shariah_auditor'] ?? 'N/A') ?></p>
                    <p><strong>Conformity:</strong> <?= getStatusBadge($app['conformity_status']) ?></p>
                </div>
            </div>
            <div>
                <h4 style="font-family: var(--font-display); font-weight: 700; margin-bottom: 8px; font-size: 0.95rem;">
                    <i class="fas fa-clipboard" style="color: var(--accent-500);"></i> Audit Findings
                </h4>
                <div style="background: var(--neutral-50); padding: 16px; border-radius: 10px; max-height: 200px; overflow-y: auto;">
                    <?= nl2br(htmlspecialchars($app['audit_findings'] ?? 'No findings documented.')) ?>
                </div>
            </div>
        </div>
        
        <?php if ($app['remarks']): ?>
        <div style="margin-bottom: 20px;">
            <h4 style="font-family: var(--font-display); font-weight: 700; margin-bottom: 8px; font-size: 0.95rem;">Remarks</h4>
            <div style="background: var(--neutral-50); padding: 16px; border-radius: 10px;">
                <?= nl2br(htmlspecialchars($app['remarks'])) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Decision Form -->
        <div style="border-top: 2px solid var(--neutral-100); padding-top: 24px; margin-top: 24px;">
            <h4 style="font-family: var(--font-display); font-weight: 700; margin-bottom: 16px;">
                <i class="fas fa-gavel" style="color: var(--primary-600);"></i> Submit Your Review
            </h4>
            <form method="POST">
                <input type="hidden" name="application_id" value="<?= $app['app_id'] ?>">
                <input type="hidden" name="corrective_action_id" value="<?= $app['car_id'] ?? '' ?>">
                
                <div class="form-group">
                    <label>Evidence Summary <span class="required">*</span></label>
                    <textarea name="evidence_summary" class="form-control" rows="4" required placeholder="Summarize the evidence reviewed..."></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Decision <span class="required">*</span></label>
                        <select name="decision" class="form-control" required>
                            <option value="">Select decision...</option>
                            <option value="recommend_approve">✅ Recommend Approval</option>
                            <option value="recommend_reject">❌ Recommend Rejection</option>
                            <option value="need_more_info">⚠️ Need More Information</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Review Notes</label>
                        <textarea name="review_notes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
