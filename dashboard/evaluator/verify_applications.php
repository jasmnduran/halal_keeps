<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_EVALUATOR]);

$page_title = 'Verify Applications';
$page_heading = 'Verify Applications';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/evaluator/'], ['label' => 'Verify Applications']];
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$error = ''; $success = '';

// ── Handle per-file review ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_review'])) {
    $app_id       = intval($_POST['application_id']);
    // IMPORTANT: requirement_label is a DB key and must match exactly.
    // Do NOT HTML-encode it (sanitization would break apostrophes like "Mayor's Permit").
    $req_label    = trim($_POST['requirement_label'] ?? '');
    $review_status = sanitize($_POST['review_status']); // 'accepted' or 'rejected'
    $remarks      = sanitize($_POST['remarks'] ?? '');

    if ($review_status === 'rejected' && empty($remarks)) {
        $error = 'Remarks are required when rejecting a file.';
    } else {
        /*
         * application_requirement_reviews schema:
         * - reviewed_by (NOT evaluator_id)
         * - reviewed_at (timestamp)
         *
         * There is no unique constraint on (application_id, requirement_label, reviewed_by),
         * so we do UPDATE-first to avoid creating duplicate rows.
         */
        $upd = $conn->prepare("
            UPDATE application_requirement_reviews
            SET review_status = ?,
                remarks = ?,
                reviewed_by = ?,
                reviewed_at = NOW()
            WHERE application_id = ?
              AND requirement_label = ?
              AND reviewed_by = ?
        ");
        if (!$upd) {
            $error = 'Failed to prepare review update: ' . $conn->error;
        } else {
            $upd->bind_param(
                "ssiisi",
                $review_status,
                $remarks,
                $user_id,
                $app_id,
                $req_label,
                $user_id
            );
        }

        $executed_update = false;
        if (empty($error)) {
            $executed_update = $upd->execute();
            if ($executed_update === false) {
                $error = 'Failed to update review: ' . $upd->error;
            }
        }

        // If no existing row for this reviewer+requirement, insert a new one.
        if (empty($error) && ($executed_update && $upd->affected_rows > 0)) {
            $success = 'File review saved.';
        } else {
            $ins = $conn->prepare("
                INSERT INTO application_requirement_reviews
                    (application_id, requirement_label, review_status, remarks, reviewed_by, reviewed_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            if (!$ins) {
                $error = 'Failed to prepare review insert: ' . $conn->error;
            } else {
                $ins->bind_param("isssi", $app_id, $req_label, $review_status, $remarks, $user_id);
                if ($ins->execute()) {
                    $success = 'File review saved.';
                } else {
                    $error = 'Failed to save review: ' . $conn->error;
                }
            }
        }
    }
    $action = 'review';
    // Keep on review page
    header('Location: ?action=review&id=' . intval($_POST['application_id']));
    exit();
}

// ── Handle overall application verification ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verification_status'])) {
    $app_id   = intval($_POST['application_id']);
    $status   = sanitize($_POST['verification_status']);
    $feedback = sanitize($_POST['feedback'] ?? '');
    $enterprise_type = normalizeEnterpriseType($_POST['enterprise_type'] ?? '');

    if ($status === 'returned' && empty($feedback)) {
        $error = 'Remarks are required when returning an application.';
    } elseif ($status === 'verified' && empty($enterprise_type)) {
        $error = 'Please classify the business as a micro, small, or medium enterprise before approving.';
    } else {
        // Prevent approving unless the evaluator has reviewed ALL REQUIRED files.
        if ($status === 'verified') {
            // Keep these labels in sync with `dashboard/business_owner/applications.php`.
            $optional_labels = [
                '1.12 Previous Halal Certificate from HDIP (if applicable)',
                '2.1 GMP, HACCP, SSOP, GHP, TQM, ISO Certificates (if available)',
                '4.3 Proof of Dedicated Halal Prayer Room',
            ];
            $requirement_labels = [
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
                '1.12 Previous Halal Certificate from HDIP (if applicable)',
                '2.1 GMP, HACCP, SSOP, GHP, TQM, ISO Certificates (if available)',
                '2.2 Halal Assurance System (HAS) Manual',
                '2.3 Waste Disposal Management Plan',
                '2.4 Pest Control Program',
                '2.5 Kitchen Layout',
                '2.6 Flow Chart of Product Processing',
                '3.1 Full List of Products / Menu with Corresponding Ingredients',
                '3.2 Raw Materials / Ingredients Matrix with Sources (Local or Imported)',
                '3.3 Packaging Materials List with Corresponding Halal Certificates',
                '4.1 Halal Certificates for All Raw Materials (especially meat products)',
                '4.2 Appointment of at Least 2 Muslim Cooks and 2 Muslim Crew Members',
                '4.3 Proof of Dedicated Halal Prayer Room',
                '4.4 Alcohol and Liquor Prohibition Compliance in the Kitchen',
                '4.5 Warehouse Halal Certificate and Storage System Description',
                '4.6 Transportation Details for Halal Products',
            ];
            $required_labels = array_values(array_diff($requirement_labels, $optional_labels));
            $required_count = count($required_labels);

            // Enforce: evaluator must review ALL uploaded files before overall approval.
            // Also enforce: all REQUIRED labels must be accepted (no required rejection).
            // Note: requirement_label is a DB key. We support legacy combined label by aliasing it in-memory.
            $legacy_kitchen_flow_label = '2.5 Kitchen Layout and Flow Chart of Product Processing';
            $kitchen_layout_label      = '2.5 Kitchen Layout';
            $flow_chart_label          = '2.6 Flow Chart of Product Processing';

            $uploads = [];
            $uf = $conn->prepare("SELECT requirement_label, file_path, uploaded_at FROM application_requirement_uploads WHERE application_id = ?");
            if ($uf) {
                $uf->bind_param("i", $app_id);
                $uf->execute();
                foreach ($uf->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
                    $uploads[$row['requirement_label']] = $row;
                }
            } else {
                $error = 'Failed to load uploaded files for validation: ' . $conn->error;
            }

            $reviews = [];
            $rv = $conn->prepare("
                SELECT requirement_label, review_status, remarks, reviewed_at, reviewed_by
                FROM application_requirement_reviews
                WHERE application_id = ?
                  AND reviewed_by = ?
            ");
            if (empty($error) && $rv) {
                $rv->bind_param("ii", $app_id, $user_id);
                $rv->execute();
                foreach ($rv->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
                    $reviews[$row['requirement_label']] = $row;
                }
            } elseif (empty($error)) {
                $error = 'Failed to load evaluator reviews for validation: ' . $conn->error;
            }

            if (empty($error)) {
                // Legacy mapping (uploads)
                if (isset($uploads[$legacy_kitchen_flow_label])) {
                    if (!isset($uploads[$kitchen_layout_label])) {
                        $uploads[$kitchen_layout_label] = $uploads[$legacy_kitchen_flow_label];
                    }
                    if (!isset($uploads[$flow_chart_label])) {
                        $uploads[$flow_chart_label] = $uploads[$legacy_kitchen_flow_label];
                    }
                }

                // Legacy mapping (reviews)
                if (isset($reviews[$legacy_kitchen_flow_label])) {
                    if (!isset($reviews[$kitchen_layout_label])) {
                        $reviews[$kitchen_layout_label] = $reviews[$legacy_kitchen_flow_label];
                    }
                    if (!isset($reviews[$flow_chart_label])) {
                        $reviews[$flow_chart_label] = $reviews[$legacy_kitchen_flow_label];
                    }
                }

                $uploaded_labels = array_values(array_intersect(array_keys($uploads), $requirement_labels));
                if (empty($uploaded_labels)) {
                    $error = 'No uploaded requirement files were found to validate.';
                } else {
                    // Ensure each uploaded file has a review decision (accepted/rejected)
                    foreach ($uploaded_labels as $lbl) {
                        if (!isset($reviews[$lbl]) || !in_array($reviews[$lbl]['review_status'], ['accepted', 'rejected'], true)) {
                            $error = 'You cannot approve this application yet. Please review all uploaded files first.';
                            break;
                        }
                    }

                    // Ensure all required labels are accepted
                    if (empty($error)) {
                        foreach ($required_labels as $lbl) {
                            if (!isset($reviews[$lbl]) || $reviews[$lbl]['review_status'] !== 'accepted') {
                                $error = 'You cannot approve this application because at least one required file was rejected. Use Return / Incomplete instead.';
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($error)) {
            // Block approval and show error on the same page.
            // (Do not insert into application_verification / update application status.)
        } else {
        $stmt = $conn->prepare("INSERT INTO application_verification
            (application_id, evaluator_id, verification_status, feedback, verified_at)
            VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiss", $app_id, $user_id, $status, $feedback);

        if ($stmt->execute()) {
            $new_status = $status === 'verified' ? 'verified' : 'incomplete';
            if ($status === 'verified') {
                $upd = $conn->prepare("UPDATE hdp_applications SET status = ?, enterprise_type = ? WHERE id = ?");
                $upd->bind_param("ssi", $new_status, $enterprise_type, $app_id);
            } else {
                $upd = $conn->prepare("UPDATE hdp_applications SET status = ? WHERE id = ?");
                $upd->bind_param("si", $new_status, $app_id);
            }
            $upd->execute();

            $owner_q = $conn->prepare("SELECT business_owner_id FROM hdp_applications WHERE id = ?");
            $owner_q->bind_param("i", $app_id);
            $owner_q->execute();
            $owner = $owner_q->get_result()->fetch_assoc();
            if ($owner) {
                $msg = $status === 'verified'
                    ? 'Your HDP application has been approved.'
                    : 'Your HDP application has been returned. Please check the evaluator\'s feedback and resubmit.';
                createNotification($conn, $owner['business_owner_id'],
                    'Application ' . ($status === 'verified' ? 'Approved' : 'Returned'),
                    $msg,
                    $status === 'verified' ? 'success' : 'warning',
                    BASE_URL . 'dashboard/business_owner/applications.php');
            }
            logActivity($conn, $user_id, 'Application Verified', 'App #' . $app_id . ' ' . $status, 'evaluation');
            $success = 'Application ' . $status . '.';
            $action = 'list';
        } else {
            $error = 'Failed to save: ' . $conn->error;
        }
        }
    }
}

$filter = $_GET['filter'] ?? 'submitted';

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= $error ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<?php
$where = $filter === 'all' ? '' : "WHERE ha.status = '$filter'";
$apps = $conn->query("
    SELECT ha.*, loi.company_name, u.full_name as owner_name,
        (SELECT COUNT(*) FROM application_verification av WHERE av.application_id = ha.id AND av.verification_status = 'returned') as was_returned
    FROM hdp_applications ha
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    JOIN users u ON ha.business_owner_id = u.id
    $where ORDER BY ha.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="display: flex; gap: 8px;">
        <a href="?filter=submitted" class="btn btn-sm <?= $filter === 'submitted' ? 'btn-primary' : 'btn-secondary' ?>">Pending</a>
        <a href="?filter=verified" class="btn btn-sm <?= $filter === 'verified' ? 'btn-primary' : 'btn-secondary' ?>">Verified</a>
        <a href="?filter=incomplete" class="btn btn-sm <?= $filter === 'incomplete' ? 'btn-primary' : 'btn-secondary' ?>">Returned</a>
        <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>HDP Applications</h3></div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($apps)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-clipboard-check"></i></div><h3>No Applications</h3></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Company</th><th>Owner</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($apps as $app): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($app['company_name']) ?></strong></td>
                            <td><?= htmlspecialchars($app['owner_name']) ?></td>
                            <td>
                                <?= getStatusBadge($app['status']) ?>
                                <?php if ($app['status'] === 'submitted' && $app['was_returned'] > 0): ?>
                                    <span class="badge badge-info" style="margin-left:4px;font-size:0.72rem;">
                                        <i class="fas fa-redo"></i> Revised
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.85rem;color:var(--neutral-500)"><?= $app['submitted_at'] ? formatDate($app['submitted_at']) : '-' ?></td>
                            <td>
                                <a href="?action=review&id=<?= $app['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Review
                                </a>
                            </td>
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
$app_id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT ha.*, loi.company_name, loi.company_address, loi.contact_person, loi.contact_phone, u.full_name as owner_name, u.email as owner_email FROM hdp_applications ha JOIN letter_of_intent loi ON ha.loi_id = loi.id JOIN users u ON ha.business_owner_id = u.id WHERE ha.id = ?");
$stmt->bind_param("i", $app_id);
$stmt->execute();
$app = $stmt->get_result()->fetch_assoc();

// Backward compatibility: previously this app used one combined label.
// If DB still contains that legacy label, treat it as BOTH new requirements.
$legacy_kitchen_flow_label = '2.5 Kitchen Layout and Flow Chart of Product Processing';
$kitchen_layout_label      = '2.5 Kitchen Layout';
$flow_chart_label          = '2.6 Flow Chart of Product Processing';

// Load uploaded files
$uploads = [];
if ($app) {
    $uf = $conn->prepare("SELECT * FROM application_requirement_uploads WHERE application_id = ?");
    $uf->bind_param("i", $app_id);
    $uf->execute();
    foreach ($uf->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $uploads[$row['requirement_label']] = $row;
    }

    // Legacy label mapping (uploads)
    if (isset($uploads[$legacy_kitchen_flow_label])) {
        if (!isset($uploads[$kitchen_layout_label])) {
            $uploads[$kitchen_layout_label] = $uploads[$legacy_kitchen_flow_label];
        }
        if (!isset($uploads[$flow_chart_label])) {
            $uploads[$flow_chart_label] = $uploads[$legacy_kitchen_flow_label];
        }
    }
}

// Load existing file reviews
$reviews = [];
if ($app) {
    $rv = $conn->prepare("SELECT * FROM application_requirement_reviews WHERE application_id = ?");
    $rv->bind_param("i", $app_id);
    $rv->execute();
    foreach ($rv->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $reviews[$row['requirement_label']] = $row;
    }

    // Legacy label mapping (reviews)
    if (isset($reviews[$legacy_kitchen_flow_label])) {
        if (!isset($reviews[$kitchen_layout_label])) {
            $reviews[$kitchen_layout_label] = $reviews[$legacy_kitchen_flow_label];
        }
        if (!isset($reviews[$flow_chart_label])) {
            $reviews[$flow_chart_label] = $reviews[$legacy_kitchen_flow_label];
        }
    }
}

// Keep requirement labels in sync with the Business Owner's upload requirements.
// These values are used as DB keys in `application_requirement_uploads` and must match exactly.
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

$required_labels = array_values(array_diff($requirement_labels, $optional_labels));
$total_required   = count($required_labels);

$accepted_required_count = 0;
$rejected_required_count = 0;
foreach ($reviews as $label => $r) {
    if (!in_array($label, $required_labels, true)) continue;
    if ($r['review_status'] === 'accepted') $accepted_required_count++;
    if ($r['review_status'] === 'rejected') $rejected_required_count++;
}

$uploaded_required_count = count(array_filter(
    $uploads,
    fn($_u, $label) => in_array($label, $required_labels, true),
    ARRAY_FILTER_USE_BOTH
));
$pending_required_count = max(0, $uploaded_required_count - $accepted_required_count - $rejected_required_count);
?>
<?php if ($app): ?>
<a href="?action=list&filter=<?= $filter ?>" class="btn btn-outline btn-sm" style="margin-bottom:20px;"><i class="fas fa-arrow-left"></i> Back</a>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    <!-- Files + per-file review -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-folder-open" style="color:var(--primary-600);margin-right:8px;"></i>
                <?= htmlspecialchars($app['company_name']) ?> — Submitted Requirements
            </h3>
            <?= getStatusBadge($app['status']) ?>
        </div>
        <div class="card-body" style="padding:0;">
            <table class="table" style="margin:0;">
                <thead>
                    <tr>
                        <th style="width:40%;">Requirement</th>
                        <th>File</th>
                        <th>Review Status</th>
                        <?php if ($app['status'] === 'submitted'): ?>
                        <th style="width:200px;">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requirement_labels as $i => $label): ?>
                    <?php
                        $file   = $uploads[$label] ?? null;
                        $review = $reviews[$label] ?? null;
                        $is_optional = in_array($label, $optional_labels, true);
                    ?>
                    <tr id="row-<?= $i ?>">
                        <td style="font-size:0.88rem;color:var(--neutral-700);vertical-align:middle;">
                            <?= htmlspecialchars($label) ?>
                            <?php if ($is_optional): ?>
                                <span style="display:inline-flex;align-items:center;gap:3px;margin-left:8px;background:#f1f5f9;color:#64748b;font-size:0.68rem;font-weight:600;padding:2px 7px;border-radius:99px;vertical-align:middle;">
                                    <i class="fas fa-star" style="font-size:0.5rem;color:#94a3b8;"></i> Optional
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="vertical-align:middle;">
                            <?php if ($file): ?>
                                <a href="<?= BASE_URL ?>uploads/<?= $file['file_path'] ?>" target="_blank"
                                   class="btn btn-sm btn-outline" style="font-size:0.78rem;">
                                    <i class="fas fa-file"></i> View
                                </a>
                            <?php else: ?>
                                <span style="font-size:0.8rem;color:var(--neutral-300);">Not uploaded</span>
                            <?php endif; ?>
                        </td>
                        <td id="reviewStatus-<?= $i ?>" style="vertical-align:middle;">
                            <?php if ($review): ?>
                                <?php if ($review['review_status'] === 'accepted'): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Accepted</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><i class="fas fa-times"></i> Rejected</span>
                                    <?php if ($review['remarks']): ?>
                                        <br><small style="color:var(--neutral-500);font-size:0.75rem;" title="<?= htmlspecialchars($review['remarks']) ?>">
                                            <i class="fas fa-comment"></i> <?= htmlspecialchars(mb_strimwidth($review['remarks'], 0, 40, '…')) ?>
                                        </small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size:0.8rem;color:var(--neutral-300);">—</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($app['status'] === 'submitted'): ?>
                        <td style="vertical-align:middle;">
                            <?php if ($file): ?>
                            <?php if ($review && $review['review_status'] === 'accepted'): ?>
                                <!-- Already accepted — no action -->
                                <span style="font-size:0.78rem;color:var(--neutral-400);">—</span>
                            <?php else: ?>
                            <div style="display:flex;gap:6px;align-items:center;">
                                <!-- Accept button -->
                                <form method="POST" class="file-review-form" data-index="<?= $i ?>" style="display:inline;">
                                    <input type="hidden" name="file_review" value="1">
                                    <input type="hidden" name="application_id" value="<?= $app_id ?>">
                                    <input type="hidden" name="requirement_label" value="<?= htmlspecialchars($label) ?>">
                                    <input type="hidden" name="review_status" value="accepted">
                                    <input type="hidden" name="remarks" value="">
                                    <button type="submit" class="btn btn-sm btn-success"
                                        title="Accept this file"
                                        style="padding:4px 10px;">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <!-- Reject button — opens inline remarks -->
                                <button type="button" class="btn btn-sm btn-danger"
                                    title="Reject this file"
                                    style="padding:4px 10px;"
                                    onclick="toggleRejectForm(<?= $i ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <!-- Reject form (hidden by default) -->
                            <div id="rejectForm-<?= $i ?>" style="display:none;margin-top:8px;">
                                <form method="POST" class="file-review-form" data-index="<?= $i ?>">
                                    <input type="hidden" name="file_review" value="1">
                                    <input type="hidden" name="application_id" value="<?= $app_id ?>">
                                    <input type="hidden" name="requirement_label" value="<?= htmlspecialchars($label) ?>">
                                    <input type="hidden" name="review_status" value="rejected">
                                    <textarea name="remarks" class="form-control" rows="2"
                                        placeholder="Remarks required for rejection..."
                                        style="font-size:0.8rem;margin-bottom:6px;"
                                        id="remarks-<?= $i ?>"></textarea>
                                    <div style="display:flex;gap:6px;">
                                        <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return validateRemarks(<?= $i ?>)">
                                            <i class="fas fa-times-circle"></i> Reject
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline"
                                            onclick="toggleRejectForm(<?= $i ?>)">Cancel</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size:0.78rem;color:var(--neutral-300);">No file</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Verification Panel -->
    <div class="card" style="position:sticky;top:20px;">
        <div class="card-header">
            <h3><i class="fas fa-clipboard-check" style="color:var(--primary-600);margin-right:8px;"></i> Verification</h3>
        </div>
        <div class="card-body">
            <div style="margin-bottom:12px;">
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Company</div>
                <div style="font-weight:600;"><?= htmlspecialchars($app['company_name']) ?></div>
            </div>
            <div style="margin-bottom:12px;">
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Submitted By</div>
                <div><?= htmlspecialchars($app['owner_name']) ?></div>
                <div style="font-size:0.85rem;color:var(--neutral-400);"><?= htmlspecialchars($app['owner_email']) ?></div>
            </div>
            <div style="margin-bottom:20px;">
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">File Review Progress</div>
                <div style="display:flex;gap:12px;font-size:0.85rem;">
                    <span style="color:var(--success);font-weight:600;"><i class="fas fa-check-circle"></i> <span id="acceptedCount"><?= $accepted_required_count ?></span> accepted</span>
                    <span style="color:var(--danger);font-weight:600;"><i class="fas fa-times-circle"></i> <span id="rejectedCount"><?= $rejected_required_count ?></span> rejected</span>
                    <span style="color:var(--neutral-400);"><span id="pendingCount"><?= $pending_required_count ?></span> pending</span>
                </div>
                <div style="background:var(--neutral-100);border-radius:99px;height:6px;overflow:hidden;margin-top:8px;">
                    <div id="reviewProgressBar" style="background:var(--primary-500);height:100%;width:<?= $total_required > 0 ? round(($accepted_required_count / $total_required) * 100) : 0 ?>%;"></div>
                </div>
                <div style="font-size:0.75rem;color:var(--neutral-400);margin-top:4px;"><span id="uploadedRequiredCount"><?= $uploaded_required_count ?></span> / <span id="totalRequiredCount"><?= $total_required ?></span> required files uploaded</div>
            </div>

            <?php if ($app['status'] === 'submitted'): ?>
            <form method="POST" id="overallVerifyForm">
                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                <div class="form-group">
                    <label>Enterprise Classification <span class="required">*</span></label>
                    <select name="enterprise_type" id="enterpriseType" class="form-control" required>
                        <option value="">Select classification...</option>
                        <?php foreach (getEnterpriseStandards() as $type => $standard): ?>
                        <option value="<?= $type ?>" <?= (($app['enterprise_type'] ?? '') === $type) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($standard['label']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-text" id="enterpriseStandardText">
                        <?= htmlspecialchars(getEnterpriseStandardSummary($app['enterprise_type'] ?? '')) ?>
                    </p>
                </div>
                <div class="form-group">
                    <label>Overall Feedback / Remarks</label>
                    <textarea name="feedback" id="appFeedback" class="form-control" rows="4"
                        placeholder="Required if returning — optional for approval..."></textarea>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;margin-top:8px;">
                    <button type="submit" name="verification_status" value="verified" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Approve Application
                    </button>
                    <button type="button" class="btn btn-danger" onclick="submitAppReturn()">
                        <i class="fas fa-times-circle"></i> Return / Incomplete
                    </button>
                </div>
            </form>
            <?php else: ?>
            <div style="text-align:center;"><?= getStatusBadge($app['status']) ?></div>
            <div style="margin-top:12px;text-align:center;font-size:0.86rem;color:var(--neutral-500);">
                <?= htmlspecialchars(getEnterpriseStandardSummary($app['enterprise_type'] ?? '')) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
const enterpriseStandardSummaries = <?= json_encode(array_reduce(array_keys(getEnterpriseStandards()), function ($summaries, $type) {
    $summaries[$type] = getEnterpriseStandardSummary($type);
    return $summaries;
}, [])) ?>;

const enterpriseTypeSelect = document.getElementById('enterpriseType');
if (enterpriseTypeSelect) {
    enterpriseTypeSelect.addEventListener('change', () => {
        const summary = document.getElementById('enterpriseStandardText');
        if (summary) {
            summary.textContent = enterpriseStandardSummaries[enterpriseTypeSelect.value] || 'Select an enterprise classification to apply the standard auditor plan in Terms of Reference.';
        }
    });
}

function toggleRejectForm(i) {
    const form = document.getElementById('rejectForm-' + i);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    if (form.style.display === 'block') {
        document.getElementById('remarks-' + i).focus();
    }
}

function validateRemarks(i) {
    const remarks = document.getElementById('remarks-' + i);
    if (!remarks.value.trim()) {
        remarks.style.borderColor = 'var(--danger)';
        remarks.placeholder = 'Remarks are required for rejection.';
        remarks.focus();
        return false;
    }
    return true;
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function updateReviewCounters(counters) {
    if (!counters) return;

    const acceptedCount = document.getElementById('acceptedCount');
    const rejectedCount = document.getElementById('rejectedCount');
    const pendingCount = document.getElementById('pendingCount');
    const uploadedRequiredCount = document.getElementById('uploadedRequiredCount');
    const totalRequiredCount = document.getElementById('totalRequiredCount');
    const progressBar = document.getElementById('reviewProgressBar');

    if (acceptedCount) acceptedCount.textContent = counters.accepted;
    if (rejectedCount) rejectedCount.textContent = counters.rejected;
    if (pendingCount) pendingCount.textContent = counters.pending;
    if (uploadedRequiredCount) uploadedRequiredCount.textContent = counters.uploaded_required;
    if (totalRequiredCount) totalRequiredCount.textContent = counters.total_required;
    if (progressBar) progressBar.style.width = `${counters.progress_pct}%`;
}

function renderReviewStatus(index, status, remarks) {
    const statusCell = document.getElementById('reviewStatus-' + index);
    if (!statusCell) return;

    if (status === 'accepted') {
        statusCell.innerHTML = '<span class="badge badge-success"><i class="fas fa-check"></i> Accepted</span>';
        return;
    }

    const safeRemarks = escapeHtml(remarks || '');
    const shortRemarks = safeRemarks.length > 40 ? safeRemarks.slice(0, 40) + '...' : safeRemarks;
    statusCell.innerHTML = `
        <span class="badge badge-danger"><i class="fas fa-times"></i> Rejected</span>
        ${safeRemarks ? `<br><small style="color:var(--neutral-500);font-size:0.75rem;" title="${safeRemarks}">
            <i class="fas fa-comment"></i> ${shortRemarks}
        </small>` : ''}
    `;
}

document.querySelectorAll('.file-review-form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const index = form.dataset.index;
        const status = form.querySelector('[name="review_status"]')?.value;
        if (status === 'rejected' && !validateRemarks(index)) {
            return;
        }

        const submitButton = form.querySelector('[type="submit"]');
        const actionCell = form.closest('td');
        const formData = new FormData(form);
        formData.append('action_type', 'file_review');

        if (submitButton) submitButton.disabled = true;

        try {
            const response = await fetch('ajax_verify_application.php', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const result = await response.json();

            if (!result.success) {
                alert(result.message || 'Unable to save the file review.');
                return;
            }

            renderReviewStatus(index, result.review_status, result.remarks);
            updateReviewCounters(result.counters);

            if (result.review_status === 'accepted' && actionCell) {
                actionCell.innerHTML = '<span style="font-size:0.78rem;color:var(--neutral-400);">--</span>';
            } else {
                const rejectForm = document.getElementById('rejectForm-' + index);
                if (rejectForm) rejectForm.style.display = 'none';
            }
        } catch (error) {
            alert('Unable to save the file review. Please try again.');
        } finally {
            if (submitButton) submitButton.disabled = false;
        }
    });
});

function submitAppReturn() {
    const feedback = document.getElementById('appFeedback');
    if (!feedback.value.trim()) {
        feedback.style.borderColor = 'var(--danger)';
        feedback.placeholder = 'Remarks are required when returning an application.';
        feedback.focus();
        let msg = document.getElementById('appFeedbackError');
        if (!msg) {
            msg = document.createElement('p');
            msg.id = 'appFeedbackError';
            msg.style.cssText = 'color:var(--danger);font-size:0.82rem;margin-top:4px;';
            msg.textContent = 'Please provide remarks explaining why the application is being returned.';
            feedback.parentNode.appendChild(msg);
        }
        return;
    }
    // Set hidden input and submit
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'verification_status';
    hidden.value = 'returned';
    document.getElementById('overallVerifyForm').appendChild(hidden);
    document.getElementById('overallVerifyForm').submit();
}
</script>
