<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$page_title    = 'NCR Reports';
$page_heading  = 'Non-Conformance Reports';
$is_dashboard  = true;
$breadcrumbs   = [
    ['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/business_owner/'],
    ['label' => 'NCR Reports'],
];
$user_id = $_SESSION['user_id'];

// ── Load the business owner's application ─────────────────────────────────────
$app_stmt = $conn->prepare("
    SELECT ha.*, loi.company_name
    FROM hdp_applications ha
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    WHERE ha.business_owner_id = ?
    ORDER BY ha.created_at DESC
    LIMIT 1
");
$app_stmt->bind_param("i", $user_id);
$app_stmt->execute();
$application = $app_stmt->get_result()->fetch_assoc();

// ── Load NCR reports for this application ─────────────────────────────────────
$ncrs = [];
if ($application) {
    $app_id = intval($application['id']);

    $ncr_stmt = $conn->prepare("
        SELECT n.*, u.full_name AS auditor_name
        FROM ncr_reports n
        LEFT JOIN users u ON n.prepared_by = u.id
        WHERE n.application_id = ?
        ORDER BY n.created_at DESC
    ");
    $ncr_stmt->bind_param("i", $app_id);
    $ncr_stmt->execute();
    $ncrs = $ncr_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ── Mark NCR notifications as read ───────────────────────────────────────────
if ($application) {
    $mark = $conn->prepare("
        UPDATE notifications SET is_read = 1
        WHERE user_id = ? AND (title LIKE '%NCR%' OR title LIKE '%Non-Conformance%') AND is_read = 0
    ");
    if ($mark) { $mark->bind_param("i", $user_id); $mark->execute(); }
}

// ── Stats ─────────────────────────────────────────────────────────────────────
$total_ncrs = count($ncrs);
$open_ncrs  = count(array_filter($ncrs, fn($n) => in_array($n['status'], ['open','in_progress'])));
$closed_ncrs = count(array_filter($ncrs, fn($n) => in_array($n['status'], ['resolved','closed'])));

require_once __DIR__ . '/../../includes/header.php';

// ── NCR category badge ────────────────────────────────────────────────────────
function ncrCatBadge($cat) {
    $styles = [
        'minor'       => 'background:#fef08a;color:#713f12;',
        'major'       => 'background:#fed7aa;color:#9a3412;',
        'serious'     => 'background:#fecdd3;color:#9f1239;',
        'observation' => 'background:#bfdbfe;color:#1e40af;',
    ];
    $labels = ['minor'=>'Minor','major'=>'Major','serious'=>'Serious','observation'=>'Observation'];
    $style  = $styles[$cat] ?? 'background:#e2e8f0;color:#374151;';
    $label  = $labels[$cat] ?? ucfirst($cat);
    return "<span style=\"{$style}padding:3px 12px;border-radius:99px;font-size:0.75rem;font-weight:700;text-transform:uppercase;\">{$label}</span>";
}

// ── NCR status badge ──────────────────────────────────────────────────────────
function ncrStatusBadge($status) {
    $map = [
        'open'        => ['badge-danger',   'Open'],
        'in_progress' => ['badge-warning',  'In Progress'],
        'resolved'    => ['badge-success',  'Resolved'],
        'closed'      => ['badge-secondary','Closed'],
    ];
    [$cls, $label] = $map[$status] ?? ['badge-secondary', ucfirst($status)];
    return "<span class=\"badge {$cls}\">{$label}</span>";
}
?>

<?php if (!$application): ?>
<div class="empty-state">
    <div class="empty-icon"><i class="fas fa-clipboard"></i></div>
    <h3>No Application Found</h3>
    <p>You need a verified Letter of Intent and an active application before NCR reports are available.</p>
    <a href="<?= BASE_URL ?>dashboard/business_owner/letter_of_intent.php" class="btn btn-primary">
        <i class="fas fa-arrow-right"></i> Go to Letter of Intent
    </a>
</div>

<?php elseif (empty($ncrs)): ?>
<div class="empty-state">
    <div class="empty-icon"><i class="fas fa-check-circle" style="color:var(--success);"></i></div>
    <h3>No NCR Reports</h3>
    <p>No Non-Conformance Reports have been issued for
        <strong><?= htmlspecialchars($application['company_name']) ?></strong> yet.</p>
</div>

<?php else: ?>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="card card-stat">
        <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
        <div class="stat-value"><?= $total_ncrs ?></div>
        <div class="stat-label">Total NCRs</div>
    </div>
    <div class="card card-stat <?= $open_ncrs > 0 ? 'accent' : '' ?>">
        <div class="stat-icon <?= $open_ncrs > 0 ? 'red' : 'green' ?>"><i class="fas fa-exclamation-circle"></i></div>
        <div class="stat-value"><?= $open_ncrs ?></div>
        <div class="stat-label">Open / In Progress</div>
    </div>
    <div class="card card-stat">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-value"><?= $closed_ncrs ?></div>
        <div class="stat-label">Resolved / Closed</div>
    </div>
</div>

<?php if ($open_ncrs > 0): ?>
<div class="alert alert-warning" style="margin-bottom:24px;">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        <strong>Action Required — <?= $open_ncrs ?> Open NCR<?= $open_ncrs > 1 ? 's' : '' ?></strong>
        <p style="margin-top:4px;font-size:0.9rem;">
            Please review the reports below and take the required corrective actions before your application can proceed.
        </p>
    </div>
</div>
<?php endif; ?>

<!-- NCR list -->
<div style="display:flex;flex-direction:column;gap:24px;">
<?php foreach ($ncrs as $ncr):
    $is_closed  = in_array($ncr['status'], ['resolved','closed']);
    $is_overdue = !$is_closed && !empty($ncr['deadline']) && strtotime($ncr['deadline']) < time();
    $cat_borders = ['minor'=>'#fde68a','major'=>'#fed7aa','serious'=>'#fecdd3','observation'=>'#bfdbfe'];
    $border_color = $is_closed ? '#e2e8f0' : ($cat_borders[$ncr['category']] ?? '#e2e8f0');
    $cat_bgs = ['minor'=>'#fefce8','major'=>'#fff7ed','serious'=>'#fff1f2','observation'=>'#eff6ff'];
    $header_bg = $is_closed ? '#f8fafc' : ($cat_bgs[$ncr['category']] ?? '#f8fafc');
?>
<div style="border:1px solid <?= $border_color ?>;border-radius:12px;overflow:hidden;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.05);">

    <!-- Header -->
    <div style="background:<?= $header_bg ?>;padding:14px 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;border-bottom:1px solid <?= $border_color ?>;">
        <div>
            <span style="font-weight:800;font-size:1rem;color:var(--neutral-800);"><?= htmlspecialchars($ncr['ncr_number']) ?></span>
            <span style="margin-left:10px;font-size:0.78rem;color:var(--neutral-500);">Issued <?= formatDate($ncr['created_at']) ?></span>
            <?php if (!empty($ncr['auditor_name'])): ?>
            <span style="margin-left:10px;font-size:0.78rem;color:var(--neutral-500);">
                by <?= htmlspecialchars($ncr['auditor_name']) ?>
            </span>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <?= ncrCatBadge($ncr['category']) ?>
            <?= ncrStatusBadge($ncr['status']) ?>
            <?php if ($is_overdue): ?>
                <span class="badge badge-danger"><i class="fas fa-clock"></i> Overdue</span>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>dashboard/auditor/ncr_pdf.php?id=<?= $ncr['id'] ?>"
               class="btn btn-sm btn-success" title="Download PDF"
               style="font-size:0.78rem;">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
        </div>
    </div>

    <!-- Info grid -->
    <table style="width:100%;border-collapse:collapse;font-size:0.85rem;border-bottom:1px solid #e2e8f0;">
        <tr>
            <td style="padding:8px 16px;font-weight:700;background:#f8fafc;width:16%;border-right:1px solid #e2e8f0;white-space:nowrap;">DATE</td>
            <td style="padding:8px 16px;border-right:1px solid #e2e8f0;"><?= formatDate($ncr['created_at']) ?></td>
            <td style="padding:8px 16px;font-weight:700;background:#f8fafc;width:16%;border-right:1px solid #e2e8f0;white-space:nowrap;">LOCATION</td>
            <td style="padding:8px 16px;"><?= htmlspecialchars($ncr['location'] ?? '—') ?></td>
        </tr>
        <?php if (!empty($ncr['time_of_inspection']) || !empty($ncr['person_in_charge'])): ?>
        <tr style="border-top:1px solid #e2e8f0;">
            <td style="padding:8px 16px;font-weight:700;background:#f8fafc;border-right:1px solid #e2e8f0;">TIME</td>
            <td style="padding:8px 16px;border-right:1px solid #e2e8f0;"><?= $ncr['time_of_inspection'] ? date('h:i A', strtotime($ncr['time_of_inspection'])) : '—' ?></td>
            <td style="padding:8px 16px;font-weight:700;background:#f8fafc;border-right:1px solid #e2e8f0;">PERSON IN CHARGE</td>
            <td style="padding:8px 16px;"><?= htmlspecialchars($ncr['person_in_charge'] ?? '—') ?></td>
        </tr>
        <?php endif; ?>
        <?php if (!empty($ncr['document_reference'])): ?>
        <tr style="border-top:1px solid #e2e8f0;">
            <td style="padding:8px 16px;font-weight:700;background:#f8fafc;border-right:1px solid #e2e8f0;">DOC. REF.</td>
            <td colspan="3" style="padding:8px 16px;"><?= htmlspecialchars($ncr['document_reference']) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- Brief Summary -->
    <?php if (!empty($ncr['brief_summary'])): ?>
    <div style="padding:14px 20px;border-bottom:1px solid #e2e8f0;">
        <div style="font-weight:700;text-decoration:underline;margin-bottom:6px;font-size:0.85rem;">Brief Summary</div>
        <div style="color:var(--neutral-700);font-size:0.88rem;line-height:1.7;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($ncr['brief_summary'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- CARs -->
    <?php if (!empty($ncr['finding_details'])): ?>
    <div style="padding:14px 20px;border-bottom:1px solid #e2e8f0;">
        <div style="font-weight:700;margin-bottom:6px;font-size:0.85rem;">CARs — Corrective Action Requests</div>
        <div style="color:var(--neutral-700);font-size:0.88rem;line-height:1.7;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($ncr['finding_details'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Corrective Action Required -->
    <?php if (!empty($ncr['corrective_action_required'])): ?>
    <div style="padding:14px 20px;border-bottom:1px solid #e2e8f0;background:<?= $is_closed ? '#f0fdf4' : '#fff7ed' ?>;">
        <div style="font-weight:700;margin-bottom:6px;font-size:0.85rem;color:<?= $is_closed ? 'var(--success)' : '#92400e' ?>;">
            <i class="fas fa-tools" style="margin-right:5px;"></i> Corrective Action Required
        </div>
        <div style="color:var(--neutral-700);font-size:0.88rem;line-height:1.7;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($ncr['corrective_action_required'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Laboratory Test Required -->
    <?php if ($ncr['auditor_type'] === 'shariah' && $ncr['requires_lab_test'] && !empty($ncr['lab_test_details'])): ?>
    <?php
        // Decode HTML entities first, then decode JSON
        $labTestJson = html_entity_decode($ncr['lab_test_details']);
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
    <div style="padding:14px 20px;border-bottom:1px solid #e2e8f0;background:#fdf4ff;">
        <div style="font-weight:700;margin-bottom:10px;font-size:0.85rem;color:#7e22ce;">
            <i class="fas fa-flask" style="margin-right:5px;"></i> Laboratory Tests Required
        </div>
        <div style="color:#6b21a8;font-size:0.88rem;line-height:1.7;">
            <?php foreach ($labTests as $category => $tests): ?>
                <?php if (!empty($tests)): ?>
                <div style="margin-bottom:12px;">
                    <div style="font-weight:600;color:#7e22ce;margin-bottom:6px;">
                        <?= htmlspecialchars($categoryLabels[$category] ?? ucfirst($category)) ?>:
                    </div>
                    <ul style="margin:0;padding-left:24px;list-style:disc;">
                        <?php foreach ($tests as $test): ?>
                        <li style="margin-bottom:4px;"><?= htmlspecialchars($testLabels[$test] ?? ucwords(str_replace('_', ' ', $test))) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:12px;padding:10px;background:#fff;border:1px solid #e9d5ff;border-radius:6px;font-size:0.85rem;color:#6b21a8;">
            <i class="fas fa-info-circle" style="margin-right:6px;"></i>
            <strong>Note:</strong> Please coordinate with the laboratory analyst to schedule sample collection and testing.
        </div>
        <div style="margin-top:12px;text-align:center;">
            <a href="<?= BASE_URL ?>dashboard/business_owner/laboratory.php" class="btn btn-primary" style="display:inline-flex;align-items:center;gap:8px;">
                <i class="fas fa-flask"></i> Submit Laboratory Test Request
            </a>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Recommendation -->
    <?php if (!empty($ncr['recommendation'])): ?>
    <div style="padding:14px 20px;border-bottom:1px solid #e2e8f0;">
        <div style="font-weight:700;margin-bottom:6px;font-size:0.85rem;">Recommendation</div>
        <div style="color:var(--neutral-700);font-size:0.88rem;line-height:1.7;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($ncr['recommendation'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Corrective Action Taken (if any) -->
    <?php if (!empty($ncr['corrective_action_taken'])): ?>
    <div style="padding:14px 20px;border-bottom:1px solid #e2e8f0;background:#f0fdf4;">
        <div style="font-weight:700;margin-bottom:6px;font-size:0.85rem;color:var(--success);">
            <i class="fas fa-check-circle" style="margin-right:5px;"></i> Corrective Action Taken
        </div>
        <div style="color:var(--neutral-700);font-size:0.88rem;line-height:1.7;white-space:pre-wrap;"><?= nl2br(htmlspecialchars($ncr['corrective_action_taken'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Due dates -->
    <div style="display:grid;grid-template-columns:1fr 1fr;border-bottom:1px solid #e2e8f0;">
        <div style="padding:12px 20px;border-right:1px solid #e2e8f0;">
            <div style="font-weight:700;font-size:0.8rem;margin-bottom:4px;">Due Date to be Implemented</div>
            <div style="font-size:0.88rem;color:<?= $is_overdue ? 'var(--danger)' : 'var(--neutral-700)' ?>;">
                <?= $ncr['deadline'] ? formatDate($ncr['deadline']) : '—' ?>
                <?php if ($is_overdue): ?><span style="font-size:0.75rem;"> (Overdue)</span><?php endif; ?>
            </div>
        </div>
        <div style="padding:12px 20px;">
            <div style="font-weight:700;font-size:0.8rem;margin-bottom:4px;">Verification / Follow Up Audit</div>
            <div style="font-size:0.88rem;color:var(--neutral-700);"><?= !empty($ncr['followup_date']) ? formatDate($ncr['followup_date']) : '—' ?></div>
        </div>
    </div>

    <!-- Action prompt -->
    <?php if (in_array($ncr['status'], ['open','in_progress']) && empty($ncr['corrective_action_taken'])): ?>
    <div style="padding:12px 20px;background:#fff7ed;font-size:0.85rem;color:#92400e;">
        <i class="fas fa-clock" style="margin-right:6px;color:#f97316;"></i>
        <strong>Action Required:</strong> Please address the corrective actions listed above and inform the auditor.
    </div>
    <?php endif; ?>

</div>
<?php endforeach; ?>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
