<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The single source of truth for dose titration.
 *
 * Every ladder consumer (variation map defaults, reorder pricing, wizard dose
 * pickers, the ±1 reorder gate, the switching-dose matrix) reads from here.
 * Step order comes from these explicit arrays — never derive it from the
 * filtered variation map at runtime, where a missing product would silently
 * change what "one step" means.
 *
 * Philosophy (BUILD-BRIEF-v3 §3): dose logic proposes and flags; it never
 * blocks. Out-of-band requests are clamped to the nearest safe dose and the
 * order is flagged in _tc_review_flags for the prescriber, who is the
 * clinical backstop for every order.
 */
class TC_Dose_Ladder {

	const LADDERS = [
		'wegovy'         => [ '0.25mg', '0.5mg', '1mg', '1.7mg', '2.4mg' ],
		'mounjaro'       => [ '2.5mg', '5mg', '7.5mg', '10mg', '12.5mg', '15mg' ],
		// Oral semaglutide — a DISTINCT treatment from injectable Wegovy (same
		// molecule, different form, different titration), with its own id and
		// ladder. Never an alias of 'wegovy' (see the molecule-collision note in
		// TC_Variation_Map). Titration order confirmed with the prescriber.
		'wegovy-tablets' => [ '1.5mg', '4mg', '9mg', '25mg' ],
		// Orforglipron (Foundayo) — a non-peptide GLP-1, MHRA-licensed Aug 2026.
		// A DISTINCT molecule from both semaglutide products; shares no name
		// fragment with them, but the same exact-match identity rule applies.
		// Six licensed strengths, minimum 30 days at each before an increase.
		'foundayo'       => [ '0.8mg', '2.5mg', '5.5mg', '9mg', '14.5mg', '17.2mg' ],
	];

	/**
	 * Statuses that anchor the ±1 reorder gate: paid orders only. Deliberately
	 * narrower than the prefill's qualifying set (which includes on-hold for
	 * convenience) — an unapproved or unpaid order must never raise a
	 * patient's dose ceiling.
	 */
	const PAID_BASELINE_STATUSES = [ 'processing', 'completed' ];

	public static function ladders() {
		return apply_filters( 'tc_dose_ladders', self::LADDERS );
	}

	public static function ladder( $treatment ) {
		$treatment = TC_Variation_Map::normalize_treatment( $treatment );
		$ladders   = self::ladders();
		return isset( $ladders[ $treatment ] ) ? array_values( $ladders[ $treatment ] ) : [];
	}

	public static function index_of( $treatment, $dose ) {
		$dose  = TC_Variation_Map::normalize_dose( $dose );
		$index = array_search( $dose, self::ladder( $treatment ), true );
		return ( $index === false ) ? false : (int) $index;
	}

	public static function starter( $treatment ) {
		$ladder = self::ladder( $treatment );
		return $ladder ? $ladder[0] : '';
	}

	/**
	 * The dose $steps rungs away, or null past either end of the ladder.
	 */
	public static function step( $treatment, $dose, $steps ) {
		$ladder = self::ladder( $treatment );
		$index  = self::index_of( $treatment, $dose );
		if ( $index === false ) {
			return null;
		}
		$target = $index + (int) $steps;
		return ( $target >= 0 && $target < count( $ladder ) ) ? $ladder[ $target ] : null;
	}

	/**
	 * A dose is available when it maps to a real, purchasable product.
	 */
	public static function is_available( $treatment, $dose ) {
		$product_id = TC_Variation_Map::get_variation_id( $treatment, $dose );
		if ( ! $product_id ) {
			return false;
		}
		$product = wc_get_product( $product_id );
		return $product && $product->exists() && $product->get_price() !== '';
	}

