<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Reorder_Plugin {

	const SHORTCODE = 'tc_reorder_form';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		TC_Reorder_DB::maybe_upgrade();

		new TC_Reorder_Ajax();
		new TC_Reorder_Checkout();
		new TC_Reorder_Cron();

		add_shortcode( self::SHORTCODE, [ $this, 'render_wizard' ] );

		add_filter( 'body_class', [ $this, 'add_body_class' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend' ] );
		add_action( 'template_redirect',  [ $this, 'enforce_reorder_access' ], 5 );
	}

	public static function on_activate() {
		TC_Reorder_DB::create_table();

		if ( get_option( 'tc_reorder_enforce_login', null ) === null ) {
			add_option( 'tc_reorder_enforce_login', '1' );
		}

		if ( get_option( 'tc_reorder_retention_days', null ) === null ) {
			add_option( 'tc_reorder_retention_days', 30 );
		}

		TC_Reorder_Cron::schedule();

		TC_Reorder_Log::info( 'plugin_activated', [ 'version' => TC_REORDER_VERSION ] );
	}

	public static function on_deactivate() {
		TC_Reorder_Cron::unschedule();
		TC_Reorder_Log::info( 'plugin_deactivated' );
	}

	public function add_body_class( $classes ) {
		if ( $this->is_reorder_page() ) {
			$classes[] = 'tc-reorder-page';
		}
		return $classes;
	}

	public function render_wizard( $atts = [] ) {
		$atts = shortcode_atts( [], $atts, self::SHORTCODE );

		if ( ! is_user_logged_in() ) {
			$login_url = add_query_arg(
				[ 'redirect_to' => get_permalink() ],
				wc_get_page_permalink( 'myaccount' )
			);
			return sprintf(
				'<div class="tc-reorder tc-reorder--guest"><p>Please <a href="%s">log in</a> to reorder your treatment.</p></div>',
				esc_url( $login_url )
			);
		}

		$user_id = get_current_user_id();
		$prefill = TC_Reorder_Prefill::for_user( $user_id );

		$is_admin_preview = self::is_admin_preview();

		if ( ( ! $prefill || ! $prefill['has_previous_order'] ) && ! $is_admin_preview ) {
			$assessment_url = $this->assessment_url();
			return sprintf(
				'<div class="tc-reorder tc-reorder--noorders"><p>We can\'t find a previous weight loss treatment on your account. Please <a href="%s">start a new assessment</a> to begin.</p></div>',
				esc_url( $assessment_url )
			);
		}

		if ( $is_admin_preview && ( ! $prefill || ! $prefill['has_previous_order'] ) ) {
			$current_user = wp_get_current_user();
			$prefill = [
				'user_id'             => $user_id,
				'first_name'          => $current_user->first_name ?: 'Preview',
				'last_name'           => $current_user->last_name ?: 'Mode',
				'email'               => $current_user->user_email,
				'previous_order_id'   => 0,
				'previous_medication' => 'wegovy',
				'previous_dose'       => '0.25mg',
				'has_previous_order'  => true,
			];

			echo '<div style="background:#fef3c7;border:1px solid #f59e0b;padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;color:#92400e;">⚠️ <strong>Admin preview mode</strong> — synthetic placeholder data is being used. Add a real order to test with real prefill data.</div>';
		}

		ob_start();
		include TC_REORDER_PATH . 'templates/wizard.php';
		return ob_get_clean();
	}

	public static function is_admin_preview() {
		if ( empty( $_GET['preview_reorder'] ) ) {
			return false;
		}
		return current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
	}

	public function enforce_reorder_access() {
		if ( ! $this->is_reorder_page() ) {
			return;
		}

		nocache_headers();

		if ( get_option( 'tc_reorder_enforce_login', '1' ) !== '1' ) {
			return;
		}

		if ( self::is_admin_preview() ) {
			TC_Reorder_Log::info( 'reorder_admin_preview_mode', [
				'user_id' => get_current_user_id(),
			] );
			return;
		}

		if ( ! is_user_logged_in() ) {
			$account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : site_url( '/my-account/' );
			$login_url   = add_query_arg(
				[ 'redirect_to' => get_permalink( get_queried_object_id() ) ],
				$account_url
			);
			TC_Reorder_Log::info( 'reorder_redirect_to_login', [
				'page_id' => get_queried_object_id(),
			] );
			wp_safe_redirect( $login_url );
			exit;
		}

		$user_id = get_current_user_id();
		$prefill = TC_Reorder_Prefill::for_user( $user_id );

		if ( ! $prefill || ! $prefill['has_previous_order'] ) {
			$assessment_url = $this->assessment_url( true );
			TC_Reorder_Log::info( 'reorder_redirect_to_assessment', [
				'user_id' => $user_id,
				'reason'  => 'no_previous_order',
			] );
			wp_safe_redirect( $assessment_url );
			exit;
		}
	}

	private function is_reorder_page() {
		$post = get_post();
		if ( ! $post ) {
			return false;
		}
		return has_shortcode( $post->post_content, self::SHORTCODE );
	}

	public function enqueue_frontend() {
		if ( ! $this->is_reorder_page() ) {
			return;
		}

		nocache_headers();

		// Fonts are self-hosted (see assets/fonts + assets/css/fonts.css) so that
		// loading the reorder page never sends a patient's IP address to Google.
		wp_enqueue_style(
			'tc-reorder-fonts',
			TC_REORDER_URL . 'assets/css/fonts.css',
			[],
			TC_REORDER_VERSION
		);

		wp_enqueue_style(
			'tc-reorder',
			TC_REORDER_URL . 'assets/css/reorder.css',
			[ 'tc-reorder-fonts' ],
			TC_REORDER_VERSION
		);

		wp_add_inline_style( 'tc-reorder', '
			.tc-reorder-page h1 { display: none !important; }
			.tc-reorder-page .tc-reorder h1 { display: block !important; }
			.tc-reorder-page .entry-title,
			.tc-reorder-page .page-title,
			.tc-reorder-page .post-title,
			.tc-reorder-page .wp-block-post-title,
			.tc-reorder-page .page-header,
			.tc-reorder-page header.entry-header,
			.tc-reorder-page .entry-header { display: none !important; }
		' );

		wp_enqueue_script(
			'tc-reorder',
			TC_REORDER_URL . 'assets/js/reorder.js',
			[],
			TC_REORDER_VERSION,
			true
		);

		$user_id = get_current_user_id();
		$prefill = $user_id ? TC_Reorder_Prefill::for_user( $user_id ) : null;

		if ( self::is_admin_preview() && ( ! $prefill || ! $prefill['has_previous_order'] ) ) {
			$current_user = wp_get_current_user();
			$prefill = [
				'user_id'             => $user_id,
				'first_name'          => $current_user->first_name ?: 'Preview',
				'last_name'           => $current_user->last_name ?: 'Mode',
				'email'               => $current_user->user_email,
				'previous_order_id'   => 0,
				'previous_medication' => 'wegovy',
				'previous_dose'       => '0.25mg',
				'has_previous_order'  => true,
			];
		}

		$dose_options_with_prices = [];
		if ( $prefill && $prefill['previous_medication'] ) {
			$dose_options_with_prices = TC_Reorder_Pricing::get_dose_options_with_prices( $prefill['previous_medication'] );
		}

		wp_localize_script( 'tc-reorder', 'tcReorder', [
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( TC_Reorder_Ajax::NONCE_ACTION ),
			'cookieName'   => TC_Reorder_Cookie_Store::COOKIE_NAME,
			'cookieMaxAge' => TC_Reorder_Cookie_Store::COOKIE_LIFETIME,
			'checkoutUrl'  => wc_get_checkout_url(),
			'homeUrl'      => home_url( '/' ),
			'assessmentUrl' => $this->assessment_url( true ),
			'currency'     => get_woocommerce_currency_symbol(),
			'prefill'      => $prefill ? [
				'firstName'           => $prefill['first_name'],
				'lastName'            => $prefill['last_name'],
				'email'               => $prefill['email'],
				'previousMedication'  => $prefill['previous_medication'],
				'previousDose'        => $prefill['previous_dose'],
				'previousOrderId'     => $prefill['previous_order_id'],
			] : null,
			'doseOptions'  => $dose_options_with_prices,
			'assets'       => [
				'wegovy'   => TC_REORDER_URL . 'assets/img/wegovy.jpg',
				'mounjaro' => TC_REORDER_URL . 'assets/img/mounjaro.png',
				'logo'     => TC_REORDER_URL . 'assets/img/together-clinic-logo.png',
			],
		] );
	}

	private function assessment_url( $force_assessment = false ) {
		$page_id = (int) get_option( 'tc_eligibility_assessment_page_id', 0 );
		$url = $page_id ? get_permalink( $page_id ) : home_url( '/weight-loss-eligibility/' );
		if ( $force_assessment ) {
			$url = add_query_arg( 'force_assessment', '1', $url );
		}
		return $url;
	}
}
