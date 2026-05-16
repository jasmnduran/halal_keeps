<?php
/**
 * AJAX endpoint: upload a single requirement file for an HDP application.
 * Expects multipart/form-data POST with:
 *   - req_file      : the file
 *   - application_id: int
 *   - requirement_label: string
 * Returns JSON { success, file_path, uploaded_at, message }
 */
require_once __DIR__ . '/../config/app.php';
header('Content-Type: application/json');

// Must be logged-in business owner
if (!isLoggedIn() || ($_SESSION['role_id'] ?? 0) !== ROLE_BUSINESS_OWNER) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$app_id  = intval($_POST['application_id'] ?? 0);
$label   = trim($_POST['requirement_label'] ?? ''); // trim only — must not HTML-encode label used as a DB key

if (!$app_id || !$label) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
    exit();
}

// Verify the application belongs to this user
$chk = $conn->prepare("SELECT id FROM hdp_applications WHERE id = ? AND business_owner_id = ? AND status IN ('draft','incomplete')");
$chk->bind_param("ii", $app_id, $user_id);
$chk->execute();
if (!$chk->get_result()->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Application not found or not editable.']);
    exit();
}

if (!isset($_FILES['req_file']) || $_FILES['req_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file received or upload error.']);
    exit();
}

$allowed = ['pdf', 'png', 'jpg', 'jpeg'];
$upload  = uploadFile($_FILES['req_file'], 'documents', $allowed);

if (!$upload['success']) {
    echo json_encode(['success' => false, 'message' => $upload['message']]);
    exit();
}

// Replace existing upload for same label
$del = $conn->prepare("DELETE FROM application_requirement_uploads WHERE application_id = ? AND requirement_label = ?");
$del->bind_param("is", $app_id, $label);
$del->execute();

$ins = $conn->prepare("INSERT INTO application_requirement_uploads (application_id, requirement_label, file_path) VALUES (?, ?, ?)");
$ins->bind_param("iss", $app_id, $label, $upload['path']);
$ins->execute();

// Clear any previous rejection so evaluator can re-review
$clr = $conn->prepare("DELETE FROM application_requirement_reviews WHERE application_id = ? AND requirement_label = ? AND review_status = 'rejected'");
$clr->bind_param("is", $app_id, $label);
$clr->execute();

// Return the new file info so the UI can update in place
echo json_encode([
    'success'     => true,
    'file_path'   => $upload['path'],
    'uploaded_at' => date('M d, Y'),
    'message'     => 'Uploaded successfully.',
]);
