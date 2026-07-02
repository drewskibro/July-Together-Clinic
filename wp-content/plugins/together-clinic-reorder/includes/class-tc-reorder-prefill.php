<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Reorder_Prefill {

	public static function for_user( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return null;
		}

		$first_name = trim( (string) $user->first_name );
		$last_name  = trim( (string) $user->last_name );

		if ( ! $first_name ) {
			$first_name = (string) get_user_meta( $user_id, 'billing_first_name', true );
		}
		if ( ! $last_name ) {
			$last_name = (string) get_user_meta( $user_id, 'billing_last_name', true );
		}

		$last_order   = self::last_qualifying_order_for( $user_id );
		$prev_med     = '';
		$prev_dose    = '';
		$prev_order_id = 0;

		if ( $last_order ) {
			$prev_order_id = $last_order->get_id();
			list( $prev_med, $prev_dose ) = self::extract_medication_and_dose( $last_order );
		}

		return [
			'user_id'           => (int) $user_id,
			'first_name'        => $first_name,
			'last_name'         => $last_name,
			'email'             => $user->user_email,
			'previous_order_id' => $prev_order_id,
			'previous_medication' => $prev_med,
			'previous_dose'     => $prev_dose,
			'has_previous_order' => $prev_order_id > 0 && $prev_med !== '',
		];
	}

	public static function last_qualifying_order_for( $user_id ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return null;
		}

		$statuses = apply_filters( 'tc_reorder_qualifying_order_statuses', [ 'completed', 'processing', 'on-hold' ] );

		try {
			$orders = wc_get_orders( [
				'customer_id' => (int) $user_id,
				'status'      => $statuses,
				'limit'       => 20,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'type'        => 'shop_order',
			] );
		} catch ( Exception $e ) {
			TC_Reorder_Log::warn( 'last_qualifying_order_fetch_failed', [ 'error' => $e->getMessage() ] );
			return null;
		}

		$qualifying_ids = TC_Reorder_Pricing::get_variation_map();
		$flat = [];
		foreach ( $qualifying_ids as $doses ) {
			foreach ( $doses as $id ) {
				if ( $id > 0 ) {
					$flat[] = (int) $id;
				}
			}
		}

		if ( empty( $flat ) ) {
			return null;
		}

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$vid = (int) $item->get_variation_id();
				$pid = (int) $item->get_product_id();
				if ( in_array( $vid, $flat, true ) || in_array( $pid, $flat, true ) ) {
					return $order;
				}
			}
		}

		return null;
	}

	public static function extract_medication_and_dose( WC_Order $order ) {
		$map     = TC_Reorder_Pricing::get_variation_map();
		$reverse = [];
		foreach ( $map as $treatment => $doses ) {
			foreach ( $doses as $dose => $id ) {
				if ( $id > 0 ) {
					$reverse[ (int) $id ] = [ $treatment, $dose ];
				}
			}
		}

		foreach ( $order->get_items() as $item ) {
			$vid = (int) $item->get_variation_id();
			$pid = (int) $item->get_product_id();
			foreach ( [ $vid, $pid ] as $candidate ) {
				if ( $candidate && isset( $reverse[ $candidate ] ) ) {
					return $reverse[ $candidate ];
				}
			}
		}

		$rrqr_raw = $order->get_meta( '_rrqr_raw' );
		if ( $rrqr_raw ) {
			$decoded = json_decode( $rrqr_raw, true );
			if ( is_array( $decoded ) ) {
				$med = TC_Reorder_Pricing::normalize_treatment( $decoded['currentMedication'] ?? '' );
				$dose = TC_Reorder_Pricing::normalize_dose( $decoded['selectedDose'] ?? $decoded['currentDose'] ?? '' );
				if ( $med && $dose ) {
					return [ $med, $dose ];
				}
			}
		}

		$elig_raw = $order->get_meta( '_tc_eligibility_raw' );
		if ( $elig_raw ) {
			$decoded = json_decode( $elig_raw, true );
			if ( is_array( $decoded ) ) {
				$med  = TC_Reorder_Pricing::normalize_treatment( $decoded['selectedTreatment'] ?? '' );
				$dose = TC_Reorder_Pricing::normalize_dose( $decoded['selectedDose'] ?? '' );
				if ( $med && $dose ) {
					return [ $med, $dose ];
				}
			}
		}

		return [ '', '' ];
	}
}
