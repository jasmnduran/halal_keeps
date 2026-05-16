<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_LAB_ANALYST]);

$page_title = 'Analyze Samples';
$page_heading = 'Analyze Samples';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/lab_analyst/'], ['label' => 'Analyze']];
$user_id = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_type = $_POST['action_type'] ?? '';
    
    if ($action_type === 'start_testing') {
        $request_id = intval($_POST['request_id']);
        $conn->query("UPDATE laboratory_requests SET status = 'testing', analyst_id = $user_id, testing_started_at = NOW() WHERE id = $request_id");
        $success = 'Testing started!';
    }
    
    if ($action_type === 'submit_results') {
        $request_id = intval($_POST['request_id']);
        $app_id = intval($_POST['application_id']);
        $result = sanitize($_POST['test_result']);
        $analysis = sanitize($_POST['analysis_details']);
        $halal_status = sanitize($_POST['halal_status']);
        $report_number = sanitize($_POST['report_ref_no'] ?? generateReference('R11'));

        // Build structured report content from per-sample fields
        $sample_codes  = $_POST['sample_codes']  ?? [];
        $sample_descs  = $_POST['sample_descs']  ?? [];
        $parameters    = $_POST['parameters']    ?? [];
        $results       = $_POST['results']       ?? [];
        $methodology   = sanitize($_POST['methodology']    ?? '');
        $remarks       = sanitize($_POST['remarks']        ?? '');
        $analyst_name  = sanitize($_POST['analyst_name']   ?? '');
        $analyst_lic   = sanitize($_POST['analyst_license'] ?? '');
        $reviewer_name = sanitize($_POST['reviewer_name']  ?? '');
        $reviewer_lic  = sanitize($_POST['reviewer_license'] ?? '');
        $approver_name = sanitize($_POST['approver_name']  ?? '');
        $date_analyzed = sanitize($_POST['date_analyzed']  ?? date('Y-m-d'));
        $date_reported = sanitize($_POST['date_reported']  ?? date('Y-m-d'));
        $sample_type   = sanitize($_POST['sample_submitted_type'] ?? '');

        // Compile full report content as structured text
        $report_lines = [];
        $report_lines[] = "REPORT OF ANALYSIS — Halal Verification Testing";
        $report_lines[] = "Request Reference No: $report_number";
        $report_lines[] = "Date Analyzed: $date_analyzed | Date Reported: $date_reported";
        $report_lines[] = "Sample Submitted: $sample_type";
        $report_lines[] = "";
        $report_lines[] = "SAMPLE RESULTS:";
        foreach ($sample_codes as $i => $code) {
            $desc   = $sample_descs[$i]  ?? '';
            $param  = $parameters[$i]    ?? '';
            $res    = $results[$i]       ?? '';
            $report_lines[] = "  $code | $desc | $param | $res";
        }
        $report_lines[] = "";
        $report_lines[] = "METHODOLOGY: $methodology";
        $report_lines[] = "";
        $report_lines[] = "REMARKS: $remarks";
        $report_lines[] = "";
        $report_lines[] = "Analyzed By: $analyst_name (PRC $analyst_lic)";
        $report_lines[] = "Reviewed By: $reviewer_name (PRC $reviewer_lic)";
        $report_lines[] = "Approved By: $approver_name";
        $report_content = implode("\n", $report_lines);

        // Update lab request
        $stmt = $conn->prepare("UPDATE laboratory_requests SET status = 'completed', test_result = ?, analysis_details = ?, halal_status = ?, completed_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssi", $result, $analysis, $halal_status, $request_id);
        $stmt->execute();
        
        // Create lab report
        $stmt2 = $conn->prepare("INSERT INTO laboratory_reports (laboratory_request_id, application_id, analyst_id, report_number, report_content, halal_status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("iiisss", $request_id, $app_id, $user_id, $report_number, $report_content, $halal_status);
        $stmt2->execute();
        $lab_report_id = $stmt2->insert_id;
        
        // Notify evaluator/auditors
        $evaluators = $conn->query("SELECT id FROM users WHERE role_id IN (" . ROLE_EVALUATOR . "," . ROLE_AUDITOR_TECHNICAL . ") AND role_status = 'approved'");
        while ($e = $evaluators->fetch_assoc()) {
            createNotification($conn, $e['id'], 'Lab Report Ready: ' . $report_number,
                'Laboratory analysis is complete. Report is available for review.', 'info',
                BASE_URL . 'dashboard/evaluator/');
        }
        
        $owner = $conn->query("SELECT business_owner_id FROM laboratory_requests WHERE id = $request_id")->fetch_assoc();
        if ($owner) {
            $halal_label = $halal_status === 'halal' ? 'Halal — Compliant' : ($halal_status === 'haram' ? 'Haram — Non-Compliant' : 'Mushbooh — Doubtful');
            createNotification($conn, $owner['business_owner_id'],
                'Laboratory Report Ready: ' . $report_number,
                'Your laboratory analysis is complete. Report: ' . $report_number . ' | Halal Status: ' . $halal_label . '. Click to view your full Report of Analysis.',
                $halal_status === 'halal' ? 'success' : 'warning',
                BASE_URL . 'dashboard/business_owner/lab_report_view.php?id=' . $lab_report_id
            );
        }
        
        logActivity($conn, $user_id, 'Lab Report Created', 'Report ' . $report_number, 'laboratory');
        $success = 'Lab report ' . $report_number . ' submitted successfully!';
    }
}

