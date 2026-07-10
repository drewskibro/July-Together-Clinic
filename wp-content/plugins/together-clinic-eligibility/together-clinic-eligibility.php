<?php
/**
 * Plugin Name:       Together Clinic Eligibility Checker
 * Plugin URI:        https://togetherclinic.co.uk/
 * Description:       Multi-step weight-loss eligibility assessment with WooCommerce checkout integration (block + classic), patient and clinician notifications, and full audit trail.
 * Version:           1.3.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Together Clinic
 * License:           GPL-2.0-or-later
 * Text Domain:       together-clinic-eligibility
 * Domain Path:       /languages
 *
 * WC requires at least: 8.0
 * WC tested up to:      9.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TC_ELIGIBILITY_VERSION', '1.3.0' );
define( 'TC_ELIGIBILITY_FILE', __FILE__ );
define( 'TC_ELIGIBILITY_PATH', plugin_dir_path( __FILE__ ) );
define( 'TC_ELIGIBILITY_URL', plugin_dir_url( __FILE__ ) );
define( 'TC_ELIGIBILITY_BASENAME', plugin_basename( __FILE__ ) );

require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-db.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-log.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-cookie-store.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-dose-ladder.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-variation-map.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-review-status.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-review-order.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-review-actions.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-review-emails.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-review-cron.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-eligibility-rules.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-emails.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-returning-customer.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-my-account.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-ajax.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-checkout.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-checkout-blocks.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-order-admin.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-settings.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-account.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-cron.php';
require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-eligibility-plugin.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once TC_ELIGIBILITY_PATH . 'includes/class-tc-cli.php';
}

register_activation_hook( __FILE__, [ 'TC_DB', 'create_table' ] );
register_activation_hook( __FILE__, [ 'TC_Eligibility_Plugin', 'on_activate' ] );
register_deactivation_hook( __FILE__, [ 'TC_Eligibility_Plugin', 'on_deactivate' ] );

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'together-clinic-eligibility', false, dirname( TC_ELIGIBILITY_BASENAME ) . '/languages' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>Together Clinic Eligibility Checker</strong> requires WooCommerce to be installed and active.</p></div>';
		} );
		return;
	}

	TC_Eligibility_Plugin::instance();
} );
