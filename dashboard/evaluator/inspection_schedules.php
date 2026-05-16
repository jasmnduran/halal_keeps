<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_EVALUATOR]);

$page_title = 'Inspection Schedules';
$page_heading = 'Inspection Schedules';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Dashboard', 'url' => BASE_URL . 'dashboard/evaluator/'], ['label' => 'Inspection Schedules']];
$user_id = $_SESSION['user_id'];
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $app_id = intval($_POST['application_id']);
    $schedule_date = sanitize($_POST['schedule_date']);
    $schedule_time = sanitize($_POST['schedule_time'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $inspectors = sanitize($_POST['inspectors'] ?? '');
    $fees = floatval($_POST['inspection_fees'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');

    $stmt = $conn->prepare("INSERT INTO inspection_schedules (application_id, evaluator_id, schedule_date, schedule_time, location, inspectors, inspection_fees, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')");
    $stmt->bind_param("iissssds", $app_id, $user_id, $schedule_date, $schedule_time, $location, $inspectors, $fees, $notes);
    if ($stmt->execute()) {
        // Notify the business owner so they can settle the inspection fee.
        // Auditors are NOT notified yet — they will be notified after payment is confirmed.
        $owner_q = $conn->prepare("SELECT business_owner_id FROM hdp_applications WHERE id = ?");
        $owner_q->bind_param("i", $app_id);
        $owner_q->execute();
        $owner = $owner_q->get_result()->fetch_assoc();
        if ($owner) {
            $fee_fmt = number_format($fees, 2);
            createNotification(
                $conn,
                $owner['business_owner_id'],
                'Inspection Scheduled — Payment Required',
                'An on-site inspection has been scheduled for ' . $schedule_date .
                    ($schedule_time ? ' at ' . $schedule_time : '') .
                    '. Please settle the inspection fee of ₱' . $fee_fmt .
                    ' before the inspection date to confirm your slot.',
                'action_required',
                BASE_URL . 'dashboard/business_owner/inspection_schedules.php'
            );
        }
        logActivity($conn, $user_id, 'Inspection Scheduled', 'Inspection scheduled for App #' . $app_id . ' on ' . $schedule_date, 'evaluation');
        $success = 'Inspection scheduled successfully! The business owner has been notified to settle the inspection fee.';
    }
}

// Get applications with verified/approved status.
// Also pull the accepted (or latest) TOR inspection fee for each app.
$apps_raw = $conn->query("
    SELECT ha.id, ha.status, loi.company_name, loi.company_address,
        (SELECT t.auditor_total_amount
         FROM terms_of_reference t
         WHERE t.application_id = ha.id
           AND t.business_response = 'accepted'
         ORDER BY t.created_at DESC LIMIT 1) AS tor_fees
    FROM hdp_applications ha
    JOIN letter_of_intent loi ON ha.loi_id = loi.id
    WHERE ha.status IN ('verified', 'approved')
    ORDER BY ha.updated_at DESC
")->fetch_all(MYSQLI_ASSOC);

$schedules = $conn->query("SELECT s.*, loi.company_name, u.full_name as evaluator_name FROM inspection_schedules s JOIN hdp_applications ha ON s.application_id = ha.id JOIN letter_of_intent loi ON ha.loi_id = loi.id JOIN users u ON s.evaluator_id = u.id ORDER BY s.schedule_date DESC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span><button class="close-alert"><i class="fas fa-times"></i></button></div>
<?php endif; ?>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-header"><h3><i class="fas fa-calendar-plus" style="color:var(--primary-600);margin-right:8px"></i> Schedule Inspection</h3></div>
    <div class="card-body">
        <form method="POST">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Application <span class="required">*</span></label>
                    <select name="application_id" id="appSelect" class="form-control" required>
                        <option value="">Select application...</option>
                        <?php foreach ($apps_raw as $a): ?>
                        <option value="<?= $a['id'] ?>"
                            data-fees="<?= $a['tor_fees'] !== null ? number_format((float)$a['tor_fees'], 2, '.', '') : '' ?>">
                            <?= htmlspecialchars($a['company_name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Inspection Date <span class="required">*</span></label>
                    <input type="date" name="schedule_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Time</label>
                    <input type="time" name="schedule_time" class="form-control">
                </div>

                <div class="form-group">
                    <label>Fees (₱)</label>
                    <div style="position:relative;">
                        <input type="number" name="inspection_fees" id="inspectionFees" class="form-control" step="0.01" value="0.00">
                        <span id="feesHint" style="display:none;position:absolute;right:0;top:calc(100% + 2px);font-size:0.75rem;color:var(--neutral-500);">
                            <i class="fas fa-tag"></i> From accepted TOR
                        </span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" class="form-control" placeholder="Inspection location">
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-check"></i> Schedule Inspection</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3>All Schedules</h3></div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($schedules)): ?>
            <div class="empty-state"><div class="empty-icon"><i class="fas fa-calendar"></i></div><h3>No Schedules</h3></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Company</th><th>Date</th><th>Time</th><th>Fees</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($schedules as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['company_name']) ?></strong></td>
                            <td><?= formatDate($s['schedule_date']) ?></td>
                            <td><?= $s['schedule_time'] ?? '-' ?></td>
                            <td><?= formatCurrency($s['inspection_fees']) ?></td>
                            <td><?= getStatusBadge($s['status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script>
document.getElementById('appSelect').addEventListener('change', function () {
    const option = this.selectedOptions[0];
    const fees   = option?.dataset.fees;
    const input  = document.getElementById('inspectionFees');
    const hint   = document.getElementById('feesHint');

    if (fees !== undefined && fees !== '') {
        input.value = fees;
        if (hint) hint.style.display = 'block';
    } else {
        input.value = '0.00';
        if (hint) hint.style.display = 'none';
    }
});
</script>
