<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
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
