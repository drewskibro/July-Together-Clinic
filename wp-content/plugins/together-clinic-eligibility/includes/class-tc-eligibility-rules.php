<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Eligibility_Rules {

	const DISQUALIFYING_CONDITIONS = [
		'chronic_malabsorption' => 'chronic malabsorption syndrome',
		'cholestasis'           => 'cholestasis',
		'cancer_treatment'      => "currently being treated for cancer",
		'diabetic_retinopathy'  => 'diabetic retinopathy',
		'heart_failure'         => 'severe heart failure',
		'thyroid_cancer'        => 'family history of thyroid cancer',
		'kidney_disease'        => 'end-stage kidney disease',
		'men2'                  => 'Multiple endocrine neoplasia type 2 (MEN2)',
		'pancreatitis'          => 'history of pancreatitis',
		'eating_disorder'       => 'eating disorder',
		'thyroid_surgery'       => 'surgery or an operation to my thyroid',
	];

	const MIN_BMI_DEFAULT = 27.0;
	const MIN_BMI_SOUTH_ASIAN = 23.0;

	public static function evaluate( array $payload ) {
		$age_band = (string) ( $payload['ageBand'] ?? '' );
		if ( $age_band === 'under-18' ) {
			return self::ineligible( "Our weight loss plan isn't suitable for people under 18 years old." );
		}
		if ( $age_band === '75-over' ) {
			return self::ineligible( "Our weight loss plan isn't suitable for people over 75 years old." );
		}

		$dob_age = self::age_from_dob( $payload['dob'] ?? '' );
		if ( $dob_age !== null ) {
			if ( $dob_age < 18 ) {
				return self::ineligible( 'You must be at least 18 years old to use this service.' );
			}
			if ( $dob_age >= 75 ) {
				return self::ineligible( "Our weight loss plan isn't suitable for people over 75 years old." );
			}
		}

		if ( ( $payload['sex'] ?? '' ) === 'female' ) {
			if ( ( $payload['pregnant'] ?? '' ) === 'yes'
				|| ( $payload['breastfeeding'] ?? '' ) === 'yes'
				|| ( $payload['conceive'] ?? '' ) === 'yes' ) {
				return self::ineligible( 'For safety reasons, weight loss medications cannot be prescribed during pregnancy, when planning to become pregnant, or while breastfeeding.' );
			}
		}

		$bmi       = (float) ( $payload['bmi'] ?? 0 );
		$ethnicity = strtolower( (string) ( $payload['ethnicity'] ?? '' ) );
		$min_bmi   = self::min_bmi_for_ethnicity( $ethnicity );

		if ( $bmi > 0 && $bmi < $min_bmi ) {
			$is_south_asian = strpos( $ethnicity, 'asian' ) !== false;
			$reason         = sprintf(
				'Based on your BMI of %s, weight loss medication is not clinically appropriate at this time. A BMI of %s or above is required%s.',
				number_format( $bmi, 1 ),
				number_format( $min_bmi, 1 ),
				$is_south_asian ? ' (adjusted for South Asian ethnicity)' : ''
			);
			return self::ineligible( $reason );
		}

		$conditions = (array) ( $payload['conditions'] ?? [] );
		foreach ( $conditions as $condition ) {
			if ( self::is_disqualifying_condition( $condition ) ) {
				return self::ineligible( 'Based on the medical history you provided, weight loss medication is not clinically appropriate. Please speak with your GP about alternative options.' );
			}
		}

		$bariatric_details = trim( (string) ( $payload['bariatricDetails'] ?? '' ) );
		$has_bariatric     = self::list_contains( $conditions, 'bariatric' );
		if ( $has_bariatric && ( $payload['bariatricRecent'] ?? '' ) === 'yes' ) {
			return self::ineligible( 'Weight loss medication is not suitable within 6 months of bariatric surgery.' );
		}

		if ( empty( $payload['termsAgreed'] ) ) {
			return self::ineligible( 'You must agree to the terms and conditions to proceed.' );
		}

		return [ 'eligible' => true, 'reason' => '' ];
	}

	public static function min_bmi_for_ethnicity( $ethnicity ) {
		$ethnicity = strtolower( (string) $ethnicity );
		$override  = (float) get_option( 'tc_eligibility_min_bmi_default', self::MIN_BMI_DEFAULT );
		$asian     = (float) get_option( 'tc_eligibility_min_bmi_south_asian', self::MIN_BMI_SOUTH_ASIAN );

		if ( strpos( $ethnicity, 'asian' ) !== false ) {
			return $asian > 0 ? $asian : self::MIN_BMI_SOUTH_ASIAN;
		}

		return $override > 0 ? $override : self::MIN_BMI_DEFAULT;
	}

	public static function is_disqualifying_condition( $condition ) {
		$condition = strtolower( trim( (string) $condition ) );
		if ( $condition === '' || $condition === 'none of these apply' ) {
			return false;
		}

		foreach ( self::DISQUALIFYING_CONDITIONS as $needle ) {
			if ( strpos( $condition, strtolower( $needle ) ) !== false ) {
				return true;
			}

			$first_words = implode( ' ', array_slice( explode( ' ', strtolower( $needle ) ), 0, 3 ) );
			if ( $first_words && strpos( $condition, $first_words ) !== false ) {
				return true;
			}
		}

		return false;
	}

	private static function list_contains( array $list, $needle ) {
		$needle = strtolower( $needle );
		foreach ( $list as $item ) {
			if ( strpos( strtolower( (string) $item ), $needle ) !== false ) {
				return true;
			}
		}
		return false;
	}

	private static function age_from_dob( $dob ) {
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

	private static function ineligible( $reason ) {
		return [ 'eligible' => false, 'reason' => $reason ];
	}
}
