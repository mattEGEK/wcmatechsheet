<?php
$config = file_exists(__DIR__ . '/config.php')
    ? require __DIR__ . '/config.php'
    : require __DIR__ . '/config.example.php';
$siteName = $config['site_name'] ?? 'NASCC Tech Sheet';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?> - Vehicle Inspection Form</title>
    <link rel="stylesheet" href="assets/css/form.css">
</head>
<body>
    <header>
        <div class="header-brand">
            <img src="assets/images/WCMA-Logo.png" alt="WCMA" class="logo">
            <div>
                <h1>WCMA Go Racing!</h1>
                <p class="subtitle">VEHICLE INSPECTION FORM</p>
            </div>
        </div>
        <p class="disclaimer">It is the competitor's sole responsibility to ensure compliance to the regulations initially and at all times during the event. Inspections by the organizers, if any, do not imply compliance or any guarantee of vehicle safety.</p>
    </header>

    <form id="techForm" action="submit.php" method="post">
        <!-- Honeypot -->
        <div class="hp" aria-hidden="true">
            <label for="website">Leave blank</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="progress-wrap">
            <div class="progress-bar" role="progressbar" aria-valuenow="1" aria-valuemin="1" aria-valuemax="5" id="progressBar"></div>
            <p class="progress-text" id="progressText">Step 1 of 5</p>
        </div>

        <div class="steps">
            <!-- Step 1: Vehicle Info -->
            <section class="form-section step-panel" data-step="1">
                <h2>Vehicle Information</h2>
                <div class="grid-2">
                    <label>Entrant <input type="text" name="entrant" required></label>
                    <label>Car Make <input type="text" name="car_make" required></label>
                    <label>Car Model <input type="text" name="car_model" required></label>
                    <label>Car Colour <input type="text" name="car_colour" required></label>
                    <label>Driver/Team Name <input type="text" name="driver_team" required></label>
                    <label>Car Number <input type="text" name="car_number" required></label>
                    <label>Class <input type="text" name="class"></label>
                    <label>Engine (CC) <input type="text" name="engine_cc" inputmode="numeric"></label>
                    <label>Engine (HP) <input type="text" name="engine_hp" inputmode="numeric"></label>
                    <label>Car Weight <input type="text" name="car_weight"></label>
                    <label class="full">Competitor Email <input type="email" name="competitor_email" required placeholder="For your copy of the PDF"></label>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn-next">Next</button>
                </div>
            </section>

            <!-- Step 2: Under Vehicle, Wheels, Engine -->
            <section class="form-section step-panel" data-step="2">
                <h2>Under Vehicle, Wheels & Tires, Engine</h2>
                <div class="checkgroup" data-verify>
                    <?php
                    $step2 = [
                        'uv_0' => 'Steering linkage', 'uv_1' => 'Suspension & shocks', 'uv_2' => 'Wheel bearing condition',
                        'uv_3' => 'Brakes & hoses', 'uv_4' => 'Ball joints, rod ends, bushings',
                        'wt_0' => 'Wheel and tire condition', 'wt_1' => 'Meets class criteria',
                        'ec_0' => 'Fuel pump, lines & fittings zero leaks', 'ec_1' => 'Oil supply tank, oil lines security',
                        'ec_2' => 'Oil catch tank (min. 1L)', 'ec_3' => 'Coolant hose condition', 'ec_4' => 'Coolant catch tank (min 1L)',
                        'ec_5' => 'Battery terminal posts insulated', 'ec_6' => 'Battery mount', 'ec_7' => 'Wiring mounting and integrity',
                        'ec_8' => 'Carburetion / fuel injection security'
                    ];
                    foreach ($step2 as $name => $label) {
                        echo "<label class='cb'><input type='checkbox' name='{$name}' value='1'> {$label}</label>";
                    }
                    ?>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn-prev">Back</button>
                    <button type="button" class="btn-next">Next</button>
                </div>
            </section>

            <!-- Step 3: Interior, Exterior, Fuel Tank -->
            <section class="form-section step-panel" data-step="3">
                <h2>Interior, Exterior & Fuel Tank</h2>
                <div class="checkgroup" data-verify>
                    <?php
                    $step3 = [
                        'vi_0' => 'Roll bar padding/roll cage integrity', 'vi_1' => 'Accessories properly mounted',
                        'vi_2' => "Driver's seat securely mounted", 'vi_3' => 'Rearview mirror', 'vi_4' => 'Firewall and floor have no holes',
                        'vi_5' => 'Window net/Arm restraints', 'vi_6' => 'Window net release mechanism',
                        've_0' => 'Front and Rear tow points', 've_1' => 'Appearance and Markings', 've_2' => 'Body panels secure',
                        've_3' => 'Windshield & windows', 've_4' => 'Headlights (Night and Ice events)',
                        've_5' => 'Brake & tail lights as per class rules', 've_6' => 'Exhaust system meets regulations',
                        've_7' => 'Window clips or Urethane', 've_8' => 'Bumper condition/attachment', 've_9' => 'Exterior mirrors (2)',
                        've_10' => 'Master switch - kills engine', 've_11' => 'Aero and Mud flaps secure',
                        've_12' => 'Rain lights/Rear facing light', 've_13' => 'Hood and Trunk fastened properly',
                        'ft_0' => 'Proper ventilation and check valves', 'ft_1' => 'Surge tank safely mounted',
                        'ft_2' => 'Firewall/bulkhead', 'ft_3' => 'Fuel tank/fuel cell securely mounted'
                    ];
                    foreach ($step3 as $name => $label) {
                        echo "<label class='cb'><input type='checkbox' name='{$name}' value='1'> {$label}</label>";
                    }
                    ?>
                </div>
                <label class="cb row">
                    <span>Fire Extinguisher</span>
                    <span><input type="text" name="fire_ext_type" placeholder="Type"> <input type="text" name="fire_ext_age" placeholder="Age"></span>
                </label>
                <label class="cb row">
                    <span>Seat belts (5 or 6 point) Expiry date</span>
                    <span><input type="date" name="seatbelt_expiry"></span>
                </label>
                <div class="step-actions">
                    <button type="button" class="btn-prev">Back</button>
                    <button type="button" class="btn-next">Next</button>
                </div>
            </section>

            <!-- Step 4: Driver Safety (tech fills), Signatures -->
            <section class="form-section step-panel" data-step="4">
                <h2>Driver Safety & Signatures</h2>
                <p class="tech-fills">Driver Safety Equipment – Tech fills at track</p>
                <div class="sig-group">
                    <div class="sig-box">
                        <label>Entrant's Signature</label>
                        <canvas id="sigEntrant" width="300" height="120"></canvas>
                        <input type="hidden" name="sig_entrant" id="sigEntrantData">
                        <button type="button" class="clear-sig" data-for="sigEntrant">Clear</button>
                    </div>
                    <div class="sig-box">
                        <label>Driver's Signature</label>
                        <canvas id="sigDriver" width="300" height="120"></canvas>
                        <input type="hidden" name="sig_driver" id="sigDriverData">
                        <button type="button" class="clear-sig" data-for="sigDriver">Clear</button>
                    </div>
                    <div class="sig-box placeholder">
                        <label>Tech Representative's Signature</label>
                        <p>Tech fills at track</p>
                    </div>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn-prev">Back</button>
                    <button type="button" class="btn-next">Next</button>
                </div>
            </section>

            <!-- Step 5: Review, Log Book, Submit -->
            <section class="form-section step-panel" data-step="5">
                <h2>Review & Submit</h2>
                <label class="radio-group">
                    Vehicle Log Book Turned In
                    <span><input type="radio" name="logbook" value="yes"> Yes <input type="radio" name="logbook" value="no"> No</span>
                </label>
                <p class="declaration">Participants Declaration: I hereby stipulate that the above vehicle meets the regulations for the event.</p>
                <div class="check-all-wrap">
                    <button type="button" id="checkAllBtn">Check All Verification Items</button>
                </div>
                <div class="step-actions">
                    <button type="button" class="btn-prev">Back</button>
                    <button type="submit" id="submitBtn">Submit Tech Sheet</button>
                </div>
                <p id="submitError" class="error" aria-live="polite"></p>
            </section>
        </div>
    </form>

    <footer>Revised 2021</footer>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script src="assets/js/form.js"></script>
</body>
</html>
