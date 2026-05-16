<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_AUDITOR_TECHNICAL, ROLE_AUDITOR_SHARIAH]);

$page_title    = 'Inspections';
$page_heading  = 'Conduct Inspections';
$is_dashboard  = true;
$breadcrumbs   = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/auditor/'], ['label' => 'Inspections']];
$user_id       = $_SESSION['user_id'];
$role_id       = $_SESSION['role_id'];
$error         = '';
$success       = '';

// Which DB columns belong to this auditor
$is_technical   = ($role_id == ROLE_AUDITOR_TECHNICAL);
$auditor_field  = $is_technical ? 'auditor_technical_id'   : 'auditor_shariah_id';
$findings_field = $is_technical ? 'tech_audit_findings'    : 'shariah_audit_findings';
$conform_field  = $is_technical ? 'tech_conformity_status' : 'shariah_conformity_status';
$remarks_field  = $is_technical ? 'tech_remarks'           : 'shariah_remarks';
$done_field     = $is_technical ? 'tech_completed_at'      : 'shariah_completed_at';
$auditor_label  = $is_technical ? 'Technical'              : 'Shariah';

// ── Ensure per-auditor columns exist (idempotent) ─────────────────────────────
$conn->query("
    ALTER TABLE inspections
        ADD COLUMN IF NOT EXISTS tech_audit_findings      TEXT          DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS tech_conformity_status   ENUM('conforming','non_conforming','partial') DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS tech_remarks             TEXT          DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS tech_completed_at        TIMESTAMP     NULL DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS shariah_audit_findings   TEXT          DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS shariah_conformity_status ENUM('conforming','non_conforming','partial') DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS shariah_remarks          TEXT          DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS shariah_completed_at     TIMESTAMP     NULL DEFAULT NULL
");

// ── Document checklist definition ─────────────────────────────────────────────
$document_checklist = [
    ['key' => 'business_permit',            'label' => 'Business Permit',                    'aliases' => ['1.5 Business Permit', 'Bus. Permit']],
    ['key' => 'license_to_operate',         'label' => 'License to Operate',                 'aliases' => ['1.10 FDA License to Operate (LTO)', 'FDA License to Operate']],
    ['key' => 'mayors_permit',              'label' => "Mayor's Permit",                     'aliases' => ["1.4 Mayor's Permit", 'Mayors Permit']],
    ['key' => 'barangay_permit',            'label' => 'Barangay Permit',                    'aliases' => ['1.6 Barangay Permit', 'Brgy. Permit']],
    ['key' => 'fda_cpr',                    'label' => 'FDA CPR of the Products',            'aliases' => ['1.11 Certificate of Product Registration (CPR)', 'Certificate of Product Registration']],
    ['key' => 'dti_sec_license',            'label' => 'DTI / SEC Registration License',    'aliases' => ['1.3 SEC Registration / DTI License', 'DTI - License', 'SEC Registration']],
    ['key' => 'sanitary_permit',            'label' => 'Sanitary Permit',                    'aliases' => ['1.7 Sanitary Permit']],
    ['key' => 'fire_clearance',             'label' => 'Fire Clearance Certificate',         'aliases' => ['1.8 Fire Clearance Certificate', 'Fire Clearance']],
    ['key' => 'denr_certificate',           'label' => 'Environment Certificate - DENR',     'aliases' => ['1.9 DENR Environment Certificate', 'DENR Environment Certificate']],
    ['key' => 'quality_certificates',       'label' => 'GMP, HACCP, ISO Certificate (if any)', 'aliases' => ['2.1 GMP, HACCP, SSOP, GHP, TQM, ISO Certificates (if available)']],
    ['key' => 'packaging_halal_certificate','label' => 'Packaging Halal Certificate',        'aliases' => ['3.3 Packaging Materials List with Corresponding Halal Certificates', 'Packaging Materials List']],
    ['key' => 'warehouse_halal_certificate','label' => 'Warehouse Halal Certificate',        'aliases' => ['4.5 Warehouse Halal Certificate and Storage System Description']],
    ['key' => 'products_menu_ingredients',  'label' => 'Products / Menu and Ingredients',   'aliases' => ['3.1 Full List of Products / Menu with Corresponding Ingredients', '2.2 Name of products/menu and corresponding ingredients']],
    ['key' => 'raw_material_halal_certificate', 'label' => 'Halal Certificate of Raw Materials', 'aliases' => ['4.1 Halal Certificates for All Raw Materials (especially meat products)', '2.7 Halal Certification for ingredients', '2.4 Halal Certificate of meat products']],
    ['key' => 'previous_hdip_certificate',  'label' => 'Previous Halal Certificate from HDIP', 'aliases' => ['1.12 Previous Halal Certificate from HDIP (if applicable)']],
];

// ── Ensure document conformity table exists with the correct schema ───────────
// First create the table if it doesn't exist at all (fresh install)
$conn->query("
    CREATE TABLE IF NOT EXISTS inspection_document_conformity (
        id                INT(11)      NOT NULL AUTO_INCREMENT,
        inspection_id     INT(11)      NOT NULL,
        application_id    INT(11)      NOT NULL,
        auditor_id        INT(11)      NOT NULL DEFAULT 0,
        auditor_role      ENUM('technical','shariah') NOT NULL DEFAULT 'technical',
        checklist_key     VARCHAR(100) NOT NULL,
        requirement_label VARCHAR(255) NOT NULL,
        uploaded_file_id  INT(11)      DEFAULT NULL,
        conformity_status ENUM('conforming','non_conforming','not_applicable') DEFAULT NULL,
        remarks           TEXT         DEFAULT NULL,
        reviewed_by       INT(11)      DEFAULT NULL,
        reviewed_at       TIMESTAMP    NULL DEFAULT NULL,
        created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_inspection_auditor_checklist (inspection_id, auditor_id, checklist_key),
        KEY idx_idc_application (application_id),
        KEY idx_idc_upload (uploaded_file_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// Migrate existing table: add auditor_id / auditor_role columns if missing,
// then swap the unique key to include auditor_id.
$idc_cols = [];
$col_res = $conn->query("SHOW COLUMNS FROM inspection_document_conformity");
if ($col_res) {
    foreach ($col_res->fetch_all(MYSQLI_ASSOC) as $col) {
        $idc_cols[] = $col['Field'];
    }
}

if (!in_array('auditor_id', $idc_cols, true)) {
    $conn->query("ALTER TABLE inspection_document_conformity
        ADD COLUMN auditor_id   INT(11)                        NOT NULL DEFAULT 0 AFTER application_id,
        ADD COLUMN auditor_role ENUM('technical','shariah')    NOT NULL DEFAULT 'technical' AFTER auditor_id
    ");
}

// Remove 'pending' from the conformity_status ENUM if it's still there
$conn->query("ALTER TABLE inspection_document_conformity
    MODIFY COLUMN conformity_status ENUM('conforming','non_conforming','not_applicable') DEFAULT NULL
");
// Replace the old unique key (inspection_id, checklist_key) with the new one
// that includes auditor_id so each auditor can have their own row per checklist item.
$idx_res = $conn->query("SHOW INDEX FROM inspection_document_conformity WHERE Key_name = 'uniq_inspection_checklist'");
if ($idx_res && $idx_res->num_rows > 0) {
    $conn->query("ALTER TABLE inspection_document_conformity DROP INDEX uniq_inspection_checklist");
}
$idx_new = $conn->query("SHOW INDEX FROM inspection_document_conformity WHERE Key_name = 'uniq_inspection_auditor_checklist'");
if ($idx_new && $idx_new->num_rows === 0) {
    $conn->query("ALTER TABLE inspection_document_conformity
        ADD UNIQUE KEY uniq_inspection_auditor_checklist (inspection_id, auditor_id, checklist_key)
    ");
}

// ── Helper functions ──────────────────────────────────────────────────────────
function normalizeChecklistText($text) {
    return preg_replace('/[^a-z0-9]+/', '', strtolower((string)$text));
}

function getApplicationUploads($conn, $applicationId) {
    $stmt = $conn->prepare("SELECT * FROM application_requirement_uploads WHERE application_id = ? ORDER BY requirement_label ASC");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function findChecklistUpload($uploads, $item) {
    $aliases = array_merge([$item['label']], $item['aliases']);
    foreach ($aliases as $alias) {
        $needle = normalizeChecklistText($alias);
        foreach ($uploads as $upload) {
            $haystack = normalizeChecklistText($upload['requirement_label']);
            if ($needle !== '' && ($needle === $haystack || strpos($haystack, $needle) !== false || strpos($needle, $haystack) !== false)) {
                return $upload;
            }
        }
    }
    return null;
}

function getSavedDocumentConformity($conn, $inspectionId, $auditorId) {
    $saved = [];
    $stmt = $conn->prepare("SELECT * FROM inspection_document_conformity WHERE inspection_id = ? AND auditor_id = ?");
    $stmt->bind_param("ii", $inspectionId, $auditorId);
    $stmt->execute();
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $saved[$row['checklist_key']] = $row;
    }
    return $saved;
}

function saveDocumentConformity($conn, $inspectionId, $applicationId, $userId, $auditorRole, $documentChecklist) {
    $statuses  = $_POST['document_status']    ?? [];
    $remarks   = $_POST['document_remarks']   ?? [];
    $uploadIds = $_POST['document_upload_id'] ?? [];

    $stmt = $conn->prepare("
        INSERT INTO inspection_document_conformity
            (inspection_id, application_id, auditor_id, auditor_role, checklist_key, requirement_label,
             uploaded_file_id, conformity_status, remarks, reviewed_by, reviewed_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            requirement_label = VALUES(requirement_label),
            uploaded_file_id  = VALUES(uploaded_file_id),
            conformity_status = VALUES(conformity_status),
            remarks           = VALUES(remarks),
            reviewed_by       = VALUES(reviewed_by),
            reviewed_at       = NOW()
    ");

    foreach ($documentChecklist as $item) {
        $key      = $item['key'];
        $status   = $statuses[$key] ?? 'conforming';
        if (!in_array($status, ['conforming','non_conforming','not_applicable'], true)) {
            $status = 'conforming';
        }
        $label    = $item['label'];
        $remark   = sanitize($remarks[$key] ?? '');
        $uploadId = isset($uploadIds[$key]) && intval($uploadIds[$key]) > 0 ? intval($uploadIds[$key]) : null;
        $stmt->bind_param("iiissssssi",
            $inspectionId, $applicationId, $userId, $auditorRole,
            $key, $label, $uploadId, $status, $remark, $userId
        );
        $stmt->execute();
    }
}

function countPendingDocumentConformity($documentChecklist) {
    $statuses = $_POST['document_status'] ?? [];
    $pending  = 0;
    foreach ($documentChecklist as $item) {
        $status = $statuses[$item['key']] ?? '';
        if (!in_array($status, ['conforming','non_conforming','not_applicable'], true)) {
            $pending++;
        }
    }
    return $pending;
}

// ── POST handlers ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_type = $_POST['action_type'] ?? '';

    // ── Start inspection ──────────────────────────────────────────────────────
    if ($action_type === 'start_inspection') {
        $schedule_id = intval($_POST['schedule_id']);
        $app_id      = intval($_POST['application_id']);

        // Payment gate
        $pay_check = $conn->prepare("
            SELECT id FROM payments
            WHERE reference_type = 'inspection' AND reference_id = ? AND status = 'verified'
            LIMIT 1
        ");
        $pay_check->bind_param("i", $schedule_id);
        $pay_check->execute();
        if (!$pay_check->get_result()->fetch_assoc()) {
            $error = 'The inspection fee has not been paid yet. The business owner must settle the payment before the inspection can begin.';
        } else {
            $check = $conn->prepare("SELECT id FROM inspections WHERE schedule_id = ?");
            $check->bind_param("i", $schedule_id);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();

            if ($existing) {
                $stmt = $conn->prepare("UPDATE inspections SET $auditor_field = ?, status = 'in_progress' WHERE schedule_id = ?");
                $stmt->bind_param("ii", $user_id, $schedule_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO inspections (schedule_id, application_id, $auditor_field, inspection_date, status) VALUES (?, ?, ?, CURDATE(), 'in_progress')");
                $stmt->bind_param("iii", $schedule_id, $app_id, $user_id);
            }

            if ($stmt->execute()) {
                $conn->query("UPDATE inspection_schedules SET status = 'in_progress' WHERE id = $schedule_id");
                $success = 'Inspection started!';
            }
        }
    }

    // ── Submit findings ───────────────────────────────────────────────────────
    if ($action_type === 'submit_findings') {
        $inspection_id   = intval($_POST['inspection_id']);
        $is_save_only    = isset($_POST['save_checklist']);
        $audit_findings  = sanitize($_POST['audit_findings'] ?? '');
        $conform_status  = sanitize($_POST['conformity_status'] ?? 'pending');
        $remarks_text    = sanitize($_POST['remarks'] ?? '');
        $auditor_role    = $is_technical ? 'technical' : 'shariah';

        // Verify this auditor owns this inspection
        $own = $conn->prepare("SELECT id, application_id FROM inspections WHERE id = ? AND $auditor_field = ?");
        $own->bind_param("ii", $inspection_id, $user_id);
        $own->execute();
        $inspection_row = $own->get_result()->fetch_assoc();

        if (!$inspection_row) {
            $error = 'Inspection not found or not assigned to you.';
        } else {
            $app_id = intval($inspection_row['application_id']);

            // Validate checklist rules (only on full submit, not save)
            if (!$is_save_only) {
                $statuses  = $_POST['document_status']    ?? [];
                $doc_rmks  = $_POST['document_remarks']   ?? [];
                $uploadIds = $_POST['document_upload_id'] ?? [];

                foreach ($document_checklist as $item) {
                    $key      = $item['key'];
                    $status   = $statuses[$key] ?? '';
                    $remark   = trim($doc_rmks[$key] ?? '');
                    $uploadId = isset($uploadIds[$key]) && intval($uploadIds[$key]) > 0 ? intval($uploadIds[$key]) : null;

                    if ($status === 'non_conforming' && empty($remark)) {
                        $error = 'Document "' . $item['label'] . '" is marked as non-conforming but has no remarks. Please explain why it is non-conforming.';
                        break;
                    }
                    if ($status === 'not_applicable' && $uploadId !== null) {
                        $error = 'Document "' . $item['label'] . '" cannot be marked as N/A because a file has been uploaded. Please choose Conforming or Non-conforming.';
                        break;
                    }
                }
            }

            if (empty($error)) {
                saveDocumentConformity($conn, $inspection_id, $app_id, $user_id, $auditor_role, $document_checklist);
            }
        }

        if (empty($error) && $is_save_only) {
            $success = 'Document checklist saved.';
            logActivity($conn, $user_id, 'Inspection Checklist Saved', 'Inspection #' . $inspection_id, 'audit');

        } elseif (empty($error) && countPendingDocumentConformity($document_checklist) > 0) {
            $error = 'Please mark every checklist item as conforming, non-conforming, or N/A before submitting findings.';

        } elseif (empty($error) && ($audit_findings === '' || !in_array($conform_status, ['conforming','non_conforming','partial'], true))) {
            $error = 'Audit findings and conformity status are required before completing the inspection.';

        } elseif (empty($error)) {
            // Save this auditor's findings to their own columns
            $upd = $conn->prepare("
                UPDATE inspections
                SET $findings_field = ?,
                    $conform_field  = ?,
                    $remarks_field  = ?,
                    $done_field     = NOW()
                WHERE id = ?
            ");
            $upd->bind_param("sssi", $audit_findings, $conform_status, $remarks_text, $inspection_id);

            if ($upd->execute()) {
                logActivity($conn, $user_id, $auditor_label . ' Auditor Findings Submitted', 'Inspection #' . $inspection_id, 'audit');

                // Re-fetch the inspection to check if BOTH auditors are now done
                $chk = $conn->prepare("
                    SELECT tech_completed_at, shariah_completed_at,
                           tech_conformity_status, shariah_conformity_status,
                           application_id
                    FROM inspections WHERE id = ?
                ");
                $chk->bind_param("i", $inspection_id);
                $chk->execute();
                $insp_state = $chk->get_result()->fetch_assoc();

                $both_done = !empty($insp_state['tech_completed_at'])
                          && !empty($insp_state['shariah_completed_at']);

                if ($both_done) {
                    // Derive combined conformity: worst of the two
                    $statuses_pair = [
                        $insp_state['tech_conformity_status'],
                        $insp_state['shariah_conformity_status'],
                    ];
                    if (in_array('non_conforming', $statuses_pair)) {
                        $combined = 'non_conforming';
                    } elseif (in_array('partial', $statuses_pair)) {
                        $combined = 'partial';
                    } else {
                        $combined = 'conforming';
                    }

                    // Mark inspection completed with combined status
                    $fin = $conn->prepare("UPDATE inspections SET status = 'completed', conformity_status = ? WHERE id = ?");
                    $fin->bind_param("si", $combined, $inspection_id);
                    $fin->execute();

                    // Update schedule status
                    $conn->query("UPDATE inspection_schedules s JOIN inspections i ON s.id = i.schedule_id SET s.status = 'completed' WHERE i.id = $inspection_id");

                    // Notify impartial committee — they review the completed inspection next.
                    // The business owner will be notified separately via NCR report.
                    $comm = $conn->query("SELECT id FROM users WHERE role_id = " . ROLE_IMPARTIAL_COMMITTEE . " AND role_status = 'approved'");
                    while ($c = $comm->fetch_assoc()) {
                        createNotification($conn, $c['id'], 'Inspection Completed',
                            'An inspection has been completed by both auditors and is ready for review.',
                            'action_required', BASE_URL . 'dashboard/impartial_committee/review_evidence.php');
                    }

                    $success = 'Your findings have been submitted. Both auditors have now completed this inspection.';
                } else {
                    // Only one auditor done — tell them to wait for the other
                    $other_label = $is_technical ? 'Shariah' : 'Technical';
                    $success = 'Your findings have been saved. Waiting for the ' . $other_label . ' Auditor to complete their inspection.';
                }
            }
        }
    }
}

// ── Load schedules with payment status ───────────────────────────────────────
$schedules = $conn->query("
    SELECT
        s.*,
        loi.company_name,
        loi.company_address,
        ha.id  AS app_id,
        i.id   AS inspection_id,
        i.status AS insp_status,
        i.auditor_technical_id,
        i.auditor_shariah_id,
        i.tech_completed_at,
        i.shariah_completed_at,
        p.status AS payment_status
    FROM inspection_schedules s
    JOIN hdp_applications ha ON s.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    LEFT JOIN inspections i ON s.id = i.schedule_id
    LEFT JOIN payments p ON p.reference_type = 'inspection'
        AND p.reference_id = s.id
        AND p.status = 'verified'
    ORDER BY s.schedule_date DESC
")->fetch_all(MYSQLI_ASSOC);

// ── Load my active inspections ────────────────────────────────────────────────
$my_inspections = [];
$mi = $conn->prepare("
    SELECT i.*, s.schedule_date, loi.company_name, loi.company_address
    FROM inspections i
    JOIN inspection_schedules s ON i.schedule_id = s.id
    JOIN hdp_applications ha ON i.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    WHERE i.$auditor_field = ?
      AND i.$done_field IS NULL
    ORDER BY i.created_at DESC
");
$mi->bind_param("i", $user_id);
$mi->execute();
$my_inspections = $mi->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= $error ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<?php if (!empty($my_inspections)): ?>
<div class="card" style="margin-bottom: 24px; border: 2px solid var(--primary-200);">
    <div class="card-header" style="background: var(--primary-50);">
        <h3><i class="fas fa-spinner fa-spin" style="color: var(--primary-600); margin-right: 8px;"></i> My Active Inspections (<?= $auditor_label ?> Auditor)</h3>
    </div>
    <div class="card-body">
        <?php foreach ($my_inspections as $insp): ?>
        <?php
            $uploads = getApplicationUploads($conn, intval($insp['application_id']));
            $saved_conformity = getSavedDocumentConformity($conn, intval($insp['id']), $user_id);
            $matched_upload_ids = [];

            // Fetch the business owner's menu items for this application
            $menu_stmt = $conn->prepare("
                SELECT mi.name, mi.category, mi.description, mi.price, mi.is_available
                FROM menu_items mi
                JOIN halal_restaurants hr ON mi.restaurant_id = hr.id
                JOIN hdp_applications ha ON hr.business_owner_id = ha.business_owner_id
                WHERE ha.id = ?
                ORDER BY mi.category ASC, mi.name ASC
            ");
            $menu_stmt->bind_param("i", $insp['application_id']);
            $menu_stmt->execute();
            $menu_items = $menu_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Group by category
            $menu_by_category = [];
            foreach ($menu_items as $mi) {
                $cat = $mi['category'] ?: 'Uncategorized';
                $menu_by_category[$cat][] = $mi;
            }
        ?>
        <div style="background: var(--neutral-50); border-radius: 12px; padding: 20px; margin-bottom: 16px;">
            <h4 style="font-family: var(--font-display); font-weight: 700; margin-bottom: 12px;">
                <?= htmlspecialchars($insp['company_name']) ?>
                <span class="badge badge-primary" style="margin-left: 8px;">In Progress</span>
            </h4>
            <form method="POST" data-checklist-form>
                <input type="hidden" name="action_type" value="submit_findings">
                <input type="hidden" name="inspection_id" value="<?= $insp['id'] ?>">

                <div style="background:#fff;border:1px solid var(--neutral-200);border-radius:10px;overflow:hidden;margin-bottom:20px;">
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;padding:14px 16px;background:var(--primary-50);border-bottom:1px solid var(--primary-100);">
                        <div>
                            <div style="font-weight:700;color:var(--primary-900);"><i class="fas fa-clipboard-check" style="margin-right:6px;"></i> Document Checklist</div>
                            <div style="font-size:0.78rem;color:var(--neutral-500);margin-top:2px;">Based on the HDIP document checklist. View each uploaded file, then mark its conformity.</div>
                        </div>
                        <span class="badge badge-info"><?= count($uploads) ?> uploaded file<?= count($uploads) === 1 ? '' : 's' ?></span>
                    </div>
                    <div class="table-responsive">
                        <table class="table" style="margin:0;">
                            <thead>
                                <tr>
                                    <th style="width:30%;">Document to Check</th>
                                    <th style="width:22%;">Owner Upload</th>
                                    <th style="width:20%;">Conformity</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($document_checklist as $item): ?>
                                <?php
                                    $matched_upload = findChecklistUpload($uploads, $item);
                                    if ($matched_upload) {
                                        $matched_upload_ids[] = intval($matched_upload['id']);
                                    }
                                    $saved = $saved_conformity[$item['key']] ?? null;
                                    $selected_status = $saved['conformity_status'] ?? 'conforming';
                                    $saved_remarks = $saved['remarks'] ?? '';
                                    $has_file = $matched_upload !== null;
                                    $key_attr = htmlspecialchars($item['key']);
                                ?>
                                <tr data-key="<?= $key_attr ?>" data-has-file="<?= $has_file ? '1' : '0' ?>">
                                    <td style="vertical-align:middle;font-size:0.88rem;color:var(--neutral-700);">
                                        <?= htmlspecialchars($item['label']) ?>
                                    </td>
                                    <td style="vertical-align:middle;">
                                        <input type="hidden" name="document_upload_id[<?= $key_attr ?>]" value="<?= $matched_upload ? intval($matched_upload['id']) : '' ?>">
                                        <?php if ($matched_upload): ?>
                                            <a href="<?= BASE_URL ?>uploads/<?= htmlspecialchars($matched_upload['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline" style="font-size:0.78rem;">
                                                <i class="fas fa-file"></i> View file
                                            </a>
                                            <div style="font-size:0.72rem;color:var(--neutral-400);margin-top:4px;line-height:1.3;" title="<?= htmlspecialchars($matched_upload['requirement_label']) ?>">
                                                <?= htmlspecialchars(function_exists('mb_strimwidth') ? mb_strimwidth($matched_upload['requirement_label'], 0, 42, '...') : substr($matched_upload['requirement_label'], 0, 42)) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Not uploaded</span>
                                        <?php endif; ?>
                                        <?php if ($item['key'] === 'products_menu_ingredients'): ?>
                                        <?php
                                            // Inline preview of the uploaded menu/products file
                                            $menu_file     = $matched_upload;
                                            $menu_file_path = $menu_file['file_path'] ?? '';
                                            $menu_file_url  = $menu_file_path ? BASE_URL . 'uploads/' . $menu_file_path : null;
                                            $menu_ext       = $menu_file_path ? strtolower(pathinfo($menu_file_path, PATHINFO_EXTENSION)) : '';
                                            $is_image       = in_array($menu_ext, ['jpg','jpeg','png','gif','webp']);
                                            $is_pdf         = ($menu_ext === 'pdf');
                                        ?>
                                        <?php if ($menu_file_url): ?>
                                        <div style="margin-top:8px;">
                                            <button type="button"
                                                id="menuToggle<?= $insp['id'] ?>"
                                                onclick="
                                                    var panel = document.getElementById('menuPanel<?= $insp['id'] ?>');
                                                    var open = panel.style.display !== 'none';
                                                    panel.style.display = open ? 'none' : 'block';
                                                    this.innerHTML = open
                                                        ? '<i class=\'fas fa-utensils\'></i> View Menu / Products'
                                                        : '<i class=\'fas fa-chevron-up\'></i> Hide Menu / Products';
                                                "
                                                class="btn btn-sm btn-outline"
                                                style="font-size:0.75rem;border-color:var(--primary-300);color:var(--primary-700);">
                                                <i class="fas fa-utensils"></i> View Menu / Products
                                            </button>
                                            <div id="menuPanel<?= $insp['id'] ?>" style="display:none;margin-top:8px;">
                                                <?php if ($is_image): ?>
                                                    <img src="<?= htmlspecialchars($menu_file_url) ?>"
                                                         alt="Menu / Products"
                                                         style="max-width:100%;border-radius:8px;border:1px solid var(--neutral-200);display:block;">
                                                <?php elseif ($is_pdf): ?>
                                                    <iframe src="<?= htmlspecialchars($menu_file_url) ?>"
                                                            style="width:100%;height:480px;border:1px solid var(--neutral-200);border-radius:8px;"
                                                            title="Menu / Products PDF"></iframe>
                                                <?php else: ?>
                                                    <a href="<?= htmlspecialchars($menu_file_url) ?>" target="_blank" class="btn btn-sm btn-outline" style="font-size:0.78rem;">
                                                        <i class="fas fa-download"></i> Download file
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php elseif (!empty($menu_by_category)): ?>
                                        <div style="margin-top:8px;">
                                            <button type="button"
                                                onclick="var p=this.nextElementSibling;var o=p.style.display!=='none';p.style.display=o?'none':'block';this.innerHTML=o?'<i class=\'fas fa-utensils\'></i> Show Menu (<?= count($menu_items) ?> items)':'<i class=\'fas fa-chevron-up\'></i> Hide Menu';"
                                                class="btn btn-sm btn-outline"
                                                style="font-size:0.75rem;border-color:var(--primary-300);color:var(--primary-700);">
                                                <i class="fas fa-utensils"></i> Show Menu <small>(<?= count($menu_items) ?> items)</small>
                                            </button>
                                            <div style="display:none;margin-top:8px;max-height:320px;overflow-y:auto;border:1px solid var(--neutral-200);border-radius:8px;background:#fff;">
                                                <?php foreach ($menu_by_category as $cat => $cat_items): ?>
                                                <div style="padding:6px 10px;background:var(--primary-50);font-size:0.72rem;font-weight:700;color:var(--primary-800);text-transform:uppercase;letter-spacing:0.04em;border-bottom:1px solid var(--primary-100);position:sticky;top:0;">
                                                    <?= htmlspecialchars($cat) ?>
                                                </div>
                                                <?php foreach ($cat_items as $mi): ?>
                                                <div style="padding:8px 10px;border-bottom:1px solid var(--neutral-100);display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                                                    <div style="flex:1;min-width:0;">
                                                        <div style="font-size:0.82rem;font-weight:600;color:var(--neutral-800);"><?= htmlspecialchars($mi['name']) ?></div>
                                                        <?php if (!empty($mi['description'])): ?>
                                                        <div style="font-size:0.75rem;color:var(--neutral-500);margin-top:2px;"><?= htmlspecialchars($mi['description']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (!empty($mi['price'])): ?>
                                                    <div style="font-size:0.8rem;font-weight:600;color:var(--primary-700);white-space:nowrap;">₱<?= number_format((float)$mi['price'], 2) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endforeach; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td style="vertical-align:middle;">
                                        <select
                                            name="document_status[<?= $key_attr ?>]"
                                            class="form-control checklist-status-select"
                                            data-key="<?= $key_attr ?>"
                                            data-has-file="<?= $has_file ? '1' : '0' ?>"
                                            style="font-size:0.82rem;padding:7px 9px;">
                                            <option value="conforming" <?= $selected_status === 'conforming' ? 'selected' : '' ?>>Conforming</option>
                                            <option value="non_conforming" <?= $selected_status === 'non_conforming' ? 'selected' : '' ?>>Non-conforming</option>
                                            <option value="not_applicable"
                                                <?= $selected_status === 'not_applicable' ? 'selected' : '' ?>
                                                <?= $has_file ? 'disabled title="Cannot select N/A — a file has been uploaded for this document."' : '' ?>>
                                                N/A<?= $has_file ? ' (file uploaded)' : '' ?>
                                            </option>
                                        </select>
                                        <?php if ($has_file && $selected_status === 'not_applicable'): ?>
                                        <div style="font-size:0.72rem;color:var(--danger);margin-top:3px;">
                                            <i class="fas fa-exclamation-circle"></i> N/A not allowed — file is uploaded.
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="vertical-align:middle;">
                                        <input
                                            type="text"
                                            name="document_remarks[<?= $key_attr ?>]"
                                            class="form-control checklist-remarks-input"
                                            data-key="<?= $key_attr ?>"
                                            value="<?= htmlspecialchars($saved_remarks) ?>"
                                            placeholder="<?= $selected_status === 'non_conforming' ? 'Required — explain the non-conformance...' : 'Notes...' ?>"
                                            style="font-size:0.82rem;padding:7px 9px;<?= $selected_status === 'non_conforming' ? 'border-color:var(--danger);' : '' ?>">
                                        <div class="remarks-required-hint" data-key="<?= $key_attr ?>" style="font-size:0.72rem;color:var(--danger);margin-top:3px;display:<?= ($selected_status === 'non_conforming' && empty($saved_remarks)) ? 'block' : 'none' ?>;">
                                            <i class="fas fa-exclamation-circle"></i> Remarks are required for non-conforming items.
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php
                                    $unmatched_uploads = array_filter($uploads, function ($upload) use ($matched_upload_ids) {
                                        return !in_array(intval($upload['id']), $matched_upload_ids, true);
                                    });
                                ?>
                                <?php if (!empty($unmatched_uploads)): ?>
                                <tr>
                                    <td colspan="4" style="background:var(--neutral-100);font-weight:700;font-size:0.78rem;color:var(--neutral-600);text-transform:uppercase;letter-spacing:0.04em;">
                                        Other Uploaded Files
                                    </td>
                                </tr>
                                <?php foreach ($unmatched_uploads as $upload): ?>
                                <tr>
                                    <td style="font-size:0.88rem;color:var(--neutral-700);"><?= htmlspecialchars($upload['requirement_label']) ?></td>
                                    <td colspan="3">
                                        <a href="<?= BASE_URL ?>uploads/<?= htmlspecialchars($upload['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline" style="font-size:0.78rem;">
                                            <i class="fas fa-file"></i> View file
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="form-group">
                    <label><?= $auditor_label ?> Audit Findings <span class="required">*</span></label>
                    <textarea name="audit_findings" class="form-control" rows="5" placeholder="Document all audit findings during the inspection. Include observations on halal compliance, food handling, storage, sourcing, etc."><?= htmlspecialchars($insp[$findings_field] ?? '') ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Conformity Status <span class="required">*</span></label>
                        <select name="conformity_status" class="form-control" required>
                            <option value="">Select...</option>
                            <option value="conforming" <?= ($insp[$conform_field] ?? '') === 'conforming' ? 'selected' : '' ?>>Conforming - Fully Compliant</option>
                            <option value="partial" <?= ($insp[$conform_field] ?? '') === 'partial' ? 'selected' : '' ?>>Partial - Minor Issues</option>
                            <option value="non_conforming" <?= ($insp[$conform_field] ?? '') === 'non_conforming' ? 'selected' : '' ?>>Non-Conforming - Major Issues</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Additional remarks..."><?= htmlspecialchars($insp[$remarks_field] ?? '') ?></textarea>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="submit" name="save_checklist" value="1" class="btn btn-outline" formnovalidate><i class="fas fa-save"></i> Save Checklist</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check-circle"></i> Submit Findings</button>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-calendar-alt" style="color: var(--accent-500); margin-right: 8px;"></i> Scheduled Inspections</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($schedules)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-calendar"></i></div><h3>No Inspections Scheduled</h3></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Company</th><th>Location</th><th>Date</th><th>Schedule Status</th><th>Payment</th><th>Inspection</th><th>Menu / Products</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($schedules as $s):
                            // Fetch the 3.1 menu/products upload for this schedule's application
                            $menu_upload_stmt = $conn->prepare("
                                SELECT file_path FROM application_requirement_uploads
                                WHERE application_id = ? AND requirement_label LIKE '%3.1%'
                                LIMIT 1
                            ");
                            $menu_upload_stmt->bind_param("i", $s['app_id']);
                            $menu_upload_stmt->execute();
                            $menu_upload_row = $menu_upload_stmt->get_result()->fetch_assoc();
                            $menu_upload_path = $menu_upload_row['file_path'] ?? null;
                            $menu_upload_ext  = $menu_upload_path ? strtolower(pathinfo($menu_upload_path, PATHINFO_EXTENSION)) : '';
                            $menu_is_image    = in_array($menu_upload_ext, ['jpg','jpeg','png','gif','webp']);
                            $menu_is_pdf      = ($menu_upload_ext === 'pdf');
                            $menu_upload_url  = $menu_upload_path ? BASE_URL . 'uploads/' . $menu_upload_path : null;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['company_name']) ?></strong></td>
                            <td style="font-size:0.85rem"><?= htmlspecialchars($s['location'] ?? $s['company_address'] ?? '-') ?></td>
                            <td><?= formatDate($s['schedule_date']) ?></td>
                            <td><?= getStatusBadge($s['status']) ?></td>
                            <td>
                                <?php if ($s['payment_status'] === 'verified'): ?>
                                    <span class="badge badge-success"><i class="fas fa-check-circle"></i> Paid</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $s['insp_status'] ? getStatusBadge($s['insp_status']) : '<span class="badge badge-secondary">Not Started</span>' ?></td>
                            <td>
                                <?php if ($menu_upload_url): ?>
                                    <button type="button"
                                        class="btn btn-sm btn-outline"
                                        style="font-size:0.75rem;border-color:var(--primary-300);color:var(--primary-700);"
                                        data-modal="menuFileModal<?= $s['id'] ?>">
                                        <i class="fas fa-utensils"></i> View Menu
                                    </button>
                                    <!-- Menu file modal -->
                                    <div class="modal-overlay" id="menuFileModal<?= $s['id'] ?>">
                                        <div class="modal" style="max-width:780px;">
                                            <div class="modal-header">
                                                <h3><i class="fas fa-utensils" style="color:var(--primary-600);margin-right:8px;"></i>
                                                    Menu / Products — <?= htmlspecialchars($s['company_name']) ?>
                                                </h3>
                                                <button class="modal-close"><i class="fas fa-times"></i></button>
                                            </div>
                                            <div class="modal-body" style="padding:16px;">
                                                <p style="font-size:0.8rem;color:var(--neutral-500);margin-bottom:12px;">
                                                    <i class="fas fa-info-circle" style="margin-right:4px;"></i>
                                                    Full List of Products / Menu with Corresponding Ingredients (3.1)
                                                    — <a href="<?= htmlspecialchars($menu_upload_url) ?>" target="_blank" style="color:var(--primary-600);">Open in new tab</a>
                                                </p>
                                                <?php if ($menu_is_image): ?>
                                                    <img src="<?= htmlspecialchars($menu_upload_url) ?>"
                                                         alt="Menu / Products"
                                                         style="max-width:100%;border-radius:8px;border:1px solid var(--neutral-200);display:block;">
                                                <?php elseif ($menu_is_pdf): ?>
                                                    <iframe src="<?= htmlspecialchars($menu_upload_url) ?>"
                                                            style="width:100%;height:520px;border:1px solid var(--neutral-200);border-radius:8px;"
                                                            title="Menu / Products PDF"></iframe>
                                                <?php else: ?>
                                                    <a href="<?= htmlspecialchars($menu_upload_url) ?>" target="_blank" class="btn btn-outline">
                                                        <i class="fas fa-download"></i> Download file
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size:0.78rem;color:var(--neutral-400);">Not uploaded</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    // This auditor's specific assignment field and done timestamp
                                    $my_auditor_id_col   = $is_technical ? 'auditor_technical_id' : 'auditor_shariah_id';
                                    $my_done_col         = $is_technical ? 'tech_completed_at'    : 'shariah_completed_at';
                                    $already_assigned    = !empty($s[$my_auditor_id_col]);
                                    $already_done        = !empty($s[$my_done_col]);
                                    $insp_completed      = ($s['insp_status'] === 'completed');
                                    // Can start if: payment verified, inspection not completed,
                                    // and this auditor hasn't been assigned yet
                                    $can_start = $s['payment_status'] === 'verified'
                                              && !$insp_completed
                                              && !$already_assigned
                                              && in_array($s['status'], ['scheduled','confirmed','in_progress'], true);
                                ?>
                                <?php if ($insp_completed && $already_done): ?>
                                    <button class="btn btn-sm btn-outline" data-modal="myFindingModal<?= $s['inspection_id'] ?>">
                                        <i class="fas fa-eye"></i> My Findings
                                    </button>
                                <?php elseif ($insp_completed): ?>
                                    <span class="badge badge-secondary">Completed</span>
                                <?php elseif ($already_done): ?>
                                    <button class="btn btn-sm btn-outline" data-modal="myFindingModal<?= $s['inspection_id'] ?>">
                                        <i class="fas fa-eye"></i> My Findings
                                    </button>
                                <?php elseif ($already_assigned): ?>
                                    <span class="badge badge-primary"><i class="fas fa-spinner fa-spin"></i> In Progress</span>
                                <?php elseif ($can_start): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action_type" value="start_inspection">
                                        <input type="hidden" name="schedule_id" value="<?= $s['id'] ?>">
                                        <input type="hidden" name="application_id" value="<?= $s['app_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-play"></i> Start</button>
                                    </form>
                                <?php elseif ($s['payment_status'] !== 'verified'): ?>
                                    <span class="badge badge-warning" title="Waiting for the business owner to settle the inspection fee before you can start.">
                                        <i class="fas fa-clock"></i> Awaiting Payment
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php
                            // Render a "My Findings" modal for this row if the auditor has submitted
                            if ($already_done && $s['inspection_id']):
                                $mf = $conn->prepare("
                                    SELECT i.$findings_field AS my_findings,
                                           i.$conform_field  AS my_conform,
                                           i.$remarks_field  AS my_remarks,
                                           i.$done_field     AS submitted_on,
                                           i.status          AS insp_status,
                                           i.conformity_status AS combined_conform
                                    FROM inspections i
                                    WHERE i.id = ? AND i.$auditor_field = ?
                                    LIMIT 1
                                ");
                                $mf->bind_param("ii", $s['inspection_id'], $user_id);
                                $mf->execute();
                                $mf_row = $mf->get_result()->fetch_assoc();
                        ?>
                        <?php if ($mf_row): ?>
                        <div class="modal-overlay" id="myFindingModal<?= $s['inspection_id'] ?>">
                            <div class="modal" style="max-width:680px;">
                                <div class="modal-header">
                                    <h3><i class="fas fa-clipboard-check" style="margin-right:8px;color:var(--primary-600);"></i>My Findings — <?= htmlspecialchars($s['company_name']) ?></h3>
                                    <button class="modal-close"><i class="fas fa-times"></i></button>
                                </div>
                                <div class="modal-body">
                                    <?php
                                        $nc_stmt = $conn->prepare("
                                            SELECT requirement_label, remarks
                                            FROM inspection_document_conformity
                                            WHERE inspection_id = ? AND auditor_id = ? AND conformity_status = 'non_conforming'
                                            ORDER BY requirement_label ASC
                                        ");
                                        $nc_stmt->bind_param("ii", $s['inspection_id'], $user_id);
                                        $nc_stmt->execute();
                                        $nc_docs = $nc_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                    ?>
                                    <div style="background:var(--primary-50);border:1px solid var(--primary-100);border-radius:10px;padding:16px;margin-bottom:16px;">
                                        <div style="font-weight:700;color:var(--primary-800);margin-bottom:12px;">
                                            <i class="fas fa-user-check" style="margin-right:6px;"></i>
                                            <?= $auditor_label ?> Auditor Submission
                                            <span style="font-weight:400;font-size:0.82rem;color:var(--neutral-500);margin-left:8px;">
                                                Submitted <?= formatDate($mf_row['submitted_on']) ?>
                                            </span>
                                        </div>
                                        <div style="margin-bottom:12px;">
                                            <strong>Audit Findings:</strong>
                                            <div style="background:#fff;padding:12px;border-radius:8px;margin-top:6px;border:1px solid var(--neutral-200);">
                                                <?= $mf_row['my_findings'] !== '' && $mf_row['my_findings'] !== null
                                                    ? nl2br(htmlspecialchars($mf_row['my_findings']))
                                                    : '<em style="color:var(--neutral-400);">No findings recorded.</em>' ?>
                                            </div>
                                        </div>
                                        <div style="margin-bottom:12px;">
                                            <strong>My Conformity:</strong>
                                            <span style="margin-left:8px;"><?= getStatusBadge($mf_row['my_conform'] ?: 'pending') ?></span>
                                        </div>
                                        <div style="margin-bottom:<?= !empty($nc_docs) ? '16px' : '0' ?>;">
                                            <strong>Remarks:</strong>
                                            <div style="background:#fff;padding:12px;border-radius:8px;margin-top:6px;border:1px solid var(--neutral-200);">
                                                <?= $mf_row['my_remarks'] !== '' && $mf_row['my_remarks'] !== null
                                                    ? nl2br(htmlspecialchars($mf_row['my_remarks']))
                                                    : '<em style="color:var(--neutral-400);">No remarks.</em>' ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($nc_docs)): ?>
                                        <div>
                                            <strong style="color:var(--danger,#ef4444);"><i class="fas fa-exclamation-triangle" style="margin-right:5px;"></i>Non-Conforming Documents:</strong>
                                            <div style="margin-top:8px;display:flex;flex-direction:column;gap:8px;">
                                                <?php foreach ($nc_docs as $nc): ?>
                                                <div style="background:#fff;border:1px solid #fca5a5;border-left:4px solid var(--danger,#ef4444);border-radius:8px;padding:10px 14px;">
                                                    <div style="font-weight:600;font-size:0.88rem;color:var(--neutral-800);margin-bottom:4px;">
                                                        <?= htmlspecialchars($nc['requirement_label']) ?>
                                                    </div>
                                                    <div style="font-size:0.84rem;color:var(--neutral-600);">
                                                        <?= $nc['remarks'] !== '' && $nc['remarks'] !== null
                                                            ? nl2br(htmlspecialchars($nc['remarks']))
                                                            : '<em style="color:var(--neutral-400);">No remarks provided.</em>' ?>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($mf_row['insp_status'] === 'completed'): ?>
                                    <div style="background:var(--neutral-50);border:1px solid var(--neutral-200);border-radius:10px;padding:14px;text-align:center;">
                                        <div style="font-weight:600;color:var(--neutral-600);margin-bottom:6px;">Combined Inspection Result</div>
                                        <?= getStatusBadge($mf_row['combined_conform']) ?>
                                    </div>
                                    <?php else: ?>
                                    <div style="background:var(--warning-50,#fffbeb);border:1px solid var(--warning-200,#fde68a);border-radius:10px;padding:14px;text-align:center;color:var(--warning-700,#92400e);font-size:0.88rem;">
                                        <i class="fas fa-hourglass-half" style="margin-right:6px;"></i>
                                        Waiting for the other auditor to submit their findings.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
(function () {
    'use strict';
    document.querySelectorAll('.checklist-status-select').forEach(function (select) {
        applyChecklistRules(select, false);
        select.addEventListener('change', function () { applyChecklistRules(this, true); });
    });

    function applyChecklistRules(select, isUserChange) {
        var key      = select.dataset.key;
        var hasFile  = select.dataset.hasFile === '1';
        var status   = select.value;
        var remarksInput = document.querySelector('.checklist-remarks-input[data-key="' + key + '"]');
        var hint         = document.querySelector('.remarks-required-hint[data-key="' + key + '"]');

        var naOption = select.querySelector('option[value="not_applicable"]');
        if (naOption) {
            if (hasFile) {
                naOption.disabled = true;
                naOption.textContent = 'N/A (file uploaded — not allowed)';
                if (status === 'not_applicable' && isUserChange) {
                    select.value = 'conforming';
                    status = 'conforming';
                    showToast('N/A cannot be selected because a file has been uploaded for this document.', 'error');
                }
            } else {
                naOption.disabled = false;
                naOption.textContent = 'N/A';
            }
        }

        if (remarksInput) {
            if (status === 'non_conforming') {
                remarksInput.placeholder = 'Required — explain the non-conformance...';
                remarksInput.style.borderColor = 'var(--danger)';
                if (hint) hint.style.display = remarksInput.value.trim() === '' ? 'block' : 'none';
                remarksInput.addEventListener('input', function onInput() {
                    if (hint) hint.style.display = this.value.trim() === '' ? 'block' : 'none';
                    this.style.borderColor = this.value.trim() === '' ? 'var(--danger)' : '';
                });
            } else {
                remarksInput.placeholder = 'Notes...';
                remarksInput.style.borderColor = '';
                if (hint) hint.style.display = 'none';
            }
        }
    }

    document.querySelectorAll('form[data-checklist-form]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (document.activeElement && document.activeElement.hasAttribute('formnovalidate')) return true;
            var firstError = null;
            document.querySelectorAll('.checklist-status-select').forEach(function (select) {
                if (firstError) return;
                var key     = select.dataset.key;
                var hasFile = select.dataset.hasFile === '1';
                var status  = select.value;
                var remarks = (document.querySelector('.checklist-remarks-input[data-key="' + key + '"]') || {}).value || '';
                if (status === 'non_conforming' && remarks.trim() === '') {
                    firstError = 'Please add remarks for every non-conforming document before submitting.';
                    var hint = document.querySelector('.remarks-required-hint[data-key="' + key + '"]');
                    if (hint) hint.style.display = 'block';
                    var input = document.querySelector('.checklist-remarks-input[data-key="' + key + '"]');
                    if (input) { input.style.borderColor = 'var(--danger)'; input.focus(); }
                }
                if (status === 'not_applicable' && hasFile) {
                    firstError = 'N/A cannot be selected for a document that has a file uploaded. Please choose Conforming or Non-conforming.';
                }
            });
            if (firstError) {
                e.preventDefault();
                showToast(firstError, 'error');
                return false;
            }
        });
    });

    function showToast(msg, type) {
        var colors = { success: '#22c55e', error: '#ef4444', info: '#3b82f6' };
        var t = document.createElement('div');
        t.style.cssText = [
            'position:fixed', 'bottom:24px', 'right:24px', 'z-index:9999',
            'background:#1e293b', 'color:#fff', 'padding:12px 20px',
            'border-radius:10px', 'font-size:.85rem', 'font-weight:600',
            'box-shadow:0 8px 24px rgba(0,0,0,.25)',
            'display:flex', 'align-items:center', 'gap:10px',
            'border-left:4px solid ' + (colors[type] || colors.info),
            'max-width:380px'
        ].join(';');
        var icon = type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle';
        t.innerHTML = '<i class="fas fa-' + icon + '" style="color:' + (colors[type] || colors.info) + '"></i>' + msg;
        document.body.appendChild(t);
        setTimeout(function () { t.remove(); }, 5000);
    }
})();
</script>
