<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side triage rules for the weight-management questionnaire.
 *
 * Source of truth: AT Health IP-FRM-01 section 3A, rules version
 * WM-2026-10-v1 (Superintendent Pharmacist). The questionnaire is TRIAGE
 * ONLY: it screens out patients who clearly fall outside the product licence
 * or meet an exclusion. It never decides that a patient will be treated; the
 * prescriber decides in a video consultation after ID, weight/height and
 * Summary Care Record checks.
 *
 * Key rules:
 *  - Starting BMI is the licence threshold for every adult: 30+, or 27 to
 *    29.9 with a weight-related condition. No ethnicity reduction (none of the
 *    four UK licences allows one; NICE's 2.5 reduction applies to NHS
 *    thresholds that sit above the licence).
 *  - BMI is always recalculated here from weight and height. The browser's
 *    BMI is never trusted.
 *  - Patients already on a GLP-1 are assessed on their BMI when they first
 *    started (documentary proof checked by the prescriber) and a current BMI
 *    floor of 20.
 *  - Possible medicine exclusions are FLAGGED for the prescriber from the
 *    free-text medicines list, never shown to the patient (GPhC review of
 *    weight management services, April 2026).
 *
 * evaluate() returns:
 *   eligible  bool    may proceed to a prescriber consultation
 *   reason    string  patient-facing reason when not eligible
 *   bmi       float   server-calculated current BMI
 *   start_bmi float   server-calculated BMI when first starting (switchers)
 *   pathway   string  new | transfer
 *   flags     array   key => prescriber-facing note (merged into order flags)
 *   rules_version string
 */
class TC_Eligibility_Rules {

	const RULES_VERSION = 'WM-2026-10-v1';

	const DISQUALIFYING_CONDITIONS = [
		'chronic_malabsorption' => 'chronic malabsorption syndrome',
		'cholestasis'           => 'cholestasis',
		'cancer_treatment'      => 'currently being treated for cancer',
		'diabetic_retinopathy'  => 'diabetic retinopathy',
		'heart_failure'         => 'severe heart failure',
		'thyroid_cancer'        => 'family history of thyroid cancer',
		'kidney_disease'        => 'severe kidney disease',
		'kidney_disease_legacy' => 'end-stage kidney disease',
		'liver_disease'         => 'severe liver disease',
		'gi_disease'            => 'severe stomach or bowel',
		'men2'                  => 'Multiple endocrine neoplasia type 2 (MEN2)',
		'pancreatitis'          => 'history of pancreatitis',
		'eating_disorder'       => 'eating disorder',
		'thyroid_surgery'       => 'surgery or an operation to my thyroid',
		'secondary_obesity'     => 'weight gain is caused by',
	];

	/** Weight-related conditions named in the SmPCs: qualify at BMI 27 to 29.9. */
	const COMORBIDITY_A = [
		'high blood pressure' => 'High blood pressure',
		'high cholesterol'    => 'High cholesterol / dyslipidaemia',
		'sleep apnoea'        => 'Obstructive sleep apnoea',
		'heart or circulation' => 'Cardiovascular disease',
		'heart/cardiovascular' => 'Cardiovascular disease',
	];

	/** Qualify only where the prescriber records the clinical link to weight. */
	const COMORBIDITY_B = [
		'osteoarthritis' => 'Osteoarthritis',
		'polycystic'     => 'Polycystic ovary syndrome',
		'fatty liver'    => 'Fatty liver disease',
		'copd'           => 'COPD',
	];

	/** Diabetes answers that count as a list A condition. */
	const DIABETES_QUALIFYING = [ 'type2-meds', 'type2-diet', 'pre', 'medication', 'diet' ];

	const WEEKLY_PRODUCTS = [ 'wegovy', 'mounjaro' ];

