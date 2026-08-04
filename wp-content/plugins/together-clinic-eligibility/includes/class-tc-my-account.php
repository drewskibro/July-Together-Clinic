<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_My_Account {

	public function __construct() {
		add_action( 'template_redirect', [ $this, 'redirect_shop' ], 10 );
		add_filter( 'woocommerce_return_to_shop_redirect', [ $this, 'filter_return_url' ] );
		add_filter( 'gettext', [ $this, 'filter_strings' ], 20, 3 );

		// Reorder discoverability: a "Reorder" item in the My Account menu and a
		// dashboard call-to-action, shown only to returning customers (a patient
		// whose first order has reached the pharmacy). The link resolves to the
		// reorder page — a label-only menu item whose URL we rewrite, so there is
		// no rewrite endpoint to register and no template to duplicate.
		add_filter( 'woocommerce_account_menu_items', [ $this, 'add_reorder_menu_item' ] );
		add_filter( 'woocommerce_get_endpoint_url', [ $this, 'reorder_menu_url' ], 10, 4 );
		add_action( 'woocommerce_account_dashboard', [ $this, 'dashboard_reorder_cta' ], 5 );
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

	/**
	 * Add a "Reorder" item to the My Account menu, spliced in immediately after
	 * "Orders". Shown only to returning customers — a non-returning or logged-out
	 * user would only be bounced to the assessment by the reorder page's own
	 * access guard, so gating on the same flag that guard uses keeps the menu
	 * honest and avoids a pointless redirect.
	 */
	public function add_reorder_menu_item( $items ) {
		if ( ! $this->is_returning_customer() ) {
			return $items;
		}

		$reordered = [];
		foreach ( $items as $key => $label ) {
			$reordered[ $key ] = $label;
			if ( 'orders' === $key ) {
				$reordered['tc-reorder'] = 'Reorder';
			}
		}
		if ( ! isset( $reordered['tc-reorder'] ) ) {
			$reordered['tc-reorder'] = 'Reorder';
		}

		return $reordered;
	}

	/**
	 * Point the "tc-reorder" menu item at the reorder page. It is a label-only
	 * pseudo-endpoint (never registered as a real WooCommerce endpoint), so its
	 * link is rewritten here to the canonical reorder URL rather than resolving
	 * to /my-account/tc-reorder/.
	 */
	public function reorder_menu_url( $url, $endpoint, $value, $permalink ) {
		if ( 'tc-reorder' === $endpoint ) {
			return TC_Checkout::reorder_url();
		}
		return $url;
	}

	/** A reorder call-to-action at the top of the My Account dashboard. */
	public function dashboard_reorder_cta() {
		if ( ! $this->is_returning_customer() ) {
			return;
		}

		$url = TC_Checkout::reorder_url();
		?>
		<div style="background:#f7f4f9;border:1px solid #e5e0ef;border-left:4px solid #7d76ba;padding:16px 20px;margin:0 0 24px;border-radius:6px;">
			<h3 style="margin:0 0 6px;">Need a repeat prescription?</h3>
			<p style="margin:0 0 12px;">Reorder your treatment in a couple of clicks — a prescriber reviews every request before it is dispensed.</p>
			<a class="button" href="<?php echo esc_url( $url ); ?>" style="background:#7d76ba;color:#fff;">Reorder now</a>
		</div>
		<?php
	}

	private function destination_for_current_user() {
		$assessment_page_id = (int) get_option( 'tc_eligibility_assessment_page_id', 0 );

		$assessment_url = $assessment_page_id ? get_permalink( $assessment_page_id ) : home_url( '/weight-loss-eligibility/' );
		$reorder_url    = TC_Checkout::reorder_url();

		return $this->is_returning_customer() ? $reorder_url : $assessment_url;
	}

	private function is_returning_customer() {
		return is_user_logged_in()
			&& class_exists( 'TC_Returning_Customer' )
			&& TC_Returning_Customer::is_returning();
	}
}
