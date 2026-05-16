<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_EVALUATOR]);

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$action_type = $_POST['action_type'] ?? '';

// ── Per-file review ───────────────────────────────────────────────────────────
if ($action_type === 'file_review') {
    $app_id        = intval($_POST['application_id']);
    $req_label     = trim($_POST['requirement_label'] ?? '');
    $review_status = sanitize($_POST['review_status']);
    $remarks       = sanitize($_POST['remarks'] ?? '');

    if ($review_status === 'rejected' && empty($remarks)) {
        echo json_encode(['success' => false, 'message' => 'Remarks are required when rejecting a file.']);
        exit();
    }

    // Try UPDATE first
    $upd = $conn->prepare("
        UPDATE application_requirement_reviews
        SET review_status = ?,
            remarks = ?,
            reviewed_by = ?,
            reviewed_at = NOW()
        WHERE application_id = ?
          AND requirement_label = ?
          AND reviewed_by = ?
    ");
    if (!$upd) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        exit();
    }
    $upd->bind_param("ssiisi", $review_status, $remarks, $user_id, $app_id, $req_label, $user_id);
    $upd->execute();

    if ($upd->affected_rows <= 0) {
        // No existing row — INSERT
        $ins = $conn->prepare("
            INSERT INTO application_requirement_reviews
                (application_id, requirement_label, review_status, remarks, reviewed_by, reviewed_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        if (!$ins) {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
            exit();
        }
        $ins->bind_param("isssi", $app_id, $req_label, $review_status, $remarks, $user_id);
        if (!$ins->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to save review: ' . $conn->error]);
            exit();
        }
    }

    // Re-compute counters to return fresh progress data
    $optional_labels = [
        '1.12 Previous Halal Certificate from HDIP (if applicable)',
        '2.1 GMP, HACCP, SSOP, GHP, TQM, ISO Certificates (if available)',
        '4.3 Proof of Dedicated Halal Prayer Room',
    ];
    $requirement_labels = [
        '1.1 Letter of Intent','1.2 Company Profile','1.3 SEC Registration / DTI License',
        '1.4 Mayor\'s Permit','1.5 Business Permit','1.6 Barangay Permit',
        '1.7 Sanitary Permit','1.8 Fire Clearance Certificate','1.9 DENR Environment Certificate',
        '1.10 FDA License to Operate (LTO)','1.11 Certificate of Product Registration (CPR)',
        '1.12 Previous Halal Certificate from HDIP (if applicable)',
        '2.1 GMP, HACCP, SSOP, GHP, TQM, ISO Certificates (if available)',
        '2.2 Halal Assurance System (HAS) Manual','2.3 Waste Disposal Management Plan',
        '2.4 Pest Control Program','2.5 Kitchen Layout','2.6 Flow Chart of Product Processing',
        '3.1 Full List of Products / Menu with Corresponding Ingredients',
        '3.2 Raw Materials / Ingredients Matrix with Sources (Local or Imported)',
        '3.3 Packaging Materials List with Corresponding Halal Certificates',
        '4.1 Halal Certificates for All Raw Materials (especially meat products)',
        '4.2 Appointment of at Least 2 Muslim Cooks and 2 Muslim Crew Members',
        '4.3 Proof of Dedicated Halal Prayer Room',
        '4.4 Alcohol and Liquor Prohibition Compliance in the Kitchen',
        '4.5 Warehouse Halal Certificate and Storage System Description',
        '4.6 Transportation Details for Halal Products',
    ];
    $required_labels = array_values(array_diff($requirement_labels, $optional_labels));
    $total_required  = count($required_labels);

    $uploads = [];
    $uf = $conn->prepare("SELECT requirement_label FROM application_requirement_uploads WHERE application_id = ?");
    $uf->bind_param("i", $app_id);
    $uf->execute();
    foreach ($uf->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $uploads[$row['requirement_label']] = true;
    }

    $reviews = [];
    $rv = $conn->prepare("SELECT requirement_label, review_status FROM application_requirement_reviews WHERE application_id = ? AND reviewed_by = ?");
    $rv->bind_param("ii", $app_id, $user_id);
    $rv->execute();
    foreach ($rv->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        $reviews[$row['requirement_label']] = $row['review_status'];
    }

    $accepted = 0; $rejected = 0;
    foreach ($required_labels as $lbl) {
        if (($reviews[$lbl] ?? '') === 'accepted') $accepted++;
        if (($reviews[$lbl] ?? '') === 'rejected')  $rejected++;
    }
    $uploaded_required = count(array_filter($required_labels, fn($l) => isset($uploads[$l])));
    $pending = max(0, $uploaded_required - $accepted - $rejected);

    echo json_encode([
        'success'        => true,
        'message'        => 'File review saved.',
        'review_status'  => $review_status,
        'remarks'        => $remarks,
        'counters'       => [
            'accepted'          => $accepted,
            'rejected'          => $rejected,
            'pending'           => $pending,
            'total_required'    => $total_required,
            'uploaded_required' => $uploaded_required,
            'progress_pct'      => $total_required > 0 ? round(($accepted / $total_required) * 100) : 0,
        ],
    ]);
    exit();
}

// ── Overall verification (approve / return) ───────────────────────────────────
if ($action_type === 'overall_verify') {
    $app_id   = intval($_POST['application_id']);
    $status   = sanitize($_POST['verification_status']);
    $feedback = sanitize($_POST['feedback'] ?? '');
    $enterprise_type = normalizeEnterpriseType($_POST['enterprise_type'] ?? '');

    if ($status === 'returned' && empty($feedback)) {
        echo json_encode(['success' => false, 'message' => 'Remarks are required when returning an application.']);
        exit();
    }
    if ($status === 'verified' && empty($enterprise_type)) {
        echo json_encode(['success' => false, 'message' => 'Please classify the business as a micro, small, or medium enterprise before approving.']);
        exit();
    }

    if ($status === 'verified') {
        $optional_labels = [
            '1.12 Previous Halal Certificate from HDIP (if applicable)',
            '2.1 GMP, HACCP, SSOP, GHP, TQM, ISO Certificates (if available)',
            '4.3 Proof of Dedicated Halal Prayer Room',
        ];
        $requirement_labels = [
            '1.1 Letter of Intent','1.2 Company Profile','1.3 SEC Registration / DTI License',
            '1.4 Mayor\'s Permit','1.5 Business Permit','1.6 Barangay Permit',
            '1.7 Sanitary Permit','1.8 Fire Clearance Certificate','1.9 DENR Environment Certificate',
            '1.10 FDA License to Operate (LTO)','1.11 Certificate of Product Registration (CPR)',
            '1.12 Previous Halal Certificate from HDIP (if applicable)',
            '2.1 GMP, HACCP, SSOP, GHP, TQM, ISO Certificates (if available)',
            '2.2 Halal Assurance System (HAS) Manual','2.3 Waste Disposal Management Plan',
            '2.4 Pest Control Program','2.5 Kitchen Layout','2.6 Flow Chart of Product Processing',
            '3.1 Full List of Products / Menu with Corresponding Ingredients',
            '3.2 Raw Materials / Ingredients Matrix with Sources (Local or Imported)',
            '3.3 Packaging Materials List with Corresponding Halal Certificates',
            '4.1 Halal Certificates for All Raw Materials (especially meat products)',
            '4.2 Appointment of at Least 2 Muslim Cooks and 2 Muslim Crew Members',
            '4.3 Proof of Dedicated Halal Prayer Room',
            '4.4 Alcohol and Liquor Prohibition Compliance in the Kitchen',
            '4.5 Warehouse Halal Certificate and Storage System Description',
            '4.6 Transportation Details for Halal Products',
        ];
        $required_labels = array_values(array_diff($requirement_labels, $optional_labels));

        $legacy_kitchen_flow_label = '2.5 Kitchen Layout and Flow Chart of Product Processing';
        $kitchen_layout_label      = '2.5 Kitchen Layout';
        $flow_chart_label          = '2.6 Flow Chart of Product Processing';

        $uploads = [];
        $uf = $conn->prepare("SELECT requirement_label FROM application_requirement_uploads WHERE application_id = ?");
        $uf->bind_param("i", $app_id);
        $uf->execute();
        foreach ($uf->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $uploads[$row['requirement_label']] = true;
        }
        // Legacy label mapping
        if (isset($uploads[$legacy_kitchen_flow_label])) {
            $uploads[$kitchen_layout_label] = $uploads[$kitchen_layout_label] ?? true;
            $uploads[$flow_chart_label]     = $uploads[$flow_chart_label]     ?? true;
        }

        $reviews = [];
        $rv = $conn->prepare("SELECT requirement_label, review_status FROM application_requirement_reviews WHERE application_id = ? AND reviewed_by = ?");
        $rv->bind_param("ii", $app_id, $user_id);
        $rv->execute();
        foreach ($rv->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $reviews[$row['requirement_label']] = $row['review_status'];
        }
        // Legacy label mapping
        if (isset($reviews[$legacy_kitchen_flow_label])) {
            $reviews[$kitchen_layout_label] = $reviews[$kitchen_layout_label] ?? $reviews[$legacy_kitchen_flow_label];
            $reviews[$flow_chart_label]     = $reviews[$flow_chart_label]     ?? $reviews[$legacy_kitchen_flow_label];
        }

        $uploaded_labels = array_values(array_intersect(array_keys($uploads), $requirement_labels));
        if (empty($uploaded_labels)) {
            echo json_encode(['success' => false, 'message' => 'No uploaded requirement files were found to validate.']);
            exit();
        }
        foreach ($uploaded_labels as $lbl) {
            if (!isset($reviews[$lbl]) || !in_array($reviews[$lbl], ['accepted', 'rejected'], true)) {
                echo json_encode(['success' => false, 'message' => 'You cannot approve this application yet. Please review all uploaded files first.']);
                exit();
            }
        }
        foreach ($required_labels as $lbl) {
            if (!isset($reviews[$lbl]) || $reviews[$lbl] !== 'accepted') {
                echo json_encode(['success' => false, 'message' => 'You cannot approve this application because at least one required file was rejected. Use Return / Incomplete instead.']);
                exit();
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO application_verification (application_id, evaluator_id, verification_status, feedback, verified_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiss", $app_id, $user_id, $status, $feedback);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to save: ' . $conn->error]);
        exit();
    }

    $new_status = $status === 'verified' ? 'verified' : 'incomplete';
    if ($status === 'verified') {
        $upd = $conn->prepare("UPDATE hdp_applications SET status = ?, enterprise_type = ? WHERE id = ?");
        $upd->bind_param("ssi", $new_status, $enterprise_type, $app_id);
    } else {
        $upd = $conn->prepare("UPDATE hdp_applications SET status = ? WHERE id = ?");
        $upd->bind_param("si", $new_status, $app_id);
    }
    $upd->execute();

    $owner_q = $conn->prepare("SELECT business_owner_id FROM hdp_applications WHERE id = ?");
    $owner_q->bind_param("i", $app_id);
    $owner_q->execute();
    $owner = $owner_q->get_result()->fetch_assoc();
    if ($owner) {
        $msg = $status === 'verified'
            ? 'Your HDP application has been approved.'
            : 'Your HDP application has been returned. Please check the evaluator\'s feedback and resubmit.';
        createNotification($conn, $owner['business_owner_id'],
            'Application ' . ($status === 'verified' ? 'Approved' : 'Returned'),
            $msg,
            $status === 'verified' ? 'success' : 'warning',
            BASE_URL . 'dashboard/business_owner/applications.php');
    }
    logActivity($conn, $user_id, 'Application Verified', 'App #' . $app_id . ' ' . $status, 'evaluation');

    echo json_encode([
        'success'    => true,
        'message'    => 'Application ' . $status . '.',
        'new_status' => $new_status,
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unknown action type.']);
