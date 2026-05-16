<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_AUDITOR_TECHNICAL, ROLE_AUDITOR_SHARIAH]);

$page_title = 'Audit Findings';
$page_heading = 'Audit Findings';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/auditor/'], ['label' => 'Audit Findings']];

$user_id     = $_SESSION['user_id'];
$role_id     = $_SESSION['role_id'];
$is_technical = ($role_id == ROLE_AUDITOR_TECHNICAL);

// Determine which columns belong to this auditor
$auditor_field   = $is_technical ? 'auditor_technical_id'    : 'auditor_shariah_id';
$findings_field  = $is_technical ? 'tech_audit_findings'     : 'shariah_audit_findings';
$conform_field   = $is_technical ? 'tech_conformity_status'  : 'shariah_conformity_status';
$remarks_field   = $is_technical ? 'tech_remarks'            : 'shariah_remarks';
$done_field      = $is_technical ? 'tech_completed_at'       : 'shariah_completed_at';
$auditor_label   = $is_technical ? 'Technical'               : 'Shariah';

// Fetch all inspections where this auditor has already submitted their findings
// (done_field IS NOT NULL), regardless of whether the other auditor is done yet.
$stmt = $conn->prepare("
    SELECT i.*,
           loi.company_name,
           s.schedule_date,
           ut.full_name AS tech_name,
           us.full_name AS shariah_name
    FROM inspections i
    JOIN hdp_applications ha  ON i.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    JOIN inspection_schedules s ON i.schedule_id = s.id
    LEFT JOIN users ut ON i.auditor_technical_id = ut.id
    LEFT JOIN users us ON i.auditor_shariah_id   = us.id
    WHERE i.$auditor_field = ?
      AND i.$done_field IS NOT NULL
    ORDER BY i.$done_field DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$inspections = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-clipboard" style="color: var(--primary-600); margin-right: 8px;"></i>
            My Submitted Findings (<?= $auditor_label ?> Auditor)
        </h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($inspections)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-clipboard"></i></div>
                <h3>No Submitted Findings Yet</h3>
                <p>Your findings will appear here once you submit them from the Inspections page.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Inspection Date</th>
                            <th>Submitted On</th>
                            <th>My Conformity</th>
                            <th>Overall Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inspections as $i): ?>
                        <?php
                            $my_findings  = $i[$findings_field] ?? '';
                            $my_conform   = $i[$conform_field]  ?? '';
                            $my_remarks   = $i[$remarks_field]  ?? '';
                            $submitted_on = $i[$done_field]     ?? $i['created_at'];
                            $overall_done = ($i['status'] === 'completed');
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($i['company_name']) ?></strong></td>
                            <td><?= formatDate($i['inspection_date'] ?? $i['created_at']) ?></td>
                            <td><?= formatDate($submitted_on) ?></td>
                            <td><?= getStatusBadge($my_conform ?: 'pending') ?></td>
                            <td>
                                <?php if ($overall_done): ?>
                                    <?= getStatusBadge($i['conformity_status']) ?>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock" style="margin-right:4px;"></i>
                                        Awaiting other auditor
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline" data-modal="findingModal<?= $i['id'] ?>">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Modal: per-auditor findings -->
                        <div class="modal-overlay" id="findingModal<?= $i['id'] ?>">
                            <div class="modal" style="max-width: 700px;">
                                <div class="modal-header">
                                    <h3>My Findings: <?= htmlspecialchars($i['company_name']) ?></h3>
                                    <button class="modal-close"><i class="fas fa-times"></i></button>
                                </div>
                                <div class="modal-body">

                                    <!-- This auditor's own submission -->
                                    <?php
                                        $nc_stmt = $conn->prepare("
                                            SELECT requirement_label, remarks
                                            FROM inspection_document_conformity
                                            WHERE inspection_id = ? AND auditor_id = ? AND conformity_status = 'non_conforming'
                                            ORDER BY requirement_label ASC
                                        ");
                                        $nc_stmt->bind_param("ii", $i['id'], $user_id);
                                        $nc_stmt->execute();
                                        $nc_docs = $nc_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                    ?>
                                    <div style="background:var(--primary-50);border:1px solid var(--primary-100);border-radius:10px;padding:16px;margin-bottom:20px;">
                                        <div style="font-weight:700;color:var(--primary-800);margin-bottom:12px;">
                                            <i class="fas fa-user-check" style="margin-right:6px;"></i>
                                            My Submission (<?= $auditor_label ?> Auditor)
                                        </div>
                                        <div style="margin-bottom:12px;">
                                            <strong>Audit Findings:</strong>
                                            <div style="background:#fff;padding:12px;border-radius:8px;margin-top:6px;border:1px solid var(--neutral-200);">
                                                <?= $my_findings !== '' ? nl2br(htmlspecialchars($my_findings)) : '<em style="color:var(--neutral-400);">No findings recorded.</em>' ?>
                                            </div>
                                        </div>
                                        <div style="margin-bottom:12px;">
                                            <strong>Conformity:</strong>
                                            <span style="margin-left:8px;"><?= getStatusBadge($my_conform ?: 'pending') ?></span>
                                        </div>
                                        <div style="margin-bottom:<?= !empty($nc_docs) ? '16px' : '0' ?>;">
                                            <strong>Remarks:</strong>
                                            <div style="background:#fff;padding:12px;border-radius:8px;margin-top:6px;border:1px solid var(--neutral-200);">
                                                <?= $my_remarks !== '' ? nl2br(htmlspecialchars($my_remarks)) : '<em style="color:var(--neutral-400);">No remarks.</em>' ?>
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

                                    <!-- Combined result (only visible once both auditors are done) -->
                                    <?php if ($overall_done): ?>
                                    <div style="background:var(--neutral-50);border:1px solid var(--neutral-200);border-radius:10px;padding:16px;">
                                        <div style="font-weight:700;color:var(--neutral-700);margin-bottom:12px;">
                                            <i class="fas fa-clipboard-check" style="margin-right:6px;"></i>
                                            Combined Inspection Result
                                        </div>
                                        <div style="margin-bottom:12px;">
                                            <strong>Overall Conformity:</strong>
                                            <span style="margin-left:8px;"><?= getStatusBadge($i['conformity_status']) ?></span>
                                        </div>
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                                            <div>
                                                <div style="font-size:0.82rem;font-weight:600;color:var(--neutral-500);margin-bottom:6px;">Technical Auditor</div>
                                                <div><?= htmlspecialchars($i['tech_name'] ?? 'N/A') ?></div>
                                                <div style="margin-top:4px;"><?= getStatusBadge($i['tech_conformity_status'] ?? 'pending') ?></div>
                                            </div>
                                            <div>
                                                <div style="font-size:0.82rem;font-weight:600;color:var(--neutral-500);margin-bottom:6px;">Shariah Auditor</div>
                                                <div><?= htmlspecialchars($i['shariah_name'] ?? 'N/A') ?></div>
                                                <div style="margin-top:4px;"><?= getStatusBadge($i['shariah_conformity_status'] ?? 'pending') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div style="background:var(--warning-50,#fffbeb);border:1px solid var(--warning-200,#fde68a);border-radius:10px;padding:14px;text-align:center;color:var(--warning-700,#92400e);">
                                        <i class="fas fa-hourglass-half" style="margin-right:6px;"></i>
                                        The combined result will be available once the other auditor submits their findings.
                                    </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
