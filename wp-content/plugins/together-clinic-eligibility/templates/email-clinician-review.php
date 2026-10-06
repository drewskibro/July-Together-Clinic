<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * @var array  $payload
 * @var array  $eligibility  [ 'eligible' => bool, 'reason' => string ]
 * @var string $assessment_id
 */

$is_eligible = ! empty( $eligibility['eligible'] );
$bmi         = (float) ( $payload['bmi'] ?? 0 );
$bmi_warn    = $bmi > 0 && ( $bmi < 27 || $bmi > 50 );
$tc_flags    = (array) ( $payload['clinicalFlags'] ?? ( $eligibility['flags'] ?? [] ) );
?>

<div style="background: <?php echo $is_eligible ? '#dcfce7' : '#fee2e2'; ?>; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">
	<strong><?php echo $is_eligible ? 'ELIGIBLE — clinician review required' : 'INELIGIBLE — patient was screened out'; ?></strong>
	<?php if ( ! $is_eligible ) : ?>
		<div style="margin-top: 4px; font-size: 13px;">Reason: <?php echo esc_html( $eligibility['reason'] ); ?></div>
	<?php endif; ?>
</div>

<?php if ( $is_eligible ) : ?>
	<p style="background:#eef2ff;padding:12px;border-left:4px solid #6366f1;font-size:13px;">
		<strong>Triage only.</strong> The questionnaire does not decide treatment. Before prescribing: photo ID check, video consultation, NHS Summary Care Record check, and independently verified weight and height (IP-SOP-11 / IP-FRM-01 3A, rules <?php echo esc_html( $payload['rulesVersion'] ?? '' ); ?>).
	</p>
<?php endif; ?>

<?php if ( $tc_flags ) : ?>
	<h3>Prescriber flags</h3>
	<ul style="font-size:13px;">
		<?php foreach ( $tc_flags as $tc_flag ) : ?>
			<li><?php echo esc_html( is_array( $tc_flag ) ? wp_json_encode( $tc_flag ) : (string) $tc_flag ); ?></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

<h3>Patient</h3>
<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; font-size: 13px; border-collapse: collapse;">
	<tr><th align="left">Name</th><td><?php echo esc_html( ( $payload['firstName'] ?? '' ) . ' ' . ( $payload['lastName'] ?? '' ) ); ?></td></tr>
	<tr><th align="left">Email</th><td><?php echo esc_html( $payload['email'] ?? '' ); ?></td></tr>
	<tr><th align="left">Phone</th><td><?php echo esc_html( $payload['phone'] ?? '' ); ?></td></tr>
	<tr><th align="left">DOB</th><td><?php echo esc_html( $payload['dob'] ?? '' ); ?></td></tr>
	<tr><th align="left">Address</th><td><?php
		echo esc_html( trim( implode( ', ', array_filter( [
			$payload['addressLine1'] ?? '',
			$payload['addressLine2'] ?? '',
			$payload['city'] ?? '',
			$payload['postcode'] ?? '',
			$payload['country'] ?? '',
		] ) ) ) );
	?></td></tr>
	<tr><th align="left">GP</th><td><?php echo esc_html( ( $payload['gpName'] ?? '' ) . ' ' . ( $payload['gpPostcode'] ?? '' ) ); ?></td></tr>
</table>

<h3 style="margin-top: 24px;">Patient type</h3>
<p><?php echo ( ( $payload['userType'] ?? '' ) === 'switching' ) ? 'Switching provider' : 'New to treatment'; ?></p>

