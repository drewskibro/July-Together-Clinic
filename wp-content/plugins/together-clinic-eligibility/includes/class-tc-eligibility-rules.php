<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side triage rules for the weight-management questionnaire.
 *
 * Source of truth: AT Health IP-FRM-01 section 3A, rules version
 * WM-2026-10-v2 (Superintendent Pharmacist). The questionnaire is TRIAGE
 * ONLY: it screens out patients who clearly fall outside the product licence
 * or meet an exclusion. It never decides that a patient will be treated; the
 * prescriber decides in a video consultation after ID, weight/height and
 * Summary Care Record checks.
 *
 * Key rules:
 *  - Age 18 to 85 inclusive, by date of birth; screened out from the 86th
 *    birthday (rule S1). Ages 75 to 85 (rule S1A): extra answers (falls, fracture, PRISMA-7, medicines
 *    count, blood pressure or water tablets, kidney test) are recorded for
 *    the prescriber. Those answers never pass or fail anyone, except a
 *    reported eGFR below 30 (exclusion E11).
 *  - Starting BMI is the licence threshold for every adult: 30+, or 27 to
 *    29.9 with a weight-related condition. No ethnicity reduction (none of the
 *    four UK licences allows one; NICE's 2.5 reduction applies to NHS
 *    thresholds that sit above the licence).
 *  - BMI is always recalculated here from weight and height. The browser's
 *    BMI is never trusted.
 *  - Patients already on a GLP-1 (last dose within 3 months) are assessed
 *    on their BMI when they first started (documentary proof checked by the
 *    prescriber) and must have a current BMI above 25. More than 3 months
 *    off treatment: assessed as starting treatment.
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
 *   prisma7_score int|null  ages 75 to 85 only (rule S1A)
 *   rules_version string
 */
class TC_Eligibility_Rules {

	const RULES_VERSION = 'WM-2026-10-v2';

	/** Rule S1: online service age range, inclusive, by date of birth. */
	const MIN_AGE = 18;
	const MAX_AGE = 85;
	/** Rule S1A applies from this age to MAX_AGE. */
	const S1A_AGE = 75;

	/** Heading used wherever the rule S1A answers are shown to staff. */
	const S1A_LABEL = 'Age 75 to 85: rule S1A checks';

	/** PRISMA-7 questions 3 to 7 (asked); 1 and 2 come from DOB and sex. */
	const PRISMA_ITEMS = [
		'limitActivities' => 'Health problems that limit activities',
		'needHelp'        => 'Needs someone to help on a regular basis',
		'stayHome'        => 'Health problems that require staying at home',
		'countOnSomeone'  => 'Can count on someone close if help is needed',
		'walkingAid'      => 'Regularly uses a stick, walker or wheelchair',
	];

	const S1A_MEDS_COUNT = [
		'under-5' => 'Fewer than 5',
		'5-9'     => '5 to 9',
		'10-plus' => '10 or more',
		'unsure'  => 'Not sure',
	];

