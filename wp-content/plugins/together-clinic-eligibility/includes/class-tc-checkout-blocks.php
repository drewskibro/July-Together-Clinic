<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Checkout_Blocks {

	const SCHEMA_NAMESPACE = 'tc-eligibility';

	public function __construct() {
		add_action( 'woocommerce_blocks_loaded', [ $this, 'register_store_api_extension' ] );

		add_action( 'woocommerce_store_api_checkout_update_order_from_request', [ $this, 'attach_to_order_from_blocks' ], 10, 2 );
	}

	public function register_store_api_extension() {
		if ( ! class_exists( '\Automattic\WooCommerce\StoreApi\StoreApi' ) ) {
			return;
		}

		if ( ! class_exists( '\Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema' ) ) {
			return;
		}

		try {
			$extend = \Automattic\WooCommerce\StoreApi\StoreApi::container()->get(
				\Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema::class
			);

			$extend->register_endpoint_data(
				[
					'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema::IDENTIFIER,
					'namespace'       => self::SCHEMA_NAMESPACE,
					'data_callback'   => [ $this, 'extension_data' ],
					'schema_callback' => [ $this, 'extension_schema' ],
					'schema_type'     => ARRAY_A,
				]
			);
		} catch ( \Throwable $e ) {
			TC_Log::warn( 'blocks_storeapi_register_failed', [ 'error' => $e->getMessage() ] );
		}
	}

	public function extension_data() {
		$cookie = TC_Cookie_Store::get();
		if ( empty( $cookie ) ) {
			return [
				'has_assessment' => false,
				'assessment_id'  => '',
				'prefill'        => null,
			];
		}

		$first_name = $cookie['firstName'] ?? '';
		$last_name  = $cookie['lastName'] ?? '';
		if ( ! $first_name && ! empty( $cookie['fullName'] ) ) {
			list( $first_name, $last_name ) = TC_Cookie_Store::split_full_name( $cookie['fullName'] );
		}

		return [
			'has_assessment' => true,
			'assessment_id'  => (string) ( $cookie['assessment_id'] ?? '' ),
			'prefill'        => [
				'first_name' => (string) $first_name,
				'last_name'  => (string) $last_name,
				'email'      => (string) ( $cookie['email'] ?? '' ),
				'phone'      => (string) ( $cookie['phone'] ?? '' ),
				'address_1'  => (string) ( $cookie['addressLine1'] ?? '' ),
				'address_2'  => (string) ( $cookie['addressLine2'] ?? '' ),
				'city'       => (string) ( $cookie['city'] ?? '' ),
				'postcode'   => (string) ( $cookie['postcode'] ?? '' ),
				'country'    => TC_Account::country_code( $cookie['country'] ?? 'United Kingdom' ),
			],
		];
	}

	public function extension_schema() {
		return [
			'has_assessment' => [
				'description' => 'Whether the visitor has a completed eligibility assessment in their session.',
				'type'        => 'boolean',
				'readonly'    => true,
			],
			'assessment_id' => [
				'description' => 'UUID of the eligibility assessment.',
				'type'        => 'string',
				'readonly'    => true,
			],
			'prefill' => [
				'description' => 'Patient details to prefill into the checkout form.',
				'type'        => [ 'object', 'null' ],
				'readonly'    => true,
				'properties'  => [
					'first_name' => [ 'type' => 'string' ],
					'last_name'  => [ 'type' => 'string' ],
					'email'      => [ 'type' => 'string' ],
					'phone'      => [ 'type' => 'string' ],
					'address_1'  => [ 'type' => 'string' ],
					'address_2'  => [ 'type' => 'string' ],
					'city'       => [ 'type' => 'string' ],
					'postcode'   => [ 'type' => 'string' ],
					'country'    => [ 'type' => 'string' ],
				],
			],
		];
	}

	public function attach_to_order_from_blocks( $order, $request = null ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}
		TC_Checkout::attach_assessment_to_order( $order );
	}
}
