<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * The reorder functionality (and ownership of its options, cron and table)
 * moved into the Together Clinic Eligibility Checker plugin. Deleting this
 * shell must NOT purge the module's live data — cleanup only runs if the
 * host plugin is gone too.
 */
if ( file_exists( WP_PLUGIN_DIR . '/together-clinic-eligibility/together-clinic-eligibility.php' ) ) {
	return;
}

if ( ! defined( 'TC_REORDER_UNINSTALL_DROP_TABLE' ) ) {
	define( 'TC_REORDER_UNINSTALL_DROP_TABLE', false );
}

if ( wp_next_scheduled( 'tc_reorder_purge_stale' ) ) {
	wp_clear_scheduled_hook( 'tc_reorder_purge_stale' );
}

$options = [
	'tc_reorder_db_version',
	'tc_reorder_enforce_login',
	'tc_reorder_retention_days',
];

foreach ( $options as $opt ) {
	delete_option( $opt );
}

if ( TC_REORDER_UNINSTALL_DROP_TABLE ) {
	global $wpdb;
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'tc_reorder_submissions' );
}
