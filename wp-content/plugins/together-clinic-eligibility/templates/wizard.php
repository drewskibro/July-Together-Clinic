<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wegovy_img   = TC_ELIGIBILITY_URL . 'assets/img/wegovy.jpg';
$mounjaro_img = TC_ELIGIBILITY_URL . 'assets/img/mounjaro.png';
$logo_img     = TC_ELIGIBILITY_URL . 'assets/img/together-clinic-logo.png';
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

			<div class="testimonial-box">
				<div class="testimonial-header">
					<div>
						<div class="testimonial-name">Lesley Slade</div>
						<div class="stars">
							<span class="star">&#9733;</span>
							<span class="star">&#9733;</span>
							<span class="star">&#9733;</span>
							<span class="star">&#9733;</span>
							<span class="star">&#9733;</span>
						</div>
					</div>
				</div>
				<p class="testimonial-text">&ldquo;This clinic is excellent. I needed help losing weight and the support and advice I've been given has been amazing. I would definitely recommend Together Clinic to anyone wanting to lose weight or wanting general health advice.&rdquo;</p>
			</div>
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
				<strong class="pathway-title">I'm new to treatment</strong>
				<span class="pathway-subtitle">First time using Wegovy or Mounjaro</span>
			</button>
			<button class="pathway-card" data-action="set-user-type" data-value="switching">
				<div class="pathway-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9h16"/><path d="M8 5l-4 4 4 4"/><path d="M20 15H4"/><path d="M16 19l4-4-4-4"/></svg></div>
				<strong class="pathway-title">Switching providers</strong>
				<span class="pathway-subtitle">Currently using medication elsewhere</span>
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
			<button class="pathway-card" data-action="set-current-medication" data-value="wegovy">
				<div class="pathway-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.5 20.5 10.5 3.5"/><path d="M14.5 20.5 14.5 3.5"/><rect x="7" y="3" width="10" height="4" rx="1"/><rect x="7" y="17" width="10" height="4" rx="1"/></svg></div>
				<strong class="pathway-title">Wegovy</strong><span class="pathway-subtitle">Semaglutide injection</span>
			</button>
			<button class="pathway-card" data-action="set-current-medication" data-value="mounjaro">
				<div class="pathway-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.5 20.5 10.5 3.5"/><path d="M14.5 20.5 14.5 3.5"/><rect x="7" y="3" width="10" height="4" rx="1"/><rect x="7" y="17" width="10" height="4" rx="1"/></svg></div>
				<strong class="pathway-title">Mounjaro</strong><span class="pathway-subtitle">Tirzepatide injection</span>
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

		<!-- Screen 4: Age -->
		<div id="screen-4" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">25%</div><div class="progress-bar"><div class="progress-fill" style="width: 25%"></div></div></div></div>
			<h2>How old are you?</h2>
			<div class="radio-group">
				<label class="radio-item"><input type="radio" name="age" value="under-18" data-action="set-age" /><span>Under 18</span></label>
				<label class="radio-item"><input type="radio" name="age" value="18-74" data-action="set-age" /><span>18 to 74</span></label>
				<label class="radio-item"><input type="radio" name="age" value="75-over" data-action="set-age" /><span>75 or over</span></label>
			</div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 5: Ethnicity -->
		<div id="screen-5" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">30%</div><div class="progress-bar"><div class="progress-fill" style="width: 30%"></div></div></div></div>
			<h2>Which ethnicity are you?</h2>
			<p>Healthy BMI ranges differ according to ethnic background. Our clinicians evaluate your BMI and complete medical history together.</p>
			<div class="radio-group">
				<?php
				$ethnicities = [
					'asian or asian british'    => 'Asian or Asian British',
					'black (caribbean, african)' => 'Black (Caribbean, African)',
					'mixed ethnicities'         => 'Mixed ethnicities',
					'other ethnic group'        => 'Other ethnic group',
					'white'                     => 'White',
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
				<p class="bmi-next-text" id="bmi-message">Our clinical team will review your full profile.</p>
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
				<label class="radio-item"><input type="radio" name="diabetes" value="medication" data-action="set-diabetes" /><span>I have diabetes and take medication for it</span></label>
				<label class="radio-item"><input type="radio" name="diabetes" value="diet" data-action="set-diabetes" /><span>I have diabetes and it's diet-controlled</span></label>
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
			<h2>Are you currently taking any regular prescription medications?</h2>
			<div class="radio-group">
				<label class="radio-item"><input type="radio" name="current-meds" value="none" data-action="set-current-meds" /><span>No, I don't take any prescription medications</span></label>
				<label class="radio-item"><input type="radio" name="current-meds" value="bp" data-action="set-current-meds" /><span>Blood pressure medication</span></label>
				<label class="radio-item"><input type="radio" name="current-meds" value="cholesterol" data-action="set-current-meds" /><span>Cholesterol medication</span></label>
				<label class="radio-item"><input type="radio" name="current-meds" value="diabetes" data-action="set-current-meds" /><span>Diabetes medication</span></label>
				<label class="radio-item"><input type="radio" name="current-meds" value="mental" data-action="set-current-meds" /><span>Mental health medication</span></label>
				<label class="radio-item"><input type="radio" name="current-meds" value="other" data-action="set-current-meds-other" /><span>Other / I take more than one prescription medication</span></label>
			</div>
			<button class="button button-secondary" data-action="previous">Back</button>
		</div>

		<!-- Screen 14a: Medication List -->
		<div id="screen-14a" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">77%</div><div class="progress-bar"><div class="progress-fill" style="width: 77%"></div></div></div></div>
			<h2>Please include a full list of all medication that you currently take</h2>
			<textarea class="form-textarea" id="medication-list" placeholder="List all your current medications..."></textarea>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="goto" data-value="15">Next &rarr;</button>
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

		<!-- Screen 15b: Pregnancy Gate -->
		<div id="screen-15b" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">80%</div><div class="progress-bar"><div class="progress-fill" style="width: 80%"></div></div></div></div>
			<h2>Are you currently pregnant, planning to become pregnant, or breastfeeding?</h2>
			<p>Weight loss medications are not suitable during pregnancy or breastfeeding.</p>
			<div class="two-col-buttons">
				<button class="button button-secondary" data-action="set-pregnancy-gate" data-value="yes">Yes</button>
				<button class="button button-secondary" data-action="set-pregnancy-gate" data-value="no">No</button>
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

		<!-- Screen 20: GP -->
		<div id="screen-20" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">95%</div><div class="progress-bar"><div class="progress-fill" style="width: 95%"></div></div></div></div>
			<h2>Who is your GP?</h2>
			<div class="form-group"><label class="form-label">GP Surgery Name</label><input type="text" id="gp-name" class="form-input" placeholder="Surgery name" /></div>
			<div class="form-group"><label class="form-label">GP Surgery Postcode</label><input type="text" id="gp-postcode" class="form-input" placeholder="SW1A 1AA" /></div>
			<label class="checkbox-item" style="background:white;border:2px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:12px;">
				<input type="checkbox" id="gp-consent-1" /><span>I consent for Together Clinic to share information regarding any treatment prescribed with my GP</span>
			</label>
			<label class="checkbox-item" style="background:white;border:2px solid #e5e7eb;border-radius:12px;padding:16px;margin-bottom:24px;">
				<input type="checkbox" id="gp-consent-2" /><span>I consent to a one-off request from Together Clinic to access my summary care record to verify the information I have provided</span>
			</label>
			<div class="button-group">
				<button class="button button-secondary" data-action="previous">Back</button>
				<button class="button button-primary" data-action="next">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 21: Treatment Selection -->
		<div id="screen-21" class="screen">
			<div class="progress-section"><div class="progress-bar-container"><div class="progress-percentage">100%</div><div class="progress-bar"><div class="progress-fill" style="width: 100%"></div></div></div></div>
			<div style="text-align:center;margin-bottom:40px;">
				<div style="width:80px;height:80px;border-radius:50%;background:#dcfce7;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:40px;margin:0 auto 24px;">&#10003;</div>
				<h1 style="font-size:32px;margin-bottom:12px;">You're eligible for treatment!</h1>
				<p>Based on your assessment, you qualify for GLP-1 weight loss treatment.</p>
			</div>
			<h2 style="text-align:center;margin:32px 0 16px;">Choose Your Treatment</h2>
			<p style="text-align:center;margin-bottom:24px;">Select the medication that works best for you</p>
			<div class="treatment-grid">
				<button class="treatment-card" id="wegovy-card" data-action="select-treatment" data-value="wegovy">
					<div class="treatment-header"><div class="treatment-title">Wegovy</div></div>
					<div class="treatment-price">&pound;109<span style="font-size:14px;color:#6b7280;">/month</span></div>
					<p class="treatment-price-note">Starting dose (0.25mg)</p>
					<div class="treatment-image"><img src="<?php echo esc_url( $wegovy_img ); ?>" alt="Wegovy injection pen" /></div>
					<p class="treatment-description">Clinically proven semaglutide injection for significant weight loss</p>
					<ul class="treatment-benefits">
						<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Average 15% weight loss</li>
						<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Once-weekly injection</li>
						<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>MHRA approved</li>
					</ul>
				</button>
				<button class="treatment-card" id="mounjaro-card" data-action="select-treatment" data-value="mounjaro">
					<div class="treatment-header"><div class="treatment-title">Mounjaro</div></div>
					<div class="treatment-price">&pound;159<span style="font-size:14px;color:#6b7280;">/month</span></div>
					<p class="treatment-price-note">Starting dose (2.5mg)</p>
					<div class="treatment-image"><img src="<?php echo esc_url( $mounjaro_img ); ?>" alt="Mounjaro injection pen" /></div>
					<p class="treatment-description">Dual-action tirzepatide formula for maximum weight loss results</p>
					<ul class="treatment-benefits">
						<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Average 20% weight loss</li>
						<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>Once-weekly injection</li>
						<li class="treatment-benefit"><svg class="benefit-icon" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>MHRA approved</li>
					</ul>
				</button>
			</div>
			<div class="success-timeline">
				<h3 style="margin-bottom:16px;">What happens next</h3>
				<div class="timeline-item"><div class="timeline-number">1</div><div class="timeline-content"><p class="timeline-title">Clinician Review</p><p class="timeline-desc">Your assessment will be reviewed within 24 hours</p></div></div>
				<div class="timeline-item"><div class="timeline-number">2</div><div class="timeline-content"><p class="timeline-title">Prescription Issued</p><p class="timeline-desc">If approved, your prescription is issued instantly</p></div></div>
				<div class="timeline-item"><div class="timeline-number">3</div><div class="timeline-content"><p class="timeline-title">Fast Delivery</p><p class="timeline-desc">Free next-day delivery to your door</p></div></div>
			</div>
			<button class="button button-primary" id="submit-button" data-action="submit-assessment" disabled>Submit Assessment &amp; Continue to Checkout</button>
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
			<button class="button button-primary" id="continue-to-checkout-button" data-action="continue-to-checkout" style="margin-bottom:16px;">Continue to Checkout &rarr;</button>
			<div class="success-timeline">
				<h3 style="margin-bottom:16px;">What happens next</h3>
				<div class="timeline-item"><div class="timeline-number">1</div><div class="timeline-content"><p class="timeline-title">Clinician Review</p><p class="timeline-desc">A clinician will review your assessment within 24 hours</p></div></div>
				<div class="timeline-item"><div class="timeline-number">2</div><div class="timeline-content"><p class="timeline-title">Prescription Issued</p><p class="timeline-desc">If approved, your prescription will be issued</p></div></div>
				<div class="timeline-item"><div class="timeline-number">3</div><div class="timeline-content"><p class="timeline-title">Fast Delivery</p><p class="timeline-desc">Your medication will be dispatched with free next-day delivery</p></div></div>
			</div>
			<div class="confirmed-contact-box">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
				<span style="font-size:14px;color:#6b7280;">Questions? Contact us at <a href="mailto:care@togetherclinic.co.uk">care@togetherclinic.co.uk</a></span>
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
