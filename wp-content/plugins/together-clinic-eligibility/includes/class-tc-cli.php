<?php
if ( ! defined( 'ABSPATH' ) || ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	return;
}

class TC_Eligibility_CLI {

	/**
	 * Show submission counts by status.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tc-eligibility status
	 */
	public function status() {
		$counts = TC_DB::count_by_status();
		$rows   = [];
		foreach ( [ 'partial', 'eligible', 'ineligible', 'order_placed' ] as $status ) {
			$rows[] = [ 'status' => $status, 'count' => (int) ( $counts[ $status ] ?? 0 ) ];
		}
		WP_CLI\Utils\format_items( 'table', $rows, [ 'status', 'count' ] );
	}

	/**
	 * Purge abandoned submissions older than retention window.
	 *
	 * ## OPTIONS
	 *
	 * [--days=<days>]
	 * : Retention window. Defaults to setting (30).
	 *
	 * ## EXAMPLES
	 *
	 *     wp tc-eligibility purge-stale
	 *     wp tc-eligibility purge-stale --days=14
	 */
	public function purge_stale( $args, $assoc ) {
		$days    = isset( $assoc['days'] ) ? (int) $assoc['days'] : (int) get_option( 'tc_eligibility_retention_days', 30 );
		$deleted = TC_DB::purge_stale( $days );
		WP_CLI::success( sprintf( 'Deleted %d stale submissions older than %d days.', $deleted, $days ) );
	}

	/**
	 * Re-send the patient confirmation email for a submission.
	 *
	 * ## OPTIONS
	 *
	 * <assessment_id>
	 * : The UUID of the assessment.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tc-eligibility resend-confirmation 12345678-1234-1234-1234-123456789abc
	 */
	public function resend_confirmation( $args ) {
		list( $assessment_id ) = $args;

		$row = TC_DB::get_by_assessment_id( $assessment_id );
		if ( ! $row ) {
			WP_CLI::error( 'Assessment not found.' );
		}

		$payload = json_decode( $row['raw_payload'] ?? '', true );
		if ( ! is_array( $payload ) ) {
			$payload = [
				'firstName' => $row['first_name'],
				'lastName'  => $row['last_name'],
				'email'     => $row['email'],
			];
		}

		$sent = TC_Emails::send_patient_confirmation( $payload, $assessment_id );
		if ( $sent ) {
			WP_CLI::success( 'Confirmation email re-sent.' );
		} else {
			WP_CLI::error( 'wp_mail() returned false. Check error log.' );
		}
	}
}

WP_CLI::add_command( 'tc-eligibility', 'TC_Eligibility_CLI' );
