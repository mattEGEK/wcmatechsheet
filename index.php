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
        <h1>UCTA GO RACING!</h1>
        <p class="subtitle">VEHICLE INSPECTION FORM</p>
        <p class="disclaimer">It is the competitor's sole responsibility to ensure compliance to the regulations initially and at all times during the event. Inspections by the organizers, if any, do not imply compliance or any guarantee of vehicle safety.</p>
    </header>

    <form id="techForm" action="submit.php" method="post">
        <!-- Honeypot -->
        <div class="hp" aria-hidden="true">
            <label for="website">Leave blank</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <section class="form-section" id="vehicle-info">
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
        </section>

        <section class="form-section" id="under-vehicle">
            <h2>Under Vehicle <span class="verify-hint">(Indicate OK)</span></h2>
            <div class="checkgroup">
                <?php
                $underVehicle = ['Steering linkage', 'Suspension & shocks', 'Wheel bearing condition', 'Brakes & hoses', 'Ball joints, rod ends, bushings'];
                foreach ($underVehicle as $i => $label) {
                    $name = 'uv_' . $i;
                    echo "<label class='cb'><input type='checkbox' name='{$name}' value='1'> {$label}</label>";
                }
                ?>
            </div>
        </section>

        <section class="form-section" id="wheels-tires">
            <h2>Wheels and Tires</h2>
            <div class="checkgroup">
                <label class="cb"><input type="checkbox" name="wt_0" value="1"> Wheel and tire condition</label>
                <label class="cb"><input type="checkbox" name="wt_1" value="1"> Meets class criteria</label>
            </div>
        </section>

        <section class="form-section" id="engine-compartment">
            <h2>Engine Compartment</h2>
            <div class="checkgroup">
                <?php
                $engine = ['Fuel pump, lines & fittings zero leaks', 'Oil supply tank, oil lines security', 'Oil catch tank (min. 1L)', 'Coolant hose condition', 'Coolant catch tank (min 1L)', 'Battery terminal posts insulated', 'Battery mount', 'Wiring mounting and integrity', 'Carburetion / fuel injection security'];
                foreach ($engine as $i => $label) {
                    $name = 'ec_' . $i;
                    echo "<label class='cb'><input type='checkbox' name='{$name}' value='1'> {$label}</label>";
                }
                ?>
            </div>
        </section>

        <section class="form-section" id="vehicle-interior">
            <h2>Vehicle Interior</h2>
            <div class="checkgroup">
                <label class="cb"><input type="checkbox" name="vi_0" value="1"> Roll bar padding/roll cage integrity</label>
                <label class="cb"><input type="checkbox" name="vi_1" value="1"> Accessories properly mounted</label>
                <label class="cb"><input type="checkbox" name="vi_2" value="1"> Driver's seat securely mounted</label>
                <label class="cb"><input type="checkbox" name="vi_3" value="1"> Rearview mirror</label>
                <label class="cb"><input type="checkbox" name="vi_4" value="1"> Firewall and floor have no holes</label>
                <label class="cb"><input type="checkbox" name="vi_5" value="1"> Window net/Arm restraints</label>
                <label class="cb"><input type="checkbox" name="vi_6" value="1"> Window net release mechanism</label>
                <label class="cb row">
                    <span>Fire Extinguisher</span>
                    <span><input type="text" name="fire_ext_type" placeholder="Type"> <input type="text" name="fire_ext_age" placeholder="Age"></span>
                </label>
                <label class="cb row">
                    <span>Seat belts (5 or 6 point) Expiry date</span>
                    <span><input type="date" name="seatbelt_expiry"></span>
                </label>
            </div>
        </section>

        <section class="form-section" id="vehicle-exterior">
            <h2>Vehicle Exterior <span class="verify-hint">(Indicate OK)</span></h2>
            <div class="checkgroup">
                <?php
                $exterior = ['Front and Rear tow points', 'Appearance and Markings', 'Body panels secure', 'Windshield & windows', 'Headlights (Night and Ice events)', 'Brake & tail lights as per class rules', 'Exhaust system meets regulations', 'Window clips or Urethane', 'Bumper condition/attachment', 'Exterior mirrors (2)', 'Master switch - kills engine', 'Aero and Mud flaps secure', 'Rain lights/Rear facing light', 'Hood and Trunk fastened properly'];
                foreach ($exterior as $i => $label) {
                    $name = 've_' . $i;
                    echo "<label class='cb'><input type='checkbox' name='{$name}' value='1'> {$label}</label>";
                }
                ?>
            </div>
        </section>

        <section class="form-section" id="fuel-tank">
            <h2>Fuel Tank Compartment</h2>
            <div class="checkgroup">
                <label class="cb"><input type="checkbox" name="ft_0" value="1"> Proper ventilation and check valves</label>
                <label class="cb"><input type="checkbox" name="ft_1" value="1"> Surge tank safely mounted</label>
                <label class="cb"><input type="checkbox" name="ft_2" value="1"> Firewall/bulkhead</label>
                <label class="cb"><input type="checkbox" name="ft_3" value="1"> Fuel tank/fuel cell securely mounted</label>
            </div>
        </section>

        <section class="form-section" id="driver-safety">
            <h2>Driver Safety Equipment</h2>
            <p class="tech-fills">Tech fills at track</p>
        </section>

        <section class="form-section" id="signatures">
            <h2>Signatures</h2>
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
        </section>

        <section class="form-section" id="footer-fields">
            <div class="grid-2">
                <label>Date Received <input type="date" name="date_received" required></label>
                <label class="radio-group">
                    Vehicle Log Book Turned In
                    <span><input type="radio" name="logbook" value="yes"> Yes <input type="radio" name="logbook" value="no"> No</span>
                </label>
            </div>
        </section>

        <p class="declaration">Participants Declaration: I hereby stipulate that the above vehicle meets the regulations for the event.</p>

        <div class="submit-wrap">
            <button type="submit" id="submitBtn">Submit Tech Sheet</button>
            <p id="submitError" class="error" aria-live="polite"></p>
        </div>
    </form>

    <footer>Revised 2021</footer>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script src="assets/js/form.js"></script>
</body>
</html>
