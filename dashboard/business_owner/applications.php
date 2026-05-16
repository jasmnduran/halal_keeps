<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$page_title = 'Applications';
$page_heading = 'HDP Application';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/business_owner/'], ['label' => 'Applications']];
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// The fixed requirement labels from the official form
$requirement_labels = [
    // 1. Administrative & Legal Documentation
    '1.1 Letter of Intent',
    '1.2 Company Profile',
    '1.3 SEC Registration / DTI License',
    '1.4 Mayor\'s Permit',
    '1.5 Business Permit',
    '1.6 Barangay Permit',
    '1.7 Sanitary Permit',
    '1.8 Fire Clearance Certificate',
    '1.9 DENR Environment Certificate',
    '1.10 FDA License to Operate (LTO)',
    '1.11 Certificate of Product Registration (CPR)',
    '1.12 Previous Halal Certificate from HDIP (if applicable)',      // OPTIONAL
    // 2. Technical & Quality Management
    '2.1 GMP, HACCP, SSOP, GHP, TQM, ISO Certificates (if available)', // OPTIONAL
    '2.2 Halal Assurance System (HAS) Manual',
    '2.3 Waste Disposal Management Plan',
    '2.4 Pest Control Program',
    '2.5 Kitchen Layout',
    '2.6 Flow Chart of Product Processing',
    // 3. Product & Raw Material Specifications
    '3.1 Full List of Products / Menu with Corresponding Ingredients',
    '3.2 Raw Materials / Ingredients Matrix with Sources (Local or Imported)',
    '3.3 Packaging Materials List with Corresponding Halal Certificates',
    // 4. Halal Compliance & Logistics
    '4.1 Halal Certificates for All Raw Materials (especially meat products)',
    '4.2 Appointment of at Least 2 Muslim Cooks and 2 Muslim Crew Members',
    '4.3 Proof of Dedicated Halal Prayer Room',                        // OPTIONAL
    '4.4 Alcohol and Liquor Prohibition Compliance in the Kitchen',
    '4.5 Warehouse Halal Certificate and Storage System Description',
    '4.6 Transportation Details for Halal Products',
];

// Labels that are optional — upload is encouraged but not required for submission
$optional_labels = [
    '1.12 Previous Halal Certificate from HDIP (if applicable)',
    '2.1 GMP, HACCP, SSOP, GHP, TQM, ISO Certificates (if available)',
    '4.3 Proof of Dedicated Halal Prayer Room',
];

// Required labels = all labels minus optional ones
$required_labels = array_values(array_diff($requirement_labels, $optional_labels));

// Backward compatibility: previously this app used one combined label.
// If DB still contains that legacy label, treat it as BOTH new requirements.
$legacy_kitchen_flow_label = '2.5 Kitchen Layout and Flow Chart of Product Processing';
$kitchen_layout_label      = '2.5 Kitchen Layout';
$flow_chart_label          = '2.6 Flow Chart of Product Processing';

// Get the business owner's verified LOI
$loi_stmt = $conn->prepare("SELECT * FROM letter_of_intent WHERE business_owner_id = ? AND status = 'verified' LIMIT 1");
$loi_stmt->bind_param("i", $user_id);
$loi_stmt->execute();
$verified_loi = $loi_stmt->get_result()->fetch_assoc();

// Get or auto-create the application tied to the verified LOI
$application = null;
if ($verified_loi) {
    $app_stmt = $conn->prepare("SELECT * FROM hdp_applications WHERE business_owner_id = ? AND loi_id = ? LIMIT 1");
    $app_stmt->bind_param("ii", $user_id, $verified_loi['id']);
    $app_stmt->execute();
    $application = $app_stmt->get_result()->fetch_assoc();

    if (!$application) {
        // Auto-create draft application from LOI
        $ins = $conn->prepare("INSERT INTO hdp_applications (loi_id, business_owner_id, status) VALUES (?, ?, 'draft')");
        $ins->bind_param("ii", $verified_loi['id'], $user_id);
        if ($ins->execute()) {
            $app_id = $conn->insert_id;
            $app_stmt2 = $conn->prepare("SELECT * FROM hdp_applications WHERE id = ?");
            $app_stmt2->bind_param("i", $app_id);
            $app_stmt2->execute();
            $application = $app_stmt2->get_result()->fetch_assoc();
        }
    }
}

