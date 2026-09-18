<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * In-memory hold repository — the reference implementation and the one the test
 * harness uses. Keeps value semantics (stores and returns clones) so callers
 * can't mutate persisted state by holding a reference. The production
 * $wpdb-backed repository implements the same contract.
 */
class TC_Array_Hold_Repository implements TC_Hold_Repository {

	/** @var TC_Payment_Hold[] keyed by hold_ref. */
	private $rows = [];

	private $auto_id = 1;

	public function save( TC_Payment_Hold $hold ): TC_Payment_Hold {
		if ( null === $hold->id ) {
			$hold->id = $this->auto_id++;
		}
		$this->rows[ $hold->hold_ref ] = clone $hold;
		return $hold;
	}

	public function find_by_ref( string $hold_ref ): ?TC_Payment_Hold {
		return isset( $this->rows[ $hold_ref ] ) ? clone $this->rows[ $hold_ref ] : null;
	}

	public function find_active_for_submission( string $submission_id ): ?TC_Payment_Hold {
		foreach ( $this->rows as $hold ) {
			if ( $hold->submission_id === $submission_id && $hold->is_active() ) {
				return clone $hold;
			}
		}
		return null;
	}

	public function all_for_submission( string $submission_id ): array {
		$out = [];
		foreach ( $this->rows as $hold ) {
			if ( $hold->submission_id === $submission_id ) {
				$out[] = clone $hold;
			}
		}
		return $out;
	}
}
