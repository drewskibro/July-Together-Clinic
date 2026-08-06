<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The payment PORT — the single seam every processor plugs into.
 *
 * This is the whole point of "provider-agnostic from day one": the clinical
 * flow, the review queue and the UX speak only to this interface. Stripe is one
 * implementation; Adyen, Braintree, Square or a backup processor are just more
 * implementations of the same three verbs. Swapping (or adding a failover)
 * processor means writing one new class here — nothing else changes.
 *
 * The model is the universal auth-then-capture primitive, not a Stripe feature:
 *
 *   authorize()  place a hold on the card — money reserved, NOT taken
 *   capture()    take the previously-held funds (on prescriber approval)
 *   void()       release the hold (on rejection / timeout) — no money moves
 *
 * Contract every implementation must honour:
 *  - authorize() is idempotent on TC_Payment_Request::$idempotency_key: the same
 *    key returns the same hold, never a second one.
 *  - capture()/void() are idempotent on the hold reference: repeating a capture
 *    returns captured; repeating a void returns voided.
 *  - Illegal transitions fail cleanly (capture a voided hold → FAILED), never
 *    throw past the boundary.
 *  - Amounts are always in the currency's minor unit.
 *  - is_configured() is honest: false whenever credentials are missing or the
 *    provider must not run here, so the registry can fail closed rather than
 *    silently take (or fake) a real payment.
 */
interface TC_Payment_Provider {

	/** Stable machine id, e.g. 'stripe', 'fake', 'adyen'. */
	public function id(): string;

	/** Human-readable name for the settings screen. */
	public function label(): string;

	/** True only when this provider is fully credentialed and permitted to run in this environment. */
	public function is_configured(): bool;

	/**
	 * Place a hold. Returns AUTHORIZED when the hold is live, REQUIRES_ACTION
	 * (with a client_secret) when the client must complete SCA/3-D Secure first,
	 * or FAILED. Must be idempotent on the request's idempotency key.
	 */
	public function authorize( TC_Payment_Request $request ): TC_Payment_Result;

	/**
	 * Capture a live hold, optionally for less than the authorised amount
	 * (null = the full amount). Idempotent: capturing an already-captured hold
	 * returns CAPTURED.
	 */
	public function capture( string $hold_ref, ?int $amount_minor = null ): TC_Payment_Result;

	/**
	 * Release a hold. Idempotent: voiding an already-voided hold returns VOIDED.
	 * Voiding a captured hold FAILS (use a refund path for that, added with the
	 * first real adapter).
	 */
	public function void( string $hold_ref ): TC_Payment_Result;

	/**
	 * Fetch the current state of a hold straight from the provider —
	 * reconciliation for after a client-side confirmation, or when a webhook is
	 * delayed or missed. Returns the mapped current status, or FAILED if the hold
	 * cannot be found.
	 */
	public function retrieve( string $hold_ref ): TC_Payment_Result;
}