<?php if ( ( $payload['userType'] ?? '' ) === 'switching' ) : ?>
	<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; font-size: 13px; border-collapse: collapse;">
		<tr><th align="left">Previous provider</th><td><?php echo esc_html( ucwords( str_replace( '-', ' ', $payload['provider'] ?? '' ) ) ); ?></td></tr>
		<tr><th align="left">Previous medication</th><td><?php echo esc_html( ucfirst( $payload['currentMedication'] ?? '' ) ); ?></td></tr>
		<tr><th align="left">Previous dose</th><td><strong><?php echo esc_html( $payload['currentDose'] ?? '' ); ?></strong></td></tr>
		<tr><th align="left">Date of last dose</th><td><?php echo esc_html( $payload['lastDoseDate'] ?? 'Not given' ); ?></td></tr>
		<tr><th align="left">Weight when first started</th><td><?php echo esc_html( ! empty( $payload['startWeightKg'] ) ? number_format( (float) $payload['startWeightKg'], 1 ) . ' kg (BMI ' . number_format( (float) ( $payload['startBmi'] ?? 0 ), 1 ) . ', declared; proof required)' : 'Not given' ); ?></td></tr>
	</table>
<?php endif; ?>

<h3 style="margin-top: 24px;">Selected treatment</h3>
<p style="font-size: 16px;"><strong><?php echo esc_html( ucfirst( $payload['selectedTreatment'] ?? '' ) . ' ' . ( $payload['selectedDose'] ?? '' ) ); ?></strong></p>

<h3 style="margin-top: 24px;">Health metrics</h3>
<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; font-size: 13px; border-collapse: collapse;">
	<tr><th align="left">Age band</th><td><?php echo esc_html( $payload['ageBand'] ?? '' ); ?></td></tr>
	<tr><th align="left">Ethnicity</th><td><?php echo esc_html( $payload['ethnicity'] ?? '' ); ?></td></tr>
	<tr><th align="left">Sex at birth</th><td><?php echo esc_html( $payload['sex'] ?? '' ); ?></td></tr>
	<tr><th align="left">Weight</th><td><?php echo esc_html( number_format( (float) ( $payload['weightKg'] ?? 0 ), 1 ) ); ?> kg</td></tr>
	<tr><th align="left">Height</th><td><?php echo esc_html( number_format( (float) ( $payload['heightCm'] ?? 0 ), 1 ) ); ?> cm</td></tr>
	<tr style="<?php echo $bmi_warn ? 'background:#fee2e2;' : ''; ?>"><th align="left">BMI</th><td><strong><?php echo esc_html( number_format( $bmi, 1 ) ); ?></strong><?php echo $bmi_warn ? ' <em>(outside typical range)</em>' : ''; ?></td></tr>
	<tr><th align="left">Diabetes</th><td><?php echo esc_html( $payload['diabetes'] ?? 'Not specified' ); ?></td></tr>
	<tr><th align="left">Goal weight</th><td><?php echo esc_html( $payload['goalWeight'] ?? 'Not provided' ); ?></td></tr>
</table>

<?php if ( ( $payload['sex'] ?? '' ) === 'female' ) : ?>
	<h3 style="margin-top: 24px;">Pregnancy screening</h3>
	<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; font-size: 13px; border-collapse: collapse;">
		<tr><th align="left">Pregnant</th><td><?php echo esc_html( $payload['pregnant'] ?? 'Not asked' ); ?></td></tr>
		<tr><th align="left">Breastfeeding</th><td><?php echo esc_html( $payload['breastfeeding'] ?? 'Not asked' ); ?></td></tr>
		<tr><th align="left">Trying to conceive</th><td><?php echo esc_html( $payload['conceive'] ?? 'Not asked' ); ?></td></tr>
		<tr><th align="left">Could become pregnant</th><td><?php echo esc_html( $payload['couldConceive'] ?? 'Not asked' ); ?></td></tr>
		<tr><th align="left">Contraception</th><td><?php echo esc_html( $payload['contraception'] ?? 'Not asked' ); ?></td></tr>
		<tr><th align="left">Agreed to use contraception</th><td><?php echo ! empty( $payload['consentContraception'] ) ? 'Yes' : 'No / not asked'; ?></td></tr>
	</table>
<?php endif; ?>

<?php if ( ! empty( $payload['conditions'] ) ) : ?>
	<h3 style="margin-top: 24px;">Serious medical conditions</h3>
	<ul><?php foreach ( $payload['conditions'] as $c ) : ?><li><?php echo esc_html( $c ); ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<?php if ( ! empty( $payload['bariatricDetails'] ) ) : ?>
	<h3 style="margin-top: 24px;">Bariatric details</h3>
	<p style="background:#fef3c7;padding:12px;border-left:4px solid #f59e0b;"><?php echo nl2br( esc_html( $payload['bariatricDetails'] ) ); ?></p>
