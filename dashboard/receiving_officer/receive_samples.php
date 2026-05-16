<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_RECEIVING_OFFICER]);

$page_title = 'Laboratory Requests';
$page_heading = 'Laboratory Requests';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/receiving_officer/'], ['label' => 'Laboratory Requests']];
$user_id = $_SESSION['user_id'];
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id']);
    $action = sanitize($_POST['action']);
    
    if ($action === 'approve_with_samples') {
        // Get sample codes and descriptions from form
        $sample_codes = $_POST['sample_codes'] ?? [];
        $sample_descriptions = $_POST['sample_descriptions'] ?? [];
        
        // Build sample description text
        $sample_desc_lines = [];
        foreach ($sample_codes as $idx => $code) {
            if (!empty($code) && !empty($sample_descriptions[$idx])) {
                $sample_desc_lines[] = $code . ': ' . $sample_descriptions[$idx];
            }
        }
        $sample_description = implode("\n", $sample_desc_lines);
        
        // Update laboratory request with sample info and change status to pending_payment
        $stmt = $conn->prepare("UPDATE laboratory_requests SET sample_description = ?, status = 'pending_payment', received_by = ?, received_at = NOW() WHERE id = ?");
        $stmt->bind_param("sii", $sample_description, $user_id, $request_id);
        
        if ($stmt->execute()) {
            // Get business owner info — try full JOIN first, fall back to direct lookup
            $req = $conn->query("SELECT lr.business_owner_id, loi.company_name, lr.payment_testing_fee FROM laboratory_requests lr JOIN hdp_applications ha ON lr.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id WHERE lr.id = $request_id")->fetch_assoc();
            
            if (!$req) {
                // Fallback: get at least the business_owner_id and fee directly
                $req = $conn->query("SELECT business_owner_id, payment_testing_fee, '' AS company_name FROM laboratory_requests WHERE id = $request_id")->fetch_assoc();
            }
            
            // Notify business owner to pay (only if we found the record)
            if ($req && !empty($req['business_owner_id'])) {
                createNotification(
                    $conn,
                    $req['business_owner_id'],
                    'Laboratory Request Approved - Payment Required',
                    'Your laboratory request has been approved. Please proceed with payment of ' . formatCurrency($req['payment_testing_fee']) . ' to continue.',
                    'action_required',
                    BASE_URL . 'dashboard/business_owner/laboratory.php'
                );
            }
            
            logActivity($conn, $user_id, 'Lab Request Approved', 'Approved lab request #' . $request_id . ' - awaiting payment', 'laboratory');
            $success = 'Laboratory request approved with sample details. Business owner notified to proceed with payment.';
        } else {
            $error = 'Failed to approve request: ' . $conn->error;
        }
    } elseif ($action === 'reject') {
        $notes = sanitize($_POST['notes'] ?? '');
        $stmt = $conn->prepare("UPDATE laboratory_requests SET status = 'rejected', receiving_notes = ? WHERE id = ?");
        $stmt->bind_param("si", $notes, $request_id);
        
        if ($stmt->execute()) {
            // Notify business owner
            $req = $conn->query("SELECT business_owner_id FROM laboratory_requests WHERE id = $request_id")->fetch_assoc();
            createNotification(
                $conn,
                $req['business_owner_id'],
                'Laboratory Request Rejected',
                'Your laboratory request has been rejected. Reason: ' . $notes,
                'warning',
                BASE_URL . 'dashboard/business_owner/laboratory.php'
            );
            $success = 'Laboratory request rejected.';
        }
    }
}

