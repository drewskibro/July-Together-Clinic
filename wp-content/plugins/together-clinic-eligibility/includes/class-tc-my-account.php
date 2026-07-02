<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_My_Account {

	public function __construct() {
		add_action( 'template_redirect', [ $this, 'redirect_shop' ], 10 );
		add_filter( 'woocommerce_return_to_shop_redirect', [ $this, 'filter_return_url' ] );
		add_filter( 'gettext', [ $this, 'filter_strings' ], 20, 3 );
	}

	public function redirect_shop() {
		if ( is_admin() ) {
			return;
		}

		if ( get_option( 'tc_redirect_shop', '1' ) !== '1' ) {
			return;
		}

		if ( ! function_exists( 'is_shop' ) ) {
			return;
		}

		if ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) {
			return;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		nocache_headers();
		TC_Log::info( 'shop_redirect_patient', [
			'is_returning' => $this->is_returning_customer() ? 'yes' : 'no',
		] );

		wp_safe_redirect( $this->destination_for_current_user() );
		exit;
	}

	public function filter_return_url( $url ) {
		return $this->destination_for_current_user();
	}

	public function filter_strings( $translated, $original, $domain ) {
		if ( $domain !== 'woocommerce' ) {
			return $translated;
		}

		if ( $original !== 'Browse products' && $original !== 'No order has been made yet.' ) {
			return $translated;
		}

		$is_returning = $this->is_returning_customer();

		if ( $original === 'Browse products' ) {
			return $is_returning ? 'Reorder now' : 'Start your assessment';
		}

		if ( $original === 'No order has been made yet.' ) {
			return $is_returning
				? 'Ready to reorder your treatment?'
				: 'No orders yet — start with a quick eligibility check.';
		}

		return $translated;
	}

	private function destination_for_current_user() {
		$assessment_page_id = (int) get_option( 'tc_eligibility_assessment_page_id', 0 );
		$reorder_page_id    = (int) get_option( 'tc_reorder_page_id', 0 );

		$assessment_url = $assessment_page_id ? get_permalink( $assessment_page_id ) : home_url( '/weight-loss-eligibility/' );
		$reorder_url    = $reorder_page_id ? get_permalink( $reorder_page_id ) : home_url( '/reorder-now/' );

		return $this->is_returning_customer() ? $reorder_url : $assessment_url;
	}

	private function is_returning_customer() {
		return is_user_logged_in()
			&& class_exists( 'TC_Returning_Customer' )
			&& TC_Returning_Customer::is_returning();
	}
}
