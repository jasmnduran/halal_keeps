<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$page_title = 'Letter of Intent';
$page_heading = 'Letter of Intent';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/business_owner/'], ['label' => 'Letter of Intent']];
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle create/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = sanitize($_POST['company_name'] ?? '');
    $company_address = sanitize($_POST['company_address'] ?? '');
    $application_type = sanitize($_POST['application_type'] ?? '');
    $certifying_body = sanitize($_POST['certifying_body'] ?? 'HDIP');
    $date_of_intent = sanitize($_POST['date_of_intent'] ?? '');
    // Build menu_list from dynamic items array
    $menu_items = $_POST['menu_items'] ?? [];
    $menu_items = array_filter(array_map('trim', $menu_items));
    $menu_list = implode("\n", $menu_items);
    $contact_person = sanitize($_POST['contact_person'] ?? '');
    $contact_email = sanitize($_POST['contact_email'] ?? '');
    $contact_phone = sanitize($_POST['contact_phone'] ?? '');
    $submit_action = $_POST['submit_action'] ?? 'draft';
    // Signature — raw base64 data URL, not run through sanitize (it strips special chars)
    $signature_data = $_POST['signature_data'] ?? '';
    // Validate it's a proper PNG data URL
    if ($signature_data && !preg_match('/^data:image\/png;base64,/', $signature_data)) {
        $signature_data = '';
    }
    
    if (empty($company_name)) {
        $error = 'Company name is required.';
    } elseif ($submit_action === 'submit' && empty($signature_data)) {
        $error = 'Please sign the letter before submitting.';
    } else {
        $status = ($submit_action === 'submit') ? 'submitted' : 'draft';
        $submitted_at = ($submit_action === 'submit') ? date('Y-m-d H:i:s') : null;
        
        if (isset($_POST['loi_id']) && $_POST['loi_id'] > 0) {
            // Check previous status before updating
            $prev_stmt = $conn->prepare("SELECT status FROM letter_of_intent WHERE id = ? AND business_owner_id = ?");
            $prev_stmt->bind_param("ii", $_POST['loi_id'], $user_id);
            $prev_stmt->execute();
            $prev_row = $prev_stmt->get_result()->fetch_assoc();
            $was_returned = ($prev_row && $prev_row['status'] === 'returned');

            // Update existing
            $stmt = $conn->prepare("UPDATE letter_of_intent SET company_name=?, company_address=?, application_type=?, certifying_body=?, menu_list=?, contact_person=?, contact_email=?, contact_phone=?, status=?, submitted_at=COALESCE(?, submitted_at), date_of_intent=?, signature_data=COALESCE(NULLIF(?,  ''), signature_data) WHERE id=? AND business_owner_id=?");
            if (!$stmt) { $error = 'DB error: ' . $conn->error; } else {
            $stmt->bind_param("ssssssssssssii", $company_name, $company_address, $application_type, $certifying_body, $menu_list, $contact_person, $contact_email, $contact_phone, $status, $submitted_at, $date_of_intent, $signature_data, $_POST['loi_id'], $user_id);
            
            if ($stmt->execute()) {
                $success = $submit_action === 'submit' ? 'Letter of Intent submitted successfully!' : 'Draft saved.';
                if ($submit_action === 'submit') {
                    $eval_query = $conn->query("SELECT id FROM users WHERE role_id = " . ROLE_EVALUATOR . " AND role_status = 'approved'");
                    while ($eval = $eval_query->fetch_assoc()) {
                        if ($was_returned) {
                            createNotification($conn, $eval['id'], 'LOI Revised & Resubmitted',
                                $_SESSION['full_name'] . ' has revised and resubmitted their Letter of Intent for ' . $company_name . ' based on your feedback.',
                                'action_required', BASE_URL . 'dashboard/evaluator/verify_loi.php?action=review&id=' . $_POST['loi_id']);
                        } else {
                            createNotification($conn, $eval['id'], 'New Letter of Intent',
                                $_SESSION['full_name'] . ' has submitted a Letter of Intent for ' . $company_name,
                                'action_required', BASE_URL . 'dashboard/evaluator/verify_loi.php');
                        }
                    }
                }
                $action = 'list';
            } else {
                $error = 'Failed to save. Please try again.';
            }
            } // end if $stmt
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO letter_of_intent (business_owner_id, company_name, company_address, application_type, certifying_body, menu_list, contact_person, contact_email, contact_phone, status, submitted_at, date_of_intent, signature_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) { $error = 'DB error: ' . $conn->error; } else {
            $stmt->bind_param("issssssssssss", $user_id, $company_name, $company_address, $application_type, $certifying_body, $menu_list, $contact_person, $contact_email, $contact_phone, $status, $submitted_at, $date_of_intent, $signature_data);
            
            if ($stmt->execute()) {
                $success = $submit_action === 'submit' ? 'Letter of Intent submitted successfully!' : 'Draft saved.';
                logActivity($conn, $user_id, 'LOI Created', 'Created LOI for ' . $company_name, 'loi');
                if ($submit_action === 'submit') {
                    $eval_query = $conn->query("SELECT id FROM users WHERE role_id = " . ROLE_EVALUATOR . " AND role_status = 'approved'");
                    while ($eval = $eval_query->fetch_assoc()) {
                        createNotification($conn, $eval['id'], 'New Letter of Intent', 
                            $_SESSION['full_name'] . ' has submitted a Letter of Intent for ' . $company_name,
                            'action_required', BASE_URL . 'dashboard/evaluator/verify_loi.php');
                    }
                }
                $action = 'list';
            } else {
                $error = 'Failed to create. Please try again.';
            }
            } // end if $stmt
        }
    }
}