	/**
	 * The reorder rule: current dose, one step up, one step down — clamped at
	 * the ladder ends. A mid-ladder dose with no purchasable product is
	 * skipped to the next available rung in the same direction, and the skip
	 * is reported so the order can be flagged for the prescriber.
	 *
	 * @return array { doses: string[], skipped: string[] }
	 */
	public static function allowed_reorder_doses( $treatment, $current_dose ) {
		$allowed = [];
		$skipped = [];

		$index = self::index_of( $treatment, $current_dose );
		if ( $index === false ) {
			return [ 'doses' => [], 'skipped' => [] ];
		}

		if ( self::is_available( $treatment, $current_dose ) ) {
			$allowed[] = $current_dose;
		} else {
			$skipped[] = $current_dose;
		}

		foreach ( [ -1, 1 ] as $direction ) {
			$dose = self::step( $treatment, $current_dose, $direction );
			while ( $dose !== null && ! self::is_available( $treatment, $dose ) ) {
				$skipped[] = $dose;
				$dose      = self::step( $treatment, $dose, $direction );
			}
			if ( $dose !== null ) {
				$allowed[] = $dose;
			}
		}

		usort( $allowed, function ( $a, $b ) use ( $treatment ) {
			return self::index_of( $treatment, $a ) <=> self::index_of( $treatment, $b );
		} );

		return [ 'doses' => array_values( array_unique( $allowed ) ), 'skipped' => array_values( array_unique( $skipped ) ) ];
	}

	/**
	 * Nearest allowed dose to the request (by ladder distance; ties resolve
	 * to the lower dose — clamp conservatively).
	 */
	public static function clamp_to_allowed( $treatment, array $allowed_doses, $requested_dose ) {
		if ( empty( $allowed_doses ) ) {
			return '';
		}

		$requested_index = self::index_of( $treatment, $requested_dose );
		if ( $requested_index === false ) {
			return $allowed_doses[0];
		}

		$best          = '';
		$best_distance = PHP_INT_MAX;
		foreach ( $allowed_doses as $dose ) {
			$index    = self::index_of( $treatment, $dose );
			$distance = abs( $index - $requested_index );
			if ( $distance < $best_distance || ( $distance === $best_distance && $index < self::index_of( $treatment, $best ) ) ) {
				$best          = $dose;
				$best_distance = $distance;
			}
		}
		return $best;
	}

	/**
	 * The nearest purchasable dose to the given one (searching outward by
	 * ladder distance, preferring the lower dose on ties), or '' when the
	 * treatment has no purchasable dose at all. Used so a proposal whose
	 * exact rung is missing from the catalogue degrades to the closest safe
	 * dose (propose + flag) instead of failing the submission.
	 */
	public static function nearest_available( $treatment, $dose ) {
		if ( self::is_available( $treatment, $dose ) ) {
			return $dose;
		}

		$ladder = self::ladder( $treatment );
		$index  = self::index_of( $treatment, $dose );
		if ( $index === false ) {
			$index = 0;
		}

		for ( $distance = 1; $distance < count( $ladder ); $distance++ ) {
			foreach ( [ -1, 1 ] as $direction ) {
				$candidate_index = $index + ( $distance * $direction );
				if ( $candidate_index >= 0 && $candidate_index < count( $ladder )
					&& self::is_available( $treatment, $ladder[ $candidate_index ] ) ) {
					return $ladder[ $candidate_index ];
				}
			}
		}

		return '';
	}

