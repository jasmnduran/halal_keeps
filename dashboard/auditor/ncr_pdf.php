<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_AUDITOR_TECHNICAL, ROLE_AUDITOR_SHARIAH, ROLE_BUSINESS_OWNER]);

$ncr_id = intval($_GET['id'] ?? 0);
if (!$ncr_id) { http_response_code(400); exit('Missing NCR ID.'); }

// Load NCR with related data
$stmt = $conn->prepare("
    SELECT n.*,
           loi.company_name, loi.company_address,
           u.full_name  AS auditor_name,
           ub.full_name AS owner_name
    FROM ncr_reports n
    JOIN hdp_applications ha  ON n.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    LEFT JOIN users u  ON n.prepared_by        = u.id
    LEFT JOIN users ub ON ha.business_owner_id = ub.id
    WHERE n.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $ncr_id);
$stmt->execute();
$ncr = $stmt->get_result()->fetch_assoc();

if (!$ncr) { http_response_code(404); exit('NCR not found.'); }

// Business owners may only download their own NCRs
if ($_SESSION['role_id'] == ROLE_BUSINESS_OWNER) {
    $own = $conn->prepare("SELECT id FROM hdp_applications WHERE id = ? AND business_owner_id = ?");
    $own->bind_param("ii", $ncr['application_id'], $_SESSION['user_id']);
    $own->execute();
    if (!$own->get_result()->fetch_assoc()) { http_response_code(403); exit('Access denied.'); }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmtDate($d) {
    if (!$d) return '—';
    $ts = strtotime($d);
    return $ts ? date('F j, Y', $ts) : $d;
}
function fmtTime($t) {
    if (!$t) return '—';
    $ts = strtotime($t);
    return $ts ? date('h:i A', $ts) : $t;
}
function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nl($s)  { return nl2br(esc((string)$s)); }

$cat_labels = ['minor'=>'Minor','major'=>'Major','serious'=>'Serious','observation'=>'Observation'];
$cat_colors = ['minor'=>'#b45309','major'=>'#c2410c','serious'=>'#be123c','observation'=>'#1d4ed8'];
$cat_color  = $cat_colors[$ncr['category']] ?? '#374151';
$cat_label  = $cat_labels[$ncr['category']] ?? ucfirst($ncr['category']);

// ── Build HTML for mPDF ───────────────────────────────────────────────────────
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 10pt; color: #1e293b; }

  .header-bar {
    background: #1e3a5f;
    color: #fff;
    padding: 10px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0;
  }
  .header-bar .title { font-size: 13pt; font-weight: bold; letter-spacing: 0.04em; }
  .header-bar .sub   { font-size: 8pt; opacity: 0.8; margin-top: 2px; }
  .header-bar .ncr-num { font-size: 12pt; font-weight: bold; text-align: right; }
  .cat-badge {
    display: inline-block;
    background: <?= $cat_color ?>;
    color: #fff;
    padding: 2px 10px;
    border-radius: 99px;
    font-size: 7.5pt;
    font-weight: bold;
    text-transform: uppercase;
    margin-top: 4px;
  }

  table.info-grid {
    width: 100%;
    border-collapse: collapse;
    font-size: 9pt;
    border: 1px solid #cbd5e1;
  }
  table.info-grid td {
    padding: 7px 10px;
    border: 1px solid #cbd5e1;
    vertical-align: middle;
  }
  table.info-grid td.lbl {
    background: #f8fafc;
    font-weight: bold;
    font-size: 8pt;
    text-transform: uppercase;
    white-space: nowrap;
    width: 16%;
  }

  .section {
    border: 1px solid #cbd5e1;
    border-top: none;
    padding: 10px 14px;
    min-height: 50px;
  }
  .section-title {
    font-weight: bold;
    text-decoration: underline;
    margin-bottom: 6px;
    font-size: 9.5pt;
  }
  .section-body {
    font-size: 9pt;
    line-height: 1.6;
    color: #374151;
    white-space: pre-wrap;
  }

  .ncr-level-row {
    border: 1px solid #cbd5e1;
    border-top: none;
    padding: 8px 14px;
    font-size: 9pt;
  }
  .checkbox {
    display: inline-block;
    width: 11px; height: 11px;
    border: 1.5px solid #374151;
    margin-right: 4px;
    vertical-align: middle;
    text-align: center;
    line-height: 10px;
    font-size: 8pt;
  }

  .dates-row {
    border: 1px solid #cbd5e1;
    border-top: none;
    display: table;
    width: 100%;
  }
  .dates-cell {
    display: table-cell;
    width: 50%;
    padding: 10px 14px;
    border-right: 1px solid #cbd5e1;
    font-size: 9pt;
  }
  .dates-cell:last-child { border-right: none; }
  .dates-cell .lbl { font-weight: bold; font-size: 8pt; margin-bottom: 3px; }

  .sig-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #cbd5e1;
    border-top: none;
  }
  .sig-table td {
    border: 1px solid #cbd5e1;
    padding: 10px 14px 36px;
    text-align: center;
    font-weight: bold;
    font-size: 9pt;
    width: 33.33%;
  }
  .sig-name {
    font-size: 8.5pt;
    color: #64748b;
    font-weight: normal;
    padding-top: 4px;
  }

  .lab-row {
    border: 1px solid #cbd5e1;
    border-top: none;
    padding: 8px 14px;
    font-size: 9pt;
    background: #fdf4ff;
  }
  .footer-note {
    margin-top: 14px;
    font-size: 7.5pt;
    color: #94a3b8;
    text-align: center;
  }
