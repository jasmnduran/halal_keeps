<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$page_title = 'Inspection Schedules';
$page_heading = 'Inspection Schedules';
$is_dashboard = true;
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/business_owner/'],
    ['label' => 'Inspection Schedules'],
];
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_inspection'])) {
    $schedule_id = intval($_POST['schedule_id']);

    $check = $conn->prepare("
        SELECT s.id, s.inspection_fees, s.evaluator_id, loi.company_name
        FROM inspection_schedules s
        JOIN hdp_applications ha ON s.application_id = ha.id
        JOIN letter_of_intent loi ON ha.loi_id = loi.id
        WHERE s.id = ? AND ha.business_owner_id = ?
        LIMIT 1
    ");
    $check->bind_param("ii", $schedule_id, $user_id);
    $check->execute();
    $schedule_for_payment = $check->get_result()->fetch_assoc();

    if (!$schedule_for_payment) {
        $error = 'Inspection schedule not found.';
    } else {
        $existing = $conn->prepare("SELECT id, status, receipt_path FROM payments WHERE user_id = ? AND reference_type = 'inspection' AND reference_id = ? ORDER BY created_at DESC LIMIT 1");
        $existing->bind_param("ii", $user_id, $schedule_id);
        $existing->execute();
        $existing_payment = $existing->get_result()->fetch_assoc();

        if (($existing_payment['status'] ?? '') === 'verified') {
            $error = 'This inspection payment has already been verified.';
        }
    }

    if (!$error) {
        $amount = floatval($schedule_for_payment['inspection_fees']);
        $success_url = 'http://' . $_SERVER['HTTP_HOST'] . BASE_URL . 'dashboard/business_owner/inspection_schedules.php?paymongo_success=1&schedule_id=' . $schedule_id;
        $cancel_url = 'http://' . $_SERVER['HTTP_HOST'] . BASE_URL . 'dashboard/business_owner/inspection_schedules.php?paymongo_cancelled=1&schedule_id=' . $schedule_id;
        $checkout = createPayMongoCheckoutSession(
            $amount,
            'Inspection Fee - ' . $schedule_for_payment['company_name'],
            $success_url,
            $cancel_url,
            [
                'reference_type' => 'inspection',
                'schedule_id' => (string)$schedule_id,
                'business_owner_id' => (string)$user_id,
            ]
        );

        if (!$checkout['success']) {
            $error = $checkout['message'];
        } else {
            $checkout_data = $checkout['data']['data'] ?? [];
            $checkout_id = $checkout_data['id'] ?? '';
            $checkout_url = $checkout_data['attributes']['checkout_url'] ?? '';

            if (empty($checkout_id) || empty($checkout_url)) {
                $error = 'PayMongo did not return a checkout URL.';
            } else {
                if (!empty($existing_payment['id'])) {
                    $raw_response = json_encode($checkout['data']);
                    $upd = $conn->prepare("UPDATE payments SET amount = ?, payment_method = 'paymongo', transaction_reference = ?, paymongo_checkout_url = ?, paymongo_raw_response = ?, status = 'pending', paid_at = NULL WHERE id = ?");
                    $upd->bind_param("dsssi", $amount, $checkout_id, $checkout_url, $raw_response, $existing_payment['id']);
                    $saved = $upd->execute();
                } else {
                    $raw_response = json_encode($checkout['data']);
                    $ins = $conn->prepare("INSERT INTO payments (user_id, reference_type, reference_id, amount, payment_method, transaction_reference, paymongo_checkout_url, paymongo_raw_response, status, paid_at) VALUES (?, 'inspection', ?, ?, 'paymongo', ?, ?, ?, 'pending', NULL)");
                    $ins->bind_param("iidsss", $user_id, $schedule_id, $amount, $checkout_id, $checkout_url, $raw_response);
                    $saved = $ins->execute();
                }

                if ($saved) {
                    header('Location: ' . $checkout_url);
                    exit();
                }
                $error = 'Failed to save PayMongo checkout session.';
            }
        }
    }
}

