(function () {
    'use strict';

    const form = document.getElementById('techForm');
    const submitBtn = document.getElementById('submitBtn');
    const errorEl = document.getElementById('formError');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    const panels = document.querySelectorAll('.step-panel');
    const totalSteps = panels.length;

    let padEntrant, padDriver;
    const stepsInner = document.getElementById('stepsInner');
    const stepsContainer = document.getElementById('stepsContainer');

    function showStep(step) {
        const n = parseInt(step, 10);
        currentStep = n;
        panels.forEach(function (p) {
            p.classList.toggle('active', parseInt(p.dataset.step, 10) === n);
        });
        if (stepsInner && stepsContainer) {
            var stepWidth = stepsContainer.offsetWidth || document.documentElement.clientWidth || 300;
            var offset = (n - 1) * stepWidth;
            stepsInner.style.transform = 'translateX(-' + offset + 'px)';
        }
        progressBar.style.width = (n / totalSteps * 100) + '%';
        progressBar.setAttribute('aria-valuenow', n);
        progressText.textContent = 'Step ' + n + ' of ' + totalSteps;
        // Scroll to top so the new step content is visible (avoids disjointed feel with varying panel lengths)
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    var currentStep = 1;
    function initSteps() {
        showStep(1);
        window.addEventListener('resize', function () {
            showStep(currentStep);
        });
        document.querySelectorAll('.btn-next').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const panel = btn.closest('.step-panel');
                const next = parseInt(panel.dataset.step, 10) + 1;
                if (next <= totalSteps) showStep(next);
            });
        });
        document.querySelectorAll('.btn-prev').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const panel = btn.closest('.step-panel');
                const prev = parseInt(panel.dataset.step, 10) - 1;
                if (prev >= 1) showStep(prev);
            });
        });
    }

    function resizeCanvases(clearPads) {
        const canvasEntrant = document.getElementById('sigEntrant');
        const canvasDriver = document.getElementById('sigDriver');
        if (!canvasEntrant || !canvasDriver) return;
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        [canvasEntrant, canvasDriver].forEach(function (c) {
            var w = c.offsetWidth || c.getBoundingClientRect().width || 300;
            var h = c.offsetHeight || c.getBoundingClientRect().height || 120;
            if (w < 1 || h < 1) { w = 300; h = 120; }
            c.width = Math.floor(w * ratio);
            c.height = Math.floor(h * ratio);
            var ctx = c.getContext('2d');
            ctx.scale(ratio, ratio);
        });
        if (clearPads !== false && padEntrant) padEntrant.clear();
        if (clearPads !== false && padDriver) padDriver.clear();
    }

    function initSignatures() {
        const canvasEntrant = document.getElementById('sigEntrant');
        const canvasDriver = document.getElementById('sigDriver');
        if (!canvasEntrant || !canvasDriver || typeof SignaturePad === 'undefined') return;

        resizeCanvases();
        padEntrant = new SignaturePad(canvasEntrant, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)'
        });
        padDriver = new SignaturePad(canvasDriver, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)'
        });
        padEntrant.clear();
        padDriver.clear();

        padEntrant.addEventListener('endStroke', function () {
            if (!padEntrant.isEmpty()) {
                document.getElementById('sigEntrantData').value = padEntrant.toDataURL('image/png');
            }
        });
        padDriver.addEventListener('endStroke', function () {
            if (!padDriver.isEmpty()) {
                document.getElementById('sigDriverData').value = padDriver.toDataURL('image/png');
            }
        });

        window.addEventListener('resize', function () { resizeCanvases(); });
        window.addEventListener('orientationchange', function () {
            setTimeout(function () { resizeCanvases(); }, 100);
        });

        document.querySelectorAll('.clear-sig').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-for');
                if (id === 'sigEntrant') {
                    if (padEntrant) padEntrant.clear();
                    document.getElementById('sigEntrantData').value = '';
                }
                if (id === 'sigDriver') {
                    if (padDriver) padDriver.clear();
                    document.getElementById('sigDriverData').value = '';
                }
            });
        });

        canvasEntrant.addEventListener('touchstart', function () { canvasEntrant.focus(); }, { passive: true });
        canvasDriver.addEventListener('touchstart', function () { canvasDriver.focus(); }, { passive: true });

        document.addEventListener('touchstart', function (e) {
            if (e.target === canvasEntrant || e.target === canvasDriver) {
                if (e.cancelable) e.preventDefault();
            }
        }, { passive: false, capture: true });
        document.addEventListener('touchmove', function (e) {
            if (e.target === canvasEntrant || e.target === canvasDriver) {
                if (e.cancelable) e.preventDefault();
            }
        }, { passive: false, capture: true });
        document.addEventListener('touchend', function (e) {
            if (e.target === canvasEntrant || e.target === canvasDriver) {
                if (e.cancelable) e.preventDefault();
            }
        }, { passive: false, capture: true });
    }

    function captureSignatures() {
        if (padEntrant && !padEntrant.isEmpty()) {
            document.getElementById('sigEntrantData').value = padEntrant.toDataURL('image/png');
        }
        if (padDriver && !padDriver.isEmpty()) {
            document.getElementById('sigDriverData').value = padDriver.toDataURL('image/png');
        }
    }

    function serializeForm() {
        var data = {};
        for (var i = 0; i < form.elements.length; i++) {
            var el = form.elements[i];
            if (!el.name || el.name === 'website') continue;
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (el.checked) data[el.name] = el.value;
            } else {
                data[el.name] = el.value || '';
            }
        }
        var label = [data.car_number || '', data.car_make || '', data.class || ''].filter(Boolean).join(' - ') || 'Previous submission';
        return { formData: data, label: label };
    }

    function restoreForm(data) {
        var d = data.formData || data;
        for (var i = 0; i < form.elements.length; i++) {
            var el = form.elements[i];
            if (!el.name || el.name === 'website') continue;
            var val = d[el.name];
            if (el.type === 'checkbox') {
                el.checked = (val === '1' || val === el.value);
            } else if (el.type === 'radio') {
                el.checked = (val === el.value);
            } else if (el.tagName === 'SELECT') {
                el.value = val || '';
            } else {
                el.value = val || '';
            }
        }
        var sigEntrant = d.sig_entrant;
        var sigDriver = d.sig_driver;
        if (sigEntrant && padEntrant && typeof padEntrant.fromDataURL === 'function') {
            document.getElementById('sigEntrantData').value = sigEntrant;
            padEntrant.fromDataURL(sigEntrant);
        }
        if (sigDriver && padDriver && typeof padDriver.fromDataURL === 'function') {
            document.getElementById('sigDriverData').value = sigDriver;
            padDriver.fromDataURL(sigDriver);
        }
    }

    function validateForm() {
        captureSignatures();
        var seasonEl = form.querySelector('[name="season"]');
        if (seasonEl && (!seasonEl.value || seasonEl.value.trim() === '')) {
            return { valid: false, message: 'Please select Season.', step: 1, element: seasonEl };
        }
        var textRequired = ['entrant', 'car_make', 'car_model', 'car_colour', 'driver_team', 'car_number', 'competitor_email', 'class', 'engine_cc', 'engine_hp', 'car_weight'];
        var textLabels = { entrant: 'Entrant', car_make: 'Car Make', car_model: 'Car Model', car_colour: 'Car Colour', driver_team: 'Driver/Team Name', car_number: 'Car Number', competitor_email: 'Competitor Email', class: 'Class', engine_cc: 'Engine (CC)', engine_hp: 'Engine (HP)', car_weight: 'Car Weight' };
        for (var i = 0; i < textRequired.length; i++) {
            var name_ = textRequired[i];
            var el = form.querySelector('[name="' + name_ + '"]');
            if (!el) continue;
            var val = (el.value || '').trim();
            if (val === '') return { valid: false, message: 'Please fill in: ' + textLabels[name_], step: 1, element: el };
            if (name_ === 'competitor_email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                return { valid: false, message: 'Please enter a valid email address.', step: 1, element: el };
            }
        }
        var checkboxRequired = ['uv_0', 'uv_1', 'uv_2', 'uv_3', 'uv_4', 'wt_0', 'wt_1', 'ec_0', 'ec_1', 'ec_3', 'ec_5', 'ec_6', 'ec_7', 'ec_8', 'vi_1', 'vi_2', 'vi_3', 'vi_4', 've_0', 've_1', 've_2', 've_3', 've_4', 've_5', 've_6', 've_8', 've_11', 've_13', 'ft_2', 'ft_3'];
        var cbLabels = { uv_0: 'Steering linkage', uv_1: 'Suspension & shocks', uv_2: 'Wheel bearing condition', uv_3: 'Brakes & hoses', uv_4: 'Ball joints, rod ends, bushings', wt_0: 'Wheel and tire condition', wt_1: 'Meets class criteria', ec_0: 'Fuel pump, lines & fittings zero leaks', ec_1: 'Oil supply tank, oil lines security', ec_3: 'Coolant hose condition', ec_5: 'Battery terminal posts insulated', ec_6: 'Battery mount', ec_7: 'Wiring mounting and integrity', ec_8: 'Carburetion / fuel injection security', vi_1: 'Accessories properly mounted', vi_2: "Driver's seat securely mounted", vi_3: 'Rearview mirror', vi_4: 'Firewall and floor have no holes', ve_0: 'Front and Rear tow points', ve_1: 'Appearance and Markings', ve_2: 'Body panels secure', ve_3: 'Windshield & windows', ve_4: 'Headlights (Night and Ice events)', ve_5: 'Brake & tail lights as per class rules', ve_6: 'Exhaust system meets regulations', ve_8: 'Bumper condition/attachment', ve_11: 'Aero and Mud flaps secure', ve_13: 'Hood and Trunk fastened properly', ft_2: 'Firewall/bulkhead', ft_3: 'Fuel tank/fuel cell securely mounted' };
        for (var j = 0; j < checkboxRequired.length; j++) {
            var cbName = checkboxRequired[j];
            var cb = form.querySelector('input[name="' + cbName + '"]');
            if (!cb || !cb.checked) {
                return { valid: false, message: 'Please complete: ' + (cbLabels[cbName] || cbName), step: cbName.indexOf('vi_') === 0 || cbName.indexOf('ve_') === 0 || cbName.indexOf('ft_') === 0 ? 3 : 2, element: cb };
            }
        }
        var selectRequired = ['helmet_rating', 'suit_rating', 'head_neck_restraint'];
        var selLabels = { helmet_rating: 'Helmet-Rating', suit_rating: 'Suit-Rating', head_neck_restraint: 'Head & Neck Restraint' };
        for (var k = 0; k < selectRequired.length; k++) {
            var selName = selectRequired[k];
            var sel = form.querySelector('[name="' + selName + '"]');
            if (!sel || !sel.value || sel.value.trim() === '') {
                return { valid: false, message: 'Please select: ' + selLabels[selName], step: 4, element: sel };
            }
        }
        var sigEntrantVal = document.getElementById('sigEntrantData') ? document.getElementById('sigEntrantData').value : '';
        var sigDriverVal = document.getElementById('sigDriverData') ? document.getElementById('sigDriverData').value : '';
        if (!sigEntrantVal || sigEntrantVal.length < 50) {
            return { valid: false, message: 'Please sign as Entrant.', step: 4, element: document.getElementById('sigEntrant') };
        }
        if (!sigDriverVal || sigDriverVal.length < 50) {
            return { valid: false, message: 'Please sign as Driver.', step: 4, element: document.getElementById('sigDriver') };
        }
        var logbookChecked = form.querySelector('input[name="logbook"]:checked');
        if (!logbookChecked || (logbookChecked.value !== 'yes' && logbookChecked.value !== 'no')) {
            return { valid: false, message: 'Please indicate whether the Vehicle Log Book was turned in.', step: 5, element: form.querySelector('input[name="logbook"]') };
        }
        var declCheck = form.querySelector('input[name="declaration_agree"]');
        if (!declCheck || !declCheck.checked) {
            return { valid: false, message: 'You must agree to the Participants Declaration to submit.', step: 5, element: declCheck };
        }
        return { valid: true };
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (errorEl) errorEl.textContent = '';

        var result = validateForm();
        if (!result.valid) {
            if (errorEl) errorEl.textContent = result.message;
            showStep(result.step);
            if (result.element) {
                setTimeout(function () {
                    result.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (result.element.focus) result.element.focus();
                }, 350);
            }
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting…';

        var formData = new FormData(form);

        try {
            var saved = serializeForm();
            if (saved && saved.formData && Object.keys(saved.formData).length > 0) {
                localStorage.setItem('wcmatechsheet_lastForm', JSON.stringify(saved));
            }
        } catch (err) { /* ignore */ }

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
            .then(function (res) {
                return res.json().catch(function () {
                    return { success: false, error: 'Invalid response from server.' };
                });
            })
            .then(function (data) {
                if (data.success) {
                    try {
                        var saved = serializeForm();
                        if (saved && saved.formData && Object.keys(saved.formData).length > 0) {
                            localStorage.setItem('wcmatechsheet_lastForm', JSON.stringify(saved));
                        }
                    } catch (err) { /* ignore */ }
                    window.location.href = 'thank-you.php';
                } else {
                    if (errorEl) errorEl.textContent = data.error || 'Something went wrong.';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Tech Sheet';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            })
            .catch(function () {
                if (errorEl) errorEl.textContent = 'Something went wrong. Please try again.';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Tech Sheet';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
    });

    function initLoadPrompt() {
        var overlay = document.getElementById('loadPromptOverlay');
        var labelEl = document.getElementById('loadPromptLabel');
        var loadBtn = document.getElementById('loadPromptLoad');
        var freshBtn = document.getElementById('loadPromptFresh');
        if (!overlay || !labelEl || !loadBtn || !freshBtn) return;

        var stored = null;
        try {
            var raw = localStorage.getItem('wcmatechsheet_lastForm');
            if (raw) stored = JSON.parse(raw);
        } catch (err) { /* ignore */ }

        if (!stored || !stored.formData || Object.keys(stored.formData).length === 0) return;

        labelEl.textContent = stored.label || 'Previous submission';
        overlay.classList.add('visible');
        overlay.setAttribute('aria-hidden', 'false');

        function hidePrompt() {
            overlay.classList.remove('visible');
            overlay.setAttribute('aria-hidden', 'true');
        }

        loadBtn.addEventListener('click', function () {
            restoreForm(stored);
            hidePrompt();
        });

        freshBtn.addEventListener('click', function () {
            try { localStorage.removeItem('wcmatechsheet_lastForm'); } catch (err) { /* ignore */ }
            hidePrompt();
        });
    }

    initSteps();
    initSignatures();
    initLoadPrompt();
})();