// Load uploaded files for the application (must be before POST handlers that reference $uploaded_files)
$uploaded_files = [];
if ($application) {
    $uf = $conn->prepare("SELECT * FROM application_requirement_uploads WHERE application_id = ?");
    $uf->bind_param("i", $application['id']);
    $uf->execute();
    foreach ($uf->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $uploaded_files[$row['requirement_label']] = $row;
    }

    // Legacy label mapping (uploads)
    if (isset($uploaded_files[$legacy_kitchen_flow_label])) {
        if (!isset($uploaded_files[$kitchen_layout_label])) {
            $uploaded_files[$kitchen_layout_label] = $uploaded_files[$legacy_kitchen_flow_label];
        }
        if (!isset($uploaded_files[$flow_chart_label])) {
            $uploaded_files[$flow_chart_label] = $uploaded_files[$legacy_kitchen_flow_label];
        }
    }
}

// Load evaluator file reviews
$file_reviews = [];
if ($application) {
    $rv = $conn->prepare("SELECT * FROM application_requirement_reviews WHERE application_id = ?");
    $rv->bind_param("i", $application['id']);
    $rv->execute();
    foreach ($rv->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $file_reviews[$row['requirement_label']] = $row;
    }

    // Legacy label mapping (reviews)
    if (isset($file_reviews[$legacy_kitchen_flow_label])) {
        if (!isset($file_reviews[$kitchen_layout_label])) {
            $file_reviews[$kitchen_layout_label] = $file_reviews[$legacy_kitchen_flow_label];
        }
        if (!isset($file_reviews[$flow_chart_label])) {
            $file_reviews[$flow_chart_label] = $file_reviews[$legacy_kitchen_flow_label];
        }
    }
}

// Handle TOR response from business owner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tor_response']) && $application) {
    $tor_id   = intval($_POST['tor_id']);
    $response = sanitize($_POST['response_action']); // 'accepted' or 'rejected'
    $remarks  = sanitize($_POST['business_remarks'] ?? '');

    $upd = $conn->prepare("UPDATE terms_of_reference SET business_response=?, business_remarks=?, responded_at=NOW() WHERE id=? AND application_id=?");
    $upd->bind_param("ssii", $response, $remarks, $tor_id, $application['id']);
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
                'TOR Response: ' . ucfirst($response),
                $msg,
                $response === 'accepted' ? 'success' : 'warning',
                BASE_URL . 'dashboard/evaluator/terms_of_reference.php?action=view&id=' . $tor_id);
        }
        $success = 'Your response has been submitted.';
    }
}

// Handle file uploads per requirement — now handled by /api/upload_requirement.php (AJAX)
// Kept as fallback for non-JS environments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_requirement']) && $application) {
    $app_id   = intval($application['id']);
    $label    = trim($_POST['requirement_label'] ?? ''); // trim only — must not HTML-encode label used as a DB key
    $allowed  = ['pdf', 'png', 'jpg', 'jpeg'];

    if (isset($_FILES['req_file']) && $_FILES['req_file']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['req_file'], 'documents', $allowed);
        if ($upload['success']) {
            $del = $conn->prepare("DELETE FROM application_requirement_uploads WHERE application_id = ? AND requirement_label = ?");
            $del->bind_param("is", $app_id, $label);
            $del->execute();

            $ins = $conn->prepare("INSERT INTO application_requirement_uploads (application_id, requirement_label, file_path) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $app_id, $label, $upload['path']);
            $ins->execute();

            $clr = $conn->prepare("DELETE FROM application_requirement_reviews WHERE application_id = ? AND requirement_label = ? AND review_status = 'rejected'");
            $clr->bind_param("is", $app_id, $label);
            $clr->execute();

            $success = 'File uploaded for: ' . $label;
        } else {
            $error = $upload['message'];
        }
    } else {
        $error = 'Please select a valid file (PDF, PNG, JPG, JPEG).';
    }
}

