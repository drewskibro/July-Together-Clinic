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