// Get LOI for editing
$edit_loi = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM letter_of_intent WHERE id = ? AND business_owner_id = ?");
    $stmt->bind_param("ii", $_GET['id'], $user_id);
    $stmt->execute();
    $edit_loi = $stmt->get_result()->fetch_assoc();
    if (!$edit_loi) {
        $action = 'list';
        $error = 'Letter of Intent not found.';
    }
}

// Get all LOIs for listing
$all_lois = [];
if ($action === 'list') {
    $stmt = $conn->prepare("SELECT loi.*, lv.verification_status, lv.feedback FROM letter_of_intent loi LEFT JOIN loi_verification lv ON loi.id = lv.loi_id WHERE loi.business_owner_id = ? ORDER BY loi.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $all_lois = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Check if owner already has an LOI (any status)
$has_loi = false;
$loi_check = $conn->prepare("SELECT id FROM letter_of_intent WHERE business_owner_id = ? LIMIT 1");
$loi_check->bind_param("i", $user_id);
$loi_check->execute();
$has_loi = (bool) $loi_check->get_result()->fetch_assoc();

// Check if owner has an active halal certificate awarded by the President
$has_certificate = false;
$cert_check = $conn->prepare("SELECT hc.id FROM halal_certificates hc JOIN hdp_applications ha ON hc.application_id = ha.id WHERE ha.business_owner_id = ? AND hc.status IN ('active','awarded') LIMIT 1");
$cert_check->bind_param("i", $user_id);
$cert_check->execute();
$has_certificate = (bool) $cert_check->get_result()->fetch_assoc();

// Block create/select_body if they already have an LOI and no certificate yet
if (in_array($action, ['create', 'select_body']) && $has_loi && !$has_certificate) {
    $action = 'list';
    $error = 'You already have a Letter of Intent. You can only create a new one after receiving your Halal Certificate.';
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
<!-- LOI List -->
<div class="card">
    <div class="card-header">
        <h3>My Letter of Intent</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($all_lois)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-file-alt"></i></div>
                <h3>No Letter of Intent</h3>
                <p>Create your Letter of Intent to start the halal certification process.</p>
                <a href="?action=select_body" class="btn btn-primary"><i class="fas fa-plus"></i> Create LOI</a>
            </div>
        <?php else: $loi = $all_lois[0]; ?>
            <div style="padding: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div>
                    <div style="font-size: 1.1rem; font-weight: 700; color: var(--neutral-900);"><?= htmlspecialchars($loi['company_name']) ?></div>
                    <div style="font-size: 0.85rem; color: var(--neutral-500); margin-top: 4px;">
                        Submitted: <?= $loi['submitted_at'] ? formatDate($loi['submitted_at']) : 'Not yet submitted' ?>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <?= getStatusBadge($loi['status']) ?>
                    <a href="?action=view&id=<?= $loi['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a>
                    <?php if (in_array($loi['status'], ['draft', 'returned'])): ?>
                        <a href="?action=edit&id=<?= $loi['id'] ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> <?= $loi['status'] === 'returned' ? 'Edit & Resubmit' : 'Edit' ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($loi['status'] === 'verified'): ?>
                        <a href="<?= BASE_URL ?>dashboard/business_owner/applications.php?action=create&loi_id=<?= $loi['id'] ?>" class="btn btn-sm btn-success"><i class="fas fa-arrow-right"></i> Proceed</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'view' && isset($_GET['id'])): ?>
<?php
    $stmt = $conn->prepare("SELECT loi.*, lv.verification_status, lv.feedback, lv.verified_at, u.full_name as evaluator_name FROM letter_of_intent loi LEFT JOIN loi_verification lv ON loi.id = lv.loi_id LEFT JOIN users u ON lv.evaluator_id = u.id WHERE loi.id = ? AND loi.business_owner_id = ?");
    $stmt->bind_param("ii", $_GET['id'], $user_id);
    $stmt->execute();
    $view_loi = $stmt->get_result()->fetch_assoc();

    $req_row = null;
    if ($view_loi) {
        $rq = $conn->prepare("SELECT * FROM loi_requirements WHERE loi_id = ? ORDER BY sent_at DESC LIMIT 1");
        $rq->bind_param("i", $view_loi['id']);
        $rq->execute();
        $req_row = $rq->get_result()->fetch_assoc();
    }
?>
<?php if ($view_loi): ?>
<a href="?action=list" class="btn btn-outline btn-sm" style="margin-bottom: 20px;"><i class="fas fa-arrow-left"></i> Back to List</a>

<?php if ($view_loi['status'] === 'verified'): ?>
<a href="<?= BASE_URL ?>dashboard/business_owner/loi_print.php?id=<?= $view_loi['id'] ?>"
   class="btn btn-success btn-sm" style="margin-bottom: 20px; margin-left: 8px;">
    <i class="fas fa-file-pdf"></i> Download LOI (PDF)
</a>
<?php endif; ?>

<div style="display: grid; grid-template-columns: <?= $req_row ? '1fr 1fr' : '1fr' ?>; gap: 24px; align-items: start;">

    <!-- Formal Letter -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-file-alt" style="color:var(--primary-600);margin-right:8px;"></i> Letter of Intent</h3>
            <?= getStatusBadge($view_loi['status']) ?>
        </div>
        <div class="card-body" style="padding: 40px 48px; font-family: 'Times New Roman', Times, serif; font-size: 1rem; line-height: 1.8; color: #111;">
            <p style="margin-bottom: 24px;">
                <?= $view_loi['date_of_intent'] ? date('d F Y', strtotime($view_loi['date_of_intent'])) : '[Day/Month/Year]' ?>
            </p>
            <p style="margin-bottom: 24px;">
                <strong>HADJI ABDULATIF S. SANGCUPAN</strong><br>
                President<br>
                Halal Development Institute of the Philippines (HDIP)<br>
                4th Flr. Unit 401, Central Bldg. 37 Arayat Cor. Malabito St.<br>
                Cubao, Quezon City
            </p>
            <p style="margin-bottom: 24px;">
                Subject: Letter of Intent for Halal Certification — <strong><?= htmlspecialchars($view_loi['company_name']) ?></strong>
            </p>
            <p style="margin-bottom: 24px;"> Dear Sir/Madam,</p>
            <p style="margin-bottom: 24px;"> I am writing to formally express the intent of <strong><?= htmlspecialchars($view_loi['company_name']) ?></strong><?php endif; ?> to apply for <?= !empty($view_loi['application_type']) ? '<strong>' . htmlspecialchars($view_loi['application_type']) . '</strong> of ' : '' ?>Halal Certification from the Halal Development Institute of the Philippines (HDIP) for our products/services.</p>
            <p style="margin-bottom: 24px;"> Our company, located at <strong><?= htmlspecialchars($view_loi['company_address']) ?></strong>, produces/serves
                <?php
                    $items = array_filter(explode("\n", $view_loi['menu_list'] ?? ''));
                    echo $items ? '<strong>' . htmlspecialchars(implode(', ', array_map('trim', $items))) . '</strong>' : '<strong>[Specific Product/Menu Item List]</strong>';
                ?>.
               
                We are committed to complying with all the requirements and standards set forth by the HDIP, including adherence to Shariah law, GMP (Good Manufacturing Practices), and hygiene standards.
            </p>
            <p>Thank you for your assistance.</p>
            <p>Sincerely,</p>
            <p style="margin-top: 32px;">
                <strong><?= htmlspecialchars($view_loi['contact_person'] ?? $_SESSION['full_name']) ?></strong><br>
                <?= htmlspecialchars($view_loi['contact_phone'] ?? '[Phone Number]') ?><br>
                <?= htmlspecialchars($view_loi['contact_email'] ?? $_SESSION['email']) ?>
            </p>
            <?php if (!empty($view_loi['signature_data'])): ?>
            <div style="margin-top: 24px; border-top: 1px solid #ddd; padding-top: 16px;">
                <div style="font-size: 0.8rem; color: #888; margin-bottom: 6px; font-family: Arial, sans-serif;">Applicant's Digital Signature:</div>
                <img src="<?= htmlspecialchars($view_loi['signature_data']) ?>"
                     alt="Digital Signature"
                     style="max-width: 280px; height: auto; border-bottom: 1px solid #333; display: block;">
                <div style="font-size: 0.75rem; color: #888; margin-top: 4px; font-family: Arial, sans-serif;">
                    <?= htmlspecialchars($view_loi['contact_person'] ?? $_SESSION['full_name']) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($req_row): ?>
    <!-- Requirements from Evaluator -->
    <div class="card" style="position: sticky; top: 20px;">
        <div class="card-header">
            <h3><i class="fas fa-list-check" style="color:var(--primary-600);margin-right:8px;"></i> Requirements to Submit</h3>
            <small style="color:var(--neutral-400);">Sent <?= formatDate($req_row['sent_at']) ?></small>
        </div>
        <div class="card-body" style="padding: 20px;">
            <p style="color:var(--neutral-600);font-size:0.85rem;margin-bottom:16px;">
                Please prepare and submit the following documents for your halal certification application:
            </p>
            <?php
            // Parse lines and group by section headers (lines that don't start with a number-dot-number pattern)
            $lines = array_filter(array_map('trim', explode("\n", $req_row['requirements'])));
            $sections = [];
            $current_section = null;
            foreach ($lines as $line) {
                // Section header: starts with a digit followed by a dot and a space but NOT "digit.digit"
                if (preg_match('/^\d+\.\s+[A-Z]/', $line)) {
                    $current_section = $line;
                    $sections[$current_section] = [];
                } else {
                    if ($current_section === null) { $current_section = ''; $sections[$current_section] = []; }
                    $sections[$current_section][] = $line;
                }
            }

            $section_icons = [
                '1.' => 'fa-file-alt',
                '2.' => 'fa-cogs',
                '3.' => 'fa-boxes',
                '4.' => 'fa-check-shield',
            ];
            $section_colors = [
                '1.' => ['bg' => '#dbeafe', 'color' => '#1e40af'],
                '2.' => ['bg' => '#dcfce7', 'color' => '#166534'],
                '3.' => ['bg' => '#fef9c3', 'color' => '#854d0e'],
                '4.' => ['bg' => '#ede9fe', 'color' => '#5b21b6'],
            ];

            foreach ($sections as $section_title => $items):
                if (empty($items)) continue;
                $prefix = substr(trim($section_title), 0, 2);
                $icon   = $section_icons[$prefix]   ?? 'fa-folder';
                $colors = $section_colors[$prefix]  ?? ['bg' => '#f1f5f9', 'color' => '#475569'];
            ?>
            <div style="margin-bottom: 14px; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                <?php if ($section_title): ?>
                <div style="background: <?= $colors['bg'] ?>; padding: 9px 14px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas <?= $icon ?>" style="color: <?= $colors['color'] ?>; font-size: 0.8rem;"></i>
                    <span style="font-weight: 700; font-size: 0.8rem; color: <?= $colors['color'] ?>; text-transform: uppercase; letter-spacing: 0.04em;">
                        <?= htmlspecialchars($section_title) ?>
                    </span>
                </div>
                <?php endif; ?>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php foreach ($items as $i => $item):
                        // Check if optional
                        $is_optional = stripos($item, 'optional') !== false || stripos($item, 'if applicable') !== false;
                    ?>
                    <li style="display: flex; align-items: flex-start; gap: 10px; padding: 8px 14px; border-bottom: 1px solid #f1f5f9; font-size: 0.83rem; color: var(--neutral-700); <?= $i === count($items)-1 ? 'border-bottom:none' : '' ?>">
                        <i class="fas fa-circle" style="color: var(--primary-400); font-size: 0.35rem; margin-top: 6px; flex-shrink: 0;"></i>
                        <span>
                            <?= htmlspecialchars($item) ?>
                            <?php if ($is_optional): ?>
                            <span style="font-size: 0.72rem; color: #94a3b8; font-style: italic; margin-left: 4px;">(optional)</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>

            <div style="margin-top: 18px;">
                <a href="<?= BASE_URL ?>dashboard/business_owner/applications.php?action=create&loi_id=<?= $view_loi['id'] ?>" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-arrow-right"></i> Proceed to Submit Application
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php if ($view_loi['feedback']): ?>
<div class="alert <?= $view_loi['status'] === 'returned' ? 'alert-warning' : 'alert-info' ?>" style="margin-top: 20px;">
    <i class="fas fa-<?= $view_loi['status'] === 'returned' ? 'exclamation-triangle' : 'comment' ?>"></i>
    <div style="flex: 1;">
        <strong>Evaluator Feedback</strong>
        <p style="margin-top: 4px;"><?= nl2br(htmlspecialchars($view_loi['feedback'])) ?></p>
        <?php if ($view_loi['evaluator_name']): ?>
            <small>By: <?= htmlspecialchars($view_loi['evaluator_name']) ?> | <?= $view_loi['verified_at'] ? formatDateTime($view_loi['verified_at']) : '' ?></small>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($view_loi['status'] === 'returned'): ?>
<div style="margin-top: 16px; padding: 20px; background: var(--neutral-50); border: 1px solid var(--neutral-200); border-radius: 14px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div>
        <div style="font-weight: 700; color: var(--neutral-800);">Your letter was returned by the evaluator.</div>
        <div style="font-size: 0.85rem; color: var(--neutral-500); margin-top: 2px;">Please review the feedback above, update your details, and resubmit.</div>
    </div>
    <a href="?action=edit&id=<?= $view_loi['id'] ?>" class="btn btn-primary">
        <i class="fas fa-edit"></i> Edit & Resubmit
    </a>
</div>
<?php endif; ?>

<?php elseif ($action === 'select_body'): ?>
<!-- Certifying Body Selection -->
<a href="?action=list" class="btn btn-outline btn-sm" style="margin-bottom: 20px;"><i class="fas fa-arrow-left"></i> Back</a>

<div style="text-align: center; margin-bottom: 32px;">
    <h2 style="font-family: var(--font-display); font-weight: 800; color: var(--neutral-900); margin-bottom: 8px;">Choose a Halal Certifying Body</h2>
    <p style="color: var(--neutral-500);">Select the certifying body you wish to apply with.</p>
</div>

<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; max-width: 900px; margin: 0 auto;">

    <!-- HDIP — Available -->
    <a href="?action=create&body=HDIP" style="text-decoration: none;">
        <div style="background: white; border: 2px solid var(--primary-400); border-radius: 20px; padding: 32px 24px; text-align: center; cursor: pointer; transition: all .2s; box-shadow: 0 4px 20px rgba(0,0,0,0.06);"
             onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,0.12)'"
             onmouseout="this.style.transform='';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.06)'">
            <!-- HDIP logo as SVG/text since no image file -->
            <div style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid #111; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 0 auto 16px; background: white; position: relative;">
                <div style="font-size: 0.55rem; font-weight: 800; letter-spacing: 2px; color: #111; text-align: center; line-height: 1.2;">PHILIPPINES</div>
                <div style="font-size: 1.6rem; font-family: 'Times New Roman', serif; color: #111; line-height: 1;">حلال</div>
                <div style="font-size: 0.6rem; color: #111; margin-top: 2px;">Halal</div>
                <div style="position: absolute; bottom: -14px; background: white; border: 2px solid #111; padding: 2px 10px; font-size: 0.6rem; font-weight: 800; letter-spacing: 2px; color: #111;">HDIP</div>
            </div>
            <div style="margin-top: 20px;">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--neutral-900); margin-bottom: 4px;">Halal Development Institute of the Philippines</div>
                <div style="font-size: 0.8rem; color: var(--neutral-500); margin-bottom: 12px;">(HDIP)</div>
                <span style="background: var(--primary-100); color: var(--primary-700); font-size: 0.75rem; font-weight: 600; padding: 4px 12px; border-radius: 99px;">
                    <i class="fas fa-check-circle"></i> Available
                </span>
            </div>
        </div>
    </a>

    <!-- MinHA — Coming Soon -->
    <div style="background: white; border: 2px solid var(--neutral-200); border-radius: 20px; padding: 32px 24px; text-align: center; opacity: 0.65; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 14px; right: 14px; background: var(--neutral-200); color: var(--neutral-500); font-size: 0.7rem; font-weight: 700; padding: 3px 10px; border-radius: 99px; text-transform: uppercase; letter-spacing: 1px;">Coming Soon</div>
        <!-- MinHA logo representation -->
        <div style="width: 100px; height: 100px; border-radius: 8px; border: 3px solid #2d7a2d; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 0 auto 16px; background: white; overflow: hidden;">
            <div style="background: #2d7a2d; width: 100%; text-align: center; padding: 4px 0; font-size: 0.65rem; font-weight: 800; color: white; letter-spacing: 1px;">HALAL</div>
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; position: relative; width: 100%;">
                <div style="font-size: 1.5rem; font-family: 'Times New Roman', serif; color: #111; z-index: 1;">حلال</div>
                <div style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); font-size: 1.8rem; color: #e6c800; line-height: 1;">☽</div>
            </div>
            <div style="background: #e6c800; width: 100%; text-align: center; padding: 4px 0; font-size: 0.6rem; font-weight: 800; color: #111;">Philippines</div>
        </div>
        <div style="font-weight: 700; font-size: 0.95rem; color: var(--neutral-700); margin-bottom: 4px;">Mindanao Halal Authority</div>
        <div style="font-size: 0.8rem; color: var(--neutral-400);">(MinHA)</div>
    </div>

    <!-- MMHCBI — Coming Soon -->
    <div style="background: white; border: 2px solid var(--neutral-200); border-radius: 20px; padding: 32px 24px; text-align: center; opacity: 0.65; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 14px; right: 14px; background: var(--neutral-200); color: var(--neutral-500); font-size: 0.7rem; font-weight: 700; padding: 3px 10px; border-radius: 99px; text-transform: uppercase; letter-spacing: 1px;">Coming Soon</div>
        <!-- MMHCBI logo representation -->
        <div style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid #2d7a2d; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 0 auto 16px; background: white; position: relative;">
            <div style="font-size: 0.45rem; font-weight: 700; color: #2d7a2d; text-align: center; line-height: 1.3; padding: 0 6px;">Muslim Mindanao Halal Certification Board</div>
            <div style="font-size: 1.4rem; font-family: 'Times New Roman', serif; color: #2d7a2d; line-height: 1; margin: 2px 0;">حلال</div>
            <div style="font-size: 0.65rem; font-weight: 800; color: #2d7a2d; letter-spacing: 1px;">HALAL</div>
            <div style="position: absolute; bottom: 6px; font-size: 0.45rem; color: #2d7a2d;">★ Philippines ★</div>
        </div>
        <div style="font-weight: 700; font-size: 0.95rem; color: var(--neutral-700); margin-bottom: 4px;">Muslim Mindanao Halal Certificate Board, Inc.</div>
        <div style="font-size: 0.8rem; color: var(--neutral-400);">(MMHCBI)</div>
    </div>