	const S1A_KIDNEY_TEST = [
		'within-12m' => 'In the last 12 months',
		'over-12m'   => 'More than 12 months ago',
		'unsure'     => 'Never, or not sure',
	];

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
		'foundayo_bp'     => [ 'words' => [ 'amlodipine', 'felodipine', 'nifedipine', 'lercanidipine', 'ramipril', 'lisinopril', 'perindopril', 'enalapril', 'losartan', 'candesartan', 'irbesartan', 'valsartan', 'olmesartan', 'telmisartan', 'bisoprolol', 'atenolol', 'propranolol', 'metoprolol', 'nebivolol', 'indapamide', 'bendroflumethiazide', 'furosemide', 'doxazosin', 'spironolactone' ], 'products' => [ 'foundayo' ], 'note' => 'FOUNDAYO: blood pressure medicine mentioned. SmPC 4.4: hypotension more frequent with antihypertensives; warn and monitor.' ],
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
			'prisma7_score' => null,
			'rules_version' => self::RULES_VERSION,
		];

		$too_old  = "Our weight loss plan isn't suitable for people aged 86 or over.";
		$age_band = (string) ( $payload['ageBand'] ?? '' );
		if ( $age_band === 'under-18' ) {
			return self::ineligible( $base, "Our weight loss plan isn't suitable for people under 18 years old." );
		}
		if ( $age_band === '86-over' ) {
			return self::ineligible( $base, $too_old );
		}

		// Rule S1 is applied to the date of birth, which is required.
		$dob_age = self::age_from_dob( $payload['dob'] ?? '' );
		if ( $dob_age === null ) {
			return self::ineligible( $base, 'Please enter your date of birth.' );
		}
		if ( $dob_age < self::MIN_AGE ) {
			return self::ineligible( $base, 'You must be at least 18 years old to use this service.' );
		}
		if ( $dob_age > self::MAX_AGE ) {
			return self::ineligible( $base, $too_old );
		}
		$is_s1a = $dob_age >= self::S1A_AGE;

		$is_female = ( $payload['sex'] ?? '' ) === 'female';
		if ( $is_female ) {
			foreach ( [ 'pregnant', 'breastfeeding', 'conceive', 'couldConceive' ] as $tc_q ) {
				if ( ! in_array( $payload[ $tc_q ] ?? '', [ 'yes', 'no' ], true ) ) {
					return self::ineligible( $base, 'Please answer all the pregnancy and contraception questions.' );
				}
			}
			if ( ( $payload['pregnant'] ?? '' ) === 'yes'
				|| ( $payload['breastfeeding'] ?? '' ) === 'yes'
				|| ( $payload['conceive'] ?? '' ) === 'yes' ) {
				return self::ineligible( $base, 'For safety reasons, weight loss medications cannot be prescribed during pregnancy, when planning to become pregnant, or while breastfeeding.' );
			}
		}

		// BMI is always recalculated from weight and height.
		$bmi     = self::bmi( $payload['weightKg'] ?? 0, $payload['heightCm'] ?? 0 );
		$bmi_raw = self::bmi_raw( $payload['weightKg'] ?? 0, $payload['heightCm'] ?? 0 );
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
		// Consent 7.4: no GP sharing is a red flag at every age, never an
		// automatic stop; the prescriber decides (GPhC distance guidance 4.2 k).
		// From 75 the prescriber gives particular weight to the lack of GP
		// oversight (rule S1A, Superintendent decision 6 Oct 2026).
		if ( empty( $payload['gpConsentShare'] ) ) {
			$flags['no_gp_consent'] = 'RED FLAG: patient did not consent to GP sharing. NPA: proceeding is unlikely to be appropriate; record individual risk-based decision (GPhC 4.2 k).'
				. ( $is_s1a ? ' Age 75 to 85: give particular weight to the lack of GP oversight (rule S1A).' : '' );
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
			// Thresholds use the unrounded BMI so 26.96 never passes as 27.0.
			if ( $bmi_raw < 27 ) {
				return self::ineligible( $base, $not_licensed );
			}
			if ( $bmi_raw < 30 ) {
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
			if ( $bmi_raw <= 25 ) {
				return self::ineligible( $base, $not_licensed );
			}

			$start_bmi     = self::bmi( $payload['startWeightKg'] ?? 0, $payload['heightCm'] ?? 0 );
			$start_bmi_raw = self::bmi_raw( $payload['startWeightKg'] ?? 0, $payload['heightCm'] ?? 0 );
			if ( $start_bmi === null ) {
				return self::ineligible( $base, 'Please tell us your weight when you first started weight loss medication.' );
			}
			$base['start_bmi'] = $start_bmi;
			if ( $start_bmi_raw < 27 || ( $start_bmi_raw < 30 && ! $comorbidity['a'] && ! $comorbidity['b'] ) ) {
				return self::ineligible( $base, $not_licensed );
			}
			$flags['proof_required'] = sprintf( 'Transfer: declared starting BMI %.1f. Before prescribing, see evidence from a UK-registered prescriber or pharmacy of: BMI when first starting a GLP-1, product, current dose and date of last supply. If dose or last-dose date cannot be evidenced, start at the first step. Unregulated sources (research peptides, unlicensed or overseas products) are not accepted: assess as a new patient.', $start_bmi );
			$flags['last_dose']      = sprintf( 'Last dose %s (%d days ago).', sanitize_text_field( (string) $payload['lastDoseDate'] ), $days );
		}

		foreach ( self::medicine_flags( $payload ) as $key => $note ) {
			$flags[ 'med_' . $key ] = $note;
		}

		if ( $is_s1a ) {
			$s1a = self::s1a_check( $payload, $dob_age );
			if ( $s1a['missing'] ) {
				return self::ineligible( $base, 'Please answer all the extra questions for people aged 75 and over.' );
			}
			if ( $s1a['exclude'] ) {
				// Exclusion E11: eGFR below 30.
				return self::ineligible( $base, 'Based on the medical history you provided, weight loss medication is not clinically appropriate. Please speak with your GP about alternative options.' );
			}
			$base['prisma7_score'] = $s1a['prisma7_score'];
			$flags                 = array_merge( $flags, $s1a['flags'] );
		}

		$flags['triage_only'] = 'Triage passed (rules ' . self::RULES_VERSION . '). Video consultation, photo ID, SCR check and independent weight/height verification required before prescribing.';

		$base['eligible'] = true;
		$base['flags']    = $flags;
		return $base;
	}

	/**
	 * Rule S1A (ages 75 to 85). Triage support only: the answers are recorded
	 * and flagged for the prescriber and never pass or fail the patient, with
	 * one exception: a reported eGFR below 30 is exclusion E11.
	 *
	 * @return array { missing: bool, exclude: bool, prisma7_score: int|null, flags: array }
	 */
	public static function s1a_check( array $payload, $age ) {
		$out = [ 'missing' => false, 'exclude' => false, 'prisma7_score' => null, 'flags' => [] ];
		$yn  = [ 'yes', 'no' ];

		$falls    = (string) ( $payload['s1aFalls'] ?? '' );
		$fracture = (string) ( $payload['s1aFracture'] ?? '' );
		$meds     = (string) ( $payload['s1aMedsCount'] ?? '' );
		$bp       = (string) ( $payload['s1aBpWater'] ?? '' );
		$kidney   = (string) ( $payload['s1aKidneyTest'] ?? '' );
		$prisma   = (array) ( $payload['s1aPrisma'] ?? [] );

		if ( ! in_array( $falls, $yn, true ) || ! in_array( $fracture, $yn, true )
			|| ! isset( self::S1A_MEDS_COUNT[ $meds ] )
			|| ! in_array( $bp, [ 'yes', 'no', 'unsure' ], true )
			|| ! isset( self::S1A_KIDNEY_TEST[ $kidney ] ) ) {
			$out['missing'] = true;
			return $out;
		}
		foreach ( array_keys( self::PRISMA_ITEMS ) as $item ) {
			if ( ! in_array( $prisma[ $item ] ?? '', $yn, true ) ) {
				$out['missing'] = true;
				return $out;
			}
		}

		$egfr      = self::egfr_value( $payload['s1aEgfrResult'] ?? '' );
		$egfr_date = self::egfr_date( $payload['s1aEgfrDate'] ?? '' );
		if ( $egfr !== null && $egfr < 30 ) {
			$out['exclude'] = true;
			return $out;
		}

		// PRISMA-7: one point per "yes" across all seven questions, as on the
		// Raiche PRISMA-7 form (question 1 "older than 85" is always no here).
		$score = ( ( $payload['sex'] ?? '' ) === 'male' ) ? 1 : 0;
		foreach ( array_keys( self::PRISMA_ITEMS ) as $item ) {
			$score += ( $prisma[ $item ] === 'yes' ) ? 1 : 0;
		}
		$out['prisma7_score'] = $score;

		$f = [];
		$f['s1a'] = self::S1A_LABEL . ' (age ' . (int) $age . '). Triage support only, no automatic approval. Record capacity (any doubt: no remote prescribing, face-to-face referral). GP sharing: no GP or refusal is a red flag, prescriber decides, giving particular weight to the lack of GP oversight. Take the full medicines list from the SCR. Weight from a clinical record or in-person weighing if unsteady, never scales on camera; independent weight verification every 3 months. Pause during vomiting, diarrhoea or poor fluid intake. Advise enough protein, and a vitamin and mineral supplement if intake is poor. For Wegovy, explain the fracture finding in people 75 and over (SmPC 4.8).';

		$triggers = [];
		if ( $score >= 3 ) {
			$triggers[] = 'PRISMA-7 score ' . $score . ' (counted as yes answers; some versions score question 6 the other way: confirm)';
		}
		if ( $falls === 'yes' ) {
			$triggers[] = 'fall in the last 12 months';
		}
		if ( $fracture === 'yes' ) {
			$triggers[] = 'fragility fracture';
		}
		if ( $triggers ) {
			$f['s1a_face_to_face'] = 'Rule S1A: ' . implode( '; ', $triggers ) . '. The rules require a face-to-face assessment before prescribing.';
		}

		if ( $meds === '10-plus' ) {
			$f['s1a_polypharmacy'] = 'Rule S1A: 10 or more regular medicines reported. GP liaison before starting (NICE NG56 1.3.5).';
		} elseif ( $meds === 'unsure' ) {
			$f['s1a_polypharmacy'] = 'Rule S1A: patient not sure how many regular medicines they take. Count from the SCR; 10 or more means GP liaison before starting.';
		}

		if ( $bp !== 'no' ) {
			$f['s1a_bp_water'] = 'Rule S1A: blood pressure or water tablets ' . ( $bp === 'yes' ? 'reported' : 'possible (patient not sure)' ) . '. Advise on dizziness and dehydration (Foundayo: hypotension warning).';
		}

		if ( $kidney === 'within-12m' ) {
			$f['s1a_egfr'] = 'Rule S1A: kidney test in the last 12 months reported'
				. ( $egfr_date ? ' (' . $egfr_date . ')' : '' )
				. ( $egfr !== null ? ', eGFR ' . $egfr : ', result not known' )
				. '. See the eGFR result in a clinical record before the first supply.';
		} else {
			$f['s1a_egfr'] = 'Rule S1A: no kidney test in the last 12 months reported. GP blood test (eGFR) first, before the first supply.'
				. ( $egfr !== null ? ' Older result given: eGFR ' . $egfr . ( $egfr_date ? ' (' . $egfr_date . ')' : '' ) . '.' : '' );
		}

		if ( (int) $age === self::MAX_AGE ) {
			$f['s1_age_85'] = 'Rule S1: age 85. Record that the licence data are limited from age 85 and that this was discussed with the patient.';
		}

		if ( trim( (string) ( $payload['gpName'] ?? '' ) ) === '' ) {
			$f['s1a_no_gp_name'] = 'Rule S1A: no GP surgery given. Red flag: the prescriber decides after an individual risk-based assessment, giving particular weight to the lack of GP oversight (GPhC 4.2 k).';
		}

		$out['flags'] = $f;
		return $out;
	}

	/**
	 * Rule S1A answers as label => value rows, for the clinician email and
	 * the order screen. Empty when the patient was not asked.
	 */
	public static function s1a_summary( array $payload ) {
		if ( empty( $payload['s1aFalls'] ) && empty( $payload['s1aKidneyTest'] ) ) {
			return [];
		}
		$yn     = function ( $v ) {
			return [ 'yes' => 'Yes', 'no' => 'No', 'unsure' => 'Not sure' ][ (string) $v ] ?? 'Not answered';
		};
		$prisma = (array) ( $payload['s1aPrisma'] ?? [] );
		$rows   = [];
		$rows['Falls in the last 12 months'] = $yn( $payload['s1aFalls'] ?? '' );
		$rows['Any fragility fracture']      = $yn( $payload['s1aFracture'] ?? '' );
		$rows['Number of regular medicines'] = self::S1A_MEDS_COUNT[ (string) ( $payload['s1aMedsCount'] ?? '' ) ] ?? 'Not answered';
		$rows['Blood pressure tablets or water tablets'] = $yn( $payload['s1aBpWater'] ?? '' );
		$rows['Most recent kidney blood test (eGFR)']    = self::S1A_KIDNEY_TEST[ (string) ( $payload['s1aKidneyTest'] ?? '' ) ] ?? 'Not answered';
		$egfr_date = self::egfr_date( $payload['s1aEgfrDate'] ?? '' );
		$egfr      = self::egfr_value( $payload['s1aEgfrResult'] ?? '' );
		$rows['eGFR test date (month)'] = $egfr_date ?: 'Not known';
		$rows['eGFR result']            = ( $egfr !== null ) ? (string) $egfr : 'Not known';
		$rows['PRISMA-7 Q1: older than 85'] = 'No (from date of birth)';
		$rows['PRISMA-7 Q2: male']          = ( ( $payload['sex'] ?? '' ) === 'male' ) ? 'Yes' : 'No';
		$q = 3;
		foreach ( self::PRISMA_ITEMS as $key => $label ) {
			$rows[ 'PRISMA-7 Q' . $q . ': ' . $label ] = $yn( $prisma[ $key ] ?? '' );
			$q++;
		}
		if ( isset( $payload['s1aPrismaScore'] ) && $payload['s1aPrismaScore'] !== '' && $payload['s1aPrismaScore'] !== null ) {
			$rows['PRISMA-7 score (yes answers; 3 or more: face-to-face)'] = (string) (int) $payload['s1aPrismaScore'];
		}
		return $rows;
	}

	/** eGFR as an integer 1 to 200, or null when blank or invalid. */
	public static function egfr_value( $v ) {
		$v = trim( (string) $v );
		if ( $v === '' || ! is_numeric( $v ) ) {
			return null;
		}
		$n = (float) $v;
		// Keep one decimal place so 29.5 to 29.9 stays below 30 (exclusion E11).
		return ( $n >= 1 && $n <= 200 ) ? round( $n, 1 ) : null;
	}

	/** eGFR test month as YYYY-MM, not in the future, or '' when invalid. */
	public static function egfr_date( $v ) {
		$v = (string) $v;
		if ( ! preg_match( '/^(\d{4})-(\d{2})$/', $v, $m ) || (int) $m[2] < 1 || (int) $m[2] > 12 || (int) $m[1] < 1990 ) {
			return '';
		}
		return ( $v <= gmdate( 'Y-m' ) ) ? $v : '';
	}

	public static function bmi( $weight_kg, $height_cm ) {
		$raw = self::bmi_raw( $weight_kg, $height_cm );
		return ( $raw === null ) ? null : round( $raw, 1 );
	}

	/** Unrounded BMI, used for threshold comparisons. */
	public static function bmi_raw( $weight_kg, $height_cm ) {
		$w = (float) $weight_kg;
		$h = (float) $height_cm;
		if ( $w < 30 || $w > 350 || $h < 120 || $h > 230 ) {
			return null;
		}
		return $w / pow( $h / 100, 2 );
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
		// Product-specific groups are always scanned: the prescriber may
		// choose a different product at consultation. The note names the
		// product it applies to.
		foreach ( self::MEDICINE_FLAGS as $key => $group ) {
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

	private static function ineligible( array $base, $reason ) {
		$base['eligible'] = false;
		$base['reason']   = $reason;
		$base['flags']    = [];
		return $base;
	}
}