</style>
</head>
<body>

<!-- Header -->
<div class="header-bar">
  <div>
    <div class="title">NON-CONFORMANCE REPORT</div>
    <div class="sub">Halal Keeps</div>
  </div>
  <div style="text-align:right;">
    <div class="ncr-num"><?= esc($ncr['ncr_number']) ?></div>
    <div><span class="cat-badge"><?= esc($cat_label) ?></span></div>
  </div>
</div>

<!-- Info grid -->
<table class="info-grid">
  <tr>
    <td class="lbl">Company</td>
    <td colspan="3"><?= esc($ncr['company_name']) ?></td>
  </tr>
  <tr>
    <td class="lbl">Date</td>
    <td><?= fmtDate($ncr['created_at']) ?></td>
    <td class="lbl">Location</td>
    <td><?= esc($ncr['location'] ?? $ncr['company_address'] ?? '—') ?></td>
  </tr>
  <tr>
    <td class="lbl">Time</td>
    <td><?= fmtTime($ncr['time_of_inspection']) ?></td>
    <td class="lbl">Auditor</td>
    <td><?= esc($ncr['auditor_name'] ?? '—') ?></td>
  </tr>
  <tr>
    <td class="lbl">Person in Charge</td>
    <td colspan="3"><?= esc($ncr['person_in_charge'] ?? '—') ?></td>
  </tr>
  <tr>
    <td class="lbl">Document Reference</td>
    <td colspan="3"><?= esc($ncr['document_reference'] ?? 'HAS/MANUAL/IHA-CHKLIST/01-2016') ?></td>
  </tr>
</table>

<!-- NCR Level checkboxes -->
<div class="ncr-level-row">
  <strong>NCR Level:</strong>
  <?php foreach (['minor'=>'Minor','major'=>'Major','serious'=>'Serious','observation'=>'Observation'] as $v => $l): ?>
  &nbsp;&nbsp;
  <span class="checkbox"><?= $ncr['category'] === $v ? '&#10003;' : '' ?></span><?= $l ?>
  <?php endforeach; ?>
</div>

<!-- Brief Summary -->
<div class="section">
  <div class="section-title">Brief Summary (Please Specify)</div>
  <div class="section-body"><?= nl($ncr['brief_summary'] ?? $ncr['finding_details'] ?? '') ?></div>
</div>

<!-- CARs -->
<div class="section">
  <div class="section-title">CARs — Corrective Action Requests (Please Specify)</div>
  <div class="section-body"><?= nl($ncr['finding_details'] ?? '') ?></div>
</div>

<!-- Corrective Action Required -->
<div class="section">
  <div class="section-title">Corrective Action Required</div>
  <div class="section-body"><?= nl($ncr['corrective_action_required'] ?? '') ?></div>
</div>

<!-- Recommendation -->
<div class="section">
  <div class="section-title">Recommendation</div>
  <div class="section-body"><?= nl($ncr['recommendation'] ?? '') ?></div>
</div>

<!-- Dates -->
<div class="dates-row">
  <div class="dates-cell">
    <div class="lbl">Due Date to be Implemented</div>
    <div><?= fmtDate($ncr['deadline']) ?></div>
  </div>
  <div class="dates-cell">
    <div class="lbl">Verification / Follow Up Audit</div>
    <div><?= fmtDate($ncr['followup_date']) ?></div>
  </div>
</div>

<?php if (!empty($ncr['auditor_type']) && $ncr['auditor_type'] === 'shariah'): ?>
<!-- Lab test row (Shariah only) -->
<div class="lab-row">
  <strong>Laboratory Test Required:</strong>
  <?= $ncr['requires_lab_test'] ? '<strong style="color:#7e22ce;">&#10003; Yes</strong>' : 'No' ?>
</div>
<?php endif; ?>

<!-- Signatures -->
<table class="sig-table">
  <tr>
    <td>
      Auditor
      <div class="sig-name"><?= esc($ncr['auditor_name'] ?? '—') ?></div>
    </td>
    <td>
      Auditee
      <div class="sig-name"><?= esc($ncr['owner_name'] ?? '—') ?></div>
    </td>
    <td>Verified and for Compliances</td>
  </tr>
</table>

<div class="footer-note">
  Generated by <?= esc(SITE_NAME) ?> &nbsp;·&nbsp; <?= date('F j, Y \a\t h:i A') ?>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

// ── Render with mPDF ──────────────────────────────────────────────────────────
require_once __DIR__ . '/../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf([
    'margin_top'    => 12,
    'margin_bottom' => 12,
    'margin_left'   => 14,
    'margin_right'  => 14,
    'format'        => 'A4',
    'tempDir'       => __DIR__ . '/../../tmp/mpdf',
]);

$mpdf->SetTitle('NCR ' . $ncr['ncr_number']);
$mpdf->SetAuthor(SITE_NAME);
$mpdf->WriteHTML($html);

$filename = 'NCR_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $ncr['ncr_number']) . '.pdf';
$mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
