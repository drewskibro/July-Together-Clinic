<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Order_Admin {

	public function __construct() {
		add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'render' ], 20 );
	}

	public function render( $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$raw = $order->get_meta( TC_Checkout::ORDER_META_RAW );
		if ( ! $raw ) {
			return;
		}

		$payload = json_decode( $raw, true );
		if ( ! is_array( $payload ) ) {
			return;
		}

		$assessment_id = (string) $order->get_meta( TC_Checkout::ORDER_META_ASSESSMENT_ID );

		$path = TC_ELIGIBILITY_PATH . 'templates/admin-order-meta.php';
		if ( file_exists( $path ) ) {
			include $path;
		}
	}
}
