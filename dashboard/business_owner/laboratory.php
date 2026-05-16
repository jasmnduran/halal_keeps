<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$page_title = 'Laboratory';
$page_heading = 'Laboratory Requests';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/business_owner/'], ['label' => 'Laboratory']];
$user_id = $_SESSION['user_id'];
$error = ''; $success = '';

// Get business owner's application
$app_stmt = $conn->prepare("
    SELECT ha.*, loi.company_name, loi.company_address, loi.contact_person, loi.contact_phone
    FROM hdp_applications ha
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    WHERE ha.business_owner_id = ?
    ORDER BY ha.created_at DESC
    LIMIT 1
");
$app_stmt->bind_param("i", $user_id);
$app_stmt->execute();
$application = $app_stmt->get_result()->fetch_assoc();

// Get NCR with lab test requirements
$ncr_with_lab = null;
$required_tests = [];
if ($application) {
    $ncr_stmt = $conn->prepare("
        SELECT n.*, u.full_name AS auditor_name
        FROM ncr_reports n
        LEFT JOIN users u ON n.prepared_by = u.id
        WHERE n.application_id = ? AND n.requires_lab_test = 1 AND n.auditor_type = 'shariah'
        ORDER BY n.created_at DESC
        LIMIT 1
    ");
    $ncr_stmt->bind_param("i", $application['id']);
    $ncr_stmt->execute();
    $ncr_with_lab = $ncr_stmt->get_result()->fetch_assoc();
    
    if ($ncr_with_lab && !empty($ncr_with_lab['lab_test_details'])) {
        $labTestJson = html_entity_decode($ncr_with_lab['lab_test_details']);
        $required_tests = json_decode($labTestJson, true);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_lab_request'])) {
    $app_id = intval($_POST['application_id']);
    
    // Parse test details from hidden field (JSON)
    $test_details_json = $_POST['test_details'] ?? '[]';
    $test_details_array = json_decode($test_details_json, true);
    
    // Sample description will be filled by receiving officer, so leave it empty for now
    $sample_description = 'Pending - To be filled by Receiving Officer';
    
    // Build analysis requirements with all test details including interpretation request
    $request_interpretation = sanitize($_POST['request_interpretation'] ?? 'no');
    $delivery_method = sanitize($_POST['delivery_method'] ?? 'pickup');
    $special_instructions = sanitize($_POST['special_instructions'] ?? '');
    
    $analysis_requirements = [
        'tests' => $test_details_array,
        'request_interpretation' => $request_interpretation,
        'delivery_method' => $delivery_method,
        'special_instructions' => $special_instructions
    ];
    $analysis_requirements_json = json_encode($analysis_requirements);
    
    // Calculate total testing fee
    $total_fee = 0;
    foreach ($test_details_array as $test) {
        $total_fee += floatval($test['total'] ?? 0);
    }
    
    $stmt = $conn->prepare("
        INSERT INTO laboratory_requests 
            (business_owner_id, application_id, sample_description, analysis_requirements, 
             status, payment_status, payment_testing_fee)
        VALUES (?, ?, ?, ?, 'submitted', 'unpaid', ?)
    ");
    $stmt->bind_param("iissd", $user_id, $app_id, $sample_description, $analysis_requirements_json, $total_fee);
    
    if ($stmt->execute()) {
        $success = 'Laboratory test request submitted successfully. The laboratory analyst will review your request.';
        
        // Notify lab analysts
        $lab_q = $conn->query("SELECT id FROM users WHERE role_id = " . ROLE_LABORATORY_ANALYST . " AND role_status = 'approved'");
        if ($lab_q) {
            while ($lab = $lab_q->fetch_assoc()) {
                createNotification(
                    $conn,
                    $lab['id'],
                    'New Laboratory Test Request',
                    'A new laboratory test request has been submitted by ' . $application['company_name'],
                    'action_required',
                    BASE_URL . 'dashboard/lab_analyst/'
                );
            }
        }
        
        logActivity($conn, $user_id, 'Lab Request Submitted', 'Laboratory test request for App #' . $app_id, 'laboratory');
    } else {
        $error = 'Failed to submit laboratory request: ' . $conn->error;
    }
}

$stmt = $conn->prepare("SELECT lr.*, loi.company_name, labr.id as lab_report_id, labr.report_number, labr.halal_status as report_halal_status FROM laboratory_requests lr JOIN hdp_applications ha ON lr.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id LEFT JOIN laboratory_reports labr ON labr.laboratory_request_id = lr.id WHERE lr.business_owner_id = ? ORDER BY lr.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>


<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= $error ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<?php if ($application): ?>
<!-- Laboratory Test Request Form -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h3><i class="fas fa-file-medical" style="color:var(--primary-600);margin-right:8px;"></i> Submit Laboratory Test Request</h3>
    </div>
    <div class="card-body">
        <?php if ($ncr_with_lab && !empty($required_tests)): ?>
        <div class="alert alert-info" style="margin-bottom:20px;">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>NCR Laboratory Requirements Detected</strong>
                <p style="margin-top:4px;font-size:0.9rem;">
                    Your NCR report (<?= htmlspecialchars($ncr_with_lab['ncr_number']) ?>) requires laboratory testing. 
                    The required tests have been pre-filled in the form below.
                </p>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="submit_lab_request" value="1">
            <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
            
            <!-- Company Information (Read-only) -->
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-bottom:20px;">
                <div style="font-weight:700;margin-bottom:12px;color:var(--neutral-700);">Company Information</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <div style="font-size:0.8rem;color:var(--neutral-500);margin-bottom:4px;">Company Name</div>
                        <div style="font-weight:600;"><?= htmlspecialchars($application['company_name']) ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.8rem;color:var(--neutral-500);margin-bottom:4px;">Contact Person</div>
                        <div style="font-weight:600;"><?= htmlspecialchars($application['contact_person'] ?? '—') ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.8rem;color:var(--neutral-500);margin-bottom:4px;">Address</div>
                        <div style="font-weight:600;"><?= htmlspecialchars($application['company_address'] ?? '—') ?></div>
                    </div>
                    <div>
                        <div style="font-size:0.8rem;color:var(--neutral-500);margin-bottom:4px;">Contact Number</div>
                        <div style="font-weight:600;"><?= htmlspecialchars($application['contact_phone'] ?? '—') ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Test/Calibration/Service Requested -->
            <div class="form-group">
                <label>Test/Calibration/Service Requested <span class="required">*</span></label>
                <div style="margin-bottom:12px;">
                    <table style="width:100%;border-collapse:collapse;border:1px solid #cbd5e1;font-size:0.85rem;">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th style="border:1px solid #cbd5e1;padding:10px;text-align:left;width:50%;">Test/Calibration/Service Requested</th>
                                <th style="border:1px solid #cbd5e1;padding:10px;text-align:center;width:18%;">No. of Samples/Units</th>
                                <th style="border:1px solid #cbd5e1;padding:10px;text-align:right;width:16%;">Unit Cost (₱)</th>
                                <th style="border:1px solid #cbd5e1;padding:10px;text-align:right;width:16%;">Total (₱)</th>
                            </tr>
                        </thead>
                        <tbody id="test-items-tbody">
                            <?php 
                            if (!empty($required_tests)) {
                                // Pre-fill with required tests from NCR
                                $testPrices = [
                                    // Calibration tests
                                    'tank_truck_12kl_14kl' => 2000.00,
                                    'tank_truck_16kl_22kl' => 2500.00,
                                    'tank_truck_24kl_30kl' => 3000.00,
                                    'tank_truck_32kl_40kl' => 3500.00,
                                    'calibrating_beaker_volumetric_30l' => 600.00,
                                    'proving_tank_100l_to_400l' => 350.00,
                                    'proving_tank_401l_to_2000l' => 4000.00,
                                    'proving_tank_2001l_to_5000l' => 5000.00,
                                    'test_measure_ignimbangan_up_to_30l' => 500.00,
                                    'thermometry_all_types' => 1700.00,
                                    'hygrometer' => 700.00,
                                    'mass_test_weights_class_e1_f1_f2' => 600.00,
                                    'mass_stainless_steel_25kg' => 600.00,
                                    'mass_chrome_iron_cast_1mg_to_500g' => 450.00,
                                    'mass_chrome_iron_cast_1kg_to_50kg' => 600.00,
                                    'balance_digital' => 1300.00,
                                    'balance_non_digital_elastic_weigher' => 1250.00,
                                    'flour_scale_hog_scale' => 2000.00,
                                    // Microbiology tests
                                    'aerobic_plate_count_pour_plate' => 550.00,
                                    'aerobic_plate_count_plastic_test' => 700.00,
                                    'coliform_count_mpn' => 525.00,
                                    'e_coli_detection_mpn' => 550.00,
                                    'salmonella' => 1100.00,
                                    'staphylococcus_aureus_our_plated_test' => 550.00,
                                    'enterobacteriaceae_count_rapid_test' => 1050.00,
                                    'enterobacteriaceae_count_confirmatory' => 2250.00,
                                    'campylobacter_spp_detection_rapid_test' => 2000.00,
                                    'campylobacter_spp_detection_confirmatory' => 2500.00,
                                    'yeast_and_mold_count' => 550.00,
                                    // Chemistry tests
                                    'anti_content_gravimetric_method' => 350.00,
                                    'crude_fat_soxhlet_and_hydrolysis' => 1200.00,
                                    'crude_protein' => 350.00,
                                    'moisture_content_gravimetric_method' => 350.00,
                                    'moisture_type_karl_fischer_calcium_aas' => 450.00,
                                    'potassium_aas' => 600.00,
                                    'copper_aas' => 1250.00,
                                    'iron_aas' => 1250.00,
                                    'nitrogen' => 1000.00,
                                    'nutritional_facts_formulation_drafting_nutritional_facts' => 5150.00,
                                    'nutritional_facts_computation_nutritional_facts' => 5150.00,
                                    'safety_information_msds' => 2500.00,
                                    'safety_information_sds' => 2500.00,
                                    'total_solids_kjeldametic' => 600.00,
                                    'water_activity' => 600.00,
                                    'turbidity' => 300.00,
                                    'calcium_magnesium_sodium_potassium' => 1200.00,
                                    'sulfate_nitrate_nitrites' => 900.00,
                                    'admin_absorption_spectrophotometer' => 1000.00,
                                    'total_dissolved_solids_tds_gravimetric' => 600.00,
                                    'total_suspended_solids_tss_gravimetric' => 600.00,
                                    'ph' => 300.00,
                                    'turbidity_nephelometric' => 300.00,
                                    'color' => 300.00
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
                                    'thermometry_all_types' => 'Thermometry Calibration',
                                    'hygrometer' => 'Hygrometer',
                                    'mass_test_weights_class_e1_f1_f2' => 'Test Weights Class E1, F1, F2',
                                    'mass_stainless_steel_25kg' => 'Stainless Steel 25 kg',
                                    'mass_chrome_iron_cast_1mg_to_500g' => 'Chrome/Iron/Cast (1mg to 500g)',
                                    'mass_chrome_iron_cast_1kg_to_50kg' => 'Chrome/Iron/Cast (1kg to 50kg)',
                                    'balance_digital' => 'Balance - Digital',
                                    'balance_non_digital_elastic_weigher' => 'Balance - Non-Digital',
                                    'flour_scale_hog_scale' => 'Flour Scale / Hog Scale',
                                    // Microbiology tests
                                    'aerobic_plate_count_pour_plate' => 'Aerobic Plate Count (Pour Plate)',
                                    'aerobic_plate_count_plastic_test' => 'Aerobic Plate Count (Plastic Test)',
                                    'coliform_count_mpn' => 'Coliform Count, MPN',
                                    'e_coli_detection_mpn' => 'E. coli Detection, MPN',
                                    'salmonella' => 'Salmonella',
                                    'staphylococcus_aureus_our_plated_test' => 'Staphylococcus aureus',
                                    'enterobacteriaceae_count_rapid_test' => 'Enterobacteriaceae (Rapid Test)',
                                    'enterobacteriaceae_count_confirmatory' => 'Enterobacteriaceae (Confirmatory)',
                                    'campylobacter_spp_detection_rapid_test' => 'Campylobacter spp. (Rapid Test)',
                                    'campylobacter_spp_detection_confirmatory' => 'Campylobacter spp. (Confirmatory)',
                                    'yeast_and_mold_count' => 'Yeast and Mold Count',
                                    // Chemistry tests
                                    'anti_content_gravimetric_method' => 'Anti Content (Gravimetric)',
                                    'crude_fat_soxhlet_and_hydrolysis' => 'Crude Fat (Soxhlet)',
                                    'crude_protein' => 'Crude Protein',
                                    'moisture_content_gravimetric_method' => 'Moisture Content',
                                    'moisture_type_karl_fischer_calcium_aas' => 'Calcium (AAS)',
                                    'potassium_aas' => 'Potassium (AAS)',
                                    'copper_aas' => 'Copper (AAS)',
                                    'iron_aas' => 'Iron (AAS)',
                                    'nitrogen' => 'Nitrogen',
                                    'nutritional_facts_formulation_drafting_nutritional_facts' => 'Nutritional Facts (Formulation)',
                                    'nutritional_facts_computation_nutritional_facts' => 'Nutritional Facts (Computation)',
                                    'safety_information_msds' => 'Safety Information (MSDS)',
                                    'safety_information_sds' => 'Safety Information (SDS)',
                                    'total_solids_kjeldametic' => 'Total Solids',
                                    'water_activity' => 'Water Activity',
                                    'turbidity' => 'Turbidity',
                                    'calcium_magnesium_sodium_potassium' => 'Ca/Mg/Na/K',
                                    'sulfate_nitrate_nitrites' => 'Sulfate/Nitrate/Nitrites',
                                    'admin_absorption_spectrophotometer' => 'Absorption Spectrophotometer',
                                    'total_dissolved_solids_tds_gravimetric' => 'TDS (Gravimetric)',
                                    'total_suspended_solids_tss_gravimetric' => 'TSS (Gravimetric)',
                                    'ph' => 'pH',
                                    'turbidity_nephelometric' => 'Turbidity (Nephelometric)',
                                    'color' => 'Color'
                                ];
                                
                                $rowNum = 1;
                                foreach ($required_tests as $category => $tests) {
                                    foreach ($tests as $test) {
                                        $testName = $testLabels[$test] ?? ucwords(str_replace('_', ' ', $test));
                                        $unitCost = $testPrices[$test] ?? 0;
                                        echo '<tr class="test-item-row">';
                                        echo '<td style="border:1px solid #cbd5e1;padding:8px;"><input type="text" class="form-control test-name" name="test_name[]" value="' . htmlspecialchars($testName) . '" required style="font-size:0.85rem;"></td>';
                                        echo '<td style="border:1px solid #cbd5e1;padding:8px;"><input type="number" class="form-control quantity-input" name="quantity[]" value="1" min="1" required style="font-size:0.85rem;text-align:center;"></td>';
                                        echo '<td style="border:1px solid #cbd5e1;padding:8px;"><input type="number" class="form-control unit-cost" name="unit_cost[]" value="' . number_format($unitCost, 2, '.', '') . '" step="0.01" min="0" required style="font-size:0.85rem;text-align:right;"></td>';
                                        echo '<td style="border:1px solid #cbd5e1;padding:8px;"><input type="text" class="form-control row-total" readonly value="' . number_format($unitCost, 2) . '" style="font-size:0.85rem;text-align:right;background:#f8fafc;"></td>';
                                        echo '</tr>';
                                        $rowNum++;
                                    }
                                }
                            } else {
                                // Empty row if no tests pre-filled
                                echo '<tr class="test-item-row">';
                                echo '<td style="border:1px solid #cbd5e1;padding:8px;"><input type="text" class="form-control test-name" name="test_name[]" placeholder="Enter test name" required style="font-size:0.85rem;"></td>';
                                echo '<td style="border:1px solid #cbd5e1;padding:8px;"><input type="number" class="form-control quantity-input" name="quantity[]" value="1" min="1" required style="font-size:0.85rem;text-align:center;"></td>';
                                echo '<td style="border:1px solid #cbd5e1;padding:8px;"><input type="number" class="form-control unit-cost" name="unit_cost[]" value="0.00" step="0.01" min="0" required style="font-size:0.85rem;text-align:right;"></td>';
                                echo '<td style="border:1px solid #cbd5e1;padding:8px;"><input type="text" class="form-control row-total" readonly value="0.00" style="font-size:0.85rem;text-align:right;background:#f8fafc;"></td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                        <tfoot style="background:#f8fafc;font-weight:700;">
                            <tr>
                                <td colspan="3" style="border:1px solid #cbd5e1;padding:10px;text-align:right;">GRAND TOTAL:</td>
                                <td style="border:1px solid #cbd5e1;padding:10px;text-align:right;" id="grand-total">₱ 0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <button type="button" class="btn btn-outline btn-sm" id="add-test-row" style="border-style:dashed;">
                    <i class="fas fa-plus"></i> Add Another Test
                </button>
                <input type="hidden" name="test_details" id="test-details-hidden">
                <small style="color:var(--neutral-500);font-size:0.85rem;display:block;margin-top:8px;">
                    <?php if (!empty($required_tests)): ?>
                        <i class="fas fa-check-circle" style="color:var(--success);"></i> Pre-filled with tests required by NCR <?= htmlspecialchars($ncr_with_lab['ncr_number']) ?>. You may edit quantities and add more tests.
                    <?php else: ?>
                        Add all tests, calibrations, or services you need from the laboratory.
                    <?php endif; ?>
                </small>
            </div>

            <!-- Request for Interpretation -->
            <div class="form-group">
                <label>Request for Interpretation</label>
                <div style="display:flex;gap:20px;flex-wrap:wrap;padding-top:8px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="radio" name="request_interpretation" value="yes" style="width:16px;height:16px;">
                        <i class="fas fa-check-circle"></i> Yes
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="radio" name="request_interpretation" value="no" checked style="width:16px;height:16px;">
                        <i class="fas fa-times-circle"></i> No
                    </label>
                </div>
                <small style="color:var(--neutral-500);font-size:0.85rem;display:block;margin-top:4px;">
                    Select "Yes" if you need the laboratory to provide interpretation of the test results.
                </small>
            </div>
            
            <!-- Delivery of Results -->
            <div class="form-group">
                <label>Delivery of Results <span class="required">*</span></label>
                <div style="display:flex;gap:20px;flex-wrap:wrap;padding-top:8px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="radio" name="delivery_method" value="pickup" checked style="width:16px;height:16px;">
                        <i class="fas fa-hand-holding"></i> Pick-up
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="radio" name="delivery_method" value="fax" style="width:16px;height:16px;">
                        <i class="fas fa-fax"></i> Fax
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="radio" name="delivery_method" value="email" style="width:16px;height:16px;">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="radio" name="delivery_method" value="courier" style="width:16px;height:16px;">
                        <i class="fas fa-shipping-fast"></i> Courier
                    </label>
                </div>
            </div>

            
            <!-- Terms Notice -->
            <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:14px;margin-bottom:20px;">
                <div style="font-weight:700;color:#92400e;margin-bottom:8px;"><i class="fas fa-exclamation-triangle"></i> Terms and Conditions</div>
                <div style="font-size:0.85rem;color:#78350f;line-height:1.7;">
                    <p style="margin:0 0 8px 0;"><strong>For Microbiology and Chemistry Laboratory:</strong></p>
                    <ul style="margin:0 0 12px 0;padding-left:20px;">
                        <li>Samples submitted to the Microbiology Laboratory are disposed immediately after extraction of specimen for testing. Those submitted to the Chemistry Laboratory are stored after completion of test/s. Reports of Analysis is/are released only after full payment of the analysis fees.</li>
                    </ul>
                    
                    <p style="margin:0 0 8px 0;"><strong>Serial Number (SN) Assignment:</strong></p>
                    <ul style="margin:0 0 12px 0;padding-left:20px;">
                        <li>If test has no available Serial No.(SN), it shall be provided by: [ ] Customer [ ] DOST RSTL-Davao</li>
                    </ul>
                    
                    <p style="margin:0 0 8px 0;"><strong>Equipment/Instrument Acceptance:</strong></p>
                    <ul style="margin:0 0 12px 0;padding-left:20px;">
                        <li>DOST RSTL-Davao has the right not to accept the equipment/instrument if the customer does not agree to the SN assigned by the concerned laboratory.</li>
                        <li>If it is found to be defective, the calibration will be stopped and the customer or his duly authorized representative shall be notified of the defective condition of the equipment and retrieve the same within a reasonable time. Repair of defective instrument is not included in the service of the laboratory.</li>
                    </ul>
                    
                    <p style="margin:0 0 8px 0;"><strong>Service Requirements:</strong></p>
                    <ul style="margin:0 0 12px 0;padding-left:20px;">
                        <li>If his duly authorized representative shall furnish DOST RSTL-Davao with the requirements stated in this Request Form and to have the job done in scope of work, if applicable.</li>
                    </ul>
                    
                    <p style="margin:0 0 8px 0;"><strong>Liability and Standards:</strong></p>
                    <ul style="margin:0 0 12px 0;padding-left:20px;">
                        <li>DOST RSTL-Davao is not liable for the deviations of environmental conditions from set standards during on-site calibration. Actual environmental conditions will be recorded.</li>
                        <li>The laboratory hereby authorizes the DOST RSTL-Davao to dispose in any manner it deems fit all unclaimed calibrated instruments/equipment after ninety (90) days from the instrument/sample in order to recover calibration and other related costs, if applicable.</li>
                    </ul>
                    
                    <p style="margin:0 0 8px 0;"><strong>Calibration Interval:</strong></p>
                    <ul style="margin:0 0 12px 0;padding-left:20px;">
                        <li>The recommended calibration interval is one (1) year. Otherwise, the customer and the DOST RSTL-Davao agrees on the calibration interval to be indicated in the Request Form and agrees that the due date of the instrument is _____________ year/s.</li>
                    </ul>
                    
                    <p style="margin:0 0 8px 0;"><strong>Agreement:</strong></p>
                    <ul style="margin:0 0 12px 0;padding-left:20px;">
                        <li>His duly authorized representative has read, understood and agreed to the terms and conditions stated herein.</li>
                        <li>If requests a statement of conformity to a specification or standard for the test or calibration, the specification or standard and the decision rule are clearly defined as standard. Otherwise, the laboratory communicates to the customer the decision rule selected and appropriate for the sample tested or calibrated and the test or calibration circumstances.</li>
                    </ul>
                    
                    <p style="margin:0;"><strong>Note:</strong> The conduct of tests and calibration may change depending on the quantity of sample submitted, complexity of the test required, condition of the unit and other circumstances.</p>
                </div>
                
                <!-- Agreement Checkbox -->
                <div style="margin-top:16px;padding-top:16px;border-top:2px solid #fcd34d;">
                    <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;font-weight:600;color:#92400e;">
                        <input type="checkbox" id="agree-terms" name="agree_terms" required style="width:20px;height:20px;margin-top:2px;cursor:pointer;">
                        <span style="flex:1;">
                            I have read, understood, and agree to all the terms and conditions stated above. I acknowledge that I am authorized to submit this laboratory test request on behalf of the company.
                        </span>
                    </label>
                </div>
            </div>
            
            <div style="display:flex;justify-content:flex-end;gap:12px;">
                <a href="<?= BASE_URL ?>dashboard/business_owner/" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submit-btn" disabled>
                    <i class="fas fa-paper-plane"></i> Submit Request and Samples                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-flask" style="color: var(--primary-600); margin-right: 8px;"></i> Laboratory Requests</h3>
    </div>
    <div class="card-body">
        <?php if (empty($requests)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-flask"></i></div>
                <h3>No Laboratory Requests</h3>
                <p>Laboratory requests will appear here once they are created during the certification process.</p>
            </div>
        <?php else: ?>
            <?php foreach ($requests as $req): ?>
                <?php
                    $analysis_data = json_decode($req['analysis_requirements'], true);
                    $tests = $analysis_data['tests'] ?? [];
                    $delivery_method = $analysis_data['delivery_method'] ?? 'pickup';

                    // Parse sample codes/descriptions from sample_description field
                    $sample_lines = [];
                    if (!empty($req['sample_description']) && $req['sample_description'] !== 'Pending - To be filled by Receiving Officer') {
                        foreach (explode("\n", $req['sample_description']) as $line) {
                            $line = trim($line);
                            if ($line !== '') {
                                $parts = explode(':', $line, 2);
                                $sample_lines[] = [
                                    'code' => trim($parts[0] ?? ''),
                                    'desc' => trim($parts[1] ?? '')
                                ];
                            }
                        }
                    }
                ?>

                <?php if ($req['status'] === 'pending_payment'): ?>
                <!-- Payment Required Card — styled like inspection_schedules -->
                <div class="card" style="margin-bottom:20px;">
                    <div class="card-header">
                        <h3><i class="fas fa-flask" style="color:var(--primary-600);margin-right:8px;"></i>
                            <?= htmlspecialchars($req['company_name']) ?>
                        </h3>
                        <span class="badge badge-warning" style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;padding:4px 12px;border-radius:20px;font-size:0.8rem;font-weight:700;">PAYMENT REQUIRED</span>
                    </div>
                    <div class="card-body">
                        <!-- Top metadata row -->
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:18px;margin-bottom:22px;">
                            <div>
                                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Submitted</div>
                                <div style="font-weight:700;font-size:1.05rem;"><?= formatDateTime($req['created_at']) ?></div>
                            </div>
                            <div>
                                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Testing Fee</div>
                                <div style="font-weight:700;font-size:1.05rem;color:var(--primary-600);"><?= formatCurrency($req['payment_testing_fee']) ?></div>
                            </div>
                            <div>
                                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Request Status</div>
                                <div><?= getStatusBadge($req['status']) ?></div>
                            </div>
                            <div>
                                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Payment Status</div>
                                <div><?= getStatusBadge($req['payment_status']) ?></div>
                            </div>
                            <div>
                                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Delivery Method</div>
                                <div style="font-weight:600;"><?= ucfirst(htmlspecialchars($delivery_method)) ?></div>
                            </div>
                        </div>

                        <!-- Two-column body -->
                        <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:22px;align-items:start;">
                            <!-- Left column -->
                            <div>
                                <div style="margin-bottom:16px;">
                                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Company</div>
                                    <div style="background:var(--neutral-50);border-radius:8px;padding:12px 14px;color:var(--neutral-700);">
                                        <?= htmlspecialchars($req['company_name']) ?>
                                    </div>
                                </div>

                                <div style="margin-bottom:16px;">
                                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Received At</div>
                                    <div style="color:var(--neutral-700);">
                                        <?= !empty($req['received_at']) ? formatDateTime($req['received_at']) : '<span style="color:var(--neutral-400);">Pending</span>' ?>
                                    </div>
                                </div>

                                <div>
                                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Receiving Notes</div>
                                    <div style="background:var(--neutral-50);border-radius:8px;padding:12px 14px;color:var(--neutral-700);min-height:48px;">
                                        <?= nl2br(htmlspecialchars($req['receiving_notes'] ?: 'No additional notes.')) ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Right column -->
                            <div>
                                <!-- Sample codes assigned by receiving officer -->
                                <?php if (!empty($sample_lines)): ?>
                                <div style="margin-bottom:16px;">
                                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">
                                        <i class="fas fa-barcode" style="margin-right:4px;"></i> Sample Details
                                    </div>
                                    <div style="display:flex;flex-direction:column;gap:6px;">
                                        <?php foreach ($sample_lines as $sl): ?>
                                        <div style="border:1px solid var(--neutral-100);border-radius:8px;padding:10px 12px;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                                            <div style="font-family:monospace;font-weight:700;color:var(--primary-600);white-space:nowrap;">
                                                <?= htmlspecialchars($sl['code']) ?>
                                            </div>
                                            <div style="font-size:0.85rem;color:var(--neutral-600);text-align:right;">
                                                <?= htmlspecialchars($sl['desc']) ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Tests requested -->
                                <?php if (!empty($tests)): ?>
                                <div style="margin-bottom:16px;">
                                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Tests Requested</div>
                                    <div style="display:flex;flex-direction:column;gap:6px;">
                                        <?php foreach ($tests as $t): ?>
                                        <div style="border:1px solid var(--neutral-100);border-radius:8px;padding:10px 12px;">
                                            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                                                <div>
                                                    <div style="font-weight:600;color:var(--neutral-700);font-size:0.9rem;">
                                                        <?= htmlspecialchars($t['test_name'] ?? '') ?>
                                                    </div>
                                                    <div style="font-size:0.8rem;color:var(--neutral-500);">
                                                        Qty: <?= htmlspecialchars($t['quantity'] ?? '1') ?> &times; ₱<?= number_format(floatval($t['unit_cost'] ?? 0), 2) ?>
                                                    </div>
                                                </div>
                                                <div style="font-weight:700;color:var(--neutral-700);white-space:nowrap;">
                                                    ₱<?= number_format(floatval($t['total'] ?? 0), 2) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- View Request Form button -->
                                <a href="<?= BASE_URL ?>dashboard/business_owner/lab_request_form.php?id=<?= $req['id'] ?>"
                                   class="btn btn-sm btn-outline" style="width:100%;text-align:center;margin-bottom:10px;">
                                    <i class="fas fa-file-alt"></i> View Request Form
                                </a>

                                <!-- Pay button -->
                                <a href="<?= BASE_URL ?>dashboard/business_owner/pay_laboratory.php?id=<?= $req['id'] ?>"
                                   class="btn btn-primary" style="width:100%;text-align:center;">
                                    <i class="fas fa-credit-card"></i> Pay Testing Fee — ₱<?= number_format($req['payment_testing_fee'], 2) ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <!-- Card for submitted / received / testing / completed -->
                <div class="card" style="margin-bottom:12px;">
                    <div class="card-header">
                        <h3><i class="fas fa-flask" style="color:var(--primary-600);margin-right:8px;"></i>
                            <?= htmlspecialchars($req['company_name']) ?>
                        </h3>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <?= getStatusBadge($req['payment_status']) ?>
                            <?= getStatusBadge($req['status']) ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:<?= $req['status'] === 'completed' ? '20px' : '0' ?>;">
                            <div>
                                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Testing Fee</div>
                                <div style="font-weight:700;"><?= formatCurrency($req['payment_testing_fee']) ?></div>
                            </div>
                            <div>
                                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Delivery</div>
                                <div style="font-weight:600;"><?= ucfirst(htmlspecialchars($delivery_method)) ?></div>
                            </div>
                            <div>
                                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Submitted</div>
                                <div style="font-size:0.9rem;"><?= formatDate($req['created_at']) ?></div>
                            </div>
                            <?php if ($req['status'] === 'completed' && !empty($req['report_number'])): ?>
                            <div>
                                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Report No.</div>
                                <div style="font-weight:700;font-family:monospace;"><?= htmlspecialchars($req['report_number']) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($req['status'] === 'completed' && !empty($req['lab_report_id'])): ?>
                        <!-- Results summary -->
                        <?php
                            $hs = $req['report_halal_status'] ?? $req['halal_status'] ?? '';
                            $hs_color = $hs === 'halal' ? 'var(--success)' : ($hs === 'haram' ? 'var(--danger)' : '#f59e0b');
                            $hs_bg    = $hs === 'halal' ? '#f0fdf4' : ($hs === 'haram' ? '#fef2f2' : '#fffbeb');
                            $hs_label = $hs === 'halal' ? 'Halal — Compliant' : ($hs === 'haram' ? 'Haram — Non-Compliant' : 'Mushbooh — Doubtful');
                            $hs_icon  = $hs === 'halal' ? 'check-circle' : ($hs === 'haram' ? 'times-circle' : 'exclamation-circle');
                        ?>
                        <div style="background:<?= $hs_bg ?>;border:1px solid <?= $hs_color ?>;border-radius:8px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="fas fa-<?= $hs_icon ?>" style="font-size:1.4rem;color:<?= $hs_color ?>;"></i>
                                <div>
                                    <div style="font-weight:700;color:<?= $hs_color ?>;"><?= $hs_label ?></div>
                                    <div style="font-size:0.82rem;color:var(--neutral-500);">Laboratory analysis completed &mdash; <?= formatDate($req['completed_at'] ?? $req['updated_at']) ?></div>
                                </div>
                            </div>
                            <a href="<?= BASE_URL ?>dashboard/business_owner/lab_report_view.php?id=<?= $req['lab_report_id'] ?>"
                               class="btn btn-outline btn-sm" style="white-space:nowrap;">
                                <i class="fas fa-file-medical-alt"></i> View Report of Analysis
                            </a>
                        </div>
                        <?php elseif ($req['status'] === 'received' || $req['status'] === 'testing'): ?>
                        <div class="alert alert-info" style="margin:0;">
                            <i class="fas fa-flask"></i>
                            <span>Your samples are currently being analyzed. You will be notified when the report is ready.</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    // Enable/disable submit button based on terms agreement
    const agreeCheckbox = document.getElementById('agree-terms');
    const submitBtn = document.getElementById('submit-btn');
    
    if (agreeCheckbox && submitBtn) {
        agreeCheckbox.addEventListener('change', function() {
            submitBtn.disabled = !this.checked;
            if (this.checked) {
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            } else {
                submitBtn.style.opacity = '0.5';
                submitBtn.style.cursor = 'not-allowed';
            }
        });
        
        // Initial state
        submitBtn.style.opacity = '0.5';
        submitBtn.style.cursor = 'not-allowed';
    }
    
    // Calculate row total
    function calculateRowTotal(row) {
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const unitCost = parseFloat(row.querySelector('.unit-cost').value) || 0;
        const total = quantity * unitCost;
        row.querySelector('.row-total').value = total.toFixed(2);
        calculateGrandTotal();
    }
    
    // Calculate grand total
    function calculateGrandTotal() {
        let grandTotal = 0;
        document.querySelectorAll('.test-item-row').forEach(function(row) {
            const rowTotal = parseFloat(row.querySelector('.row-total').value) || 0;
            grandTotal += rowTotal;
        });
        document.getElementById('grand-total').textContent = '₱ ' + grandTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    // Add event listeners to existing rows
    function attachRowListeners(row) {
        row.querySelector('.quantity-input').addEventListener('input', function() {
            calculateRowTotal(row);
        });
        row.querySelector('.unit-cost').addEventListener('input', function() {
            calculateRowTotal(row);
        });
    }
    
    // Initialize existing rows
    document.querySelectorAll('.test-item-row').forEach(function(row) {
        attachRowListeners(row);
        calculateRowTotal(row);
    });
    
    // Add new row button
    document.getElementById('add-test-row').addEventListener('click', function() {
        const tbody = document.getElementById('test-items-tbody');
        const rowCount = tbody.querySelectorAll('.test-item-row').length + 1;
        
        const newRow = document.createElement('tr');
        newRow.className = 'test-item-row';
        newRow.innerHTML = `
            <td style="border:1px solid #cbd5e1;padding:8px;"><input type="text" class="form-control test-name" name="test_name[]" placeholder="Enter test name" required style="font-size:0.85rem;"></td>
            <td style="border:1px solid #cbd5e1;padding:8px;"><input type="number" class="form-control quantity-input" name="quantity[]" value="1" min="1" required style="font-size:0.85rem;text-align:center;"></td>
            <td style="border:1px solid #cbd5e1;padding:8px;"><input type="number" class="form-control unit-cost" name="unit_cost[]" value="0.00" step="0.01" min="0" required style="font-size:0.85rem;text-align:right;"></td>
            <td style="border:1px solid #cbd5e1;padding:8px;"><input type="text" class="form-control row-total" readonly value="0.00" style="font-size:0.85rem;text-align:right;background:#f8fafc;"></td>
        `;
        
        tbody.appendChild(newRow);
        attachRowListeners(newRow);
        calculateRowTotal(newRow);
    });
    
    // Sync test details to hidden field before submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function() {
            const testDetails = [];
            
            document.querySelectorAll('.test-item-row').forEach(function(row) {
                const testName = row.querySelector('input[name="test_name[]"]').value;
                const quantity = row.querySelector('input[name="quantity[]"]').value;
                const unitCost = row.querySelector('input[name="unit_cost[]"]').value;
                const total = row.querySelector('.row-total').value;
                
                if (testName) {
                    testDetails.push({
                        test_name: testName,
                        quantity: quantity,
                        unit_cost: unitCost,
                        total: total
                    });
                }
            });
            document.getElementById('test-details-hidden').value = JSON.stringify(testDetails);
        });
    }
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
