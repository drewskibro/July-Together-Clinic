<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persistence port for payment holds. The service talks only to this, so the
 * ledger can live in a custom table in production and in memory in tests — the
 * orchestration logic never changes.
 */
interface TC_Hold_Repository {

	/** Insert or update (matched on hold_ref). Returns the hold with its id populated. */
	public function save( TC_Payment_Hold $hold ): TC_Payment_Hold;

	/** The hold with this opaque reference, or null. */
	public function find_by_ref( string $hold_ref ): ?TC_Payment_Hold;

	/** The single ACTIVE (authorized) hold for a submission, or null. */
	public function find_active_for_submission( string $submission_id ): ?TC_Payment_Hold;

	/** Every hold ever recorded for a submission (for supersede + audit). @return TC_Payment_Hold[] */
	public function all_for_submission( string $submission_id ): array;
}
