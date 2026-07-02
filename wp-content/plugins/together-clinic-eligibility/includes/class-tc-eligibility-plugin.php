<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Eligibility_Plugin {

	const SHORTCODE = 'tc_eligibility_wizard';
	const SHORTCODE_BUTTON = 'tc_eligibility_button';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		TC_DB::maybe_upgrade();

		new TC_Ajax();
		new TC_Checkout();
		new TC_Checkout_Blocks();
		new TC_Order_Admin();
		new TC_Settings();
		new TC_Cron();
		new TC_My_Account();

		add_shortcode( self::SHORTCODE, [ $this, 'render_wizard' ] );
		add_shortcode( self::SHORTCODE_BUTTON, [ $this, 'render_button' ] );

		add_filter( 'body_class', [ $this, 'add_body_class' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_button_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_checkout' ] );

		add_action( 'woocommerce_thankyou', [ $this, 'output_calendly_swap' ], 5, 1 );
	}

	public static function on_activate() {
		TC_DB::create_table();

		$defaults = [
			'tc_eligibility_from_email'                         => 'care@togetherclinic.co.uk',
			'tc_eligibility_from_name'                          => 'Together Clinic',
			'tc_eligibility_clinician_recipients'               => 'ahmed@at-health.co.uk,care@togetherclinic.co.uk',
			'tc_eligibility_send_clinician_emails'              => '1',
			'tc_eligibility_enforce_assessment_before_checkout' => '1',
			'tc_eligibility_block_direct_add_to_cart'           => '1',
			'tc_eligibility_min_bmi_default'                    => 27,
			'tc_eligibility_min_bmi_south_asian'                => 23,
			'tc_eligibility_retention_days'                     => 30,
		];

		foreach ( $defaults as $key => $value ) {
			if ( get_option( $key, null ) === null ) {
				add_option( $key, $value );
			}
		}

		TC_Cron::schedule();

		TC_Log::info( 'plugin_activated', [ 'version' => TC_ELIGIBILITY_VERSION ] );
	}

	public static function on_deactivate() {
		TC_Cron::unschedule();
		TC_Log::info( 'plugin_deactivated' );
	}

	public function add_body_class( $classes ) {
		if ( $this->is_assessment_page() ) {
			$classes[] = 'tc-eligibility-page';
		}
		return $classes;
	}

	public function render_wizard( $atts = [] ) {
		$atts = shortcode_atts( [], $atts, self::SHORTCODE );

		ob_start();
		include TC_ELIGIBILITY_PATH . 'templates/wizard.php';
		return ob_get_clean();
	}

	public function render_button( $atts = [] ) {
		$atts = shortcode_atts( [
			'text'  => 'Start your assessment',
			'class' => '',
			'align' => '',
		], $atts, self::SHORTCODE_BUTTON );

		$page_id = (int) get_option( 'tc_eligibility_assessment_page_id', 0 );
		if ( ! $page_id ) {
			return '';
		}

		$wrapper_style = '';
		if ( $atts['align'] === 'center' ) {
			$wrapper_style = 'text-align: center;';
		} elseif ( $atts['align'] === 'right' ) {
			$wrapper_style = 'text-align: right;';
		}

		$wrapper_open  = $wrapper_style ? '<div style="' . esc_attr( $wrapper_style ) . '">' : '';
		$wrapper_close = $wrapper_style ? '</div>' : '';

		return sprintf(
			'%s<a href="%s" class="tc-eligibility-cta %s">%s &rarr;</a>%s',
			$wrapper_open,
			esc_url( get_permalink( $page_id ) ),
			esc_attr( $atts['class'] ),
			esc_html( $atts['text'] ),
			$wrapper_close
		);
	}

	public function enqueue_button_styles() {
		global $post;
		if ( ! $post || $this->is_assessment_page() ) {
			return;
		}
		if ( ! has_shortcode( $post->post_content, self::SHORTCODE_BUTTON ) ) {
			return;
		}
		wp_enqueue_style(
			'tc-eligibility',
			TC_ELIGIBILITY_URL . 'assets/css/eligibility.css',
			[],
			TC_ELIGIBILITY_VERSION
		);
	}

	private function is_assessment_page() {
		$assessment_page_id = (int) get_option( 'tc_eligibility_assessment_page_id', 0 );
		if ( $assessment_page_id && is_page( $assessment_page_id ) ) {
			return true;
		}

		$post = get_post();
		if ( $post && has_shortcode( $post->post_content, self::SHORTCODE ) ) {
			return true;
		}

		return false;
	}

	public function enqueue_frontend() {
		if ( ! $this->is_assessment_page() ) {
			return;
		}

		nocache_headers();

		wp_enqueue_style(
			'tc-eligibility',
			TC_ELIGIBILITY_URL . 'assets/css/eligibility.css',
			[],
			TC_ELIGIBILITY_VERSION
		);

		wp_add_inline_style( 'tc-eligibility', '
			.tc-eligibility-page h1 { display: none !important; }
			.tc-eligibility-page .tc-eligibility h1 { display: block !important; }
			.tc-eligibility-page .entry-title,
			.tc-eligibility-page .page-title,
			.tc-eligibility-page .post-title,
			.tc-eligibility-page .wp-block-post-title,
			.tc-eligibility-page .page-header,
			.tc-eligibility-page header.entry-header,
			.tc-eligibility-page .entry-header,
			.tc-eligibility-page .single-page-title,
			.tc-eligibility-page .post-header,
			.tc-eligibility-page .post-header__title,
			.tc-eligibility-page .heading-primary,
			.tc-eligibility-page .page-banner,
			.tc-eligibility-page .page-banner__title,
			.tc-eligibility-page .hero-title,
			.tc-eligibility-page .banner-title,
			.tc-eligibility-page .title-section,
			.tc-eligibility-page .section-title,
			.tc-eligibility-page main > header,
			.tc-eligibility-page article > header,
			.tc-eligibility-page .content-area > header { display: none !important; }
		' );

		wp_enqueue_script(
			'tc-eligibility',
			TC_ELIGIBILITY_URL . 'assets/js/eligibility.js',
			[],
			TC_ELIGIBILITY_VERSION,
			true
		);

		wp_localize_script( 'tc-eligibility', 'tcEligibility', [
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( TC_Ajax::NONCE_ACTION ),
			'cookieName'      => TC_Cookie_Store::COOKIE_NAME,
			'cookieMaxAge'    => TC_Cookie_Store::COOKIE_LIFETIME,
			'checkoutUrl'     => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
			'homeUrl'         => home_url( '/' ),
			'minBmiDefault'   => (float) get_option( 'tc_eligibility_min_bmi_default', 27 ),
			'minBmiAsian'     => (float) get_option( 'tc_eligibility_min_bmi_south_asian', 23 ),
			'assets'          => [
				'wegovy'   => TC_ELIGIBILITY_URL . 'assets/img/wegovy.jpg',
				'mounjaro' => TC_ELIGIBILITY_URL . 'assets/img/mounjaro.png',
			],
		] );
	}

	public function enqueue_checkout() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}

		wp_enqueue_script(
			'tc-checkout-debounce',
			TC_ELIGIBILITY_URL . 'assets/js/checkout-debounce.js',
			[],
			TC_ELIGIBILITY_VERSION,
			true
		);

		$prefill = $this->build_checkout_prefill();

		if ( $this->checkout_is_block() ) {
			wp_enqueue_script(
				'tc-checkout-blocks',
				TC_ELIGIBILITY_URL . 'assets/js/checkout-prefill-blocks.js',
				[ 'wp-data', 'wc-blocks-checkout' ],
				TC_ELIGIBILITY_VERSION,
				true
			);
			wp_localize_script( 'tc-checkout-blocks', 'tcCheckoutPrefill', $prefill );
		} else {
			wp_enqueue_script(
				'tc-checkout-classic',
				TC_ELIGIBILITY_URL . 'assets/js/checkout-prefill-classic.js',
				[ 'jquery' ],
				TC_ELIGIBILITY_VERSION,
				true
			);
			wp_localize_script( 'tc-checkout-classic', 'tcCheckoutPrefill', $prefill );
		}
	}

	private function build_checkout_prefill() {
		$data = TC_Cookie_Store::get();
		if ( empty( $data ) ) {
			return [ 'hasData' => false ];
		}

		$first_name = $data['firstName'] ?? '';
		$last_name  = $data['lastName'] ?? '';
		if ( ! $first_name && ! empty( $data['fullName'] ) ) {
			list( $first_name, $last_name ) = TC_Cookie_Store::split_full_name( $data['fullName'] );
		}

		return [
			'hasData'   => true,
			'firstName' => (string) $first_name,
			'lastName'  => (string) ( $last_name ?: $first_name ),
			'email'     => (string) ( $data['email'] ?? '' ),
			'phone'     => (string) ( $data['phone'] ?? '' ),
			'address_1' => (string) ( $data['addressLine1'] ?? '' ),
			'address_2' => (string) ( $data['addressLine2'] ?? '' ),
			'city'      => (string) ( $data['city'] ?? '' ),
			'postcode'  => (string) ( $data['postcode'] ?? '' ),
			'country'   => TC_Account::country_code( $data['country'] ?? 'United Kingdom' ),
		];
	}

	private function checkout_is_block() {
		$post = get_post();
		if ( ! $post ) {
			return false;
		}
		return has_block( 'woocommerce/checkout', $post );
	}

	public function output_calendly_swap( $order_id ) {
		if ( ! $order_id ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$user_type = (string) $order->get_meta( TC_Checkout::ORDER_META_PREFIX . 'userType' );
		if ( ! $user_type ) {
			$raw = $order->get_meta( TC_Checkout::ORDER_META_RAW );
			if ( $raw ) {
				$decoded   = json_decode( $raw, true );
				$user_type = is_array( $decoded ) ? ( $decoded['userType'] ?? 'new' ) : 'new';
			}
		}

		$url = '';
		if ( $user_type === 'switching' ) {
			$url = get_option( 'tc_eligibility_calendly_switching', '' );
		} elseif ( in_array( $user_type, [ 'returning', 'reorder', 'existing' ], true ) ) {
			$url = get_option( 'tc_eligibility_calendly_returning', '' );
		} else {
			$url = get_option( 'tc_eligibility_calendly_new', '' );
		}

		if ( ! $url ) {
			return;
		}

		?>
		<script type="text/javascript">
		(function () {
			var target = <?php echo wp_json_encode( esc_url_raw( $url ) ); ?>;

			function swap() {
				document.querySelectorAll('a[href*="calendly.com"], button[onclick*="calendly"], iframe[src*="calendly.com"]').forEach(function (el) {
					if (el.tagName === 'A') {
						el.setAttribute('href', target);
						if (!el.getAttribute('target')) el.setAttribute('target', '_blank');
					} else if (el.tagName === 'IFRAME') {
						el.setAttribute('src', target);
					} else {
						var oc = el.getAttribute('onclick') || '';
						el.setAttribute('onclick', oc.replace(/https?:\/\/calendly\.com\/[^'"\)]+/gi, target));
					}
				});
			}

			swap();
			setTimeout(swap, 500);
			setTimeout(swap, 1500);
		})();
		</script>
		<?php
	}
}
