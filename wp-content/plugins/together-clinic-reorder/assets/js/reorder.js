(function () {
	'use strict';

	var cfg = window.tcReorder || {};

	var state = {
		currentScreen: 1,
		screenHistory: [],
		assessmentId: '',
		isSubmitting: false,
		data: {
			firstName: (cfg.prefill && cfg.prefill.firstName) || '',
			lastName:  (cfg.prefill && cfg.prefill.lastName)  || '',
			email:     (cfg.prefill && cfg.prefill.email)     || '',
			dob: '',
			currentMedication: (cfg.prefill && cfg.prefill.previousMedication) || '',
			currentDose:       (cfg.prefill && cfg.prefill.previousDose)       || '',
			selectedDose: '',
			currentWeight: '',
			hasLostWeight: '',
			appetiteLasting: '',
			hasSideEffects: '',
			healthChanged: '',
			newMedications: '',
			newMedicationsList: '',
			couldBePregnant: '',
			wantsClinicalSupport: ''
		},
		agreementChecks: [false, false, false, false, false]
	};

	function root() { return document.getElementById('tc-reorder-root'); }
	function $(id) { return document.getElementById(id); }
	function $$(selector) { return root().querySelectorAll(selector); }

	function showScreen(id) {
		$$('.screen').forEach(function (s) { s.classList.remove('active'); });
		var el = root().querySelector('[data-screen="' + id + '"]');
		if (el) {
			el.classList.add('active');
			state.currentScreen = id;
			if (id === 12) renderFinalScreen();
		}
		window.scrollTo(0, 0);
	}

	function pushScreen(id) {
		state.screenHistory.push(state.currentScreen);
		showScreen(id);
	}

	function nextScreen() {
		var current = state.currentScreen;
		state.screenHistory.push(current);
		showScreen(current + 1);
	}

	function previousScreen() {
		if (state.screenHistory.length > 0) {
			showScreen(state.screenHistory.pop());
		}
	}

	function setCookie(assessmentId) {
		try {
			var payload = JSON.stringify({ assessment_id: assessmentId });
			document.cookie = (cfg.cookieName || 'tc_reorder_data') + '=' + encodeURIComponent(payload) + '; max-age=' + (cfg.cookieMaxAge || 86400) + '; path=/; samesite=lax' + (location.protocol === 'https:' ? '; secure' : '');
		} catch (e) {
			console.warn('[tc-reorder] cookie set failed', e);
		}
	}

	function ajax(action, data) {
		var url = (cfg.ajaxUrl || '/wp-admin/admin-ajax.php') + '?action=' + encodeURIComponent(action) + '&nonce=' + encodeURIComponent(cfg.nonce || '');
		return fetch(url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-TC-Nonce': cfg.nonce || '' },
			body: JSON.stringify(data || {})
		}).then(function (resp) {
			return resp.json().then(function (body) {
				if (!resp.ok || !body || body.success !== true) {
					var msg = (body && body.data && body.data.message) || 'Request failed';
					var err = new Error(msg);
					err.status = resp.status;
					err.body = body;
					throw err;
				}
				return body.data || {};
			});
		});
	}

	function updateAgreementButton() {
		var btn = $('btn-screen-1');
		var counter = $('agreement-counter');
		var checked = state.agreementChecks.filter(Boolean).length;
		if (btn) btn.disabled = checked !== 5;
		if (counter) counter.textContent = checked + ' of 5 confirmed';
	}

	function setupAgreement() {
		$$('.agreement-checkbox').forEach(function (cb) {
			cb.addEventListener('change', function () {
				var i = parseInt(this.getAttribute('data-index'), 10);
				state.agreementChecks[i] = this.checked;
				updateAgreementButton();
			});
		});
	}

	function handleAgreeContinue() {
		if (!state.agreementChecks.every(Boolean)) return;

		var btn = $('btn-screen-1');
		if (btn) { btn.disabled = true; btn.textContent = 'Loading...'; }

		ajax('tc_reorder_save_partial', {}).then(function (data) {
			state.assessmentId = data.assessment_id || '';
			if (btn) btn.textContent = 'Continue to My Assessment →';
			nextScreen();
		}).catch(function (e) {
			if (btn) { btn.disabled = false; btn.textContent = 'Continue to My Assessment →'; }
			alert(e.message || 'Could not start the reorder. Please try again.');
		});
	}

	function setupDobAutoAdvance() {
		var d = $('dob-day'), m = $('dob-month'), y = $('dob-year');
		if (!d || !m || !y) return;
		[d, m, y].forEach(function (el) {
			el.addEventListener('input', function () { this.value = this.value.replace(/\D/g, ''); });
		});
		d.addEventListener('input', function () { if (this.value.length >= 2) m.focus(); });
		m.addEventListener('input', function () { if (this.value.length >= 2) y.focus(); });
	}

	function saveDOB() {
		var day = ($('dob-day').value || '').trim();
		var month = ($('dob-month').value || '').trim();
		var year = ($('dob-year').value || '').trim();
		var err = $('dob-error');

		if (!day || !month || !year) {
			err.textContent = 'Please enter your full date of birth';
			err.classList.remove('hidden');
			return;
		}

		var d = parseInt(day, 10), mo = parseInt(month, 10), y = parseInt(year, 10);
		var thisYear = new Date().getFullYear();
		if (isNaN(d) || isNaN(mo) || isNaN(y) || d < 1 || d > 31 || mo < 1 || mo > 12 || y < 1900 || y > thisYear) {
			err.textContent = 'Please enter a valid date';
			err.classList.remove('hidden');
			return;
		}

		var iso = y + '-' + (mo < 10 ? '0' + mo : mo) + '-' + (d < 10 ? '0' + d : d);
		var dobDate = new Date(iso + 'T00:00:00');
		if (isNaN(dobDate.getTime()) || dobDate.getDate() !== d || (dobDate.getMonth() + 1) !== mo) {
			err.textContent = "That date doesn't exist (please check the day and month)";
			err.classList.remove('hidden');
			return;
		}

		var today = new Date();
		if (dobDate > today) {
			err.textContent = 'Date of birth cannot be in the future';
			err.classList.remove('hidden');
			return;
		}

		var age = today.getFullYear() - dobDate.getFullYear();
		var mDiff = today.getMonth() - dobDate.getMonth();
		if (mDiff < 0 || (mDiff === 0 && today.getDate() < dobDate.getDate())) age--;

		if (age < 18) {
			err.textContent = 'You must be at least 18 years old to use this service';
			err.classList.remove('hidden');
			return;
		}

		err.classList.add('hidden');
		state.data.dob = iso;
		nextScreen();
	}

	function setWeightLoss(value) {
		state.data.hasLostWeight = value;
		var msg = $('weight-loss-message');
		if (value === 'no') {
			msg.classList.remove('hidden');
			setTimeout(function () { nextScreen(); }, 2000);
		} else {
			msg.classList.add('hidden');
			nextScreen();
		}
	}

	function setupCurrentWeight() {
		var input = $('reorder-current-weight');
		if (!input) return;
		input.addEventListener('input', function () {
			state.data.currentWeight = input.value;
			var btn = $('btn-screen-5');
			if (btn) btn.disabled = !input.value || parseFloat(input.value) < 40 || parseFloat(input.value) > 250;
		});
	}

	function setAppetite(value) {
		state.data.appetiteLasting = value;
		var msg = $('appetite-message');
		if (value === 'no') {
			msg.classList.remove('hidden');
			setTimeout(function () { nextScreen(); }, 2000);
		} else {
			msg.classList.add('hidden');
			nextScreen();
		}
	}

	function setSideEffects(value) {
		state.data.hasSideEffects = value;
		var msg = $('side-effects-message');
		if (value === 'yes') {
			msg.classList.remove('hidden');
			setTimeout(function () { nextScreen(); }, 3000);
		} else {
			msg.classList.add('hidden');
			nextScreen();
		}
	}

	function setHealthChanged(value) {
		state.data.healthChanged = value;
		if (value === 'yes') {
			pushScreen(13);
		} else {
			nextScreen();
		}
	}

	function setNewMeds(value) {
		state.data.newMedications = value;
		var input = $('new-medications-input');
		var btn = $('btn-screen-9');
		if (value === 'yes') {
			input.classList.remove('hidden');
			btn.disabled = true;
			var list = $('reorder-new-meds-list');
			list.addEventListener('input', function () {
				state.data.newMedicationsList = list.value.trim();
				btn.disabled = !list.value.trim();
			});
		} else {
			input.classList.add('hidden');
			state.data.newMedicationsList = '';
			btn.disabled = false;
		}
	}

	function proceedNewMeds() {
		if (state.data.newMedications === 'yes' && !state.data.newMedicationsList) return;
		nextScreen();
	}

	function setPregnancy(value) {
		state.data.couldBePregnant = value;
		if (value === 'yes') {
			pushScreen(14);
		} else {
			nextScreen();
		}
	}

	function setClinicalSupport(value) {
		state.data.wantsClinicalSupport = value;
		nextScreen();
	}

	function renderFinalScreen() {
		var container = $('final-screen-content');
		if (!container) return;

		if (state.data.wantsClinicalSupport === 'yes') {
			renderClinicalReview(container);
		} else {
			renderDoseSelection(container);
		}
	}

	function renderClinicalReview(container) {
		var calendlyUrl = cfg.calendlyReturning || '#';
		container.innerHTML =
			'<div class="clinical-review-card">' +
				'<h2>Let\'s optimise your treatment</h2>' +
				'<p>Your responses indicate you\'d benefit from personalised guidance. A quick 10-minute consultation can help optimise your dose and manage any side effects.</p>' +
				'<div class="feature-grid">' +
					'<div class="feature-item"><div class="feature-title">Video Call</div><div class="feature-desc">Face-to-face consultation</div></div>' +
					'<div class="feature-item"><div class="feature-title">10 Minutes</div><div class="feature-desc">Quick & convenient</div></div>' +
					'<div class="feature-item"><div class="feature-title">Free Service</div><div class="feature-desc">No additional cost</div></div>' +
				'</div>' +
				'<a href="' + escapeAttr(calendlyUrl) + '" target="_blank" rel="noopener" class="book-btn">Book My Free Consultation</a>' +
			'</div>' +
			'<button data-action="previous" class="btn btn-secondary" style="width:100%;margin-top:16px;">Back</button>';

		container.querySelector('[data-action="previous"]').addEventListener('click', previousScreen);
	}

	function renderDoseSelection(container) {
		var doseOptions = cfg.doseOptions || [];
		var medication = state.data.currentMedication;
		var currentDose = state.data.currentDose;
		var medImage = (medication === 'mounjaro') ? (cfg.assets && cfg.assets.mounjaro) : (cfg.assets && cfg.assets.wegovy);
		var medName = medication === 'mounjaro' ? 'Mounjaro' : 'Wegovy';

		var doseCardsHtml = doseOptions.map(function (opt) {
			var isCurrent = opt.dose === currentDose;
			var isSelected = opt.dose === state.data.selectedDose;
			return '<label class="dose-option-card' + (isSelected ? ' selected' : '') + '" data-dose="' + escapeAttr(opt.dose) + '">' +
					'<input type="radio" name="dose-option" value="' + escapeAttr(opt.dose) + '"' + (isSelected ? ' checked' : '') + ' />' +
					'<span class="dose-price">' + opt.formatted_price + '</span>' +
					'<span class="dose-name">' + escapeHtml(opt.dose) + '</span>' +
					(isCurrent ? '<span class="dose-current-tag">Current</span>' : '') +
				'</label>';
		}).join('');

		container.innerHTML =
			'<h2 style="text-align:center;">Choose your dose</h2>' +
			'<p style="text-align:center;">Continue at the same dose, step up, or step down. Our prescriber reviews every choice.</p>' +
			'<div class="med-info-card"><div class="med-info-inner">' +
				'<div class="med-thumb"><img src="' + escapeAttr(medImage || '') + '" alt="' + escapeAttr(medName) + '" /></div>' +
				'<div class="med-info-body">' +
					'<div class="med-badge">Your current medication</div>' +
					'<p class="med-name">' + escapeHtml(medName) + '</p>' +
					'<div class="dose-current"><span class="dose-label">Currently on:</span><span class="dose-pill">' + escapeHtml(currentDose) + '</span></div>' +
				'</div>' +
			'</div></div>' +
			doseCardsHtml +
			'<div class="btn-group">' +
				'<button data-action="previous" class="btn btn-secondary">Back</button>' +
				'<button id="reorder-submit-btn" data-action="submit-reorder" class="btn btn-primary btn-flex" disabled>Submit for Review &rarr;</button>' +
			'</div>';

		container.querySelector('#reorder-submit-btn').disabled = !state.data.selectedDose;

		container.querySelectorAll('input[name="dose-option"]').forEach(function (input) {
			input.addEventListener('change', function () {
				state.data.selectedDose = this.value;
				container.querySelectorAll('.dose-option-card').forEach(function (card) {
					card.classList.toggle('selected', card.getAttribute('data-dose') === state.data.selectedDose);
				});
				var submitBtn = container.querySelector('#reorder-submit-btn');
				if (submitBtn) submitBtn.disabled = !state.data.selectedDose;
			});
		});

		container.querySelector('[data-action="previous"]').addEventListener('click', previousScreen);
		container.querySelector('[data-action="submit-reorder"]').addEventListener('click', submitReorder);
	}

	function submitReorder() {
		if (state.isSubmitting) return;
		if (!state.data.selectedDose) {
			alert('Please choose a dose before continuing.');
			return;
		}
		state.isSubmitting = true;

		var btn = $('reorder-submit-btn');
		if (btn) { btn.disabled = true; btn.textContent = 'Submitting...'; }

		var payload = Object.assign({}, state.data, { assessment_id: state.assessmentId });

		ajax('tc_reorder_save', payload).then(function (data) {
			if (data.ok === false) {
				state.isSubmitting = false;
				if (data.redirect) {
					window.location.href = data.redirect;
					return;
				}
				if (data.code === 'health_changed') { pushScreen(13); return; }
				if (data.code === 'pregnancy')      { pushScreen(14); return; }
				alert(data.reason || 'We can\'t process this reorder.');
				if (btn) { btn.disabled = false; btn.textContent = 'Submit for Review →'; }
				return;
			}

			if (data.nonce) cfg.nonce = data.nonce;
			state.isSubmitting = false;
			renderSubmittedForReview();
		}).catch(function (e) {
			state.isSubmitting = false;
			if (btn) { btn.disabled = false; btn.textContent = 'Submit for Review →'; }
			alert(e.message || 'Something went wrong. Please try again.');
		});
	}

	function renderSubmittedForReview() {
		var email = (cfg.prefill && cfg.prefill.email) || state.data.email || '';
		root().innerHTML =
			'<div class="consult-card" style="text-align:center;">' +
				'<div style="width:64px;height:64px;margin:0 auto 16px;border-radius:50%;background:#dcfce7;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:30px;">&#10003;</div>' +
				'<h2>Reorder submitted for review</h2>' +
				'<p>Thank you. Your reorder has been sent to one of our prescribers' +
					(email ? ', and a confirmation email is on its way to <strong>' + escapeHtml(email) + '</strong>.' : '.') +
				'</p>' +
				'<div style="background:#f7f4f9;border-radius:8px;padding:16px;text-align:left;margin:16px 0;">' +
					'<p style="margin:0;"><strong>No payment is taken now.</strong> Once your prescriber approves your treatment (usually within 24 hours), we will email you a secure payment link. Your medication is dispatched after review and payment.</p>' +
				'</div>' +
				'<small>Questions? Contact us at <a href="mailto:care@togetherclinic.co.uk">care@togetherclinic.co.uk</a></small>' +
			'</div>';
		window.scrollTo({ top: 0, behavior: 'smooth' });
	}

	function escapeHtml(s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
			return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
		});
	}
	function escapeAttr(s) { return escapeHtml(s); }

	function handleClick(e) {
		var target = e.target.closest('[data-action]');
		if (!target || !root().contains(target)) return;
		var action = target.getAttribute('data-action');

		switch (action) {
			case 'agree-continue':         handleAgreeContinue(); break;
			case 'previous':               previousScreen(); break;
			case 'save-dob':               saveDOB(); break;
			case 'confirm-treatment':      nextScreen(); break;
			case 'save-weight':            nextScreen(); break;
			case 'proceed-new-meds':       proceedNewMeds(); break;
			case 'back-to-health-changed':
				state.data.healthChanged = '';
				showScreen(8);
				break;
			case 'back-to-pregnancy':
				state.data.couldBePregnant = '';
				showScreen(10);
				break;
		}
	}

	function handleChange(e) {
		var t = e.target;
		var action = t.getAttribute && t.getAttribute('data-action');
		if (!action || !t.checked) return;

		if (action === 'set-weight-loss')      setWeightLoss(t.value);
		else if (action === 'set-appetite')    setAppetite(t.value);
		else if (action === 'set-side-effects') setSideEffects(t.value);
		else if (action === 'set-health-changed') setHealthChanged(t.value);
		else if (action === 'set-new-meds')    setNewMeds(t.value);
		else if (action === 'set-pregnancy')   setPregnancy(t.value);
		else if (action === 'set-clinical-support') setClinicalSupport(t.value);
	}

	function init() {
		if (!root()) return;
		setupAgreement();
		setupDobAutoAdvance();
		setupCurrentWeight();
		root().addEventListener('click', handleClick);
		root().addEventListener('change', handleChange);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
