<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Reorder_Checkout {

	const ORDER_META_RAW = '_rrqr_raw';
	const ORDER_META_ASSESSMENT_ID = '_rrqr_assessment_id';
	const ORDER_META_PREVIOUS_ORDER = '_rrqr_previous_order_id';
	const ORDER_META_PREFIX = '_rrqr_';

	public function __construct() {
		add_filter( 'woocommerce_get_item_data',              [ $this, 'show_reorder_badge' ], 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'persist_rrqr_to_line_item' ], 10, 4 );
		add_action( 'woocommerce_checkout_create_order',      [ $this, 'attach_to_order' ], 10, 2 );
		add_action( 'woocommerce_thankyou',                   [ $this, 'attach_to_existing_order' ], 10, 1 );
		add_action( 'woocommerce_thankyou',                   [ $this, 'clear_cookie_after_order' ], 20, 1 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', [ $this, 'attach_from_blocks' ], 10, 2 );
	}

	public function show_reorder_badge( $item_data, $cart_item ) {
		if ( empty( $cart_item['rrqr_data'] ) ) {
			return $item_data;
		}
		$item_data[] = [
			'key'   => 'Reorder',
			'value' => 'Yes (verified returning patient)',
		];
		return $item_data;
	}

	public function persist_rrqr_to_line_item( $item, $cart_item_key, $values, $order ) {
		if ( ! empty( $values['rrqr_data'] ) ) {
			$item->add_meta_data( '_rrqr_data', $values['rrqr_data'], true );
		}
	}

	public function attach_to_order( $order, $data ) {
		self::attach_assessment_to_order( $order );
	}

	public function attach_to_existing_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		self::attach_assessment_to_order( $order );
	}

	public function attach_from_blocks( $order, $request = null ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}
		self::attach_assessment_to_order( $order );
	}

	public function clear_cookie_after_order( $order_id ) {
		if ( ! $order_id ) {
			return;
		}
		TC_Reorder_Cookie_Store::clear();
	}

	/**
	 * Creates the order at reorder submission (review-first model): born in the
	 * awaiting-review status with the clinical payload attached, paid later via
	 * the pay link sent on prescriber approval. No cart or checkout involved.
	 *
	 * @return WC_Order|WP_Error
	 */
	public static function create_from_submission( array $payload, $assessment_id, $user_id, $prefill ) {
		$treatment = $payload['currentMedication'] ?? '';

		// The ±1 gate: propose + flag, never block. The supplied dose is
		// clamped to the safe band around the paid-order baseline; every
		// deviation lands in _tc_review_flags for the prescriber.
		$gate  = self::apply_dose_gate( $payload, $user_id );
		$dose  = $gate['dose'];
		$flags = $gate['flags'];

		$product_id = TC_Reorder_Pricing::get_product_id( $treatment, $dose );
		$product    = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $product || ! $product->exists() ) {
			TC_Reorder_Log::error( 'review_order_product_missing', [
				'assessment_id' => $assessment_id,
				'treatment'     => $treatment,
				'dose'          => $dose,
			] );
			return new WP_Error(
				'tc_no_product',
				sprintf( 'No product is configured for %s %s. Please contact us and we will complete your reorder.', ucfirst( $treatment ), $dose )
			);
		}

		$order = wc_create_order( [ 'customer_id' => (int) $user_id ] );
		if ( is_wp_error( $order ) ) {
			TC_Reorder_Log::error( 'review_order_create_failed', [
				'assessment_id' => $assessment_id,
				'error'         => $order->get_error_message(),
			] );
			return $order;
		}

		$order->add_product( $product, 1 );
		$order->set_created_via( 'tc_reorder_submission' );

		// Addresses: the reorder check-in doesn't capture an address, so copy
		// from the previous qualifying order, falling back to payload basics.
		$previous = ! empty( $prefill['previous_order_id'] ) ? wc_get_order( (int) $prefill['previous_order_id'] ) : false;
		if ( $previous ) {
			$order->set_address( $previous->get_address( 'billing' ), 'billing' );
			$order->set_address( $previous->get_address( 'shipping' ), 'shipping' );
		}
		if ( ! $order->get_billing_email() && ! empty( $payload['email'] ) ) {
			$order->set_billing_email( sanitize_email( $payload['email'] ) );
		}
		if ( ! $order->get_billing_first_name() && ! empty( $payload['firstName'] ) ) {
			$order->set_billing_first_name( sanitize_text_field( $payload['firstName'] ) );
			$order->set_billing_last_name( sanitize_text_field( $payload['lastName'] ?? '' ) );
		}

		// Attaches _rrqr_* meta from the session/DB row (session was saved just
		// before this call) and back-links the order onto the submissions row.
		self::attach_assessment_to_order( $order );

		$order->update_meta_data( '_tc_review_flags', $flags );
		$order->calculate_totals();
		$order->save();

		$status = class_exists( 'TC_Review_Status' ) ? TC_Review_Status::STATUS : 'on-hold';
		$order->update_status(
			$status,
			'Order created automatically from the reorder check-in. Awaiting prescriber review — no payment has been taken.'
		);

		TC_Reorder_Log::info( 'review_order_created', [
			'order_id'      => $order->get_id(),
			'assessment_id' => $assessment_id,
			'treatment'     => $treatment,
			'dose'          => $dose,
			'user_id'       => (int) $user_id,
		] );

		return $order;
	}

	/**
	 * The reorder ±1 rule, anchored on the most recent PAID order — never on
	 * the patient's self-report or an unpaid/unapproved order. Allowed doses
	 * are the baseline, one step up and one step down (clamped at ladder
	 * ends, unpurchasable rungs skipped). Requests outside the band are
	 * clamped to the nearest allowed dose and flagged; an unverifiable
	 * baseline proceeds on the requested dose with a flag. Nothing blocks —
	 * the prescriber reviews every order before payment.
	 *
	 * (The admin ?preview_reorder=1 path never reaches this: its synthetic
	 * prefill cannot pass save_partial's has_previous_order check. The
	 * preview skip lives in the wizard's dose-option filtering instead.)
	 *
	 * @return array { dose: string, flags: array }
	 */
	private static function apply_dose_gate( array $payload, $user_id ) {
		$treatment = (string) ( $payload['currentMedication'] ?? '' );
		$requested = (string) ( $payload['selectedDose'] ?? '' );
		$flags     = [];

		if ( ! class_exists( 'TC_Dose_Ladder' ) ) {
			$flags['dose_gate_unavailable'] = sprintf( 'Dose ladder unavailable; supplied requested dose %s.', $requested );
			return [ 'dose' => $requested, 'flags' => $flags ];
		}

		$baseline = TC_Reorder_Prefill::paid_baseline_for_user( $user_id );

		if ( ! $baseline['order'] || ! $baseline['dose'] ) {
			$flags['dose_unverified'] = sprintf( 'No paid order found to anchor the dose check; supplied requested dose %s.', $requested );
			return [ 'dose' => $requested, 'flags' => $flags ];
		}

		$created = $baseline['order']->get_date_created();
		if ( $created ) {
			$age_days                  = max( 0, (int) floor( ( time() - $created->getTimestamp() ) / DAY_IN_SECONDS ) );
			$flags['reference_order'] = sprintf(
				'#%s (%d day(s) old): %s %s',
				$baseline['order']->get_order_number(),
				$age_days,
				ucfirst( $baseline['medication'] ),
				$baseline['dose']
			);
		}

		if ( $baseline['medication'] !== $treatment ) {
			$flags['dose_unverified'] = sprintf(
				'Paid history is %s but this reorder is for %s; supplied requested dose %s.',
				ucfirst( $baseline['medication'] ),
				ucfirst( $treatment ),
				$requested
			);
			return [ 'dose' => $requested, 'flags' => $flags ];
		}

		$allowed = TC_Dose_Ladder::allowed_reorder_doses( $treatment, $baseline['dose'] );

		if ( ! empty( $allowed['skipped'] ) ) {
			$flags['dose_gap_skipped'] = sprintf(
				'Unpurchasable rung(s) skipped when computing the dose band: %s.',
				implode( ', ', $allowed['skipped'] )
			);
		}

		if ( empty( $allowed['doses'] ) ) {
			$flags['dose_unverified'] = sprintf(
				'Baseline dose %s is not on the %s ladder; supplied requested dose %s.',
				$baseline['dose'],
				ucfirst( $treatment ),
				$requested
			);
			return [ 'dose' => $requested, 'flags' => $flags ];
		}

		if ( in_array( $requested, $allowed['doses'], true ) ) {
			return [ 'dose' => $requested, 'flags' => $flags ];
		}

		$clamped = TC_Dose_Ladder::clamp_to_allowed( $treatment, $allowed['doses'], $requested );

		$flags['dose_out_of_range'] = sprintf(
			'Requested %s; paid baseline %s (allowed: %s); supplied %s.',
			$requested,
			$baseline['dose'],
			implode( ' / ', $allowed['doses'] ),
			$clamped
		);

		TC_Reorder_Log::warn( 'dose_out_of_range_clamped', [
			'user_id'   => (int) $user_id,
			'treatment' => $treatment,
			'requested' => $requested,
			'baseline'  => $baseline['dose'],
			'supplied'  => $clamped,
		] );

		return [ 'dose' => $clamped, 'flags' => $flags ];
	}

	public static function attach_assessment_to_order( WC_Order $order ) {
		$data = TC_Reorder_Cookie_Store::get();
		if ( empty( $data ) ) {
			return;
		}

		$assessment_id = $data['assessment_id'] ?? '';
		if ( ! $assessment_id ) {
			return;
		}

		// Idempotent by assessment identity: skip only when this order already
		// carries the *same* reorder assessment. A newly-taken reorder (a new
		// id) overwrites an earlier snapshot — e.g. a Blocks draft order stamped
		// before the patient retook the reorder assessment.
		$stored_id = (string) $order->get_meta( self::ORDER_META_ASSESSMENT_ID );
		if ( $stored_id === $assessment_id ) {
			return;
		}

		$row = TC_Reorder_DB::get_by_assessment_id( $assessment_id );
		if ( ! $row ) {
			return;
		}

		$payload = json_decode( $row['raw_payload'] ?? '', true );
		if ( ! is_array( $payload ) ) {
			$payload = $data;
		}

		$order->update_meta_data( self::ORDER_META_ASSESSMENT_ID, $assessment_id );
		$order->update_meta_data( self::ORDER_META_PREVIOUS_ORDER, (int) ( $row['previous_order_id'] ?? 0 ) );
		$order->update_meta_data( self::ORDER_META_RAW, wp_json_encode( $payload ) );

		$flat_keys = [
			'firstName', 'lastName', 'email', 'dob',
			'currentMedication', 'currentDose', 'selectedDose',
			'currentWeight', 'hasLostWeight', 'appetiteLasting',
			'hasSideEffects', 'healthChanged',
			'newMedications', 'newMedicationsList',
			'couldBePregnant', 'wantsClinicalSupport',
		];
		foreach ( $flat_keys as $key ) {
			if ( isset( $payload[ $key ] ) && is_scalar( $payload[ $key ] ) ) {
				$order->update_meta_data( self::ORDER_META_PREFIX . $key, sanitize_text_field( (string) $payload[ $key ] ) );
			}
		}

		$order->save();

		TC_Reorder_DB::attach_order( $assessment_id, $order->get_id() );

		TC_Reorder_Log::info( 'order_linked_to_reorder', [
			'order_id'          => $order->get_id(),
			'assessment_id'     => $assessment_id,
			'previous_order_id' => (int) ( $row['previous_order_id'] ?? 0 ),
		] );
	}
}
