<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Reorder_Rules {

	/** Rule O4.2: the 12-week stop rule (Xenical SmPC 4.1). */
	const ORLISTAT_STOP_WEEKS   = 12;
	const ORLISTAT_MIN_LOSS_PCT = 5;
	/** Nominal days a supply covers (84 capsules, one with each main meal). */
	const ORLISTAT_PACK_DAYS    = 28;

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

		// Rules WM-2026-10-v1 onwards (IP-FRM-01 3A.7; Orlistat OE8): stop
		// below BMI 20. Uses the height from the patient's eligibility
		// assessment and the weight they report now. 20 to 22.9 is flagged
		// for the prescriber (review_flags()).
		if ( class_exists( 'TC_DB' ) && class_exists( 'TC_Eligibility_Rules' ) ) {
			$height = TC_DB::latest_height_for_user( get_current_user_id() );
			$bmi    = TC_Eligibility_Rules::bmi( $payload['currentWeight'] ?? 0, $height );
			if ( $bmi !== null && $bmi < 20 ) {
				return self::block( 'bmi_floor', 'Based on your current weight, a clinician consultation is needed before any further treatment.' );
			}
		}

		// Rule O4.2 (WM-2026-10-v3): Orlistat stops if less than 5% of the
		// starting weight is lost by 12 weeks from the first supply.
		if ( $payload_med === 'orlistat' && class_exists( 'TC_DB' ) ) {
			$ctx    = self::orlistat_context( get_current_user_id() );
			$review = self::orlistat_twelve_week( $ctx['start_weight'], $payload['currentWeight'] ?? 0, $ctx['days_since_first_supply'] );
			if ( $review['block'] ) {
				return self::block( 'orlistat_12_week', 'Based on the weight you entered, Orlistat should not be continued: its licence says to stop if less than 5% of your starting weight has been lost after 12 weeks. Please contact us or speak with your GP about other options.' );
			}
		}

		return [ 'ok' => true, 'reason' => '' ];
	}

	/**
	 * Rule O4.2 as a pure check. Applies at the first reorder that would take
	 * treatment beyond 12 weeks from the first supply date (a nominal 28-day
	 * supply from today ending after day 84), and at every later reorder.
	 * Counted from the first supply date, never from the number of packs.
	 *
	 * @param float|null $start_kg  Starting weight, or null if unknown.
	 * @param float      $current_kg Weight reported now.
	 * @param int|null   $days      Days since the first supply, or null if unknown.
	 * @return array { applies: bool|null, block: bool, loss_pct: float|null, flags: array }
	 */
	public static function orlistat_twelve_week( $start_kg, $current_kg, $days ) {
		$out  = [ 'applies' => null, 'block' => false, 'loss_pct' => null, 'flags' => [] ];
		$loss = class_exists( 'TC_Eligibility_Rules' ) ? TC_Eligibility_Rules::percent_loss( $start_kg, $current_kg ) : null;
		$out['loss_pct'] = $loss;

		$weights = sprintf(
			'Start weight %s, current weight %s, change %s.',
			$start_kg ? sprintf( '%.1f kg', (float) $start_kg ) : 'not known',
			$current_kg ? sprintf( '%.1f kg', (float) $current_kg ) : 'not given',
			$loss === null ? 'not known' : sprintf( '%s%.1f%%', $loss > 0 ? '-' : '+', abs( $loss ) )
		);

		if ( $days === null ) {
			$out['flags']['orl_12_week_unknown'] = 'RED FLAG O4.2 (Orlistat): first supply date not found. ' . $weights . ' Check the 12-week stop rule by hand (stop if under 5% loss at 12 weeks). Basis: Xenical SmPC 4.1.';
			return $out;
		}

		$out['applies'] = ( (int) $days + self::ORLISTAT_PACK_DAYS ) > self::ORLISTAT_STOP_WEEKS * 7;
		$out['flags']['orl_weight_change'] = sprintf( 'Orlistat weight review (rule O4.2): first supply %d days ago (week %d). %s', (int) $days, (int) floor( $days / 7 ), $weights );

		if ( ! $out['applies'] ) {
			return $out;
		}
		if ( $loss === null ) {
			$out['flags']['orl_12_week_unknown'] = 'RED FLAG O4.2 (Orlistat): 12-week stop rule applies but the weight change cannot be worked out. Check it before supply. Basis: Xenical SmPC 4.1.';
			return $out;
		}
		if ( $loss < self::ORLISTAT_MIN_LOSS_PCT ) {
			$out['block'] = true;
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
	public static function review_flags( array $payload, $user_id ) {
		$flags     = [];
		$treatment = TC_Reorder_Pricing::normalize_treatment( $payload['currentMedication'] ?? '' );

		if ( class_exists( 'TC_DB' ) && class_exists( 'TC_Eligibility_Rules' ) ) {
			$bmi = TC_Eligibility_Rules::bmi( $payload['currentWeight'] ?? 0, TC_DB::latest_height_for_user( $user_id ) );
			$flags = array_merge( $flags, self::bmi_band_flags( $bmi, $treatment ) );
		}

		if ( $treatment === 'orlistat' ) {
			if ( class_exists( 'TC_DB' ) ) {
				$ctx    = self::orlistat_context( $user_id );
				$review = self::orlistat_twelve_week( $ctx['start_weight'], $payload['currentWeight'] ?? 0, $ctx['days_since_first_supply'] );
				$flags  = array_merge( $flags, $review['flags'] );
				if ( $ctx['start_source'] && isset( $flags['orl_weight_change'] ) ) {
					$flags['orl_weight_change'] .= ' Start weight source: ' . $ctx['start_source'] . '.';
				}
			}
			$flags = array_merge( $flags, self::orlistat_new_medicine_flags( (string) ( $payload['newMedicationsList'] ?? '' ) ) );
		}
		return $flags;
	}

	/** BMI 20 to 22.9 at a reorder: prescriber decision (rule T2; Orlistat O4.3). */
	public static function bmi_band_flags( $bmi, $treatment ) {
		if ( $bmi === null || $bmi < 20 || $bmi >= 23 ) {
			return [];
		}
		return [
			'bmi_20_23' => sprintf( 'Current BMI %.1f (20 to 22.9): prescriber decision only, reason recorded; generally stop and monitor.%s Weight must be independently verified before every supply below BMI 23 (section 9).', $bmi, $treatment === 'orlistat' ? ' Orlistat: rule O4.3.' : ' Reduced dose considered (rule T2).' ),
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

	/**
	 * Start weight and first supply date for the 12-week rule.
	 *
	 * @return array { start_weight: float|null, start_source: string, days_since_first_supply: int|null }
	 */
	public static function orlistat_context( $user_id ) {
		$out = [ 'start_weight' => null, 'start_source' => '', 'days_since_first_supply' => null ];
		$row = TC_DB::orlistat_start_for_user( $user_id );
		if ( $row ) {
			$transfer = ( $row['user_type'] ?? '' ) === 'switching' && ( $row['current_medication'] ?? '' ) === 'orlistat' && (float) $row['start_weight_kg'] > 0;
			$out['start_weight'] = $transfer ? (float) $row['start_weight_kg'] : (float) $row['weight_kg'];
			$out['start_source'] = $transfer
				? 'declared weight when first starting, before transferring to us (check the evidence)'
				: 'weight reported at the assessment on ' . substr( (string) $row['created_at'], 0, 10 );
		}

		$first = self::first_orlistat_supply( $user_id, $row ? (string) $row['created_at'] : '' );
		if ( $first ) {
			$today = new DateTime( 'today' );
			$out['days_since_first_supply'] = (int) $today->diff( $first )->days;
		}
		return $out;
	}

	/** Date of the first paid Orlistat order since the starting assessment. */
	private static function first_orlistat_supply( $user_id, $since ) {
		if ( ! function_exists( 'wc_get_orders' ) || ! class_exists( 'TC_Variation_Map' ) ) {
			return null;
		}
		$product_id = (int) TC_Variation_Map::get_variation_id( 'orlistat', '120mg' );
		if ( ! $product_id ) {
			return null;
		}
		$args = [
			'customer_id' => (int) $user_id,
			'status'      => [ 'processing', 'completed' ],
			'limit'       => 50,
			'orderby'     => 'date',
			'order'       => 'ASC',
			'type'        => 'shop_order',
		];
		if ( $since !== '' ) {
			$args['date_created'] = '>=' . strtotime( $since );
		}
		try {
			$orders = wc_get_orders( $args );
		} catch ( Exception $e ) {
			return null;
		}
		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				if ( (int) $item->get_variation_id() === $product_id || (int) $item->get_product_id() === $product_id ) {
					$date = $order->get_date_paid() ?: $order->get_date_created();
					return $date ? new DateTime( $date->date( 'Y-m-d' ) ) : null;
				}
			}
		}
		return null;
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
