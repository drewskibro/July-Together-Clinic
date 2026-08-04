<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reorder-lane emails. Reuses the eligibility plugin's sender/recipient
 * options (same option keys) so both lanes are configured in one place.
 */
class TC_Reorder_Emails {

	public static function send_clinician_notification( array $payload, $assessment_id, $prefill, $order = null ) {
		if ( get_option( 'tc_eligibility_send_clinician_emails', '1' ) !== '1' ) {
			TC_Reorder_Log::info( 'clinician_email_skipped kill_switch_off', [ 'assessment_id' => $assessment_id ] );
			return false;
		}

		$recipients = self::clinician_recipients();
		if ( empty( $recipients ) ) {
			TC_Reorder_Log::warn( 'clinician_email_skipped no_recipients', [ 'assessment_id' => $assessment_id ] );
			return false;
		}

		$patient      = trim( sanitize_text_field( ( $payload['firstName'] ?? '' ) . ' ' . ( $payload['lastName'] ?? '' ) ) );
		$medication   = sanitize_text_field( $payload['currentMedication'] ?? '' );
		$current_dose = sanitize_text_field( $prefill['previous_dose'] ?? ( $payload['currentDose'] ?? '' ) );
		$requested    = sanitize_text_field( $payload['selectedDose'] ?? '' );

		$subject = sprintf(
			'[Together Clinic] [REORDER] %s — %s %s (currently %s)',
			$patient ?: 'Returning patient',
			ucfirst( $medication ),
			$requested,
			$current_dose ?: 'unknown'
		);

		$rows = [
			'Patient'                     => $patient,
			'Email'                       => sanitize_email( $payload['email'] ?? '' ),
			'Date of birth'               => sanitize_text_field( $payload['dob'] ?? '' ),
			'Lane'                        => 'Reorder (returning patient)',
			'Medication'                  => ucfirst( $medication ),
			'Current dose (from order history)' => $current_dose ?: 'Could not be derived',
			'Requested dose'              => $requested,
			'Previous order'              => '#' . (int) ( $prefill['previous_order_id'] ?? 0 ),
			'Weight now'                  => $payload['currentWeight'] ? $payload['currentWeight'] . ' kg' : '',
			'Lost weight since starting'  => sanitize_text_field( $payload['hasLostWeight'] ?? '' ),
			'Appetite suppression lasting' => sanitize_text_field( $payload['appetiteLasting'] ?? '' ),
			'Side effects'                => sanitize_text_field( $payload['hasSideEffects'] ?? '' ),
			'Health changed'              => sanitize_text_field( $payload['healthChanged'] ?? '' ),
			'New medications'             => sanitize_text_field( $payload['newMedications'] ?? '' ),
			'New medications list'        => sanitize_textarea_field( $payload['newMedicationsList'] ?? '' ),
			'Could be pregnant'           => sanitize_text_field( $payload['couldBePregnant'] ?? '' ),
			'Wants clinical support'      => sanitize_text_field( $payload['wantsClinicalSupport'] ?? '' ),
		];

		$body = '<div style="background:#dcfce7;padding:12px 16px;border-radius:8px;margin-bottom:16px;"><strong>REORDER — prescriber review required</strong></div>';
		$body .= '<table cellspacing="0" cellpadding="6" border="1" style="width:100%;font-size:13px;border-collapse:collapse;">';
		foreach ( $rows as $label => $value ) {
			if ( $value === '' ) {
				continue;
			}
			$body .= sprintf( '<tr><th align="left">%s</th><td>%s</td></tr>', esc_html( $label ), esc_html( $value ) );
		}
		$body .= '</table>';

		if ( $order instanceof WC_Order ) {
			$body .= sprintf(
				'<p style="margin-top:24px;background:#fef3c7;padding:12px;border-left:4px solid #f59e0b;"><strong>Awaiting your review:</strong> order #%s has been created and is held pending prescriber sign-off. No payment has been taken.<br><a href="%s">Review this order &rarr;</a></p>',
				esc_html( $order->get_order_number() ),
				esc_url( $order->get_edit_order_url() )
			);
		}

		$body .= sprintf(
			'<p style="color:#6b7280;font-size:11px;margin-top:24px;">Reorder assessment ID: %s</p>',
			esc_html( $assessment_id )
		);

		$sent = wp_mail( $recipients, $subject, self::wrap( $body ), self::headers() );

		TC_Reorder_Log::info(
			'clinician_email_' . ( $sent ? 'sent' : 'failed' ),
			[ 'assessment_id' => $assessment_id, 'to' => implode( ',', $recipients ) ]
		);

		return $sent;
	}