<?php endif; ?>

<?php if ( ! empty( $payload['weightConditions'] ) ) : ?>
	<h3 style="margin-top: 24px;">Weight-related conditions</h3>
	<ul><?php foreach ( $payload['weightConditions'] as $c ) : ?><li><?php echo esc_html( $c ); ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<?php if ( ! empty( $payload['mentalHealthDetails'] ) ) : ?>
	<h3 style="margin-top: 24px;">Mental health details</h3>
	<p style="background:#dbeafe;padding:12px;border-left:4px solid #3b82f6;"><?php echo nl2br( esc_html( $payload['mentalHealthDetails'] ) ); ?></p>
<?php endif; ?>

<?php if ( ! empty( $payload['otherConditionsList'] ) || ! empty( $payload['otherConditions'] ) ) : ?>
	<h3 style="margin-top: 24px;">Other medical conditions</h3>
	<p><?php echo nl2br( esc_html( $payload['otherConditionsList'] ?? '' ) ); ?></p>
<?php endif; ?>

<h3 style="margin-top: 24px;">Medications &amp; allergies</h3>
<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; font-size: 13px; border-collapse: collapse;">
	<tr><th align="left">Previous weight-loss medications</th><td><?php echo esc_html( implode( ', ', (array) ( $payload['prevMeds'] ?? [] ) ) ); ?></td></tr>
	<tr><th align="left">Current medications</th><td><?php echo nl2br( esc_html( $payload['currentMedsList'] ?? ( $payload['currentMeds'] ?? 'None' ) ) ); ?></td></tr>
	<tr><th align="left">Allergies</th><td><?php echo nl2br( esc_html( $payload['allergiesList'] ?? ( $payload['allergies'] ?? 'None reported' ) ) ); ?></td></tr>
</table>

<h3 style="margin-top: 24px;">Consents</h3>
<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; font-size: 13px; border-collapse: collapse;">
	<tr><th align="left">Photo ID and video consultation with weight and height check</th><td><?php echo ! empty( $payload['consentIdVideo'] ) ? 'Yes' : 'No'; ?></td></tr>
	<tr><th align="left">Summary Care Record check</th><td><?php echo ! empty( $payload['gpConsentSCR'] ) ? 'Yes' : 'No'; ?></td></tr>
	<tr style="<?php echo empty( $payload['gpConsentShare'] ) ? 'background:#fee2e2;' : ''; ?>"><th align="left">Share with GP</th><td><?php echo ! empty( $payload['gpConsentShare'] ) ? 'Yes' : 'NO (red flag)'; ?></td></tr>
	<tr><th align="left">Diet and activity commitment</th><td><?php echo ! empty( $payload['consentLifestyle'] ) ? 'Yes' : 'No'; ?></td></tr>
</table>

<?php if ( ! empty( $order_id ) ) : ?>
	<?php $tc_review_order = wc_get_order( $order_id ); ?>
	<?php if ( $tc_review_order ) : ?>
		<p style="margin-top: 24px; background:#fef3c7; padding:12px; border-left:4px solid #f59e0b;">
			<strong>Awaiting your review:</strong> order #<?php echo esc_html( $tc_review_order->get_order_number() ); ?> has been created and is held pending prescriber sign-off. No payment has been captured.
			<br><a href="<?php echo esc_url( $tc_review_order->get_edit_order_url() ); ?>">Review this order &rarr;</a>
		</p>
	<?php endif; ?>
<?php endif; ?>

<p style="margin-top: 24px;">
	<a href="<?php echo esc_url( TC_Review_Status::queue_url() ); ?>">View all orders awaiting review &rarr;</a>
</p>

<p style="color: #6b7280; font-size: 11px; margin-top: 24px;">Assessment ID: <?php echo esc_html( $assessment_id ); ?></p>
