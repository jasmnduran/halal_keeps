<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_EVALUATOR]);

$page_title = 'Terms of Reference';
$page_heading = 'Terms of Reference';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/evaluator/'], ['label' => 'Terms of Reference']];
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$error = ''; $success = '';

$default_tor = "TERMS OF REFERENCE
Including particular conditions for the assessment of management systems by Halal Development Institute and Development, herein after termed \"HDIP\", with its contracting partners, hereinafter termed \"client\" or \"clients\".

1. Scope
1.1 These conditions apply to contracts agreed between HDIP and its clients, unless it is otherwise agreed in writing or so prescribed by statutory instruments.
1.2 In the following text, audits and assessments are referred to as \"assessments\", Auditors, assessors and Technical Experts are referred to as \"assessors\" and reports on audits and assessments are referred to as \"assessment reports\".

2. Assessment of Halal Management System, Products and Processes
2.1 HDIP assesses the processes, products and management system of its client, or parts thereof, with the goal of determining its conformity with Islamic rites and specified requirements, including the effectiveness of the system. The client receives an audit report and a HDIP certificate or confirmation.
2.2 HDIP is independent, neutral and objective in its assessments. Assessments are performed at the client's place of operations. The type, extent and time schedule of the procedure are subject to separate agreement by the parties. If nonconformities with Islamic rites and the requirements of the respective specification are identified during an assessment, the corrective actions must demonstrably be carried out by the client within the time frame specified in the reference document or by an appropriate agreed deadline, before a HDIP certificate can be issued. HDIP strives to minimize any disturbances of the business process while conducting the assessment on the client's premises.
2.3 Porcine and its derivatives may not be used in the facility that produces Halal products.
2.4 Where Ovine, Bovine, Caprine, Cervine and Avian slaughtering and processing takes place:
2.4.1 The correct number of Muslim delegates shall be maintained in accordance with HDIP requirements and importing country requirements such as GSO 993, MS1500:2011
2.4.2 Slaughter and stunning procedures must be adhered to as per GSO 993, MS1500:2011
2.4.3 Stunning methods (such as electric shock and gassing) are not acceptable in the GSO 993 for poultry and therefore processed poultry used in goods cannot be accepted into the Gulf Countries.
2.4.4 Captive Bolt as a stunning method is not acceptable as Halal for any animal
2.5 All equipment, machinery, utensils, stoves, receptacles, benches and ovens used for Halal goods preparation shall be cleaned prior to use under the overall supervision of the Site Manager or their appointee.
2.6 Storage and preparation areas reserved for the preparation of Halal goods shall be segregated.
2.7 Storage, preparation, heating and/or cooking of Halal goods shall be carried out under the overall supervision of the Site Manager or their appointee.
2.8 Halal and non-Halal goods shall not be prepared, mixed, cooked or heated in/on the same equipment at the same time.
2.9 All raw, frozen, dried, processed and prepared ingredients required for the preparation of Halal goods, shall be acquired from suppliers approved by HDIP and kept segregated in storage from non-acceptable ingredients.
2.10 Any product, ingredient or ready-made goods not approved by HDIP shall not be used in the preparation of Halal goods.
2.11 The HDIP requirements for specific industry types are adhered to as per importing country standards such as GSO 993, GSO 2055, MS1500.
2.12 Abide by Food Safety requirements set out in importing country requirements i.e. GSO 993, GSO 2055, MS1500 and other country standards.
2.13 Cosmetics, Personal Care and Pharmaceuticals under the standards of Therapeutics Goods Administration shall adhere to Importing country Food and Drug Authorities such as GSO2055.

3. Certification Cycles
3.1 The Halal Certification period is 3 years subject to annual surveillance audits in Year 1 and Year 2 and recertification in year 3.
3.2 Certificates shall be subject to update after each surveillance audit.
3.3 HDIP has a certification period starting from 1st January 2017 to 31 December 2019 and every 3 year the certification periods shall be ongoing.

4. Audits
4.1.1 The number and choice of auditors is incumbent upon HDIP, who will nominate the auditors (Sharia and Technical).
4.1.2 HDIP commits itself to use only auditors who are suitable for the task on the basis of their understanding of Islamic rites and technical qualifications, their experience and their personal abilities.
4.2.1 Clients will be advised of scheduled audits via email and shall be confirmed or rescheduled by return email.
4.2.2 Should the client not respond, the audit shall be assumed as confirmed.
4.2.3 Cancelling or rescheduling the audit within fourteen (14) days of the scheduled audit will cause the cost of the audit stated in the Audit Plan as a cancellation fee.

5. Use of HDIP Trademarks and Certificates
5.1 The use of the HDIP trademark is governed by HDIP, protected by Intellectual property laws, and requires HDIP approval.