// Handle resubmission after fixing rejected files
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resubmit_application']) && $application) {
    $app_id = intval($application['id']);

    // Make sure no files are still in rejected state (count only current requirement labels)
    $still_rejected = 0;
    foreach ($file_reviews as $lbl => $review) {
        if (!in_array($lbl, $requirement_labels, true)) continue;
        if (($review['review_status'] ?? '') === 'rejected') $still_rejected++;
    }

    if ($still_rejected > 0) {
        $error = 'Please re-upload all rejected files before resubmitting.';
    } else {
        $upd = $conn->prepare("UPDATE hdp_applications SET status = 'submitted', submitted_at = NOW() WHERE id = ? AND business_owner_id = ?");
        $upd->bind_param("ii", $app_id, $user_id);
        if ($upd->execute()) {
            $eval_query = $conn->query("SELECT id FROM users WHERE role_id = " . ROLE_EVALUATOR . " AND role_status = 'approved'");
            while ($eval = $eval_query->fetch_assoc()) {
                createNotification($conn, $eval['id'], 'Application Revised & Resubmitted',
                    $_SESSION['full_name'] . ' has revised and resubmitted their HDP application after addressing the evaluator\'s feedback.',
                    'action_required', BASE_URL . 'dashboard/evaluator/verify_applications.php');
            }
            $success = 'Application resubmitted successfully!';
            $app_stmt3 = $conn->prepare("SELECT * FROM hdp_applications WHERE id = ?");
            $app_stmt3->bind_param("i", $app_id);
            $app_stmt3->execute();
            $application = $app_stmt3->get_result()->fetch_assoc();
        }
    }
}

// Handle final submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application']) && $application) {
    $app_id = intval($application['id']);

    // Check that all REQUIRED (non-optional) documents are uploaded
    $uploaded_labels = array_keys($uploaded_files);
    $missing_required = array_diff($required_labels, $uploaded_labels);

    if (count($missing_required) > 0) {
        $missing_count = count($missing_required);
        $error = 'Please upload all ' . count($required_labels) . ' required documents before submitting. '
               . $missing_count . ' required file(s) still missing.';
    } else {
        $upd = $conn->prepare("UPDATE hdp_applications SET status = 'submitted', submitted_at = NOW() WHERE id = ? AND business_owner_id = ?");
        $upd->bind_param("ii", $app_id, $user_id);
        if ($upd->execute()) {
            // Notify evaluators
            $eval_query = $conn->query("SELECT id FROM users WHERE role_id = " . ROLE_EVALUATOR . " AND role_status = 'approved'");
            while ($eval = $eval_query->fetch_assoc()) {
                createNotification($conn, $eval['id'], 'New HDP Application',
                    $_SESSION['full_name'] . ' has submitted an HDP application.',
                    'action_required', BASE_URL . 'dashboard/evaluator/verify_applications.php');
            }
            $success = 'Application submitted successfully!';
            // Refresh
            $app_stmt3 = $conn->prepare("SELECT * FROM hdp_applications WHERE id = ?");
            $app_stmt3->bind_param("i", $app_id);
            $app_stmt3->execute();
            $application = $app_stmt3->get_result()->fetch_assoc();
        }
    }
}

// Load latest overall evaluator feedback
$evaluator_feedback = null;
if ($application) {
    $ef = $conn->prepare("SELECT av.*, u.full_name as evaluator_name FROM application_verification av JOIN users u ON av.evaluator_id = u.id WHERE av.application_id = ? ORDER BY av.verified_at DESC LIMIT 1");
    $ef->bind_param("i", $application['id']);
    $ef->execute();
    $evaluator_feedback = $ef->get_result()->fetch_assoc();
}

