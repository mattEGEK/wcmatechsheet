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
$required = ['entrant', 'car_make', 'car_model', 'car_colour', 'driver_team', 'car_number', 'competitor_email'];
foreach ($required as $field) {
    if (empty(trim($post[$field] ?? ''))) {
        sendJsonResponse(false, 'Please fill in all required fields.');
    }
}

$email = filter_var(trim($post['competitor_email']), FILTER_VALIDATE_EMAIL);
if (!$email) {
    sendJsonResponse(false, 'Please enter a valid email address.');
}

// Declaration agreement required
if (empty($post['declaration_agree']) || $post['declaration_agree'] !== '1') {
    sendJsonResponse(false, 'You must agree to the Participants Declaration to submit.');
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

$dateSubmitted = date('Y-m-d H:i');
$logoPath = __DIR__ . '/assets/images/WCMA-Logo.png';

// Generate PDF - match original layout exactly, single page
try {
    $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
    $pdf->SetCreator('NASCC Tech Sheet');
    $pdf->SetMargins(10, 8, 10);
    $pdf->SetAutoPageBreak(false);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->AddPage();

    $x = 10;
    $y = 8;
    $lh = 3.5;
    $colW = 95;

    // Header: Logo + title
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, $x, $y, 25, 0, 'PNG');
    }
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetXY(38, $y);
    $pdf->Cell(0, 5, 'WCMA Go Racing!', 0, 1);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetX(38);
    $pdf->Cell(0, 4, 'VEHICLE INSPECTION FORM', 0, 1);
    $y = $pdf->GetY() + 1;
    $pdf->SetFont('helvetica', '', 6);
    $pdf->MultiCell(0, 3, "It is the competitor's sole responsibility to ensure compliance to the regulations initially and at all times during the event. Inspections by the organizers, if any, do not imply compliance or any guarantee of vehicle safety.", 0, 'L');
    $y = $pdf->GetY() + 2;

    // Vehicle info - two columns per original
    // Left: ENTRANT, CAR MAKE, CAR MODEL, CAR COLOUR
    // Right: DRIVER/TEAM NAME, CAR NUMBER, CLASS, ENGINE (CC) (HP), CAR WEIGHT
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetXY($x, $y);
    $pdf->Cell(22, $lh, 'ENTRANT:', 0, 0);
    $pdf->Cell(45, $lh, $post['entrant'] ?? '', 'B', 0);
    $pdf->SetXY($x + $colW, $y);
    $pdf->Cell(28, $lh, 'DRIVER/TEAM NAME:', 0, 0);
    $pdf->Cell(0, $lh, $post['driver_team'] ?? '', 'B', 1);

    $y = $pdf->GetY();
    $pdf->SetXY($x, $y);
    $pdf->Cell(22, $lh, 'CAR MAKE:', 0, 0);
    $pdf->Cell(45, $lh, $post['car_make'] ?? '', 'B', 0);
    $pdf->SetXY($x + $colW, $y);
    $pdf->Cell(22, $lh, 'CAR NUMBER:', 0, 0);
    $pdf->Cell(25, $lh, $post['car_number'] ?? '', 'B', 0);
    $pdf->Cell(12, $lh, 'CLASS:', 0, 0);
    $pdf->Cell(0, $lh, $post['class'] ?? '', 'B', 1);

    $y = $pdf->GetY();
    $pdf->SetXY($x, $y);
    $pdf->Cell(22, $lh, 'CAR MODEL:', 0, 0);
    $pdf->Cell(45, $lh, $post['car_model'] ?? '', 'B', 0);
    $pdf->SetXY($x + $colW, $y);
    $pdf->Cell(22, $lh, 'ENGINE:', 0, 0);
    $pdf->Cell(15, $lh, $post['engine_cc'] ?? '', 'B', 0);
    $pdf->Cell(8, $lh, '(CC)', 0, 0);
    $pdf->Cell(15, $lh, $post['engine_hp'] ?? '', 'B', 0);
    $pdf->Cell(8, $lh, '(HP)', 0, 1);

    $y = $pdf->GetY();
    $pdf->SetXY($x, $y);
    $pdf->Cell(22, $lh, 'CAR COLOUR:', 0, 0);
    $pdf->Cell(45, $lh, $post['car_colour'] ?? '', 'B', 0);
    $pdf->SetXY($x + $colW, $y);
    $pdf->Cell(22, $lh, 'CAR WEIGHT:', 0, 0);
    $pdf->Cell(0, $lh, $post['car_weight'] ?? '', 'B', 1);

    $y = $pdf->GetY() + 1;

    // EACH ITEM MUST BE VERIFIED...
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(0, 5, 'EACH ITEM MUST BE VERIFIED BY THE COMPETITOR PRIOR TO THE TECHNICAL INSPECTION', 1, 1, 'C', true);
    $y = $pdf->GetY() + 1;

    // Two-column checklist - Left col then Right col
    $leftCol = [
        'UNDER VEHICLE' => ['uv_0' => 'Steering linkage', 'uv_1' => 'Suspension & shocks', 'uv_2' => 'Wheel bearing condition', 'uv_3' => 'Brakes & hoses', 'uv_4' => 'Ball joints, rod ends, bushings'],
        'WHEELS and TIRES' => ['wt_0' => 'Wheel and tire condition', 'wt_1' => 'Meets class criteria'],
        'ENGINE COMPARTMENT' => ['ec_0' => 'Fuel pump, lines & fittings zero leaks', 'ec_1' => 'Oil supply tank, oil lines security', 'ec_2' => 'Oil catch tank (min. 1L)', 'ec_3' => 'Coolant hose condition', 'ec_4' => 'Coolant catch tank (min 1L)', 'ec_5' => 'Battery terminal posts insulated', 'ec_6' => 'Battery mount', 'ec_7' => 'Wiring mounting and integrity', 'ec_8' => 'Carburetion / fuel injection security'],
        'VEHICLE INTERIOR' => ['vi_0' => 'Roll bar padding/roll cage integrity', 'vi_1' => 'Accessories properly mounted', 'vi_2' => "Driver's seat securely mounted", 'vi_3' => 'Rearview mirror', 'vi_4' => 'Firewall and floor have no holes', 'vi_5' => 'Window net/Arm restraints', 'vi_6' => 'Window net release mechanism', 'fire_ext' => 'Fire Extinguisher – Type ' . ($post['fire_ext_type'] ?? '') . ' Age ' . ($post['fire_ext_age'] ?? ''), 'seatbelt' => 'Seat belts (5 or 6 point) (Expiry date) ' . ($post['seatbelt_expiry'] ?? '')],
    ];
    $rightCol = [
        'VEHICLE EXTERIOR' => ['ve_0' => 'Front and Rear tow points', 've_1' => 'Appearance and Markings', 've_2' => 'Body panels secure', 've_3' => 'Windshield & windows', 've_4' => 'Headlights (Night and Ice events)', 've_5' => 'Brake & tail lights as per class rules', 've_6' => 'Exhaust system meets regulations', 've_7' => 'Window clips or Urethane', 've_8' => 'Bumper condition/attachment', 've_9' => 'Exterior mirrors (2)', 've_10' => 'Master switch - kills engine', 've_11' => 'Aero and Mud flaps secure', 've_12' => 'Rain lights/Rear facing light', 've_13' => 'Hood and Trunk fastened properly'],
        'FUEL TANK COMPARTMENT' => ['ft_0' => 'Proper ventilation and check valves', 'ft_1' => 'Surge tank safely mounted', 'ft_2' => 'Firewall/bulkhead', 'ft_3' => 'Fuel tank/fuel cell securely mounted'],
    ];

    $driverSafetyFields = [
        ['type' => 'dropdown', 'name' => 'helmet_rating', 'label' => 'Helmet-Rating'],
        ['type' => 'checkbox', 'name' => 'goggles_visor', 'label' => 'Goggles or visor'],
        ['type' => 'dropdown', 'name' => 'suit_rating', 'label' => 'Suit-Rating'],
        ['type' => 'checkbox', 'name' => 'underwear', 'label' => 'Underwear (if required)'],
        ['type' => 'dropdown', 'name' => 'shoes', 'label' => 'Shoes'],
        ['type' => 'dropdown', 'name' => 'socks', 'label' => 'Socks'],
        ['type' => 'dropdown', 'name' => 'gloves', 'label' => 'Gloves'],
        ['type' => 'dropdown', 'name' => 'balaclava', 'label' => 'Balaclava'],
        ['type' => 'dropdown', 'name' => 'head_neck_restraint', 'label' => 'Head & Neck Restraint'],
    ];

    function renderCheckColumn($pdf, $sections, $post, $x, $colW, $lh) {
        $y = $pdf->GetY();
        foreach ($sections as $title => $items) {
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->SetXY($x, $y);
            $pdf->Cell($colW - 10, $lh, $title, 0, 0);
            $y += $lh + 0.5;
            $pdf->SetFont('helvetica', '', 6);
            foreach ($items as $key => $label) {
                $checked = ' ';
                if ($key === 'fire_ext') {
                    $checked = (!empty($post['fire_ext_type']) || !empty($post['fire_ext_age'])) ? 'X' : ' ';
                } elseif ($key === 'seatbelt') {
                    $checked = !empty($post['seatbelt_expiry']) ? 'X' : ' ';
                } elseif (isset($post[$key]) && $post[$key]) {
                    $checked = 'X';
                }
                $pdf->SetXY($x, $y);
                $pdf->Cell(5, $lh, '[' . $checked . ']', 0, 0);
                $pdf->Cell($colW - 15, $lh, $label, 0, 0);
                $y += $lh;
            }
            $y += 1;
        }
        return $y;
    }

    function renderDriverSafetySection($pdf, $post, $fields, $x, $colW, $lh) {
        $y = $pdf->GetY();
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY($x, $y);
        $pdf->Cell($colW - 10, $lh, 'DRIVER SAFETY EQUIPMENT', 0, 0);
        $y += $lh + 0.5;
        $pdf->SetFont('helvetica', '', 6);
        foreach ($fields as $field) {
            $pdf->SetXY($x, $y);
            if ($field['type'] === 'checkbox') {
                $checked = (!empty($post[$field['name']]) && $post[$field['name']]) ? 'X' : ' ';
                $pdf->Cell(5, $lh, '[' . $checked . ']', 0, 0);
                $pdf->Cell($colW - 15, $lh, $field['label'], 0, 0);
            } else {
                $val = trim($post[$field['name']] ?? '');
                $label = $field['label'] . ': ' . ($val !== '' ? $val : '');
                $pdf->Cell(5, $lh, '', 0, 0);
                $pdf->Cell($colW - 15, $lh, $label, 0, 0);
            }
            $y += $lh;
        }
        return $y + 1;
    }

    $pdf->SetY($y);
    $yStart = $y;
    $yLeft = renderCheckColumn($pdf, $leftCol, $post, $x, $colW, $lh);
    $pdf->SetY($yStart);
    $yRight = renderCheckColumn($pdf, $rightCol, $post, $x + $colW, $colW, $lh);
    $pdf->SetY($yRight);
    $yRight = renderDriverSafetySection($pdf, $post, $driverSafetyFields, $x + $colW, $colW, $lh);
    $y = max($yLeft, $yRight) + 2;

    // Footer: Declaration, Signatures, Date Submitted, Log Book
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetXY($x, $y);
    $fullWidth = 195;
    $pdf->MultiCell($fullWidth, 3.5, "Participants Declaration: [X] I agree – I hereby stipulate that the above vehicle meets the regulations for the event.", 0, 'L');
    $y = $pdf->GetY() + 1;

    $sigW = 50;
    $sigH = 18;
    $pdf->Cell(0, 3, "Entrant's Signature", 0, 1);
    $data = $post['sig_entrant'] ?? '';
    if ($data && preg_match('/^data:image\/png;base64,(.+)$/', $data, $m)) {
        $img = base64_decode($m[1]);
        if ($img !== false) {
            $tmp = tempnam(sys_get_temp_dir(), 'sig');
            file_put_contents($tmp, $img);
            $pdf->Image($tmp, $x, $pdf->GetY(), $sigW, $sigH, 'PNG');
            @unlink($tmp);
        }
    }
    $pdf->SetY($pdf->GetY() + $sigH + 1);
    $pdf->Cell(0, 3, "Driver's Signature", 0, 1);
    $data = $post['sig_driver'] ?? '';
    if ($data && preg_match('/^data:image\/png;base64,(.+)$/', $data, $m)) {
        $img = base64_decode($m[1]);
        if ($img !== false) {
            $tmp = tempnam(sys_get_temp_dir(), 'sig');
            file_put_contents($tmp, $img);
            $pdf->Image($tmp, $x, $pdf->GetY(), $sigW, $sigH, 'PNG');
            @unlink($tmp);
        }
    }
    $pdf->SetY($pdf->GetY() + $sigH + 1);
    $pdf->Cell(0, $lh, 'Date Submitted: ' . $dateSubmitted, 0, 1);
    $pdf->Cell(0, $lh, 'Vehicle Log Book Turned In: ' . (($post['logbook'] ?? '') === 'yes' ? 'Yes' : (($post['logbook'] ?? '') === 'no' ? 'No' : '')), 0, 1);
    $pdf->SetFont('helvetica', '', 6);
    $pdf->Cell(0, 4, 'Revised 2026', 0, 1);

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
