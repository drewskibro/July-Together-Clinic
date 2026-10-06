<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Rule S1A answers (ages 75 to 85), shared by the clinician review email and
 * the order screen. Shown only when the patient was asked.
 *
 * @var array $payload
 */
$tc_s1a_rows = TC_Eligibility_Rules::s1a_summary( (array) $payload );
if ( ! $tc_s1a_rows ) {
	return;
}
?>
<h3 style="margin-top: 24px;"><?php echo esc_html( TC_Eligibility_Rules::S1A_LABEL ); ?></h3>
<p style="background:#fef3c7;padding:12px;border-left:4px solid #f59e0b;font-size:13px;margin:8px 0;">
	Triage support only. These answers did not pass or fail the patient (except a reported eGFR below 30, exclusion E11). The prescriber decides, including any face-to-face assessment.
</p>
<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; font-size: 13px; border-collapse: collapse;">
	<?php foreach ( $tc_s1a_rows as $tc_s1a_label => $tc_s1a_value ) : ?>
		<tr><th align="left"><?php echo esc_html( $tc_s1a_label ); ?></th><td><?php echo esc_html( $tc_s1a_value ); ?></td></tr>
	<?php endforeach; ?>
</table>
