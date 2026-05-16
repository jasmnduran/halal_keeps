<?php
require_once __DIR__ . '/../../../config/app.php';
requireLogin();
requireRole([ROLE_AUDITOR_TECHNICAL, ROLE_AUDITOR_SHARIAH]);

header('Content-Type: application/json');

$inspection_id = intval($_GET['inspection_id'] ?? 0);
$user_id       = $_SESSION['user_id'];
$role_id       = $_SESSION['role_id'];
$is_technical  = ($role_id == ROLE_AUDITOR_TECHNICAL);

if (!$inspection_id) {
    echo json_encode(['findings' => '', 'nc_remarks' => []]);
    exit;
}

$findings_field = $is_technical ? 'tech_audit_findings' : 'shariah_audit_findings';
$auditor_field  = $is_technical ? 'auditor_technical_id' : 'auditor_shariah_id';

// Get this auditor's audit findings text
$stmt = $conn->prepare("
    SELECT $findings_field AS findings
    FROM inspections
    WHERE id = ? AND $auditor_field = ?
    LIMIT 1
");
$stmt->bind_param("ii", $inspection_id, $user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$findings = $row['findings'] ?? '';

// Get non-conforming document remarks for this auditor
$stmt2 = $conn->prepare("
    SELECT requirement_label, remarks
    FROM inspection_document_conformity
    WHERE inspection_id = ? AND auditor_id = ? AND conformity_status = 'non_conforming'
    ORDER BY requirement_label ASC
");
$stmt2->bind_param("ii", $inspection_id, $user_id);
$stmt2->execute();
$nc_rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$nc_remarks = [];
foreach ($nc_rows as $r) {
    $nc_remarks[] = [
        'label'   => $r['requirement_label'],
        'remarks' => $r['remarks'] ?? '',
    ];
}

echo json_encode([
    'findings'   => $findings,
    'nc_remarks' => $nc_remarks,
]);