// Check if a TOR exists (for the notification banner)
$tor = null;
if ($application) {
    $tor_q = $conn->prepare("SELECT id FROM terms_of_reference WHERE application_id = ? LIMIT 1");
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

<?php if (!$verified_loi): ?>
<div class="empty-state">
    <div class="empty-icon"><i class="fas fa-file-alt"></i></div>
    <h3>No Verified LOI</h3>
    <p>Your Letter of Intent must be approved by an evaluator before you can submit an application.</p>
    <a href="<?= BASE_URL ?>dashboard/business_owner/letter_of_intent.php" class="btn btn-primary">
        <i class="fas fa-arrow-right"></i> Go to Letter of Intent
    </a>
</div>

<?php elseif ($application): ?>

<div style="display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start;">

    <!-- Requirements Upload -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clipboard-list" style="color:var(--primary-600);margin-right:8px;"></i>
                <?= htmlspecialchars($verified_loi['company_name']) ?> — Application Requirements
            </h3>
            <?= getStatusBadge($application['status']) ?>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table" style="margin: 0;">
                <thead>
                    <tr>
                        <th style="width: 40%;">Requirement</th>
                        <th>File</th>
                        <th style="width: 140px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $section_headers = [
                    '1.1 Letter of Intent'                                                   => '1. Administrative & Legal Documentation',
                    '2.1 GMP, HACCP, SSOP, GHP, TQM, ISO Certificates (if available)'      => '2. Technical & Quality Management',
                    '3.1 Full List of Products / Menu with Corresponding Ingredients'        => '3. Product & Raw Material Specifications',
                    '4.1 Halal Certificates for All Raw Materials (especially meat products)'=> '4. Halal Compliance & Logistics',
                ];
                foreach ($requirement_labels as $label):
                    $is_optional  = in_array($label, $optional_labels);
                    $uploaded     = $uploaded_files[$label] ?? null;
                    $review       = $file_reviews[$label] ?? null;
                    $is_rejected  = $review && $review['review_status'] === 'rejected';
                    $is_accepted  = $review && $review['review_status'] === 'accepted';
                    $can_upload   = in_array($application['status'], ['draft', 'incomplete']) || $is_rejected;
                    // Show upload zone when: can upload AND (not yet uploaded OR rejected OR optional-already-uploaded for replace)
                    $show_upload  = $can_upload && (!$uploaded || $is_rejected || $is_optional);
                ?>
                <?php if (isset($section_headers[$label])): ?>
                <tr>
                    <td colspan="3" style="background:var(--primary-50);padding:10px 16px;font-weight:700;font-size:0.8rem;color:var(--primary-800);text-transform:uppercase;letter-spacing:0.04em;border-top:2px solid var(--primary-100);">
                        <i class="fas fa-folder-open" style="margin-right:6px;"></i><?= $section_headers[$label] ?>
                    </td>
                </tr>
                <?php endif; ?>
                    <tr style="<?= $is_optional ? 'background:#fafafa;' : '' ?>"
                        data-required="<?= $is_optional ? '0' : '1' ?>"
                        data-uploaded="<?= $uploaded ? '1' : '0' ?>"
                        data-label="<?= htmlspecialchars($label, ENT_COMPAT) ?>">
                        <td style="font-size:0.88rem;color:var(--neutral-700);vertical-align:middle;">
                            <?= htmlspecialchars($label) ?>
                            <?php if ($is_optional): ?>
                            <span style="display:inline-flex;align-items:center;gap:3px;margin-left:6px;background:#f1f5f9;color:#64748b;font-size:0.68rem;font-weight:600;padding:2px 7px;border-radius:99px;vertical-align:middle;">
                                <i class="fas fa-star" style="font-size:0.5rem;color:#94a3b8;"></i> Optional
                            </span>
                            <?php else: ?>
                            <span style="display:inline-flex;align-items:center;gap:3px;margin-left:6px;background:#dcfce7;color:#166534;font-size:0.68rem;font-weight:600;padding:2px 7px;border-radius:99px;vertical-align:middle;">
                                <i class="fas fa-asterisk" style="font-size:0.5rem;"></i> Required
                            </span>
                            <?php endif; ?>
                        </td>

                        <!-- File column -->
                        <td style="vertical-align:middle;">
                            <?php if ($show_upload): ?>
                            <div class="req-upload-wrap"
                                 data-app-id="<?= $application['id'] ?>"
                                 data-label="<?= htmlspecialchars($label, ENT_COMPAT) ?>"
                                 data-optional="<?= $is_optional ? '1' : '0' ?>">

                                <?php if ($uploaded && $is_optional && !$is_rejected): ?>
                                <!-- Optional already uploaded: file link + replace button -->
                                <div class="req-file-info" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <a href="<?= BASE_URL ?>uploads/<?= $uploaded['file_path'] ?>" target="_blank"
                                       class="req-view-link"
                                       style="font-size:0.83rem;color:var(--primary-600);display:inline-flex;align-items:center;gap:5px;">
                                        <i class="fas fa-file"></i> <span class="req-filename">View file</span>
                                    </a>
                                    <label class="req-replace-btn" style="cursor:pointer;font-size:0.75rem;color:#64748b;display:inline-flex;align-items:center;gap:4px;background:#f1f5f9;padding:3px 9px;border-radius:6px;border:1px solid #e2e8f0;">
                                        <i class="fas fa-sync-alt" style="font-size:0.65rem;"></i> Replace
                                        <input type="file" accept=".pdf,.png,.jpg,.jpeg" style="display:none;" class="req-file-input">
                                    </label>
                                </div>
                                <?php else: ?>
                                <div class="req-dropzone <?= $is_rejected ? 'rejected-dropzone' : ($is_optional ? 'optional-dropzone' : '') ?>"
                                     ondragover="event.preventDefault();this.classList.add('drag-over')"
                                     ondragleave="this.classList.remove('drag-over')"
                                     ondrop="handleReqDrop(event,this)">
                                    <i class="fas fa-cloud-upload-alt req-icon" style="font-size:1.3rem;color:<?= $is_rejected ? 'var(--danger)' : ($is_optional ? '#94a3b8' : 'var(--primary-500)') ?>;margin-bottom:3px;"></i>
                                    <div class="req-dropzone-label" style="font-weight:600;font-size:0.75rem;color:var(--neutral-600);">
                                        <?= $is_rejected ? 'Re-upload file' : ($is_optional ? 'Upload (optional)' : 'Click to upload or drag & drop') ?>
                                    </div>
                                    <div style="font-size:0.7rem;color:var(--neutral-400);margin-top:1px;">PDF, JPG, PNG · max 5MB</div>
                                    <input type="file" accept=".pdf,.png,.jpg,.jpeg" style="display:none;" class="req-file-input">
                                    <!-- Progress bar (hidden until upload starts) -->
                                    <div class="req-progress" style="display:none;width:100%;margin-top:6px;">
                                        <div style="background:#e2e8f0;border-radius:99px;height:4px;overflow:hidden;">
                                            <div class="req-progress-bar" style="background:var(--primary-500);height:100%;width:0%;transition:width .2s;"></div>
                                        </div>
                                        <div class="req-progress-text" style="font-size:0.68rem;color:#64748b;margin-top:3px;text-align:center;">Uploading…</div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Success state (injected by JS after upload) -->
                                <div class="req-success-state" style="display:none;">
                                    <a href="#" target="_blank" class="req-view-link"
                                       style="font-size:0.83rem;color:var(--primary-600);display:inline-flex;align-items:center;gap:5px;">
                                        <i class="fas fa-file"></i> <span class="req-filename">View file</span>
                                    </a>
                                    <?php if ($can_upload): ?>
                                    <label style="cursor:pointer;font-size:0.75rem;color:#64748b;display:inline-flex;align-items:center;gap:4px;background:#f1f5f9;padding:3px 9px;border-radius:6px;border:1px solid #e2e8f0;margin-left:6px;">
                                        <i class="fas fa-sync-alt" style="font-size:0.65rem;"></i> Replace
                                        <input type="file" accept=".pdf,.png,.jpg,.jpeg" style="display:none;" class="req-file-input">
                                    </label>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php elseif ($uploaded): ?>
                                <a href="<?= BASE_URL ?>uploads/<?= $uploaded['file_path'] ?>" target="_blank"
                                   style="font-size:0.85rem;color:var(--primary-600);display:inline-flex;align-items:center;gap:6px;">
                                    <i class="fas fa-file"></i> View file
                                </a>
                                <br><small style="color:var(--neutral-400);"><?= formatDate($uploaded['uploaded_at']) ?></small>
                            <?php else: ?>
                                <span style="font-size:0.8rem;color:var(--neutral-300);">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Status column -->
                        <td style="vertical-align:middle;">
                            <?php if (!$uploaded): ?>
                                <?php if ($is_optional): ?>
                                <span style="font-size:0.75rem;color:#94a3b8;font-style:italic;">Not submitted</span>
                                <?php else: ?>
                                <span style="font-size:0.75rem;color:#cbd5e1;">—</span>
                                <?php endif; ?>
                            <?php elseif ($is_accepted): ?>
                                <span class="badge badge-success" style="font-size:0.75rem;">
                                    <i class="fas fa-check-circle"></i> Accepted
                                </span>
                            <?php elseif ($is_rejected): ?>
                                <span class="badge badge-danger" style="font-size:0.75rem;">
                                    <i class="fas fa-times-circle"></i> Rejected
                                </span>
                                <?php if ($review['remarks']): ?>
                                <br><small style="color:var(--danger);font-size:0.75rem;display:block;margin-top:3px;line-height:1.3;">
                                    <i class="fas fa-comment"></i> <?= htmlspecialchars($review['remarks']) ?>
                                </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-warning" style="font-size:0.75rem;">
                                    <i class="fas fa-clock"></i> Pending review
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Panel -->
    <div class="card" style="position:sticky;top:20px;">
        <div class="card-header">
            <h3><i class="fas fa-info-circle" style="color:var(--primary-600);margin-right:8px;"></i> Summary</h3>
        </div>
        <div class="card-body">
            <div style="margin-bottom:16px;">
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Company</div>
                <div style="font-weight:600;"><?= htmlspecialchars($verified_loi['company_name']) ?></div>
            </div>
            <div style="margin-bottom:16px;">
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Status</div>
                <?= getStatusBadge($application['status']) ?>
            </div>

            <?php if ($evaluator_feedback && $evaluator_feedback['feedback']): ?>
            <div class="alert <?= $evaluator_feedback['verification_status'] === 'returned' ? 'alert-warning' : 'alert-info' ?>" style="margin-bottom:16px;font-size:0.85rem;">
                <i class="fas fa-<?= $evaluator_feedback['verification_status'] === 'returned' ? 'exclamation-triangle' : 'comment' ?>"></i>
                <div>
                    <strong>Evaluator Remarks</strong>
                    <p style="margin-top:4px;"><?= nl2br(htmlspecialchars($evaluator_feedback['feedback'])) ?></p>
                    <small style="color:var(--neutral-400);">By: <?= htmlspecialchars($evaluator_feedback['evaluator_name']) ?> · <?= formatDate($evaluator_feedback['verified_at']) ?></small>
                </div>
            </div>
            <?php endif; ?>

            <?php
                $uploaded_count    = count($uploaded_files);
                $total_all         = count($requirement_labels);
                $total_required    = count($required_labels);
                $uploaded_required = count(array_intersect(array_keys($uploaded_files), $required_labels));
                $uploaded_optional = count(array_intersect(array_keys($uploaded_files), $optional_labels));
                $all_required_done = ($uploaded_required >= $total_required);
                $pending_rejections = count(array_filter($file_reviews, fn($r) => $r['review_status'] === 'rejected'));
            ?>

            <?php if ($application['status'] === 'draft'): ?>
                <!-- Required progress -->
                <div style="margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                        <span style="font-size:0.78rem;color:var(--neutral-500);font-weight:600;text-transform:uppercase;">Required Documents</span>
                        <span id="req-progress-label" style="font-size:0.8rem;font-weight:700;color:<?= $all_required_done ? 'var(--success)' : 'var(--neutral-700)' ?>">
                            <?= $uploaded_required ?> / <?= $total_required ?>
                        </span>
                    </div>
                    <div style="background:var(--neutral-100);border-radius:99px;height:8px;overflow:hidden;">
                        <div id="req-progress-bar" style="background:<?= $all_required_done ? 'var(--success)' : 'var(--primary-500)' ?>;height:100%;width:<?= round($uploaded_required/$total_required*100) ?>%;transition:width .3s;"></div>
                    </div>
                    <span id="req-total" data-total="<?= $total_required ?>" style="display:none;"></span>
                </div>
                <!-- Optional progress -->
                <div style="margin-bottom:20px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                        <span style="font-size:0.78rem;color:#94a3b8;font-weight:600;text-transform:uppercase;">Optional Documents</span>
                        <span style="font-size:0.8rem;color:#94a3b8;"><?= $uploaded_optional ?> / <?= count($optional_labels) ?></span>
                    </div>
                    <div style="background:var(--neutral-100);border-radius:99px;height:6px;overflow:hidden;">
                        <div style="background:#cbd5e1;height:100%;width:<?= count($optional_labels) > 0 ? round($uploaded_optional/count($optional_labels)*100) : 0 ?>%;transition:width .3s;"></div>
                    </div>
                </div>

                <?php if (!$all_required_done): ?>
                <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px 14px;margin-bottom:12px;font-size:0.83rem;color:#92400e;">
                    <i class="fas fa-exclamation-triangle" style="color:#f97316;margin-right:6px;"></i>
                    Upload all <strong><?= $total_required ?></strong> required documents to submit.
                    <strong style="color:var(--danger);"><?= $total_required - $uploaded_required ?> remaining.</strong>
                </div>
                <?php else: ?>
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px 14px;margin-bottom:12px;font-size:0.83rem;color:#166534;">
                    <i class="fas fa-check-circle" style="margin-right:6px;"></i>
                    All required documents uploaded. You may submit.
                </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="submit_application" value="1">
                    <button type="submit" id="submitAppBtn" class="btn btn-primary" style="width:100%;"
                        <?= !$all_required_done ? 'disabled title="Upload all required documents first"' : '' ?>
                        <?= $all_required_done ? 'onclick="return confirm(\'Submit your application to the evaluator? You can still upload optional files after submission if needed.\')"' : '' ?>>
                        <i class="fas fa-paper-plane"></i> Submit Application
                        <?php if (!$all_required_done): ?>(<?= $uploaded_required ?>/<?= $total_required ?> required)<?php endif; ?>
                    </button>
                </form>

            <?php elseif ($application['status'] === 'incomplete'): ?>
                <?php if ($pending_rejections > 0): ?>
                <div style="background:#fff5f5;border:1px solid #fca5a5;border-radius:10px;padding:12px 14px;margin-bottom:12px;font-size:0.85rem;color:var(--neutral-700);">
                    <i class="fas fa-times-circle" style="color:var(--danger);margin-right:6px;"></i>
                    Re-upload <strong><?= $pending_rejections ?></strong> rejected file(s) before resubmitting.
                </div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="resubmit_application" value="1">
                    <button type="submit" class="btn btn-primary" style="width:100%;"
                        <?= $pending_rejections > 0 ? 'disabled title="Re-upload all rejected files first"' : '' ?>
                        <?= $pending_rejections === 0 ? 'onclick="return confirm(\'Resubmit your revised application to the evaluator?\')"' : '' ?>>
                        <i class="fas fa-redo"></i> Resend Application
                    </button>
                </form>

            <?php elseif ($application['status'] === 'submitted'): ?>
            <div class="alert alert-info" style="margin:0;font-size:0.85rem;">
                <i class="fas fa-clock"></i> Application submitted on <?= formatDate($application['submitted_at']) ?>. Awaiting review.
            </div>

            <?php else: ?>
            <div style="text-align:center;"><?= getStatusBadge($application['status']) ?></div>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php endif; ?>