	/**
	 * Medicine keywords scanned in the free-text list. Each group raises a
	 * prescriber flag; nothing here blocks automatically, because a keyword
	 * match cannot tell "I take gliclazide" from "I stopped gliclazide".
	 * 'products' limits a group to selected treatments (empty = all).
	 */
	const MEDICINE_FLAGS = [
		'insulin'         => [ 'words' => [ 'insulin', 'novorapid', 'humalog', 'lantus', 'levemir', 'tresiba', 'toujeo', 'abasaglar', 'humulin' ], 'products' => [], 'note' => 'POSSIBLE EXCLUSION: insulin mentioned. AT Health policy excludes insulin users (refer to GP or diabetes team). Confirm at consultation and on SCR.' ],
		'sulfonylurea'    => [ 'words' => [ 'gliclazide', 'glimepiride', 'glipizide', 'glibenclamide', 'tolbutamide' ], 'products' => [], 'note' => 'POSSIBLE EXCLUSION: sulfonylurea mentioned. AT Health policy excludes (hypoglycaemia; refer). Confirm at consultation and on SCR.' ],
		'dpp4'            => [ 'words' => [ 'sitagliptin', 'linagliptin', 'alogliptin', 'saxagliptin', 'vildagliptin', 'januvia', 'trajenta', 'janumet', 'jentadueto' ], 'products' => [], 'note' => 'POSSIBLE EXCLUSION: DPP-4 inhibitor mentioned. AT Health policy excludes for all four products.' ],
		'glp1'            => [ 'words' => [ 'semaglutide', 'ozempic', 'rybelsus', 'wegovy', 'liraglutide', 'saxenda', 'victoza', 'dulaglutide', 'trulicity', 'exenatide', 'byetta', 'bydureon', 'tirzepatide', 'mounjaro', 'orforglipron', 'foundayo' ], 'products' => [], 'note' => 'GLP-1 medicine mentioned in current medicines. Never two GLP-1 products together: confirm current supply has stopped and record date of last dose.' ],
		'other_weightloss' => [ 'words' => [ 'orlistat', 'alli', 'xenical', 'mysimba', 'phentermine', 'naltrexone' ], 'products' => [], 'note' => 'POSSIBLE EXCLUSION: another weight-loss medicine mentioned. Policy: no other weight-loss medicines, including OTC or herbal.' ],
		'warfarin'        => [ 'words' => [ 'warfarin', 'acenocoumarol', 'phenindione' ], 'products' => [], 'note' => 'Warfarin/coumarin: INR plan with anticoagulation provider before starting (SmPC 4.5).' ],
		'digoxin'         => [ 'words' => [ 'digoxin' ], 'products' => [ 'mounjaro' ], 'note' => 'Digoxin with tirzepatide: monitor at start and after each dose increase (SmPC 4.5).' ],
		'levothyroxine'   => [ 'words' => [ 'levothyroxine', 'thyroxine', 'liothyronine' ], 'products' => [ 'wegovy-tablets', 'wegovy' ], 'note' => 'Levothyroxine with semaglutide: thyroxine exposure rises (tablets SmPC 4.5); check thyroid function; tablets need empty-stomach timing advice.' ],
		'foundayo_avoid'  => [ 'words' => [ 'ritonavir', 'paxlovid', 'telaprevir', 'carbamazepine', 'tegretol', 'rifampicin', 'rifampin', 'phenytoin', "st john", 'st. john' ], 'products' => [ 'foundayo' ], 'note' => 'FOUNDAYO: strong CYP3A4 inducer or CYP3A4+OATP1B inhibitor mentioned. SmPC: avoid. Choose another product or do not treat.' ],
		'foundayo_cap'    => [ 'words' => [ 'ketoconazole', 'clarithromycin', 'itraconazole', 'ciclosporin', 'cyclosporine' ], 'products' => [ 'foundayo' ], 'note' => 'FOUNDAYO: strong CYP3A4 or OATP1B inhibitor mentioned. SmPC: maximum 9 mg daily.' ],
		'foundayo_other'  => [ 'words' => [ 'bosentan', 'efavirenz', 'simvastatin', 'rosuvastatin', 'topotecan' ], 'products' => [ 'foundayo' ], 'note' => 'FOUNDAYO interaction (SmPC 4.5): moderate inducer monitor/adjust; halve simvastatin; rosuvastatin above 20 mg caution; oral topotecan monitor.' ],
	];