	public static function send_patient_confirmation( array $payload, $assessment_id ) {
		$email = sanitize_email( $payload['email'] ?? '' );
		if ( ! is_email( $email ) ) {
			TC_Reorder_Log::warn( 'patient_email_skipped invalid_email', [ 'assessment_id' => $assessment_id ] );
			return false;
		}

		$first_name = sanitize_text_field( $payload['firstName'] ?? 'there' );
		$subject    = sprintf( '[Together Clinic] Your reorder has been submitted, %s', $first_name );

		$body  = sprintf( '<p>Hi %s,</p>', esc_html( $first_name ) );
		$body .= '<p>Thank you &mdash; your reorder has been submitted to Together Clinic.</p>';
		$body .= '<h3 style="margin-top:24px;">What happens next</h3><ol>';
		$body .= '<li>One of our prescribers will review your reorder within 24 hours.</li>';
		$body .= '<li>If your treatment is approved, we will email you a secure payment link. <strong>No payment is taken until then.</strong></li>';
		$body .= '<li>Once you have paid, your medication will be dispatched with free next-day delivery.</li></ol>';
		$body .= sprintf(
			'<p style="margin-top:24px;">If you have any questions, contact us at <a href="mailto:%1$s">%1$s</a>.</p>',
			esc_attr( sanitize_email( get_option( 'tc_eligibility_from_email', 'care@togetherclinic.co.uk' ) ) )
		);
		$body .= '<p>Best wishes,<br>The Together Clinic team</p>';
		$body .= sprintf( '<p style="color:#6b7280;font-size:11px;margin-top:24px;">Reference: %s</p>', esc_html( $assessment_id ) );

		$sent = wp_mail( $email, $subject, self::wrap( $body ), self::headers() );

		TC_Reorder_Log::info(
			'patient_email_' . ( $sent ? 'sent' : 'failed' ),
			[ 'assessment_id' => $assessment_id, 'to' => $email ]
		);

		return $sent;
	}

	private static function clinician_recipients() {
		$raw = get_option( 'tc_eligibility_clinician_recipients', 'ahmed@at-health.co.uk,care@togetherclinic.co.uk' );
		$arr = array_filter( array_map( 'trim', explode( ',', (string) $raw ) ), 'is_email' );
		return array_values( $arr );
	}

	private static function headers() {
		$from_email = sanitize_email( get_option( 'tc_eligibility_from_email', 'care@togetherclinic.co.uk' ) );
		$from_name  = sanitize_text_field( get_option( 'tc_eligibility_from_name', 'Together Clinic' ) );

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		if ( $from_email ) {
			$headers[] = sprintf( 'From: %s <%s>', $from_name ?: 'Together Clinic', $from_email );
			$headers[] = sprintf( 'Reply-To: %s', $from_email );
		}
		return $headers;
	}

	private static function wrap( $content ) {
		if ( function_exists( 'WC' ) && WC()->mailer() ) {
			$mailer = WC()->mailer();
			if ( method_exists( $mailer, 'wrap_message' ) ) {
				return $mailer->wrap_message( '', $content );
			}
		}
		return $content;
	}
}
