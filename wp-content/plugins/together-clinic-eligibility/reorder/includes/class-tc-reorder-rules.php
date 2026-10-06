<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Reorder_Rules {

	public static function evaluate( array $payload, $prefill ) {
		if ( ( $payload['healthChanged'] ?? '' ) === 'yes' ) {
			return self::block( 'health_changed', 'A clinician consultation is required because of new or worsening health.' );
		}

		if ( ( $payload['couldBePregnant'] ?? '' ) === 'yes' ) {
			return self::block( 'pregnancy', 'Weight loss medications cannot be prescribed during pregnancy.' );
		}

		$payload_med = TC_Reorder_Pricing::normalize_treatment( $payload['currentMedication'] ?? '' );
		$prev_med    = isset( $prefill['previous_medication'] ) ? TC_Reorder_Pricing::normalize_treatment( $prefill['previous_medication'] ) : '';

		if ( $prev_med && $payload_med && $payload_med !== $prev_med ) {
			return self::block( 'medication_mismatch', 'Switching medication requires a fresh clinical assessment.' );
		}

		$dob_age = self::age_from_dob( $payload['dob'] ?? '' );
		if ( $dob_age !== null && $dob_age < 18 ) {
			return self::block( 'under_18', 'You must be at least 18 years old to use this service.' );
		}

		// Rules WM-2026-10-v1 (IP-FRM-01 3A.7): stop below BMI 20. Uses the
		// height from the patient's eligibility assessment and the weight
		// they report now. The prescriber reviews 20 to 22.9 at approval.
		if ( class_exists( 'TC_DB' ) && class_exists( 'TC_Eligibility_Rules' ) ) {
			$height = TC_DB::latest_height_for_user( get_current_user_id() );
			$bmi    = TC_Eligibility_Rules::bmi( $payload['currentWeight'] ?? 0, $height );
			if ( $bmi !== null && $bmi < 20 ) {
				return self::block( 'bmi_floor', 'Based on your current weight, a clinician consultation is needed before any further treatment.' );
			}
		}

		return [ 'ok' => true, 'reason' => '' ];
	}

	public static function age_from_dob( $dob ) {
		if ( empty( $dob ) ) {
			return null;
		}
		try {
			$dob_date = new DateTime( $dob );
			$today    = new DateTime( 'today' );
			if ( $dob_date > $today ) {
				return null;
			}
			return (int) $today->diff( $dob_date )->y;
		} catch ( Exception $e ) {
			return null;
		}
	}

	private static function block( $code, $message ) {
		return [
			'ok'      => false,
			'code'    => $code,
			'reason'  => $message,
		];
	}
}