	public static function evaluate( array $payload ) {
		$flags = [];
		$base  = [
			'eligible'      => false,
			'reason'        => '',
			'bmi'           => 0.0,
			'start_bmi'     => 0.0,
			'pathway'       => ( ( $payload['userType'] ?? '' ) === 'switching' ) ? 'transfer' : 'new',
			'flags'         => [],
			'rules_version' => self::RULES_VERSION,
		];

		$age_band = (string) ( $payload['ageBand'] ?? '' );
		if ( $age_band === 'under-18' ) {
			return self::ineligible( $base, "Our weight loss plan isn't suitable for people under 18 years old." );
		}
		if ( $age_band === '75-over' ) {
			return self::ineligible( $base, "Our weight loss plan isn't suitable for people over 75 years old." );
		}

		$dob_age = self::age_from_dob( $payload['dob'] ?? '' );
		if ( $dob_age !== null ) {
			if ( $dob_age < 18 ) {
				return self::ineligible( $base, 'You must be at least 18 years old to use this service.' );
			}
			if ( $dob_age >= 75 ) {
				return self::ineligible( $base, "Our weight loss plan isn't suitable for people over 75 years old." );
			}
		}

		$is_female = ( $payload['sex'] ?? '' ) === 'female';
		if ( $is_female ) {
			if ( ( $payload['pregnant'] ?? '' ) === 'yes'
				|| ( $payload['breastfeeding'] ?? '' ) === 'yes'
				|| ( $payload['conceive'] ?? '' ) === 'yes' ) {
				return self::ineligible( $base, 'For safety reasons, weight loss medications cannot be prescribed during pregnancy, when planning to become pregnant, or while breastfeeding.' );
			}
		}

		// BMI is always recalculated from weight and height.
		$bmi = self::bmi( $payload['weightKg'] ?? 0, $payload['heightCm'] ?? 0 );
		if ( $bmi === null ) {
			return self::ineligible( $base, 'We could not work out your BMI. Please check the weight and height you entered.' );
		}
		$base['bmi'] = $bmi;
		$sent_bmi    = (float) ( $payload['bmi'] ?? 0 );
		if ( $sent_bmi > 0 && abs( $sent_bmi - $bmi ) > 0.5 ) {
			$flags['bmi_mismatch'] = sprintf( 'Browser BMI %.1f differs from server BMI %.1f; server value used.', $sent_bmi, $bmi );
		}

		$diabetes = (string) ( $payload['diabetes'] ?? '' );
		if ( $diabetes === 'type1' ) {
			return self::ineligible( $base, 'Based on your answers, our online weight loss service is not suitable for you. Please speak with your GP or diabetes team about the options available to you.' );
		}

		$conditions = (array) ( $payload['conditions'] ?? [] );
		foreach ( $conditions as $condition ) {
			if ( self::is_disqualifying_condition( $condition ) ) {
				return self::ineligible( $base, 'Based on the medical history you provided, weight loss medication is not clinically appropriate. Please speak with your GP about alternative options.' );
			}
		}

		$has_bariatric = self::list_contains( $conditions, 'bariatric' );
		if ( $has_bariatric && ( $payload['bariatricRecent'] ?? '' ) === 'yes' ) {
			return self::ineligible( $base, 'Weight loss medication is not suitable within 6 months of bariatric surgery.' );
		}
		if ( $has_bariatric ) {
			$flags['bariatric_history'] = 'Previous bariatric surgery (more than 6 months ago): review details before prescribing.';
		}

		if ( empty( $payload['termsAgreed'] ) ) {
			return self::ineligible( $base, 'You must agree to the terms and conditions to proceed.' );
		}

		// Mandatory consents (AT Health decisions 16 and 18 Sep 2026).
		if ( empty( $payload['consentIdVideo'] ) || empty( $payload['gpConsentSCR'] ) || empty( $payload['consentLifestyle'] ) ) {
			return self::ineligible( $base, 'To be treated, you need to agree to a photo ID check, a video consultation where your weight and height are checked, a check of your NHS Summary Care Record, and to follow a reduced-calorie diet with more physical activity.' );
		}
		if ( empty( $payload['gpConsentShare'] ) ) {
			$flags['no_gp_consent'] = 'RED FLAG: patient did not consent to GP sharing. NPA: proceeding is unlikely to be appropriate; record individual risk-based decision.';
		}

		if ( $is_female && ( $payload['couldConceive'] ?? '' ) === 'yes' ) {
			if ( empty( $payload['consentContraception'] ) ) {
				return self::ineligible( $base, 'These medicines can only be prescribed if you agree to use effective contraception during treatment.' );
			}
			$contraception = (string) ( $payload['contraception'] ?? '' );
			if ( $contraception === 'none' ) {
				$flags['contraception_none'] = 'Could become pregnant and reports no contraception: counsel and confirm effective method before prescribing (Mounjaro SmPC: not recommended without contraception).';
			}
			if ( $contraception === 'pill' ) {
				$flags['contraception_pill'] = 'Oral contraceptive: if Mounjaro, non-oral method or barrier for 4 weeks after starting and each increase; if Foundayo, the same for 30 days.';
			}
		}

		$comorbidity = self::comorbidity( $payload );
		if ( $comorbidity['a'] ) {
			$flags['comorbidity_a'] = 'Weight-related condition reported (SmPC-named): ' . implode( ', ', $comorbidity['a'] ) . '. Verify on SCR if relied on.';
		}
		if ( $comorbidity['b'] ) {
			$flags['comorbidity_b'] = 'Condition needing prescriber judgement: ' . implode( ', ', $comorbidity['b'] ) . '. Record clinical link to weight if relied on.';
		}

		$not_licensed = 'Based on your answers, the weight loss medicines we offer are not licensed for you at the moment. Please speak with your GP, who can discuss other support.';

		// Transfer rules apply only if the last dose was within 3 months;
		// otherwise the patient is assessed as starting treatment (3A.3).
		$days = null;
		if ( $base['pathway'] === 'transfer' ) {
			$days = self::days_since( $payload['lastDoseDate'] ?? '' );
			if ( $days === null ) {
				return self::ineligible( $base, 'Please tell us the date of your last dose.' );
			}
			if ( $days > 91 ) {
				$base['pathway']           = 'new';
				$flags['restart_over_3m']  = sprintf( 'Last GLP-1 dose %d days ago (more than 3 months): assessed as starting treatment on current BMI; first step of the ladder.', $days );
			}
		}

		if ( $base['pathway'] === 'new' ) {
			if ( $bmi < 27 ) {
				return self::ineligible( $base, $not_licensed );
			}
			if ( $bmi < 30 ) {
				if ( ! $comorbidity['a'] && ! $comorbidity['b'] ) {
					return self::ineligible( $base, $not_licensed );
				}
				$flags['pathway'] = $comorbidity['a']
					? sprintf( 'BMI %.1f (27 to 29.9): eligible only with the condition relied on, verified.', $bmi )
					: sprintf( 'BMI %.1f (27 to 29.9) with list B condition only: prescriber judgement required.', $bmi );
			}
		} else {
			// Transfer / restart within 3 months / change of product. Current
			// BMI must be above 25 (AT Health policy, as the Deltera PGDs);
			// the 20 to 25 bands apply only to continuing AT Health patients.
			if ( $bmi <= 25 ) {
				return self::ineligible( $base, $not_licensed );
			}

			$start_bmi = self::bmi( $payload['startWeightKg'] ?? 0, $payload['heightCm'] ?? 0 );
			if ( $start_bmi === null ) {
				return self::ineligible( $base, 'Please tell us your weight when you first started weight loss medication.' );
			}
			$base['start_bmi'] = $start_bmi;
			if ( $start_bmi < 27 || ( $start_bmi < 30 && ! $comorbidity['a'] && ! $comorbidity['b'] ) ) {
				return self::ineligible( $base, $not_licensed );
			}
			$flags['proof_required'] = sprintf( 'Transfer: declared starting BMI %.1f. Before prescribing, see evidence from a UK-registered prescriber or pharmacy of: BMI when first starting a GLP-1, product, current dose and date of last supply. If dose or last-dose date cannot be evidenced, start at the first step. Unregulated sources (research peptides, unlicensed or overseas products) are not accepted: assess as a new patient.', $start_bmi );
			$flags['last_dose']      = sprintf( 'Last dose %s (%d days ago).', sanitize_text_field( (string) $payload['lastDoseDate'] ), $days );
		}

		foreach ( self::medicine_flags( $payload ) as $key => $note ) {
			$flags[ 'med_' . $key ] = $note;
		}

		$flags['triage_only'] = 'Triage passed (rules ' . self::RULES_VERSION . '). Video consultation, photo ID, SCR check and independent weight/height verification required before prescribing.';

		$base['eligible'] = true;
		$base['flags']    = $flags;
		return $base;
	}

