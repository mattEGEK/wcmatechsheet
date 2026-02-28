<?php
/**
 * Generates the Tech Sheet PDF matching the 2021 Race Tech Form layout.
 */

class PdfGenerator
{
    public static function generate(array $post, string $rootDir): string
    {
        $dateSubmitted = date('Y-m-d H:i');
        $logoPath = $rootDir . '/assets/images/WCMA-Logo.png';

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
        $fullWidth = 195;  // matches grey header width
        $checklistGap = 10;  // white space between col 2 and col 3
        $leftColWidth = 90;  // left section (cols 1-2)
        $rightColWidth = $fullWidth - $leftColWidth - $checklistGap;  // right section (cols 3-4), aligns with header

        // Header: WCMA logo + form title (logo only - no separate text per reference)
        $logoW = 25;
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, $x, $y, $logoW, 0, 'PNG');  // height 0 = auto aspect ratio
            $logoBottom = $pdf->getImageRBY();
        } else {
            $logoBottom = $y + 5;
        }
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY($x + $logoW + 2, $y);
        $pdf->Cell(0, 5, 'ANNUAL VEHICLE INSPECTION FORM', 0, 1);
        $y = max($logoBottom, $pdf->GetY()) + 0.5;
        $pdf->SetXY($x, $y);
        $pdf->SetFont('helvetica', '', 6);
        $pdf->MultiCell(0, 3, "It is the competitor's sole responsibility to ensure compliance to the regulations initially and at all times during the event. Inspections by the organizers, if any, do not imply compliance or any guarantee of vehicle safety.", 0, 'L');
        $y = $pdf->GetY() + 2;

        // Vehicle info - table with borders (two columns: left and right)
        $labelW = 28;
        $valueW = $colW - $labelW - 1;  // Left half value width
        $labelW2 = 28;
        $valueW2 = 195 - $colW - $labelW2;  // Right half value width
        $pdf->SetFont('helvetica', '', 7);
        $rows = [
            ['ENTRANT:', $post['entrant'] ?? '', 'DRIVER/TEAM NAME:', $post['driver_team'] ?? ''],
            ['CAR MAKE:', $post['car_make'] ?? '', 'CAR NUMBER:', $post['car_number'] ?? ''],
            ['CAR MODEL:', $post['car_model'] ?? '', 'ENGINE:', ($post['engine_cc'] ?? '') . ' (CC) ' . ($post['engine_hp'] ?? '') . ' (HP)'],
            ['CAR COLOUR:', $post['car_colour'] ?? '', 'CAR WEIGHT:', $post['car_weight'] ?? ''],
            ['CLASS:', $post['class'] ?? '', '', ''],
        ];
        if (!empty($post['season'] ?? '')) {
            $rows[] = ['SEASON:', $post['season'] ?? '', '', ''];
        }
        foreach ($rows as $row) {
            $hasRight = ($row[2] !== '');
            $bRight = $hasRight ? 1 : 0;  // borderless for empty right cells (below CAR WEIGHT)
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->SetXY($x, $y);
            $pdf->Cell($labelW, $lh, $row[0], 1, 0);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->Cell($valueW, $lh, $row[1], 1, 0);
            $pdf->SetXY($x + $colW, $y);
            if ($hasRight) {
                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->Cell($labelW2, $lh, $row[2], 1, 0);
                $pdf->SetFont('helvetica', '', 7);
                $pdf->Cell($valueW2, $lh, $row[3], 1, 0);
            } else {
                $pdf->Cell($labelW2 + $valueW2, $lh, $row[3], $bRight, 0);
            }
            $y += $lh;
        }
        $y += 1;

        // EACH ITEM MUST BE VERIFIED... - full-width row, center aligned, below entrant table
        $pdf->SetXY($x, $y);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(195, 5, 'EACH ITEM MUST BE VERIFIED BY THE COMPETITOR PRIOR TO THE TECHNICAL INSPECTION', 1, 1, 'C', true);
        $y = $pdf->GetY() + 1;

        // Checklist tables FIRST (per 2021 - signatures below tables)
        $seatbeltExpiry = trim($post['seatbelt_expiry'] ?? '');
        if ($seatbeltExpiry && preg_match('/^\d{4}-\d{2}$/', $seatbeltExpiry)) {
            $seatbeltExpiry = date('M Y', strtotime($seatbeltExpiry . '-01'));
        }
        $leftCol = [
            'UNDER VEHICLE' => ['uv_0' => 'Steering linkage', 'uv_1' => 'Suspension & shocks', 'uv_2' => 'Wheel bearing condition', 'uv_3' => 'Brakes & hoses', 'uv_4' => 'Ball joints, rod ends, bushings'],
            'WHEELS and TIRES' => ['wt_0' => 'Wheel and tire condition'],
            'ENGINE COMPARTMENT' => ['ec_8' => 'Carburetion / fuel injection security', 'ec_0' => 'Fuel pump, lines & fittings zero leaks', 'ec_1' => 'Oil supply tank, oil lines security', 'ec_2' => 'Oil catch tank (min. 1L)', 'ec_3' => 'Coolant hose condition', 'ec_4' => 'Coolant catch tank (min 1L)', 'ec_5' => 'Battery terminal posts insulated', 'ec_6' => 'Battery mount', 'ec_7' => 'Wiring mounting and integrity', 'vi_1' => 'Accessories properly mounted', 'wt_1' => 'Meets class criteria'],
            'VEHICLE INTERIOR' => ['vi_0' => 'Roll bar padding/roll cage integrity', 'vi_2' => "Driver's seat securely mounted", 'vi_3' => 'Rearview mirror', 'vi_4' => 'Firewall and floor have no holes', 'vi_5' => 'Window net/Arm restraints', 'vi_6' => 'Window net release mechanism', 'fire_ext' => 'Fire Extinguisher – Type / Age', 'seatbelt' => 'Seat belts (5 or 6 point) (Expiry date) ' . $seatbeltExpiry],
        ];
        $rightCol = [
            'VEHICLE EXTERIOR' => ['ve_0' => 'Front and Rear tow points', 've_1' => 'Appearance and Markings', 've_2' => 'Body panels secure', 've_3' => 'Windshield & windows', 've_4' => 'Headlights (Night and Ice events)', 've_5' => 'Brake & tail lights as per class rules', 've_6' => 'Exhaust system meets regulations', 've_7' => 'Window clips or Urethane', 've_8' => 'Bumper condition/attachment', 've_9' => 'Exterior mirrors (2)', 've_10' => 'Master switch - kills engine', 've_11' => 'Aero and Mud flaps secure', 've_12' => 'Rain lights/Rear facing light'],
            'FUEL TANK COMPARTMENT' => ['ft_1' => 'Surge tank safely mounted', 'ft_0' => 'Proper ventilation and check valves', 've_13' => 'Hood and Trunk fastened properly', 'ft_2' => 'Firewall/bulkhead', 'ft_3' => 'Fuel tank/fuel cell securely mounted'],
        ];
        $driverSafetyFields = [
            ['type' => 'dropdown', 'name' => 'helmet_rating', 'label' => 'Helmet-Rating'],
            ['type' => 'checkbox', 'name' => 'goggles_visor', 'label' => 'Goggles or visor'],
            ['type' => 'dropdown', 'name' => 'suit_rating', 'label' => 'Suit - Rating'],
            ['type' => 'checkbox', 'name' => 'underwear', 'label' => 'Underwear (if required)'],
            ['type' => 'dropdown', 'name' => 'shoes', 'label' => 'Shoes'],
            ['type' => 'dropdown', 'name' => 'socks', 'label' => 'Socks'],
            ['type' => 'dropdown', 'name' => 'gloves', 'label' => 'Gloves'],
            ['type' => 'dropdown', 'name' => 'balaclava', 'label' => 'Balaclava'],
            ['type' => 'dropdown', 'name' => 'head_neck_restraint', 'label' => 'Head & Neck Restraints'],
        ];

        $rightColStart = $x + $leftColWidth + $checklistGap;
        $yStart = $y;
        $yLeft = self::renderCheckColumn($pdf, $leftCol, $post, $x, $leftColWidth, $lh, $y);
        $pdf->SetY($yStart);
        $yRight = self::renderCheckColumn($pdf, $rightCol, $post, $rightColStart, $rightColWidth, $lh, $y);
        $pdf->SetY($yRight);
        $yBottom = self::renderDriverSafetySection($pdf, $post, $driverSafetyFields, $rightColStart, $rightColWidth, $lh);

        // Declaration and signatures BELOW tables (two-column layout, clean spacing per reference)
        $y = max($yLeft, $yBottom) + 5;  // increased whitespace between tables and declaration
        $pdf->SetXY($x, $y);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Write(3.5, "Participants Declaration: ");
        $pdf->SetFont('helvetica', 'I', 7);
        $pdf->Write(3.5, "I hereby stipulate that the above vehicle meets the regulations for the event.");
        $y = $pdf->GetY() + 5;  // increased whitespace between declaration and fields below

        $sigW = 50;
        $sigH = 18;
        $halfW = 97.5;

        // Row 1: Entrant's Signature (left) | Driver's Signature (right) - bold labels
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY($x, $y);
        $pdf->Cell(0, 3, "Entrant's Signature:", 0, 0);
        $pdf->SetXY($x + $halfW, $y);
        $pdf->Cell(0, 3, "Driver's Signature:", 0, 1);
        $y += 4;

        // Signature lines (or images) - compact, clean spacing
        $pdf->SetFont('helvetica', '', 7);
        $sigY = $y;
        $leftBottom = $sigY + $lh;
        $rightBottom = $sigY + $lh;
        $data = $post['sig_entrant'] ?? '';
        if ($data && preg_match('/^data:image\/png;base64,(.+)$/', $data, $m)) {
            $img = base64_decode($m[1]);
            if ($img !== false) {
                $tmp = tempnam(sys_get_temp_dir(), 'sig');
                file_put_contents($tmp, $img);
                $pdf->Image($tmp, $x, $sigY, $sigW, $sigH, 'PNG');
                @unlink($tmp);
                $pdf->SetXY($x, $sigY + $sigH);
                $pdf->Cell($sigW, $lh, '', 'B', 0);
                $leftBottom = $sigY + $sigH + $lh;
            }
        } else {
            $pdf->SetXY($x, $sigY);
            $pdf->Cell($sigW, $lh, '', 'B', 0);
        }
        $data = $post['sig_driver'] ?? '';
        if ($data && preg_match('/^data:image\/png;base64,(.+)$/', $data, $m)) {
            $img = base64_decode($m[1]);
            if ($img !== false) {
                $tmp = tempnam(sys_get_temp_dir(), 'sig');
                file_put_contents($tmp, $img);
                $pdf->Image($tmp, $x + $halfW, $sigY, $sigW, $sigH, 'PNG');
                @unlink($tmp);
                $pdf->SetXY($x + $halfW, $sigY + $sigH);
                $pdf->Cell($sigW, $lh, '', 'B', 1);
                $rightBottom = $sigY + $sigH + $lh;
            }
        } else {
            $pdf->SetXY($x + $halfW, $sigY);
            $pdf->Cell($sigW, $lh, '', 'B', 1);
        }
        $y = max($leftBottom, $rightBottom) + 3;  // clean white space before next row

        // Row 2: Vehicle Log Book (left) | Date Received (right) - balanced two-column, bold labels
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetXY($x, $y);
        $pdf->Cell(0, 3, 'Vehicle Log Book Turned In', 0, 0);
        $pdf->SetXY($x + $halfW, $y);
        $pdf->Cell(0, 3, 'Date Received', 0, 1);
        $y += 4;
        $logYes = ($post['logbook'] ?? '') === 'yes' ? '__X__' : '______';
        $logNo = ($post['logbook'] ?? '') === 'no' ? '__X__' : '______';
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY($x, $y);
        $pdf->Cell(0, $lh, 'Yes ' . $logYes . ' No ' . $logNo, 0, 0);
        $pdf->SetXY($x + $halfW, $y);
        $pdf->Cell(0, $lh, $dateSubmitted, 'B', 1);
        $pdf->SetFont('helvetica', '', 6);
        $pdf->Cell(0, 4, 'Revised 2026', 0, 1);

        return $pdf->Output('', 'S');
    }

    private static function renderCheckColumn($pdf, array $sections, array $post, float $x, float $colW, float $lh, float $yStart): float
    {
        $labelW = $colW - 28;  // Space for Competitor Indicates OK column
        $checkW = 28;
        $y = $yStart;
        foreach ($sections as $title => $items) {
            $y0 = $y;
            $pdf->SetFont('helvetica', 'B', 6);
            $pdf->SetXY($x, $y);
            $pdf->MultiCell($labelW, $lh, $title, 1, 'L', false, 0);
            $y1 = $pdf->GetY();
            $pdf->SetFont('helvetica', '', 4);
            $pdf->SetXY($x + $labelW, $y0);
            $pdf->MultiCell($checkW, $lh, 'Competitor Indicates OK', 1, 'C', false, 2);
            $y2 = $pdf->GetY();
            $y = max($y1, $y2);
            $pdf->SetFont('helvetica', '', 6);
            foreach ($items as $key => $label) {
                $checked = ' ';
                if ($key === 'seatbelt') {
                    $checked = !empty($post['seatbelt_expiry']) ? 'X' : ' ';
                } elseif (isset($post[$key]) && $post[$key]) {
                    $checked = 'X';
                }
                $pdf->SetXY($x, $y);
                $pdf->Cell($labelW, $lh, $label, 1, 0);
                $pdf->SetXY($x + $labelW, $y);
                $pdf->Cell($checkW, $lh, '[' . $checked . ']', 1, 0, 'C');
                $y += $lh;
            }
            $y += 1;
        }
        return $y;
    }

    private static function renderDriverSafetySection($pdf, array $post, array $fields, float $x, float $colW, float $lh): float
    {
        $labelW = $colW - 28;
        $checkW = 28;
        $y = $pdf->GetY();
        $y0 = $y;
        $pdf->SetFont('helvetica', 'B', 6);
        $pdf->SetXY($x, $y);
        $pdf->MultiCell($labelW, $lh, 'DRIVER SAFETY EQUIPMENT Tech Rep Approved', 1, 'L', false, 0);
        $y1 = $pdf->GetY();
        $pdf->SetFont('helvetica', '', 4);
        $pdf->SetXY($x + $labelW, $y0);
        $pdf->MultiCell($checkW, $lh, 'Competitor Indicates OK', 1, 'C', false, 2);
        $y = max($y1, $pdf->GetY());
        $pdf->SetFont('helvetica', '', 6);
        foreach ($fields as $field) {
            $checked = ' ';
            if ($field['type'] === 'checkbox' && !empty($post[$field['name']]) && $post[$field['name']]) {
                $checked = 'X';
            }
            $label = $field['type'] === 'checkbox'
                ? $field['label']
                : ($field['label'] . ': ' . trim($post[$field['name']] ?? ''));
            $pdf->SetXY($x, $y);
            $pdf->Cell($labelW, $lh, $label, 1, 0);
            $pdf->SetXY($x + $labelW, $y);
            $pdf->Cell($checkW, $lh, '[' . $checked . ']', 1, 0, 'C');
            $y += $lh;
        }
        return $y;
    }
}
