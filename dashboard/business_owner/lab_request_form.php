<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$page_title = 'Laboratory Request Form';
$page_heading = 'Laboratory Request Form';
$is_dashboard = true;
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/business_owner/'],
    ['label' => 'Laboratory', 'url' => BASE_URL . 'dashboard/business_owner/laboratory.php'],
    ['label' => 'Request Form']
];

$user_id = $_SESSION['user_id'];
$request_id = intval($_GET['id'] ?? 0);

// Fetch the laboratory request with company info
$stmt = $conn->prepare("
    SELECT lr.*, loi.company_name, loi.company_address, loi.contact_person, loi.contact_phone,
           u.full_name AS received_by_name
    FROM laboratory_requests lr
    JOIN hdp_applications ha ON lr.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    LEFT JOIN users u ON lr.received_by = u.id
    WHERE lr.id = ? AND lr.business_owner_id = ?
");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();

if (!$req) {
    $_SESSION['error'] = 'Laboratory request not found.';
    header('Location: ' . BASE_URL . 'dashboard/business_owner/laboratory.php');
    exit;
}

$analysis_data = json_decode($req['analysis_requirements'], true);
$tests = $analysis_data['tests'] ?? [];
$request_interpretation = $analysis_data['request_interpretation'] ?? 'no';
$delivery_method = $analysis_data['delivery_method'] ?? 'pickup';
$special_instructions = $analysis_data['special_instructions'] ?? '';

// Parse sample codes/descriptions
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

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card" style="margin-bottom:24px;">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <h3><i class="fas fa-file-alt" style="color:var(--primary-600);margin-right:8px;"></i> Laboratory Request Form</h3>
        <div style="display:flex;align-items:center;gap:10px;">
            <?= getStatusBadge($req['status']) ?>
            <?php if ($req['status'] === 'pending_payment'): ?>
            <a href="<?= BASE_URL ?>dashboard/business_owner/pay_laboratory.php?id=<?= $req['id'] ?>"
               class="btn btn-primary btn-sm">
                <i class="fas fa-credit-card"></i> Pay Testing Fee
            </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">

        <!-- Company Information -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-bottom:24px;">
            <div style="font-weight:700;font-size:0.85rem;text-transform:uppercase;color:var(--neutral-500);margin-bottom:14px;letter-spacing:0.05em;">
                Company Information
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);margin-bottom:3px;">Company Name</div>
                    <div style="font-weight:600;"><?= htmlspecialchars($req['company_name']) ?></div>
                </div>
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);margin-bottom:3px;">Contact Person</div>
                    <div style="font-weight:600;"><?= htmlspecialchars($req['contact_person'] ?? '—') ?></div>
                </div>
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);margin-bottom:3px;">Address</div>
                    <div style="font-weight:600;"><?= htmlspecialchars($req['company_address'] ?? '—') ?></div>
                </div>
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);margin-bottom:3px;">Contact Number</div>
                    <div style="font-weight:600;"><?= htmlspecialchars($req['contact_phone'] ?? '—') ?></div>
                </div>
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);margin-bottom:3px;">Date Submitted</div>
                    <div style="font-weight:600;"><?= formatDateTime($req['created_at']) ?></div>
                </div>
                <?php if (!empty($req['received_at'])): ?>
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);margin-bottom:3px;">Date Received</div>
                    <div style="font-weight:600;"><?= formatDateTime($req['received_at']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($req['received_by_name'])): ?>
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);margin-bottom:3px;">Received By</div>
                    <div style="font-weight:600;"><?= htmlspecialchars($req['received_by_name']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sample Details (filled by receiving officer) -->
        <?php if (!empty($sample_lines)): ?>
        <div style="margin-bottom:24px;">
            <div style="font-weight:700;font-size:0.85rem;text-transform:uppercase;color:var(--neutral-500);margin-bottom:10px;letter-spacing:0.05em;">
                <i class="fas fa-barcode" style="margin-right:6px;"></i> Sample Details
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;border:1px solid #e2e8f0;font-size:0.9rem;">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th style="border:1px solid #e2e8f0;padding:10px 14px;text-align:left;width:22%;">Sample Code</th>
                            <th style="border:1px solid #e2e8f0;padding:10px 14px;text-align:left;">Description / Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sample_lines as $sl): ?>
                        <tr>
                            <td style="border:1px solid #e2e8f0;padding:10px 14px;font-family:monospace;font-weight:700;color:var(--primary-600);">
                                <?= htmlspecialchars($sl['code']) ?>
                            </td>
                            <td style="border:1px solid #e2e8f0;padding:10px 14px;">
                                <?= htmlspecialchars($sl['desc']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php elseif ($req['status'] === 'submitted'): ?>
        <div class="alert alert-info" style="margin-bottom:24px;">
            <i class="fas fa-clock"></i>
            <span>Sample codes and descriptions will be assigned by the receiving officer upon review.</span>
        </div>
        <?php endif; ?>

        <!-- Tests / Services Requested -->
        <?php if (!empty($tests)): ?>
        <div style="margin-bottom:24px;">
            <div style="font-weight:700;font-size:0.85rem;text-transform:uppercase;color:var(--neutral-500);margin-bottom:10px;letter-spacing:0.05em;">
                <i class="fas fa-flask" style="margin-right:6px;"></i> Test / Calibration / Service Requested
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;border:1px solid #e2e8f0;font-size:0.9rem;">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th style="border:1px solid #e2e8f0;padding:10px 14px;text-align:left;">Test / Service</th>
                            <th style="border:1px solid #e2e8f0;padding:10px 14px;text-align:center;width:90px;">Qty</th>
                            <th style="border:1px solid #e2e8f0;padding:10px 14px;text-align:right;width:130px;">Unit Cost</th>
                            <th style="border:1px solid #e2e8f0;padding:10px 14px;text-align:right;width:130px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests as $t): ?>
                        <tr>
                            <td style="border:1px solid #e2e8f0;padding:10px 14px;"><?= htmlspecialchars($t['test_name'] ?? '') ?></td>
                            <td style="border:1px solid #e2e8f0;padding:10px 14px;text-align:center;"><?= htmlspecialchars($t['quantity'] ?? '1') ?></td>
                            <td style="border:1px solid #e2e8f0;padding:10px 14px;text-align:right;">₱<?= number_format(floatval($t['unit_cost'] ?? 0), 2) ?></td>
                            <td style="border:1px solid #e2e8f0;padding:10px 14px;text-align:right;">₱<?= number_format(floatval($t['total'] ?? 0), 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot style="background:#f8fafc;font-weight:700;">
                        <tr>
                            <td colspan="3" style="border:1px solid #e2e8f0;padding:10px 14px;text-align:right;">Grand Total:</td>
                            <td style="border:1px solid #e2e8f0;padding:10px 14px;text-align:right;color:var(--primary-600);">
                                ₱<?= number_format($req['payment_testing_fee'], 2) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Additional Options -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
            <div>
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Request for Interpretation</div>
                <div style="font-weight:600;">
                    <?php if ($request_interpretation === 'yes'): ?>
                        <span style="color:var(--success);"><i class="fas fa-check-circle"></i> Yes</span>
                    <?php else: ?>
                        <span style="color:var(--neutral-500);"><i class="fas fa-times-circle"></i> No</span>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Delivery of Results</div>
                <div style="font-weight:600;"><?= ucfirst(htmlspecialchars($delivery_method)) ?></div>
            </div>
            <?php if (!empty($special_instructions)): ?>
            <div style="grid-column:1/-1;">
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Special Instructions</div>
                <div style="background:var(--neutral-50);border-radius:8px;padding:12px 14px;color:var(--neutral-700);">
                    <?= nl2br(htmlspecialchars($special_instructions)) ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($req['receiving_notes'])): ?>
            <div style="grid-column:1/-1;">
                <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Receiving Officer Notes</div>
                <div style="background:var(--neutral-50);border-radius:8px;padding:12px 14px;color:var(--neutral-700);">
                    <?= nl2br(htmlspecialchars($req['receiving_notes'])) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer actions -->
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;padding-top:16px;border-top:1px solid #e2e8f0;">
            <a href="<?= BASE_URL ?>dashboard/business_owner/laboratory.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Laboratory
            </a>
            <?php if ($req['status'] === 'pending_payment'): ?>
            <a href="<?= BASE_URL ?>dashboard/business_owner/pay_laboratory.php?id=<?= $req['id'] ?>"
               class="btn btn-primary">
                <i class="fas fa-credit-card"></i> Proceed to Payment — ₱<?= number_format($req['payment_testing_fee'], 2) ?>
            </a>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