	public static function bmi( $weight_kg, $height_cm ) {
		$w = (float) $weight_kg;
		$h = (float) $height_cm;
		if ( $w < 30 || $w > 350 || $h < 120 || $h > 230 ) {
			return null;
		}
		return round( $w / pow( $h / 100, 2 ), 1 );
	}

	/**
	 * @return array { a: string[], b: string[] }
	 */
	public static function comorbidity( array $payload ) {
		$a = [];
		$b = [];
		foreach ( (array) ( $payload['weightConditions'] ?? [] ) as $c ) {
			$c = strtolower( (string) $c );
			foreach ( self::COMORBIDITY_A as $needle => $label ) {
				if ( strpos( $c, $needle ) !== false ) {
					$a[ $label ] = $label;
				}
			}
			foreach ( self::COMORBIDITY_B as $needle => $label ) {
				if ( strpos( $c, $needle ) !== false ) {
					$b[ $label ] = $label;
				}
			}
		}
		$diabetes = (string) ( $payload['diabetes'] ?? '' );
		if ( in_array( $diabetes, self::DIABETES_QUALIFYING, true ) ) {
			$label       = ( $diabetes === 'pre' ) ? 'Pre-diabetes' : 'Type 2 diabetes';
			$a[ $label ] = $label;
		}
		return [ 'a' => array_values( $a ), 'b' => array_values( $b ) ];
	}

