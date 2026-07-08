<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * @var array  $payload
 * @var string $first_name
 * @var string $assessment_id
 */
?>
<p>Hi <?php echo esc_html( $first_name ); ?>,</p>

<p>Thank you &mdash; your assessment has been successfully submitted to Together Clinic.</p>

<h3 style="margin-top: 24px;">What happens next</h3>
<ol>
	<li>One of our prescribers will review your assessment within 24 hours.</li>
	<li>If your treatment is approved, we will email you a secure payment link. <strong>No payment is taken until then.</strong></li>
	<li>Once you have paid, your medication will be dispatched with free next-day delivery.</li>
</ol>

<h3 style="margin-top: 24px;">Your assessment summary</h3>

<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; font-size: 13px; border-collapse: collapse;">
	<tr><th align="left">Name</th><td><?php echo esc_html( ( $payload['firstName'] ?? '' ) . ' ' . ( $payload['lastName'] ?? '' ) ); ?></td></tr>
	<tr><th align="left">Email</th><td><?php echo esc_html( $payload['email'] ?? '' ); ?></td></tr>
	<tr><th align="left">Phone</th><td><?php echo esc_html( $payload['phone'] ?? '' ); ?></td></tr>
	<tr><th align="left">Date of birth</th><td><?php echo esc_html( $payload['dob'] ?? '' ); ?></td></tr>
	<tr><th align="left">Patient type</th><td><?php echo ( ( $payload['userType'] ?? '' ) === 'switching' ) ? 'Switching provider' : 'New to treatment'; ?></td></tr>
	<?php if ( ( $payload['userType'] ?? '' ) === 'switching' ) : ?>
		<tr><th align="left">Current treatment</th><td><?php echo esc_html( ucfirst( $payload['currentMedication'] ?? '' ) . ' ' . ( $payload['currentDose'] ?? '' ) ); ?></td></tr>
	<?php endif; ?>
	<tr><th align="left">Selected treatment</th><td><?php echo esc_html( ucfirst( $payload['selectedTreatment'] ?? '' ) . ' ' . ( $payload['selectedDose'] ?? '' ) ); ?></td></tr>
	<tr><th align="left">Weight</th><td><?php echo esc_html( number_format( (float) ( $payload['weightKg'] ?? 0 ), 1 ) ); ?> kg</td></tr>
	<tr><th align="left">Height</th><td><?php echo esc_html( number_format( (float) ( $payload['heightCm'] ?? 0 ), 1 ) ); ?> cm</td></tr>
	<tr><th align="left">BMI</th><td><?php echo esc_html( number_format( (float) ( $payload['bmi'] ?? 0 ), 1 ) ); ?></td></tr>
</table>

<h3 style="margin-top: 32px;">Your account</h3>
<p>We've set up an account for you at Together Clinic. Log in any time to view this assessment, track your order, and reorder in minutes when you need to.</p>
<p>
	<a href="<?php echo esc_url( home_url( '/my-account/' ) ); ?>" style="display: inline-block; background: #8e88d0; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 600;">Log in to your account</a>
</p>
<p style="font-size: 13px; color: #6b7280;">First time logging in? Click "Lost your password?" on the login page and we'll email you a link to set one.</p>

<p style="margin-top: 32px;">If you have any questions, reply to this email or contact us at <a href="mailto:care@togetherclinic.co.uk">care@togetherclinic.co.uk</a>.</p>

<p>Best wishes,<br>The Together Clinic team</p>

<p style="color: #6b7280; font-size: 11px; margin-top: 24px;">Reference: <?php echo esc_html( $assessment_id ); ?></p>