6. Breaches of Certification
6.1.1 HDIP may only issue certificates if all Halal requirements have been fulfilled following the audit.
6.2.1 HDIP is entitled to suspend a certificate for a limited period of time if the client demonstrably violates Islamic rites, halal rules and contractual or financial obligations towards HDIP.

7. Appeals and Complaints
7.1 Every client has the right to have services performed within the agreed scope. In case of a difference of opinion, each client has the right to submit an appeal or a complaint via email hdiphilippines@gmail.com or phone +63 917 980 6317.

8. Arbitration
8.1 In the event of failure to resolve a major complaint, an independent arbitration may be deployed.

9. Jurisdiction and Applicable Laws
9.1 Court of jurisdiction is New South Wales and the NSW law applies in all respects.

10. Diverging Agreements
10.1 Diverging or supplementary agreements have to be made in writing.

11. Additional Conditions
11.1 Specific requirements of individual standards or specifications obtain in their current versions.
11.2 Clients shall be given one calendar months' notice of any changes to the Terms & Conditions.

12. Management of Impartiality
12.1 HDIP management is committed to maintenance of impartiality throughout the certification process. Certification decisions shall be based on objective evidence of conformity obtained during audits and shall not be influenced by any other interests or by other parties.";

$technical_auditors = $conn->query("SELECT id, full_name FROM users WHERE role_id = " . ROLE_AUDITOR_TECHNICAL . " AND role_status = 'approved' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
$shariah_auditors = $conn->query("SELECT id, full_name FROM users WHERE role_id = " . ROLE_AUDITOR_SHARIAH . " AND role_status = 'approved' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
$auditor_lookup = [];
foreach (array_merge($technical_auditors, $shariah_auditors) as $auditor) {
    $auditor_lookup[intval($auditor['id'])] = $auditor['full_name'];
}

function normalizeTorAuditorAssignments($roles, $auditorIds, $amounts, $auditorLookup) {
    $assignments = [];
    foreach ($roles as $index => $role) {
        $role = strtolower(trim((string)$role));
        if (!in_array($role, ['shariah', 'technical'], true)) continue;

        $auditor_id = intval($auditorIds[$index] ?? 0);
        $amount = max(0, floatval($amounts[$index] ?? 0));
        $assignments[] = [
            'role' => $role,
            'auditor_id' => $auditor_id,
            'auditor_name' => $auditor_id && isset($auditorLookup[$auditor_id]) ? $auditorLookup[$auditor_id] : '',
            'amount' => $amount,
        ];
    }
    return $assignments;
}

// Handle sending TOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_tor'])) {
    $app_id      = intval($_POST['application_id']);
    $tor_content = $_POST['tor_content'] ?? $default_tor;
    $payment     = floatval($_POST['payment_for_inspection'] ?? 0);
    $fees        = floatval($_POST['inspection_fees'] ?? 0);
    $enterprise_type = normalizeEnterpriseType($_POST['enterprise_type'] ?? '');
    $auditor_assignments = normalizeTorAuditorAssignments(
        $_POST['auditor_role'] ?? [],
        $_POST['auditor_id'] ?? [],
        $_POST['auditor_amount'] ?? [],
        $auditor_lookup
    );
    $auditor_total = getAuditorAssignmentTotal($auditor_assignments);
    $auditor_assignments_json = json_encode($auditor_assignments);

    // Handle optional file upload
    $tor_file_path = '';
    if (isset($_FILES['tor_file']) && $_FILES['tor_file']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['tor_file'], 'documents', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
        if ($upload['success']) {
            $tor_file_path = $upload['path'];
        } else {
            $error = 'File upload failed: ' . $upload['message'];
        }
    }

    if (!$error) {
        // Check if TOR already exists for this application
        $existing = $conn->prepare("SELECT id, tor_file_path FROM terms_of_reference WHERE application_id = ?");
        $existing->bind_param("i", $app_id);
        $existing->execute();
        $existing_tor = $existing->get_result()->fetch_assoc();

        // Keep old file if no new one uploaded
        if (empty($tor_file_path) && $existing_tor) {
            $tor_file_path = $existing_tor['tor_file_path'] ?? '';
        }

        if ($existing_tor) {
            $upd = $conn->prepare("UPDATE terms_of_reference SET tor_content=?, payment_for_inspection=?, inspection_fees=?, tor_file_path=?, enterprise_type=?, auditor_assignments=?, auditor_total_amount=?, status='sent', business_response='pending', responded_at=NULL WHERE application_id=?");
            $upd->bind_param("sddsssdi", $tor_content, $payment, $fees, $tor_file_path, $enterprise_type, $auditor_assignments_json, $auditor_total, $app_id);
            $upd->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO terms_of_reference (application_id, evaluator_id, tor_content, payment_for_inspection, inspection_fees, tor_file_path, enterprise_type, auditor_assignments, auditor_total_amount, status, business_response) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'sent', 'pending')");
            $stmt->bind_param("iisddsssd", $app_id, $user_id, $tor_content, $payment, $fees, $tor_file_path, $enterprise_type, $auditor_assignments_json, $auditor_total);
            $stmt->execute();
        }

        if (!empty($enterprise_type)) {
            $app_upd = $conn->prepare("UPDATE hdp_applications SET enterprise_type = ? WHERE id = ?");
            $app_upd->bind_param("si", $enterprise_type, $app_id);
            $app_upd->execute();
        }
    }

    $owner_q = $conn->prepare("SELECT business_owner_id FROM hdp_applications WHERE id = ?");
    $owner_q->bind_param("i", $app_id);
    $owner_q->execute();
    $owner = $owner_q->get_result()->fetch_assoc();
    if ($owner && !$error) {
        $is_update = !empty($existing_tor);
        createNotification($conn, $owner['business_owner_id'],
            $is_update ? 'Terms of Reference Updated' : 'Terms of Reference Sent',
            $is_update
                ? 'The evaluator has updated the Terms of Reference for your application. Please review the changes and respond.'
                : 'The evaluator has sent you the Terms of Reference for your halal certification. Please review and respond.',
            'action_required',
            BASE_URL . 'dashboard/business_owner/terms_of_reference.php');
    }
    if (!$error) {
        logActivity($conn, $user_id, 'TOR Sent', 'TOR sent for application #' . $app_id, 'evaluation');
        $success = $existing_tor ? 'Terms of Reference updated and resent.' : 'Terms of Reference sent to business owner.';
        $action = 'list';
    }
}