	public static function medicine_flags( array $payload ) {
		$text      = ' ' . strtolower( (string) ( $payload['currentMedsList'] ?? '' ) ) . ' ';
		$treatment = (string) ( $payload['selectedTreatment'] ?? '' );
		$out       = [];
		if ( trim( $text ) === '' ) {
			return $out;
		}
		foreach ( self::MEDICINE_FLAGS as $key => $group ) {
			if ( $group['products'] && ! in_array( $treatment, $group['products'], true ) ) {
				continue;
			}
			foreach ( $group['words'] as $word ) {
				if ( preg_match( '/(?<![a-z])' . preg_quote( $word, '/' ) . '(?![a-z])/', $text ) ) {
					$out[ $key ] = $group['note'];
					break;
				}
			}
		}
		return $out;
	}

	/**
	 * Days since an ISO date (YYYY-MM-DD). Null if missing, invalid, in the
	 * future, or more than 5 years ago.
	 */
	public static function days_since( $iso ) {
		$iso = (string) $iso;
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $iso ) ) {
			return null;
		}
		try {
			$date  = new DateTime( $iso );
			$today = new DateTime( 'today' );
		} catch ( Exception $e ) {
			return null;
		}
		if ( $date->format( 'Y-m-d' ) !== $iso || $date > $today ) {
			return null;
		}
		$days = (int) $today->diff( $date )->days;
		return ( $days > 1826 ) ? null : $days;
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

	private static function ineligible( array $base, $reason ) {
		$base['eligible'] = false;
		$base['reason']   = $reason;
		$base['flags']    = [];
		return $base;
	}
}
