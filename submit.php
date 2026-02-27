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

// Required fields
$required = ['entrant', 'car_make', 'car_model', 'car_colour', 'driver_team', 'car_number', 'competitor_email', 'date_received'];
foreach ($required as $field) {
    if (empty(trim($post[$field] ?? ''))) {
        sendJsonResponse(false, 'Please fill in all required fields.');
    }
}

$email = filter_var(trim($post['competitor_email']), FILTER_VALIDATE_EMAIL);
if (!$email) {
    sendJsonResponse(false, 'Please enter a valid email address.');
}

// Signatures required
if (empty($post['sig_entrant']) || empty($post['sig_driver'])) {
    sendJsonResponse(false, 'Both Entrant and Driver signatures are required.');
}

// Load TCPDF
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/lib/tcpdf/tcpdf.php')) {
    require_once __DIR__ . '/lib/tcpdf/tcpdf.php';
} else {
    sendJsonResponse(false, 'PDF library not configured. Run: composer install');
}

use TCPDF;

// Generate PDF
try {
    $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
    $pdf->SetCreator('NASCC Tech Sheet');
    $pdf->SetMargins(12, 12, 12);
    $pdf->SetAutoPageBreak(true, 12);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->AddPage();

    $y = 15;
    $lh = 5;

    // Header
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 6, 'UCTA GO RACING!', 0, 1);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 5, 'VEHICLE INSPECTION FORM', 0, 1);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->MultiCell(0, 4, "It is the competitor's sole responsibility to ensure compliance to the regulations initially and at all times during the event. Inspections by the organizers, if any, do not imply compliance or any guarantee of vehicle safety.", 0, 'L');
    $y = $pdf->GetY() + 3;
    $pdf->SetFont('helvetica', '', 9);

    // Vehicle info - two columns
    $pdf->SetXY(12, $y);
    $pdf->Cell(40, $lh, 'ENTRANT:', 0, 0);
    $pdf->Cell(50, $lh, $post['entrant'] ?? '', 'B', 0);
    $pdf->Cell(15, $lh, '', 0, 0);
    $pdf->Cell(35, $lh, 'CAR MAKE:', 0, 0);
    $pdf->Cell(0, $lh, $post['car_make'] ?? '', 'B', 1);

    $pdf->Cell(40, $lh, 'CAR MODEL:', 0, 0);
    $pdf->Cell(50, $lh, $post['car_model'] ?? '', 'B', 0);
    $pdf->Cell(15, $lh, '', 0, 0);
    $pdf->Cell(35, $lh, 'CAR COLOUR:', 0, 0);
    $pdf->Cell(0, $lh, $post['car_colour'] ?? '', 'B', 1);

    $pdf->Cell(40, $lh, 'DRIVER/TEAM NAME:', 0, 0);
    $pdf->Cell(50, $lh, $post['driver_team'] ?? '', 'B', 0);
    $pdf->Cell(15, $lh, '', 0, 0);
    $pdf->Cell(35, $lh, 'CAR NUMBER:', 0, 0);
    $pdf->Cell(0, $lh, $post['car_number'] ?? '', 'B', 1);

    $pdf->Cell(40, $lh, 'CLASS:', 0, 0);
    $pdf->Cell(50, $lh, $post['class'] ?? '', 'B', 0);
    $pdf->Cell(15, $lh, '', 0, 0);
    $pdf->Cell(35, $lh, 'ENGINE (CC):', 0, 0);
    $pdf->Cell(0, $lh, $post['engine_cc'] ?? '', 'B', 1);

    $pdf->Cell(40, $lh, 'ENGINE (HP):', 0, 0);
    $pdf->Cell(50, $lh, $post['engine_hp'] ?? '', 'B', 0);
    $pdf->Cell(15, $lh, '', 0, 0);
    $pdf->Cell(35, $lh, 'CAR WEIGHT:', 0, 0);
    $pdf->Cell(0, $lh, $post['car_weight'] ?? '', 'B', 1);

    $y = $pdf->GetY() + 2;

    // Checkbox sections
    $sections = [
        'UNDER VEHICLE' => ['uv_0' => 'Steering linkage', 'uv_1' => 'Suspension & shocks', 'uv_2' => 'Wheel bearing condition', 'uv_3' => 'Brakes & hoses', 'uv_4' => 'Ball joints, rod ends, bushings'],
        'WHEELS and TIRES' => ['wt_0' => 'Wheel and tire condition', 'wt_1' => 'Meets class criteria'],
        'ENGINE COMPARTMENT' => ['ec_0' => 'Fuel pump, lines & fittings zero leaks', 'ec_1' => 'Oil supply tank, oil lines security', 'ec_2' => 'Oil catch tank (min. 1L)', 'ec_3' => 'Coolant hose condition', 'ec_4' => 'Coolant catch tank (min 1L)', 'ec_5' => 'Battery terminal posts insulated', 'ec_6' => 'Battery mount', 'ec_7' => 'Wiring mounting and integrity', 'ec_8' => 'Carburetion / fuel injection security'],
        'VEHICLE INTERIOR' => ['vi_0' => 'Roll bar padding/roll cage integrity', 'vi_1' => 'Accessories properly mounted', 'vi_2' => "Driver's seat securely mounted", 'vi_3' => 'Rearview mirror', 'vi_4' => 'Firewall and floor have no holes', 'vi_5' => 'Window net/Arm restraints', 'vi_6' => 'Window net release mechanism'],
        'VEHICLE EXTERIOR' => ['ve_0' => 'Front and Rear tow points', 've_1' => 'Appearance and Markings', 've_2' => 'Body panels secure', 've_3' => 'Windshield & windows', 've_4' => 'Headlights (Night and Ice events)', 've_5' => 'Brake & tail lights as per class rules', 've_6' => 'Exhaust system meets regulations', 've_7' => 'Window clips or Urethane', 've_8' => 'Bumper condition/attachment', 've_9' => 'Exterior mirrors (2)', 've_10' => 'Master switch - kills engine', 've_11' => 'Aero and Mud flaps secure', 've_12' => 'Rain lights/Rear facing light', 've_13' => 'Hood and Trunk fastened properly'],
        'FUEL TANK COMPARTMENT' => ['ft_0' => 'Proper ventilation and check valves', 'ft_1' => 'Surge tank safely mounted', 'ft_2' => 'Firewall/bulkhead', 'ft_3' => 'Fuel tank/fuel cell securely mounted'],
    ];

    foreach ($sections as $title => $items) {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, $lh, $title, 0, 1);
        $pdf->SetFont('helvetica', '', 8);
        foreach ($items as $key => $label) {
            $checked = !empty($post[$key]) ? 'X' : '';
            $pdf->Cell(6, $lh, '[' . $checked . ']', 0, 0);
            $pdf->Cell(0, $lh, $label, 0, 1);
        }
        $pdf->Ln(1);
    }

    // Fire extinguisher, seat belts
    $pdf->Cell(6, $lh, '[' . (!empty($post['fire_ext_type']) || !empty($post['fire_ext_age']) ? 'X' : '') . ']', 0, 0);
    $pdf->Cell(0, $lh, 'Fire Extinguisher – Type: ' . ($post['fire_ext_type'] ?? '') . ' Age: ' . ($post['fire_ext_age'] ?? ''), 0, 1);
    $pdf->Cell(6, $lh, '[' . (!empty($post['seatbelt_expiry']) ? 'X' : '') . ']', 0, 0);
    $pdf->Cell(0, $lh, 'Seat belts (5 or 6 point) Expiry date: ' . ($post['seatbelt_expiry'] ?? ''), 0, 1);

    // Driver safety - blank
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, $lh, 'DRIVER SAFETY EQUIPMENT (Tech fills at track)', 0, 1);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Ln(2);

    // Signatures
    $sigW = 55;
    $sigH = 25;
    $sigLabels = ['sig_entrant' => "Entrant's Signature", 'sig_driver' => "Driver's Signature"];
    $sigX = 12;
    foreach ($sigLabels as $key => $label) {
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(0, 4, $label, 0, 1);
        $data = $post[$key] ?? '';
        if ($data && preg_match('/^data:image\/png;base64,(.+)$/', $data, $m)) {
            $img = base64_decode($m[1]);
            if ($img !== false) {
                $tmp = tempnam(sys_get_temp_dir(), 'sig');
                file_put_contents($tmp, $img);
                $pdf->Image($tmp, $sigX, $pdf->GetY(), $sigW, $sigH, 'PNG');
                @unlink($tmp);
            }
        }
        $pdf->SetY($pdf->GetY() + $sigH + 2);
    }
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(0, $lh, "Tech Rep's Signature: (Tech fills at track)", 0, 1);

    // Footer
    $pdf->Ln(2);
    $pdf->Cell(0, $lh, 'Date Received: ' . ($post['date_received'] ?? ''), 0, 1);
    $pdf->Cell(0, $lh, 'Vehicle Log Book Turned In: ' . (($post['logbook'] ?? '') === 'yes' ? 'Yes' : (($post['logbook'] ?? '') === 'no' ? 'No' : '')), 0, 1);
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->MultiCell(0, 4, "Participants Declaration: I hereby stipulate that the above vehicle meets the regulations for the event.", 0, 'L');
    $pdf->SetFont('helvetica', '', 7);
    $pdf->Cell(0, 5, 'Revised 2021', 0, 1);

    $pdfContent = $pdf->Output('', 'S');

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
