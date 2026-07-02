<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Reorder_Cron {

	const HOOK_PURGE = 'tc_reorder_purge_stale';

	public function __construct() {
		add_action( self::HOOK_PURGE, [ __CLASS__, 'run_purge' ] );
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::HOOK_PURGE ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK_PURGE );
		}
	}

	public static function unschedule() {
		$ts = wp_next_scheduled( self::HOOK_PURGE );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::HOOK_PURGE );
		}
	}

	public static function run_purge() {
		$days    = (int) get_option( 'tc_reorder_retention_days', 30 );
		$deleted = TC_Reorder_DB::purge_stale( $days );
		TC_Reorder_Log::info( 'cron_purge_complete', [ 'deleted' => $deleted, 'days' => $days ] );
		return $deleted;
	}
}
