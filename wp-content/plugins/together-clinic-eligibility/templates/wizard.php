<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wegovy_img         = TC_ELIGIBILITY_URL . 'assets/img/wegovy.jpg';
$mounjaro_img       = TC_ELIGIBILITY_URL . 'assets/img/mounjaro.png';
$wegovy_tablets_img = TC_ELIGIBILITY_URL . 'assets/img/wegovy-tablets.png';
$foundayo_img       = TC_ELIGIBILITY_URL . 'assets/img/foundayo.png';
$logo_img           = TC_ELIGIBILITY_URL . 'assets/img/together-clinic-logo.png';
?>
<div class="tc-eligibility" id="tc-eligibility-root">
	<div class="tc-container">

		<!-- Screen 1: Agreement -->
		<div id="screen-1" class="screen active">
			<div class="progress-section">
				<div class="progress-bar-container">
					<div class="progress-percentage">5%</div>
					<div class="progress-bar"><div class="progress-fill" style="width: 5%"></div></div>
				</div>
			</div>
			<h1>Do you agree to the following?</h1>
			<div class="checkbox-group" id="agreement-group">
				<label class="checkbox-item"><input type="checkbox" class="agreement-checkbox" data-index="0" /><span>I am completing this consultation for myself and to the best of my knowledge</span></label>
				<label class="checkbox-item"><input type="checkbox" class="agreement-checkbox" data-index="1" /><span>I will disclose any medical conditions, serious illnesses or operations I have had</span></label>
				<label class="checkbox-item"><input type="checkbox" class="agreement-checkbox" data-index="2" /><span>I will disclose any prescription medications I am currently taking and agree to use only one weight loss treatment at a time</span></label>
				<label class="checkbox-item"><input type="checkbox" class="agreement-checkbox" data-index="3" /><span>I agree to the Terms &amp; Conditions, Terms of Sale, and confirm that I have read the Privacy Policy</span></label>
				<label class="checkbox-item"><input type="checkbox" class="agreement-checkbox" data-index="4" /><span>I understand that withholding or providing false information can severely harm my health and may result in life-threatening consequences</span></label>
			</div>
			<button class="button button-primary" id="agree-continue" data-action="agree-continue" disabled>Agree and start consultation &rarr;</button>
		</div>

		<!-- Screen 1b: Early Capture -->
		<div id="screen-1b" class="screen">
			<div class="progress-section">
				<div class="progress-bar-container">
					<div class="progress-percentage">20%</div>
					<div class="progress-bar"><div class="progress-fill" style="width: 20%"></div></div>
				</div>
			</div>
			<h2>Let's save your assessment</h2>
			<p>Enter your details so our clinicians can send your eligibility result and support you with the next steps if treatment is appropriate.</p>
			<div class="form-group">
				<div class="form-grid-2">
					<div><label class="form-label">First name</label><input type="text" class="form-input" id="early-first-name" placeholder="e.g. Sarah" autocomplete="given-name" /></div>
					<div><label class="form-label">Last name</label><input type="text" class="form-input" id="early-last-name" placeholder="e.g. Jones" autocomplete="family-name" /></div>
				</div>
			</div>
			<div class="form-group"><label class="form-label">Email address</label><input type="email" class="form-input" id="early-email" placeholder="e.g. sarah@example.com" autocomplete="email" /></div>
			<div class="form-group"><label class="form-label">Phone number</label><input type="tel" class="form-input" id="early-phone" placeholder="e.g. 07XXX XXXXXX" autocomplete="tel" /></div>
			<div id="early-form-error" class="error-message" style="display:none;"></div>
			<div class="security-badge">
				<svg class="security-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
				<span>Your details are kept confidential and used only for your clinical assessment and treatment support.</span>
			</div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="early-continue">Continue</button>
			</div>
		</div>

		<!-- Screen 2: User Type -->
		<div id="screen-2" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">10%</div><div class="progress-bar"><div class="progress-fill" style="width: 10%"></div></div></div></div>
			<h2>Are you currently using weight loss medication?</h2>
			<button class="pathway-card" data-action="set-user-type" data-value="new">
				<div class="pathway-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.912 5.813a2 2 0 0 0 1.275 1.275L21 12l-5.813 1.912a2 2 0 0 0-1.275 1.275L12 21l-1.912-5.813a2 2 0 0 0-1.275-1.275L3 12l5.813-1.912a2 2 0 0 0 1.275-1.275L12 3z"/></svg></div>
				<strong class="pathway-title">I'm not taking it now</strong>
				<span class="pathway-subtitle">I have never used weight loss medication, or I stopped more than 3 months ago</span>
			</button>
			<button class="pathway-card" data-action="set-user-type" data-value="switching">
				<div class="pathway-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9h16"/><path d="M8 5l-4 4 4 4"/><path d="M20 15H4"/><path d="M16 19l4-4-4-4"/></svg></div>
				<strong class="pathway-title">I'm taking it now, or stopped recently</strong>
				<span class="pathway-subtitle">I use weight loss medication from another provider, or stopped in the last 3 months</span>
			</button>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 3: Provider -->
		<div id="screen-3" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">15%</div><div class="progress-bar"><div class="progress-fill" style="width: 15%"></div></div></div></div>
			<h2>Where do you currently get your weight loss medication?</h2>
			<div class="radio-group">
				<?php
				$providers = [
					'boots'                  => 'Boots',
					'lloyds-pharmacy'        => 'Lloyds Pharmacy',
					'asda'                   => 'ASDA',
					'juniper'                => 'Juniper',
					'numan'                  => 'Numan',
					'medexpress'             => 'MedExpress',
					'simple-online-pharmacy' => 'Simple Online Pharmacy',
					'other'                  => 'Other',
					'prefer-not-to-say'      => 'Prefer not to say',
				];
				foreach ( $providers as $value => $label ) :
					?>
					<label class="radio-item">
						<input type="radio" name="provider" value="<?php echo esc_attr( $value ); ?>" data-action="set-provider" />
						<span><?php echo esc_html( $label ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 3a: Current Medication -->
		<div id="screen-3a" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">18%</div><div class="progress-bar"><div class="progress-fill" style="width: 18%"></div></div></div></div>
			<h2>Which medication are you currently taking?</h2>
			<?php
			/*
			 * Generated from the canonical dose ladder, not hardcoded: every
			 * treatment the clinic sells must be declarable here. A switcher
			 * who cannot name their actual medication picks the nearest wrong
			 * one, which then drives the dose list and reaches the prescriber
			 * as false clinical history.
			 */
			$tc_injection_icon = '<path d="M10.5 20.5 10.5 3.5"/><path d="M14.5 20.5 14.5 3.5"/><rect x="7" y="3" width="10" height="4" rx="1"/><rect x="7" y="17" width="10" height="4" rx="1"/>';
			$tc_tablet_icon    = '<rect x="3" y="8" width="18" height="8" rx="4"/><path d="M12 8v8"/>';

			foreach ( array_keys( TC_Dose_Ladder::ladders() ) as $tc_treatment ) :
				$tc_desc = TC_Variation_Map::treatment_descriptor( $tc_treatment );
				$tc_icon = ( $tc_desc['form'] === 'injection' ) ? $tc_injection_icon : $tc_tablet_icon;
				?>
				<button class="pathway-card" data-action="set-current-medication" data-value="<?php echo esc_attr( $tc_treatment ); ?>">
					<div class="pathway-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $tc_icon; // phpcs:ignore WordPress.Security.EscapeOutput ?></svg></div>
					<strong class="pathway-title"><?php echo esc_html( TC_Variation_Map::treatment_label( $tc_treatment ) ); ?></strong><span class="pathway-subtitle"><?php echo esc_html( $tc_desc['text'] ); ?></span>
				</button>
			<?php endforeach; ?>
			<button class="pathway-card" data-action="set-current-medication" data-value="other">
				<div class="pathway-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg></div>
				<strong class="pathway-title">Another medicine</strong><span class="pathway-subtitle">For example Ozempic, Saxenda or Rybelsus</span>
			</button>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 3b: Current Dose -->
		<div id="screen-3b" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">19%</div><div class="progress-bar"><div class="progress-fill" style="width: 19%"></div></div></div></div>
			<h2>What dose are you currently taking?</h2>
			<div class="radio-group" id="dose-group"></div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 3c: Last dose and starting weight (rules WM-2026-10-v1, 3A.3) -->
		<div id="screen-3c" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">20%</div><div class="progress-bar"><div class="progress-fill" style="width: 20%"></div></div></div></div>
			<h2>About your current treatment</h2>
			<p>Your prescriber needs this to choose a safe dose. Please have your previous prescription or provider record ready: the prescriber will ask to see it.</p>
			<div class="form-group">
				<label class="form-label">Date of your last dose *</label>
				<div class="dob-inputs">
					<input type="text" id="last-dose-day" class="form-input dob-input" inputmode="numeric" placeholder="DD" maxlength="2" aria-label="Day of last dose" />
					<input type="text" id="last-dose-month" class="form-input dob-input" inputmode="numeric" placeholder="MM" maxlength="2" aria-label="Month of last dose" />
					<input type="text" id="last-dose-year" class="form-input dob-input" inputmode="numeric" placeholder="YYYY" maxlength="4" aria-label="Year of last dose" />
				</div>
			</div>
			<div class="form-group">
				<label class="form-label">Your weight when you first started weight loss medication *</label>
				<div class="unit-selector">
					<label class="unit-option"><input type="radio" name="start-weight-unit" value="kg" checked /><span>kg</span></label>
					<label class="unit-option"><input type="radio" name="start-weight-unit" value="st" /><span>st/lbs</span></label>
				</div>
				<input type="number" id="start-weight-kg" class="form-input" placeholder="Weight in kg" step="0.1" min="40" max="300" />
				<div id="start-weight-st-inputs" style="display:none;" class="grid-input-group">
					<div><label class="form-label">Stone</label><input type="number" id="start-weight-stone" class="form-input" placeholder="St" min="6" max="45" /></div>
					<div><label class="form-label">Pounds</label><input type="number" id="start-weight-pounds" class="form-input" placeholder="Lbs" min="0" max="13" /></div>
				</div>
			</div>
			<div id="current-treatment-error" class="error-message" style="display:none;"></div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="save-current-treatment">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 4: Age -->
		<div id="screen-4" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">25%</div><div class="progress-bar"><div class="progress-fill" style="width: 25%"></div></div></div></div>
			<h2>How old are you?</h2>
			<div class="radio-group">
				<label class="radio-item"><input type="radio" name="age" value="under-18" data-action="set-age" /><span>Under 18</span></label>
				<label class="radio-item"><input type="radio" name="age" value="18-85" data-action="set-age" /><span>18 to 85</span></label>
				<label class="radio-item"><input type="radio" name="age" value="86-over" data-action="set-age" /><span>86 or over</span></label>
			</div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 5: Ethnicity -->
		<div id="screen-5" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">30%</div><div class="progress-bar"><div class="progress-fill" style="width: 30%"></div></div></div></div>
			<h2>Which ethnicity are you?</h2>
			<p>Some health risks linked to weight differ between ethnic backgrounds. This helps your prescriber assess your health.</p>
			<div class="radio-group">
				<?php
				$ethnicities = [
					'south asian'       => 'South Asian (for example Indian, Pakistani, Bangladeshi, Sri Lankan)',
					'chinese'           => 'Chinese',
					'other asian'       => 'Other Asian background',
					'middle eastern'    => 'Middle Eastern or Arab',
					'black african'     => 'Black African',
					'african-caribbean' => 'Black Caribbean',
					'mixed'             => 'Mixed or multiple ethnic groups',
					'white'             => 'White',
					'other'             => 'Other ethnic group',
					'prefer not to say' => 'Prefer not to say',
				];
				foreach ( $ethnicities as $value => $label ) :
					?>
					<label class="radio-item"><input type="radio" name="ethnicity" value="<?php echo esc_attr( $value ); ?>" data-action="set-ethnicity" /><span><?php echo esc_html( $label ); ?></span></label>
				<?php endforeach; ?>
			</div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 6: Sex -->
		<div id="screen-6" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">35%</div><div class="progress-bar"><div class="progress-fill" style="width: 35%"></div></div></div></div>
			<h2>What sex were you assigned at birth?</h2>
			<div class="gender-buttons">
				<button class="button" data-action="set-sex" data-value="male">Male</button>
				<button class="button" data-action="set-sex" data-value="female">Female</button>
			</div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 6b: Female Screening -->
		<div id="screen-6b" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">38%</div><div class="progress-bar"><div class="progress-fill" style="width: 38%"></div></div></div></div>
			<h2>A few important questions to keep you safe</h2>
			<p>These questions help our clinicians ensure treatment is appropriate and safe for you.</p>
			<?php foreach ( [ 'pregnant' => 'Are you currently pregnant?', 'breastfeeding' => 'Are you currently breastfeeding?', 'conceive' => 'Are you trying to conceive?' ] as $name => $question ) : ?>
				<div class="screening-question">
					<h3><?php echo esc_html( $question ); ?></h3>
					<div class="radio-button-group">
						<div class="radio-button">
							<input type="radio" id="<?php echo esc_attr( $name ); ?>-yes" name="<?php echo esc_attr( $name ); ?>" value="yes" />
							<label class="radio-button-label" for="<?php echo esc_attr( $name ); ?>-yes">Yes</label>
						</div>
						<div class="radio-button">
							<input type="radio" id="<?php echo esc_attr( $name ); ?>-no" name="<?php echo esc_attr( $name ); ?>" value="no" />
							<label class="radio-button-label" for="<?php echo esc_attr( $name ); ?>-no">No</label>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" id="female-screening-continue" data-action="female-screening-continue" disabled>Continue</button>
			</div>
		</div>

		<!-- Screen 7: Weight -->
		<div id="screen-7" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">40%</div><div class="progress-bar"><div class="progress-fill" style="width: 40%"></div></div></div></div>
			<h2>What is your weight?</h2>
			<div class="unit-selector">
				<label class="unit-option"><input type="radio" name="weight-unit" value="kg" checked /><span>kg</span></label>
				<label class="unit-option"><input type="radio" name="weight-unit" value="st" /><span>st/lbs</span></label>
			</div>
			<input type="number" id="weight-kg-input" class="form-input" placeholder="Weight in kg" step="0.1" min="40" max="250" />
			<div id="weight-st-inputs" style="display:none;" class="grid-input-group">
				<div><label class="form-label">Stone</label><input type="number" id="weight-stone" class="form-input" placeholder="St" min="6" max="40" /></div>
				<div><label class="form-label">Pounds</label><input type="number" id="weight-pounds" class="form-input" placeholder="Lbs" min="0" max="13" /></div>
			</div>
			<div id="weight-error" class="error-message" style="display:none;"></div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="save-weight">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 8: Height -->
		<div id="screen-8" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">45%</div><div class="progress-bar"><div class="progress-fill" style="width: 45%"></div></div></div></div>
			<h2>What is your height?</h2>
			<div class="unit-selector">
				<label class="unit-option"><input type="radio" name="height-unit" value="cm" checked /><span>cm</span></label>
				<label class="unit-option"><input type="radio" name="height-unit" value="ft" /><span>ft/in</span></label>
			</div>
			<input type="number" id="height-cm-input" class="form-input" placeholder="Height in cm" step="0.1" min="120" max="230" />
			<div id="height-ft-inputs" style="display:none;" class="grid-input-group">
				<div><label class="form-label">Feet</label><input type="number" id="height-feet" class="form-input" placeholder="Ft" min="4" max="7" /></div>
				<div><label class="form-label">Inches</label><input type="number" id="height-inches" class="form-input" placeholder="In" min="0" max="11" /></div>
			</div>
			<div id="height-error" class="error-message" style="display:none;"></div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="calculate-bmi">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 8b: BMI Result -->
		<div id="screen-8b" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">48%</div><div class="progress-bar"><div class="progress-fill" style="width: 48%"></div></div></div></div>
			<h2>Your BMI Result</h2>
			<p>Based on the weight and height you provided.</p>
			<div class="bmi-result-card">
				<div class="bmi-result-accent"></div>
				<div class="bmi-result-content">
					<p class="info-label">Your Body Mass Index</p>
					<p class="bmi-number" id="bmi-display">-</p>
					<p class="bmi-category-label" id="bmi-category">-</p>
				</div>
			</div>
			<div class="bmi-next-steps">
				<div class="bmi-next-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
				<p class="bmi-next-text" id="bmi-message">This is based on the weight and height you entered. Your prescriber will check your weight and height on a video call before any treatment.</p>
			</div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="check-bmi-eligibility">Continue &rarr;</button>
			</div>
		</div>

		<!-- Screen 9: Diabetes -->
		<div id="screen-9" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">50%</div><div class="progress-bar"><div class="progress-fill" style="width: 50%"></div></div></div></div>
			<h2>Have you been diagnosed with diabetes?</h2>
			<p>Diabetes treatments can impact the way the medication included with our weight loss plan works.</p>
			<div class="radio-group">
				<label class="radio-item"><input type="radio" name="diabetes" value="type1" data-action="set-diabetes" /><span>I have type 1 diabetes</span></label>
				<label class="radio-item"><input type="radio" name="diabetes" value="type2-meds" data-action="set-diabetes" /><span>I have type 2 diabetes and take medication for it</span></label>
				<label class="radio-item"><input type="radio" name="diabetes" value="type2-diet" data-action="set-diabetes" /><span>I have type 2 diabetes controlled by diet</span></label>
				<label class="radio-item"><input type="radio" name="diabetes" value="family" data-action="set-diabetes" /><span>No, but there is history of diabetes in my family</span></label>
				<label class="radio-item"><input type="radio" name="diabetes" value="pre" data-action="set-diabetes" /><span>I have pre-diabetes</span></label>
				<label class="radio-item"><input type="radio" name="diabetes" value="none" data-action="set-diabetes" /><span>I don't have diabetes</span></label>
			</div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 10: Conditions -->
		<div id="screen-10" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">55%</div><div class="progress-bar"><div class="progress-fill" style="width: 55%"></div></div></div></div>
			<h2>Do any of the following statements apply to you?</h2>
			<p>These conditions can lead to serious complications when losing weight or taking weight loss medications.</p>
			<div class="checkbox-group-form" id="conditions-group"></div>
			<div id="conditions-error" class="error-message" style="display:none;"></div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="proceed-conditions">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 10a: Bariatric Timing -->
		<div id="screen-10a" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">57%</div><div class="progress-bar"><div class="progress-fill" style="width: 57%"></div></div></div></div>
			<h2>Was your bariatric operation in the last 6 months?</h2>
			<div class="two-col-buttons">
				<button class="button button-secondary" data-action="bariatric-timing" data-value="yes">Yes</button>
				<button class="button button-secondary" data-action="bariatric-timing" data-value="no">No</button>
			</div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 10b: Bariatric Details -->
		<div id="screen-10b" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">60%</div><div class="progress-bar"><div class="progress-fill" style="width: 60%"></div></div></div></div>
			<h2>Please tell us further details:</h2>
			<ul style="color:#374151;margin-bottom:24px;padding-left:20px;list-style:disc;">
				<li>What type of bariatric surgery did you have?</li>
				<li>When was the surgery?</li>
				<li>Did you experience any post-surgical complications?</li>
				<li>What was your BMI before surgery?</li>
				<li>Are you still losing weight?</li>
				<li>Are you undergoing any ongoing monitoring?</li>
			</ul>
			<textarea class="form-textarea" id="bariatric-details" placeholder="Please provide details..."></textarea>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="goto" data-value="11">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 11: Weight-Related Conditions -->
		<div id="screen-11" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">62%</div><div class="progress-bar"><div class="progress-fill" style="width: 62%"></div></div></div></div>
			<h2>Do any of the following statements apply to you?</h2>
			<p>These conditions are often weight related and may be improved as a result of losing weight.</p>
			<div class="checkbox-group-form" id="weight-conditions-group"></div>
			<div id="weight-conditions-error" class="error-message" style="display:none;"></div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="proceed-weight-conditions">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 11a: Mental Health Details -->
		<div id="screen-11a" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">65%</div><div class="progress-bar"><div class="progress-fill" style="width: 65%"></div></div></div></div>
			<h2>Please tell us more about your mental health condition and how you manage it</h2>
			<textarea class="form-textarea" id="mental-health-details" placeholder="Please provide details..."></textarea>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="goto" data-value="12">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 12: Other Conditions -->
		<div id="screen-12" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">68%</div><div class="progress-bar"><div class="progress-fill" style="width: 68%"></div></div></div></div>
			<h2>Do you have any other medical conditions?</h2>
			<p>Our clinicians need to know your full medical history.</p>
			<div class="two-col-buttons">
				<button class="button button-secondary" data-action="set-other-conditions" data-value="yes">Yes</button>
				<button class="button button-secondary" data-action="set-other-conditions" data-value="no">No</button>
			</div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 12a: Other Conditions Details -->
		<div id="screen-12a" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">70%</div><div class="progress-bar"><div class="progress-fill" style="width: 70%"></div></div></div></div>
			<h2>Please list any other medical conditions you have</h2>
			<textarea class="form-textarea" id="other-conditions" placeholder="My health conditions are..."></textarea>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="goto" data-value="13">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 13: Previous Medications -->
		<div id="screen-13" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">72%</div><div class="progress-bar"><div class="progress-fill" style="width: 72%"></div></div></div></div>
			<h2>Have you ever taken any of the following medications to help you lose weight?</h2>
			<div class="checkbox-group-form" id="prev-meds-group"></div>
			<div id="prev-meds-error" class="error-message" style="display:none;"></div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="proceed-prev-meds">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 13-weight -->
		<div id="screen-13-weight" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">74%</div><div class="progress-bar"><div class="progress-fill" style="width: 74%"></div></div></div></div>
			<h2 id="prev-weight-question">What was your weight in kg before starting?</h2>
			<input type="number" id="prev-weight" class="form-input" placeholder="Weight" step="0.1" />
			<div class="unit-selector">
				<label class="unit-option"><input type="radio" name="prev-weight-unit" value="kg" checked /><span>kg</span></label>
				<label class="unit-option"><input type="radio" name="prev-weight-unit" value="st" /><span>st/lbs</span></label>
			</div>
			<div class="button-group">
				<button class="button button-secondary" data-action="skip-prev-weight">Skip</button>
				<button class="button button-primary" data-action="save-prev-weight">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 14: Current Meds -->
		<div id="screen-14" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">76%</div><div class="progress-bar"><div class="progress-fill" style="width: 76%"></div></div></div></div>
			<h2>Do you take any medicines?</h2>
			<p>Include anything prescribed, the contraceptive pill, and anything you buy from a pharmacy, shop or online, including herbal remedies and supplements.</p>
			<div class="two-col-buttons">
				<button class="button button-secondary" data-action="set-current-meds-choice" data-value="yes">Yes</button>
				<button class="button button-secondary" data-action="set-current-meds-choice" data-value="none">No</button>
			</div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 14a: Medication List -->
		<div id="screen-14a" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">77%</div><div class="progress-bar"><div class="progress-fill" style="width: 77%"></div></div></div></div>
			<h2>Please list every medicine you take</h2>
			<p>Include the name, strength and how often you take it, if you know. Include the contraceptive pill, and anything bought without a prescription.</p>
			<textarea class="form-textarea" id="medication-list" placeholder="List all your current medicines..."></textarea>
			<div id="medication-list-error" class="error-message" style="display:none;"></div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="save-medication-list">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 15: Allergies -->
		<div id="screen-15" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">78%</div><div class="progress-bar"><div class="progress-fill" style="width: 78%"></div></div></div></div>
			<h2>Do you have any allergies?</h2>
			<div class="two-col-buttons">
				<button class="button button-secondary" data-action="set-allergies" data-value="yes">Yes</button>
				<button class="button button-secondary" data-action="set-allergies" data-value="no">No</button>
			</div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 15a: Allergies Details -->
		<div id="screen-15a" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">79%</div><div class="progress-bar"><div class="progress-fill" style="width: 79%"></div></div></div></div>
			<h2>Please list your allergies</h2>
			<textarea class="form-textarea" id="allergies" placeholder="My allergies are..."></textarea>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="continue-allergies">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 15b: Contraception (rules WM-2026-10-v1) -->
		<div id="screen-15b" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">80%</div><div class="progress-bar"><div class="progress-fill" style="width: 80%"></div></div></div></div>
			<h2>Could you become pregnant?</h2>
			<p>For example, you still have periods and have not been sterilised or had a hysterectomy.</p>
			<div class="two-col-buttons">
				<button class="button button-secondary" data-action="set-could-conceive" data-value="yes">Yes</button>
				<button class="button button-secondary" data-action="set-could-conceive" data-value="no">No</button>
			</div>
			<div id="contraception-section" style="display:none;margin-top:24px;">
				<h3>Which contraception do you use?</h3>
				<div class="radio-group">
					<label class="radio-item"><input type="radio" name="contraception" value="pill" /><span>The pill (combined or progestogen-only)</span></label>
					<label class="radio-item"><input type="radio" name="contraception" value="lng-iud" /><span>Coil, implant or injection</span></label>
					<label class="radio-item"><input type="radio" name="contraception" value="barrier" /><span>Condoms or another barrier method only</span></label>
					<label class="radio-item"><input type="radio" name="contraception" value="other" /><span>Something else</span></label>
					<label class="radio-item"><input type="radio" name="contraception" value="none" /><span>I don't use contraception</span></label>
				</div>
				<label class="checkbox-item" style="background:white;border:2px solid #e5e7eb;border-radius:12px;padding:16px;margin:16px 0;">
					<input type="checkbox" id="consent-contraception" /><span>I will use effective contraception while taking weight loss medication and for at least 2 months after stopping, and I will tell the prescriber straight away if I could be pregnant. I understand some of these medicines make the pill less reliable.</span>
				</label>
				<div id="contraception-error" class="error-message" style="display:none;"></div>
				<button class="button button-primary" data-action="save-contraception">Next &rarr;</button>
			</div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 16: Goal Weight Q -->
		<div id="screen-16" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">81%</div><div class="progress-bar"><div class="progress-fill" style="width: 81%"></div></div></div></div>
			<h2>Do you have a goal weight you would like to achieve?</h2>
			<div class="two-col-buttons">
				<button class="button button-secondary" data-action="set-goal-weight-q" data-value="yes">Yes</button>
				<button class="button button-secondary" data-action="set-goal-weight-q" data-value="no">No</button>
			</div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 17: Goal Weight Input -->
		<div id="screen-17" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">82%</div><div class="progress-bar"><div class="progress-fill" style="width: 82%"></div></div></div></div>
			<h2>What is your goal weight?</h2>
			<input type="number" id="goal-weight" class="form-input" placeholder="Goal weight" step="0.1" />
			<div class="unit-selector">
				<label class="unit-option"><input type="radio" name="goal-unit" value="kg" checked /><span>kg</span></label>
				<label class="unit-option"><input type="radio" name="goal-unit" value="st" /><span>st/lbs</span></label>
			</div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="next">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 18: DOB -->
		<div id="screen-18" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">85%</div><div class="progress-bar"><div class="progress-fill" style="width: 85%"></div></div></div></div>
			<h2>Almost there &mdash; just a couple more details</h2>
			<p>We need your date of birth to verify your eligibility.</p>
			<div class="dob-identity-card">
				<div class="dob-identity-avatar" id="dob-avatar-initials">?</div>
				<div>
					<div class="dob-identity-label">Completing as</div>
					<div class="dob-identity-value" id="completing-as">-</div>
					<div class="dob-identity-email" id="completing-as-email"></div>
				</div>
			</div>
			<div class="form-group">
				<label class="form-label">Date of Birth *</label>
				<div class="dob-inputs">
					<input type="text" id="dob-day" class="form-input dob-input" inputmode="numeric" placeholder="DD" maxlength="2" autocomplete="bday-day" aria-label="Day" />
					<input type="text" id="dob-month" class="form-input dob-input" inputmode="numeric" placeholder="MM" maxlength="2" autocomplete="bday-month" aria-label="Month" />
					<input type="text" id="dob-year" class="form-input dob-input" inputmode="numeric" placeholder="YYYY" maxlength="4" autocomplete="bday-year" aria-label="Year" />
				</div>
				<p style="font-size:13px;color:#6b7280;margin-top:8px;margin-bottom:0;">For example, 17 03 1985</p>
				<div id="dob-error" class="error-message" style="display:none;margin-top:8px;"></div>
			</div>
			<div class="dob-trust-row">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
				Your information is encrypted and stored securely
			</div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="save-dob">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 18a: Ages 75 to 85 (rules WM-2026-10-v2, rule S1A) -->
		<div id="screen-18a" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">87%</div><div class="progress-bar"><div class="progress-fill" style="width: 87%"></div></div></div></div>
			<h2>A few more questions for people aged 75 and over</h2>
			<p>Your prescriber needs these answers to check whether treatment would be safe for you. Please answer as best you can.</p>
			<?php
			$tc_s1a_yes_no = [
				's1a-falls'               => 'Have you had a fall in the last 12 months?',
				's1a-fracture'            => 'Have you ever broken a bone from a minor fall or knock (for example a fall from standing height)?',
				's1a-prisma-limit'        => 'In general, do you have any health problems that require you to limit your activities?',
				's1a-prisma-help'         => 'Do you need someone to help you on a regular basis?',
				's1a-prisma-home'         => 'In general, do you have any health problems that require you to stay at home?',
				's1a-prisma-count'        => 'If you need help, can you count on someone close to you?',
				's1a-prisma-aid'          => 'Do you regularly use a stick, walker or wheelchair to get about?',
			];
			foreach ( $tc_s1a_yes_no as $name => $question ) :
				?>
				<div class="screening-question">
					<h3><?php echo esc_html( $question ); ?></h3>
					<div class="radio-button-group">
						<div class="radio-button">
							<input type="radio" id="<?php echo esc_attr( $name ); ?>-yes" name="<?php echo esc_attr( $name ); ?>" value="yes" />
							<label class="radio-button-label" for="<?php echo esc_attr( $name ); ?>-yes">Yes</label>
						</div>
						<div class="radio-button">
							<input type="radio" id="<?php echo esc_attr( $name ); ?>-no" name="<?php echo esc_attr( $name ); ?>" value="no" />
							<label class="radio-button-label" for="<?php echo esc_attr( $name ); ?>-no">No</label>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
			<div class="screening-question">
				<h3>How many different medicines do you take regularly?</h3>
				<div class="radio-group">
					<?php foreach ( TC_Eligibility_Rules::S1A_MEDS_COUNT as $value => $label ) : ?>
						<label class="radio-item"><input type="radio" name="s1a-meds-count" value="<?php echo esc_attr( $value ); ?>" /><span><?php echo esc_html( $label ); ?></span></label>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="screening-question">
				<h3>Do you take blood pressure tablets or water tablets (diuretics)?</h3>
				<div class="radio-group">
					<label class="radio-item"><input type="radio" name="s1a-bp-water" value="yes" /><span>Yes</span></label>
					<label class="radio-item"><input type="radio" name="s1a-bp-water" value="no" /><span>No</span></label>
					<label class="radio-item"><input type="radio" name="s1a-bp-water" value="unsure" /><span>Not sure</span></label>
				</div>
			</div>
			<div class="screening-question">
				<h3>When did you last have a kidney blood test (eGFR)?</h3>
				<div class="radio-group">
					<?php foreach ( TC_Eligibility_Rules::S1A_KIDNEY_TEST as $value => $label ) : ?>
						<label class="radio-item"><input type="radio" name="s1a-kidney-test" value="<?php echo esc_attr( $value ); ?>" /><span><?php echo esc_html( $label ); ?></span></label>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="form-group">
				<label class="form-label">If you know them: month and year of that test, and the eGFR result</label>
				<div class="dob-inputs">
					<input type="text" id="s1a-egfr-month" class="form-input dob-input" inputmode="numeric" placeholder="MM" maxlength="2" aria-label="Month of kidney test" />
					<input type="text" id="s1a-egfr-year" class="form-input dob-input" inputmode="numeric" placeholder="YYYY" maxlength="4" aria-label="Year of kidney test" />
					<input type="text" id="s1a-egfr-result" class="form-input dob-input" inputmode="numeric" placeholder="eGFR" maxlength="3" aria-label="eGFR result" />
				</div>
				<p style="font-size:13px;color:#6b7280;margin-top:8px;margin-bottom:0;">Leave these blank if you do not know. Your prescriber will check your records.</p>
			</div>
			<div id="s1a-error" class="error-message" style="display:none;"></div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="save-s1a">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 19: Address -->
		<div id="screen-19" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">90%</div><div class="progress-bar"><div class="progress-fill" style="width: 90%"></div></div></div></div>
			<h2>Where should we deliver your treatment?</h2>
			<p>We'll use this address to ship your medication securely and discreetly.</p>
			<div class="form-group"><label class="form-label">Address Line 1 *</label><input type="text" id="address-line1" class="form-input" placeholder="Street address" autocomplete="address-line1" /></div>
			<div class="form-group"><label class="form-label">Address Line 2</label><input type="text" id="address-line2" class="form-input" placeholder="Apartment, suite, etc." autocomplete="address-line2" /></div>
			<div class="form-grid-2">
				<div class="form-group"><label class="form-label">City/Town *</label><input type="text" id="city" class="form-input" placeholder="London" autocomplete="address-level2" /></div>
				<div class="form-group"><label class="form-label">Postcode *</label><input type="text" id="postcode" class="form-input" placeholder="SW1A 1AA" autocomplete="postal-code" /></div>
			</div>
			<div class="form-group"><label class="form-label">Country *</label>
				<select id="country" class="form-select">
					<option value="United Kingdom">United Kingdom</option>
					<option value="England">England</option>
					<option value="Scotland">Scotland</option>
					<option value="Wales">Wales</option>
					<option value="Northern Ireland">Northern Ireland</option>
				</select>
			</div>
			<div id="address-error" class="error-message" style="display:none;"></div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="save-address">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 20: GP and consents (rules WM-2026-10-v2) -->
		<div id="screen-20" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">95%</div><div class="progress-bar"><div class="progress-fill" style="width: 95%"></div></div></div></div>
			<h2>Your GP and your consent</h2>
			<div class="form-group"><label class="form-label">GP Surgery Name</label><input type="text" id="gp-name" class="form-input" placeholder="Surgery name" /></div>
			<div class="form-group"><label class="form-label">GP Surgery Postcode</label><input type="text" id="gp-postcode" class="form-input" placeholder="SW1A 1AA" /></div>
			<label class="checkbox-item" style="background:white;border:2px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:12px;">
				<input type="checkbox" id="consent-id-video" /><span><strong>Required.</strong> I agree to a photo ID check and to a video consultation with a prescriber, where my weight and height will be checked. I understand no medicine is prescribed without this.</span>
			</label>
			<label class="checkbox-item" style="background:white;border:2px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:12px;">
				<input type="checkbox" id="gp-consent-2" /><span><strong>Required.</strong> I agree to the prescriber checking my NHS Summary Care Record before prescribing, and at least every 6 months while I am treated.</span>
			</label>
			<label class="checkbox-item" style="background:white;border:2px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:12px;">
				<input type="checkbox" id="consent-lifestyle" /><span><strong>Required.</strong> I will follow a reduced-calorie diet and increase my physical activity alongside any treatment.</span>
			</label>
			<label class="checkbox-item" style="background:white;border:2px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:24px;">
				<input type="checkbox" id="gp-consent-1" /><span>I agree to Together Clinic telling my GP about any treatment prescribed, including the medicine and dose. <strong id="gp-share-note-under-75">Strongly recommended:</strong><span id="gp-share-note-under-75-text">without this, the prescriber may not be able to treat you safely.</span></span>
			</label>
			<div id="consent-error" class="error-message" style="display:none;"></div>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="save-consents">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 21: Treatment Selection -->
		<div id="screen-21" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">100%</div><div class="progress-bar"><div class="progress-fill" style="width: 100%"></div></div></div></div>
			<div style="text-align:center;margin-bottom:40px;">
				<div style="width:80px;height:80px;border-radius:50%;background:#dcfce7;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:40px;margin:0 auto 24px;">&#10003;</div>
				<h1 style="font-size:32px;margin-bottom:12px;">Your answers are ready for a prescriber</h1>
				<p>Based on your answers, you can have a video consultation with one of our prescribers. They will decide with you whether treatment is suitable.</p>
			</div>
			<h2 style="text-align:center;margin:32px 0 16px;">Your preferred treatment</h2>
			<p style="text-align:center;margin-bottom:24px;">Choose the treatment you would like to discuss. Your prescriber may recommend a different option or dose.</p>
			<div class="treatment-grid" role="group" aria-label="Choose your treatment">
				<button type="button" class="treatment-card" id="wegovy-card" data-action="select-treatment" data-value="wegovy" aria-pressed="false">
					<span class="treatment-selected-pill" aria-hidden="true"><svg class="tsp-check" viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7"></path></svg>Selected</span>
					<div class="treatment-image"><img src="<?php echo esc_url( $wegovy_img ); ?>" alt="Wegovy injection pen" /></div>
					<div class="treatment-body">
						<div class="treatment-body-head">
							<div class="treatment-head-text">
								<span class="treatment-title">Wegovy</span>
								<div class="treatment-price">&pound;109<span class="treatment-price-unit">/month</span></div>
								<p class="treatment-price-note">Starting dose (0.25mg)</p>
							</div>
							<span class="tc-radio" aria-hidden="true"><svg class="tc-radio-check" viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7"></path></svg></span>
						</div>
						<p class="treatment-description">Semaglutide, a once-weekly injection licensed for weight management</p>
						<ul class="treatment-benefits">
							<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Licensed for weight management in the UK</li>
							<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Once-weekly injection</li>
							<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>MHRA approved</li>
						</ul>
					</div>
				</button>
				<button type="button" class="treatment-card" id="mounjaro-card" data-action="select-treatment" data-value="mounjaro" aria-pressed="false">
					<span class="treatment-selected-pill" aria-hidden="true"><svg class="tsp-check" viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7"></path></svg>Selected</span>
					<div class="treatment-image"><img src="<?php echo esc_url( $mounjaro_img ); ?>" alt="Mounjaro injection pen" /></div>
					<div class="treatment-body">
						<div class="treatment-body-head">
							<div class="treatment-head-text">
								<span class="treatment-title">Mounjaro</span>
								<div class="treatment-price">&pound;159<span class="treatment-price-unit">/month</span></div>
								<p class="treatment-price-note">Starting dose (2.5mg)</p>
							</div>
							<span class="tc-radio" aria-hidden="true"><svg class="tc-radio-check" viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7"></path></svg></span>
						</div>
						<p class="treatment-description">Tirzepatide, a once-weekly injection licensed for weight management</p>
						<ul class="treatment-benefits">
							<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Licensed for weight management in the UK</li>
							<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Once-weekly injection</li>
							<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>MHRA approved</li>
						</ul>
					</div>
				</button>
				<button type="button" class="treatment-card" id="wegovy-tablets-card" data-action="select-treatment" data-value="wegovy-tablets" aria-pressed="false">
					<span class="treatment-selected-pill" aria-hidden="true"><svg class="tsp-check" viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7"></path></svg>Selected</span>
					<div class="treatment-image"><img src="<?php echo esc_url( $wegovy_tablets_img ); ?>" alt="Wegovy tablets pack" onerror="this.style.display='none'" /></div>
					<div class="treatment-body">
						<div class="treatment-body-head">
							<div class="treatment-head-text">
								<span class="treatment-title">Wegovy Tablets</span>
								<div class="treatment-price">&pound;99<span class="treatment-price-unit">/month</span></div>
								<p class="treatment-price-note">Starting dose (1.5mg)</p>
							</div>
							<span class="tc-radio" aria-hidden="true"><svg class="tc-radio-check" viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7"></path></svg></span>
						</div>
						<p class="treatment-description">Oral semaglutide &mdash; the same active ingredient as Wegovy injections, taken as a daily tablet instead of a weekly injection.</p>
						<ul class="treatment-benefits">
							<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>No needles &mdash; one tablet a day</li>
							<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Same active ingredient (semaglutide)</li>
							<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Reviewed by a prescriber before dispatch</li>
						</ul>
						<p class="treatment-admin-note">Take one tablet on an empty stomach with a small sip of water, at least 30 minutes before eating, drinking, or taking any other medicines.</p>
					</div>
				</button>
				<button type="button" class="treatment-card" id="foundayo-card" data-action="select-treatment" data-value="foundayo" aria-pressed="false">
					<span class="treatment-selected-pill" aria-hidden="true"><svg class="tsp-check" viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7"></path></svg>Selected</span>
					<div class="treatment-image"><img src="<?php echo esc_url( $foundayo_img ); ?>" alt="Illustration of once-daily tablets" onerror="this.style.display='none'" /></div>
					<div class="treatment-body">
						<div class="treatment-body-head">
							<div class="treatment-head-text">
								<span class="treatment-title">Foundayo</span>
								<div class="treatment-price">&pound;99<span class="treatment-price-unit">/month</span></div>
								<p class="treatment-price-note">Starting dose (0.8mg)</p>
							</div>
							<span class="tc-radio" aria-hidden="true"><svg class="tc-radio-check" viewBox="0 0 24 24"><path d="M5 12.5l4.2 4.2L19 7"></path></svg></span>
						</div>
						<p class="treatment-description">Orforglipron &mdash; the first non-peptide GLP-1 tablet licensed in the UK. A daily tablet with no fasting rules.</p>
						<ul class="treatment-benefits">
							<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Take it any time &mdash; with or without food</li>
							<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>No needles &mdash; one tablet a day</li>
							<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>MHRA approved &mdash; reviewed by a prescriber before dispatch</li>
						</ul>
						<p class="treatment-admin-note">Swallow one tablet whole with water, at any time of day. No waiting period before eating or drinking.</p>
					</div>
				</button>
			</div>
			<div class="success-timeline">
				<h3 style="margin-bottom:16px;">What happens next</h3>
				<div class="timeline-item"><div class="timeline-number">1</div><div class="timeline-content"><p class="timeline-title">Video consultation</p><p class="timeline-desc">We contact you to book a video call. The prescriber checks your photo ID, your weight and height, and your NHS Summary Care Record</p></div></div>
				<div class="timeline-item"><div class="timeline-number">2</div><div class="timeline-content"><p class="timeline-title">Prescriber decision</p><p class="timeline-desc">We place a temporary hold on your card. You are only charged if the prescriber issues a prescription; otherwise the hold is released</p></div></div>
				<div class="timeline-item"><div class="timeline-number">3</div><div class="timeline-content"><p class="timeline-title">Tracked Delivery</p><p class="timeline-desc">Free tracked delivery to your door</p></div></div>
			</div>
			<button class="button button-primary" id="submit-button" data-action="submit-assessment" disabled>Submit Assessment for Review</button>
		</div>

		<!-- Screen: Confirmed -->
		<div id="screen-confirmed" class="screen confirmed-screen">
			<div class="confirmed-success-icon"><svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
			<h2>Assessment Submitted</h2>
			<p style="color:#6b7280;margin-bottom:24px;">Thank you, <strong style="color:#111827;" id="confirmed-name">Name</strong>. Your assessment has been submitted and a confirmation email has been sent to <strong id="confirmed-email">email</strong>.</p>
			<div class="confirmed-treatment-banner">
				<div>
					<div class="confirmed-treatment-name" id="confirmed-treatment-name">Wegovy</div>
					<div class="confirmed-treatment-price" id="confirmed-treatment-price">&pound;109/month &middot; Starting dose (0.25mg)</div>
				</div>
				<span class="confirmed-treatment-badge">Selected</span>
			</div>
			<div class="info-box" style="text-align:left;margin-bottom:16px;">
				<p><strong>You are not charged now.</strong> A prescriber will contact you to book a video consultation. You are only charged if they prescribe treatment, and nothing is dispatched before then.</p>
			</div>
			<div class="success-timeline">
				<h3 style="margin-bottom:16px;">What happens next</h3>
				<div class="timeline-item"><div class="timeline-number">1</div><div class="timeline-content"><p class="timeline-title">Video consultation</p><p class="timeline-desc">We will contact you to book a video call with a prescriber</p></div></div>
				<div class="timeline-item"><div class="timeline-number">2</div><div class="timeline-content"><p class="timeline-title">Prescriber decision</p><p class="timeline-desc">You are only charged if the prescriber issues a prescription</p></div></div>
				<div class="timeline-item"><div class="timeline-number">3</div><div class="timeline-content"><p class="timeline-title">Tracked Delivery</p><p class="timeline-desc">Your medication will be dispatched with free tracked delivery</p></div></div>
			</div>
			<div class="confirmed-contact-box">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
				<span style="font-size:14px;color:#6b7280;">Questions? Contact us at <a href="mailto:info@togetherclinic.co.uk">info@togetherclinic.co.uk</a></span>
			</div>
		</div>

		<!-- Screen: Ineligible -->
		<div id="screen-ineligible" class="screen ineligible-screen">
			<div class="ineligible-icon">&#x2715;</div>
			<h2>No suitable treatment</h2>
			<p id="ineligible-reason" style="margin-bottom:24px;">-</p>
			<div class="info-box" style="text-align:left;"><p>We recommend speaking with your GP who can discuss alternative options and support you with your weight management goals.</p></div>
			<button class="button button-primary" data-action="review-answers" style="margin-bottom:16px;">Review your answers</button>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:block;text-align:center;color:#8e88d0;font-weight:600;text-decoration:none;">Back to homepage</a>
		</div>

	</div>

	<footer class="powered-by-footer">
		<span class="powered-by-label">POWERED BY:</span>
		<img src="<?php echo esc_url( $logo_img ); ?>" alt="Together Clinic" class="powered-by-img" />
	</footer>
</div>
