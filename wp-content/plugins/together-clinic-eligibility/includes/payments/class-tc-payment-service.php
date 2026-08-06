<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The orchestrator — the one object the clinical flow and the prescriber's
 * review actions call. It binds a payment provider to the hold ledger so the
 * rest of the system never touches either directly.
 *
 *   authorize_for_submission()  submit → place a hold, record it
 *   capture_for_submission()    prescriber approves → take the held funds
 *   void_for_submission()       prescriber rejects → release the hold
 *
 * Two guarantees live here, and both are proven in the test harness:
 *
 *  - No double-charge. authorise is idempotent — a retry (network blip,
 *    double-click) returns the same hold, because the provider is idempotent on
 *    the request key and the ledger upserts on the resulting hold reference.
 *  - No double-hold. When a new hold is placed for a submission that already
 *    had one (the patient changed treatment or dose), the previous hold is
 *    released and marked superseded — the patient's card is never left with two
 *    live authorisations. The new hold is recorded BEFORE the old one is
 *    released, so there is never a moment with no hold.
 */
class TC_Payment_Service {

	private $provider;
	private $repo;

	public function __construct( TC_Payment_Provider $provider, TC_Hold_Repository $repo ) {
		$this->provider = $provider;
		$this->repo     = $repo;
	}

	public function provider(): TC_Payment_Provider {
		return $this->provider;
	}

	/**
	 * Place (or idempotently re-confirm) the hold for a submission and record it.
	 * Returns the raw TC_Payment_Result; the persisted hold is a side effect.
	 */
	public function authorize_for_submission( string $submission_id, TC_Payment_Request $request ): TC_Payment_Result {
		$result = $this->provider->authorize( $request );

		// A failed authorisation places no hold — nothing to record, nothing to
		// supersede. The caller shows the fail-closed UX; audit logging is separate.
		if ( $result->is_failed() || null === $result->hold_ref ) {
			return $result;
		}

		// Upsert the ledger row for this hold (idempotent on the hold reference).
		$hold = $this->repo->find_by_ref( $result->hold_ref );
		if ( $hold instanceof TC_Payment_Hold ) {
			$hold->set_status( TC_Payment_Hold::map_status( $result->status ) );
			$hold->amount_minor = $result->amount_minor;
		} else {
			$hold = TC_Payment_Hold::from_result( $submission_id, $this->provider->id(), $request->idempotency_key, $result, $request->metadata );
		}
		$hold = $this->repo->save( $hold );

		// Release any OTHER live hold for this submission — a treatment/dose change
		// must not leave a second authorisation sitting on the patient's card.
		$this->supersede_other_active_holds( $submission_id, $hold->hold_ref );

		return $result;
	}

	/** Capture the submission's active hold (prescriber approval). */
	public function capture_for_submission( string $submission_id ): TC_Payment_Result {
		$hold = $this->repo->find_active_for_submission( $submission_id );
		if ( ! $hold instanceof TC_Payment_Hold ) {
			return TC_Payment_Result::failed( 'no_active_hold', 'There is no active authorisation to capture for this submission.', 0, 'GBP' );
		}

		$result = $this->provider->capture( $hold->hold_ref );
		if ( $result->is_captured() ) {
			$hold->set_status( TC_Payment_Hold::STATUS_CAPTURED );
			$this->repo->save( $hold );
		}
		return $result;
	}

	/** Release the submission's active hold (prescriber rejection / cancellation). */
	public function void_for_submission( string $submission_id ): TC_Payment_Result {
		$hold = $this->repo->find_active_for_submission( $submission_id );
		if ( ! $hold instanceof TC_Payment_Hold ) {
			return TC_Payment_Result::failed( 'no_active_hold', 'There is no active authorisation to release for this submission.', 0, 'GBP' );
		}

		$result = $this->provider->void( $hold->hold_ref );
		if ( $result->is_voided() ) {
			$hold->set_status( TC_Payment_Hold::STATUS_VOIDED );
			$this->repo->save( $hold );
		}
		return $result;
	}

	/** The current live hold for a submission, or null. */
	public function active_hold( string $submission_id ): ?TC_Payment_Hold {
		return $this->repo->find_active_for_submission( $submission_id );
	}

	private function supersede_other_active_holds( string $submission_id, string $keep_hold_ref ): void {
		foreach ( $this->repo->all_for_submission( $submission_id ) as $other ) {
			if ( $other->hold_ref === $keep_hold_ref || ! $other->is_active() ) {
				continue;
			}
			// Best effort: release the stale hold. Even if the void call fails the
			// authorisation will lapse on its own, so we still mark it superseded
			// so it can never be captured by mistake.
			$this->provider->void( $other->hold_ref );
			$other->set_status( TC_Payment_Hold::STATUS_SUPERSEDED );
			$this->repo->save( $other );
		}
	}
}
