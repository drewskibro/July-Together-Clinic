<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * @var array  $payload
 * @var string $assessment_id
 * @var WC_Order $order
 */
?>
<h3>Eligibility Assessment</h3>

<?php if ( ! empty( $payload['userType'] ) ) :
	$is_switching = $payload['userType'] === 'switching';
	?>
	<p>
		<strong>Patient Type:</strong>
		<span style="background:<?php echo $is_switching ? '#dbeafe' : '#dcfce7'; ?>;padding:4px 8px;border-radius:4px;font-weight:600;">
			<?php echo $is_switching ? 'Switching Provider' : 'New to Treatment'; ?>
		</span>
	</p>
<?php endif; ?>

<?php if ( ! empty( $payload['userType'] ) && $payload['userType'] === 'switching' ) : ?>
	<div style="background:#f0f9ff;border-left:4px solid #0284c7;padding:16px;margin:16px 0;">
		<h4 style="margin:0 0 12px 0;color:#0c4a6e;">Previous Provider</h4>
		<?php if ( ! empty( $payload['provider'] ) ) : ?>
			<p><strong>Provider:</strong> <?php echo esc_html( ucwords( str_replace( '-', ' ', $payload['provider'] ) ) ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $payload['currentMedication'] ) ) : ?>
			<p><strong>Medication:</strong> <?php echo esc_html( ucfirst( $payload['currentMedication'] ) ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $payload['currentDose'] ) ) : ?>
			<p><strong>Dose:</strong> <span style="font-weight:700;color:#0c4a6e;"><?php echo esc_html( $payload['currentDose'] ); ?></span></p>
		<?php endif; ?>
	</div>
<?php endif; ?>

<h4 style="margin-top:20px;border-bottom:2px solid #e5e7eb;padding-bottom:8px;">Contact</h4>
<p><strong>Name:</strong> <?php echo esc_html( ( $payload['firstName'] ?? '' ) . ' ' . ( $payload['lastName'] ?? '' ) ); ?></p>
<p><strong>Email:</strong> <?php echo esc_html( $payload['email'] ?? '' ); ?></p>
<p><strong>Phone:</strong> <?php echo esc_html( $payload['phone'] ?? '' ); ?></p>
<p><strong>DOB:</strong> <?php echo esc_html( $payload['dob'] ?? '' ); ?></p>

<?php if ( ! empty( $payload['gpName'] ) || ! empty( $payload['gpPostcode'] ) ) : ?>
	<h4 style="margin-top:20px;border-bottom:2px solid #e5e7eb;padding-bottom:8px;">GP Surgery</h4>
	<p><strong>Surgery name:</strong> <?php echo esc_html( $payload['gpName'] ?? '' ); ?></p>
	<p><strong>Surgery postcode:</strong> <?php echo esc_html( $payload['gpPostcode'] ?? '' ); ?></p>
<?php endif; ?>

<h4 style="margin-top:20px;border-bottom:2px solid #e5e7eb;padding-bottom:8px;">Treatment</h4>
<p><strong>Selected:</strong> <?php echo esc_html( ucfirst( $payload['selectedTreatment'] ?? '' ) . ' ' . ( $payload['selectedDose'] ?? '' ) ); ?></p>

<h4 style="margin-top:20px;border-bottom:2px solid #e5e7eb;padding-bottom:8px;">Health metrics</h4>
<p><strong>Age band:</strong> <?php echo esc_html( $payload['ageBand'] ?? '' ); ?></p>
<p><strong>Ethnicity:</strong> <?php echo esc_html( $payload['ethnicity'] ?? '' ); ?></p>
<p><strong>Sex at birth:</strong> <?php echo esc_html( $payload['sex'] ?? '' ); ?></p>
<p><strong>Weight:</strong> <?php echo esc_html( number_format( (float) ( $payload['weightKg'] ?? 0 ), 1 ) ); ?> kg</p>
<p><strong>Height:</strong> <?php echo esc_html( number_format( (float) ( $payload['heightCm'] ?? 0 ), 1 ) ); ?> cm</p>
<p><strong>BMI:</strong> <?php echo esc_html( number_format( (float) ( $payload['bmi'] ?? 0 ), 1 ) ); ?></p>

