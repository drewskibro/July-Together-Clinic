<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Account {

	const META_LINKED_ASSESSMENT = '_tc_eligibility_assessment_id';

	public static function ensure_account_for( array $payload, $assessment_id ) {
		$email = sanitize_email( $payload['email'] ?? '' );
		if ( ! is_email( $email ) ) {
			return 0;
		}

		$user = get_user_by( 'email', $email );
		if ( $user ) {
			update_user_meta( $user->ID, self::META_LINKED_ASSESSMENT, $assessment_id );
			self::sync_woo_billing( $user->ID, $payload );

			if ( ! is_user_logged_in() ) {
				wp_set_current_user( $user->ID );
				wp_set_auth_cookie( $user->ID, true );
			}

			TC_Log::info( 'account_linked_existing', [ 'user_id' => $user->ID, 'assessment_id' => $assessment_id ] );
			return (int) $user->ID;
		}

		if ( ! function_exists( 'wc_create_new_customer' ) ) {
			return 0;
		}

		$first   = sanitize_text_field( $payload['firstName'] ?? '' );
		$last    = sanitize_text_field( $payload['lastName'] ?? '' );

		$suppress_welcome = function ( $enabled, $object = null ) {
			return false;
		};
		add_filter( 'woocommerce_email_enabled_customer_new_account', $suppress_welcome, 99, 2 );

		$user_id = wc_create_new_customer( $email, '', '', [
			'first_name' => $first,
			'last_name'  => $last,
		] );

		remove_filter( 'woocommerce_email_enabled_customer_new_account', $suppress_welcome, 99 );

		if ( is_wp_error( $user_id ) ) {
			TC_Log::warn( 'account_create_failed', [
				'email' => $email,
				'error' => $user_id->get_error_message(),
			] );
			return 0;
		}

		update_user_meta( $user_id, self::META_LINKED_ASSESSMENT, $assessment_id );
		self::sync_woo_billing( $user_id, $payload );

		if ( ! is_user_logged_in() ) {
			wc_set_customer_auth_cookie( $user_id );
		}

		TC_Log::info( 'account_created', [
			'user_id'       => $user_id,
			'email'         => $email,
			'assessment_id' => $assessment_id,
		] );

		return (int) $user_id;
	}

	private static function sync_woo_billing( $user_id, array $payload ) {
		$map = [
			'billing_first_name' => $payload['firstName'] ?? '',
			'billing_last_name'  => $payload['lastName'] ?? '',
			'billing_email'      => $payload['email'] ?? '',
			'billing_phone'      => $payload['phone'] ?? '',
			'billing_address_1'  => $payload['addressLine1'] ?? '',
			'billing_address_2'  => $payload['addressLine2'] ?? '',
			'billing_city'       => $payload['city'] ?? '',
			'billing_postcode'   => $payload['postcode'] ?? '',
			'billing_country'    => self::country_code( $payload['country'] ?? 'United Kingdom' ),
			'shipping_first_name' => $payload['firstName'] ?? '',
			'shipping_last_name'  => $payload['lastName'] ?? '',
			'shipping_address_1'  => $payload['addressLine1'] ?? '',
			'shipping_address_2'  => $payload['addressLine2'] ?? '',
			'shipping_city'       => $payload['city'] ?? '',
			'shipping_postcode'   => $payload['postcode'] ?? '',
			'shipping_country'    => self::country_code( $payload['country'] ?? 'United Kingdom' ),
		];

		foreach ( $map as $key => $value ) {
			if ( $value !== '' ) {
				update_user_meta( $user_id, $key, sanitize_text_field( $value ) );
			}
		}
	}

	public static function country_code( $country ) {
		$map = [
			'united kingdom'   => 'GB',
			'england'          => 'GB',
			'scotland'         => 'GB',
			'wales'            => 'GB',
			'northern ireland' => 'GB',
		];
		$key = strtolower( trim( (string) $country ) );
		return $map[ $key ] ?? 'GB';
	}
}
