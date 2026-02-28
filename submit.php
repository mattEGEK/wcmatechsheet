<?php
/**
 * Tech Sheet Form Handler - validates, generates PDF, emails
 */

$config = file_exists(__DIR__ . '/config.php')
    ? require __DIR__ . '/config.php'
    : require __DIR__ . '/config.example.php';
$techEmail = $config['tech_email'] ?? 'nascc.tech.director@gmail.com';

function sendJsonResponse($success, $message = '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'error' => $message]);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request.');
}

$post = $_POST;

// Honeypot
if (!empty($post['website'])) {
    sendJsonResponse(false, 'Submission rejected.');
}

// Field name to friendly label for error messages
$fieldLabels = [
    'season' => 'Season', 'entrant' => 'Entrant', 'car_make' => 'Car Make', 'car_model' => 'Car Model', 'car_colour' => 'Car Colour',
    'driver_team' => 'Driver/Team Name', 'car_number' => 'Car Number', 'competitor_email' => 'Competitor Email',
    'class' => 'Class', 'engine_cc' => 'Engine (CC)', 'engine_hp' => 'Engine (HP)', 'car_weight' => 'Car Weight',
    'uv_0' => 'Steering linkage', 'uv_1' => 'Suspension & shocks', 'uv_2' => 'Wheel bearing condition',
    'uv_3' => 'Brakes & hoses', 'uv_4' => 'Ball joints, rod ends, bushings',
    'wt_0' => 'Wheel and tire condition', 'wt_1' => 'Meets class criteria',
    'ec_0' => 'Fuel pump, lines & fittings zero leaks', 'ec_1' => 'Oil supply tank, oil lines security',
    'ec_3' => 'Coolant hose condition', 'ec_5' => 'Battery terminal posts insulated',
    'ec_6' => 'Battery mount', 'ec_7' => 'Wiring mounting and integrity', 'ec_8' => 'Carburetion / fuel injection security',
    'vi_1' => 'Accessories properly mounted', 'vi_2' => "Driver's seat securely mounted",
    'vi_3' => 'Rearview mirror', 'vi_4' => 'Firewall and floor have no holes',
    've_0' => 'Front and Rear tow points', 've_1' => 'Appearance and Markings', 've_2' => 'Body panels secure',
    've_3' => 'Windshield & windows', 've_4' => 'Headlights (Night and Ice events)',
    've_5' => 'Brake & tail lights as per class rules', 've_6' => 'Exhaust system meets regulations',
    've_8' => 'Bumper condition/attachment', 've_11' => 'Aero and Mud flaps secure',
    've_13' => 'Hood and Trunk fastened properly',
    'ft_2' => 'Firewall/bulkhead', 'ft_3' => 'Fuel tank/fuel cell securely mounted',
    'helmet_rating' => 'Helmet-Rating', 'suit_rating' => 'Suit-Rating', 'head_neck_restraint' => 'Head & Neck Restraint',
    'logbook' => 'Vehicle Log Book Turned In', 'sig_entrant' => "Entrant's signature", 'sig_driver' => "Driver's signature",
    'declaration_agree' => 'Participants Declaration',
];

$missing = [];

// Step 1 text fields
$textRequired = ['entrant', 'car_make', 'car_model', 'car_colour', 'driver_team', 'car_number', 'competitor_email', 'class', 'engine_cc', 'engine_hp', 'car_weight'];
foreach ($textRequired as $field) {
    if (empty(trim($post[$field] ?? ''))) {
        $missing[] = $fieldLabels[$field] ?? $field;
    }
}

$email = filter_var(trim($post['competitor_email'] ?? ''), FILTER_VALIDATE_EMAIL);
if (!empty(trim($post['competitor_email'] ?? '')) && !$email) {
    $missing[] = 'Competitor Email (valid email)';
}

// Step 2 & 3 mandatory checkboxes
$checkboxRequired = ['uv_0', 'uv_1', 'uv_2', 'uv_3', 'uv_4', 'wt_0', 'wt_1', 'ec_0', 'ec_1', 'ec_3', 'ec_5', 'ec_6', 'ec_7', 'ec_8', 'vi_1', 'vi_2', 'vi_3', 'vi_4', 've_0', 've_1', 've_2', 've_3', 've_4', 've_5', 've_6', 've_8', 've_11', 've_13', 'ft_2', 'ft_3'];
foreach ($checkboxRequired as $field) {
    if (empty($post[$field]) || $post[$field] !== '1') {
        $missing[] = $fieldLabels[$field] ?? $field;
    }
}

// Step 1 & 4 mandatory selects
$selectRequired = ['season', 'helmet_rating', 'suit_rating', 'head_neck_restraint'];
foreach ($selectRequired as $field) {
    $val = trim($post[$field] ?? '');
    if ($val === '') {
        $missing[] = $fieldLabels[$field] ?? $field;
    }
}

// Signatures
if (empty($post['sig_entrant']) || strlen($post['sig_entrant']) < 50) {
    $missing[] = $fieldLabels['sig_entrant'];
}
if (empty($post['sig_driver']) || strlen($post['sig_driver']) < 50) {
    $missing[] = $fieldLabels['sig_driver'];
}

// Step 5 logbook
$logbook = trim($post['logbook'] ?? '');
if ($logbook !== 'yes' && $logbook !== 'no') {
    $missing[] = $fieldLabels['logbook'];
}

// Declaration
if (empty($post['declaration_agree']) || $post['declaration_agree'] !== '1') {
    $missing[] = $fieldLabels['declaration_agree'];
}

if (!empty($missing)) {
    $msg = count($missing) === 1
        ? 'Please complete: ' . $missing[0]
        : 'Please complete: ' . implode(', ', array_slice($missing, 0, 5)) . (count($missing) > 5 ? ' and ' . (count($missing) - 5) . ' more' : '');
    sendJsonResponse(false, $msg);
}

// Load TCPDF
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/lib/tcpdf/tcpdf.php')) {
    require_once __DIR__ . '/lib/tcpdf/tcpdf.php';
} else {
    sendJsonResponse(false, 'PDF library not configured. Run: composer install');
}

require_once __DIR__ . '/lib/PdfGenerator.php';

try {
    $pdfContent = PdfGenerator::generate($post, __DIR__);
} catch (Exception $e) {
    sendJsonResponse(false, 'Failed to generate PDF. Please try again.');
}

// Send emails
$carNumber = $post['car_number'] ?? '';
$driverTeam = $post['driver_team'] ?? '';
$subject = 'Tech Sheet - ' . $carNumber . ' - ' . $driverTeam;

$boundary = '----=_Part_' . md5(uniqid());
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

$body = "--{$boundary}\r\n";
$body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
$body .= "Attached is the completed tech inspection form for Car #{$carNumber} - {$driverTeam}.\r\n\r\n";
$body .= "--{$boundary}\r\n";
$body .= "Content-Type: application/pdf; name=\"techsheet-{$carNumber}.pdf\"\r\n";
$body .= "Content-Transfer-Encoding: base64\r\n";
$body .= "Content-Disposition: attachment; filename=\"techsheet-{$carNumber}.pdf\"\r\n\r\n";
$body .= chunk_split(base64_encode($pdfContent)) . "\r\n";
$body .= "--{$boundary}--\r\n";

$sentTech = @mail($techEmail, $subject, $body, $headers);
$sentCompetitor = @mail($email, $subject, $body, $headers);

if (!$sentTech) {
    sendJsonResponse(false, 'Email could not be sent. Please try again or contact tech director.');
}

sendJsonResponse(true);
