<?php
/**
 * Plugin Name:       Together Clinic Reorder
 * Plugin URI:        https://togetherclinic.co.uk/
 * Description:       Multi-step reorder form for verified returning patients. Pulls medication / dose from previous order, reads live prices from WooCommerce, attaches rrqr_data to cart for the eligibility plugin's checkout bypass.
 * Version:           1.0.6
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Together Clinic
 * License:           GPL-2.0-or-later
 * Text Domain:       together-clinic-reorder
 *
 * WC requires at least: 8.0
 * WC tested up to:      9.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TC_REORDER_VERSION', '1.0.6' );
define( 'TC_REORDER_FILE', __FILE__ );
define( 'TC_REORDER_PATH', plugin_dir_path( __FILE__ ) );
define( 'TC_REORDER_URL', plugin_dir_url( __FILE__ ) );
define( 'TC_REORDER_BASENAME', plugin_basename( __FILE__ ) );

require_once TC_REORDER_PATH . 'includes/class-tc-reorder-log.php';
require_once TC_REORDER_PATH . 'includes/class-tc-reorder-db.php';
require_once TC_REORDER_PATH . 'includes/class-tc-reorder-cookie-store.php';
require_once TC_REORDER_PATH . 'includes/class-tc-reorder-pricing.php';
require_once TC_REORDER_PATH . 'includes/class-tc-reorder-prefill.php';
require_once TC_REORDER_PATH . 'includes/class-tc-reorder-rules.php';
require_once TC_REORDER_PATH . 'includes/class-tc-reorder-ajax.php';
require_once TC_REORDER_PATH . 'includes/class-tc-reorder-checkout.php';
require_once TC_REORDER_PATH . 'includes/class-tc-reorder-cron.php';
require_once TC_REORDER_PATH . 'includes/class-tc-reorder-plugin.php';

register_activation_hook( __FILE__, [ 'TC_Reorder_DB', 'create_table' ] );
register_activation_hook( __FILE__, [ 'TC_Reorder_Plugin', 'on_activate' ] );
register_deactivation_hook( __FILE__, [ 'TC_Reorder_Plugin', 'on_deactivate' ] );

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>Together Clinic Reorder</strong> requires WooCommerce to be installed and active.</p></div>';
		} );
		return;
	}

	TC_Reorder_Plugin::instance();
} );