if (isset($_GET['paymongo_success'], $_GET['schedule_id'])) {
    $schedule_id = intval($_GET['schedule_id']);
    $payment_q = $conn->prepare("
        SELECT p.*, s.evaluator_id, loi.company_name
        FROM payments p
        JOIN inspection_schedules s ON p.reference_id = s.id
        JOIN hdp_applications ha ON s.application_id = ha.id
        JOIN letter_of_intent loi ON ha.loi_id = loi.id
        WHERE p.user_id = ?
          AND p.reference_type = 'inspection'
          AND p.reference_id = ?
          AND ha.business_owner_id = ?
        ORDER BY p.created_at DESC
        LIMIT 1
    ");
    $payment_q->bind_param("iii", $user_id, $schedule_id, $user_id);
    $payment_q->execute();
    $payment = $payment_q->get_result()->fetch_assoc();

    if ($payment && !empty($payment['transaction_reference'])) {
        $checkout = retrievePayMongoCheckoutSession($payment['transaction_reference']);
        if ($checkout['success'] && isPayMongoCheckoutPaid($checkout['data'])) {
            $upd = $conn->prepare("UPDATE payments SET status = 'verified', paid_at = NOW() WHERE id = ?");
            $upd->bind_param("i", $payment['id']);
            $upd->execute();

            // Notify the evaluator
            createNotification(
                $conn,
                $payment['evaluator_id'],
                'Inspection Payment Verified',
                $_SESSION['full_name'] . ' completed PayMongo payment for the inspection schedule of ' . $payment['company_name'] . '.',
                'success',
                BASE_URL . 'dashboard/evaluator/inspection_schedules.php'
            );

            // Notify the assigned auditors from the TOR so they know they can now start
            $tor_q = $conn->prepare("
                SELECT t.auditor_assignments
                FROM terms_of_reference t
                JOIN inspection_schedules s ON s.tor_id = t.id OR t.application_id = s.application_id
                WHERE s.id = ?
                ORDER BY t.created_at DESC
                LIMIT 1
            ");
            $tor_q->bind_param("i", $schedule_id);
            $tor_q->execute();
            $tor_row = $tor_q->get_result()->fetch_assoc();
            $auditor_assignments = json_decode($tor_row['auditor_assignments'] ?? '[]', true);
            if (is_array($auditor_assignments)) {
                $notified_ids = [];
                foreach ($auditor_assignments as $assignment) {
                    $auditor_id = intval($assignment['auditor_id'] ?? 0);
                    if ($auditor_id > 0 && !in_array($auditor_id, $notified_ids, true)) {
                        createNotification(
                            $conn,
                            $auditor_id,
                            'Inspection Payment Confirmed — Ready to Start',
                            $payment['company_name'] . ' has settled the inspection fee. You may now proceed with the on-site inspection.',
                            'action_required',
                            BASE_URL . 'dashboard/auditor/inspections.php'
                        );
                        $notified_ids[] = $auditor_id;
                    }
                }
            }

            $success = 'PayMongo payment completed and verified. The assigned auditors have been notified.';
        } else {
            $success = 'PayMongo checkout completed. Payment verification is still pending.';
        }
    } else {
        $error = 'Unable to find the PayMongo payment record for this schedule.';
    }
}

if (isset($_GET['paymongo_cancelled'])) {
    $error = 'PayMongo checkout was cancelled. You can try again anytime.';
}

$stmt = $conn->prepare("
    SELECT
        s.*,
        loi.company_name,
        loi.company_address,
        ha.status AS application_status,
        ha.enterprise_type AS application_enterprise_type,
        t.id AS tor_id,
        t.business_response,
        t.enterprise_type AS tor_enterprise_type,
        t.auditor_assignments,
        t.auditor_total_amount,
        t.inspection_fees AS tor_inspection_fees,
        p.id AS payment_id,
        p.status AS payment_status,
        p.payment_method,
        p.transaction_reference,
        p.receipt_path,
        p.paid_at,
        u.full_name AS evaluator_name,
        u.email AS evaluator_email
    FROM inspection_schedules s
    JOIN hdp_applications ha ON s.application_id = ha.id
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    JOIN users u ON s.evaluator_id = u.id
    LEFT JOIN terms_of_reference t ON s.tor_id = t.id OR t.application_id = ha.id
    LEFT JOIN payments p ON p.id = (
        SELECT p2.id
        FROM payments p2
        WHERE p2.reference_type = 'inspection'
          AND p2.reference_id = s.id
          AND p2.user_id = ?
        ORDER BY p2.created_at DESC
        LIMIT 1
    )
    WHERE ha.business_owner_id = ?
    ORDER BY s.schedule_date DESC, s.schedule_time DESC
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= $error ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<?php if (empty($schedules)): ?>
<div class="empty-state">
    <div class="empty-icon"><i class="fas fa-calendar-alt"></i></div>
    <h3>No Inspection Schedule Yet</h3>
    <p>Your inspection schedule will appear here after the evaluator schedules it.</p>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:20px;">
    <?php foreach ($schedules as $schedule): ?>
    <?php
        $enterprise_type = normalizeEnterpriseType($schedule['tor_enterprise_type'] ?? $schedule['application_enterprise_type'] ?? '');
        $auditor_assignments = json_decode($schedule['auditor_assignments'] ?? '[]', true);
        if (!is_array($auditor_assignments)) $auditor_assignments = [];
        $schedule_datetime = trim(formatDate($schedule['schedule_date']) . ' ' . (!empty($schedule['schedule_time']) ? date('h:i A', strtotime($schedule['schedule_time'])) : ''));
    ?>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-calendar-check" style="color:var(--primary-600);margin-right:8px;"></i>
                <?= htmlspecialchars($schedule['company_name']) ?>
            </h3>
            <?= getStatusBadge($schedule['status']) ?>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-bottom:22px;">
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Inspection Date</div>
                    <div style="font-weight:700;font-size:1.05rem;"><?= htmlspecialchars($schedule_datetime) ?></div>
                </div>
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Inspection Fee</div>
                    <div style="font-weight:700;font-size:1.05rem;"><?= formatCurrency($schedule['inspection_fees']) ?></div>
                </div>
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Application Status</div>
                    <div><?= getStatusBadge($schedule['application_status']) ?></div>
                </div>
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">TOR Response</div>
                    <?php $resp = $schedule['business_response'] ?? 'pending'; ?>
                    <span class="badge <?= $resp === 'accepted' ? 'badge-success' : ($resp === 'rejected' ? 'badge-danger' : 'badge-warning') ?>">
                        <?= ucfirst($resp) ?>
                    </span>
                </div>
                <div>
                    <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;">Payment</div>
                    <?= getStatusBadge($schedule['payment_status'] ?? 'pending') ?>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:22px;align-items:start;">
                <div>
                    <div style="margin-bottom:16px;">
                        <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Location</div>
                        <div style="background:var(--neutral-50);border-radius:8px;padding:12px 14px;color:var(--neutral-700);">
                            <?= nl2br(htmlspecialchars($schedule['location'] ?: $schedule['company_address'] ?: 'To be confirmed')) ?>
                        </div>
                    </div>

                    <div style="margin-bottom:16px;">
                        <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Evaluator</div>
                        <div style="color:var(--neutral-700);">
                            <?= htmlspecialchars($schedule['evaluator_name']) ?>
                            <?php if (!empty($schedule['evaluator_email'])): ?>
                            <br><small style="color:var(--neutral-500);"><?= htmlspecialchars($schedule['evaluator_email']) ?></small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Notes</div>
                        <div style="background:var(--neutral-50);border-radius:8px;padding:12px 14px;color:var(--neutral-700);min-height:48px;">
                            <?= nl2br(htmlspecialchars($schedule['notes'] ?: 'No additional notes.')) ?>
                        </div>
                    </div>
                </div>

                <div>
                    <div style="margin-bottom:16px;">
                        <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Enterprise Standard</div>
                        <div style="background:var(--neutral-50);border-radius:8px;padding:12px 14px;color:var(--neutral-700);">
                            <?= htmlspecialchars(getEnterpriseStandardSummary($enterprise_type)) ?>
                        </div>
                    </div>

                    <div style="margin-bottom:16px;">
                        <div style="font-size:0.8rem;color:var(--neutral-500);text-transform:uppercase;font-weight:600;margin-bottom:6px;">Assigned Inspectors</div>
                        <?php if (!empty($auditor_assignments)): ?>
                            <div style="display:flex;flex-direction:column;gap:8px;">
                            <?php foreach ($auditor_assignments as $assignment): ?>
                                <div style="border:1px solid var(--neutral-100);border-radius:8px;padding:10px 12px;">
                                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                                        <div>
                                            <div style="font-weight:600;color:var(--neutral-700);">
                                                <?= htmlspecialchars($assignment['auditor_name'] ?: 'To be assigned') ?>
                                            </div>
                                            <div style="font-size:0.8rem;color:var(--neutral-500);">
                                                <?= ucfirst(htmlspecialchars($assignment['role'] ?? 'auditor')) ?> auditor
                                            </div>
                                        </div>
                                        <div style="font-weight:700;color:var(--neutral-700);white-space:nowrap;">
                                            <?= formatCurrency($assignment['amount'] ?? 0) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="background:var(--neutral-50);border-radius:8px;padding:12px 14px;color:var(--neutral-600);">
                                <?= htmlspecialchars($schedule['inspectors'] ?: 'Inspectors will be announced by the evaluator.') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($schedule['tor_id'])): ?>
                    <a href="<?= BASE_URL ?>dashboard/business_owner/terms_of_reference.php" class="btn btn-sm btn-outline" style="width:100%;text-align:center;">
                        <i class="fas fa-file-contract"></i> View Terms of Reference
                    </a>
                    <?php endif; ?>

                    <div style="margin-top:12px;">
                        <?php if (($schedule['payment_status'] ?? '') === 'verified'): ?>
                        <div class="alert alert-success" style="margin:0;">
                            <i class="fas fa-check-circle"></i>
                            <span>PayMongo payment verified<?= !empty($schedule['paid_at']) ? ' on ' . formatDate($schedule['paid_at']) : '' ?>.</span>
                        </div>
                        <?php else: ?>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="pay_inspection" value="1">
                            <input type="hidden" name="schedule_id" value="<?= intval($schedule['id']) ?>">
                            <button type="submit" class="btn btn-primary" style="width:100%;text-align:center;">
                                <i class="fas fa-credit-card"></i>
                                <?= !empty($schedule['payment_id']) ? 'Resume PayMongo Checkout' : 'Pay Inspection Fee' ?>
                            </button>
                        </form>
                        <?php if (($schedule['payment_status'] ?? '') === 'pending' && !empty($schedule['payment_id'])): ?>
                        <p class="form-text" style="margin-top:8px;">A PayMongo checkout session was created. Complete checkout to verify payment.</p>
                        <?php elseif (($schedule['payment_status'] ?? '') === 'rejected'): ?>
                        <p class="form-text" style="margin-top:8px;color:var(--danger);">Payment was rejected. Start a new PayMongo checkout to try again.</p>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
