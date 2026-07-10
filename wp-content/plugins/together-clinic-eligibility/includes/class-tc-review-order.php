<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates the WooCommerce order at assessment submission (review-first model).
 *
 * The order is born with the full clinical payload attached and sits in the
 * awaiting-review status until a prescriber acts on it. No cart or checkout
 * is involved: payment happens later, via the pay link sent on approval.
 */
class TC_Review_Order {

	/**
	 * @param array  $payload       Normalised assessment payload.
	 * @param string $assessment_id UUID of the submissions-table row.
	 * @param int    $user_id       Patient user ID (0 for guest).
	 * @param array  $flags         Review flags for the prescriber (e.g. switch_proposed).
	 * @return WC_Order|WP_Error
	 */
	public static function create_from_assessment( array $payload, $assessment_id, $user_id = 0, array $flags = [] ) {
		$treatment = $payload['selectedTreatment'] ?? '';
		$dose      = $payload['selectedDose'] ?? '';
		if ( ! $dose ) {
			$dose = TC_Dose_Ladder::starter( $treatment );
		}

		$variation_id = TC_Variation_Map::get_variation_id( $treatment, $dose );
		$product      = $variation_id ? wc_get_product( $variation_id ) : false;

		if ( ! $product || ! $product->exists() ) {
			TC_Log::error( 'review_order_product_missing', [
				'assessment_id' => $assessment_id,
				'treatment'     => $treatment,
				'dose'          => $dose,
			] );
			return new WP_Error(
				'tc_no_product',
				sprintf( 'No product is configured for %s %s. Please contact us and we will complete your order.', ucfirst( $treatment ), $dose )
			);
		}

		$order = wc_create_order( [ 'customer_id' => (int) $user_id ] );
		if ( is_wp_error( $order ) ) {
			TC_Log::error( 'review_order_create_failed', [
				'assessment_id' => $assessment_id,
				'error'         => $order->get_error_message(),
			] );
			return $order;
		}

		$order->add_product( $product, 1 );
		self::set_addresses_from_payload( $order, $payload );
		$order->set_created_via( 'tc_eligibility_assessment' );

		// Attaches the raw payload, flat clinical keys and the assessment link
		// (reads the request cache populated by TC_Cookie_Store::save_to_session
		// moments earlier in the submit handler), and back-links the order onto
		// the submissions row.
		TC_Checkout::attach_assessment_to_order( $order );

		$order->update_meta_data( TC_Review_Status::FLAGS_META, $flags );
		$order->calculate_totals();
		$order->save();

		$order->update_status(
			TC_Review_Status::STATUS,
			'Order created automatically from the eligibility assessment. Awaiting prescriber review — no payment has been taken.'
		);

		TC_Log::info( 'review_order_created', [
			'order_id'      => $order->get_id(),
			'assessment_id' => $assessment_id,
			'treatment'     => $treatment,
			'dose'          => $dose,
			'user_id'       => (int) $user_id,
		] );

		return $order;
	}

	private static function set_addresses_from_payload( WC_Order $order, array $payload ) {
		$first_name = $payload['firstName'] ?? '';
		$last_name  = $payload['lastName'] ?? '';
		if ( ! $first_name && ! empty( $payload['fullName'] ) ) {
			list( $first_name, $last_name ) = TC_Cookie_Store::split_full_name( $payload['fullName'] );
		}

		$country = TC_Account::country_code( $payload['country'] ?? 'United Kingdom' );

		$billing = [
			'first_name' => $first_name,
			'last_name'  => $last_name ?: $first_name,
			'email'      => $payload['email'] ?? '',
			'phone'      => $payload['phone'] ?? '',
			'address_1'  => $payload['addressLine1'] ?? '',
			'address_2'  => $payload['addressLine2'] ?? '',
			'city'       => $payload['city'] ?? '',
			'postcode'   => $payload['postcode'] ?? '',
			'country'    => $country,
		];

		$shipping = $billing;
		unset( $shipping['email'], $shipping['phone'] );

		$order->set_address( $billing, 'billing' );
		$order->set_address( $shipping, 'shipping' );
	}
}
