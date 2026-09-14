/**
 * Treatments page — "Am I eligible?" BMI check.
 *
 * A service-eligibility indication, not a clinical decision and not a
 * drug-efficacy projection: it names no medicine and never estimates weight
 * loss. Thresholds come from the page (data-bmi-min / data-bmi-full), which
 * the template reads from the same option the assessment plugin uses, so the
 * two can't drift apart. The full assessment — and a prescriber — decide.
 *
 * Nothing is sent anywhere; the calculation is local and nothing is stored.
 */
(function () {
    'use strict';

    var root = document.getElementById('bmiCheck');
    var form = document.getElementById('bmiForm');
    var result = document.getElementById('bmiResult');
    var errorEl = document.getElementById('bmiError');
    if (!root || !form || !result) return;

    var BMI_MIN  = parseFloat(root.getAttribute('data-bmi-min'))  || 27;
    var BMI_FULL = parseFloat(root.getAttribute('data-bmi-full')) || 30;
    var CTA_URL     = root.getAttribute('data-cta') || '/weight-loss-eligibility/';
    var CONTACT_URL = root.getAttribute('data-contact') || '/contact/';

    var units = 'metric';
    var $ = function (id) { return document.getElementById(id); };

    /* ---- Units toggle ---- */
    var unitButtons = root.querySelectorAll('.tr-unit-btn');
    var panels = root.querySelectorAll('[data-units-panel]');

    function setUnits(next) {
        units = next;
        unitButtons.forEach(function (b) {
            var on = b.getAttribute('data-units') === next;
            b.classList.toggle('active', on);
            b.classList.toggle('text-gray-500', !on);
            b.setAttribute('aria-pressed', String(on));
        });
        panels.forEach(function (p) {
            p.style.display = (p.getAttribute('data-units-panel') === next) ? '' : 'none';
        });
        hideError();
        result.classList.add('hidden');
    }

    unitButtons.forEach(function (b) {
        b.addEventListener('click', function () { setUnits(b.getAttribute('data-units')); });
    });

    /* ---- Helpers ---- */
    function num(id) {
        var el = $(id);
        var v = el ? parseFloat(el.value) : NaN;
        return isNaN(v) ? null : v;
    }

    function showError(msg) {
        if (!errorEl) return;
        errorEl.textContent = msg;
        errorEl.classList.remove('hidden');
    }

    function hideError() {
        if (!errorEl) return;
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
    }

    function readMeasurements() {
        var heightM, weightKg;

        if (units === 'metric') {
            var cm = num('bmiHeightCm');
            var kg = num('bmiWeightKg');
            if (cm === null || kg === null) return { error: 'Please enter both your height and your weight.' };
            if (cm < 120 || cm > 230) return { error: 'Please enter a height between 120 and 230 cm.' };
            if (kg < 30 || kg > 300)  return { error: 'Please enter a weight between 30 and 300 kg.' };
            heightM = cm / 100;
            weightKg = kg;
        } else {
            var ft = num('bmiHeightFt');
            var inch = num('bmiHeightIn') || 0;
            var st = num('bmiWeightSt');
            var lb = num('bmiWeightLb') || 0;
            if (ft === null || st === null) return { error: 'Please enter your height (ft) and weight (st).' };
            var totalIn = ft * 12 + inch;
            var totalLb = st * 14 + lb;
            if (totalIn < 47 || totalIn > 91)  return { error: 'Please enter a height between 3ft 11in and 7ft 7in.' };
            if (totalLb < 66 || totalLb > 661) return { error: 'Please enter a weight between 4st 10lb and 47st.' };
            heightM = totalIn * 0.0254;
            weightKg = totalLb * 0.45359237;
        }

        return { heightM: heightM, weightKg: weightKg };
    }

    function arrow() {
        return '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>';
    }

    function button(href, label, primary) {
        var cls = primary
            ? 'inline-flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold px-5 py-3 rounded-xl transition-all mt-4'
            : 'inline-flex items-center justify-center gap-2 border-2 border-gray-200 hover:border-purple-300 text-gray-800 text-sm font-semibold px-5 py-3 rounded-xl transition-all mt-4';
        return '<a href="' + href + '" class="' + cls + '">' + label + ' ' + arrow() + '</a>';
    }

    /* ---- Render ---- */
    function render(bmi) {
        var value = bmi.toFixed(1);
        var tone, body, cta;

        if (bmi >= BMI_FULL) {
            tone = 'likely';
            body = 'You may be eligible for prescription treatment. The next step is a short assessment, reviewed by a UK-registered prescriber.';
            cta  = button(CTA_URL, 'Start your assessment', true);
        } else if (bmi >= BMI_MIN) {
            tone = 'conditional';
            body = 'You may be eligible if you also have a weight-related health condition — for example high blood pressure, type 2 diabetes or sleep apnoea. The assessment will ask about this.';
            cta  = button(CTA_URL, 'Start your assessment', true);
        } else {
            tone = 'below';
            body = 'Prescription weight-management treatment is usually only considered at a BMI of ' + BMI_MIN + ' or above. If you would like to talk it through, our pharmacy team can help.';
            cta  = button(CONTACT_URL, 'Talk to our team', false);
        }

        result.innerHTML =
            '<div class="tr-bmi-result tr-bmi-result--' + tone + '">' +
                '<div class="text-[11px] font-bold uppercase tracking-[0.14em] text-gray-500 mb-2">Your BMI</div>' +
                '<div class="tr-bmi-value">' + value + '</div>' +
                '<p class="text-sm text-gray-700 leading-relaxed">' + body + '</p>' +
                cta +
            '</div>';
        result.classList.remove('hidden');
    }

    /* ---- Submit ---- */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        hideError();

        var m = readMeasurements();
        if (m.error) { showError(m.error); result.classList.add('hidden'); return; }

        var bmi = m.weightKg / (m.heightM * m.heightM);
        if (!isFinite(bmi) || bmi <= 0) { showError('Please check the numbers you entered.'); return; }

        render(bmi);
    });

    // Typing again after a result: clear the old result so it can't mislead.
    form.addEventListener('input', function () {
        result.classList.add('hidden');
        hideError();
    });
})();
