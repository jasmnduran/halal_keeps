<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../vendor/autoload.php';

requireLogin();
requireRole([ROLE_BUSINESS_OWNER]);

$user_id = $_SESSION['user_id'];
$loi_id  = intval($_GET['id'] ?? 0);

if (!$loi_id) {
    header('Location: ' . BASE_URL . 'dashboard/business_owner/letter_of_intent.php');
    exit();
}

$stmt = $conn->prepare("
    SELECT loi.*, lv.verification_status, lv.verified_at, u2.full_name as evaluator_name
    FROM letter_of_intent loi
    LEFT JOIN loi_verification lv ON loi.id = lv.loi_id
    LEFT JOIN users u2 ON lv.evaluator_id = u2.id
    WHERE loi.id = ? AND loi.business_owner_id = ? AND loi.status = 'verified'
");
$stmt->bind_param("ii", $loi_id, $user_id);
$stmt->execute();
$loi = $stmt->get_result()->fetch_assoc();

if (!$loi) {
    header('Location: ' . BASE_URL . 'dashboard/business_owner/letter_of_intent.php');
    exit();
}

$items = array_filter(explode("\n", $loi['menu_list'] ?? ''));
$menu_str = $items ? htmlspecialchars(implode(', ', array_map('trim', $items))) : 'our products and services';

$date_str = $loi['date_of_intent'] ? date('d F Y', strtotime($loi['date_of_intent'])) : date('d F Y');
$verified_date = $loi['verified_at'] ? date('d/m/Y', strtotime($loi['verified_at'])) : date('d/m/Y');

$enterprise = htmlspecialchars($loi['enterprise_type'] ?? '');
$app_type   = htmlspecialchars($loi['application_type'] ?? '');
$company    = htmlspecialchars($loi['company_name']);
$address    = htmlspecialchars($loi['company_address']);
$contact    = htmlspecialchars($loi['contact_person'] ?? $_SESSION['full_name']);
$full_name  = htmlspecialchars($_SESSION['full_name']);
$phone      = htmlspecialchars($loi['contact_phone'] ?? '');
$email      = htmlspecialchars($loi['contact_email'] ?? $_SESSION['email']);

// Build signature HTML
$sig_html = '';
if (!empty($loi['signature_data'])) {
    $sig_html = '<img src="' . htmlspecialchars($loi['signature_data']) . '" style="max-width:200px;height:auto;display:block;border-bottom:1px solid #333;margin-bottom:4px;">';
} else {
    $sig_html = '<div style="border-bottom:1px solid #333;width:220px;margin-bottom:4px;margin-top:40px;">&nbsp;</div>';
}

$html = '
<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: "Times New Roman", Times, serif; font-size: 12pt; color: #111; line-height: 1.9; }
    p { margin-bottom: 14pt; text-align: justify; }
    .date-line { margin-bottom: 24pt; }
    .recipient { margin-bottom: 24pt; }
    .subject { margin-bottom: 24pt; }
    .closing { margin-top: 14pt; margin-bottom: 40pt; }
    .sig-name { font-weight: bold; margin-bottom: 2pt; }
    .stamp {
        position: absolute;
        bottom: 20mm;
        right: 20mm;
        width: 80px;
        height: 80px;
        border: 3px solid #16a34a;
        border-radius: 50%;
        text-align: center;
        color: #16a34a;
        font-family: Arial, sans-serif;
        font-size: 7pt;
        font-weight: bold;
        padding-top: 18px;
        line-height: 1.4;
        opacity: 0.75;
        transform: rotate(-15deg);
    }
    .stamp-main { font-size: 10pt; letter-spacing: 1px; }
</style>
</head>
<body>

<p class="date-line">' . $date_str . '</p>

<p class="recipient">
    <strong>HADJI ABDULATIF S. SANGCUPAN</strong><br>
    President/CEO<br>
    Halal Development Institute of the Philippines (HDIP)<br>
    4th Flr. Unit 401, Central Bldg. 37 Arayat Cor. Malabito St.<br>
    Cubao, Quezon City
</p>

<p class="subject">
    <strong>Subject: Letter of Intent for Halal Certification' . ($app_type ? ' — ' . $app_type : '') . '</strong><br>
    ' . $company . ($enterprise ? ' (<em>' . $enterprise . '</em>)' : '') . '
</p>

<p>Dear Sir/Madam,</p>

<p>
    I am writing to formally express the intent of <strong>' . $company . '</strong>' .
    ($enterprise ? ', a <strong>' . $enterprise . '</strong>,' : '') .
    ' to apply for ' . ($app_type ? '<strong>' . $app_type . '</strong> of ' : '') .
    'Halal Certification from the Halal Development Institute of the Philippines (HDIP) for our products/services.
</p>

<p>
    Our company, located at <strong>' . $address . '</strong>, produces/serves
    <strong>' . $menu_str . '</strong>.
    We are committed to complying with all the requirements and standards set forth by the HDIP,
    including adherence to Shariah law, GMP (Good Manufacturing Practices), and hygiene standards.
</p>

<p>
    We have attached our company profile, product list, and other relevant documents for your preliminary review.
    We request an assessment of our facility for Halal compliance.
</p>

<p>Thank you for your assistance.</p>

<p class="closing">Sincerely,</p>

<div>
    ' . $sig_html . '
    <p class="sig-name">' . $contact . '</p>
    <p>' . $full_name . '</p>
    <p>' . $phone . '</p>
    <p>' . $email . '</p>
</div>

<div class="stamp">
    <div class="stamp-main">VERIFIED</div>
    <div>HDIP</div>
    <div>' . $verified_date . '</div>
</div>

</body>
</html>';

// Generate PDF
$mpdf = new \Mpdf\Mpdf([
    'tempDir'       => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mpdf',
    'margin_top'    => 25,
    'margin_bottom' => 20,
    'margin_left'   => 25,
    'margin_right'  => 25,
    'format'        => 'A4',
]);

$mpdf->SetTitle('Letter of Intent — ' . $loi['company_name']);
$mpdf->SetAuthor($loi['contact_person'] ?? $_SESSION['full_name']);
$mpdf->WriteHTML($html);

$filename = 'LOI_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $loi['company_name']) . '_' . date('Ymd') . '.pdf';
$mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
exit();
