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
		isSubmitting: false,
		ineligibleReason: ''
	};

	var SERIOUS_CONDITIONS = [
		'I have chronic malabsorption syndrome',
		'I have cholestasis',
		"I'm currently being treated for cancer",
		'I have diabetic retinopathy',
		'I have severe heart failure',
		"I have a family history of thyroid cancer and/or I've had thyroid cancer",
		'I have end-stage kidney disease',
		'I have Multiple endocrine neoplasia type 2 (MEN2)',
		'I have a history of pancreatitis',
		'I have or have had an eating disorder',
		'I have had surgery or an operation to my thyroid',
		'I have had a bariatric operation',
		'None of these apply'
	];

	var DISQUALIFYING_CONDITIONS = SERIOUS_CONDITIONS.slice(0, 11).filter(function (c) {
		return c.indexOf('bariatric') === -1;
	});

	var WEIGHT_CONDITIONS = [
		'I have been diagnosed with a mental health condition such as depression or anxiety',
		'My weight makes me anxious in social situations',
		'I have joint pains and/or aches',
		'I have osteoarthritis',
		'I have GORD and/or indigestion',
		'I have a heart/cardiovascular problem',
		"I've been diagnosed with high blood pressure",
		"I've been diagnosed with high cholesterol",
		'I have fatty liver disease',
		'I have sleep apnoea',
		'I have asthma or COPD',
		'I have erectile dysfunction',
		'I have low testosterone',
		'I have menopausal symptoms',
		'I have polycystic ovary syndrome (PCOS)',
		'None of these apply'
	];

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
			selectedTreatment: state.selectedTreatment,
			selectedWegovyDose: state.selectedWegovyDose,
			selectedMounjaroDose: state.selectedMounjaroDose,
			selectedDose: state.selectedTreatment === 'wegovy' ? state.selectedWegovyDose : state.selectedMounjaroDose,
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
		populateDoseOptions();
		pushScreen('3b');
	}

	function populateDoseOptions() {
		var group = $('dose-group');
		if (!group) return;
		group.innerHTML = '';
		var doses = state.userData.currentMedication === 'wegovy'
			? [['0.25mg', 'starter dose'], ['0.5mg', ''], ['1mg', ''], ['1.7mg', ''], ['2.4mg', 'maximum dose']]
			: [['2.5mg', 'starter dose'], ['5mg', ''], ['7.5mg', ''], ['10mg', ''], ['12.5mg', ''], ['15mg', 'maximum dose']];

		doses.forEach(function (d) {
			var label = document.createElement('label');
			label.className = 'radio-item';
			label.innerHTML = '<input type="radio" name="current-dose" value="' + d[0] + '" data-action="set-current-dose" /><span>' + d[0] + (d[1] ? ' (' + d[1] + ')' : '') + '</span>';
			group.appendChild(label);
		});
	}

	function setCurrentDose(value) {
		state.userData.currentDose = value;
		pushScreen(4);
	}

	function setAge(value) {
		state.userData.age = value;
		if (value === 'under-18') {
			showIneligible("Our weight loss plan isn't suitable for people under 18 years old.");
		} else if (value === '75-over') {
			showIneligible("Our weight loss plan isn't suitable for people over 75 years old.");
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

		var isAsian = (state.userData.ethnicity || '').indexOf('asian') !== -1;
		var min = isAsian ? (cfg.minBmiAsian || 23) : (cfg.minBmiDefault || 27);
		var msg = $('bmi-message');
		if (msg) {
			msg.textContent = bmi >= min
				? 'Great news — based on your BMI, you may be eligible for clinically-supported weight loss treatment.'
				: 'Our clinical team will review your full profile to determine the most appropriate next steps for you.';
		}
	}

	function checkBMIEligibility() {
		var bmi = parseFloat(state.userData.bmi || '0');
		var isAsian = (state.userData.ethnicity || '').indexOf('asian') !== -1;
		var min = isAsian ? (cfg.minBmiAsian || 23) : (cfg.minBmiDefault || 27);
		if (bmi < min) {
			showIneligible('Based on your BMI of ' + state.userData.bmi + ', weight loss medication is not clinically appropriate at this time. A BMI of ' + min + ' or above is required' + (isAsian ? ' (adjusted for South Asian ethnicity)' : '') + '.');
		} else {
			pushScreen(9);
		}
	}

	function setDiabetes(value) {
		state.userData.diabetes = value;
		nextScreen();
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
		var disqualifying = values.some(function (v) {
			return DISQUALIFYING_CONDITIONS.indexOf(v) !== -1;
		});

		if (disqualifying) {
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

	function setCurrentMeds(value) {
		state.userData.currentMeds = value;
		nextScreen();
	}

	function setCurrentMedsOther() {
		state.userData.currentMeds = 'other';
		pushScreen('14a');
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

	function setPregnancyGate(answer) {
		if (answer === 'yes') {
			showIneligible('For safety reasons, weight loss medications cannot be prescribed during pregnancy, when planning to become pregnant, or while breastfeeding.');
		} else {
			state.userData.pregnant = 'no';
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
		if (age >= 75) {
			showIneligible("Our weight loss plan isn't suitable for people over 75 years old.");
			return;
		}

		err.style.display = 'none';
		state.userData.dob = iso;
		nextScreen();
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
		state.selectedTreatment = value;
		updateTreatmentCards();
		updateSubmitButton();
	}

	function updateTreatmentCards() {
		var w = $('wegovy-card');
		var m = $('mounjaro-card');
		if (w) w.classList.toggle('selected', state.selectedTreatment === 'wegovy');
		if (m) m.classList.toggle('selected', state.selectedTreatment === 'mounjaro');
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
		ajax('tc_eligibility_save', payload).then(function (data) {
			if (data.eligible === false) {
				showIneligible(data.reason || 'You do not meet the eligibility criteria.');
				state.isSubmitting = false;
				return;
			}

			state.assessmentId = data.assessment_id || state.assessmentId;
			if (data.nonce) cfg.nonce = data.nonce;
			setCookie(state.assessmentId);

			$('confirmed-name').textContent = state.userData.firstName || '';
			$('confirmed-email').textContent = state.userData.email || '';
			updateConfirmedTreatmentBanner();
			showScreen('confirmed');
			state.isSubmitting = false;
		}).catch(function (e) {
			state.isSubmitting = false;
			if (btn) { btn.disabled = false; btn.textContent = 'Submit Assessment & Continue to Checkout'; }
			alert(e.message || 'Something went wrong submitting your assessment. Please try again.');
		});
	}

	function continueToCheckout() {
		var btn = $('continue-to-checkout-button');
		if (btn) { btn.disabled = true; btn.textContent = 'Loading...'; }

		ajax('tc_eligibility_add_to_cart', {}).then(function (data) {
			window.location.href = data.checkout_url || cfg.checkoutUrl || '/checkout/';
		}).catch(function (e) {
			if (btn) { btn.disabled = false; btn.textContent = 'Continue to Checkout →'; }
			alert(e.message || 'Could not proceed to checkout. Please contact us.');
		});
	}

	function updateConfirmedTreatmentBanner() {
		var name = state.selectedTreatment === 'mounjaro' ? 'Mounjaro' : 'Wegovy';
		var price = state.selectedTreatment === 'mounjaro' ? '£159/month · Starting dose (2.5mg)' : '£109/month · Starting dose (0.25mg)';
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
			case 'set-current-meds-other': setCurrentMedsOther(); break;
			case 'set-allergies': setAllergies(value); break;
			case 'continue-allergies': continueAllergies(); break;
			case 'set-pregnancy-gate': setPregnancyGate(value); break;
			case 'set-goal-weight-q': setGoalWeightQ(value); break;
			case 'save-dob': saveDOB(); break;
			case 'save-address': saveAddress(); break;
			case 'select-treatment': selectTreatment(value); break;
			case 'submit-assessment': submitAssessment(); break;
			case 'continue-to-checkout': continueToCheckout(); break;
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
		else if (action === 'set-current-meds' && t.checked) setCurrentMeds(t.value);

		if (t.name === 'pregnant' || t.name === 'breastfeeding' || t.name === 'conceive') {
			updateFemaleScreeningButton();
		}

		if (t.name === 'weight-unit' || t.name === 'height-unit') {
			toggleUnitInputs();
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
