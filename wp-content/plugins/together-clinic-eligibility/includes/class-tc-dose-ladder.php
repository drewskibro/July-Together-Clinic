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
		'wegovy'   => [ '0.25mg', '0.5mg', '1mg', '1.7mg', '2.4mg' ],
		'mounjaro' => [ '2.5mg', '5mg', '7.5mg', '10mg', '12.5mg', '15mg' ],
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
	public static function propose_start_dose( $from_drug, $from_dose, $to_drug ) {
		$from_drug = TC_Variation_Map::normalize_treatment( $from_drug );
		$to_drug   = TC_Variation_Map::normalize_treatment( $to_drug );
		$from_dose = TC_Variation_Map::normalize_dose( $from_dose );

		// Same drug, new provider: continue at the declared current dose.
		if ( $from_drug === $to_drug ) {
			if ( self::index_of( $to_drug, $from_dose ) !== false ) {
				return [
					'dose'  => $from_dose,
					'range' => null,
					'rule'  => 'same_drug_continue',
				];
			}
			return [
				'dose'  => self::starter( $to_drug ),
				'range' => null,
				'rule'  => 'same_drug_unrecognised_dose',
			];
		}

		$from_index = self::index_of( $from_drug, $from_dose );

		if ( $from_drug === 'mounjaro' && $to_drug === 'wegovy' ) {
			if ( $from_index === false ) {
				return [ 'dose' => self::starter( 'wegovy' ), 'range' => null, 'rule' => 'switch_unrecognised_dose' ];
			}
			// Mounjaro 2.5–7.5mg (index 0–2) → Wegovy 0.5–1mg; 10–15mg → Wegovy 1.7–2.4mg.
			if ( $from_index <= 2 ) {
				return [ 'dose' => '0.5mg', 'range' => '0.5mg–1mg', 'rule' => 'mounjaro_low_to_wegovy' ];
			}
			return [ 'dose' => '1.7mg', 'range' => '1.7mg–2.4mg', 'rule' => 'mounjaro_high_to_wegovy' ];
		}

		if ( $from_drug === 'wegovy' && $to_drug === 'mounjaro' ) {
			if ( $from_index === false ) {
				return [ 'dose' => self::starter( 'mounjaro' ), 'range' => null, 'rule' => 'switch_unrecognised_dose' ];
			}
			// Wegovy ≤1mg (index 0–2) → Mounjaro 2.5mg; 1.7/2.4mg → Mounjaro 5mg.
			if ( $from_index <= 2 ) {
				return [ 'dose' => '2.5mg', 'range' => null, 'rule' => 'wegovy_low_to_mounjaro' ];
			}
			return [ 'dose' => '5mg', 'range' => null, 'rule' => 'wegovy_high_to_mounjaro' ];
		}

		// Unknown source medication: start at the target's starter dose.
		return [ 'dose' => self::starter( $to_drug ), 'range' => null, 'rule' => 'switch_unknown_source' ];
	}
}
