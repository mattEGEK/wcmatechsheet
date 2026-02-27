(function () {
    'use strict';

    const form = document.getElementById('techForm');
    const submitBtn = document.getElementById('submitBtn');
    const errorEl = document.getElementById('submitError');
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
        if (n === 4 && padEntrant && padDriver) {
            reinitSignatures();
        }
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

    function reinitSignatures() {
        if (!padEntrant || !padDriver) return;
        var dataEntrant = padEntrant.isEmpty() ? null : padEntrant.toDataURL('image/png');
        var dataDriver = padDriver.isEmpty() ? null : padDriver.toDataURL('image/png');
        padEntrant.off();
        padDriver.off();
        resizeCanvases(false);
        padEntrant.on();
        padDriver.on();
        if (dataEntrant) padEntrant.fromDataURL(dataEntrant);
        if (dataDriver) padDriver.fromDataURL(dataDriver);
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

        window.addEventListener('resize', function () { resizeCanvases(); });
        window.addEventListener('orientationchange', function () {
            setTimeout(function () { resizeCanvases(); }, 100);
        });

        document.querySelectorAll('.clear-sig').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-for');
                if (id === 'sigEntrant' && padEntrant) padEntrant.clear();
                if (id === 'sigDriver' && padDriver) padDriver.clear();
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

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        errorEl.textContent = '';

        if (!padEntrant || padEntrant.isEmpty()) {
            errorEl.textContent = 'Please sign as Entrant.';
            showStep(4);
            document.getElementById('sigEntrant').scrollIntoView({ behavior: 'smooth' });
            return;
        }
        if (!padDriver || padDriver.isEmpty()) {
            errorEl.textContent = 'Please sign as Driver.';
            showStep(4);
            document.getElementById('sigDriver').scrollIntoView({ behavior: 'smooth' });
            return;
        }

        captureSignatures();
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting…';

        const formData = new FormData(form);

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
                    window.location.href = 'thank-you.php';
                } else {
                    errorEl.textContent = data.error || 'Something went wrong.';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit Tech Sheet';
                }
            })
            .catch(function () {
                errorEl.textContent = 'Something went wrong. Please try again.';
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Tech Sheet';
            });
    });

    initSteps();
    initSignatures();
})();