<?php if ($tor): ?>
<!-- TOR moved to its own page — show a link instead -->
<div class="alert alert-info" style="margin-top: 24px;">
    <i class="fas fa-file-contract"></i>
    <div>
        <strong>Terms of Reference Available</strong>
        <p style="margin-top:4px;font-size:0.9rem;">The evaluator has sent you a Terms of Reference. Please review and respond.</p>
        <a href="<?= BASE_URL ?>dashboard/business_owner/terms_of_reference.php" class="btn btn-sm btn-primary" style="margin-top:8px;">
            <i class="fas fa-arrow-right"></i> View Terms of Reference
        </a>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<style>
.req-dropzone {
    border: 2px dashed var(--neutral-300);
    border-radius: 10px;
    padding: 14px 10px;
    text-align: center;
    cursor: pointer;
    background: var(--neutral-50);
    transition: border-color .2s, background .2s;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.req-dropzone:hover, .req-dropzone.drag-over {
    border-color: var(--primary-500);
    background: var(--primary-50);
}
.req-dropzone.rejected-dropzone {
    border-color: var(--danger);
    background: #fff5f5;
}
.req-dropzone.rejected-dropzone:hover, .req-dropzone.rejected-dropzone.drag-over {
    border-color: var(--danger);
    background: #ffe4e4;
}
.req-dropzone.optional-dropzone {
    border-color: #cbd5e1;
    background: #f8fafc;
}
.req-dropzone.optional-dropzone:hover, .req-dropzone.optional-dropzone.drag-over {
    border-color: #94a3b8;
    background: #f1f5f9;
}
</style>
<script>
const UPLOAD_URL    = '<?= BASE_URL ?>api/upload_requirement.php';
const BASE_UPLOADS  = '<?= BASE_URL ?>uploads/';

// ── Wire every upload wrap on page load ──────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.req-upload-wrap').forEach(wireWrap);
});