// Load all TORs with business response
$all_tors = $conn->query("
    SELECT t.*, loi.company_name, u.full_name as owner_name, ha.id as app_id
    FROM terms_of_reference t
    JOIN hdp_applications ha ON t.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    JOIN users u ON ha.business_owner_id = u.id
    ORDER BY t.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Apps with no TOR yet
$apps_without_tor = $conn->query("
    SELECT ha.*, loi.company_name
    FROM hdp_applications ha
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    WHERE ha.status = 'verified'
      AND ha.id NOT IN (SELECT application_id FROM terms_of_reference)
    ORDER BY ha.updated_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get TOR for edit action
$edit_tor = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $et = $conn->prepare("SELECT t.*, loi.company_name, ha.enterprise_type AS app_enterprise_type FROM terms_of_reference t JOIN hdp_applications ha ON t.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id WHERE t.id = ?");
    $et->bind_param("i", $_GET['id']);
    $et->execute();
    $edit_tor = $et->get_result()->fetch_assoc();
    // Block editing if already accepted
    if ($edit_tor && ($edit_tor['business_response'] ?? '') === 'accepted') {
        $action = 'view';
        $_GET['id'] = $edit_tor['id'];
        $error = 'This Terms of Reference has been accepted by the business owner and can no longer be edited.';
        $edit_tor = null;
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= $error ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>

<?php if (!empty($apps_without_tor)): ?>
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3><i class="fas fa-paper-plane" style="color:var(--primary-600);margin-right:8px;"></i> Send Terms of Reference</h3>
    </div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="send_tor" value="1">
            <div class="form-group">
                <label>Select Verified Application <span class="required">*</span></label>
                <select name="application_id" class="form-control" required>
                    <option value="">Select application...</option>
                    <?php foreach ($apps_without_tor as $va): ?>
                    <?php
                        $va_enterprise_type = normalizeEnterpriseType($va['enterprise_type'] ?? '');
                        $va_default_assignments = buildDefaultAuditorAssignments($va_enterprise_type);
                        $va_default_total = getAuditorAssignmentTotal($va_default_assignments);
                    ?>
                    <option value="<?= $va['id'] ?>"
                        data-enterprise-type="<?= htmlspecialchars($va_enterprise_type) ?>"
                        data-enterprise-summary="<?= htmlspecialchars(getEnterpriseStandardSummary($va_enterprise_type)) ?>"
                        data-auditors='<?= htmlspecialchars(json_encode($va_default_assignments), ENT_QUOTES, 'UTF-8') ?>'
                        data-total="<?= $va_default_total ?>">
                        <?= htmlspecialchars($va['company_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Enterprise Classification: hidden when already set on the application, shown only when unclassified -->
            <input type="hidden" name="enterprise_type" id="torEnterpriseTypeValue">
            <div class="form-group" id="torEnterpriseTypeDropdownGroup">
                <label>Enterprise Classification <span class="required">*</span></label>
                <select id="torEnterpriseType" class="form-control"
                    onchange="document.getElementById('torEnterpriseTypeValue').value = this.value; applyEnterpriseStandard(this.value);">
                    <option value="">Select classification...</option>
                    <?php foreach (getEnterpriseStandards() as $type => $standard): ?>
                    <option value="<?= $type ?>"><?= htmlspecialchars($standard['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" id="torEnterpriseTypeReadonlyGroup" style="display:none;">
                <label>Enterprise Classification</label>
                <div id="torEnterpriseTypeReadonly" style="background:var(--neutral-50);border-radius:8px;padding:10px 14px;color:var(--neutral-700);font-size:0.9rem;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-tag" style="color:var(--primary-500);"></i>
                    <span id="torEnterpriseTypeLabel"></span>
                    <small style="color:var(--neutral-400);margin-left:4px;">(set during application approval)</small>
                </div>
            </div>
            <div class="form-group">
                <label>Enterprise Standard</label>
                <div id="torEnterpriseSummary" style="background:var(--neutral-50);border-radius:8px;padding:12px 14px;color:var(--neutral-600);font-size:0.9rem;">
                    Select an application to load the standard auditor plan.
                </div>
            </div>

            <!-- Toggle -->
            <div class="form-group">
                <label>How would you like to provide the TOR?</label>
                <div style="display:flex;gap:12px;margin-top:6px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:normal;">
                        <input type="radio" name="tor_input_mode" value="type" checked onchange="toggleTorMode('type')">
                        <span>Type / Paste Content</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:normal;">
                        <input type="radio" name="tor_input_mode" value="upload" onchange="toggleTorMode('upload')">
                        <span>Upload File</span>
                    </label>
                </div>
            </div>

            <!-- Type mode -->
            <div id="torTypeMode" class="form-group">
                <label>Terms of Reference Content</label>
                <textarea name="tor_content" class="form-control" rows="12" style="font-size:0.85rem;font-family:monospace;"><?= htmlspecialchars($default_tor) ?></textarea>
                <p class="form-text">You may edit the content before sending.</p>
            </div>

            <!-- Upload mode -->
            <div id="torUploadMode" class="form-group" style="display:none;">
                <label>Upload TOR File <span class="required">*</span> <small style="color:var(--neutral-400);">PDF, DOC, DOCX, JPG, PNG</small></label>
                <div class="file-upload">
                    <input type="file" name="tor_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" id="torFileInput">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p class="file-label">Click or drag to upload TOR document</p>
                </div>
            </div>
            <div class="form-group">
                <label>Auditors for Inspection</label>
                <div class="table-responsive">
                    <table class="table" style="margin-bottom:10px;">
                        <thead>
                            <tr><th style="width:22%;">Role</th><th>Auditor</th><th style="width:22%;">Amount</th><th style="width:60px;"></th></tr>
                        </thead>
                        <tbody id="auditorRows"></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline" onclick="addAuditorRow()">
                    <i class="fas fa-plus"></i> Add Auditor
                </button>
                <p class="form-text">These rows start from the enterprise standard, but you can change the number of auditors and each amount.</p>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;background:var(--neutral-50);border-radius:10px;padding:12px 18px;margin-bottom:16px;">
                <span style="font-size:0.85rem;font-weight:600;color:var(--neutral-500);text-transform:uppercase;letter-spacing:0.04em;"><i class="fas fa-receipt" style="margin-right:6px;color:var(--primary-500);"></i>Total Inspection Fee</span>
                <span id="auditorTotalDisplay" style="font-size:1.15rem;font-weight:700;color:var(--primary-700);">₱0.00</span>
            </div>
            <input type="hidden" name="payment_for_inspection" id="paymentForInspection" value="0">
            <input type="hidden" name="inspection_fees" id="inspectionFees" value="0">
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send TOR to Business Owner</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3>Sent Terms of Reference</h3></div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($all_tors)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-file-contract"></i></div><h3>No TORs Sent Yet</h3></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Company</th><th>Owner</th><th>Enterprise</th><th>Inspection Fee</th><th>Business Response</th><th>Sent</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_tors as $tor): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($tor['company_name']) ?></strong></td>
                            <td><?= htmlspecialchars($tor['owner_name']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($tor['enterprise_type'] ?? 'Not set')) ?></td>
                            <td><?= formatCurrency($tor['inspection_fees']) ?></td>
                            <td>
                                <?php
                                $resp = $tor['business_response'] ?? 'pending';
                                $badges = ['pending' => 'badge-warning', 'accepted' => 'badge-success', 'rejected' => 'badge-danger'];
                                ?>
                                <span class="badge <?= $badges[$resp] ?? 'badge-secondary' ?>"><?= ucfirst($resp) ?></span>
                                <?php if ($resp === 'rejected' && $tor['business_remarks']): ?>
                                    <br><small style="color:var(--neutral-500);font-size:0.78rem;" title="<?= htmlspecialchars($tor['business_remarks']) ?>">
                                        <i class="fas fa-comment"></i> Has remarks
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.85rem;color:var(--neutral-500)"><?= formatDate($tor['created_at']) ?></td>
                            <td style="display:flex;gap:6px;">
                                <a href="?action=view&id=<?= $tor['id'] ?>" class="btn btn-sm btn-outline">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if (($tor['business_response'] ?? 'pending') !== 'accepted'): ?>
                                <a href="?action=edit&id=<?= $tor['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php else: ?>
                                <span class="badge badge-success" style="align-self:center;">
                                    <i class="fas fa-lock"></i> Accepted
                                </span>
                                <?php endif; ?>
                            </td>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'view' && isset($_GET['id'])): ?>
<?php
$tor_id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT t.*, loi.company_name, loi.company_address, u.full_name as owner_name, u.email as owner_email, ha.enterprise_type AS app_enterprise_type FROM terms_of_reference t JOIN hdp_applications ha ON t.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id JOIN users u ON ha.business_owner_id = u.id WHERE t.id = ?");
$stmt->bind_param("i", $tor_id);
$stmt->execute();
$tor = $stmt->get_result()->fetch_assoc();
?>
<?php if ($tor): ?>
<a href="?action=list" class="btn btn-outline btn-sm" style="margin-bottom:20px;"><i class="fas fa-arrow-left"></i> Back</a>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-file-contract" style="color:var(--primary-600);margin-right:8px;"></i> Terms of Reference</h3>
            <span class="badge <?= $tor['business_response'] === 'accepted' ? 'badge-success' : ($tor['business_response'] === 'rejected' ? 'badge-danger' : 'badge-warning') ?>">
                Business: <?= ucfirst($tor['business_response'] ?? 'pending') ?>
            </span>
        </div>
        <?php
            $has_file = !empty($tor['tor_file_path']);
            $ext      = $has_file ? strtolower(pathinfo($tor['tor_file_path'], PATHINFO_EXTENSION)) : '';
            $file_url = $has_file ? BASE_URL . 'uploads/' . htmlspecialchars($tor['tor_file_path']) : '';
        ?>
        <?php if ($has_file && $ext === 'pdf'): ?>
            <iframe src="<?= $file_url ?>" style="width:100%;height:650px;border:none;display:block;"></iframe>
        <?php elseif ($has_file && in_array($ext, ['jpg','jpeg','png'])): ?>
            <div style="padding:24px;text-align:center;">
                <img src="<?= $file_url ?>" alt="TOR Document"
                     style="max-width:100%;height:auto;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.1);">
            </div>
        <?php elseif ($has_file): ?>
            <div style="padding:48px;text-align:center;color:var(--neutral-500);">
                <i class="fas fa-file-alt" style="font-size:3rem;margin-bottom:12px;display:block;"></i>
                <p>Preview not available for this file type.</p>
                <a href="<?= $file_url ?>" target="_blank" class="btn btn-outline" style="margin-top:8px;">
                    <i class="fas fa-external-link-alt"></i> Open File
                </a>
            </div>
        <?php else: ?>
            <div class="card-body" style="white-space: pre-wrap; font-family: 'Times New Roman', serif; font-size: 0.95rem; line-height: 1.8; color: #111; max-height: 650px; overflow-y: auto;">
                <?= htmlspecialchars($tor['tor_content']) ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="display: flex; flex-direction: column; gap: 16px; position: sticky; top: 20px;">
        <div class="card">
            <div class="card-header"><h3>Details</h3></div>
            <div class="card-body">
                <div style="margin-bottom:12px;">
                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Company</div>
                    <div style="font-weight:600;"><?= htmlspecialchars($tor['company_name']) ?></div>
                </div>
                <div style="margin-bottom:12px;">
                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Business Owner</div>
                    <div><?= htmlspecialchars($tor['owner_name']) ?></div>
                    <div style="font-size:0.85rem;color:var(--neutral-400);"><?= htmlspecialchars($tor['owner_email']) ?></div>
                </div>
                <div style="margin-bottom:12px;">
                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Inspection Fee</div>
                    <div><?= formatCurrency($tor['inspection_fees']) ?></div>
                </div>
                <?php
                    $tor_enterprise_type = normalizeEnterpriseType($tor['enterprise_type'] ?? $tor['app_enterprise_type'] ?? '');
                    $tor_assignments = json_decode($tor['auditor_assignments'] ?? '[]', true);
                    if (!is_array($tor_assignments)) $tor_assignments = [];
                ?>
                <div style="margin-bottom:12px;">
                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Enterprise Standard</div>
                    <div><?= htmlspecialchars(getEnterpriseStandardSummary($tor_enterprise_type)) ?></div>
                </div>
                <?php if (!empty($tor_assignments)): ?>
                <div style="margin-bottom:12px;">
                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Assigned Auditors</div>
                    <?php foreach ($tor_assignments as $assignment): ?>
                    <div style="font-size:0.86rem;color:var(--neutral-700);margin-bottom:4px;">
                        <?= ucfirst(htmlspecialchars($assignment['role'] ?? 'auditor')) ?>:
                        <?= htmlspecialchars($assignment['auditor_name'] ?: 'Unassigned') ?>
                        <span style="color:var(--neutral-500);">(<?= formatCurrency($assignment['amount'] ?? 0) ?>)</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Sent On</div>
                    <div><?= formatDateTime($tor['created_at']) ?></div>
                </div>
                <?php if (!empty($tor['tor_file_path'])): ?>
                <div style="margin-top:12px;">
                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Attached File</div>
                    <a href="<?= BASE_URL ?>uploads/<?= htmlspecialchars($tor['tor_file_path']) ?>" target="_blank" download
                       class="btn btn-sm btn-outline" style="width:100%;text-align:center;">
                        <i class="fas fa-file-download"></i> Download File
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (($tor['business_response'] ?? 'pending') !== 'pending'): ?>
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-reply" style="color:var(--primary-600);margin-right:8px;"></i> Business Response</h3>
            </div>
            <div class="card-body">
                <div style="margin-bottom:12px;">
                    <span class="badge <?= $tor['business_response'] === 'accepted' ? 'badge-success' : 'badge-danger' ?>" style="font-size:0.9rem;padding:8px 16px;">
                        <?= $tor['business_response'] === 'accepted' ? '✅ Accepted' : '❌ Rejected / Has Remarks' ?>
                    </span>
                </div>
                <?php if ($tor['business_remarks']): ?>
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Remarks from Business Owner</div>
                    <div style="background:var(--neutral-50);padding:14px;border-radius:10px;font-size:0.9rem;color:var(--neutral-700);line-height:1.6;">
                        <?= nl2br(htmlspecialchars($tor['business_remarks'])) ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($tor['responded_at']): ?>
                <p style="font-size:0.78rem;color:var(--neutral-400);margin-top:8px;">Responded on <?= formatDateTime($tor['responded_at']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php elseif ($action === 'edit' && $edit_tor): ?>
<a href="?action=list" class="btn btn-outline btn-sm" style="margin-bottom:20px;"><i class="fas fa-arrow-left"></i> Back</a>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-edit" style="color:var(--primary-600);margin-right:8px;"></i>
            Edit Terms of Reference — <?= htmlspecialchars($edit_tor['company_name']) ?>
        </h3>
    </div>
    <div class="card-body">
        <?php if ($edit_tor['business_response'] === 'accepted'): ?>
        <div class="alert alert-warning" style="margin-bottom:20px;">
            <i class="fas fa-exclamation-triangle"></i>
            <span>The business owner has already <strong>accepted</strong> this TOR. Editing and resending will reset their response to pending.</span>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="send_tor" value="1">
            <input type="hidden" name="application_id" value="<?= $edit_tor['application_id'] ?>">
            <?php
                $edit_enterprise_type = normalizeEnterpriseType($edit_tor['enterprise_type'] ?? $edit_tor['app_enterprise_type'] ?? '');
                $edit_assignments = json_decode($edit_tor['auditor_assignments'] ?? '[]', true);
                if (!is_array($edit_assignments) || empty($edit_assignments)) {
                    $edit_assignments = buildDefaultAuditorAssignments($edit_enterprise_type);
                }
            ?>
            <div class="form-group">
                <label>Enterprise Classification <span class="required">*</span></label>
                <select name="enterprise_type" id="torEnterpriseType" class="form-control" required>
                    <option value="">Select classification...</option>
                    <?php foreach (getEnterpriseStandards() as $type => $standard): ?>
                    <option value="<?= $type ?>" <?= $edit_enterprise_type === $type ? 'selected' : '' ?>>
                        <?= htmlspecialchars($standard['label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Enterprise Standard</label>
                <div id="torEnterpriseSummary" style="background:var(--neutral-50);border-radius:8px;padding:12px 14px;color:var(--neutral-600);font-size:0.9rem;">
                    <?= htmlspecialchars(getEnterpriseStandardSummary($edit_enterprise_type)) ?>
                </div>
            </div>

            <!-- Toggle -->
            <div class="form-group">
                <label>How would you like to provide the TOR?</label>
                <div style="display:flex;gap:12px;margin-top:6px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:normal;">
                        <input type="radio" name="tor_input_mode" value="type"
                            <?= empty($edit_tor['tor_file_path']) ? 'checked' : '' ?>
                            onchange="toggleTorMode('type')">
                        <span>Type / Paste Content</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:normal;">
                        <input type="radio" name="tor_input_mode" value="upload"
                            <?= !empty($edit_tor['tor_file_path']) ? 'checked' : '' ?>
                            onchange="toggleTorMode('upload')">
                        <span>Upload File</span>
                    </label>
                </div>
            </div>

            <!-- Type mode -->
            <div id="torTypeMode" class="form-group" <?= !empty($edit_tor['tor_file_path']) ? 'style="display:none;"' : '' ?>>
                <label>Terms of Reference Content</label>
                <textarea name="tor_content" class="form-control" rows="12" style="font-size:0.85rem;font-family:monospace;"><?= htmlspecialchars($edit_tor['tor_content']) ?></textarea>
            </div>

            <!-- Upload mode -->
            <div id="torUploadMode" class="form-group" <?= empty($edit_tor['tor_file_path']) ? 'style="display:none;"' : '' ?>>
                <label>Upload TOR File <small style="color:var(--neutral-400);">PDF, DOC, DOCX, JPG, PNG</small></label>
                <?php if (!empty($edit_tor['tor_file_path'])): ?>
                <div style="margin-bottom:10px;padding:10px 14px;background:var(--neutral-50);border-radius:8px;font-size:0.85rem;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-file" style="color:var(--primary-500);"></i>
                    <span>Current file: <a href="<?= BASE_URL ?>uploads/<?= htmlspecialchars($edit_tor['tor_file_path']) ?>" target="_blank">View current file</a></span>
                </div>
                <?php endif; ?>
                <div class="file-upload">
                    <input type="file" name="tor_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" id="torFileInput">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p class="file-label">Upload new file to replace current</p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Payment for Inspection (₱)</label>
                    <input type="number" name="payment_for_inspection" id="paymentForInspection" class="form-control" step="0.01"
                        value="<?= $edit_tor['payment_for_inspection'] ?>">
                </div>
                <div class="form-group">
                    <label>Inspection Fees (₱)</label>
                    <input type="number" name="inspection_fees" id="inspectionFees" class="form-control" step="0.01"
                        value="<?= $edit_tor['inspection_fees'] ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Auditors for Inspection</label>
                <div class="table-responsive">
                    <table class="table" style="margin-bottom:10px;">
                        <thead>
                            <tr><th style="width:22%;">Role</th><th>Auditor</th><th style="width:22%;">Amount</th><th style="width:60px;"></th></tr>
                        </thead>
                        <tbody id="auditorRows"></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline" onclick="addAuditorRow()">
                    <i class="fas fa-plus"></i> Add Auditor
                </button>
                <p class="form-text">These rows start from the enterprise standard, but you can change the number of auditors and each amount.</p>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Update & Resend TOR
            </button>
        </form>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
const technicalAuditors = <?= json_encode($technical_auditors) ?>;
const shariahAuditors = <?= json_encode($shariah_auditors) ?>;
const initialAuditorAssignments = <?= json_encode($edit_assignments ?? []) ?>;
const enterpriseDefaults = <?= json_encode(array_reduce(array_keys(getEnterpriseStandards()), function ($defaults, $type) {
    $defaults[$type] = [
        'summary' => getEnterpriseStandardSummary($type),
        'assignments' => buildDefaultAuditorAssignments($type),
    ];
    return $defaults;
}, [])) ?>;

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function getAuditorsForRole(role) {
    return role === 'technical' ? technicalAuditors : shariahAuditors;
}

function buildAuditorOptions(role, selectedId) {
    const auditors = getAuditorsForRole(role);
    let options = '<option value="">Select auditor...</option>';
    auditors.forEach((auditor) => {
        const selected = String(auditor.id) === String(selectedId || '') ? 'selected' : '';
        options += `<option value="${auditor.id}" ${selected}>${escapeHtml(auditor.full_name)}</option>`;
    });
    return options;
}

function addAuditorRow(assignment = {}) {
    const rows = document.getElementById('auditorRows');
    if (!rows) return;

    const role = assignment.role || 'shariah';
    const auditorId = assignment.auditor_id || '';
    const amount = Number(assignment.amount || 0).toFixed(2);
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="auditor_role[]" class="form-control auditor-role" onchange="refreshAuditorOptions(this); updateAuditorTotal();">
                <option value="shariah" ${role === 'shariah' ? 'selected' : ''}>Shariah</option>
                <option value="technical" ${role === 'technical' ? 'selected' : ''}>Technical</option>
            </select>
        </td>
        <td>
            <select name="auditor_id[]" class="form-control auditor-select">
                ${buildAuditorOptions(role, auditorId)}
            </select>
        </td>
        <td>
            <input type="number" name="auditor_amount[]" class="form-control auditor-amount" step="0.01" min="0" value="${amount}" oninput="updateAuditorTotal()">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline" onclick="this.closest('tr').remove(); updateAuditorTotal();" title="Remove auditor">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    rows.appendChild(tr);
    updateAuditorTotal();
}

function refreshAuditorOptions(roleSelect) {
    const row = roleSelect.closest('tr');
    const auditorSelect = row?.querySelector('.auditor-select');
    if (auditorSelect) {
        auditorSelect.innerHTML = buildAuditorOptions(roleSelect.value, '');
    }
}

function renderAuditorRows(assignments) {
    const rows = document.getElementById('auditorRows');
    if (!rows) return;
    rows.innerHTML = '';
    (assignments || []).forEach((assignment) => addAuditorRow(assignment));
    updateAuditorTotal();
}

function updateAuditorTotal() {
    const total = Array.from(document.querySelectorAll('.auditor-amount')).reduce((sum, input) => {
        return sum + Number(input.value || 0);
    }, 0);
    const payment = document.getElementById('paymentForInspection');
    const fees = document.getElementById('inspectionFees');
    if (payment) payment.value = total.toFixed(2);
    if (fees) fees.value = total.toFixed(2);
    const display = document.getElementById('auditorTotalDisplay');
    if (display) display.textContent = '\u20b1' + total.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function applyEnterpriseStandard(type) {
    const standard = enterpriseDefaults[type];
    const summaryBox = document.getElementById('torEnterpriseSummary');
    if (summaryBox) {
        summaryBox.textContent = standard?.summary || 'Select a classification to load the standard auditor plan.';
    }
    renderAuditorRows(standard?.assignments || []);
}

const enterpriseLabels = {};
Object.keys(enterpriseDefaults).forEach(type => {
    const opt = document.querySelector(`#torEnterpriseType option[value="${type}"]`);
    if (opt) enterpriseLabels[type] = opt.textContent.trim();
});

const applicationSelect = document.querySelector('select[name="application_id"]');
if (applicationSelect && !applicationSelect.closest('form')?.querySelector('input[name="application_id"][type="hidden"]')) {
    applicationSelect.addEventListener('change', () => {
        const option = applicationSelect.selectedOptions[0];
        const enterpriseType = option?.dataset.enterpriseType || '';
        const summary = option?.dataset.enterpriseSummary || 'Select an application to load the standard auditor plan.';
        const assignments = option?.dataset.auditors ? JSON.parse(option.dataset.auditors) : [];

        const hiddenInput   = document.getElementById('torEnterpriseTypeValue');
        const dropdownGroup = document.getElementById('torEnterpriseTypeDropdownGroup');
        const readonlyGroup = document.getElementById('torEnterpriseTypeReadonlyGroup');
        const readonlyLabel = document.getElementById('torEnterpriseTypeLabel');
        const dropdown      = document.getElementById('torEnterpriseType');
        const summaryBox    = document.getElementById('torEnterpriseSummary');

        if (enterpriseType) {
            // Already classified — show read-only, hide dropdown
            if (hiddenInput) hiddenInput.value = enterpriseType;
            if (dropdown)    dropdown.required  = false;
            if (dropdownGroup) dropdownGroup.style.display = 'none';
            if (readonlyGroup) readonlyGroup.style.display = 'block';
            if (readonlyLabel) readonlyLabel.textContent   = enterpriseLabels[enterpriseType] || enterpriseType;
        } else {
            // Unclassified — show dropdown
            if (hiddenInput) hiddenInput.value = '';
            if (dropdown)    { dropdown.value = ''; dropdown.required = true; }
            if (dropdownGroup) dropdownGroup.style.display = 'block';
            if (readonlyGroup) readonlyGroup.style.display = 'none';
        }

        if (summaryBox) summaryBox.textContent = summary;
        renderAuditorRows(assignments);
    });
}

if (initialAuditorAssignments.length > 0) {
    renderAuditorRows(initialAuditorAssignments);
}

function toggleTorMode(mode) {
    document.getElementById('torTypeMode').style.display  = mode === 'type'   ? 'block' : 'none';
    document.getElementById('torUploadMode').style.display = mode === 'upload' ? 'block' : 'none';
    if (mode === 'upload') {
        const ta = document.querySelector('textarea[name="tor_content"]');
        if (ta) ta.value = '';
    } else {
        const fi = document.getElementById('torFileInput');
        if (fi) fi.value = '';
    }
}
</script>
