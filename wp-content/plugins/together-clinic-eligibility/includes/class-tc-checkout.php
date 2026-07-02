<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Checkout {

	const ORDER_META_RAW = '_tc_eligibility_raw';
	const ORDER_META_ASSESSMENT_ID = '_tc_eligibility_assessment_id';
	const ORDER_META_PREFIX = '_tc_elig_';

	public function __construct() {
		add_action( 'template_redirect', [ $this, 'enforce_before_checkout' ] );
		add_action( 'template_redirect', [ $this, 'redirect_returning_customers_from_assessment' ], 5 );

		add_filter( 'woocommerce_checkout_fields',    [ $this, 'prefill_checkout_fields' ] );
		add_filter( 'woocommerce_checkout_get_value', [ $this, 'prefill_checkout_get_value' ], 999, 2 );

		add_action( 'woocommerce_checkout_create_order', [ $this, 'attach_to_order' ], 10, 2 );
		add_action( 'woocommerce_thankyou',              [ $this, 'attach_to_existing_order' ], 10, 1 );
		add_action( 'woocommerce_thankyou',              [ $this, 'clear_cookie_after_order' ], 20, 1 );

		add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_add_to_cart' ], 10, 3 );

		add_action( 'woocommerce_email_order_meta', [ 'TC_Emails', 'inject_into_woo_order_email' ], 20, 2 );
	}

	public function enforce_before_checkout() {
		if ( get_option( 'tc_eligibility_enforce_assessment_before_checkout', '1' ) !== '1' ) {
			return;
		}

		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}

		if ( self::cart_has_reorder_item() ) {
			TC_Log::info( 'checkout_bypass_reorder_cart' );
			return;
		}

		$assessment_page_id = (int) get_option( 'tc_eligibility_assessment_page_id', 0 );
		if ( ! $assessment_page_id ) {
			return;
		}

		if ( get_queried_object_id() === $assessment_page_id ) {
			return;
		}

		$cookie = TC_Cookie_Store::get();
		if ( empty( $cookie ) || empty( $cookie['assessment_id'] ) ) {
			nocache_headers();
			TC_Log::info( 'checkout_redirect_no_assessment', [
				'ip' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '',
			] );
			wp_safe_redirect( get_permalink( $assessment_page_id ) );
			exit;
		}
	}

	public function redirect_returning_customers_from_assessment() {
		$assessment_page_id = (int) get_option( 'tc_eligibility_assessment_page_id', 0 );
		if ( ! $assessment_page_id || get_queried_object_id() !== $assessment_page_id ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( ! class_exists( 'TC_Returning_Customer' ) || ! TC_Returning_Customer::is_returning() ) {
			return;
		}

		if ( ! empty( $_GET['force_assessment'] ) ) {
			return;
		}

		if ( class_exists( 'TC_Variation_Map' ) && empty( TC_Variation_Map::qualifying_variation_ids() ) ) {
			TC_Log::info( 'assessment_redirect_skipped_no_variations', [
				'user_id' => get_current_user_id(),
			] );
			return;
		}

		$reorder_page_id = (int) get_option( 'tc_reorder_page_id', 0 );
		$reorder_url     = $reorder_page_id ? get_permalink( $reorder_page_id ) : home_url( '/reorder/' );

		TC_Log::info( 'assessment_redirect_to_reorder', [
			'user_id'         => get_current_user_id(),
			'reorder_page_id' => $reorder_page_id,
		] );

		nocache_headers();
		wp_safe_redirect( $reorder_url );
		exit;
	}

	public static function cart_has_reorder_item() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item['rrqr_data'] ) ) {
				return true;
			}
		}
		return false;
	}

	public function prefill_checkout_fields( $fields ) {
		$elig = TC_Cookie_Store::get();
		if ( empty( $elig ) ) {
			return $fields;
		}

		$first_name = $elig['firstName'] ?? '';
		$last_name  = $elig['lastName'] ?? '';
		if ( ! $first_name && ! empty( $elig['fullName'] ) ) {
			list( $first_name, $last_name ) = TC_Cookie_Store::split_full_name( $elig['fullName'] );
		}

		$country = TC_Account::country_code( $elig['country'] ?? 'United Kingdom' );

		$map = [
			'billing_first_name'  => $first_name,
			'billing_last_name'   => $last_name ?: $first_name,
			'billing_email'       => $elig['email']        ?? '',
			'billing_phone'       => $elig['phone']        ?? '',
			'billing_address_1'   => $elig['addressLine1'] ?? '',
			'billing_address_2'   => $elig['addressLine2'] ?? '',
			'billing_city'        => $elig['city']         ?? '',
			'billing_postcode'    => $elig['postcode']     ?? '',
			'billing_country'     => $country,

			'shipping_first_name' => $first_name,
			'shipping_last_name'  => $last_name ?: $first_name,
			'shipping_address_1'  => $elig['addressLine1'] ?? '',
			'shipping_address_2'  => $elig['addressLine2'] ?? '',
			'shipping_city'       => $elig['city']         ?? '',
			'shipping_postcode'   => $elig['postcode']     ?? '',
			'shipping_country'    => $country,
		];

		foreach ( $map as $key => $val ) {
			if ( $val === '' ) {
				continue;
			}
			if ( isset( $fields['billing'][ $key ] ) ) {
				$fields['billing'][ $key ]['default'] = $val;
			} elseif ( isset( $fields['shipping'][ $key ] ) ) {
				$fields['shipping'][ $key ]['default'] = $val;
			}
		}

		return $fields;
	}

	public function prefill_checkout_get_value( $value, $input ) {
		$elig = TC_Cookie_Store::get();
		if ( empty( $elig ) ) {
			return $value;
		}

		$first_name = $elig['firstName'] ?? '';
		$last_name  = $elig['lastName'] ?? '';
		if ( ! $first_name && ! empty( $elig['fullName'] ) ) {
			list( $first_name, $last_name ) = TC_Cookie_Store::split_full_name( $elig['fullName'] );
		}

		$country = TC_Account::country_code( $elig['country'] ?? 'United Kingdom' );

		$map = [
			'billing_first_name'  => $first_name,
			'billing_last_name'   => $last_name ?: $first_name,
			'billing_email'       => $elig['email']        ?? '',
			'billing_phone'       => $elig['phone']        ?? '',
			'billing_address_1'   => $elig['addressLine1'] ?? '',
			'billing_address_2'   => $elig['addressLine2'] ?? '',
			'billing_city'        => $elig['city']         ?? '',
			'billing_postcode'    => $elig['postcode']     ?? '',
			'billing_country'     => $country,
			'shipping_first_name' => $first_name,
			'shipping_last_name'  => $last_name ?: $first_name,
			'shipping_address_1'  => $elig['addressLine1'] ?? '',
			'shipping_address_2'  => $elig['addressLine2'] ?? '',
			'shipping_city'       => $elig['city']         ?? '',
			'shipping_postcode'   => $elig['postcode']     ?? '',
			'shipping_country'    => $country,
		];

		if ( isset( $map[ $input ] ) && $map[ $input ] !== '' ) {
			return $map[ $input ];
		}

		return $value;
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

	public function clear_cookie_after_order( $order_id ) {
		if ( ! $order_id ) {
			return;
		}
		TC_Cookie_Store::clear();
	}

	public static function attach_assessment_to_order( WC_Order $order ) {
		if ( $order->get_meta( self::ORDER_META_RAW ) ) {
			return;
		}

		$elig = TC_Cookie_Store::get();
		if ( empty( $elig ) ) {
			return;
		}

		$assessment_id = $elig['assessment_id'] ?? '';
		if ( $assessment_id ) {
			$order->update_meta_data( self::ORDER_META_ASSESSMENT_ID, $assessment_id );
		}

		$order->update_meta_data( self::ORDER_META_RAW, wp_json_encode( $elig ) );

		$flat_keys = [
			'firstName', 'lastName', 'fullName', 'email', 'phone', 'dob',
			'userType', 'provider', 'currentMedication', 'currentDose',
			'ageBand', 'ethnicity', 'sex',
			'weightKg', 'heightCm', 'bmi',
			'pregnant', 'breastfeeding', 'conceive',
			'diabetes',
			'goalWeight',
			'addressLine1', 'addressLine2', 'city', 'postcode', 'country',
			'gpName', 'gpPostcode', 'gpConsentShare', 'gpConsentSCR',
			'selectedTreatment', 'selectedDose',
			'termsAgreed',
		];

		foreach ( $flat_keys as $key ) {
			if ( ! isset( $elig[ $key ] ) ) {
				continue;
			}
			$value = $elig[ $key ];
			$order->update_meta_data(
				self::ORDER_META_PREFIX . $key,
				is_scalar( $value ) ? sanitize_text_field( (string) $value ) : wp_json_encode( $value )
			);
		}

		$json_keys = [
			'conditions', 'weightConditions', 'prevMeds', 'prevWeights',
			'bariatricDetails', 'mentalHealthDetails', 'otherConditions',
			'currentMeds', 'currentMedsList',
			'allergies', 'allergiesList',
		];
		foreach ( $json_keys as $key ) {
			if ( ! isset( $elig[ $key ] ) ) {
				continue;
			}
			$order->update_meta_data(
				self::ORDER_META_PREFIX . $key,
				is_scalar( $elig[ $key ] ) ? sanitize_text_field( (string) $elig[ $key ] ) : wp_json_encode( $elig[ $key ] )
			);
		}

		$order->save();

		if ( $assessment_id ) {
			TC_DB::attach_order( $assessment_id, $order->get_id() );
			TC_Log::info( 'order_linked_to_assessment', [
				'order_id'      => $order->get_id(),
				'assessment_id' => $assessment_id,
			] );
		}
	}

	public function validate_add_to_cart( $passed, $product_id, $qty ) {
		if ( is_admin() ) {
			return $passed;
		}

		if ( get_option( 'tc_eligibility_block_direct_add_to_cart', '1' ) !== '1' ) {
			return $passed;
		}

		$is_ajax     = function_exists( 'wp_doing_ajax' ) && wp_doing_ajax();
		$ajax_action = $_REQUEST['action'] ?? '';

		if ( $is_ajax && $ajax_action === 'tc_eligibility_add_to_cart' ) {
			return $passed;
		}

		if ( $is_ajax && in_array( $ajax_action, [ 'rrqr_add_to_cart', 'tc_reorder_add_to_cart' ], true ) ) {
			return $passed;
		}

		if ( ! self::product_requires_assessment( $product_id ) ) {
			return $passed;
		}

		$cookie = TC_Cookie_Store::get();
		if ( empty( $cookie ) || empty( $cookie['assessment_id'] ) ) {
			wc_add_notice( 'Please complete the eligibility assessment before ordering.', 'error' );
			TC_Log::info( 'add_to_cart_blocked_no_assessment', [ 'product_id' => $product_id ] );
			return false;
		}

		return $passed;
	}

	private static function product_requires_assessment( $product_id ) {
		$qualifying = TC_Variation_Map::qualifying_variation_ids();

		if ( in_array( (int) $product_id, $qualifying, true ) ) {
			return true;
		}

		$product = wc_get_product( $product_id );
		if ( $product && $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $child_id ) {
				if ( in_array( (int) $child_id, $qualifying, true ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