<h4 style="margin-top:20px;border-bottom:2px solid #e5e7eb;padding-bottom:8px;">Medical history</h4>
<p><strong>Diabetes:</strong> <?php echo esc_html( $payload['diabetes'] ?? 'Not specified' ); ?></p>

<?php if ( ! empty( $payload['conditions'] ) && is_array( $payload['conditions'] ) ) : ?>
	<p><strong>Serious conditions:</strong></p>
	<ul style="margin:8px 0;padding-left:20px;"><?php foreach ( $payload['conditions'] as $c ) : ?><li><?php echo esc_html( $c ); ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<?php if ( ! empty( $payload['bariatricDetails'] ) ) : ?>
	<div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:12px;margin:8px 0;">
		<strong>Bariatric surgery details:</strong><br>
		<em><?php echo nl2br( esc_html( $payload['bariatricDetails'] ) ); ?></em>
	</div>
<?php endif; ?>

<?php if ( ! empty( $payload['weightConditions'] ) && is_array( $payload['weightConditions'] ) ) : ?>
	<p><strong>Weight-related conditions:</strong></p>
	<ul style="margin:8px 0;padding-left:20px;"><?php foreach ( $payload['weightConditions'] as $c ) : ?><li><?php echo esc_html( $c ); ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<?php if ( ! empty( $payload['mentalHealthDetails'] ) ) : ?>
	<div style="background:#dbeafe;border-left:4px solid #3b82f6;padding:12px;margin:8px 0;">
		<strong>Mental health:</strong><br>
		<em><?php echo nl2br( esc_html( $payload['mentalHealthDetails'] ) ); ?></em>
	</div>
<?php endif; ?>

<?php if ( ! empty( $payload['otherConditionsList'] ) ) : ?>
	<div style="background:#fef2f2;border-left:4px solid #ef4444;padding:12px;margin:8px 0;">
		<strong>Other conditions:</strong><br>
		<em><?php echo nl2br( esc_html( $payload['otherConditionsList'] ) ); ?></em>
	</div>
<?php endif; ?>

<h4 style="margin-top:20px;border-bottom:2px solid #e5e7eb;padding-bottom:8px;">Medications &amp; allergies</h4>

<?php if ( ! empty( $payload['prevMeds'] ) && is_array( $payload['prevMeds'] ) ) : ?>
	<p><strong>Previous weight-loss medications:</strong></p>
	<ul style="margin:8px 0;padding-left:20px;"><?php foreach ( $payload['prevMeds'] as $m ) : ?><li><?php echo esc_html( $m ); ?></li><?php endforeach; ?></ul>
<?php endif; ?>

<?php if ( ! empty( $payload['currentMedsList'] ) ) : ?>
	<div style="background:#f0fdf4;border-left:4px solid #10b981;padding:12px;margin:8px 0;">
		<strong>Current medications:</strong><br>
		<em><?php echo nl2br( esc_html( $payload['currentMedsList'] ) ); ?></em>
	</div>
<?php else : ?>
	<p><strong>Current medications:</strong> None</p>
<?php endif; ?>

<?php if ( ! empty( $payload['allergiesList'] ) ) : ?>
	<div style="background:#fef2f2;border-left:4px solid #ef4444;padding:12px;margin:8px 0;">
		<strong>Allergies:</strong><br>
		<em><?php echo nl2br( esc_html( $payload['allergiesList'] ) ); ?></em>
	</div>
<?php else : ?>
	<p><strong>Allergies:</strong> None reported</p>
<?php endif; ?>

<?php if ( $assessment_id ) : ?>
	<p style="color:#6b7280;font-size:11px;margin-top:24px;">Assessment ID: <code><?php echo esc_html( $assessment_id ); ?></code></p>
<?php endif; ?>
