(function () {
	'use strict';

	var cfg = window.tcEligibility || {};

	var state = {
		currentScreen: 1,
		screenHistory: [],
		assessmentId: '',
		userData: {
			prevMeds: [],
			prevMedsToAsk: [],
			currentMedIndex: 0,
			prevWeights: {}
		},
		agreementChecks: [false, false, false, false, false],
		selectedTreatment: '',
		selectedWegovyDose: '0.25mg',
		selectedMounjaroDose: '2.5mg',
		selectedTabletsDose: '1.5mg',
		selectedFoundayoDose: '0.8mg',
		selectedOrlistatDose: '120mg',
		// Answers that rule out the GLP-1 products but not Orlistat (rules
		// section O, OF16): keyed by the question, so a changed answer clears it.
		glp1Only: {},
		isSubmitting: false,
		ineligibleReason: ''
	};

	// Rules WM-2026-10-v3 (AT Health IP-FRM-01 3A, and section O for
	// Orlistat). Mirrors TC_Eligibility_Rules on the server, which always has
	// the final say. The treatment is chosen last, so the browser only screens
	// a patient out when neither the GLP-1 products nor Orlistat remain open.
	var SERIOUS_CONDITIONS = [
		'I have chronic malabsorption syndrome',
		'I have cholestasis',
		"I'm currently being treated for cancer",
		'I have diabetic retinopathy',
		'I have severe heart failure',
		"I have a family history of thyroid cancer and/or I've had thyroid cancer",
		'I have severe kidney disease or kidney failure',
		'I have severe liver disease (for example cirrhosis)',
		'I have severe stomach or bowel problems, including gastroparesis (very slow stomach emptying)',
		'I have Multiple endocrine neoplasia type 2 (MEN2)',
		'I have a history of pancreatitis',
		'I have or have had an eating disorder',
		'I have had surgery or an operation to my thyroid',
		'My weight gain is caused by a hormone condition or by a medicine I take',
		'I have had a bariatric operation',
		'None of these apply'
	];

	var DISQUALIFYING_CONDITIONS = SERIOUS_CONDITIONS.filter(function (c) {
		return c.indexOf('bariatric') === -1 && c !== 'None of these apply';
	});

	// Orlistat blocks OE5, OE6 and OE9; the other conditions above rule out
	// the GLP-1 products only (information flags for Orlistat, OF16).
	var ORLISTAT_BLOCK_CONDITIONS = [
		'I have chronic malabsorption syndrome',
		'I have cholestasis',
		'I have or have had an eating disorder'
	];
	var GLP1_CARDS = ['wegovy-card', 'mounjaro-card', 'wegovy-tablets-card', 'foundayo-card'];

	var WEIGHT_CONDITIONS = [
		"I've been diagnosed with high blood pressure",
		"I've been diagnosed with high cholesterol",
		'I have sleep apnoea',
		'I have a heart or circulation problem (for example heart disease, angina or a previous stroke)',
		'I have osteoarthritis',
		'I have polycystic ovary syndrome (PCOS)',
		'I have fatty liver disease',
		'I have COPD',
		'I have asthma',
		'I have been diagnosed with a mental health condition such as depression or anxiety',
		'I have joint pains and/or aches',
		'I have GORD and/or indigestion',
		'I have erectile dysfunction',
		'I have low testosterone',
		'I have menopausal symptoms',
		'None of these apply'
	];

	var COMORBIDITY_NEEDLES = ['high blood pressure', 'high cholesterol', 'sleep apnoea', 'heart or circulation', 'osteoarthritis', 'polycystic', 'fatty liver', 'copd'];
	var DIABETES_QUALIFYING = ['type2-meds', 'type2-diet', 'pre'];
	var NOT_LICENSED = 'Based on your answers, the weight loss medicines we offer are not licensed for you at the moment. Please speak with your GP, who can discuss other support.';

	function hasQualifyingCondition() {
		var u = state.userData;
		if (DIABETES_QUALIFYING.indexOf(u.diabetes || '') !== -1) return true;
		return (u.weightConditions || []).some(function (c) {
			var l = c.toLowerCase();
			return COMORBIDITY_NEEDLES.some(function (n) { return l.indexOf(n) !== -1; });
		});
	}

	function bmiFrom(kg, cm) {
		kg = parseFloat(kg || '0'); cm = parseFloat(cm || '0');
		if (!kg || !cm) return 0;
		return kg / Math.pow(cm / 100, 2);
	}

	// Rule S1: 18 to 85 inclusive by date of birth. Rule S1A: extra
	// questions from 75. The answers are for the
	// prescriber and never pass or fail anyone here, except a reported eGFR
	// below 30 (exclusion E11), which the server also applies.
	var MAX_AGE = 85;
	var S1A_AGE = 75;
	var TOO_OLD = "Our weight loss plan isn't suitable for people aged 86 or over.";
	var S1A_PRISMA = {
		limitActivities: 's1a-prisma-limit',
		needHelp: 's1a-prisma-help',
		stayHome: 's1a-prisma-home',
		countOnSomeone: 's1a-prisma-count',
		walkingAid: 's1a-prisma-aid'
	};

	function isS1A() {
		var a = state.userData.ageYears;
		return typeof a === 'number' && a >= S1A_AGE && a <= MAX_AGE;
	}

	var PREV_MEDS = ['Wegovy', 'Ozempic', 'Saxenda', 'Rybelsus', 'Mounjaro', 'Alli', 'Mysimba', 'Other', 'I have never taken medication to lose weight'];

	function root() {
		return document.getElementById('tc-eligibility-root');
	}

	function $(id) {
		return document.getElementById(id);
	}

	function $$(selector, parent) {
		return (parent || root()).querySelectorAll(selector);
	}

	function showScreen(id) {
		$$('.screen').forEach(function (el) { el.classList.remove('active'); });
		var screen = $('screen-' + id);
		if (screen) {
			screen.classList.add('active');
			state.currentScreen = id;
			if (String(id) === '21') updateTreatmentAvailability();
		}
		window.scrollTo(0, 0);
	}

	function pushScreen(id) {
		state.screenHistory.push(state.currentScreen);
		showScreen(id);
	}

	function nextScreen() {
		var current = state.currentScreen;
		var next = typeof current === 'number' ? current + 1 : parseInt(current, 10) + 1;
		state.screenHistory.push(state.currentScreen);
		showScreen(next);
	}

	function previousScreen() {
		if (state.screenHistory.length > 0) {
			showScreen(state.screenHistory.pop());
		}
	}

	function showIneligible(reason) {
		state.ineligibleReason = reason;
		var el = $('ineligible-reason');
		if (el) el.textContent = reason;
		showScreen('ineligible');
		recordIneligible(reason);
	}

	function recordIneligible(reason) {
		if (!state.assessmentId) return;
		ajax('tc_eligibility_ineligible', {
			assessment_id: state.assessmentId,
			reason: reason
		}).catch(function () {});
	}

	function setCookie(assessmentId) {
		try {
			var payload = JSON.stringify({ assessment_id: assessmentId });
			var encoded = encodeURIComponent(payload);
			var maxAge = cfg.cookieMaxAge || 86400;
			document.cookie = (cfg.cookieName || 'tc_eligibility_data') + '=' + encoded + '; max-age=' + maxAge + '; path=/; samesite=lax' + (location.protocol === 'https:' ? '; secure' : '');
		} catch (e) {
			console.warn('[tc-eligibility] cookie set failed', e);
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

	// The assessment page HTML embeds the nonce (cfg.nonce), but that HTML can be
	// served from the CDN cache — leaving the nonce stale or bound to another
	// session, which fails the server's security check (seen most often when a
	// logged-in user lands on a page cached while logged out). admin-ajax is never
	// edge-cached, so pull a fresh nonce for THIS session and use it thereafter.
	// Requesting a nonce needs no nonce: it is bound to the caller's own session
	// and grants nothing on its own.
	function refreshNonce() {
		var url = (cfg.ajaxUrl || '/wp-admin/admin-ajax.php') + '?action=tc_eligibility_refresh_nonce';
		return fetch(url, { method: 'GET', credentials: 'same-origin', cache: 'no-store' })
			.then(function (resp) { return resp.json(); })
			.then(function (body) {
				if (body && body.success && body.data && body.data.nonce) {
					cfg.nonce = body.data.nonce;
				}
			})
			.catch(function () { /* keep the embedded nonce as a best-effort fallback */ });
	}

	function buildCookiePayload() {
		var u = state.userData;
		return {
			assessment_id: state.assessmentId,
			firstName: u.firstName || '',
			lastName: u.lastName || '',
			fullName: u.fullName || ((u.firstName || '') + ' ' + (u.lastName || '')).trim(),
			email: u.email || '',
			phone: u.phone || '',
			dob: u.dob || '',
			userType: u.userType || 'new',
			provider: u.provider || '',
			currentMedication: u.currentMedication || '',
			currentDose: u.currentDose || '',
			ageBand: u.age || '',
			ethnicity: u.ethnicity || '',
			sex: u.sex || '',
			pregnant: u.pregnant || '',
			breastfeeding: u.breastfeeding || '',
			conceive: u.conceive || '',
			weightKg: parseFloat(u.weight || '0') || 0,
			heightCm: parseFloat(u.height || '0') || 0,
			bmi: parseFloat(u.bmi || '0') || 0,
			diabetes: u.diabetes || '',
			conditions: u.conditions || [],
			bariatricDetails: (($('bariatric-details') || {}).value) || '',
			weightConditions: u.weightConditions || [],
			mentalHealthDetails: (($('mental-health-details') || {}).value) || '',
			otherConditions: u.otherConditions || '',
			otherConditionsList: (($('other-conditions') || {}).value) || '',
			prevMeds: u.prevMeds || [],
			prevWeights: u.prevWeights || {},
			currentMeds: u.currentMeds || '',
			currentMedsList: (($('medication-list') || {}).value) || '',
			allergies: u.allergies || '',
			allergiesList: (($('allergies') || {}).value) || '',
			goalWeight: (($('goal-weight') || {}).value) || '',
			addressLine1: u.addressLine1 || '',
			addressLine2: u.addressLine2 || '',
			city: u.city || '',
			postcode: u.postcode || '',
			country: u.country || 'United Kingdom',
			gpName: (($('gp-name') || {}).value) || '',
			gpPostcode: (($('gp-postcode') || {}).value) || '',
			gpConsentShare: $('gp-consent-1') && $('gp-consent-1').checked,
			gpConsentSCR: $('gp-consent-2') && $('gp-consent-2').checked,
			consentIdVideo: $('consent-id-video') && $('consent-id-video').checked,
			consentLifestyle: $('consent-lifestyle') && $('consent-lifestyle').checked,
			s1aFalls: isS1A() ? (u.s1aFalls || '') : '',
			s1aFracture: isS1A() ? (u.s1aFracture || '') : '',
			s1aPrisma: isS1A() ? (u.s1aPrisma || {}) : {},
			s1aMedsCount: isS1A() ? (u.s1aMedsCount || '') : '',
			s1aBpWater: isS1A() ? (u.s1aBpWater || '') : '',
			s1aKidneyTest: isS1A() ? (u.s1aKidneyTest || '') : '',
			s1aEgfrDate: isS1A() ? (u.s1aEgfrDate || '') : '',
			s1aEgfrResult: isS1A() ? (u.s1aEgfrResult || '') : '',
			startWeightKg: parseFloat(u.startWeight || '0') || 0,
			lastDoseDate: u.lastDoseDate || '',
			couldConceive: u.couldConceive || '',
			contraception: u.contraception || '',
			consentContraception: !!u.consentContraception,
			selectedTreatment: state.selectedTreatment,
			selectedWegovyDose: state.selectedWegovyDose,
			selectedMounjaroDose: state.selectedMounjaroDose,
			selectedTabletsDose: state.selectedTabletsDose,
			selectedFoundayoDose: state.selectedFoundayoDose,
			selectedOrlistatDose: state.selectedOrlistatDose,
			selectedDose: (function () {
				if (state.selectedTreatment === 'mounjaro') return state.selectedMounjaroDose;
				if (state.selectedTreatment === 'wegovy-tablets') return state.selectedTabletsDose;
				if (state.selectedTreatment === 'foundayo') return state.selectedFoundayoDose;
				if (state.selectedTreatment === 'orlistat') return state.selectedOrlistatDose;
				return state.selectedWegovyDose;
			})(),
			termsAgreed: state.agreementChecks.every(Boolean),
			bariatricRecent: u.bariatricRecent || ''
		};
	}

	function initCheckboxes() {
		populateCheckboxList('conditions-group', 'conditions', SERIOUS_CONDITIONS);
		populateCheckboxList('weight-conditions-group', 'weight-conditions', WEIGHT_CONDITIONS);
		populateCheckboxList('prev-meds-group', 'prev-meds', PREV_MEDS);
	}

	function populateCheckboxList(groupId, name, items) {
		var group = $(groupId);
		if (!group) return;
		group.innerHTML = '';
		items.forEach(function (label) {
			var lbl = document.createElement('label');
			lbl.className = 'checkbox-form-item';
			var input = document.createElement('input');
			input.type = 'checkbox';
			input.name = name;
			input.value = label;
			var span = document.createElement('span');
			span.textContent = label;
			lbl.appendChild(input);
			lbl.appendChild(span);
			group.appendChild(lbl);
		});
	}

	function updateAgreementButton() {
		var btn = $('agree-continue');
		if (btn) btn.disabled = !state.agreementChecks.every(Boolean);
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

	function updateFemaleScreeningButton() {
		var p = root().querySelector('input[name="pregnant"]:checked');
		var b = root().querySelector('input[name="breastfeeding"]:checked');
		var c = root().querySelector('input[name="conceive"]:checked');
		var btn = $('female-screening-continue');
		if (btn) btn.disabled = !(p && b && c);
	}

	function handleEarlyCapture() {
		var firstName = ($('early-first-name') || {}).value || '';
		var lastName = ($('early-last-name') || {}).value || '';
		var email = ($('early-email') || {}).value || '';
		var phone = ($('early-phone') || {}).value || '';
		var err = $('early-form-error');

		firstName = firstName.trim();
		lastName = lastName.trim();
		email = email.trim();
		phone = phone.trim();

		if (!firstName || !lastName || !email || !phone) {
			err.textContent = 'Please fill in all fields';
			err.style.display = 'block';
			return;
		}

		if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
			err.textContent = 'Please enter a valid email address';
			err.style.display = 'block';
			return;
		}

		var digits = phone.replace(/\D/g, '');
		if (digits.length < 10 || digits.length > 11) {
			err.textContent = 'Please enter a valid UK phone number (e.g. 07XXX XXXXXX)';
			err.style.display = 'block';
			return;
		}

		err.style.display = 'none';

		state.userData.firstName = firstName;
		state.userData.lastName = lastName;
		state.userData.fullName = firstName + ' ' + lastName;
		state.userData.email = email;
		state.userData.phone = phone;

		var btn = root().querySelector('[data-action="early-continue"]');
		if (btn) btn.classList.add('is-loading');

		ajax('tc_eligibility_save_partial', {
			firstName: firstName,
			lastName: lastName,
			email: email,
			phone: phone
		}).then(function (data) {
			state.assessmentId = data.assessment_id || '';
			if (btn) btn.classList.remove('is-loading');
			pushScreen(5);
		}).catch(function (e) {
			if (btn) btn.classList.remove('is-loading');
			err.textContent = e.message || 'Could not save your details. Please try again.';
			err.style.display = 'block';
		});
	}

	function setUserType(type) {
		state.userData.userType = type;
		state.screenHistory.push(state.currentScreen);
		showScreen(type === 'switching' ? 3 : 4);
	}

	function setProvider(value) {
		state.userData.provider = value;
		pushScreen('3a');
	}

	function setCurrentMedication(med) {
		state.userData.currentMedication = med;
		if (med === 'other') {
			state.userData.currentDose = '';
			pushScreen('3c');
			return;
		}
		populateDoseOptions();
		pushScreen('3b');
	}

	function populateDoseOptions() {
		var group = $('dose-group');
		if (!group) return;
		group.innerHTML = '';

		// The dose list comes from the server's canonical ladder; the inline
		// copy is only a fallback for stale cached configs.
		var ladders = cfg.doseLadders || {
			wegovy: ['0.25mg', '0.5mg', '1mg', '1.7mg', '2.4mg'],
			mounjaro: ['2.5mg', '5mg', '7.5mg', '10mg', '12.5mg', '15mg'],
			'wegovy-tablets': ['1.5mg', '4mg', '9mg', '25mg'],
			foundayo: ['0.8mg', '2.5mg', '5.5mg', '9mg', '14.5mg', '17.2mg'],
			orlistat: ['120mg']
		};
		var ladder = ladders[state.userData.currentMedication] || [];
		var doses = ladder.map(function (dose, i) {
			if (ladder.length === 1) return [dose, ''];
			return [dose, i === 0 ? 'starter dose' : (i === ladder.length - 1 ? 'maximum dose' : '')];
		});

		doses.forEach(function (d) {
			var label = document.createElement('label');
			label.className = 'radio-item';
			label.innerHTML = '<input type="radio" name="current-dose" value="' + d[0] + '" data-action="set-current-dose" /><span>' + d[0] + (d[1] ? ' (' + d[1] + ')' : '') + '</span>';
			group.appendChild(label);
		});
	}

	function setCurrentDose(value) {
		state.userData.currentDose = value;
		pushScreen('3c');
	}

	function saveCurrentTreatment() {
		var err = $('current-treatment-error');
		var d = parseInt((($('last-dose-day') || {}).value || '').trim(), 10);
		var m = parseInt((($('last-dose-month') || {}).value || '').trim(), 10);
		var y = parseInt((($('last-dose-year') || {}).value || '').trim(), 10);
		var iso = (y && m && d) ? (y + '-' + (m < 10 ? '0' + m : m) + '-' + (d < 10 ? '0' + d : d)) : '';
		var dt = iso ? new Date(iso + 'T00:00:00') : null;
		var today = new Date(); today.setHours(0, 0, 0, 0);
		if (!dt || isNaN(dt.getTime()) || dt.getDate() !== d || (dt.getMonth() + 1) !== m || dt > today || (today - dt) / 86400000 > 1826) {
			err.textContent = 'Please enter a valid date for your last dose';
			err.style.display = 'block';
			return;
		}
		var unit = (root().querySelector('input[name="start-weight-unit"]:checked') || {}).value || 'kg';
		var kg = 0;
		if (unit === 'kg') {
			kg = parseFloat(($('start-weight-kg') || {}).value);
		} else {
			var st = parseInt(($('start-weight-stone') || {}).value, 10) || 0;
			var lb = parseInt(($('start-weight-pounds') || {}).value, 10) || 0;
			kg = (st * 14 + lb) * 0.453592;
		}
		if (!kg || isNaN(kg) || kg < 40 || kg > 300) {
			err.textContent = 'Please enter your weight when you first started (40-300 kg)';
			err.style.display = 'block';
			return;
		}
		err.style.display = 'none';
		state.userData.lastDoseDate = iso;
		state.userData.startWeight = kg.toFixed(1);
		pushScreen(4);
	}

	function setAge(value) {
		state.userData.age = value;
		if (value === 'under-18') {
			showIneligible("Our weight loss plan isn't suitable for people under 18 years old.");
		} else if (value === '86-over') {
			showIneligible(TOO_OLD);
		} else {
			pushScreen('1b');
		}
	}

	function setEthnicity(value) {
		state.userData.ethnicity = value;
		nextScreen();
	}

	function setSex(value) {
		state.userData.sex = value;
		if (value === 'female') {
			pushScreen('6b');
		} else {
			nextScreen();
		}
	}

	function handleFemaleScreeningContinue() {
		var p = root().querySelector('input[name="pregnant"]:checked');
		var b = root().querySelector('input[name="breastfeeding"]:checked');
		var c = root().querySelector('input[name="conceive"]:checked');

		state.userData.pregnant = p ? p.value : '';
		state.userData.breastfeeding = b ? b.value : '';
		state.userData.conceive = c ? c.value : '';

		if (state.userData.pregnant === 'yes' || state.userData.breastfeeding === 'yes' || state.userData.conceive === 'yes') {
			showIneligible('For safety reasons, weight loss medications cannot be prescribed during pregnancy, when planning to become pregnant, or while breastfeeding.');
		} else {
			pushScreen(7);
		}
	}

	function toggleUnitInputs() {
		var weightUnit = root().querySelector('input[name="weight-unit"]:checked');
		if (weightUnit && $('weight-kg-input') && $('weight-st-inputs')) {
			$('weight-kg-input').style.display = weightUnit.value === 'kg' ? 'block' : 'none';
			$('weight-st-inputs').style.display = weightUnit.value === 'st' ? 'flex' : 'none';
		}
		var heightUnit = root().querySelector('input[name="height-unit"]:checked');
		if (heightUnit && $('height-cm-input') && $('height-ft-inputs')) {
			$('height-cm-input').style.display = heightUnit.value === 'cm' ? 'block' : 'none';
			$('height-ft-inputs').style.display = heightUnit.value === 'ft' ? 'flex' : 'none';
		}
	}

	function saveWeight() {
		var unit = (root().querySelector('input[name="weight-unit"]:checked') || {}).value || 'kg';
		var err = $('weight-error');
		var kg = 0;
		if (unit === 'kg') {
			kg = parseFloat($('weight-kg-input').value);
			if (!kg || isNaN(kg) || kg < 40 || kg > 250) {
				err.textContent = 'Please enter a valid weight (40-250 kg)';
				err.style.display = 'block';
				return;
			}
		} else {
			var st = parseInt($('weight-stone').value, 10) || 0;
			var lb = parseInt($('weight-pounds').value, 10) || 0;
			var totalLb = st * 14 + lb;
			if (totalLb < 84 || totalLb > 560) {
				err.textContent = 'Please enter a valid weight (6st 0lbs - 40st 0lbs)';
				err.style.display = 'block';
				return;
			}
			kg = totalLb * 0.453592;
		}
		err.style.display = 'none';
		state.userData.weight = kg.toFixed(1);
		nextScreen();
	}

	function calculateBMI() {
		var unit = (root().querySelector('input[name="height-unit"]:checked') || {}).value || 'cm';
		var err = $('height-error');
		var cm = 0;
		if (unit === 'cm') {
			cm = parseFloat($('height-cm-input').value);
			if (!cm || isNaN(cm) || cm < 120 || cm > 230) {
				err.textContent = 'Please enter a valid height (120-230 cm)';
				err.style.display = 'block';
				return;
			}
		} else {
			var ft = parseInt($('height-feet').value, 10) || 0;
			var inches = parseInt($('height-inches').value, 10) || 0;
			var total = ft * 12 + inches;
			if (total < 48 || total > 90) {
				err.textContent = 'Please enter a valid height (4\'0" - 7\'6")';
				err.style.display = 'block';
				return;
			}
			cm = total * 2.54;
		}
		err.style.display = 'none';
		state.userData.height = cm.toFixed(1);

		var weight = parseFloat(state.userData.weight || '0');
		var bmi = weight / Math.pow(cm / 100, 2);
		state.userData.bmi = bmi.toFixed(1);
		state.userData.bmiRaw = bmi;

		updateBMIDisplay();
		pushScreen('8b');
	}

	function updateBMIDisplay() {
		var bmi = parseFloat(state.userData.bmi || '0');
		if ($('bmi-display')) $('bmi-display').textContent = state.userData.bmi || '-';

		var category = '-';
		if (bmi < 18.5) category = 'Underweight';
		else if (bmi < 25) category = 'Healthy weight';
		else if (bmi < 30) category = 'Overweight';
		else if (bmi < 35) category = 'Obese (Class I)';
		else if (bmi < 40) category = 'Obese (Class II)';
		else category = 'Obese (Class III)';

		if ($('bmi-category')) $('bmi-category').textContent = category;

		var msg = $('bmi-message');
		if (msg) {
			msg.textContent = 'This is based on the weight and height you entered. Your prescriber will check your weight and height on a video call before any treatment.';
		}
	}

	// Transfer rules apply only when the last dose was within 3 months.
	function isTransfer() {
		var u = state.userData;
		// A move from Orlistat to a GLP-1 is a new start (rule O2.3).
		if (u.userType !== 'switching' || !u.lastDoseDate || u.currentMedication === 'orlistat') return false;
		var days = (new Date().setHours(0, 0, 0, 0) - new Date(u.lastDoseDate + 'T00:00:00')) / 86400000;
		return days <= 91;
	}

	// Rule O2.3/O2.4: a patient already on Orlistat elsewhere.
	function fromOrlistat() {
		var u = state.userData;
		return u.userType === 'switching' && u.currentMedication === 'orlistat';
	}

	function currentBmi() {
		// Unrounded, to match the server (26.96 must not pass as 27.0).
		return state.userData.bmiRaw || parseFloat(state.userData.bmi || '0');
	}

	// GLP-1 floor before the condition questions: 27 to start (27 to 29.9
	// also needs a condition, checked later); transfer: current BMI above 25.
	function glp1BmiFloorOk() {
		var bmi = currentBmi();
		return !!bmi && (isTransfer() ? bmi > 25 : bmi >= 27);
	}

	// Orlistat floor (rule O2.2): 28 to start; 20 for a transfer from
	// Orlistat elsewhere (rule O2.4).
	function orlistatBmiFloorOk() {
		var bmi = currentBmi();
		return !!bmi && bmi >= (fromOrlistat() ? 20 : 28);
	}

	// Rule O2.2 / O2.4 in full (after the condition questions).
	function orlistatLicenceOk() {
		var u = state.userData;
		var bmi = currentBmi();
		var cond = hasQualifyingCondition();
		if (fromOrlistat()) {
			var start = bmiFrom(u.startWeight, u.height);
			if (start && bmi >= 20 && (start >= 30 || (start >= 28 && cond))) return true;
		}
		return bmi >= 30 || (bmi >= 28 && cond);
	}

	function glp1Blocked() {
		var g = state.glp1Only;
		return Object.keys(g).some(function (k) { return g[k]; });
	}

	function glp1Possible() {
		return !glp1Blocked() && glp1BmiFloorOk() && licenceCheckPasses();
	}

	function checkBMIEligibility() {
		if (!glp1BmiFloorOk() && !orlistatBmiFloorOk()) {
			showIneligible(NOT_LICENSED);
		} else {
			pushScreen(9);
		}
	}

	function setDiabetes(value) {
		state.userData.diabetes = value;
		// Type 1 diabetes rules out the GLP-1 products only (OF16 for Orlistat).
		state.glp1Only.type1 = (value === 'type1');
		if (value === 'type1' && !orlistatBmiFloorOk()) {
			showIneligible('Based on your answers, our online weight loss service is not suitable for you. Please speak with your GP or diabetes team about the options available to you.');
			return;
		}
		nextScreen();
	}

	// After the condition questions: apply the BMI 27 to 29.9 rule for
	// patients starting treatment, and the starting-BMI rule for patients
	// already on treatment.
	function licenceCheckPasses() {
		var u = state.userData;
		var bmi = u.bmiRaw || parseFloat(u.bmi || '0');
		if (isTransfer()) {
			var start = bmiFrom(u.startWeight, u.height);
			if (!start || start < 27) return false;
			return start >= 30 || hasQualifyingCondition();
		}
		return bmi >= 30 || (bmi >= 27 && hasQualifyingCondition());
	}

	function proceedConditions() {
		var checked = Array.from(root().querySelectorAll('input[name="conditions"]:checked'));
		var err = $('conditions-error');
		if (checked.length === 0) {
			err.textContent = 'Please select at least one option to continue';
			err.style.display = 'block';
			return;
		}
		err.style.display = 'none';

		var values = checked.map(function (cb) { return cb.value; });
		state.userData.conditions = values;

		var hasNone = values.indexOf('None of these apply') !== -1;
		var hasBariatric = values.some(function (v) { return v.toLowerCase().indexOf('bariatric') !== -1; });
		var blocksAll = values.some(function (v) {
			return ORLISTAT_BLOCK_CONDITIONS.indexOf(v) !== -1;
		});
		var glp1Only = values.some(function (v) {
			return DISQUALIFYING_CONDITIONS.indexOf(v) !== -1 && ORLISTAT_BLOCK_CONDITIONS.indexOf(v) === -1;
		});
		state.glp1Only.conditions = glp1Only;

		if (blocksAll || (glp1Only && !orlistatBmiFloorOk())) {
			showIneligible('Based on the medical history you provided, weight loss medication is not clinically appropriate. Please speak with your GP about alternative options.');
			return;
		}

		if (hasBariatric) {
			pushScreen('10a');
			return;
		}

		if (hasNone && values.length === 1) {
			pushScreen(11);
			return;
		}

		pushScreen(11);
	}

	function bariatricTiming(answer) {
		state.userData.bariatricRecent = answer;
		if (answer === 'yes') {
			showIneligible('Weight loss medication is not suitable within 6 months of bariatric surgery.');
		} else {
			pushScreen('10b');
		}
	}

	function setOtherConditions(answer) {
		state.userData.otherConditions = answer;
		if (answer === 'yes') {
			pushScreen('12a');
		} else {
			pushScreen(13);
		}
	}

	function proceedWeightConditions() {
		var checked = Array.from(root().querySelectorAll('input[name="weight-conditions"]:checked'));
		var err = $('weight-conditions-error');
		if (checked.length === 0) {
			err.textContent = 'Please select at least one option to continue';
			err.style.display = 'block';
			return;
		}
		err.style.display = 'none';

		var values = checked.map(function (cb) { return cb.value; });
		state.userData.weightConditions = values;

		var hasMentalHealth = values.some(function (v) { return v.toLowerCase().indexOf('mental health') !== -1; });

		if (!glp1Possible() && !orlistatLicenceOk()) {
			showIneligible(NOT_LICENSED);
			return;
		}

		if (hasMentalHealth) {
			pushScreen('11a');
		} else {
			pushScreen(12);
		}
	}

	function proceedPrevMeds() {
		var checked = Array.from(root().querySelectorAll('input[name="prev-meds"]:checked'));
		var err = $('prev-meds-error');
		if (checked.length === 0) {
			err.textContent = 'Please select at least one option to continue';
			err.style.display = 'block';
			return;
		}
		err.style.display = 'none';

		var never = checked.some(function (cb) {
			return cb.value === 'I have never taken medication to lose weight';
		});

		if (never) {
			state.userData.prevMeds = [];
			pushScreen(14);
			return;
		}

		state.userData.prevMeds = checked.map(function (cb) { return cb.value; }).filter(function (v) {
			return v !== 'I have never taken medication to lose weight';
		});
		state.userData.prevMedsToAsk = state.userData.prevMeds.slice();
		state.userData.currentMedIndex = 0;
		showPrevWeightQuestion();
	}

	function showPrevWeightQuestion() {
		var i = state.userData.currentMedIndex;
		var list = state.userData.prevMedsToAsk;
		if (i < list.length) {
			var q = $('prev-weight-question');
			if (q) q.textContent = 'What was your weight in kg before starting ' + list[i] + '?';
			pushScreen('13-weight');
		} else {
			pushScreen(14);
		}
	}

	function savePrevWeight() {
		var v = ($('prev-weight') || {}).value;
		var medName = state.userData.prevMedsToAsk[state.userData.currentMedIndex];
		if (v) {
			state.userData.prevWeights[medName] = v;
		}
		state.userData.currentMedIndex++;
		var input = $('prev-weight');
		if (input) input.value = '';
		showPrevWeightQuestion();
	}

	function skipPrevWeight() {
		state.userData.currentMedIndex++;
		var input = $('prev-weight');
		if (input) input.value = '';
		showPrevWeightQuestion();
	}

	function setCurrentMedsChoice(value) {
		state.userData.currentMeds = value;
		if (value === 'yes') {
			pushScreen('14a');
		} else {
			var list = $('medication-list');
			if (list) list.value = '';
			pushScreen(15);
		}
	}

	function saveMedicationList() {
		var list = (($('medication-list') || {}).value || '').trim();
		var err = $('medication-list-error');
		if (list.length < 3) {
			err.textContent = 'Please list your medicines, or go back and choose No';
			err.style.display = 'block';
			return;
		}
		err.style.display = 'none';
		pushScreen(15);
	}

	function setCouldConceive(answer) {
		state.userData.couldConceive = answer;
		var section = $('contraception-section');
		if (answer === 'yes') {
			if (section) section.style.display = 'block';
		} else {
			if (section) section.style.display = 'none';
			state.userData.contraception = '';
			state.userData.consentContraception = false;
			state.glp1Only.contraception = false;
			pushScreen(16);
		}
	}

	function saveContraception() {
		var choice = root().querySelector('input[name="contraception"]:checked');
		var agreed = $('consent-contraception') && $('consent-contraception').checked;
		var err = $('contraception-error');
		if (!choice) {
			err.textContent = 'Please tell us which contraception you use';
			err.style.display = 'block';
			return;
		}
		// The agreement is needed for the GLP-1 products, not for Orlistat
		// (rule O3). Without it only Orlistat stays open.
		if (!agreed && !orlistatLicenceOk()) {
			err.textContent = 'These medicines can only be prescribed if you agree to use effective contraception during treatment';
			err.style.display = 'block';
			return;
		}
		err.style.display = 'none';
		state.userData.contraception = choice.value;
		state.userData.consentContraception = !!agreed;
		state.glp1Only.contraception = !agreed;
		pushScreen(16);
	}

	function saveConsents() {
		var err = $('consent-error');
		var required = ['consent-id-video', 'gp-consent-2', 'consent-lifestyle'];
		var ok = required.every(function (id) {
			return $(id) && $(id).checked;
		});
		if (!ok) {
			err.textContent = 'Please tick the three required boxes. We cannot prescribe without them.';
			err.style.display = 'block';
			return;
		}
		err.style.display = 'none';
		nextScreen();
	}

	function setAllergies(answer) {
		state.userData.allergies = answer;
		if (answer === 'yes') {
			pushScreen('15a');
		} else {
			if (state.userData.sex === 'female') {
				pushScreen('15b');
			} else {
				pushScreen(16);
			}
		}
	}

	function continueAllergies() {
		if (state.userData.sex === 'female') {
			pushScreen('15b');
		} else {
			pushScreen(16);
		}
	}

	function setGoalWeightQ(answer) {
		if (answer === 'yes') {
			pushScreen(17);
		} else {
			pushScreen(18);
		}
	}

	function saveDOB() {
		var day = (($('dob-day') || {}).value || '').trim();
		var month = (($('dob-month') || {}).value || '').trim();
		var year = (($('dob-year') || {}).value || '').trim();
		var err = $('dob-error');

		if (!day || !month || !year) {
			err.textContent = 'Please enter your full date of birth';
			err.style.display = 'block';
			return;
		}

		var d = parseInt(day, 10);
		var m = parseInt(month, 10);
		var y = parseInt(year, 10);
		var thisYear = new Date().getFullYear();

		if (isNaN(d) || isNaN(m) || isNaN(y) || d < 1 || d > 31 || m < 1 || m > 12 || y < 1900 || y > thisYear) {
			err.textContent = 'Please enter a valid date';
			err.style.display = 'block';
			return;
		}

		var iso = y + '-' + (m < 10 ? '0' + m : m) + '-' + (d < 10 ? '0' + d : d);
		var dobDate = new Date(iso + 'T00:00:00');

		if (isNaN(dobDate.getTime()) || dobDate.getDate() !== d || (dobDate.getMonth() + 1) !== m) {
			err.textContent = 'That date doesn\'t exist (please check the day and month)';
			err.style.display = 'block';
			return;
		}

		var today = new Date();
		if (dobDate > today) {
			err.textContent = 'Date of birth cannot be in the future';
			err.style.display = 'block';
			return;
		}

		var age = today.getFullYear() - dobDate.getFullYear();
		var mDiff = today.getMonth() - dobDate.getMonth();
		if (mDiff < 0 || (mDiff === 0 && today.getDate() < dobDate.getDate())) age--;

		if (age < 18) {
			err.textContent = 'You must be at least 18 years old to use this service';
			err.style.display = 'block';
			return;
		}
		if (age > MAX_AGE) {
			showIneligible(TOO_OLD);
			return;
		}

		err.style.display = 'none';
		state.userData.dob = iso;
		state.userData.ageYears = age;
		updateGpShareNote();
		if (isS1A()) {
			pushScreen('18a');
		} else {
			nextScreen();
		}
	}

	function updateGpShareNote() {
		// GP sharing is strongly recommended at every age (GPhC 4.2 k); the
		// prescriber decides if it is refused. The 75+ note is no longer shown.
		['gp-share-note-under-75', 'gp-share-note-under-75-text'].forEach(function (id) {
			if ($(id)) $(id).style.display = '';
		});
		if ($('gp-share-note-75')) $('gp-share-note-75').style.display = 'none';
	}

	function radioValue(name) {
		var el = root().querySelector('input[name="' + name + '"]:checked');
		return el ? el.value : '';
	}

	function saveS1A() {
		var err = $('s1a-error');
		var u = state.userData;
		var prisma = {};
		var complete = true;
		Object.keys(S1A_PRISMA).forEach(function (key) {
			prisma[key] = radioValue(S1A_PRISMA[key]);
			if (!prisma[key]) complete = false;
		});
		var falls = radioValue('s1a-falls');
		var fracture = radioValue('s1a-fracture');
		var meds = radioValue('s1a-meds-count');
		var bp = radioValue('s1a-bp-water');
		var kidney = radioValue('s1a-kidney-test');
		if (!complete || !falls || !fracture || !meds || !bp || !kidney) {
			err.textContent = 'Please answer every question on this page';
			err.style.display = 'block';
			return;
		}

		var month = parseInt((($('s1a-egfr-month') || {}).value || '').trim(), 10);
		var year = parseInt((($('s1a-egfr-year') || {}).value || '').trim(), 10);
		var egfrDate = '';
		if (month || year) {
			var now = new Date();
			var thisMonth = now.getFullYear() * 12 + now.getMonth() + 1;
			if (!month || !year || month < 1 || month > 12 || year < 1990 || (year * 12 + month) > thisMonth) {
				err.textContent = 'Please enter a valid month and year for your kidney test, or leave both blank';
				err.style.display = 'block';
				return;
			}
			egfrDate = year + '-' + (month < 10 ? '0' + month : month);
		}
		var resultRaw = (($('s1a-egfr-result') || {}).value || '').trim();
		var egfr = '';
		if (resultRaw) {
			// Accept up to two decimal places (e.g. 29.6, 29.95) so a result just under 30
			// is never typed as 30 and passed (exclusion E11).
			var n = parseFloat(resultRaw);
			if (!/^\d{1,3}(\.\d{1,2})?$/.test(resultRaw) || isNaN(n) || n < 1 || n > 200) {
				err.textContent = 'Please enter your eGFR result as a number (for example 45 or 29.6), or leave it blank';
				err.style.display = 'block';
				return;
			}
			egfr = String(n);
		}
		err.style.display = 'none';

		u.s1aFalls = falls;
		u.s1aFracture = fracture;
		u.s1aPrisma = prisma;
		u.s1aMedsCount = meds;
		u.s1aBpWater = bp;
		u.s1aKidneyTest = kidney;
		u.s1aEgfrDate = egfrDate;
		u.s1aEgfrResult = egfr;

		// Exclusion E11 (severe kidney impairment) for the GLP-1 products; a
		// flag for Orlistat (OF11, OF16). Nothing else on this screen screens
		// anyone out: the prescriber decides.
		state.glp1Only.egfr = !!(egfr && parseFloat(egfr) < 30);
		if (state.glp1Only.egfr && !orlistatLicenceOk()) {
			showIneligible('Based on the medical history you provided, weight loss medication is not clinically appropriate. Please speak with your GP about alternative options.');
			return;
		}
		pushScreen(19);
	}

	function setupDobAutoAdvance() {
		var day = $('dob-day');
		var month = $('dob-month');
		var year = $('dob-year');
		if (!day || !month || !year) return;

		[day, month, year].forEach(function (input) {
			input.addEventListener('input', function () {
				this.value = this.value.replace(/\D/g, '');
			});
		});

		day.addEventListener('input', function () {
			if (this.value.length >= 2) month.focus();
		});
		month.addEventListener('input', function () {
			if (this.value.length >= 2) year.focus();
		});
	}

	function saveAddress() {
		var line1 = ($('address-line1') || {}).value.trim();
		var line2 = ($('address-line2') || {}).value.trim();
		var city = ($('city') || {}).value.trim();
		var postcode = ($('postcode') || {}).value.trim().toUpperCase();
		var country = ($('country') || {}).value || 'United Kingdom';
		var err = $('address-error');

		if (!line1 || !city || !postcode) {
			err.textContent = 'Please fill in all required fields';
			err.style.display = 'block';
			return;
		}

		if (!/^[A-Z]{1,2}\d{1,2}[A-Z]?\s?\d[A-Z]{2}$/i.test(postcode)) {
			err.textContent = 'Please enter a valid UK postcode';
			err.style.display = 'block';
			return;
		}

		err.style.display = 'none';
		state.userData.addressLine1 = line1;
		state.userData.addressLine2 = line2;
		state.userData.city = city;
		state.userData.postcode = postcode;
		state.userData.country = country;
		nextScreen();
	}

	function selectTreatment(value) {
		if (!treatmentAvailable(value)) return;
		state.selectedTreatment = value;
		updateTreatmentCards();
		updateSubmitButton();
	}

	// Card id ↔ treatment id. Adding a treatment means one entry here, not
	// another hardcoded branch (this function grew a line per product).
	var TREATMENT_CARDS = {
		'wegovy-card': 'wegovy',
		'mounjaro-card': 'mounjaro',
		'wegovy-tablets-card': 'wegovy-tablets',
		'foundayo-card': 'foundayo',
		'orlistat-card': 'orlistat'
	};

	function treatmentAvailable(treatment) {
		return treatment === 'orlistat' ? orlistatLicenceOk() : glp1Possible();
	}

	// Screen 21: offer only the products the answers leave open. The server
	// applies the same rules per product and has the final say.
	function updateTreatmentAvailability() {
		var glp1 = glp1Possible();
		var orl = orlistatLicenceOk();
		if (!glp1 && !orl) {
			showIneligible(NOT_LICENSED);
			return;
		}
		Object.keys(TREATMENT_CARDS).forEach(function (cardId) {
			var card = $(cardId);
			if (!card) return;
			var open = treatmentAvailable(TREATMENT_CARDS[cardId]);
			card.disabled = !open;
			card.classList.toggle('treatment-unavailable', !open);
			card.setAttribute('aria-disabled', String(!open));
		});
		if (state.selectedTreatment && !treatmentAvailable(state.selectedTreatment)) {
			state.selectedTreatment = '';
		}
		var note = $('treatment-availability-note');
		if (note) {
			var text = '';
			if (!glp1) {
				text = 'Based on your answers, Wegovy, Mounjaro and Foundayo are not options we can offer you. Orlistat can be discussed with a prescriber, who decides at your consultation whether it is suitable.';
			} else if (!orl) {
				text = 'Orlistat is not available to choose: its licence is for a BMI of 30 or above, or 28 or above with a weight-related condition.';
			}
			note.textContent = text;
			note.style.display = text ? 'block' : 'none';
		}
		updateTreatmentCards();
		updateSubmitButton();
	}

	function updateTreatmentCards() {
		Object.keys(TREATMENT_CARDS).forEach(function (cardId) {
			var card = $(cardId);
			if (!card) return;
			var on = state.selectedTreatment === TREATMENT_CARDS[cardId];
			card.classList.toggle('selected', on);
			card.setAttribute('aria-pressed', String(on));
		});
	}

	function updateSubmitButton() {
		var btn = $('submit-button');
		if (!btn) return;
		btn.disabled = !state.selectedTreatment;
	}

	function submitAssessment() {
		if (state.isSubmitting) return;
		if (!state.selectedTreatment) {
			alert('Please choose a treatment before submitting.');
			return;
		}
		state.isSubmitting = true;

		var btn = $('submit-button');
		if (btn) { btn.disabled = true; btn.textContent = 'Submitting...'; }

		var payload = buildCookiePayload();
		refreshNonce().then(function () {
			return ajax('tc_eligibility_save', payload);
		}).then(function (data) {
			if (data.eligible === false) {
				showIneligible(data.reason || 'You do not meet the eligibility criteria.');
				state.isSubmitting = false;
				return;
			}

			state.assessmentId = data.assessment_id || state.assessmentId;
			if (data.nonce) cfg.nonce = data.nonce;
			setCookie(state.assessmentId);

			// Phase 2.5 (authorise-at-submission): send the patient straight to
			// the secure order-pay page. The card is authorised there, not
			// charged — capture happens only on prescriber approval. Keep
			// isSubmitting true so a slow navigation can't double-submit.
			if (data.pay_url) {
				window.location.href = data.pay_url;
				return;
			}

			$('confirmed-name').textContent = state.userData.firstName || '';
			$('confirmed-email').textContent = state.userData.email || '';
			updateConfirmedTreatmentBanner(data);
			showScreen('confirmed');
			state.isSubmitting = false;
		}).catch(function (e) {
			state.isSubmitting = false;
			if (btn) { btn.disabled = false; btn.textContent = 'Submit Assessment for Review'; }
			alert(e.message || 'Something went wrong submitting your assessment. Please try again.');
		});
	}

	function updateConfirmedTreatmentBanner(info) {
		var names = { wegovy: 'Wegovy', mounjaro: 'Mounjaro', 'wegovy-tablets': 'Wegovy Tablets', foundayo: 'Foundayo', orlistat: 'Orlistat' };
		var prices = {
			wegovy: '£109/month · Starting dose (0.25mg)',
			mounjaro: '£159/month · Starting dose (2.5mg)',
			'wegovy-tablets': '£99/month · Starting dose (1.5mg)',
			foundayo: '£99/month · Starting dose (0.8mg)',
			orlistat: '£50/pack · 120mg capsules'
		};
		var name = names[state.selectedTreatment] || 'Wegovy';
		var price = prices[state.selectedTreatment] || prices.wegovy;

		// The server reports the dose it actually supplied (switchers start on
		// the converted dose, not the starter) and the order's real price.
		if (info && info.doseSupplied && info.priceFormatted) {
			name = info.treatmentName || name;
			price = info.priceFormatted + '/month · ' + info.doseSupplied;
		}

		if ($('confirmed-treatment-name')) $('confirmed-treatment-name').textContent = name;
		if ($('confirmed-treatment-price')) $('confirmed-treatment-price').textContent = price;
	}

	function reviewAnswers() {
		state.currentScreen = 1;
		state.screenHistory = [];
		showScreen(1);
	}

	function handleClick(e) {
		var target = e.target.closest('[data-action]');
		if (!target || !root().contains(target)) return;
		var action = target.getAttribute('data-action');
		var value = target.getAttribute('data-value');

		switch (action) {
			case 'agree-continue':
				if (state.agreementChecks.every(Boolean)) {
					state.userData.termsAgreed = true;
					nextScreen();
				}
				break;
			case 'previous': previousScreen(); break;
			case 'next': nextScreen(); break;
			case 'goto': pushScreen(value); break;
			case 'early-continue': handleEarlyCapture(); break;
			case 'set-user-type': setUserType(value); break;
			case 'set-current-medication': setCurrentMedication(value); break;
			case 'set-sex': setSex(value); break;
			case 'female-screening-continue': handleFemaleScreeningContinue(); break;
			case 'save-weight': saveWeight(); break;
			case 'calculate-bmi': calculateBMI(); break;
			case 'check-bmi-eligibility': checkBMIEligibility(); break;
			case 'proceed-conditions': proceedConditions(); break;
			case 'bariatric-timing': bariatricTiming(value); break;
			case 'set-other-conditions': setOtherConditions(value); break;
			case 'proceed-weight-conditions': proceedWeightConditions(); break;
			case 'proceed-prev-meds': proceedPrevMeds(); break;
			case 'skip-prev-weight': skipPrevWeight(); break;
			case 'save-prev-weight': savePrevWeight(); break;
			case 'set-current-meds-choice': setCurrentMedsChoice(value); break;
			case 'save-medication-list': saveMedicationList(); break;
			case 'save-current-treatment': saveCurrentTreatment(); break;
			case 'set-could-conceive': setCouldConceive(value); break;
			case 'save-contraception': saveContraception(); break;
			case 'save-consents': saveConsents(); break;
			case 'set-allergies': setAllergies(value); break;
			case 'continue-allergies': continueAllergies(); break;
			case 'set-goal-weight-q': setGoalWeightQ(value); break;
			case 'save-dob': saveDOB(); break;
			case 'save-s1a': saveS1A(); break;
			case 'save-address': saveAddress(); break;
			case 'select-treatment': selectTreatment(value); break;
			case 'submit-assessment': submitAssessment(); break;
			case 'review-answers': reviewAnswers(); break;
		}
	}

	function handleChange(e) {
		var t = e.target;
		var action = t.getAttribute && t.getAttribute('data-action');

		if (action === 'set-age' && t.checked) setAge(t.value);
		else if (action === 'set-ethnicity' && t.checked) setEthnicity(t.value);
		else if (action === 'set-provider' && t.checked) setProvider(t.value);
		else if (action === 'set-diabetes' && t.checked) setDiabetes(t.value);
		else if (action === 'set-current-dose' && t.checked) setCurrentDose(t.value);

		if (t.name === 'pregnant' || t.name === 'breastfeeding' || t.name === 'conceive') {
			updateFemaleScreeningButton();
		}

		if (t.name === 'weight-unit' || t.name === 'height-unit') {
			toggleUnitInputs();
		}

		if (t.name === 'start-weight-unit') {
			var kgIn = $('start-weight-kg');
			var stIn = $('start-weight-st-inputs');
			if (kgIn) kgIn.style.display = t.value === 'kg' ? 'block' : 'none';
			if (stIn) stIn.style.display = t.value === 'st' ? 'flex' : 'none';
		}
	}

	function updateCompletingAs() {
		var fullName = (state.userData.fullName || '').trim();
		var email = state.userData.email || '';
		if (!fullName || !email) return;

		var cn = $('completing-as');
		var ce = $('completing-as-email');
		var av = $('dob-avatar-initials');
		if (cn) cn.textContent = fullName;
		if (ce) ce.textContent = email;
		if (av) {
			var initials = fullName.split(/\s+/).map(function (p) { return p[0] || ''; }).join('').slice(0, 2).toUpperCase();
			av.textContent = initials || '?';
		}
	}

	function init() {
		if (!root()) return;

		// Replace any CDN-cached/stale nonce with a fresh, session-bound one
		// before the patient interacts with the form.
		refreshNonce();

		initCheckboxes();
		setupAgreement();
		setupDobAutoAdvance();
		updateTreatmentCards();
		updateSubmitButton();
		updateBMIDisplay();
		toggleUnitInputs();

		root().addEventListener('click', handleClick);
		root().addEventListener('change', handleChange);

		setInterval(updateCompletingAs, 800);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