$filter = $_GET['filter'] ?? 'submitted';
$where = $filter === 'all' ? "WHERE lr.status IN ('submitted', 'pending_payment', 'received')" : "WHERE lr.status = '$filter'";
$requests = $conn->query("SELECT lr.*, loi.company_name, u.full_name as owner_name FROM laboratory_requests lr JOIN hdp_applications ha ON lr.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id JOIN users u ON lr.business_owner_id = u.id $where ORDER BY lr.created_at DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= $error ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="display: flex; gap: 8px;">
        <a href="?filter=submitted" class="btn btn-sm <?= $filter === 'submitted' ? 'btn-primary' : 'btn-secondary' ?>">Pending Review</a>
        <a href="?filter=pending_payment" class="btn btn-sm <?= $filter === 'pending_payment' ? 'btn-primary' : 'btn-secondary' ?>">Awaiting Payment</a>
        <a href="?filter=received" class="btn btn-sm <?= $filter === 'received' ? 'btn-primary' : 'btn-secondary' ?>">Paid & Ready</a>
        <a href="?filter=all" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-secondary' ?>">All</a>
    </div>
</div>

<?php foreach ($requests as $lr): 
    // Parse analysis requirements
    $analysis_data = json_decode($lr['analysis_requirements'], true);
    $tests = $analysis_data['tests'] ?? [];
    $request_interpretation = $analysis_data['request_interpretation'] ?? 'no';
    $delivery_method = $analysis_data['delivery_method'] ?? 'pickup';
    $special_instructions = $analysis_data['special_instructions'] ?? '';
?>
<div class="card" style="margin-bottom: 16px;">
    <div class="card-header">
        <h3><i class="fas fa-flask" style="color:var(--primary-600);margin-right:8px;"></i> <?= htmlspecialchars($lr['company_name']) ?></h3>
        <?= getStatusBadge($lr['status']) ?>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px;">
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase;">Business Owner</label>
                <p style="font-size:0.9rem;"><?= htmlspecialchars($lr['owner_name']) ?></p>
            </div>
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase;">Testing Fee</label>
                <p style="font-size:0.9rem;font-weight:700;color:var(--primary-600);"><?= formatCurrency($lr['payment_testing_fee']) ?></p>
            </div>
            <div>
                <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase;">Payment Status</label>
                <p><?= getStatusBadge($lr['payment_status']) ?></p>
            </div>
        </div>
        
        <?php if (!empty($tests)): ?>
        <div style="margin-bottom:16px;">
            <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase;display:block;margin-bottom:8px;">Requested Tests</label>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;border:1px solid #e2e8f0;font-size:0.85rem;">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th style="border:1px solid #e2e8f0;padding:8px;text-align:left;">Test/Service</th>
                            <th style="border:1px solid #e2e8f0;padding:8px;text-align:center;">Qty</th>
                            <th style="border:1px solid #e2e8f0;padding:8px;text-align:right;">Unit Cost</th>
                            <th style="border:1px solid #e2e8f0;padding:8px;text-align:right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests as $test): ?>
                        <tr>
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
        
        <?php if ($lr['status'] === 'submitted'): ?>
        <!-- Sample Code and Description Form -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-bottom:16px;">
            <h4 style="font-weight:700;margin-bottom:12px;"><i class="fas fa-barcode"></i> Add Sample Details</h4>
            <form method="POST" id="sample-form-<?= $lr['id'] ?>">
                <input type="hidden" name="request_id" value="<?= $lr['id'] ?>">
                <input type="hidden" name="action" value="approve_with_samples">
                
                <table style="width:100%;border-collapse:collapse;border:1px solid #cbd5e1;font-size:0.85rem;margin-bottom:12px;">
                    <thead style="background:#fff;">
                        <tr>
                            <th style="border:1px solid #cbd5e1;padding:8px;text-align:left;width:20%;">Sample Code</th>
                            <th style="border:1px solid #cbd5e1;padding:8px;text-align:left;width:50%;">Sample Description / Remarks</th>
                            <th style="border:1px solid #cbd5e1;padding:8px;text-align:left;width:30%;">Test</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sampleNum = 1;
                        foreach ($tests as $test): 
                        ?>
                        <tr>
                            <td style="border:1px solid #cbd5e1;padding:8px;">
                                <input type="text" class="form-control" name="sample_codes[]" value="HVL-<?= str_pad($sampleNum, 5, '0', STR_PAD_LEFT) ?>" required style="font-size:0.85rem;">
                            </td>
                            <td style="border:1px solid #cbd5e1;padding:8px;">
                                <input type="text" class="form-control" name="sample_descriptions[]" placeholder="e.g., Hand in plastic container labelled 'BEEF STEAK, TIME 3:35PM', 300g" required style="font-size:0.85rem;">
                            </td>
                            <td style="border:1px solid #cbd5e1;padding:8px;font-size:0.85rem;">
                                <?= htmlspecialchars($test['test_name'] ?? '') ?>
                            </td>
                        </tr>
                        <?php 
                        $sampleNum++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
                
                <div style="display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" class="btn btn-danger btn-sm" data-modal="rejectModal<?= $lr['id'] ?>">
                        <i class="fas fa-times"></i> Reject
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Approve & Notify for Payment
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Reject Modal -->
        <div class="modal-overlay" id="rejectModal<?= $lr['id'] ?>">
            <div class="modal" style="max-width:500px">
                <div class="modal-header">
                    <h3>Reject Laboratory Request</h3>
                    <button class="modal-close"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="request_id" value="<?= $lr['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <div class="form-group">
                            <label>Reason for Rejection <span class="required">*</span></label>
                            <textarea name="notes" class="form-control" rows="3" required placeholder="Explain why this request is being rejected..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger btn-block">
                            <i class="fas fa-times"></i> Reject Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <?php elseif ($lr['status'] === 'pending_payment'): ?>
        <div class="alert alert-warning">
            <i class="fas fa-clock"></i>
            <span>Awaiting payment from business owner. Sample details have been recorded.</span>
        </div>
        
        <?php if (!empty($lr['sample_description'])): ?>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;">
            <label style="font-weight:600;color:var(--neutral-500);font-size:0.8rem;text-transform:uppercase;display:block;margin-bottom:8px;">Sample Details</label>
            <pre style="margin:0;white-space:pre-wrap;font-size:0.85rem;"><?= htmlspecialchars($lr['sample_description']) ?></pre>
        </div>
        <?php endif; ?>
        
        <?php elseif ($lr['status'] === 'received'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span>Payment received. Ready for laboratory analysis.</span>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php if (empty($requests)): ?>
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-inbox"></i></div>
            <h3>No Laboratory Requests</h3>
            <p>No laboratory requests matching this filter.</p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