$filter = $_GET['filter'] ?? 'received';
$where_clause = $filter === 'all' ? "WHERE lr.status IN ('received','testing','completed') AND lr.payment_status = 'paid'" : "WHERE lr.status = '$filter' AND lr.payment_status = 'paid'";
$samples = $conn->query("SELECT lr.*, loi.company_name, ha.id as app_id FROM laboratory_requests lr JOIN hdp_applications ha ON lr.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id $where_clause ORDER BY lr.created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="display: flex; gap: 8px;">
        <a href="?filter=received" class="btn btn-sm <?= $filter === 'received' ? 'btn-primary' : 'btn-secondary' ?>">Awaiting</a>
        <a href="?filter=testing" class="btn btn-sm <?= $filter === 'testing' ? 'btn-primary' : 'btn-secondary' ?>">In Testing</a>
        <a href="?filter=completed" class="btn btn-sm <?= $filter === 'completed' ? 'btn-primary' : 'btn-secondary' ?>">Completed</a>
        <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
    </div>
</div>

<?php foreach ($samples as $s): ?>
<div class="card" style="margin-bottom: 16px;">
    <div class="card-header">
        <h3><i class="fas fa-vial" style="color:var(--primary-600);margin-right:8px"></i> <?= htmlspecialchars($s['company_name']) ?></h3>
        <?= getStatusBadge($s['status']) ?>
    </div>
    <div class="card-body">
        <?php
        // Parse analysis requirements JSON
        $analysis_data = json_decode($s['analysis_requirements'], true);
        $tests = $analysis_data['tests'] ?? [];
        $request_interpretation = $analysis_data['request_interpretation'] ?? 'no';
        $delivery_method = $analysis_data['delivery_method'] ?? 'pickup';
        $special_instructions = $analysis_data['special_instructions'] ?? '';
        ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div><label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase">Payment</label><p><?= getStatusBadge($s['payment_status']) ?> - <?= formatCurrency($s['payment_testing_fee']) ?></p></div>
            <div><label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase">Received</label><p style="font-size:0.9rem"><?= $s['received_at'] ? formatDateTime($s['received_at']) : 'N/A' ?></p></div>
            <div><label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase">Delivery Method</label><p style="font-size:0.9rem"><?= ucfirst(htmlspecialchars($delivery_method)) ?></p></div>
        </div>
        
        <?php if (!empty($tests)): ?>
        <div style="margin-bottom:16px;">
            <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase;display:block;margin-bottom:8px;">Test Details</label>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;border:1px solid #e2e8f0;font-size:0.85rem;">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th style="border:1px solid #e2e8f0;padding:8px;text-align:left;">Sample Code</th>
                            <th style="border:1px solid #e2e8f0;padding:8px;text-align:left;">Sample Description</th>
                            <th style="border:1px solid #e2e8f0;padding:8px;text-align:left;">Test/Service</th>
                            <th style="border:1px solid #e2e8f0;padding:8px;text-align:center;">Qty</th>
                            <th style="border:1px solid #e2e8f0;padding:8px;text-align:right;">Unit Cost</th>
                            <th style="border:1px solid #e2e8f0;padding:8px;text-align:right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests as $test): ?>
                        <tr>
                            <td style="border:1px solid #e2e8f0;padding:8px;"><strong><?= htmlspecialchars($test['sample_code'] ?? '') ?></strong></td>
                            <td style="border:1px solid #e2e8f0;padding:8px;"><?= htmlspecialchars($test['sample_description'] ?? '') ?></td>
                            <td style="border:1px solid #e2e8f0;padding:8px;"><?= htmlspecialchars($test['test_name'] ?? '') ?></td>
                            <td style="border:1px solid #e2e8f0;padding:8px;text-align:center;"><?= htmlspecialchars($test['quantity'] ?? '1') ?></td>
                            <td style="border:1px solid #e2e8f0;padding:8px;text-align:right;">₱<?= number_format(floatval($test['unit_cost'] ?? 0), 2) ?></td>
                            <td style="border:1px solid #e2e8f0;padding:8px;text-align:right;">₱<?= number_format(floatval($test['total'] ?? 0), 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase;display:block;margin-bottom:4px;">Request for Interpretation</label>
                <p style="font-size:0.9rem;"><?= $request_interpretation === 'yes' ? '<span style="color:var(--success);"><i class="fas fa-check-circle"></i> Yes</span>' : '<span style="color:var(--neutral-500);"><i class="fas fa-times-circle"></i> No</span>' ?></p>
            </div>
            <?php if (!empty($special_instructions)): ?>
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase;display:block;margin-bottom:4px;">Special Instructions</label>
                <p style="font-size:0.9rem;"><?= nl2br(htmlspecialchars($special_instructions)) ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($s['status'] === 'received'): ?>
        <form method="POST">
            <input type="hidden" name="action_type" value="start_testing">
            <input type="hidden" name="request_id" value="<?= $s['id'] ?>">
            <button type="submit" class="btn btn-primary"><i class="fas fa-play"></i> Start Testing</button>
        </form>
        <?php elseif ($s['status'] === 'testing'): ?>
        <?php
            // Parse sample lines from sample_description
            $sample_lines_raw = [];
            if (!empty($s['sample_description']) && $s['sample_description'] !== 'Pending - To be filled by Receiving Officer') {
                foreach (explode("\n", $s['sample_description']) as $ln) {
                    $ln = trim($ln);
                    if ($ln !== '') {
                        $parts = explode(':', $ln, 2);
                        $sample_lines_raw[] = ['code' => trim($parts[0] ?? ''), 'desc' => trim($parts[1] ?? '')];
                    }
                }
            }
            $report_ref = generateReference('R11');
            $today = date('F j, Y');
        ?>
        <div style="border-top:2px solid var(--neutral-100);padding-top:24px;margin-top:16px;">

            <!-- Report of Analysis header -->
            <div style="border:2px solid #1a1a1a;padding:0;margin-bottom:0;font-family:Arial,sans-serif;font-size:0.85rem;background:#fff;">

                <!-- Letterhead -->
                <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:2px solid #1a1a1a;gap:12px;">
                    <div style="display:flex;align-items:center;gap:14px;">
                        <div style="width:56px;height:56px;border:2px solid #1a1a1a;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-flask" style="font-size:1.6rem;color:#1a1a1a;"></i>
                        </div>
                        <div style="line-height:1.4;">
                            <div style="font-size:0.7rem;">Republic of the Philippines</div>
                            <div style="font-weight:700;font-size:0.8rem;">DEPARTMENT OF SCIENCE AND TECHNOLOGY</div>
                            <div style="font-weight:700;font-size:0.75rem;">REGIONAL OFFICE NO. XI</div>
                            <div style="font-weight:700;font-size:0.75rem;">REGIONAL STANDARDS AND TESTING LABORATORY - DAVAO</div>
                        </div>
                    </div>
                    <div style="border:2px solid #1a1a1a;padding:6px 10px;text-align:center;font-size:0.7rem;min-width:100px;">
                        <div style="font-weight:700;">PAB</div>
                        <div style="font-size:0.65rem;">ACCREDITED<br>TESTING LABORATORY</div>
                    </div>
                </div>

                <!-- Report title -->
                <div style="text-align:center;padding:14px 20px;border-bottom:1px solid #ccc;">
                    <div style="font-weight:700;font-size:1.1rem;letter-spacing:0.05em;">REPORT OF ANALYSIS</div>
                    <div style="font-size:0.85rem;font-style:italic;">Halal Verification Testing</div>
                </div>

                <form method="POST" style="margin:0;">
                    <input type="hidden" name="action_type" value="submit_results">
                    <input type="hidden" name="request_id" value="<?= $s['id'] ?>">
                    <input type="hidden" name="application_id" value="<?= $s['app_id'] ?>">

                    <!-- Request metadata -->
                    <div style="padding:14px 20px;border-bottom:1px solid #ccc;display:grid;grid-template-columns:1fr 1fr;gap:4px 32px;font-size:0.82rem;">
                        <div style="display:flex;gap:8px;">
                            <span style="min-width:160px;font-weight:600;">Request Reference No</span>
                            <span>: <input type="text" name="report_ref_no" class="form-control" value="<?= htmlspecialchars($report_ref) ?>" style="display:inline;width:auto;min-width:180px;font-size:0.82rem;padding:2px 6px;height:auto;"></span>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <span style="min-width:160px;font-weight:600;">Date Submitted</span>
                            <span>: <?= formatDate($s['created_at']) ?></span>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <span style="min-width:160px;font-weight:600;">Date/s Analyzed</span>
                            <span>: <input type="date" name="date_analyzed" class="form-control" value="<?= date('Y-m-d') ?>" style="display:inline;width:auto;font-size:0.82rem;padding:2px 6px;height:auto;"></span>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <span style="min-width:160px;font-weight:600;">Date Reported</span>
                            <span>: <input type="date" name="date_reported" class="form-control" value="<?= date('Y-m-d') ?>" style="display:inline;width:auto;font-size:0.82rem;padding:2px 6px;height:auto;"></span>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <span style="min-width:160px;font-weight:600;">Sample Submitted</span>
                            <span>: <input type="text" name="sample_submitted_type" class="form-control" placeholder="e.g. Viand, Meat, etc." style="display:inline;width:auto;min-width:180px;font-size:0.82rem;padding:2px 6px;height:auto;"></span>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <span style="min-width:160px;font-weight:600;">Submitted by</span>
                            <span>: <?= htmlspecialchars($s['company_name']) ?></span>
                        </div>
                        <div style="display:flex;gap:8px;grid-column:1/-1;">
                            <span style="min-width:160px;font-weight:600;">Page</span>
                            <span>: 1 of 1</span>
                        </div>
                    </div>

                    <!-- Sample results table -->
                    <div style="padding:0 0 0 0;">
                        <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                            <thead>
                                <tr style="background:#f0f0f0;">
                                    <th style="border:1px solid #999;padding:8px 10px;text-align:center;width:14%;">Sample Code</th>
                                    <th style="border:1px solid #999;padding:8px 10px;text-align:center;width:36%;">Sample Description</th>
                                    <th style="border:1px solid #999;padding:8px 10px;text-align:center;width:25%;">Parameter</th>
                                    <th style="border:1px solid #999;padding:8px 10px;text-align:center;width:25%;">Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Build rows: one per sample code, parameter = test name
                                // If sample_lines exist, pair them with tests; otherwise use tests directly
                                $row_count = max(count($sample_lines_raw), count($tests), 1);
                                for ($i = 0; $i < $row_count; $i++):
                                    $code = $sample_lines_raw[$i]['code'] ?? ($tests[$i]['sample_code'] ?? '');
                                    $desc = $sample_lines_raw[$i]['desc'] ?? ($tests[$i]['sample_description'] ?? '');
                                    $param = $tests[$i]['test_name'] ?? '';
                                ?>
                                <tr>
                                    <td style="border:1px solid #999;padding:6px 10px;text-align:center;font-weight:700;font-family:monospace;">
                                        <?= htmlspecialchars($code) ?>
                                        <input type="hidden" name="sample_codes[]" value="<?= htmlspecialchars($code) ?>">
                                    </td>
                                    <td style="border:1px solid #999;padding:6px 10px;">
                                        <textarea name="sample_descs[]" class="form-control" rows="2" style="font-size:0.82rem;resize:vertical;min-height:40px;"><?= htmlspecialchars($desc) ?></textarea>
                                    </td>
                                    <td style="border:1px solid #999;padding:6px 10px;">
                                        <input type="text" name="parameters[]" class="form-control" value="<?= htmlspecialchars($param) ?>" style="font-size:0.82rem;" placeholder="e.g. Porcine DNA">
                                    </td>
                                    <td style="border:1px solid #999;padding:6px 10px;">
                                        <select name="results[]" class="form-control" style="font-size:0.82rem;" required>
                                            <option value="">Select...</option>
                                            <option value="Negative">Negative</option>
                                            <option value="Positive">Positive</option>
                                            <option value="Pass">Pass</option>
                                            <option value="Fail">Fail</option>
                                            <option value="Inconclusive">Inconclusive</option>
                                            <option value="Within Limits">Within Limits</option>
                                            <option value="Exceeds Limits">Exceeds Limits</option>
                                        </select>
                                    </td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Methodology -->
                    <div style="padding:12px 20px;border-top:1px solid #ccc;">
                        <div style="font-weight:700;margin-bottom:6px;font-size:0.82rem;">METHODOLOGY:</div>
                        <textarea name="methodology" class="form-control" rows="2" style="font-size:0.82rem;" placeholder="e.g. Animal DNA Analysis by Real Time-PCR (HVL-TM-005)"></textarea>
                    </div>

                    <!-- Remarks -->
                    <div style="padding:12px 20px;border-top:1px solid #ccc;">
                        <div style="font-weight:700;margin-bottom:6px;font-size:0.82rem;">REMARKS:</div>
                        <textarea name="remarks" class="form-control" rows="3" style="font-size:0.82rem;" placeholder="Standard remarks or additional observations...">The results given in this report were obtained at the time of test and refer only to the particular sample submitted.
This report shall not be reproduced except in full, without the written approval of the laboratory.
All text with a single asterisk are provided by the customer at the time of submission.</textarea>
                    </div>

                    <!-- Overall result + halal status (hidden from print, needed for DB) -->
                    <div style="padding:12px 20px;border-top:1px solid #ccc;display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group" style="margin:0;">
                            <label style="font-weight:700;font-size:0.82rem;">Overall Test Result <span class="required">*</span></label>
                            <select name="test_result" class="form-control" required style="font-size:0.82rem;">
                                <option value="">Select...</option>
                                <option value="pass">Pass</option>
                                <option value="fail">Fail</option>
                                <option value="inconclusive">Inconclusive</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label style="font-weight:700;font-size:0.82rem;">Halal Status <span class="required">*</span></label>
                            <select name="halal_status" class="form-control" required style="font-size:0.82rem;">
                                <option value="">Select...</option>
                                <option value="halal">Halal — Compliant</option>
                                <option value="haram">Haram — Non-Compliant</option>
                                <option value="mushbooh">Mushbooh — Doubtful</option>
                            </select>
                        </div>
                    </div>

                    <!-- Analyst signature block -->
                    <div style="padding:16px 20px;border-top:1px solid #ccc;display:grid;grid-template-columns:1fr 1fr;gap:24px;font-size:0.82rem;">
                        <div>
                            <div style="margin-bottom:10px;">
                                <div style="font-weight:600;margin-bottom:4px;">Analyzed By:</div>
                                <input type="text" name="analyst_name" class="form-control" value="<?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>" style="font-size:0.82rem;" placeholder="Full name, credentials">
                                <div style="font-size:0.75rem;color:#666;margin-top:3px;">Laboratory Analyst</div>
                                <input type="text" name="analyst_license" class="form-control" style="font-size:0.82rem;margin-top:4px;" placeholder="PRC License No.">
                            </div>
                            <div>
                                <div style="font-weight:600;margin-bottom:4px;">Reviewed/Checked by:</div>
                                <input type="text" name="reviewer_name" class="form-control" style="font-size:0.82rem;" placeholder="Full name, credentials">
                                <div style="font-size:0.75rem;color:#666;margin-top:3px;">Laboratory Analyst</div>
                                <input type="text" name="reviewer_license" class="form-control" style="font-size:0.82rem;margin-top:4px;" placeholder="PRC License No.">
                            </div>
                        </div>
                        <div>
                            <div style="font-weight:600;margin-bottom:4px;">Approved for Release by:</div>
                            <input type="text" name="approver_name" class="form-control" style="font-size:0.82rem;" placeholder="Full name, title">
                            <div style="font-size:0.75rem;color:#666;margin-top:3px;">DOST RSTL-Davao Laboratory Head</div>
                            <div style="margin-top:16px;">
                                <div style="font-weight:600;margin-bottom:4px;">Analysis Details / Notes:</div>
                                <textarea name="analysis_details" class="form-control" rows="3" style="font-size:0.82rem;" placeholder="Additional analysis notes..." required></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Footer note -->
                    <div style="padding:8px 20px;border-top:1px solid #ccc;font-size:0.72rem;color:#555;display:flex;justify-content:space-between;align-items:center;">
                        <span>** Approved Signatory</span>
                        <span>RSTL XI –WI-015-F29 &nbsp;|&nbsp; Revision 0</span>
                    </div>

                    <!-- Hidden field: compile full report_content from form data via JS -->
                    <input type="hidden" name="report_content" id="report_content_<?= $s['id'] ?>">

                    <div style="padding:16px 20px;border-top:2px solid #1a1a1a;display:flex;justify-content:flex-end;gap:10px;background:#f8fafc;">
                        <button type="submit" class="btn btn-primary" onclick="compileReport(<?= $s['id'] ?>)">
                            <i class="fas fa-file-medical-alt"></i> Submit Report of Analysis
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php elseif ($s['status'] === 'completed'): ?>
        <div style="background:var(--primary-50);padding:16px;border-radius:10px">
            <p><strong>Result:</strong> <?= getStatusBadge($s['test_result'] ?? 'N/A') ?></p>
            <p><strong>Halal Status:</strong> <?= getStatusBadge($s['halal_status'] ?? 'N/A') ?></p>
            <?php if ($s['analysis_details']): ?><p style="margin-top:8px;font-size:0.9rem"><?= nl2br(htmlspecialchars(substr($s['analysis_details'], 0, 200))) ?></p><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php if (empty($samples)): ?>
<div class="card"><div class="card-body"><div class="empty-state"><div class="empty-icon"><i class="fas fa-flask"></i></div><h3>No Samples</h3><p>No samples matching this filter.</p></div></div></div>
<?php endif; ?>

<script>
function compileReport(id) {
    // The report_content hidden field is compiled server-side from named fields,
    // so nothing extra needed here — just allow form submit.
    return true;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
