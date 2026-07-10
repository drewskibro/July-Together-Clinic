<?php
/**
 * Plugin Name:       Together Clinic Reorder (merged into Eligibility Checker)
 * Plugin URI:        https://togetherclinic.co.uk/
 * Description:       This plugin's functionality now lives inside the Together Clinic Eligibility Checker plugin (v2.0.0+) as its reorder module. This shell deactivates itself when the host plugin is active and can then be deleted. Kept for one release as the rollback path.
 * Version:           2.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Together Clinic
 * License:           GPL-2.0-or-later
 * Text Domain:       together-clinic-reorder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * The reorder module (classes, templates, assets, options, table, shortcode,
 * AJAX actions — all names unchanged) is loaded by together-clinic-eligibility
 * since its 2.0.0 release. This file intentionally loads nothing.
 *
 * WordPress loads together-clinic-eligibility before this file (alphabetical),
 * so if the module is present its classes already exist by the time this runs.
 */
add_action( 'admin_init', function () {
	if ( class_exists( 'TC_Reorder_Plugin' ) ) {
		// Host plugin provides the module — retire this shell.
		deactivate_plugins( plugin_basename( __FILE__ ) );
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-info"><p><strong>Together Clinic Reorder</strong> is now part of the Together Clinic Eligibility Checker plugin and has deactivated itself. It is safe to delete this plugin.</p></div>';
		} );
	}
} );

add_action( 'admin_notices', function () {
	if ( class_exists( 'TC_Reorder_Plugin' ) ) {
		return;
	}
	echo '<div class="notice notice-error"><p><strong>Together Clinic Reorder</strong> no longer contains the reorder functionality — it moved into the <strong>Together Clinic Eligibility Checker</strong> plugin (v2.0.0+). Please install/activate that plugin; this shell can then be deleted.</p></div>';
} );
