<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$page_title = 'Report of Analysis';
$page_heading = 'Report of Analysis';
$is_dashboard = true;
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/business_owner/'],
    ['label' => 'Laboratory', 'url' => BASE_URL . 'dashboard/business_owner/laboratory.php'],
    ['label' => 'Report of Analysis']
];

$user_id = $_SESSION['user_id'];
$report_id = intval($_GET['id'] ?? 0);

// Fetch report with request and company info
$stmt = $conn->prepare("
    SELECT labr.*,
           lr.sample_description, lr.analysis_requirements, lr.payment_testing_fee,
           lr.received_at, lr.created_at AS submitted_at, lr.completed_at,
           loi.company_name, loi.company_address, loi.contact_person, loi.contact_phone,
           u.full_name AS analyst_full_name
    FROM laboratory_reports labr
    JOIN laboratory_requests lr ON labr.laboratory_request_id = lr.id
    JOIN hdp_applications ha ON lr.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    JOIN users u ON labr.analyst_id = u.id
    WHERE labr.id = ? AND lr.business_owner_id = ?
");
$stmt->bind_param("ii", $report_id, $user_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

if (!$report) {
    $_SESSION['error'] = 'Report not found.';
    header('Location: ' . BASE_URL . 'dashboard/business_owner/laboratory.php');
    exit;
}

// Parse report_content back into sections
$content = $report['report_content'] ?? '';
$lines = explode("\n", $content);

// Extract structured fields from report_content
$ref_no       = '';
$date_analyzed = '';
$date_reported = '';
$sample_type  = '';
$sample_rows  = [];   // ['code','desc','param','result']
$methodology  = '';
$remarks_text = '';
$analyst_sig  = '';
$reviewer_sig = '';
$approver_sig = '';

$section = '';
foreach ($lines as $line) {
    $line = trim($line);
    if (str_starts_with($line, 'Request Reference No:'))  { $ref_no = trim(substr($line, 21)); }
    elseif (str_starts_with($line, 'Date Analyzed:'))     { preg_match('/Date Analyzed:\s*([^\|]+)/', $line, $m); $date_analyzed = trim($m[1] ?? ''); }
    elseif (str_starts_with($line, 'Sample Submitted:'))  { $sample_type = trim(substr($line, 17)); }
    elseif (str_starts_with($line, 'Date Reported:'))     { preg_match('/Date Reported:\s*(.+)/', $line, $m); $date_reported = trim($m[1] ?? ''); }
    elseif ($line === 'SAMPLE RESULTS:')                  { $section = 'samples'; }
    elseif ($line === '' && $section === 'samples')       { $section = ''; }
    elseif ($section === 'samples' && str_starts_with($line, ' ')) {
        $parts = array_map('trim', explode('|', $line));
        $sample_rows[] = ['code' => $parts[0] ?? '', 'desc' => $parts[1] ?? '', 'param' => $parts[2] ?? '', 'result' => $parts[3] ?? ''];
    }
    elseif (str_starts_with($line, 'METHODOLOGY:'))       { $methodology = trim(substr($line, 12)); }
    elseif (str_starts_with($line, 'REMARKS:'))           { $section = 'remarks'; $remarks_text = trim(substr($line, 8)); }
    elseif ($section === 'remarks' && $line !== '')       { $remarks_text .= "\n" . $line; }
    elseif (str_starts_with($line, 'Analyzed By:'))       { $analyst_sig  = trim(substr($line, 12)); $section = ''; }
    elseif (str_starts_with($line, 'Reviewed By:'))       { $reviewer_sig = trim(substr($line, 12)); }
    elseif (str_starts_with($line, 'Approved By:'))       { $approver_sig = trim(substr($line, 12)); }
}

// Fallback: parse sample_description if sample_rows is empty
if (empty($sample_rows) && !empty($report['sample_description'])) {
    $analysis_data = json_decode($report['analysis_requirements'], true);
    $tests = $analysis_data['tests'] ?? [];
    foreach (explode("\n", $report['sample_description']) as $i => $ln) {
        $ln = trim($ln);
        if ($ln === '') continue;
        $parts = explode(':', $ln, 2);
        $sample_rows[] = [
            'code'   => trim($parts[0] ?? ''),
            'desc'   => trim($parts[1] ?? ''),
            'param'  => $tests[$i]['test_name'] ?? '',
            'result' => ''
        ];
    }
}

$halal_status = $report['halal_status'];
$hs_color = $halal_status === 'halal' ? '#16a34a' : ($halal_status === 'haram' ? '#dc2626' : '#d97706');
$hs_label = $halal_status === 'halal' ? 'Halal — Compliant' : ($halal_status === 'haram' ? 'Haram — Non-Compliant' : 'Mushbooh — Doubtful');

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
@media print {
    .no-print { display: none !important; }
    .card { box-shadow: none !important; border: none !important; }
    body { background: #fff !important; }
}
.roa-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
.roa-table th, .roa-table td { border:1px solid #999; padding:8px 10px; }
.roa-table thead { background:#f0f0f0; }
</style>

<!-- Action bar -->
<div class="no-print" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <a href="<?= BASE_URL ?>dashboard/business_owner/laboratory.php" class="btn btn-outline btn-sm">
        <i class="fas fa-arrow-left"></i> Back to Laboratory
    </a>
    <button onclick="window.print()" class="btn btn-primary btn-sm">
        <i class="fas fa-print"></i> Print / Save as PDF
    </button>
</div>

<!-- Report of Analysis document -->
<div class="card" style="max-width:860px;margin:0 auto;">
    <div class="card-body" style="padding:0;">
        <div style="border:2px solid #1a1a1a;font-family:Arial,sans-serif;font-size:0.85rem;background:#fff;">

            <!-- Letterhead -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:2px solid #1a1a1a;gap:12px;">
                <div style="display:flex;align-items:center;gap:16px;">
                    <div style="width:60px;height:60px;border:2px solid #1a1a1a;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-flask" style="font-size:1.8rem;color:#1a1a1a;"></i>
                    </div>
                    <div style="line-height:1.5;">
                        <div style="font-size:0.72rem;">Republic of the Philippines</div>
                        <div style="font-weight:700;font-size:0.82rem;">DEPARTMENT OF SCIENCE AND TECHNOLOGY</div>
                        <div style="font-weight:700;font-size:0.78rem;">REGIONAL OFFICE NO. XI</div>
                        <div style="font-weight:700;font-size:0.78rem;">REGIONAL STANDARDS AND TESTING LABORATORY - DAVAO</div>
                    </div>
                </div>
                <div style="border:2px solid #1a1a1a;padding:8px 12px;text-align:center;font-size:0.72rem;min-width:110px;">
                    <div style="font-weight:700;font-size:1rem;">PAB</div>
                    <div>ACCREDITED<br>TESTING LABORATORY<br><span style="font-size:0.65rem;">PNS ISO/IEC 17025:2017</span></div>
                </div>
            </div>

            <!-- Report title -->
            <div style="text-align:center;padding:16px 24px;border-bottom:1px solid #ccc;">
                <div style="font-weight:700;font-size:1.15rem;letter-spacing:0.06em;">REPORT OF ANALYSIS</div>
                <div style="font-size:0.9rem;font-style:italic;">Halal Verification Testing</div>
            </div>

            <!-- Metadata -->
            <div style="padding:14px 24px;border-bottom:1px solid #ccc;display:grid;grid-template-columns:1fr 1fr;gap:5px 32px;font-size:0.83rem;">
                <div><span style="display:inline-block;min-width:160px;font-weight:600;">Request Reference No</span> : <?= htmlspecialchars($ref_no ?: $report['report_number']) ?></div>
                <div><span style="display:inline-block;min-width:160px;font-weight:600;">Date Submitted</span> : <?= formatDate($report['submitted_at']) ?></div>
                <div><span style="display:inline-block;min-width:160px;font-weight:600;">Date/s Analyzed</span> : <?= htmlspecialchars($date_analyzed) ?></div>
                <div><span style="display:inline-block;min-width:160px;font-weight:600;">Date Reported</span> : <?= htmlspecialchars($date_reported) ?></div>
                <div><span style="display:inline-block;min-width:160px;font-weight:600;">Sample Submitted</span> : <?= htmlspecialchars($sample_type) ?></div>
                <div><span style="display:inline-block;min-width:160px;font-weight:600;">Submitted by</span> : <?= htmlspecialchars($report['company_name']) ?></div>
                <div><span style="display:inline-block;min-width:160px;font-weight:600;">Address</span> : <?= htmlspecialchars($report['company_address'] ?? '') ?></div>
                <div><span style="display:inline-block;min-width:160px;font-weight:600;">Contact No.</span> : <?= htmlspecialchars($report['contact_phone'] ?? '') ?></div>
                <div><span style="display:inline-block;min-width:160px;font-weight:600;">Page</span> : 1 of 1</div>
            </div>

            <!-- Sample results table -->
            <div style="padding:0;">
                <table class="roa-table">
                    <thead>
                        <tr>
                            <th style="text-align:center;width:14%;">Sample Code</th>
                            <th style="text-align:center;width:38%;">Sample Description</th>
                            <th style="text-align:center;width:24%;">Parameter</th>
                            <th style="text-align:center;width:24%;">Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sample_rows)): ?>
                            <?php foreach ($sample_rows as $row): ?>
                            <tr>
                                <td style="text-align:center;font-weight:700;font-family:monospace;"><?= htmlspecialchars($row['code']) ?></td>
                                <td><?= nl2br(htmlspecialchars($row['desc'])) ?></td>
                                <td>
                                    <?php if (!empty($row['param'])): ?>
                                    <span style="display:flex;align-items:center;gap:6px;">
                                        <span style="width:6px;height:6px;background:#1a1a1a;border-radius:50%;flex-shrink:0;display:inline-block;"></span>
                                        <?= htmlspecialchars($row['param']) ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['result'])): ?>
                                    <?php
                                        $r = $row['result'];
                                        $rc = in_array($r, ['Negative','Pass','Within Limits']) ? '#16a34a' : (in_array($r, ['Positive','Fail','Exceeds Limits']) ? '#dc2626' : '#d97706');
                                    ?>
                                    <span style="display:flex;align-items:center;gap:6px;color:<?= $rc ?>;">
                                        <span style="width:6px;height:6px;background:<?= $rc ?>;border-radius:50%;flex-shrink:0;display:inline-block;"></span>
                                        <strong><?= htmlspecialchars($r) ?></strong>
                                    </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;color:#999;padding:20px;">No sample data recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Methodology -->
            <?php if (!empty($methodology)): ?>
            <div style="padding:12px 24px;border-top:1px solid #ccc;">
                <div style="font-weight:700;margin-bottom:6px;">METHODOLOGY:</div>
                <div style="display:flex;align-items:flex-start;gap:8px;">
                    <span style="width:6px;height:6px;background:#1a1a1a;border-radius:50%;flex-shrink:0;margin-top:6px;display:inline-block;"></span>
                    <span><?= nl2br(htmlspecialchars($methodology)) ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Remarks -->
            <?php if (!empty($remarks_text)): ?>
            <div style="padding:12px 24px;border-top:1px solid #ccc;">
                <div style="font-weight:700;margin-bottom:6px;">REMARKS:</div>
                <?php foreach (explode("\n", trim($remarks_text)) as $remark): ?>
                <?php if (trim($remark) === '') continue; ?>
                <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:4px;">
                    <span style="width:6px;height:6px;background:#1a1a1a;border-radius:50%;flex-shrink:0;margin-top:6px;display:inline-block;"></span>
                    <span><?= htmlspecialchars(trim($remark)) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Halal status summary -->
            <div style="padding:12px 24px;border-top:1px solid #ccc;background:#fafafa;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-weight:700;">Overall Halal Status:</span>
                    <span style="font-weight:700;color:<?= $hs_color ?>;"><?= $hs_label ?></span>
                </div>
            </div>

            <!-- Signature block -->
            <div style="padding:20px 24px;border-top:1px solid #ccc;display:grid;grid-template-columns:1fr 1fr;gap:32px;font-size:0.83rem;">
                <div>
                    <?php if (!empty($analyst_sig)): ?>
                    <div style="margin-bottom:20px;">
                        <div style="border-bottom:1px solid #999;padding-bottom:4px;margin-bottom:6px;min-height:36px;"></div>
                        <div>Analyzed By: <strong><?= htmlspecialchars($analyst_sig) ?></strong></div>
                        <div style="color:#555;">Laboratory Analyst</div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($reviewer_sig)): ?>
                    <div>
                        <div style="font-size:0.78rem;color:#555;margin-bottom:4px;">Reviewed/Checked by:</div>
                        <div style="border-bottom:1px solid #999;padding-bottom:4px;margin-bottom:6px;min-height:36px;"></div>
                        <div><strong><?= htmlspecialchars($reviewer_sig) ?></strong></div>
                        <div style="color:#555;">Laboratory Analyst</div>
                    </div>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if (!empty($approver_sig)): ?>
                    <div>
                        <div style="font-size:0.78rem;color:#555;margin-bottom:4px;">Approved for Release by:</div>
                        <div style="border-bottom:1px solid #999;padding-bottom:4px;margin-bottom:6px;min-height:36px;"></div>
                        <div><strong><?= htmlspecialchars($approver_sig) ?></strong></div>
                        <div style="color:#555;">DOST RSTL-Davao Laboratory Head</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer note -->
            <div style="padding:8px 24px;border-top:1px solid #ccc;display:flex;justify-content:space-between;font-size:0.72rem;color:#555;">
                <span>** Approved Signatory</span>
                <span>RSTL XI –WI-015-F29 &nbsp;|&nbsp; Revision 0</span>
            </div>

            <!-- Address footer -->
            <div style="padding:10px 24px;border-top:2px solid #1a1a1a;background:#f8f8f8;font-size:0.72rem;color:#555;display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                <span>Address: Cor. Dumanlas and Friendship Rds., Buhangin, Davao City &nbsp;|&nbsp; Tel. No.: (082) 227-1313 loc. 213 &nbsp;|&nbsp; Fax No.: (082) 221-0428</span>
                <span>Website: http://region11.dost.gov.ph &nbsp;|&nbsp; Email: rstl.dost11@gmail.com</span>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