function wireWrap(wrap) {
    // Click on dropzone opens file picker
    const dropzone = wrap.querySelector('.req-dropzone');
    if (dropzone) {
        dropzone.addEventListener('click', () => {
            wrap.querySelector('.req-file-input')?.click();
        });
    }

    // All file inputs in this wrap trigger upload
    wrap.querySelectorAll('.req-file-input').forEach(input => {
        input.addEventListener('change', () => {
            if (input.files[0]) uploadFile(wrap, input.files[0]);
        });
    });
}

// ── Drag & drop ──────────────────────────────────────────────────────────────
function handleReqDrop(e, dropzone) {
    e.preventDefault();
    dropzone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const wrap = dropzone.closest('.req-upload-wrap');
    uploadFile(wrap, file);
}

// ── Core AJAX upload ─────────────────────────────────────────────────────────
function uploadFile(wrap, file) {
    const appId = wrap.dataset.appId;
    const label = wrap.dataset.label;
    const row   = wrap.closest('tr');

    // Validate type client-side
    const allowed = ['application/pdf','image/png','image/jpeg'];
    if (!allowed.includes(file.type)) {
        showToastMsg('Only PDF, JPG, or PNG files are allowed.', 'error');
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        showToastMsg('File is too large (max 10 MB).', 'error');
        return;
    }

    const fd = new FormData();
    fd.append('req_file',        file);
    fd.append('application_id',  appId);
    fd.append('requirement_label', label);

    // Show progress bar
    const progress    = wrap.querySelector('.req-progress');
    const progressBar = wrap.querySelector('.req-progress-bar');
    const dropzone    = wrap.querySelector('.req-dropzone');
    if (progress) progress.style.display = 'block';
    if (dropzone) dropzone.style.pointerEvents = 'none';

    const xhr = new XMLHttpRequest();
    xhr.open('POST', UPLOAD_URL);

    xhr.upload.addEventListener('progress', e => {
        if (e.lengthComputable && progressBar) {
            progressBar.style.width = Math.round(e.loaded / e.total * 100) + '%';
        }
    });

    xhr.addEventListener('load', () => {
        if (progress) progress.style.display = 'none';
        if (dropzone) dropzone.style.pointerEvents = '';

        let data;
        try { data = JSON.parse(xhr.responseText); }
        catch { data = { success: false, message: 'Server error.' }; }

        if (data.success) {
            onUploadSuccess(wrap, row, data, label);
        } else {
            showToastMsg(data.message || 'Upload failed.', 'error');
            if (progressBar) progressBar.style.width = '0%';
        }
    });

    xhr.addEventListener('error', () => {
        if (progress) progress.style.display = 'none';
        if (dropzone) dropzone.style.pointerEvents = '';
        showToastMsg('Network error. Please try again.', 'error');
    });

    xhr.send(fd);
}

