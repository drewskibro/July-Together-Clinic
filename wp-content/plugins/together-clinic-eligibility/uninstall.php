<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! defined( 'TC_ELIGIBILITY_UNINSTALL_DROP_TABLE' ) ) {
	define( 'TC_ELIGIBILITY_UNINSTALL_DROP_TABLE', false );
}

foreach ( [ 'tc_eligibility_purge_stale', 'tc_review_daily', 'tc_reorder_purge_stale' ] as $tc_hook ) {
	if ( wp_next_scheduled( $tc_hook ) ) {
		wp_clear_scheduled_hook( $tc_hook );
	}
}

$options = [
	'tc_eligibility_db_version',
	'tc_eligibility_assessment_page_id',
	'tc_eligibility_from_email',
	'tc_eligibility_from_name',
	'tc_eligibility_clinician_recipients',
	'tc_eligibility_send_clinician_emails',
	'tc_eligibility_enforce_assessment_before_checkout',
	'tc_eligibility_block_direct_add_to_cart',
	'tc_eligibility_calendly_new',
	'tc_eligibility_calendly_switching',
	'tc_eligibility_calendly_returning',
	'tc_eligibility_min_bmi_default',
	'tc_eligibility_min_bmi_south_asian',
	'tc_eligibility_retention_days',
	'tc_eligibility_variation_map',
	// Reorder module (folded-in plugin).
	'tc_reorder_db_version',
	'tc_reorder_enforce_login',
	'tc_reorder_retention_days',
];

foreach ( $options as $opt ) {
	delete_option( $opt );
}

if ( TC_ELIGIBILITY_UNINSTALL_DROP_TABLE ) {
	global $wpdb;
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'tc_eligibility_submissions' );
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'tc_reorder_submissions' );
}
