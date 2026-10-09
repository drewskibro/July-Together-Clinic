<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side triage rules for the weight-management questionnaire.
 *
 * Source of truth: AT Health IP-FRM-01 section 3A, rules version
 * WM-2026-10-v3 (Superintendent Pharmacist): the GLP-1 rules (v2) plus
 * section O for Orlistat 120 mg (draft 9 Oct 2026). The questionnaire is TRIAGE
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
 *  - Orlistat (section O) has its own rules: BMI 30, or 28 to 29.9 with a
 *    condition (O2.2); no dose ladder, switching table or GLP-1 transfer
 *    window, and a move to or from a GLP-1 is a new start (O2.3); a
 *    transfer from Orlistat elsewhere needs a starting BMI meeting O2.2 and
 *    a current BMI of 20 or above (O2.4); blocks OE1 to OE11; red flags OF1
 *    to OF15; GLP-1-only exclusions become information flags (OF16); no
 *    contraception agreement (pregnancy and planning still block).
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

	const RULES_VERSION = 'WM-2026-10-v3';

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

	/**
	 * Orlistat 120 mg (rules section O, WM-2026-10-v3). Not a GLP-1: its own
	 * BMI rule (O2.2), no dose ladder or switching rules (O2.3), its own
	 * blocks (OE1 to OE11) and prescriber flags (OF1 to OF16).
	 */
	const ORLISTAT = 'orlistat';
	/** Rule O2.2 (Xenical SmPC 4.1): 30, or 28 to 29.9 with a list A/B condition. */
	const ORLISTAT_BMI                = 30;
	const ORLISTAT_BMI_WITH_CONDITION = 28;
	/** Rule O2.4 / OE8: current BMI floor for a transfer and for repeat supplies. */
	const ORLISTAT_TRANSFER_FLOOR = 20;

	/**
	 * Conditions that block Orlistat: OE5 malabsorption, OE6 cholestasis,
	 * OE9 current or past eating disorder. Every other GLP-1 exclusion in
	 * section 5 of the main rules is an information flag (OF16).
	 */
	const ORLISTAT_BLOCK_CONDITIONS = [ 'chronic_malabsorption', 'cholestasis', 'eating_disorder' ];

	/** Weight-loss medicines other than GLP-1s and orlistat (OE7). */
	const ORLISTAT_OTHER_WEIGHTLOSS = [ 'mysimba', 'contrave', 'phentermine', 'qsymia', 'sibutramine', 'rimonabant' ];

	/**
	 * Orlistat red flags from the free-text medicines (and, where given,
	 * the free-text other conditions). Rule O3, flags OF1 to OF15.
	 */
	const ORLISTAT_FLAGS = [
		// OF2 acarbose stays a flag pending the Superintendent's decision
		// (the checker suggested a block).
		'of1_ciclosporin'    => [ 'code' => 'OF1', 'words' => [ 'ciclosporin', 'cyclosporin', 'cyclosporine', 'neoral', 'sandimmun', 'capimune', 'deximune', 'vanquoral' ], 'note' => 'Ciclosporin: combination not recommended; if unavoidable, monitor ciclosporin levels when orlistat starts and stops.', 'basis' => 'Xenical SmPC 4.4, 4.5; Deltera PGD: exclusion, refer to GP' ],
		'of2_acarbose'       => [ 'code' => 'OF2', 'words' => [ 'acarbose', 'glucobay' ], 'note' => 'Acarbose: avoid the combination.', 'basis' => 'Xenical SmPC 4.5: should be avoided; Deltera PGD: exclusion, refer to GP' ],
		'of3_amiodarone'     => [ 'code' => 'OF3', 'words' => [ 'amiodarone', 'cordarone' ], 'note' => 'Amiodarone: reinforce clinical and ECG monitoring.', 'basis' => 'Xenical SmPC 4.5; Deltera PGD: exclusion, refer to GP' ],
		'of4_anticoagulant'  => [ 'code' => 'OF4', 'words' => [ 'warfarin', 'acenocoumarol', 'phenindione', 'apixaban', 'eliquis', 'rivaroxaban', 'xarelto', 'edoxaban', 'lixiana', 'dabigatran', 'pradaxa', 'anticoagulant', 'blood thinner', 'blood thinners' ], 'note' => 'Oral anticoagulant: monitor INR (warfarin); tell the INR or anticoagulation clinic.', 'basis' => 'Xenical SmPC 4.4, 4.5; Deltera PGD' ],
		'of5_thyroid'        => [ 'code' => 'OF5', 'words' => [ 'levothyroxine', 'thyroxine', 'liothyronine', 'eltroxin', 'iodine', 'iodide' ], 'note' => 'Levothyroxine or iodine: risk of hypothyroidism or poorer control; separate doses and monitor.', 'basis' => 'Xenical SmPC 4.4, 4.5' ],
		'of6_antiepileptic'  => [ 'code' => 'OF6', 'words' => [ 'valproate', 'valproic', 'epilim', 'depakote', 'dyzantil', 'lamotrigine', 'lamictal', 'levetiracetam', 'keppra', 'carbamazepine', 'tegretol', 'oxcarbazepine', 'trileptal', 'eslicarbazepine', 'phenytoin', 'epanutin', 'phenobarbital', 'phenobarbitone', 'primidone', 'topiramate', 'topamax', 'zonisamide', 'lacosamide', 'vimpat', 'brivaracetam', 'perampanel', 'clobazam', 'ethosuximide', 'gabapentin', 'pregabalin', 'rufinamide', 'cenobamate', 'epilepsy', 'anti-epileptic', 'antiepileptic' ], 'note' => 'Anti-epileptic medicine: fits reported with valproate and lamotrigine; monitor for changes in fit frequency or severity.', 'basis' => 'Xenical SmPC 4.4, 4.5; Deltera PGD refers' ],
		'of7_hiv'            => [ 'code' => 'OF7', 'words' => [ 'hiv', 'antiretroviral', 'antiretrovirals', 'tenofovir', 'emtricitabine', 'truvada', 'descovy', 'biktarvy', 'dolutegravir', 'tivicay', 'triumeq', 'dovato', 'juluca', 'raltegravir', 'isentress', 'elvitegravir', 'genvoya', 'stribild', 'darunavir', 'prezista', 'symtuza', 'rezolsta', 'atazanavir', 'ritonavir', 'norvir', 'lopinavir', 'kaletra', 'cobicistat', 'efavirenz', 'atripla', 'rilpivirine', 'odefsey', 'eviplera', 'edurant', 'nevirapine', 'abacavir', 'kivexa', 'lamivudine', 'zidovudine', 'doravirine', 'cabotegravir', 'vocabria' ], 'note' => 'HIV antiretroviral medicine: possible loss of virological control; liaise with the HIV clinic.', 'basis' => 'Xenical SmPC 4.4, 4.5; MHRA Drug Safety Update March 2014; Deltera PGD refers' ],
		'of8_psychiatric'    => [ 'code' => 'OF8', 'words' => [ 'sertraline', 'citalopram', 'escitalopram', 'fluoxetine', 'prozac', 'paroxetine', 'fluvoxamine', 'venlafaxine', 'desvenlafaxine', 'duloxetine', 'mirtazapine', 'amitriptyline', 'nortriptyline', 'clomipramine', 'imipramine', 'dosulepin', 'lofepramine', 'doxepin', 'trimipramine', 'trazodone', 'bupropion', 'vortioxetine', 'agomelatine', 'reboxetine', 'phenelzine', 'moclobemide', 'tranylcypromine', 'isocarboxazid', 'olanzapine', 'quetiapine', 'risperidone', 'aripiprazole', 'abilify', 'haloperidol', 'clozapine', 'lurasidone', 'paliperidone', 'amisulpride', 'sulpiride', 'chlorpromazine', 'flupentixol', 'zuclopenthixol', 'cariprazine', 'lithium', 'priadel', 'camcolit', 'liskonum', 'diazepam', 'lorazepam', 'temazepam', 'clonazepam', 'alprazolam', 'chlordiazepoxide', 'nitrazepam', 'oxazepam', 'loprazolam', 'lormetazepam', 'antidepressant', 'antidepressants', 'antipsychotic', 'antipsychotics', 'benzodiazepine', 'benzodiazepines' ], 'note' => 'Antidepressant, antipsychotic (including lithium) or benzodiazepine: case reports of reduced effect; start orlistat only after careful consideration and monitor.', 'basis' => 'Xenical SmPC 4.5' ],
		'of9_diabetes'       => [ 'code' => 'OF9', 'words' => [ 'metformin', 'glucophage', 'gliclazide', 'glimepiride', 'glipizide', 'glibenclamide', 'tolbutamide', 'sitagliptin', 'januvia', 'janumet', 'linagliptin', 'trajenta', 'jentadueto', 'alogliptin', 'saxagliptin', 'vildagliptin', 'empagliflozin', 'jardiance', 'synjardy', 'dapagliflozin', 'forxiga', 'xigduo', 'canagliflozin', 'invokana', 'ertugliflozin', 'pioglitazone', 'repaglinide', 'nateglinide', 'insulin', 'novorapid', 'humalog', 'lantus', 'levemir', 'tresiba', 'toujeo', 'abasaglar', 'humulin', 'fiasp', 'lyumjev', 'semglee' ], 'note' => 'Diabetes medicine: may need adjusting as weight falls (hypoglycaemia risk); ensure diabetes monitoring is current.', 'basis' => 'Xenical SmPC 4.4; Deltera PGD' ],
		'of10_bp'            => [ 'code' => 'OF10', 'words' => [ 'amlodipine', 'felodipine', 'nifedipine', 'lercanidipine', 'verapamil', 'diltiazem', 'ramipril', 'lisinopril', 'perindopril', 'enalapril', 'losartan', 'candesartan', 'irbesartan', 'valsartan', 'olmesartan', 'telmisartan', 'sacubitril', 'entresto', 'bisoprolol', 'atenolol', 'propranolol', 'metoprolol', 'nebivolol', 'labetalol', 'carvedilol', 'indapamide', 'bendroflumethiazide', 'hydrochlorothiazide', 'chlortalidone', 'furosemide', 'bumetanide', 'spironolactone', 'eplerenone', 'doxazosin', 'prazosin', 'terazosin', 'clonidine', 'moxonidine', 'hydralazine', 'minoxidil', 'blood pressure', 'atorvastatin', 'simvastatin', 'rosuvastatin', 'pravastatin', 'fluvastatin', 'lipitor', 'crestor', 'statin', 'statins', 'ezetimibe', 'ezetrol', 'inegy', 'fenofibrate', 'bezafibrate', 'gemfibrozil', 'bempedoic', 'nustendi', 'nilemdo', 'evolocumab', 'repatha', 'alirocumab', 'praluent', 'inclisiran', 'leqvio', 'icosapent', 'vazkepa', 'cholesterol' ], 'note' => 'Blood pressure or cholesterol medicine: ensure blood pressure and cholesterol monitoring is current; doses may need review as weight falls.', 'basis' => 'Deltera PGD' ],
		'of11_kidney'        => [ 'code' => 'OF11', 'words' => [ 'furosemide', 'bumetanide', 'bendroflumethiazide', 'indapamide', 'diuretic', 'diuretics', 'water tablet', 'water tablets' ], 'condition_words' => [ 'kidney', 'kidneys', 'renal', 'ckd', 'nephropathy', 'dialysis', 'dehydration', 'dehydrated' ], 'note' => 'Chronic kidney disease or risk of dehydration (including diuretics): risk of oxalate kidney injury; check renal function and hydration advice.', 'basis' => 'Xenical SmPC 4.4; BNF caution' ],
		'of12_liver'         => [ 'code' => 'OF12', 'words' => [], 'condition_words' => [ 'liver', 'hepatitis', 'cirrhosis', 'hepatic', 'masld', 'nafld' ], 'note' => 'Liver disease other than cholestasis: not studied in hepatic impairment; prescriber review.', 'basis' => 'Deltera PGD refers; Xenical SmPC 4.2' ],
		'of15_pill'          => [ 'code' => 'OF15', 'words' => [ 'contraceptive pill', 'the pill', 'combined pill', 'progestogen-only pill', 'mini pill', 'minipill', 'microgynon', 'rigevidon', 'ovranette', 'levest', 'cilest', 'lizinna', 'yasmin', 'lucette', 'eloine', 'marvelon', 'gedarel', 'femodene', 'femodette', 'katya', 'millinette', 'sunya', 'logynon', 'qlaira', 'zoely', 'brevinor', 'norimin', 'loestrin', 'cerazette', 'cerelle', 'desogestrel', 'feanolla', 'lovima', 'zelleta', 'slynd', 'noriday', 'norgeston' ], 'note' => 'Oral contraceptive pill: counsel to use an extra method if severe diarrhoea occurs.', 'basis' => 'Xenical SmPC 4.4, 4.5' ],
	];

	public static function evaluate( array $payload ) {
		$flags     = [];
		$treatment = self::treatment_of( $payload );
		$is_orl    = ( $treatment === self::ORLISTAT );
		$switching = ( ( $payload['userType'] ?? '' ) === 'switching' );
		$from      = class_exists( 'TC_Variation_Map' )
			? TC_Variation_Map::normalize_treatment( (string) ( $payload['currentMedication'] ?? '' ) )
			: strtolower( trim( (string) ( $payload['currentMedication'] ?? '' ) ) );
		$base      = [
			'eligible'      => false,
			'reason'        => '',
			'bmi'           => 0.0,
			'start_bmi'     => 0.0,
			'pathway'       => $switching ? 'transfer' : 'new',
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

		// Rule S1 (and O2.1 for Orlistat) is applied to the date of birth,
		// which is required. Under 18 is exclusion OE1 for Orlistat.
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
		if ( $is_orl && $is_s1a ) {
			$flags['orl_of14_age'] = self::orl_note( 'OF14', sprintf( 'Age %d. Outside the Deltera PGD age range (18 to 75); not studied in the elderly; independent prescriber decision, record reasons. Rule S1A checks apply.', $dob_age ), 'Rule O2.1; Xenical SmPC 4.2' );
		}

		// Pregnancy, planning pregnancy and breastfeeding block every product
		// (E1; Orlistat OE3 and OE4).
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
				return self::ineligible( $base, 'Our service does not prescribe weight loss medicines during pregnancy, while planning a pregnancy or while breastfeeding. Please speak with your GP.' );
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

		$not_suitable = 'Our online service cannot offer weight loss medication. Please speak with your GP.';

		// GLP-1 exclusions that are not Orlistat exclusions are carried for
		// Orlistat as information flags (OF16); the prescriber decides.
		$orl_info = [];

		$diabetes = (string) ( $payload['diabetes'] ?? '' );
		if ( $diabetes === 'type1' ) {
			if ( ! $is_orl ) {
				return self::ineligible( $base, 'Based on your answers, our online weight loss service is not suitable for you. Please speak with your GP or diabetes team about the options available to you.' );
			}
			$orl_info[] = 'type 1 diabetes';
		}

		$conditions = (array) ( $payload['conditions'] ?? [] );
		foreach ( $conditions as $condition ) {
			$key = self::condition_key( $condition );
			if ( $key === '' ) {
				continue;
			}
			if ( ! $is_orl || in_array( $key, self::ORLISTAT_BLOCK_CONDITIONS, true ) ) {
				// Orlistat: OE5 malabsorption, OE6 cholestasis, OE9 eating
				// disorder.
				return self::ineligible( $base, $not_suitable );
			}
			$orl_info[] = self::DISQUALIFYING_CONDITIONS[ $key ];
			if ( $key === 'kidney_disease' || $key === 'kidney_disease_legacy' ) {
				$flags['orl_of11_kidney_condition'] = self::orl_note( 'OF11', 'Severe kidney disease or kidney failure reported. Chronic kidney disease: risk of oxalate kidney injury; check renal function and hydration.', 'Xenical SmPC 4.4; BNF caution' );
			}
			if ( $key === 'liver_disease' ) {
				$flags['orl_of12_liver_condition'] = self::orl_note( 'OF12', 'Severe liver disease reported (not cholestasis). Not studied in hepatic impairment.', 'Deltera PGD refers; Xenical SmPC 4.2' );
			}
		}

		$has_bariatric = self::list_contains( $conditions, 'bariatric' );
		$bariatric_recent = ( $payload['bariatricRecent'] ?? '' ) === 'yes';
		if ( $has_bariatric && $is_orl && ! $bariatric_recent ) {
			// OF13 (6 months to 2 years ago). Within 6 months is OE11 below.
			$flags['orl_of13_bariatric'] = self::orl_note( 'OF13', 'Bariatric surgery more than 6 months ago. The questionnaire asks about 6 months only: confirm the date; within 2 years needs a recorded reason to proceed. If the procedure was malabsorptive, check against OE5 (chronic malabsorption, a block).', 'Deltera PGD refers ("recent"); 2-year window is AT Health policy; Xenical SmPC 4.3' );
		} elseif ( $has_bariatric && $bariatric_recent ) {
			// E15; Orlistat OE11.
			return self::ineligible( $base, 'Weight loss medication is not suitable within 6 months of bariatric surgery.' );
		} elseif ( $has_bariatric ) {
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
			$contraception = (string) ( $payload['contraception'] ?? '' );
			if ( $is_orl ) {
				// Rule O3: the GLP-1 contraception agreement is not required
				// for Orlistat. Pregnancy and planning still block (OE4).
				if ( $contraception === 'pill' ) {
					$flags['orl_of15_pill'] = self::orl_note( 'OF15', 'Oral contraceptive pill. Counsel: use an extra method if severe diarrhoea occurs.', 'Xenical SmPC 4.4, 4.5' );
				} elseif ( $contraception === 'none' || $contraception === '' ) {
					$flags['orl_contraception_none'] = 'Orlistat: could become pregnant and reports no contraception. No contraception agreement is required for Orlistat (rule O3); counsel to stop and tell us at once if pregnant or planning pregnancy (OE4).';
				}
			} else {
				if ( empty( $payload['consentContraception'] ) ) {
					return self::ineligible( $base, 'These medicines can only be prescribed if you agree to use effective contraception during treatment.' );
				}
				if ( $contraception === 'none' ) {
					$flags['contraception_none'] = 'Could become pregnant and reports no contraception: counsel and confirm effective method before prescribing (Mounjaro SmPC: not recommended without contraception).';
				}
				if ( $contraception === 'pill' ) {
					$flags['contraception_pill'] = 'Oral contraceptive: if Mounjaro, non-oral method or barrier for 4 weeks after starting and each increase; if Foundayo, the same for 30 days.';
				}
			}
		}

		$comorbidity = self::comorbidity( $payload );
		if ( $comorbidity['a'] ) {
			$flags['comorbidity_a'] = 'Weight-related condition reported (SmPC-named): ' . implode( ', ', $comorbidity['a'] ) . '. Verify on SCR if relied on.';
		}
		if ( $comorbidity['b'] ) {
			$flags['comorbidity_b'] = 'Condition needing prescriber judgement: ' . implode( ', ', $comorbidity['b'] ) . '. Record clinical link to weight if relied on.';
		}
		$has_condition = $comorbidity['a'] || $comorbidity['b'];

		$not_licensed = 'Based on your answers, the weight loss medicines we offer are not licensed for you at the moment. Please speak with your GP, who can discuss other support.';

		if ( $is_orl ) {
			$result = self::orlistat_pathway( $payload, $base, $flags, $bmi, $bmi_raw, $has_condition, $switching, $from );
			if ( $result['reason'] !== '' ) {
				return self::ineligible( $base, $result['reason'] );
			}
			$base  = $result['base'];
			$flags = $result['flags'];
		} else {
			// Rule O2.3: a move from Orlistat to a GLP-1 is a new start on the
			// GLP-1, never a GLP-1 transfer.
			if ( $switching && $from === self::ORLISTAT ) {
				$base['pathway']         = 'new';
				$flags['from_orlistat']  = 'Moving from Orlistat to a GLP-1: assessed as a new start on current BMI, first step of the ladder (rule O2.3). Confirm Orlistat has stopped: never supplied together (rule T5 extended, OE7).'
					. ( ! empty( $payload['lastDoseDate'] ) ? ' Last Orlistat dose ' . sanitize_text_field( (string) $payload['lastDoseDate'] ) . '.' : '' );
			}

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
					if ( ! $has_condition ) {
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
				if ( $start_bmi_raw < 27 || ( $start_bmi_raw < 30 && ! $has_condition ) ) {
					return self::ineligible( $base, $not_licensed );
				}
				$flags['proof_required'] = sprintf( 'Transfer: declared starting BMI %.1f. Before prescribing, see evidence from a UK-registered prescriber or pharmacy of: BMI when first starting a GLP-1, product, current dose and date of last supply. If dose or last-dose date cannot be evidenced, start at the first step. Unregulated sources (research peptides, unlicensed or overseas products) are not accepted: assess as a new patient.', $start_bmi );
				$flags['last_dose']      = sprintf( 'Last dose %s (%d days ago).', sanitize_text_field( (string) $payload['lastDoseDate'] ), $days );
			}
		}

		if ( $is_orl ) {
			$orl = self::orlistat_medicine_check( $payload, $switching, $from );
			if ( $orl['block'] !== '' ) {
				return self::ineligible( $base, $orl['block'] );
			}
			$flags = array_merge( $flags, $orl['flags'] );
		}
		foreach ( self::medicine_flags( $payload ) as $key => $note ) {
			if ( $is_orl && in_array( $key, [ 'glp1', 'other_weightloss' ], true ) ) {
				continue; // Handled by OE7 for Orlistat.
			}
			$flags[ 'med_' . $key ] = ( $is_orl ? 'GLP-1 products only, not an Orlistat rule (for the prescriber if another product is considered): ' : '' ) . $note;
		}

		if ( $is_s1a ) {
			$s1a = self::s1a_check( $payload, $dob_age );
			if ( $s1a['missing'] ) {
				return self::ineligible( $base, 'Please answer all the extra questions for people aged 75 and over.' );
			}
			if ( $s1a['exclude'] ) {
				if ( ! $is_orl ) {
					// Exclusion E11: eGFR below 30.
					return self::ineligible( $base, $not_suitable );
				}
				$orl_info[]               = 'eGFR below 30 (exclusion E11 for GLP-1 products)';
				$flags['orl_of11_egfr'] = self::orl_note( 'OF11', 'Reported eGFR ' . self::egfr_value( $payload['s1aEgfrResult'] ?? '' ) . ' (below 30). Chronic kidney disease: risk of oxalate kidney injury; check renal function and hydration.', 'Xenical SmPC 4.4; BNF caution' );
			}
			$base['prisma7_score'] = $s1a['prisma7_score'];
			$flags                 = array_merge( $flags, $s1a['flags'] );
		}

		if ( $is_orl ) {
			if ( $orl_info ) {
				$flags['orl_of16_info'] = 'INFORMATION OF16 (not an Orlistat exclusion; the prescriber decides): ' . implode( '; ', array_unique( $orl_info ) ) . '. These are GLP-1 exclusions (main rules section 5), not Orlistat exclusions. Basis: rule O3 (OF16).';
			}
			$flags['orl_counselling'] = 'Orlistat (rules O2 to O5): no dose ladder; contraception agreement not required. Counsel at consultation: about 30% of calories from fat over three main meals; skip the dose if a meal is missed or has no fat; extra contraception if severe diarrhoea on the pill; multivitamin optional, at bedtime or 2 hours after a dose; investigate severe or persistent rectal bleeding. Stop if under 5% weight loss at 12 weeks. Basis: Xenical SmPC 4.1, 4.2, 4.4, 4.5.';
		}

		$flags['triage_only'] = 'Triage passed (rules ' . self::RULES_VERSION . '). Video consultation, photo ID, SCR check and independent weight/height verification required before prescribing.';

		$base['eligible'] = true;
		$base['flags']    = $flags;
		return $base;
	}

	/**
	 * Orlistat start and transfer rules (rules O2.2 to O2.4). No dose ladder,
	 * no switching table and no GLP-1 "last dose within 3 months" logic.
	 *
	 * @return array { reason: string, base: array, flags: array }
	 */
	private static function orlistat_pathway( array $payload, array $base, array $flags, $bmi, $bmi_raw, $has_condition, $switching, $from ) {
		$fail = function ( $reason ) use ( $base, $flags ) {
			return [ 'reason' => $reason, 'base' => $base, 'flags' => $flags ];
		};
		$not_licensed = 'Based on your answers, Orlistat is not licensed for you at the moment. You can go back and choose another treatment, or speak with your GP, who can discuss other support.';

		// Rule O2.2 (licence, Xenical SmPC 4.1): BMI 30 or above, or 28 to
		// 29.9 with a list A or B condition. Unrounded BMI, as for GLP-1.
		$passes_new = $bmi_raw >= self::ORLISTAT_BMI || ( $bmi_raw >= self::ORLISTAT_BMI_WITH_CONDITION && $has_condition );

		$days = null;
		if ( $switching ) {
			$days = self::days_since( $payload['lastDoseDate'] ?? '' );
			if ( $days === null ) {
				return $fail( 'Please tell us the date of your last dose.' );
			}
		}

		if ( $switching && $from === self::ORLISTAT ) {
			// Rule O2.4: already on Orlistat elsewhere. Accepted as a transfer
			// with evidence of a starting BMI meeting O2.2 and a current BMI of
			// 20 or above; otherwise assessed as a new start.
			$start_bmi     = self::bmi( $payload['startWeightKg'] ?? 0, $payload['heightCm'] ?? 0 );
			$start_bmi_raw = self::bmi_raw( $payload['startWeightKg'] ?? 0, $payload['heightCm'] ?? 0 );
			$start_ok      = $start_bmi_raw !== null
				&& ( $start_bmi_raw >= self::ORLISTAT_BMI || ( $start_bmi_raw >= self::ORLISTAT_BMI_WITH_CONDITION && $has_condition ) );
			if ( $start_bmi !== null ) {
				$base['start_bmi'] = $start_bmi;
			}
			if ( $start_ok && $bmi_raw >= self::ORLISTAT_TRANSFER_FLOOR ) {
				$base['pathway']         = 'transfer';
				$flags['proof_required'] = sprintf( 'Orlistat transfer (rule O2.4): declared starting BMI %.1f, current BMI %.1f. Before prescribing, see evidence from a UK-registered prescriber or pharmacy of: BMI when first starting Orlistat (30, or 28 with a risk factor), product, and date of last supply. Unregulated sources are not accepted: assess as a new start.', $start_bmi, $bmi );
				$flags['last_dose']      = sprintf( 'Last Orlistat dose %s (%d days ago). Recorded only: no dose ladder or restart window for Orlistat (rule O2.3).', sanitize_text_field( (string) $payload['lastDoseDate'] ), $days );
				// Rule O2.4: on orlistat 12 weeks or more needs evidence of at
				// least 5% loss at 12 weeks, or it is not continued (licence
				// 4.1). Duration is not asked, so the prescriber checks it.
				$loss = self::percent_loss( $payload['startWeightKg'] ?? 0, $payload['weightKg'] ?? 0 );
				$flags['orl_transfer_12_week'] = self::orl_note( 'O2.4', sprintf( 'If the patient has taken orlistat for 12 weeks or more, see evidence of at least 5%% loss from the starting weight at 12 weeks; without it, orlistat is not continued. Declared loss so far: %s.', $loss === null ? 'not known' : sprintf( '%.1f%%', $loss ) ) . ( ( $loss !== null && $loss < 5 ) ? ' Declared loss is under 5%.' : '' ), 'Xenical SmPC 4.1; rule O2.4' );
				return [ 'reason' => '', 'base' => $base, 'flags' => $flags ];
			}
			if ( ! $passes_new ) {
				return $fail( $not_licensed );
			}
			$base['pathway']        = 'new';
			$flags['orl_transfer_as_new'] = sprintf( 'Already on Orlistat, but the declared starting BMI%s does not meet the transfer rule (rule O2.4): assessed as a new start on current BMI %.1f. Last dose %s.', $start_bmi !== null ? sprintf( ' %.1f', $start_bmi ) : ' (not given)', $bmi, sanitize_text_field( (string) $payload['lastDoseDate'] ) );
		} else {
			if ( ! $passes_new ) {
				return $fail( $not_licensed );
			}
			$base['pathway'] = 'new';
			if ( $switching ) {
				$label = class_exists( 'TC_Variation_Map' ) ? TC_Variation_Map::treatment_label( $from ) : $from;
				$flags['orl_new_start'] = sprintf( 'Moving from %s (last dose %s, %d days ago) to Orlistat: assessed as a new start on Orlistat (rule O2.3). No GLP-1 transfer or dose rules apply. RED FLAG OE7: confirm the previous supply has stopped; Orlistat is never supplied alongside a GLP-1 or another weight-loss medicine.', ( $from && $from !== 'other' ) ? $label : 'another weight-loss medicine', sanitize_text_field( (string) $payload['lastDoseDate'] ), $days );
			}
		}

		if ( $bmi_raw < self::ORLISTAT_BMI ) {
			$flags['pathway'] = sprintf( 'BMI %.1f (28 to 29.9): Orlistat only with the weight-related condition relied on, verified (rule O2.2; Xenical SmPC 4.1).', $bmi );
		}
		return [ 'reason' => '', 'base' => $base, 'flags' => $flags ];
	}

	/**
	 * Orlistat medicine and condition screen from the free-text lists (rule
	 * O3). Blocks: OE2 allergy, OE7 another weight-loss medicine. Every other
	 * match is a red prescriber flag (OF1 to OF13, OF15). The patient never
	 * sees these lists.
	 *
	 * @return array { block: string, flags: array }
	 */
	public static function orlistat_medicine_check( array $payload, $switching = false, $from = '' ) {
		$out       = [ 'block' => '', 'flags' => [] ];
		$meds      = (string) ( $payload['currentMedsList'] ?? '' );
		$allergies = (string) ( $payload['allergiesList'] ?? '' );
		$other     = (string) ( $payload['otherConditionsList'] ?? '' );

		// OE2: allergy to orlistat or any ingredient. Matches the active
		// ingredient and brand names in the free-text allergies (for example
		// "orlistat allergy", "allergic to Xenical"). Allergy to an excipient
		// (capsule ingredients) cannot be screened from free text: it is asked
		// at the consultation.
		if ( self::text_matches( $allergies, [ 'orlistat', 'xenical', 'alli', 'orlos' ] ) ) {
			$out['block'] = 'Based on the allergy you told us about, Orlistat is not suitable for you. Please speak with your GP.';
			return $out;
		}

		// OE7: another weight-loss medicine, including any GLP-1. A patient who
		// told us they are moving from a GLP-1 or another medicine is flagged
		// to confirm it has stopped; anyone else is screened out.
		$other_wl = self::text_matches( $meds, array_merge( self::MEDICINE_FLAGS['glp1']['words'], self::ORLISTAT_OTHER_WEIGHTLOSS ) );
		if ( $other_wl ) {
			if ( $switching && $from !== self::ORLISTAT ) {
				$out['flags']['orl_oe7_confirm_stopped'] = self::orl_note( 'OE7', 'Weight-loss medicine in current medicines (' . implode( ', ', $other_wl ) . '). Patient is moving to Orlistat: confirm the supply has stopped before the first Orlistat supply. If still taking it, do not prescribe.', 'Deltera PGD; AT Health policy (rule T5 extended)', 'BLOCK UNLESS STOPPED' );
			} else {
				$out['block'] = 'Based on your answers, Orlistat cannot be prescribed alongside another weight-loss medicine you are taking. Please speak with your GP.';
				return $out;
			}
		}
		$orl_words = self::text_matches( $meds, [ 'orlistat', 'xenical', 'alli', 'naltrexone' ] );
		if ( $orl_words ) {
			$out['flags']['orl_existing_orlistat'] = self::orl_note( 'OE7', 'Current medicines mention ' . implode( ', ', $orl_words ) . '. Orlistat from another source (including alli 60 mg) must stop: never two supplies; a current supply elsewhere is a transfer (rule O2.4). Naltrexone as Mysimba is another weight-loss medicine: confirm what it is for (flag; OE7 applies if it is Mysimba).', 'Rule O2.4; rule T5 extended', 'CHECK' );
		}

		foreach ( self::ORLISTAT_FLAGS as $key => $group ) {
			$hits = self::text_matches( $meds, $group['words'] );
			if ( ! $hits && ! empty( $group['condition_words'] ) ) {
				$hits = self::text_matches( $other, $group['condition_words'] );
			}
			if ( $hits ) {
				$out['flags'][ 'orl_' . $key ] = self::orl_note( $group['code'], $group['note'] . ' Mentioned: ' . implode( ', ', $hits ) . '.', $group['basis'] );
			}
		}

		// Structured answers that raise the same flags.
		if ( ( $payload['diabetes'] ?? '' ) === 'type2-meds' && empty( $out['flags']['orl_of9_diabetes'] ) ) {
			$out['flags']['orl_of9_diabetes'] = self::orl_note( 'OF9', self::ORLISTAT_FLAGS['of9_diabetes']['note'] . ' Patient reports type 2 diabetes treated with medication.', self::ORLISTAT_FLAGS['of9_diabetes']['basis'] );
		}
		if ( ( $payload['s1aBpWater'] ?? '' ) === 'yes' && empty( $out['flags']['orl_of10_bp'] ) ) {
			$out['flags']['orl_of10_bp'] = self::orl_note( 'OF10', self::ORLISTAT_FLAGS['of10_bp']['note'] . ' Patient reports blood pressure or water tablets.', self::ORLISTAT_FLAGS['of10_bp']['basis'] );
		}
		$egfr = self::egfr_value( $payload['s1aEgfrResult'] ?? '' );
		if ( $egfr !== null && $egfr < 60 && $egfr >= 30 ) {
			$out['flags']['orl_of11_egfr'] = self::orl_note( 'OF11', 'Reported eGFR ' . $egfr . ' (below 60). Chronic kidney disease: risk of oxalate kidney injury; check renal function and hydration.', 'Xenical SmPC 4.4; BNF caution' );
		}
		foreach ( (array) ( $payload['weightConditions'] ?? [] ) as $c ) {
			if ( stripos( (string) $c, 'fatty liver' ) !== false && empty( $out['flags']['orl_of12_liver'] ) ) {
				$out['flags']['orl_of12_liver'] = self::orl_note( 'OF12', 'Fatty liver disease reported (liver disease other than cholestasis). Not studied in hepatic impairment.', 'Deltera PGD refers; Xenical SmPC 4.2' );
			}
		}
		if ( ( $payload['contraception'] ?? '' ) === 'pill' && empty( $out['flags']['orl_of15_pill'] ) ) {
			$out['flags']['orl_of15_pill'] = self::orl_note( 'OF15', 'Oral contraceptive pill. Counsel: use an extra method if severe diarrhoea occurs.', 'Xenical SmPC 4.4, 4.5' );
		}
		return $out;
	}

	/**
	 * Words from $words found in $text as whole words, in list order.
	 *
	 * @return string[]
	 */
	public static function text_matches( $text, array $words ) {
		$text = ' ' . strtolower( (string) $text ) . ' ';
		if ( trim( $text ) === '' ) {
			return [];
		}
		$hits = [];
		foreach ( $words as $word ) {
			if ( preg_match( '/(?<![a-z])' . preg_quote( strtolower( $word ), '/' ) . '(?![a-z])/', $text ) ) {
				$hits[ $word ] = $word;
			}
		}
		return array_values( $hits );
	}

	/**
	 * Weight lost as a percentage of the starting weight (positive = loss),
	 * or null when either weight is missing or implausible.
	 */
	public static function percent_loss( $start_kg, $current_kg ) {
		$start   = (float) $start_kg;
		$current = (float) $current_kg;
		if ( $start < 30 || $start > 350 || $current < 30 || $current > 350 ) {
			return null;
		}
		return ( $start - $current ) / $start * 100;
	}

	/** Prescriber note for an Orlistat block or flag, with its basis. */
	private static function orl_note( $code, $text, $basis, $level = 'RED FLAG' ) {
		return sprintf( '%s %s (Orlistat): %s Basis: %s.', $level, $code, $text, $basis );
	}

	/** Selected treatment, normalised. */
	private static function treatment_of( array $payload ) {
		$t = (string) ( $payload['selectedTreatment'] ?? '' );
		return class_exists( 'TC_Variation_Map' ) ? TC_Variation_Map::normalize_treatment( $t ) : strtolower( trim( $t ) );
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

	/** eGFR as a number 1 to 200 (decimals kept), or null when blank or invalid. */
	public static function egfr_value( $v ) {
		$v = trim( (string) $v );
		if ( $v === '' || ! is_numeric( $v ) ) {
			return null;
		}
		$n = (float) $v;
		// No rounding, so any result below 30 (e.g. 29.6 or 29.95) stays below
		// 30 for exclusion E11.
		return ( $n >= 1 && $n <= 200 ) ? $n : null;
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
		return self::condition_key( $condition ) !== '';
	}

	/**
	 * Key in DISQUALIFYING_CONDITIONS matched by a condition answer, or ''.
	 * Each needle is tried in full first, so an exact phrase always wins over
	 * another needle's first-three-words match.
	 */
	public static function condition_key( $condition ) {
		$condition = strtolower( trim( (string) $condition ) );
		if ( $condition === '' || $condition === 'none of these apply' ) {
			return '';
		}

		foreach ( self::DISQUALIFYING_CONDITIONS as $key => $needle ) {
			if ( strpos( $condition, strtolower( $needle ) ) !== false ) {
				return $key;
			}
		}
		foreach ( self::DISQUALIFYING_CONDITIONS as $key => $needle ) {
			$first_words = implode( ' ', array_slice( explode( ' ', strtolower( $needle ) ), 0, 3 ) );
			if ( $first_words && strpos( $condition, $first_words ) !== false ) {
				return $key;
			}
		}

		return '';
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