// ── Update the row UI after a successful upload ───────────────────────────────
function onUploadSuccess(wrap, row, data, label) {
    const fileUrl = BASE_UPLOADS + data.file_path;

    // Hide the dropzone, show the success state
    const dropzone     = wrap.querySelector('.req-dropzone');
    const fileInfo     = wrap.querySelector('.req-file-info');
    const successState = wrap.querySelector('.req-success-state');

    if (dropzone)     dropzone.style.display     = 'none';
    if (fileInfo)     fileInfo.style.display      = 'none';
    if (successState) {
        successState.style.display = 'flex';
        successState.style.alignItems = 'center';
        successState.style.gap = '8px';
        const link = successState.querySelector('.req-view-link');
        if (link) { link.href = fileUrl; }
        // Wire the replace input inside success state
        successState.querySelectorAll('.req-file-input').forEach(inp => {
            inp.addEventListener('change', () => {
                if (inp.files[0]) uploadFile(wrap, inp.files[0]);
            });
        });
    }

    // Update the status cell
    const statusCell = row.querySelector('td:last-child');
    if (statusCell) {
        statusCell.innerHTML = `<span class="badge badge-warning" style="font-size:0.75rem;">
            <i class="fas fa-clock"></i> Pending review
        </span>`;
    }

    // Mark this row as uploaded so the counter is accurate
    if (row) row.dataset.uploaded = '1';

    // Update the required-docs progress bar in the summary panel
    updateProgressBar();

    showToastMsg('File uploaded successfully.', 'success');
}

