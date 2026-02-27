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

    function showStep(step) {
        const n = parseInt(step, 10);
        panels.forEach(function (p) {
            p.classList.toggle('active', parseInt(p.dataset.step, 10) === n);
        });
        progressBar.style.width = (n / totalSteps * 100) + '%';
        progressBar.setAttribute('aria-valuenow', n);
        progressText.textContent = 'Step ' + n + ' of ' + totalSteps;
    }

    function initSteps() {
        showStep(1);
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

    function initCheckAll() {
        const btn = document.getElementById('checkAllBtn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            document.querySelectorAll('[data-verify] input[type="checkbox"]').forEach(function (cb) {
                cb.checked = true;
            });
            btn.textContent = 'All Verified';
            btn.disabled = true;
        });
    }

    function initSignatures() {
        const canvasEntrant = document.getElementById('sigEntrant');
        const canvasDriver = document.getElementById('sigDriver');
        if (!canvasEntrant || !canvasDriver || typeof SignaturePad === 'undefined') return;

        padEntrant = new SignaturePad(canvasEntrant, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)'
        });
        padDriver = new SignaturePad(canvasDriver, {
            backgroundColor: 'rgb(255, 255, 255)',
            penColor: 'rgb(0, 0, 0)'
        });

        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        [canvasEntrant, canvasDriver].forEach(function (c) {
            const ctx = c.getContext('2d');
            const rect = c.getBoundingClientRect();
            c.width = rect.width * ratio;
            c.height = rect.height * ratio;
            ctx.scale(ratio, ratio);
        });
        padEntrant.clear();
        padDriver.clear();

        document.querySelectorAll('.clear-sig').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-for');
                if (id === 'sigEntrant' && padEntrant) padEntrant.clear();
                if (id === 'sigDriver' && padDriver) padDriver.clear();
            });
        });
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
    initCheckAll();
    initSignatures();
})();
