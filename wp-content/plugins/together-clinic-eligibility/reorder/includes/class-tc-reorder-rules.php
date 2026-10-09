<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Reorder_Rules {

	/** Rule O4.2: the 12-week stop rule (Xenical SmPC 4.1). */
	const ORLISTAT_MIN_LOSS_PCT = 5;
	/** From this day after the first supply, under 5% loss is a red flag. */
	const ORLISTAT_FLAG_DAY     = 56;
	/** From this day (12 weeks) after the first supply, under 5% loss blocks. */
	const ORLISTAT_BLOCK_DAY    = 84;

	/** Patient wording for the 12-week block (pharmacy checker, 9 Oct 2026). */
	const ORLISTAT_UNDER_5_MESSAGE = 'Your weight change so far is under 5% of your starting weight. The licence says Orlistat should stop if at least 5% has not been lost by 12 weeks, so a prescriber needs to review you before another pack. Please contact us.';
	/** Patient wording when the check cannot be made (never mentions the target). */
	const ORLISTAT_NO_DATA_MESSAGE = 'We could not check your progress on Orlistat, so a prescriber needs to review you before another pack. Please contact us.';

	/**
	 * @param array      $payload Normalised reorder answers.
	 * @param array|null $prefill From TC_Reorder_Prefill.
	 * @param array|null $ctx     Test seam: [ 'height' => float, 'orlistat' => orlistat_context() ]. Built from the database when null.
	 */
	public static function evaluate( array $payload, $prefill, $ctx = null ) {
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

		$ctx = self::context( $ctx, $payload_med, function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0 );

		// Rules WM-2026-10-v1 onwards (IP-FRM-01 3A.7; Orlistat OE8): stop
		// below BMI 20. Unrounded BMI, so 19.96 never passes as 20.0. Height
		// from the eligibility assessment, weight reported now. 20 to 22.9 is
		// flagged for the prescriber (review_flags()).
		if ( class_exists( 'TC_Eligibility_Rules' ) ) {
			$bmi_raw = TC_Eligibility_Rules::bmi_raw( $payload['currentWeight'] ?? 0, $ctx['height'] );
			if ( $bmi_raw !== null && $bmi_raw < 20 ) {
				return self::block( 'bmi_floor', 'Based on your current weight, a clinician consultation is needed before any further treatment.' );
			}
		}

		// Rule O4.2 (WM-2026-10-v3): Orlistat 12-week stop rule.
		if ( $payload_med === 'orlistat' ) {
			$o      = $ctx['orlistat'];
			$review = self::orlistat_twelve_week( $o['start_weight'], $payload['currentWeight'] ?? 0, $o['days_since_first_supply'] );
			if ( $review['block'] ) {
				$out = self::block( $review['block'], $review['block'] === 'orlistat_12_week' ? self::ORLISTAT_UNDER_5_MESSAGE : self::ORLISTAT_NO_DATA_MESSAGE );
				$out['prescriber'] = implode( ' ', $review['flags'] );
				return $out;
			}
		}

		return [ 'ok' => true, 'reason' => '' ];
	}

	/**
	 * Rule O4.2 as a pure check, counted in days from the FIRST supply date
	 * (never the number of packs; a pack can last longer than 28 days).
	 * - Before day 56: start weight, current weight and % change carried.
	 * - Day 56 to 83: the same, and a red flag if loss is under 5%. The
	 *   patient is never told before day 84 that they missed the target.
	 * - Day 84 onwards: under 5% loss blocks; missing data blocks.
	 * - First supply date unknown: blocks (the check cannot be made).
	 *
	 * @return array { stage: string, block: string ('' | 'orlistat_12_week' | 'orlistat_12_week_no_data'), loss_pct: float|null, flags: array }
	 */
	public static function orlistat_twelve_week( $start_kg, $current_kg, $days ) {
		$out  = [ 'stage' => '', 'block' => '', 'loss_pct' => null, 'flags' => [] ];
		$loss = class_exists( 'TC_Eligibility_Rules' ) ? TC_Eligibility_Rules::percent_loss( $start_kg, $current_kg ) : null;
		$out['loss_pct'] = $loss;

		$weights = sprintf(
			'Start weight %s, current weight %s, change %s.',
			$start_kg ? sprintf( '%.1f kg', (float) $start_kg ) : 'not found',
			$current_kg ? sprintf( '%.1f kg', (float) $current_kg ) : 'not given',
			$loss === null ? 'not known' : sprintf( '%s%.1f%%', $loss > 0 ? '-' : '+', abs( $loss ) )
		);

		if ( $days === null ) {
			$out['stage'] = 'unknown';
			$out['block'] = 'orlistat_12_week_no_data';
			$out['flags']['orl_12_week_no_data'] = 'BLOCK O4.2 (Orlistat): first supply date not found, so the 12-week stop rule cannot be checked. ' . $weights . ' Reorder blocked; review by hand (stop if under 5% loss at 12 weeks). Basis: Xenical SmPC 4.1.';
			return $out;
		}

		$days = (int) $days;
		$out['flags']['orl_weight_change'] = sprintf( 'Orlistat weight review (rule O4.2): first supply %d days ago (week %d). %s', $days, (int) floor( $days / 7 ), $weights );

		if ( $days < self::ORLISTAT_FLAG_DAY ) {
			$out['stage'] = 'early';
			return $out;
		}

		if ( $days < self::ORLISTAT_BLOCK_DAY ) {
			$out['stage'] = 'flag';
			if ( $loss === null ) {
				$out['flags']['orl_12_week_due'] = 'RED FLAG O4.2 (Orlistat): 12-week review due by day 84 and the weight change cannot be worked out. Get the start weight and a verified current weight. Basis: Xenical SmPC 4.1.';
			} elseif ( $loss < self::ORLISTAT_MIN_LOSS_PCT ) {
				$out['flags']['orl_12_week_due'] = 'RED FLAG O4.2 (Orlistat): under 5% loss so far with the 12-week review due by day 84. ' . $weights . ' At 12 weeks, under 5% means stop under the licence. Basis: Xenical SmPC 4.1.';
			}
			return $out;
		}

		$out['stage'] = 'block';
		if ( $loss === null ) {
			$out['block'] = 'orlistat_12_week_no_data';
			$out['flags']['orl_12_week_no_data'] = 'BLOCK O4.2 (Orlistat): past 12 weeks from the first supply and the weight change cannot be worked out (start or current weight missing). ' . $weights . ' Reorder blocked; review by hand. Basis: Xenical SmPC 4.1.';
		} elseif ( $loss < self::ORLISTAT_MIN_LOSS_PCT ) {
			$out['block'] = 'orlistat_12_week';
			$out['flags']['orl_12_week_stop'] = 'BLOCK O4.2 (Orlistat): Under 5% at 12 weeks: stop under the licence. ' . $weights . ' Any override is off-label and needs recorded reasons and Superintendent approval. Basis: Xenical SmPC 4.1.';
		} else {
			$out['flags']['orl_12_week_ok'] = 'Orlistat 12-week rule: at least 5% loss reported. The weight for this review must be independently verified (section 6); a reported weight alone is not enough to continue. Basis: Xenical SmPC 4.1; rule O4.2.';
		}
		return $out;
	}

	/**
	 * Prescriber flags for a reorder that was not blocked: BMI 20 to 22.9 for
	 * every product (rule T2, O4.3); for Orlistat the weight review and the
	 * section O medicine screen of any new medicines.
	 */
	public static function review_flags( array $payload, $user_id, $ctx = null ) {
		$flags     = [];
		$treatment = TC_Reorder_Pricing::normalize_treatment( $payload['currentMedication'] ?? '' );
		$ctx       = self::context( $ctx, $treatment, $user_id );

		if ( class_exists( 'TC_Eligibility_Rules' ) ) {
			$flags = array_merge( $flags, self::bmi_band_flags( TC_Eligibility_Rules::bmi_raw( $payload['currentWeight'] ?? 0, $ctx['height'] ), $treatment ) );
		}

		if ( $treatment === 'orlistat' ) {
			$o      = $ctx['orlistat'];
			$review = self::orlistat_twelve_week( $o['start_weight'], $payload['currentWeight'] ?? 0, $o['days_since_first_supply'] );
			$flags  = array_merge( $flags, $review['flags'] );
			if ( $o['start_source'] && isset( $flags['orl_weight_change'] ) ) {
				$flags['orl_weight_change'] .= ' Start weight source: ' . $o['start_source'] . '.';
			}
			$flags = array_merge( $flags, self::orlistat_new_medicine_flags( (string) ( $payload['newMedicationsList'] ?? '' ) ) );
		}
		return $flags;
	}

	/**
	 * BMI 20 to 22.9 at a reorder: prescriber decision (rule T2; Orlistat
	 * O4.3). Takes the UNROUNDED BMI so 22.96 is flagged, not read as 23.0.
	 */
	public static function bmi_band_flags( $bmi_raw, $treatment ) {
		if ( $bmi_raw === null || $bmi_raw < 20 || $bmi_raw >= 23 ) {
			return [];
		}
		return [
			'bmi_20_23' => sprintf( 'Current BMI %.2f (20 to 22.9): prescriber decision only, reason recorded; generally stop and monitor.%s Weight must be independently verified before every supply below BMI 23 (section 9).', $bmi_raw, $treatment === 'orlistat' ? ' Orlistat: rule O4.3.' : ' Reduced dose considered (rule T2).' ),
		];
	}

	/** Section O medicine screen of a reorder's new medicines (re-checked at every supply). */
	public static function orlistat_new_medicine_flags( $list ) {
		if ( trim( $list ) === '' || ! class_exists( 'TC_Eligibility_Rules' ) ) {
			return [];
		}
		// Scanned as a "moving" patient so a weight-loss medicine raises a
		// flag instead of stopping the scan; it is then marked as a block.
		$check = TC_Eligibility_Rules::orlistat_medicine_check( [ 'currentMedsList' => $list ], true, 'other' );
		$flags = $check['flags'];
		if ( isset( $flags['orl_oe7_confirm_stopped'] ) ) {
			unset( $flags['orl_oe7_confirm_stopped'] );
			$flags['orl_oe7_reorder'] = 'BLOCK OE7 (Orlistat): new medicines mention another weight-loss medicine. Orlistat is never supplied alongside a GLP-1 or another weight-loss medicine: do not supply until it has stopped. Basis: Deltera PGD; rule T5 extended.';
		}
		return $flags;
	}

	/** Height and Orlistat context, from the test seam or the database. */
	private static function context( $ctx, $treatment, $user_id ) {
		if ( is_array( $ctx ) ) {
			return $ctx + [ 'height' => 0.0, 'orlistat' => [ 'start_weight' => null, 'start_source' => '', 'days_since_first_supply' => null ] ];
		}
		$out = [
			'height'   => class_exists( 'TC_DB' ) ? TC_DB::latest_height_for_user( $user_id ) : 0.0,
			'orlistat' => [ 'start_weight' => null, 'start_source' => '', 'days_since_first_supply' => null ],
		];
		if ( $treatment === 'orlistat' ) {
			$out['orlistat'] = self::orlistat_context( $user_id );
		}
		return $out;
	}

	/**
	 * Start weight and first supply date for the 12-week rule, always from
	 * the patient's FIRST Orlistat supply with us: a later assessment never
	 * resets the clock or the start weight.
	 *
	 * @return array { start_weight: float|null, start_source: string, days_since_first_supply: int|null }
	 */
	public static function orlistat_context( $user_id ) {
		$supplies = self::orlistat_supplies( $user_id );
		$first    = self::earliest_supply( $supplies );
		$row      = class_exists( 'TC_DB' ) ? TC_DB::orlistat_start_for_user( $user_id ) : null;
		return self::context_from( $first, $row, new DateTime( 'today' ) );
	}

	/**
	 * Pure: build the 12-week context from the first supply and the
	 * earliest Orlistat assessment row (fallback for the start weight).
	 *
	 * @param array|null $first [ 'date' => 'Y-m-d', 'raw' => assessment payload array|null ]
	 * @param array|null $row   Earliest Orlistat assessment row.
	 */
	public static function context_from( $first, $row, DateTime $today ) {
		$out = [ 'start_weight' => null, 'start_source' => '', 'days_since_first_supply' => null ];
		if ( $first && ! empty( $first['date'] ) ) {
			$out['days_since_first_supply'] = (int) $today->diff( new DateTime( $first['date'] ) )->days;
		}
		$raw = ( $first && is_array( $first['raw'] ?? null ) ) ? $first['raw'] : null;
		if ( $raw && (float) ( $raw['weightKg'] ?? 0 ) > 0 ) {
			$transfer = ( $raw['userType'] ?? '' ) === 'switching' && ( $raw['currentMedication'] ?? '' ) === 'orlistat' && (float) ( $raw['startWeightKg'] ?? 0 ) > 0;
			$out['start_weight'] = $transfer ? (float) $raw['startWeightKg'] : (float) $raw['weightKg'];
			$out['start_source'] = $transfer
				? 'declared weight when first starting orlistat, before transferring to us (check the evidence)'
				: 'weight at the assessment for the first Orlistat supply (' . $first['date'] . ')';
		} elseif ( $row && (float) ( $row['weight_kg'] ?? 0 ) > 0 ) {
			$transfer = ( $row['user_type'] ?? '' ) === 'switching' && ( $row['current_medication'] ?? '' ) === 'orlistat' && (float) ( $row['start_weight_kg'] ?? 0 ) > 0;
			$out['start_weight'] = $transfer ? (float) $row['start_weight_kg'] : (float) $row['weight_kg'];
			$out['start_source'] = 'earliest Orlistat assessment, ' . substr( (string) ( $row['created_at'] ?? '' ), 0, 10 );
		}
		return $out;
	}

	/**
	 * Pure: the earliest supply in a list of [ 'date' => 'Y-m-d', 'raw' => ... ].
	 */
	public static function earliest_supply( array $supplies ) {
		$first = null;
		foreach ( $supplies as $s ) {
			if ( empty( $s['date'] ) ) {
				continue;
			}
			if ( $first === null || strcmp( $s['date'], $first['date'] ) < 0 ) {
				$first = $s;
			}
		}
		return $first;
	}

	/** Every paid Orlistat order for the customer, with its assessment snapshot. */
	private static function orlistat_supplies( $user_id ) {
		if ( ! function_exists( 'wc_get_orders' ) || ! class_exists( 'TC_Variation_Map' ) ) {
			return [];
		}
		$product_id = (int) TC_Variation_Map::get_variation_id( 'orlistat', '120mg' );
		if ( ! $product_id ) {
			return [];
		}
		try {
			$orders = wc_get_orders( [
				'customer_id' => (int) $user_id,
				'status'      => [ 'processing', 'completed' ],
				'limit'       => -1,
				'orderby'     => 'date',
				'order'       => 'ASC',
				'type'        => 'shop_order',
			] );
		} catch ( Exception $e ) {
			return [];
		}
		$out = [];
		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				if ( (int) $item->get_variation_id() === $product_id || (int) $item->get_product_id() === $product_id ) {
					$date  = $order->get_date_paid() ?: $order->get_date_created();
					$raw   = json_decode( (string) $order->get_meta( '_tc_eligibility_raw' ), true );
					$out[] = [ 'date' => $date ? $date->date( 'Y-m-d' ) : '', 'raw' => is_array( $raw ) ? $raw : null ];
					break;
				}
			}
		}
		return $out;
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