// ── Recalculate required progress bar without reload ─────────────────────────
function updateProgressBar() {
    // Count required rows that have data-uploaded="1"
    const requiredTotal    = parseInt(document.getElementById('req-total')?.dataset.total || 0);
    const requiredUploaded = document.querySelectorAll('tr[data-required="1"][data-uploaded="1"]').length;

    const bar   = document.getElementById('req-progress-bar');
    const label = document.getElementById('req-progress-label');
    const submitBtn = document.getElementById('submitAppBtn');

    if (bar)   bar.style.width = requiredTotal > 0 ? Math.round(requiredUploaded / requiredTotal * 100) + '%' : '0%';
    if (label) label.textContent = requiredUploaded + ' / ' + requiredTotal;

    if (submitBtn) {
        if (requiredUploaded >= requiredTotal) {
            submitBtn.disabled = false;
            submitBtn.title = '';
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Application';
        } else {
            submitBtn.disabled = true;
            submitBtn.title = 'Upload all required documents first';
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Application (' + requiredUploaded + '/' + requiredTotal + ' required)';
        }
    }
}

// ── Toast helper ─────────────────────────────────────────────────────────────
function showToastMsg(msg, type) {
    const colors = { success: '#22c55e', error: '#ef4444', info: '#3b82f6' };
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:9999;background:#1e293b;color:#fff;
        padding:12px 20px;border-radius:10px;font-size:.85rem;font-weight:600;
        box-shadow:0 8px 24px rgba(0,0,0,.25);display:flex;align-items:center;gap:10px;
        border-left:4px solid ${colors[type] || colors.info};max-width:340px;`;
    t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle'}"
        style="color:${colors[type] || colors.info}"></i>${msg}`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 4000);
}
</script>
