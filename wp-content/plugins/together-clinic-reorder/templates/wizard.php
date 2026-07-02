<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wegovy_img   = TC_REORDER_URL . 'assets/img/wegovy.jpg';
$mounjaro_img = TC_REORDER_URL . 'assets/img/mounjaro.png';
$logo_img     = TC_REORDER_URL . 'assets/img/together-clinic-logo.png';

$med_image = $prefill['previous_medication'] === 'mounjaro' ? $mounjaro_img : $wegovy_img;
$med_name  = $prefill['previous_medication'] === 'mounjaro' ? 'Mounjaro' : 'Wegovy';
?>
<div class="tc-reorder" id="tc-reorder-root">
	<div class="tc-reorder-container">

		<!-- Screen 1: Terms -->
		<div class="screen active" data-screen="1">
			<div class="progress-wrap">
				<span class="progress-label">7%</span>
				<div class="progress-track"><div class="progress-fill" style="width:7%"></div></div>
			</div>
			<p class="heading-accent">Your next delivery is just 2 minutes away</p>
			<h1>Quick Reorder for Existing Customers</h1>
			<p class="body-lead">Let's quickly confirm a few things to continue your treatment.</p>
			<p class="hint" style="margin-bottom: 16px;"><strong>Please tick all 5 boxes below to continue.</strong></p>

			<div class="card">
				<div class="check-list">
					<label class="check-item"><input type="checkbox" class="agreement-checkbox" data-index="0" /><span class="check-text">You are reordering for yourself</span></label>
					<label class="check-item"><input type="checkbox" class="agreement-checkbox" data-index="1" /><span class="check-text">Your health information hasn't significantly changed since your last order</span></label>
					<label class="check-item"><input type="checkbox" class="agreement-checkbox" data-index="2" /><span class="check-text">You will report any new medical conditions or medications</span></label>
					<label class="check-item"><input type="checkbox" class="agreement-checkbox" data-index="3" /><span class="check-text">You agree to our Terms &amp; Conditions and Privacy Policy</span></label>
					<label class="check-item"><input type="checkbox" class="agreement-checkbox" data-index="4" /><span class="check-text">You understand this is for continuing treatment only (not new patients)</span></label>
				</div>
			</div>

			<button id="btn-screen-1" disabled class="btn-start" data-action="agree-continue">Continue to my assessment &rarr;</button>
			<p id="agreement-counter" style="text-align:center;font-size:13px;color:#6b7280;margin-top:12px;margin-bottom:0;">0 of 5 confirmed</p>
		</div>

		<!-- Screen 2: Confirm details -->
		<div class="screen" data-screen="2">
			<div class="progress-wrap">
				<span class="progress-label">14%</span>
				<div class="progress-track"><div class="progress-fill" style="width:14%"></div></div>
			</div>
			<h2>Confirm your details</h2>
			<p class="body-lead">We've pulled these from your account &mdash; please confirm they're correct.</p>

			<div class="form-group">
				<label class="form-label">First name</label>
				<input type="text" class="form-input" id="reorder-first-name" value="<?php echo esc_attr( $prefill['first_name'] ); ?>" readonly />
			</div>
			<div class="form-group">
				<label class="form-label">Last name</label>
				<input type="text" class="form-input" id="reorder-last-name" value="<?php echo esc_attr( $prefill['last_name'] ); ?>" readonly />
			</div>
			<div class="form-group">
				<label class="form-label">Email</label>
				<input type="email" class="form-input" id="reorder-email" value="<?php echo esc_attr( $prefill['email'] ); ?>" readonly />
			</div>

			<div class="form-group">
				<label class="form-label">Date of birth *</label>
				<div class="dob-inputs">
					<input type="text" id="dob-day" class="form-input dob-input" inputmode="numeric" placeholder="DD" maxlength="2" aria-label="Day" />
					<input type="text" id="dob-month" class="form-input dob-input" inputmode="numeric" placeholder="MM" maxlength="2" aria-label="Month" />
					<input type="text" id="dob-year" class="form-input dob-input" inputmode="numeric" placeholder="YYYY" maxlength="4" aria-label="Year" />
				</div>
				<p class="hint">For example, 17 03 1985</p>
				<div id="dob-error" class="dob-error hidden"></div>
			</div>

			<div class="btn-group">
				<button data-action="previous" class="btn btn-secondary">Back</button>
				<button id="btn-screen-2" data-action="save-dob" class="btn btn-primary btn-flex">Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 3: Confirm treatment -->
		<div class="screen" data-screen="3">
			<div class="progress-wrap">
				<span class="progress-label">21%</span>
				<div class="progress-track"><div class="progress-fill" style="width:21%"></div></div>
			</div>
			<h2>Your treatment</h2>
			<p class="body-lead">We've pulled your medication from your last order.</p>

			<div class="med-info-card">
				<div class="med-info-inner">
					<div class="med-thumb"><img src="<?php echo esc_url( $med_image ); ?>" alt="<?php echo esc_attr( $med_name ); ?>" /></div>
					<div class="med-info-body">
						<div class="med-badge">Reordering</div>
						<p class="med-name"><?php echo esc_html( $med_name ); ?></p>
						<p style="margin: 8px 0 0; color: #6b7280; font-size: 14px;">You'll pick your dose at the end of the assessment.</p>
					</div>
				</div>
			</div>

			<div class="btn-group">
				<button data-action="previous" class="btn btn-secondary">Back</button>
				<button data-action="confirm-treatment" class="btn btn-primary btn-flex">Continue &rarr;</button>
			</div>
		</div>

		<!-- Screen 4: Weight loss -->
		<div class="screen" data-screen="4">
			<div class="progress-wrap">
				<span class="progress-label">29%</span>
				<div class="progress-track"><div class="progress-fill" style="width:29%"></div></div>
			</div>
			<h2>Have you lost weight in the last 4 weeks?</h2>
			<label class="radio-option"><input type="radio" name="weight-loss" value="yes" data-action="set-weight-loss" /><span>Yes</span></label>
			<label class="radio-option"><input type="radio" name="weight-loss" value="no" data-action="set-weight-loss" /><span>No</span></label>
			<div id="weight-loss-message" class="alert alert--brand hidden"><div class="alert__row"><div class="alert__body"><p>You may benefit from a dose increase.</p></div></div></div>
			<button data-action="previous" class="btn btn-secondary mt-4">Back</button>
		</div>

		<!-- Screen 5: Current weight -->
		<div class="screen" data-screen="5">
			<div class="progress-wrap">
				<span class="progress-label">36%</span>
				<div class="progress-track"><div class="progress-fill" style="width:36%"></div></div>
			</div>
			<h2>What's your current weight?</h2>
			<input type="number" id="reorder-current-weight" placeholder="Weight in kg" step="0.1" min="40" max="250" class="form-input" />
			<div class="btn-group">
				<button data-action="previous" class="btn btn-secondary">Back</button>
				<button id="btn-screen-5" data-action="save-weight" class="btn btn-primary btn-flex" disabled>Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 6: Appetite -->
		<div class="screen" data-screen="6">
			<div class="progress-wrap">
				<span class="progress-label">43%</span>
				<div class="progress-track"><div class="progress-fill" style="width:43%"></div></div>
			</div>
			<h2>Is your appetite suppression lasting 6&ndash;7 days?</h2>
			<label class="radio-option"><input type="radio" name="appetite" value="yes" data-action="set-appetite" /><span>Yes</span></label>
			<label class="radio-option"><input type="radio" name="appetite" value="no" data-action="set-appetite" /><span>No</span></label>
			<div id="appetite-message" class="alert alert--brand hidden"><div class="alert__row"><div class="alert__body"><p>You may benefit from a dose increase.</p></div></div></div>
			<button data-action="previous" class="btn btn-secondary mt-4">Back</button>
		</div>

		<!-- Screen 7: Side effects -->
		<div class="screen" data-screen="7">
			<div class="progress-wrap">
				<span class="progress-label">50%</span>
				<div class="progress-track"><div class="progress-fill" style="width:50%"></div></div>
			</div>
			<h2>Are you experiencing any side effects?</h2>
			<label class="radio-option"><input type="radio" name="side-effects" value="yes" data-action="set-side-effects" /><span>Yes</span></label>
			<label class="radio-option"><input type="radio" name="side-effects" value="no" data-action="set-side-effects" /><span>No</span></label>
			<div id="side-effects-message" class="alert alert--warning hidden"><div class="alert__row"><div class="alert__body"><p>For help managing side effects, visit our <a href="https://togetherclinic.co.uk/mounjaro-wegovy-side-effects/" target="_blank" rel="noopener">Side Effects Guide</a>.</p></div></div></div>
			<button data-action="previous" class="btn btn-secondary mt-4">Back</button>
		</div>

		<!-- Screen 8: Health changed -->
		<div class="screen" data-screen="8">
			<div class="progress-wrap">
				<span class="progress-label">57%</span>
				<div class="progress-track"><div class="progress-fill" style="width:57%"></div></div>
			</div>
			<h2>Have you been diagnosed with a new health condition or experiencing any worsening health?</h2>
			<label class="radio-option"><input type="radio" name="health-changed" value="yes" data-action="set-health-changed" /><span>Yes</span></label>
			<label class="radio-option"><input type="radio" name="health-changed" value="no" data-action="set-health-changed" /><span>No</span></label>
			<button data-action="previous" class="btn btn-secondary mt-4">Back</button>
		</div>

		<!-- Screen 9: New medications -->
		<div class="screen" data-screen="9">
			<div class="progress-wrap">
				<span class="progress-label">64%</span>
				<div class="progress-track"><div class="progress-fill" style="width:64%"></div></div>
			</div>
			<h2>Are you taking any new medications?</h2>
			<label class="radio-option"><input type="radio" name="new-meds" value="yes" data-action="set-new-meds" /><span>Yes</span></label>
			<label class="radio-option"><input type="radio" name="new-meds" value="no" data-action="set-new-meds" /><span>No</span></label>
			<div id="new-medications-input" class="hidden form-group" style="margin-top:16px;">
				<label class="form-label">Please list your new medications</label>
				<input type="text" id="reorder-new-meds-list" placeholder="Enter medication names separated by commas" class="form-input" />
			</div>
			<div class="btn-group">
				<button data-action="previous" class="btn btn-secondary">Back</button>
				<button id="btn-screen-9" data-action="proceed-new-meds" class="btn btn-primary btn-flex" disabled>Next &rarr;</button>
			</div>
		</div>

		<!-- Screen 10: Pregnancy -->
		<div class="screen" data-screen="10">
			<div class="progress-wrap">
				<span class="progress-label">71%</span>
				<div class="progress-track"><div class="progress-fill" style="width:71%"></div></div>
			</div>
			<h2>Could you be pregnant?</h2>
			<label class="radio-option"><input type="radio" name="pregnancy" value="yes" data-action="set-pregnancy" /><span>Yes</span></label>
			<label class="radio-option"><input type="radio" name="pregnancy" value="no" data-action="set-pregnancy" /><span>No</span></label>
			<label class="radio-option"><input type="radio" name="pregnancy" value="na" data-action="set-pregnancy" /><span>Not applicable</span></label>
			<button data-action="previous" class="btn btn-secondary mt-4">Back</button>
		</div>

		<!-- Screen 11: Clinical support -->
		<div class="screen" data-screen="11">
			<div class="progress-wrap">
				<span class="progress-label">86%</span>
				<div class="progress-track"><div class="progress-fill" style="width:86%"></div></div>
			</div>
			<h2>Would you like to speak with a clinician about your dose?</h2>
			<label class="radio-option"><input type="radio" name="clinical-support" value="yes" data-action="set-clinical-support" /><span>Yes</span></label>
			<label class="radio-option"><input type="radio" name="clinical-support" value="no" data-action="set-clinical-support" /><span>No</span></label>
			<button data-action="previous" class="btn btn-secondary mt-4">Back</button>
		</div>

		<!-- Screen 12: Final (dose selection or clinical review) -->
		<div class="screen" data-screen="12">
			<div class="progress-wrap">
				<span class="progress-label">100%</span>
				<div class="progress-track"><div class="progress-fill" style="width:100%"></div></div>
			</div>
			<div id="final-screen-content"></div>
		</div>

		<!-- Screen 13: Consultation required dead-end -->
		<div class="screen" data-screen="13">
			<div class="progress-wrap">
				<span class="progress-label">100%</span>
				<div class="progress-track"><div class="progress-fill" style="width:100%"></div></div>
			</div>
			<div class="consult-card">
				<div class="icon-hero icon-hero--error">!</div>
				<h2>Consultation Required</h2>
				<p>Based on your response, we need to discuss your health changes before proceeding with your reorder. Please book a consultation with one of our clinicians.</p>
				<a href="#" id="reorder-calendly-link" target="_blank" rel="noopener" class="btn-danger">Book Consultation</a>
				<small>This consultation is required to ensure your safety and treatment effectiveness</small>
			</div>
			<button data-action="back-to-health-changed" class="btn btn-secondary" style="width:100%;">Back</button>
		</div>

		<!-- Screen 14: Pregnancy block dead-end -->
		<div class="screen" data-screen="14">
			<div class="progress-wrap">
				<span class="progress-label">100%</span>
				<div class="progress-track"><div class="progress-fill" style="width:100%"></div></div>
			</div>
			<div class="pregnancy-card">
				<div class="icon-hero icon-hero--warn">!</div>
				<h2>Your safety is our priority</h2>
				<p>For the safety of you and your baby, weight loss medications like Wegovy and Mounjaro cannot be prescribed during pregnancy or when trying to conceive.</p>
				<p class="danger">These medications need to be stopped immediately if you're pregnant. If you're currently taking weight loss medication, please contact your GP or midwife as soon as possible.</p>
				<p>We'd love to support your weight management journey after pregnancy. Many of our customers successfully resume treatment after giving birth and finishing breastfeeding.</p>
				<p class="muted">If you selected 'Yes' by mistake, please go back and update your answer.</p>
			</div>
			<button data-action="back-to-pregnancy" class="btn btn-secondary" style="width:100%;">Back to question</button>
		</div>

	</div>

	<footer class="powered-by-footer">
		<span class="powered-by-label">POWERED BY:</span>
		<img src="<?php echo esc_url( $logo_img ); ?>" alt="Together Clinic" class="powered-by-img" />
	</footer>
</div>
