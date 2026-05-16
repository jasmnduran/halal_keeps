<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_AUDITOR_TECHNICAL, ROLE_AUDITOR_SHARIAH]);

$page_title    = 'NCR Reports';
$page_heading  = 'Non-Conformance Reports';
$is_dashboard  = true;
$breadcrumbs   = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/auditor/'], ['label' => 'NCR Reports']];
$user_id       = $_SESSION['user_id'];
$error = ''; $success = '';

// ── Ensure extra columns exist ────────────────────────────────────────────────
$conn->query("ALTER TABLE ncr_reports
    ADD COLUMN IF NOT EXISTS `time_of_inspection`  TIME         DEFAULT NULL AFTER `deadline`,
    ADD COLUMN IF NOT EXISTS `location`            VARCHAR(255) DEFAULT NULL AFTER `time_of_inspection`,
    ADD COLUMN IF NOT EXISTS `person_in_charge`    VARCHAR(255) DEFAULT NULL AFTER `location`,
    ADD COLUMN IF NOT EXISTS `document_reference`  VARCHAR(255) DEFAULT NULL AFTER `person_in_charge`,
    ADD COLUMN IF NOT EXISTS `brief_summary`       TEXT         DEFAULT NULL AFTER `document_reference`,
    ADD COLUMN IF NOT EXISTS `recommendation`      TEXT         DEFAULT NULL AFTER `brief_summary`,
    ADD COLUMN IF NOT EXISTS `followup_date`       DATE         DEFAULT NULL AFTER `recommendation`,
    ADD COLUMN IF NOT EXISTS `prepared_by`         INT(11)      DEFAULT NULL AFTER `followup_date`,
    ADD COLUMN IF NOT EXISTS `requires_lab_test`   TINYINT(1)   NOT NULL DEFAULT 0 AFTER `prepared_by`,
    ADD COLUMN IF NOT EXISTS `auditor_type`        ENUM('technical','shariah') DEFAULT NULL AFTER `requires_lab_test`,
    ADD COLUMN IF NOT EXISTS `lab_test_details`    TEXT         DEFAULT NULL AFTER `auditor_type`
");

// Extend category ENUM to include 'serious'
$conn->query("ALTER TABLE ncr_reports
    MODIFY COLUMN `category` ENUM('minor','major','serious','observation') DEFAULT 'minor'
");

// ── Handle form submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ncr'])) {
    $inspection_id      = intval($_POST['inspection_id']);
    $app_id             = intval($_POST['application_id']);
    $category           = sanitize($_POST['category']);
    $brief_summary      = sanitize($_POST['brief_summary'] ?? '');
    $finding_details    = sanitize($_POST['finding_details'] ?? '');
    $corrective_action  = sanitize($_POST['corrective_action_required'] ?? '');
    $recommendation     = sanitize($_POST['recommendation'] ?? '');
    $deadline           = sanitize($_POST['deadline'] ?? '');
    $followup_date      = sanitize($_POST['followup_date'] ?? '');
    $time_of_inspection = sanitize($_POST['time_of_inspection'] ?? '');
    $location           = sanitize($_POST['location'] ?? '');
    $person_in_charge   = sanitize($_POST['person_in_charge'] ?? '');
    $document_reference = sanitize($_POST['document_reference'] ?? '');
    $requires_lab_test  = ($_SESSION['role_id'] == ROLE_AUDITOR_SHARIAH && isset($_POST['requires_lab_test']) && $_POST['requires_lab_test'] == '1') ? 1 : 0;
    $lab_test_details   = '';
    if ($requires_lab_test && isset($_POST['lab_test_details'])) {
        $lab_test_details = sanitize($_POST['lab_test_details']);
    }
    $auditor_type       = ($_SESSION['role_id'] == ROLE_AUDITOR_SHARIAH) ? 'shariah' : 'technical';
    $ncr_number         = generateReference('NCR');

    $stmt = $conn->prepare("
        INSERT INTO ncr_reports
            (inspection_id, application_id, ncr_number, category,
             brief_summary, finding_details, corrective_action_required,
             recommendation, deadline, followup_date,
             time_of_inspection, location, person_in_charge, document_reference,
             prepared_by, status, requires_lab_test, auditor_type, lab_test_details)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', ?, ?, ?)
    ");
    $stmt->bind_param(
        "iisssssssssssssiss",
        $inspection_id, $app_id, $ncr_number, $category,
        $brief_summary, $finding_details, $corrective_action,
        $recommendation, $deadline, $followup_date,
        $time_of_inspection, $location, $person_in_charge, $document_reference,
        $user_id, $requires_lab_test, $auditor_type, $lab_test_details
    );

    if ($stmt->execute()) {
        // Notify business owner
        $owner_q = $conn->prepare("SELECT business_owner_id FROM hdp_applications WHERE id = ?");
        $owner_q->bind_param("i", $app_id);
        $owner_q->execute();
        $owner = $owner_q->get_result()->fetch_assoc();
        if ($owner) {
            createNotification(
                $conn,
                $owner['business_owner_id'],
                'NCR Issued: ' . $ncr_number,
                'A Non-Conformance Report (' . strtoupper($category) . ') has been issued for your application. Please review and take corrective action.',
                'warning',
                BASE_URL . 'dashboard/business_owner/audit_findings.php'
            );
        }

        // If Shariah auditor requires a lab test, notify all lab analysts
        if ($requires_lab_test) {
            $lab_q = $conn->query("SELECT id FROM users WHERE role_id = " . ROLE_LABORATORY_ANALYST . " AND role_status = 'approved'");
            if ($lab_q) {
                while ($lab = $lab_q->fetch_assoc()) {
                    createNotification(
                        $conn,
                        $lab['id'],
                        'Laboratory Test Required — ' . $ncr_number,
                        'The Shariah Auditor has flagged that a laboratory test is required for this application as part of NCR ' . $ncr_number . '. Please proceed with sample collection.',
                        'action_required',
                        BASE_URL . 'dashboard/lab_analyst/'
                    );
                }
            }
        }

        logActivity($conn, $user_id, 'NCR Created', 'NCR ' . $ncr_number . ' for App #' . $app_id, 'audit');
        $success = 'NCR Report ' . $ncr_number . ' created successfully.';
    } else {
        $error = 'Failed to save NCR: ' . $conn->error;
    }
}

// ── Load data ─────────────────────────────────────────────────────────────────
$completed = $conn->query("
    SELECT i.*, loi.company_name, ha.id AS app_id,
           s.location AS schedule_location, s.schedule_date, s.schedule_time,
           u.full_name AS owner_name
    FROM inspections i
    JOIN hdp_applications ha ON i.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    JOIN inspection_schedules s ON i.schedule_id = s.id
    JOIN users u ON ha.business_owner_id = u.id
    WHERE i.status = 'completed'
      AND i.conformity_status IN ('non_conforming','partial')
    ORDER BY i.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$ncrs = $conn->query("
    SELECT n.*,
           loi.company_name,
           loi.company_address,
           u.full_name AS auditor_name,
           ut.full_name AS tech_name,
           us.full_name AS shariah_name
    FROM ncr_reports n
    JOIN hdp_applications ha ON n.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    LEFT JOIN users u ON n.prepared_by = u.id
    LEFT JOIN inspections i ON n.inspection_id = i.id
    LEFT JOIN users ut ON i.auditor_technical_id = ut.id
    LEFT JOIN users us ON i.auditor_shariah_id = us.id
    ORDER BY n.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// View single NCR for print
$view_ncr = null;
if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    foreach ($ncrs as $n) {
        if ($n['id'] === $view_id) { $view_ncr = $n; break; }
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

<?php if ($view_ncr): ?>
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- NCR PRINT VIEW                                                            -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div style="margin-bottom:16px;display:flex;gap:10px;align-items:center;">
    <a href="<?= BASE_URL ?>dashboard/auditor/ncr_reports.php" class="btn btn-outline btn-sm">
        <i class="fas fa-arrow-left"></i> Back to List
    </a>
    <button onclick="window.print()" class="btn btn-primary btn-sm">
        <i class="fas fa-print"></i> Print NCR
    </button>
    <a href="<?= BASE_URL ?>dashboard/auditor/ncr_pdf.php?id=<?= $view_ncr['id'] ?>" class="btn btn-success btn-sm">
        <i class="fas fa-file-pdf"></i> Download PDF
    </a>
</div>

<div class="ncr-document" id="ncrPrintArea">
    <!-- Header bar -->
    <div style="background:var(--primary-700);color:#fff;padding:10px 18px;display:flex;justify-content:space-between;align-items:center;border-radius:8px 8px 0 0;">
        <div>
            <div style="font-weight:800;font-size:1.05rem;letter-spacing:0.04em;">NON-CONFORMANCE REPORT</div>
            <div style="font-size:0.78rem;opacity:0.8;">Halal Institute of Development Philippines</div>
        </div>
        <div style="text-align:right;">
            <div style="font-weight:700;font-size:1rem;"><?= htmlspecialchars($view_ncr['ncr_number']) ?></div>
            <div style="font-size:0.75rem;opacity:0.8;">
                <?php
                    $cat_colors = ['minor'=>'#fbbf24','major'=>'#f97316','serious'=>'#ef4444','observation'=>'#60a5fa'];
                    $cat_color  = $cat_colors[$view_ncr['category']] ?? '#94a3b8';
                ?>
                <span style="background:<?= $cat_color ?>;color:#fff;padding:2px 10px;border-radius:99px;font-weight:700;text-transform:uppercase;font-size:0.7rem;">
                    <?= htmlspecialchars(ucfirst($view_ncr['category'])) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Info grid -->
    <table class="ncr-table" style="width:100%;border-collapse:collapse;border:1px solid #cbd5e1;font-size:0.88rem;">
        <tr>
            <td class="ncr-label">DATE</td>
            <td><?= $view_ncr['deadline'] ? formatDate($view_ncr['created_at']) : formatDate($view_ncr['created_at']) ?></td>
            <td class="ncr-label">LOCATION</td>
            <td><?= htmlspecialchars($view_ncr['location'] ?? $view_ncr['company_address'] ?? '—') ?></td>
        </tr>
        <tr>
            <td class="ncr-label">TIME</td>
            <td><?= $view_ncr['time_of_inspection'] ? date('h:i A', strtotime($view_ncr['time_of_inspection'])) : '—' ?></td>
            <td class="ncr-label">AUDITOR</td>
            <td><?= htmlspecialchars($view_ncr['auditor_name'] ?? ($view_ncr['tech_name'] ?? $view_ncr['shariah_name'] ?? '—')) ?></td>
        </tr>
        <tr>
            <td class="ncr-label">PERSON IN CHARGE</td>
            <td colspan="3"><?= htmlspecialchars($view_ncr['person_in_charge'] ?? '—') ?></td>
        </tr>
        <tr>
            <td class="ncr-label">DOCUMENT REFERENCE</td>
            <td colspan="3"><?= htmlspecialchars($view_ncr['document_reference'] ?? 'HAS/MANUAL/IHA-CHKLIST/01-2016') ?></td>
        </tr>
    </table>

    <!-- NCR Level -->
    <div style="border:1px solid #cbd5e1;border-top:none;padding:14px 18px;">
        <strong>NCR Level:</strong>
        <?php foreach (['minor' => 'Minor', 'major' => 'Major', 'serious' => 'Serious', 'observation' => 'Observation'] as $val => $lbl): ?>
        <span style="margin-left:18px;display:inline-flex;align-items:center;gap:6px;">
            <span style="width:14px;height:14px;border:2px solid #374151;border-radius:2px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                <?php if ($view_ncr['category'] === $val): ?>
                    <i class="fas fa-check" style="font-size:0.6rem;color:#374151;"></i>
                <?php endif; ?>
            </span>
            <?= $lbl ?>
        </span>
        <?php endforeach; ?>
    </div>

    <!-- Brief Summary -->
    <div style="border:1px solid #cbd5e1;border-top:none;padding:14px 18px;min-height:90px;">
        <div style="font-weight:700;text-decoration:underline;margin-bottom:8px;">Brief Summary (Please Specify)</div>
        <div style="line-height:1.7;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($view_ncr['brief_summary'] ?? $view_ncr['finding_details'])) ?></div>
    </div>

    <!-- CARs -->
    <div style="border:1px solid #cbd5e1;border-top:none;padding:14px 18px;min-height:120px;">
        <div style="font-weight:700;margin-bottom:8px;">CARs (Please Specify)</div>
        <div style="line-height:1.7;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($view_ncr['corrective_action_required'] ?? '—')) ?></div>
    </div>

    <!-- Recommendation -->
    <div style="border:1px solid #cbd5e1;border-top:none;padding:14px 18px;min-height:90px;">
        <div style="font-weight:700;margin-bottom:8px;">Recommendation</div>
        <div style="line-height:1.7;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($view_ncr['recommendation'] ?? '—')) ?></div>
    </div>

    <!-- Due Date -->
    <div style="border:1px solid #cbd5e1;border-top:none;padding:14px 18px;min-height:70px;">
        <div style="font-weight:700;margin-bottom:8px;">Due Date to be Implemented</div>
        <div><?= $view_ncr['deadline'] ? formatDate($view_ncr['deadline']) : '—' ?></div>
    </div>

    <!-- Follow-up -->
    <div style="border:1px solid #cbd5e1;border-top:none;padding:14px 18px;min-height:70px;">
        <div style="font-weight:700;margin-bottom:8px;">Verification / Follow Up Audit</div>
        <div><?= $view_ncr['followup_date'] ? formatDate($view_ncr['followup_date']) : '—' ?></div>
    </div>

    <?php if (!empty($view_ncr['auditor_type'])): ?>
    <!-- Auditor type + lab test -->
    <div style="border:1px solid #cbd5e1;border-top:none;padding:14px 18px;display:flex;gap:40px;flex-wrap:wrap;">
        <div>
            <strong>Prepared by:</strong>
            <?= htmlspecialchars(ucfirst($view_ncr['auditor_type'])) ?> Auditor
        </div>
        <?php if ($view_ncr['auditor_type'] === 'shariah'): ?>
        <div>
            <strong>Laboratory Test Required:</strong>
            <?php if ($view_ncr['requires_lab_test']): ?>
                <span style="color:#7e22ce;font-weight:700;"><i class="fas fa-flask"></i> Yes</span>
            <?php else: ?>
                <span style="color:#64748b;">No</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($view_ncr['auditor_type'] === 'shariah' && $view_ncr['requires_lab_test'] && !empty($view_ncr['lab_test_details'])): ?>
    <!-- Lab Test Details -->
    <?php
        // Decode HTML entities first, then decode JSON
        $labTestJson = html_entity_decode($view_ncr['lab_test_details']);
        $labTests = json_decode($labTestJson, true);
        if ($labTests && is_array($labTests)):
            $categoryLabels = [
                'calibration' => 'Calibration',
                'microbiology' => 'Microbiology',
                'chemistry' => 'Chemistry'
            ];
            $testLabels = [
                // Calibration tests
                'tank_truck_12kl_14kl' => 'Tank Truck 12KL - 14KL',
                'tank_truck_16kl_22kl' => 'Tank Truck 16KL - 22KL',
                'tank_truck_24kl_30kl' => 'Tank Truck 24KL - 30KL',
                'tank_truck_32kl_40kl' => 'Tank Truck 32KL - 40KL',
                'calibrating_beaker_volumetric_30l' => 'Calibrating Beaker Volumetric 30 L',
                'proving_tank_100l_to_400l' => 'Proving Tank 100 L to 400 L',
                'proving_tank_401l_to_2000l' => 'Proving Tank 401 L to 2000 L',
                'proving_tank_2001l_to_5000l' => 'Proving Tank 2001 L to 5000 L',
                'test_measure_ignimbangan_up_to_30l' => 'Test Measure (Ignimbangan) up to 30 L',
                'thermometry_all_types' => 'Thermometry Calibration (All Types / Thermocouple)',
                'hygrometer' => 'Hygrometer',
                'mass_test_weights_class_e1_f1_f2' => 'Mass Calibration - Test Weights Class E1, F1, F2',
                'mass_stainless_steel_25kg' => 'Mass Calibration - Stainless Steel 25 kg',
                'mass_chrome_iron_cast_1mg_to_500g' => 'Mass Calibration - Chrome, Iron, Cast (1mg) to 500 g',
                'mass_chrome_iron_cast_1kg_to_50kg' => 'Mass Calibration - Chrome, Iron, Cast 1 kg to 50 kg',
                'balance_digital' => 'Balance - Digital',
                'balance_non_digital_elastic_weigher' => 'Balance - Non-Digital / Elastic Weigher',
                'flour_scale_hog_scale' => 'Flour Scale / Hog Scale',
                // Microbiology tests
                'aerobic_plate_count_pour_plate' => 'Aerobic Plate Count (Pour Plate)',
                'aerobic_plate_count_plastic_test' => 'Aerobic Plate Count (Plastic Test)',
                'coliform_count_mpn' => 'Coliform Count, MPN',
                'e_coli_detection_mpn' => 'E. coli Detection, MPN',
                'salmonella' => 'Salmonella',
                'staphylococcus_aureus_our_plated_test' => 'Staphylococcus aureus (Our Plated Test)',
                'enterobacteriaceae_count_rapid_test' => 'Enterobacteriaceae Count (Rapid Test)',
                'enterobacteriaceae_count_confirmatory' => 'Enterobacteriaceae Count (Confirmatory)',
                'campylobacter_spp_detection_rapid_test' => 'Campylobacter spp. Detection (Rapid Test)',
                'campylobacter_spp_detection_confirmatory' => 'Campylobacter spp. Detection (Confirmatory)',
                'yeast_and_mold_count' => 'Yeast and Mold Count',
                'porcine_dna'  => 'Porcine DNA',
                // Chemistry tests
                'anti_content_gravimetric_method' => 'Anti Content (Gravimetric Method)',
                'crude_fat_soxhlet_and_hydrolysis' => 'Crude Fat (Soxhlet and Hydrolysis)',
                'crude_protein' => 'Crude Protein',
                'moisture_content_gravimetric_method' => 'Moisture Content (Gravimetric Method)',
                'moisture_type_karl_fischer_calcium_aas' => 'Moisture Type Karl Fischer Calcium (AAS)',
                'potassium_aas' => 'Potassium (AAS)',
                'copper_aas' => 'Copper (AAS)',
                'iron_aas' => 'Iron (AAS)',
                'nitrogen' => 'Nitrogen',
                'nutritional_facts_formulation_drafting_nutritional_facts' => 'Nutritional Facts (Formulation & Drafting)',
                'nutritional_facts_computation_nutritional_facts' => 'Nutritional Facts (Computation)',
                'safety_information_msds' => 'Safety Information (MSDS)',
                'safety_information_sds' => 'Safety Information (SDS)',
                'total_solids_kjeldametic' => 'Total Solids (Kjeldametic)',
                'water_activity' => 'Water Activity',
                'turbidity' => 'Turbidity',
                'calcium_magnesium_sodium_potassium' => 'Calcium/Magnesium/Sodium/Potassium',
                'sulfate_nitrate_nitrites' => 'Sulfate/Nitrate/Nitrites',
                'admin_absorption_spectrophotometer' => 'Admin Absorption Spectrophotometer',
                'total_dissolved_solids_tds_gravimetric' => 'Total Dissolved Solids (TDS) (Gravimetric)',
                'total_suspended_solids_tss_gravimetric' => 'Total Suspended Solids (TSS) (Gravimetric)',
                'ph' => 'pH',
                'turbidity_nephelometric' => 'Turbidity (Nephelometric)',
                'color' => 'Color'
            ];
    ?>
    <div style="border:1px solid #cbd5e1;border-top:none;padding:14px 18px;background:#fdf4ff;">
        <div style="font-weight:700;margin-bottom:10px;color:#7e22ce;">
            <i class="fas fa-flask" style="margin-right:5px;"></i> Laboratory Tests Required
        </div>
        <?php foreach ($labTests as $category => $tests): ?>
            <?php if (!empty($tests)): ?>
            <div style="margin-bottom:10px;">
                <div style="font-weight:600;color:#7e22ce;margin-bottom:4px;">
                    <?= htmlspecialchars($categoryLabels[$category] ?? ucfirst($category)) ?>:
                </div>
                <ul style="margin:0 0 0 20px;padding:0;list-style:disc;color:#6b21a8;">
                    <?php foreach ($tests as $test): ?>
                    <li style="margin-bottom:2px;"><?= htmlspecialchars($testLabels[$test] ?? ucwords(str_replace('_', ' ', $test))) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Signature row -->
    <table style="width:100%;border-collapse:collapse;border:1px solid #cbd5e1;border-top:none;">
        <tr>
            <td style="border:1px solid #cbd5e1;padding:14px 18px;text-align:center;font-weight:700;width:33%;">Auditor</td>
            <td style="border:1px solid #cbd5e1;padding:14px 18px;text-align:center;font-weight:700;width:33%;">Auditee</td>
            <td style="border:1px solid #cbd5e1;padding:14px 18px;text-align:center;font-weight:700;width:34%;">Verified and for Compliances</td>
        </tr>
        <tr>
            <td style="border:1px solid #cbd5e1;padding:40px 18px 14px;text-align:center;font-size:0.82rem;color:var(--neutral-500);">
                <?= htmlspecialchars($view_ncr['auditor_name'] ?? '—') ?>
            </td>
            <td style="border:1px solid #cbd5e1;padding:40px 18px 14px;text-align:center;"></td>
            <td style="border:1px solid #cbd5e1;padding:40px 18px 14px;text-align:center;"></td>
        </tr>
    </table>
</div>

<style>
.ncr-document { background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08); max-width:860px; margin:0 auto; }
.ncr-label { background:#f8fafc; font-weight:700; font-size:0.8rem; text-transform:uppercase; padding:10px 14px; border:1px solid #cbd5e1; white-space:nowrap; width:18%; }
.ncr-table td { padding:10px 14px; border:1px solid #cbd5e1; vertical-align:middle; }
@media print {
    .dashboard-layout > aside,
    .dashboard-header,
    .breadcrumb,
    .btn,
    .alert { display:none !important; }
    .dashboard-main { margin-left:0 !important; }
    .dashboard-content { padding:0 !important; }
    .ncr-document { box-shadow:none; border-radius:0; max-width:100%; }
}
</style>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- NCR FORM + LIST                                                           -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->

<?php if (!empty($completed)): ?>
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h3><i class="fas fa-plus" style="color:var(--danger);margin-right:8px;"></i> Generate NCR Report</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="save_ncr" value="1">

            <!-- Header info -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:4px;">
                <div class="form-group">
                    <label>Inspection <span class="required">*</span></label>
                    <select name="inspection_id" class="form-control" required id="ncr_inspection">
                        <option value="">Select inspection...</option>
                        <?php foreach ($completed as $c): ?>
                        <option value="<?= $c['id'] ?>"
                            data-app="<?= $c['app_id'] ?>"
                            data-location="<?= htmlspecialchars($c['schedule_location'] ?? '') ?>"
                            data-time="<?= htmlspecialchars($c['schedule_time'] ?? '') ?>"
                            data-owner="<?= htmlspecialchars($c['owner_name'] ?? '') ?>">
                            <?= htmlspecialchars($c['company_name']) ?> — <?= formatDate($c['schedule_date']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="application_id" id="ncr_app_id">
                </div>
                <div class="form-group">
                    <label>NCR Level <span class="required">*</span></label>
                    <div style="display:flex;gap:16px;flex-wrap:wrap;padding-top:8px;">
                        <?php foreach (['minor'=>'Minor','major'=>'Major','serious'=>'Serious','observation'=>'Observation'] as $val => $lbl): ?>
                        <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-weight:500;">
                            <input type="radio" name="category" value="<?= $val ?>" <?= $val === 'minor' ? 'checked' : '' ?> style="width:16px;height:16px;">
                            <?= $lbl ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label>Time of Inspection</label>
                    <input type="time" name="time_of_inspection" class="form-control">
                </div>
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" id="ncr_location" class="form-control" placeholder="Inspection location">
                </div>
                <div class="form-group">
                    <label>Person in Charge</label>
                    <input type="text" name="person_in_charge" class="form-control" placeholder="Name of person in charge">
                </div>
            </div>

            <div class="form-group">
                <label>Document Reference</label>
                <input type="text" name="document_reference" class="form-control"
                    value="HAS/MANUAL/IHA-CHKLIST/01-2016"
                    placeholder="e.g. HAS/MANUAL/IHA-CHKLIST/01-2016">
            </div>

            <div class="form-group">
                <label>Brief Summary <span class="required">*</span></label>
                <textarea name="brief_summary" class="form-control" rows="3" required
                    placeholder="Briefly describe the non-conformance found..."></textarea>
            </div>

            <?php if ($_SESSION['role_id'] == ROLE_AUDITOR_TECHNICAL): ?>
            <!-- Technical auditor focus note -->
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;gap:12px;align-items:flex-start;">
                <i class="fas fa-hard-hat" style="color:#2563eb;font-size:1.1rem;margin-top:2px;flex-shrink:0;"></i>
                <div>
                    <div style="font-weight:700;color:#1e40af;margin-bottom:4px;">Technical Auditor — Scope of Inspection</div>
                    <div style="font-size:0.85rem;color:#1e40af;line-height:1.6;">
                        Your NCR should focus on: <strong>business permits &amp; licenses</strong>, <strong>physical premises</strong> (area, layout, cleanliness), <strong>building code compliance</strong>, <strong>fire safety &amp; sanitation</strong>, and <strong>operational documentation</strong>.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($_SESSION['role_id'] == ROLE_AUDITOR_SHARIAH): ?>
            <!-- Shariah auditor — lab test required toggle -->
            <div style="background:#fdf4ff;border:1px solid #e9d5ff;border-radius:10px;padding:16px 18px;margin-bottom:20px;">
                <div style="font-weight:700;color:#7e22ce;margin-bottom:10px;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-flask" style="font-size:1rem;"></i>
                    Laboratory Test Required?
                </div>
                <div style="font-size:0.85rem;color:#6b21a8;margin-bottom:14px;line-height:1.5;">
                    As the Shariah Auditor, indicate whether the business owner must undergo a laboratory test (e.g. for ingredient halal verification, contamination check, etc.).
                    If required, the laboratory analyst will be notified automatically.
                </div>
                <div style="display:flex;gap:12px;margin-bottom:16px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 20px;border-radius:8px;border:2px solid #e9d5ff;background:#fff;font-weight:600;font-size:0.9rem;transition:all .15s;" id="lab-yes-label">
                        <input type="radio" name="requires_lab_test" value="1" id="lab-yes"
                               style="width:16px;height:16px;accent-color:#7e22ce;">
                        <i class="fas fa-check-circle" style="color:#7e22ce;"></i> Yes — Lab Test Required
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:10px 20px;border-radius:8px;border:2px solid #e9d5ff;background:#fff;font-weight:600;font-size:0.9rem;transition:all .15s;" id="lab-no-label">
                        <input type="radio" name="requires_lab_test" value="0" id="lab-no" checked
                               style="width:16px;height:16px;accent-color:#7e22ce;">
                        <i class="fas fa-times-circle" style="color:#94a3b8;"></i> No — Not Required
                    </label>
                </div>
                
                <!-- Lab Test Categories Section (Hidden by default) -->
                <div id="lab-test-categories" style="display:none;border-top:1px solid #e9d5ff;padding-top:16px;margin-top:16px;">
                    <div style="font-weight:700;color:#7e22ce;margin-bottom:12px;">Select Lab Test Categories:</div>
                    
                    <!-- Calibration Category -->
                    <div style="margin-bottom:16px;padding:12px;background:#fff;border:1px solid #e9d5ff;border-radius:8px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;margin-bottom:10px;">
                            <input type="checkbox" class="lab-category-checkbox" data-category="calibration" style="width:18px;height:18px;accent-color:#7e22ce;">
                            <i class="fas fa-balance-scale" style="color:#7e22ce;"></i> Calibration
                        </label>
                        <div class="lab-tests-container" data-category="calibration" style="display:none;margin-left:26px;padding-left:12px;border-left:2px solid #e9d5ff;">
                            <div style="font-size:0.85rem;color:#6b21a8;margin-bottom:8px;">Select specific tests:</div>
                            <div style="display:grid;grid-template-columns:1fr;gap:6px;">
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_tank_truck_12kl_14kl" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Tank Truck 12KL - 14KL
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_tank_truck_16kl_22kl" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Tank Truck 16KL - 22KL
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_tank_truck_24kl_30kl" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Tank Truck 24KL - 30KL
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_tank_truck_32kl_40kl" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Tank Truck 32KL - 40KL
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_calibrating_beaker_volumetric_30l" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Calibrating Beaker Volumetric 30 L
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_proving_tank_100l_to_400l" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Proving Tank 100 L to 400 L
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_proving_tank_401l_to_2000l" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Proving Tank 401 L to 2000 L
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_proving_tank_2001l_to_5000l" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Proving Tank 2001 L to 5000 L
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_test_measure_ignimbangan_up_to_30l" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Test Measure (Ignimbangan) up to 30 L
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_thermometry_all_types" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Thermometry Calibration (All Types / Thermocouple)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_hygrometer" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Hygrometer
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_mass_test_weights_class_e1_f1_f2" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Mass Calibration - Test Weights Class E1, F1, F2
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_mass_stainless_steel_25kg" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Mass Calibration - Stainless Steel 25 kg
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_mass_chrome_iron_cast_1mg_to_500g" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Mass Calibration - Chrome, Iron, Cast (1mg) to 500 g
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_mass_chrome_iron_cast_1kg_to_50kg" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Mass Calibration - Chrome, Iron, Cast 1 kg to 50 kg
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_balance_digital" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Balance - Digital
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_balance_non_digital_elastic_weigher" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Balance - Non-Digital / Elastic Weigher
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="calibration_flour_scale_hog_scale" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Flour Scale / Hog Scale
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Microbiology Category -->
                    <div style="margin-bottom:16px;padding:12px;background:#fff;border:1px solid #e9d5ff;border-radius:8px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;margin-bottom:10px;">
                            <input type="checkbox" class="lab-category-checkbox" data-category="microbiology" style="width:18px;height:18px;accent-color:#7e22ce;">
                            <i class="fas fa-microscope" style="color:#7e22ce;"></i> Microbiology
                        </label>
                        <div class="lab-tests-container" data-category="microbiology" style="display:none;margin-left:26px;padding-left:12px;border-left:2px solid #e9d5ff;">
                            <div style="font-size:0.85rem;color:#6b21a8;margin-bottom:8px;">Select specific tests:</div>
                            <div style="display:grid;grid-template-columns:1fr;gap:6px;">
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="microbiology_aerobic_plate_count_pour_plate" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Aerobic Plate Count (Pour Plate)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="microbiology_aerobic_plate_count_plastic_test" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Aerobic Plate Count (Plastic Test)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="microbiology_coliform_count_mpn" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Coliform Count, MPN
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="microbiology_e_coli_detection_mpn" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    E. coli Detection, MPN
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="microbiology_salmonella" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Salmonella
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="microbiology_staphylococcus_aureus_our_plated_test" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Staphylococcus aureus (Our Plated Test)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="microbiology_enterobacteriaceae_count_rapid_test" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Enterobacteriaceae Count (Rapid Test)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="microbiology_enterobacteriaceae_count_confirmatory" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Enterobacteriaceae Count (Confirmatory)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="microbiology_campylobacter_spp_detection_rapid_test" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Campylobacter spp. Detection (Rapid Test)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="microbiology_campylobacter_spp_detection_confirmatory" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Campylobacter spp. Detection (Confirmatory)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="microbiology_yeast_and_mold_count" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Yeast and Mold Count
                                </label>
                                 <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="microbiology_porcine_dna" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Porcine DNA
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chemistry Category -->
                    <div style="margin-bottom:16px;padding:12px;background:#fff;border:1px solid #e9d5ff;border-radius:8px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;margin-bottom:10px;">
                            <input type="checkbox" class="lab-category-checkbox" data-category="chemistry" style="width:18px;height:18px;accent-color:#7e22ce;">
                            <i class="fas fa-flask" style="color:#7e22ce;"></i> Chemistry
                        </label>
                        <div class="lab-tests-container" data-category="chemistry" style="display:none;margin-left:26px;padding-left:12px;border-left:2px solid #e9d5ff;">
                            <div style="font-size:0.85rem;color:#6b21a8;margin-bottom:8px;">Select specific tests:</div>
                            <div style="display:grid;grid-template-columns:1fr;gap:6px;">
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_anti_content_gravimetric_method" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Anti Content (Gravimetric Method)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_crude_fat_soxhlet_and_hydrolysis" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Crude Fat (Soxhlet and Hydrolysis)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_crude_protein" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Crude Protein
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_moisture_content_gravimetric_method" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Moisture Content (Gravimetric Method)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_moisture_type_karl_fischer_calcium_aas" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Moisture Type Karl Fischer Calcium (AAS)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_potassium_aas" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Potassium (AAS)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_copper_aas" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Copper (AAS)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_iron_aas" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Iron (AAS)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_nitrogen" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Nitrogen
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_nutritional_facts_formulation_drafting_nutritional_facts" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Nutritional Facts (Formulation & Drafting of Nutritional Facts)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_nutritional_facts_computation_nutritional_facts" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Nutritional Facts (Computation of Nutritional Facts)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_safety_information_msds" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Safety Information (MSDS)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_safety_information_sds" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Safety Information (SDS)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_total_solids_kjeldametic" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Total Solids (Kjeldametic)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_water_activity" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Water Activity
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_turbidity" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Turbidity
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_calcium_magnesium_sodium_potassium" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Calcium/Magnesium/Sodium/Potassium
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_sulfate_nitrate_nitrites" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Sulfate/Nitrate/Nitrites
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_admin_absorption_spectrophotometer" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Admin Absorption Spectrophotometer
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_total_dissolved_solids_tds_gravimetric" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Total Dissolved Solids (TDS) (Gravimetric)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_total_suspended_solids_tss_gravimetric" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Total Suspended Solids (TSS) (Gravimetric)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_ph" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    pH
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_turbidity_nephelometric" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Turbidity (Nephelometric)
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;">
                                    <input type="checkbox" name="lab_tests[]" value="chemistry_color" style="width:14px;height:14px;accent-color:#7e22ce;">
                                    Color
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="lab_test_details" id="lab-test-details-hidden">
                </div>
            </div>
            <?php endif; ?>

            <!-- CARs dynamic list -->
            <div class="form-group">
                <label style="margin-bottom:8px;display:block;">CARs — Corrective Action Requests (Please Specify)</label>
                <div id="cars-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:10px;"></div>
                <button type="button" class="btn btn-outline btn-sm" id="add-car-btn" style="border-style:dashed;">
                    <i class="fas fa-plus"></i> Add CAR Item
                </button>
                <input type="hidden" name="finding_details" id="cars-hidden">
            </div>

            <!-- Corrective Action Required dynamic list -->
            <div class="form-group">
                <label style="margin-bottom:8px;display:block;">Corrective Action Required</label>
                <div id="ca-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:10px;"></div>
                <button type="button" class="btn btn-outline btn-sm" id="add-ca-btn" style="border-style:dashed;">
                    <i class="fas fa-plus"></i> Add Corrective Action
                </button>
                <input type="hidden" name="corrective_action_required" id="ca-hidden">
            </div>

            <!-- Recommendations dynamic list -->
            <div class="form-group">
                <label style="margin-bottom:8px;display:block;">Recommendation</label>
                <div id="rec-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:10px;"></div>
                <button type="button" class="btn btn-outline btn-sm" id="add-rec-btn" style="border-style:dashed;">
                    <i class="fas fa-plus"></i> Add Recommendation
                </button>
                <input type="hidden" name="recommendation" id="rec-hidden">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="form-group">
                    <label>Due Date to be Implemented</label>
                    <input type="date" name="deadline" class="form-control" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Verification / Follow Up Audit Date</label>
                    <input type="date" name="followup_date" class="form-control" min="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:8px;">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-exclamation-triangle"></i> Generate NCR
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- NCR List -->
<div class="card">
    <div class="card-header"><h3>All NCR Reports</h3></div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($ncrs)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-check-circle" style="color:var(--success)"></i></div>
                <h3>No NCRs</h3>
                <p>No non-conformance reports have been generated.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>NCR #</th>
                            <th>Company</th>
                            <th>By</th>
                            <th>Level</th>
                            <th>Brief Summary</th>
                            <th>Lab Test</th>
                            <th>Due Date</th>
                            <th>Follow-up</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ncrs as $n): ?>
                        <?php
                            $cat_badges = [
                                'minor'       => '<span class="badge badge-warning">Minor</span>',
                                'major'       => '<span class="badge badge-danger">Major</span>',
                                'serious'     => '<span class="badge" style="background:#ef4444;color:#fff;">Serious</span>',
                                'observation' => '<span class="badge badge-info">Observation</span>',
                            ];
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($n['ncr_number']) ?></strong></td>
                            <td><?= htmlspecialchars($n['company_name']) ?></td>
                            <td style="font-size:0.8rem;">
                                <?php if ($n['auditor_type'] === 'shariah'): ?>
                                    <span class="badge badge-info">Shariah</span>
                                <?php elseif ($n['auditor_type'] === 'technical'): ?>
                                    <span class="badge badge-primary">Technical</span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><?= $cat_badges[$n['category']] ?? getStatusBadge($n['category']) ?></td>
                            <td style="font-size:0.85rem;max-width:220px;">
                                <?= htmlspecialchars(mb_strimwidth($n['brief_summary'] ?? $n['finding_details'], 0, 80, '…')) ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($n['auditor_type'] === 'shariah'): ?>
                                    <?php if ($n['requires_lab_test']): ?>
                                        <span class="badge" style="background:#f3e8ff;color:#7e22ce;">
                                            <i class="fas fa-flask"></i> Required
                                        </span>
                                    <?php else: ?>
                                        <span style="font-size:0.78rem;color:#94a3b8;">No</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="font-size:0.78rem;color:#cbd5e1;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.85rem;">
                                <?php if ($n['deadline']): ?>
                                    <span style="color:<?= strtotime($n['deadline']) < time() && !in_array($n['status'],['resolved','closed']) ? 'var(--danger)' : 'inherit' ?>">
                                        <?= formatDate($n['deadline']) ?>
                                    </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td style="font-size:0.85rem;"><?= $n['followup_date'] ? formatDate($n['followup_date']) : '—' ?></td>
                            <td><?= getStatusBadge($n['status']) ?></td>
                            <td>
                                <a href="?view=<?= $n['id'] ?>" class="btn btn-sm btn-outline" title="View / Print">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="<?= BASE_URL ?>dashboard/auditor/ncr_pdf.php?id=<?= $n['id'] ?>" class="btn btn-sm btn-success" title="Download PDF">
                                    <i class="fas fa-file-pdf"></i>
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

<?php endif; ?>

<style>
.dynamic-item-row {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}
.dynamic-item-row textarea {
    flex: 1;
    resize: vertical;
    min-height: 42px;
    font-size: 0.88rem;
    padding: 9px 12px;
    border: 1px solid var(--neutral-300, #d1d5db);
    border-radius: 8px;
    font-family: inherit;
    line-height: 1.5;
    transition: border-color .15s;
}
.dynamic-item-row textarea:focus {
    outline: none;
    border-color: var(--primary-400, #34d399);
    box-shadow: 0 0 0 3px rgba(52,211,153,.15);
}
.dynamic-item-row .remove-item-btn {
    flex-shrink: 0;
    width: 34px;
    height: 34px;
    border: 1px solid var(--neutral-300, #d1d5db);
    border-radius: 8px;
    background: #fff;
    color: var(--neutral-500, #6b7280);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    transition: border-color .15s, color .15s;
    margin-top: 4px;
}
.dynamic-item-row .remove-item-btn:hover {
    border-color: var(--danger, #ef4444);
    color: var(--danger, #ef4444);
}
</style>
<script>
(function () {
    'use strict';

    const BASE = '<?= BASE_URL ?>';

    // ── Generic dynamic list builder ─────────────────────────────────────────
    function makeList(containerId, addBtnId, hiddenId, placeholder) {
        const container = document.getElementById(containerId);
        const addBtn    = document.getElementById(addBtnId);
        const hidden    = document.getElementById(hiddenId);

        function addRow(value) {
            const row = document.createElement('div');
            row.className = 'dynamic-item-row';

            const ta = document.createElement('textarea');
            ta.rows = 2;
            ta.placeholder = placeholder;
            ta.value = value || '';
            ta.addEventListener('input', syncHidden);

            const rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'remove-item-btn';
            rm.title = 'Remove';
            rm.innerHTML = '<i class="fas fa-times"></i>';
            rm.addEventListener('click', function () {
                row.remove();
                syncHidden();
            });

            row.appendChild(ta);
            row.appendChild(rm);
            container.appendChild(row);
            syncHidden();
            return ta;
        }

        function syncHidden() {
            const lines = [];
            container.querySelectorAll('textarea').forEach(function (ta) {
                const v = ta.value.trim();
                if (v) lines.push(v);
            });
            hidden.value = lines.join('\n');
        }

        function setItems(items) {
            container.innerHTML = '';
            items.forEach(function (v) { addRow(v); });
            syncHidden();
        }

        addBtn.addEventListener('click', function () {
            const ta = addRow('');
            ta.focus();
        });

        return { addRow, setItems, syncHidden };
    }

    // ── Instantiate the three lists ───────────────────────────────────────────
    const carsList = makeList('cars-list', 'add-car-btn', 'cars-hidden',
        'e.g. Provide Halal certificate for all meat ingredients used...');
    const caList   = makeList('ca-list',  'add-ca-btn',  'ca-hidden',
        'e.g. Replace non-halal ingredient with certified alternative...');
    const recList  = makeList('rec-list', 'add-rec-btn', 'rec-hidden',
        'e.g. Install pest nets around premises...');

    // Start each list with one empty row
    carsList.addRow('');
    caList.addRow('');
    recList.addRow('');

    // ── Pre-fill on inspection select ─────────────────────────────────────────
    const inspSelect = document.getElementById('ncr_inspection');
    if (inspSelect) {
        inspSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            document.getElementById('ncr_app_id').value = opt.dataset.app || '';
            const locField = document.getElementById('ncr_location');
            if (locField && opt.dataset.location) locField.value = opt.dataset.location;
            const timeField = document.querySelector('input[name="time_of_inspection"]');
            if (timeField && opt.dataset.time) timeField.value = opt.dataset.time;
            const picField = document.querySelector('input[name="person_in_charge"]');
            if (picField && opt.dataset.owner) picField.value = opt.dataset.owner;

            const inspId = this.value;
            if (!inspId) return;

            fetch(BASE + 'dashboard/auditor/api/ncr_prefill.php?inspection_id=' + inspId)
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    // CARs ← audit findings (split by newline, filter blanks)
                    const carItems = (data.findings || '')
                        .split('\n')
                        .map(function (s) { return s.trim(); })
                        .filter(Boolean);
                    carsList.setItems(carItems.length ? carItems : ['']);

                    // Corrective Action ← non-conforming doc remarks
                    const caItems = (data.nc_remarks || []).map(function (r) {
                        return r.label + (r.remarks ? ': ' + r.remarks : '');
                    });
                    caList.setItems(caItems.length ? caItems : ['']);

                    // Recommendations — keep blank, auditor fills in
                    recList.setItems(['']);
                })
                .catch(function () {
                    carsList.setItems(['']);
                    caList.setItems(['']);
                    recList.setItems(['']);
                });
        });
    }

    // ── Sync hidden fields before form submit ─────────────────────────────────
    const form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function () {
            carsList.syncHidden();
            caList.syncHidden();
            recList.syncHidden();
            syncLabTestDetails();
        });
    }

    // ── Lab Test Category Selection Handler ──────────────────────────────────
    const labYes = document.getElementById('lab-yes');
    const labNo = document.getElementById('lab-no');
    const labYesLabel = document.getElementById('lab-yes-label');
    const labNoLabel = document.getElementById('lab-no-label');
    const labTestCategories = document.getElementById('lab-test-categories');
    const labTestDetailsHidden = document.getElementById('lab-test-details-hidden');

    if (labYes && labNo && labTestCategories) {
        // Toggle lab test categories visibility
        labYes.addEventListener('change', function() {
            if (this.checked) {
                labTestCategories.style.display = 'block';
                labYesLabel.style.borderColor = '#7e22ce';
                labYesLabel.style.background = '#fdf4ff';
                labNoLabel.style.borderColor = '#e9d5ff';
                labNoLabel.style.background = '#fff';
            }
        });

        labNo.addEventListener('change', function() {
            if (this.checked) {
                labTestCategories.style.display = 'none';
                labNoLabel.style.borderColor = '#7e22ce';
                labNoLabel.style.background = '#fdf4ff';
                labYesLabel.style.borderColor = '#e9d5ff';
                labYesLabel.style.background = '#fff';
                // Uncheck all lab tests
                document.querySelectorAll('input[name="lab_tests[]"]').forEach(function(cb) {
                    cb.checked = false;
                });
                document.querySelectorAll('.lab-category-checkbox').forEach(function(cb) {
                    cb.checked = false;
                });
                document.querySelectorAll('.lab-tests-container').forEach(function(container) {
                    container.style.display = 'none';
                });
            }
        });

        // Handle category checkbox changes
        document.querySelectorAll('.lab-category-checkbox').forEach(function(categoryCheckbox) {
            categoryCheckbox.addEventListener('change', function() {
                const category = this.dataset.category;
                const testsContainer = document.querySelector('.lab-tests-container[data-category="' + category + '"]');
                
                if (this.checked) {
                    testsContainer.style.display = 'block';
                } else {
                    testsContainer.style.display = 'none';
                    // Uncheck all tests in this category
                    testsContainer.querySelectorAll('input[name="lab_tests[]"]').forEach(function(cb) {
                        cb.checked = false;
                    });
                }
                syncLabTestDetails();
            });
        });

        // Handle individual test checkbox changes
        document.querySelectorAll('input[name="lab_tests[]"]').forEach(function(testCheckbox) {
            testCheckbox.addEventListener('change', function() {
                syncLabTestDetails();
            });
        });
    }

    function syncLabTestDetails() {
        if (!labTestDetailsHidden) return;
        
        const selectedTests = {};
        
        // Group selected tests by category
        document.querySelectorAll('input[name="lab_tests[]"]:checked').forEach(function(cb) {
            const value = cb.value;
            const parts = value.split('_');
            const category = parts[0]; // calibration, microbiology, or chemistry
            const testName = parts.slice(1).join('_');
            
            if (!selectedTests[category]) {
                selectedTests[category] = [];
            }
            selectedTests[category].push(testName);
        });
        
        // Convert to JSON string
        labTestDetailsHidden.value = JSON.stringify(selectedTests);
    }
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