	/**
	 * The switching-dose conversion matrix (BUILD-BRIEF-v3 §3). Ranges mean
	 * the system supplies the conservative (lower) end and the prescriber
	 * confirms or adjusts before the patient pays.
	 *
	 * @return array { dose: string, range: string|null, rule: string }
	 */
	/**
	 * Starting dose for a patient already on a GLP-1 (IP-FRM-01 3A.3, rules
	 * WM-2026-10-v1). Replaces the old cross-molecule conversion matrix, which
	 * put Mounjaro patients straight onto Wegovy 0.5/1.7 mg and Wegovy
	 * patients onto Mounjaro 5 mg: no licence supports that, and MHRA says
	 * potency differs between products.
	 *
	 * Same product:   continue / step down / restart by days since last dose:
	 *                 weekly products 14 / 15-28 / >28; Wegovy tablets 7 /
	 *                 8-28 / >28; Foundayo 3 / 4-7 / >7.
	 * Other product:  first step of the new product, at least 7 days after
	 *                 the last dose. Licensed exceptions (SmPC 4.2): Wegovy
	 *                 2.4 mg injection -> Wegovy tablets 25 mg; Wegovy
	 *                 tablets 25 mg -> Wegovy 2.4 mg injection.
	 *
	 * Propose and flag, never block: the prescriber confirms every dose.
	 *
	 * @param int|null $days_since_last Days since last dose; null if unknown.
	 */
	public static function propose_start_dose( $from_drug, $from_dose, $to_drug, $days_since_last = null ) {
		$from_drug = TC_Variation_Map::normalize_treatment( $from_drug );
		$to_drug   = TC_Variation_Map::normalize_treatment( $to_drug );
		$from_dose = TC_Variation_Map::normalize_dose( $from_dose );
		$starter   = self::starter( $to_drug );
		// Continue / step-down windows (AT Health policy, IP-FRM-01 3A.3).
		// Foundayo has a short half-life (about 29 to 49 hours, SmPC 5.2), so
		// its windows are shorter than the semaglutide tablet's.
		$windows   = [
			'wegovy'         => [ 14, 28 ],
			'mounjaro'       => [ 14, 28 ],
			'wegovy-tablets' => [ 7, 28 ],
			'foundayo'       => [ 3, 7 ],
		];
		list( $continue, $step_down ) = $windows[ $from_drug ] ?? [ 7, 28 ];

		if ( $days_since_last === null ) {
			return [ 'dose' => $starter, 'range' => null, 'rule' => 'last_dose_unknown' ];
		}
		$days = (int) $days_since_last;

		if ( $from_drug === $to_drug ) {
			if ( self::index_of( $to_drug, $from_dose ) === false ) {
				return [ 'dose' => $starter, 'range' => null, 'rule' => 'same_drug_unrecognised_dose' ];
			}
			if ( $days <= $continue ) {
				return [ 'dose' => $from_dose, 'range' => null, 'rule' => 'same_drug_continue' ];
			}
			if ( $days <= $step_down ) {
				$down = self::step( $to_drug, $from_dose, -1 );
				return [ 'dose' => $down ?: $starter, 'range' => null, 'rule' => 'same_drug_gap_step_down' ];
			}
			return [ 'dose' => $starter, 'range' => null, 'rule' => 'same_drug_gap_restart' ];
		}

		if ( $from_drug === 'wegovy' && $to_drug === 'wegovy-tablets' && $from_dose === '2.4mg' && $days <= 14 ) {
			return [ 'dose' => '25mg', 'range' => null, 'rule' => 'smpc_injection_to_tablets' ];
		}
		if ( $from_drug === 'wegovy-tablets' && $to_drug === 'wegovy' && $from_dose === '25mg' && $days <= 7 ) {
			return [ 'dose' => '2.4mg', 'range' => null, 'rule' => 'smpc_tablets_to_injection' ];
		}

		return [ 'dose' => $starter, 'range' => null, 'rule' => ( $from_drug ? 'switch_product_restart' : 'switch_unknown_source' ) ];
	}

	/**
	 * Prescriber-facing explanation of a proposal.
	 */
	public static function explain_rule( $rule, $days = null ) {
		$map = [
			'last_dose_unknown'            => 'Date of last dose not recognised: first step proposed. Confirm last dose before prescribing.',
			'same_drug_unrecognised_dose'  => 'Same product but declared dose not recognised: first step proposed.',
			'same_drug_continue'           => 'Same product, last dose within the continuation window: declared dose proposed. Confirm with proof of previous prescription.',
			'same_drug_gap_step_down'      => 'Same product, gap beyond the continuation window: one step down proposed (AT Health policy; Foundayo 4-7 days, others up to 28 days).',
			'same_drug_gap_restart'        => 'Same product, gap beyond the step-down window: restart at the first step (AT Health policy).',
			'smpc_injection_to_tablets'    => 'Wegovy 2.4 mg injection to Wegovy tablets 25 mg (SmPC 4.2): start one week after the last injection.',
			'smpc_tablets_to_injection'    => 'Wegovy tablets 25 mg to Wegovy 2.4 mg injection (SmPC 4.2): start the day after the last tablet.',
			'switch_product_restart'       => 'Change of product: first step of the new product, at least 7 days after the last dose of the previous product. No cross-molecule conversion (MHRA).',
			'switch_unknown_source'        => 'Previous medicine not one we supply: first step proposed, at least 7 days after the last dose.',
		];
		return $map[ $rule ] ?? 'Dose proposed; confirm before prescribing.';
	}
}
