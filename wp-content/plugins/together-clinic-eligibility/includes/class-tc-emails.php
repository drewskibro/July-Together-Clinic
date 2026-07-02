<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Emails {

	const OPT_FROM_EMAIL = 'tc_eligibility_from_email';
	const OPT_FROM_NAME = 'tc_eligibility_from_name';
	const OPT_CLINICIAN_RECIPIENTS = 'tc_eligibility_clinician_recipients';
	const OPT_SEND_CLINICIAN = 'tc_eligibility_send_clinician_emails';

	public static function send_patient_confirmation( array $payload, $assessment_id ) {
		$email = sanitize_email( $payload['email'] ?? '' );
		if ( ! is_email( $email ) ) {
			TC_Log::warn( 'patient_email_skipped invalid_email', [ 'assessment_id' => $assessment_id ] );
			return false;
		}

		$first_name = sanitize_text_field( $payload['firstName'] ?? 'there' );
		$subject    = sprintf( '[Together Clinic] Your assessment has been submitted, %s', $first_name );

		$body = self::render_template( 'email-patient-confirmation.php', [
			'payload'       => $payload,
			'first_name'    => $first_name,
			'assessment_id' => $assessment_id,
		] );

		$headers = self::build_headers();
		$sent    = wp_mail( $email, $subject, $body, $headers );

		TC_Log::info(
			'patient_email_' . ( $sent ? 'sent' : 'failed' ),
			[ 'assessment_id' => $assessment_id, 'to' => $email ]
		);

		return $sent;
	}

	public static function send_clinician_notification( array $payload, $assessment_id, $eligibility ) {
		if ( get_option( self::OPT_SEND_CLINICIAN, '1' ) !== '1' ) {
			TC_Log::info( 'clinician_email_skipped kill_switch_off', [ 'assessment_id' => $assessment_id ] );
			return false;
		}

		$recipients = self::clinician_recipients();
		if ( empty( $recipients ) ) {
			TC_Log::warn( 'clinician_email_skipped no_recipients_configured', [ 'assessment_id' => $assessment_id ] );
			return false;
		}

		$patient = trim( sanitize_text_field( ( $payload['firstName'] ?? '' ) . ' ' . ( $payload['lastName'] ?? '' ) ) );
		$state   = $eligibility['eligible'] ? 'ELIGIBLE' : 'INELIGIBLE';
		$tx      = sanitize_text_field( $payload['selectedTreatment'] ?? '' );
		$dose    = sanitize_text_field( $payload['selectedDose'] ?? '' );

		$subject = sprintf(
			'[Together Clinic] [%s] %s — %s %s',
			$state,
			$patient ?: 'New assessment',
			ucfirst( $tx ),
			$dose
		);

		$body = self::render_template( 'email-clinician-review.php', [
			'payload'       => $payload,
			'eligibility'   => $eligibility,
			'assessment_id' => $assessment_id,
		] );

		$headers = self::build_headers();
		$sent    = wp_mail( $recipients, $subject, $body, $headers );

		TC_Log::info(
			'clinician_email_' . ( $sent ? 'sent' : 'failed' ),
			[ 'assessment_id' => $assessment_id, 'to' => implode( ',', $recipients ) ]
		);

		return $sent;
	}

	public static function clinician_recipients() {
		$raw = get_option( self::OPT_CLINICIAN_RECIPIENTS, 'ahmed@at-health.co.uk,care@togetherclinic.co.uk' );
		$arr = array_filter( array_map( 'trim', explode( ',', (string) $raw ) ), 'is_email' );
		return array_values( $arr );
	}

	private static function build_headers() {
		$from_email = sanitize_email( get_option( self::OPT_FROM_EMAIL, 'care@togetherclinic.co.uk' ) );
		$from_name  = sanitize_text_field( get_option( self::OPT_FROM_NAME, 'Together Clinic' ) );

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		if ( $from_email ) {
			$headers[] = sprintf( 'From: %s <%s>', $from_name ?: 'Together Clinic', $from_email );
			$headers[] = sprintf( 'Reply-To: %s', $from_email );
		}
		return $headers;
	}

	private static function render_template( $file, array $vars ) {
		$path = TC_ELIGIBILITY_PATH . 'templates/' . $file;
		if ( ! file_exists( $path ) ) {
			return '';
		}

		extract( $vars, EXTR_SKIP );
		ob_start();
		include $path;
		$content = ob_get_clean();

		if ( function_exists( 'WC' ) && WC()->mailer() ) {
			$mailer = WC()->mailer();
			if ( method_exists( $mailer, 'wrap_message' ) ) {
				return $mailer->wrap_message( '', $content );
			}
		}

		return $content;
	}

	public static function inject_into_woo_order_email( $order, $sent_to_admin = false ) {
		// The assessment summary contains sensitive clinical data (BMI, DOB,
		// treatment). It must only ever appear in admin/clinician-facing order
		// emails, never in the customer's copy.
		if ( ! $sent_to_admin ) {
			return;
		}
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}
		$raw = $order->get_meta( '_tc_eligibility_raw' );
		if ( ! $raw ) {
			return;
		}
		$payload = json_decode( $raw, true );
		if ( ! is_array( $payload ) ) {
			return;
		}

		echo '<h2 style="margin-top: 32px;">Assessment summary</h2>';
		echo '<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; font-size: 13px;">';
		printf( '<tr><th align="left">Patient type</th><td>%s</td></tr>', esc_html( ( $payload['userType'] ?? '' ) === 'switching' ? 'Switching provider' : 'New to treatment' ) );
		printf( '<tr><th align="left">Selected treatment</th><td>%s %s</td></tr>',
			esc_html( ucfirst( $payload['selectedTreatment'] ?? '' ) ),
			esc_html( $payload['selectedDose'] ?? '' )
		);
		printf( '<tr><th align="left">BMI</th><td>%s</td></tr>', esc_html( number_format( (float) ( $payload['bmi'] ?? 0 ), 1 ) ) );
		printf( '<tr><th align="left">DOB</th><td>%s</td></tr>', esc_html( $payload['dob'] ?? '' ) );
		echo '</table>';
	}
}