</div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
<!-- Create/Edit LOI Form -->
<a href="<?= $action === 'create' ? '?action=select_body' : '?action=list' ?>" class="btn btn-outline btn-sm" style="margin-bottom: 20px;"><i class="fas fa-arrow-left"></i> <?= $action === 'create' ? 'Back to Selection' : 'Back to List' ?></a>

<?php $selected_body = $_GET['body'] ?? $edit_loi['certifying_body'] ?? 'HDIP'; ?>

<div style="display: flex; align-items: center; gap: 14px; background: var(--primary-50); border: 1px solid var(--primary-200); border-radius: 14px; padding: 14px 20px; margin-bottom: 24px;">
    <div style="width: 44px; height: 44px; border-radius: 50%; border: 2px solid #111; display: flex; flex-direction: column; align-items: center; justify-content: center; background: white; flex-shrink: 0;">
        <div style="font-size: 0.9rem; font-family: 'Times New Roman', serif; color: #111; line-height: 1;">حلال</div>
        <div style="font-size: 0.35rem; font-weight: 800; letter-spacing: 1px; color: #111;">HDIP</div>
    </div>
    <div>
        <div style="font-weight: 700; color: var(--primary-800); font-size: 0.95rem;">Halal Development Institute of the Philippines (HDIP)</div>
        <div style="font-size: 0.8rem; color: var(--primary-600);">Selected certifying body</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-pen-fancy" style="color: var(--primary-600); margin-right: 8px;"></i> <?= $edit_loi ? 'Edit' : 'Create' ?> Letter of Intent</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="?action=<?= $action ?>" enctype="multipart/form-data">
            <?php if ($edit_loi): ?>
                <input type="hidden" name="loi_id" value="<?= $edit_loi['id'] ?>">
            <?php endif; ?>
            <input type="hidden" name="certifying_body" value="<?= htmlspecialchars($selected_body) ?>">
            
            <h4 style="font-family: var(--font-display); font-weight: 700; margin-bottom: 16px; color: var(--neutral-700);">
                <i class="fas fa-building" style="color: var(--primary-500);"></i> Company Information
            </h4>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Company Name <span class="required">*</span></label>
                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($edit_loi['company_name'] ?? '') ?>" required placeholder="Enter company name">
                </div>
                    </select>
                </div>
                <div class="form-group">
                    <label>Application Type <span class="required">*</span></label>
                    <select name="application_type" class="form-control" required>
                        <option value="">Select...</option>
                        <option value="Initial Application" <?= ($edit_loi['application_type'] ?? '') === 'Initial Application' ? 'selected' : '' ?>>Initial Application</option>
                        <option value="Renewal" <?= ($edit_loi['application_type'] ?? '') === 'Renewal' ? 'selected' : '' ?>>Renewal</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date of Intent <span class="required">*</span></label>
                    <input type="date" name="date_of_intent" class="form-control" value="<?= htmlspecialchars($edit_loi['date_of_intent'] ?? date('Y-m-d')) ?>" required>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Company Address <span class="required">*</span></label>
                    <textarea name="company_address" class="form-control" rows="2" required placeholder="Complete company address"><?= htmlspecialchars($edit_loi['company_address'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Contact Person</label>
                    <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($edit_loi['contact_person'] ?? $_SESSION['full_name']) ?>" placeholder="Contact person name">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Contact Email</label>
                    <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($edit_loi['contact_email'] ?? $_SESSION['email']) ?>" placeholder="Email address">
                </div>
                <div class="form-group">
                    <label>Contact Phone</label>
                    <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($edit_loi['contact_phone'] ?? '') ?>" placeholder="Phone number">
                </div>
            </div>
            
            
            <div class="form-group">
                <label>Menu List</label>
                <?php
                    $existing_items = [];
                    if (!empty($edit_loi['menu_list'])) {
                        $existing_items = array_filter(explode("\n", $edit_loi['menu_list']));
                    }
                    if (empty($existing_items)) $existing_items = [''];
                ?>
                <div id="menuItemsContainer">
                    <?php foreach ($existing_items as $item): ?>
                    <div class="menu-item-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                        <input type="text" name="menu_items[]" class="form-control" value="<?= htmlspecialchars(trim($item)) ?>" placeholder="e.g. Chicken Adobo">
                        <button type="button" class="btn btn-sm btn-outline" onclick="removeMenuItem(this)" title="Remove" style="flex-shrink:0;"><i class="fas fa-times"></i></button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-sm btn-outline" onclick="addMenuItem()" style="margin-top: 6px;">
                    <i class="fas fa-plus"></i> Add Menu Item
                </button>
                <p class="form-text">List all menu items or products you want to certify as halal.</p>
            </div>
            
            <!-- Digital Signature -->
            <div class="form-group" style="margin-top: 24px;">
                <label style="font-weight: 600; color: var(--neutral-700); display: block; margin-bottom: 8px;">
                    <i class="fas fa-signature" style="color: var(--primary-500); margin-right: 6px;"></i>
                    Digital Signature <span class="required">*</span>
                </label>
                <p style="font-size: 0.85rem; color: var(--neutral-500); margin-bottom: 10px;">
                    Sign below using your mouse or touchscreen. This signature will appear on your Letter of Intent.
                </p>
                <div style="border: 2px dashed var(--neutral-300); border-radius: 12px; background: #fafafa; position: relative; overflow: hidden;">
                    <canvas id="signatureCanvas" width="700" height="160"
                        style="display: block; width: 100%; height: 160px; cursor: crosshair; touch-action: none;"></canvas>
                    <?php if (!empty($edit_loi['signature_data'])): ?>
                    <div id="existingSigNote" style="position: absolute; top: 8px; right: 12px; font-size: 0.75rem; color: var(--primary-600); background: var(--primary-50); padding: 3px 10px; border-radius: 99px;">
                        <i class="fas fa-check-circle"></i> Signature on file — redraw to replace
                    </div>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 8px; margin-top: 8px; align-items: center;">
                    <button type="button" class="btn btn-sm btn-outline" onclick="clearSignature()">
                        <i class="fas fa-eraser"></i> Clear
                    </button>
                    <label class="btn btn-sm btn-outline" style="cursor:pointer; margin: 0;">
                        <i class="fas fa-upload"></i> Upload Signature
                        <input type="file" id="sigUploadInput" accept="image/png,image/jpeg,image/jpg"
                               style="display:none;">
                    </label>
                    <span id="sigStatus" style="font-size: 0.8rem; color: var(--neutral-400);">
                        <?= !empty($edit_loi['signature_data']) ? '<span style="color:var(--primary-600)"><i class="fas fa-check-circle"></i> Signature saved</span>' : 'No signature yet' ?>
                    </span>
                </div>
                <input type="hidden" name="signature_data" id="signatureData"
                    value="<?= htmlspecialchars($edit_loi['signature_data'] ?? '') ?>">
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <a href="?action=list" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                <button type="submit" name="submit_action" value="draft" class="btn btn-outline"><i class="fas fa-save"></i> Save as Draft</button>
                <button type="submit" name="submit_action" value="submit" class="btn btn-primary" id="submitBtn"><i class="fas fa-paper-plane"></i> Submit LOI</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
// ── Menu Items ─────────────────────────────────────────────
function addMenuItem() {
    const container = document.getElementById('menuItemsContainer');
    const row = document.createElement('div');
    row.className = 'menu-item-row';
    row.style.cssText = 'display:flex;gap:8px;margin-bottom:8px;';
    row.innerHTML = '<input type="text" name="menu_items[]" class="form-control" placeholder="e.g. Chicken Adobo">'
        + '<button type="button" class="btn btn-sm btn-outline" onclick="removeMenuItem(this)" title="Remove" style="flex-shrink:0;"><i class="fas fa-times"></i></button>';
    container.appendChild(row);
    row.querySelector('input').focus();
}

function removeMenuItem(btn) {
    const container = document.getElementById('menuItemsContainer');
    if (container.querySelectorAll('.menu-item-row').length > 1) {
        btn.closest('.menu-item-row').remove();
    } else {
        btn.closest('.menu-item-row').querySelector('input').value = '';
    }
}

// ── Signature Pad ──────────────────────────────────────────
(function () {
    const canvas = document.getElementById('signatureCanvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const input = document.getElementById('signatureData');
    const status = document.getElementById('sigStatus');
    let drawing = false;
    let hasSig = input.value.length > 0;

    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        canvas.width  = rect.width  * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);
        ctx.strokeStyle = '#1a1a2e';
        ctx.lineWidth   = 2.2;
        ctx.lineCap     = 'round';
        ctx.lineJoin    = 'round';
        if (hasSig && input.value && input.value.startsWith('data:')) {
            const img = new Image();
            img.onload = () => ctx.drawImage(img, 0, 0, rect.width, rect.height);
            img.src = input.value;
        }
    }
    resizeCanvas();

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const src  = e.touches ? e.touches[0] : e;
        return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    }
    function startDraw(e) {
        e.preventDefault();
        drawing = true;
        const p = getPos(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    }
    function draw(e) {
        if (!drawing) return;
        e.preventDefault();
        const p = getPos(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
    }
    function endDraw() {
        if (!drawing) return;
        drawing = false;
        input.value = canvas.toDataURL('image/png');
        hasSig = true;
        setStatus('<i class="fas fa-check-circle"></i> Signature captured');
        const note = document.getElementById('existingSigNote');
        if (note) note.remove();
    }

    canvas.addEventListener('mousedown',  startDraw);
    canvas.addEventListener('mousemove',  draw);
    canvas.addEventListener('mouseup',    endDraw);
    canvas.addEventListener('mouseleave', endDraw);
    canvas.addEventListener('touchstart', startDraw, { passive: false });
    canvas.addEventListener('touchmove',  draw,      { passive: false });
    canvas.addEventListener('touchend',   endDraw);

    // ── Upload signature image ─────────────────────────────
    const uploadInput = document.getElementById('sigUploadInput');
    if (uploadInput) {
        uploadInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            if (!file.type.match(/image\/(png|jpeg|jpg)/)) {
                alert('Please upload a PNG or JPG image.');
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = new Image();
                img.onload = function () {
                    const rect = canvas.getBoundingClientRect();
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    // Scale image to fit canvas height, centered
                    const scale = Math.min(rect.width / img.width, rect.height / img.height);
                    const w = img.width * scale;
                    const h = img.height * scale;
                    const x = (rect.width - w) / 2;
                    const y = (rect.height - h) / 2;
                    ctx.drawImage(img, x, y, w, h);
                    input.value = canvas.toDataURL('image/png');
                    hasSig = true;
                    setStatus('<i class="fas fa-check-circle"></i> Signature uploaded');
                    const note = document.getElementById('existingSigNote');
                    if (note) note.remove();
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    function setStatus(html) {
        if (status) status.innerHTML = '<span style="color:var(--primary-600)">' + html + '</span>';
    }
})();

window.clearSignature = function () {
    const canvas = document.getElementById('signatureCanvas');
    const input  = document.getElementById('signatureData');
    const status = document.getElementById('sigStatus');
    const upload = document.getElementById('sigUploadInput');
    if (!canvas) return;
    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
    input.value = '';
    if (upload) upload.value = '';
    if (status) status.innerHTML = 'No signature yet';
};
</script>
