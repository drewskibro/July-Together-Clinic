<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A sandbox provider that implements the full auth-then-capture contract with
 * no external processor — so the entire flow (submit → hold → prescriber
 * review → capture / void) can be built, demoed and tested end-to-end BEFORE
 * any real Stripe keys exist, and against the exact interface the real adapter
 * will implement. When Stripe lands, it drops in beside this and the flow code
 * doesn't change.
 *
 * Safety: this can NEVER be the active provider in production by accident. It
 * reports is_configured() = false unless the site explicitly opts in with the
 * TC_PAYMENTS_ALLOW_FAKE constant (or the matching filter). So a fail-closed
 * registry will refuse to route real payments through a fake — no faked money
 * movement can ship silently.
 *
 * Test hooks (sandbox only): a request whose metadata['test_behavior'] is
 * 'decline' authorises to FAILED, so the fail-closed UX can be exercised.
 *
 * State is keyed by both the idempotency key (to collapse retries) and the hold
 * reference (to resolve capture/void). It persists via options under WordPress
 * and via a static map in a bare-PHP test harness — the logic is identical.
 */
class TC_Fake_Payment_Provider implements TC_Payment_Provider {

	const STORE_PREFIX = 'tc_pay_fake_';

	/** In-memory fallback store for bare-PHP tests (no WordPress present). */
	private static $mem = [];

	public function id(): string {
		return 'fake';
	}

	public function label(): string {
		return 'Sandbox (test only)';
	}

	public function is_configured(): bool {
		$allow = defined( 'TC_PAYMENTS_ALLOW_FAKE' ) && TC_PAYMENTS_ALLOW_FAKE;
		if ( function_exists( 'apply_filters' ) ) {
			$allow = (bool) apply_filters( 'tc_payments_allow_fake', $allow );
		}
		return $allow;
	}

	public function authorize( TC_Payment_Request $request ): TC_Payment_Result {
		// Idempotent: a retry with the same key returns the original hold.
		$existing = $this->read( $request->idempotency_key );
		if ( $existing ) {
			return $this->result_from_state( $existing, true );
		}

		if ( isset( $request->metadata['test_behavior'] ) && 'decline' === $request->metadata['test_behavior'] ) {
			return TC_Payment_Result::failed( 'card_declined', 'The card was declined (sandbox).', $request->amount_minor, $request->currency );
		}

		$hold_ref = 'fake_' . substr( hash( 'sha256', $request->idempotency_key ), 0, 24 );

		$state = [
			'hold_ref'        => $hold_ref,
			'status'          => TC_Payment_Result::AUTHORIZED,
			'amount_minor'    => $request->amount_minor,
			'currency'        => $request->currency,
			'idempotency_key' => $request->idempotency_key,
		];
		$this->persist( $state );

		return TC_Payment_Result::authorized( $hold_ref, $request->amount_minor, $request->currency, [ 'provider' => 'fake' ] );
	}

	public function capture( string $hold_ref, ?int $amount_minor = null ): TC_Payment_Result {
		$state = $this->read( $hold_ref );
		if ( ! $state ) {
			return TC_Payment_Result::failed( 'not_found', 'No such hold to capture.', 0, 'GBP' );
		}
		if ( TC_Payment_Result::CAPTURED === $state['status'] ) {
			return TC_Payment_Result::captured( $hold_ref, $state['amount_minor'], $state['currency'] ); // idempotent
		}
		if ( TC_Payment_Result::AUTHORIZED !== $state['status'] ) {
			return TC_Payment_Result::failed( 'invalid_state', 'Cannot capture a ' . $state['status'] . ' hold.', $state['amount_minor'], $state['currency'] );
		}

		if ( null !== $amount_minor ) {
			$state['amount_minor'] = max( 0, (int) $amount_minor );
		}
		$state['status'] = TC_Payment_Result::CAPTURED;
		$this->persist( $state );

		return TC_Payment_Result::captured( $hold_ref, $state['amount_minor'], $state['currency'] );
	}

	public function void( string $hold_ref ): TC_Payment_Result {
		$state = $this->read( $hold_ref );
		if ( ! $state ) {
			return TC_Payment_Result::failed( 'not_found', 'No such hold to void.', 0, 'GBP' );
		}
		if ( TC_Payment_Result::VOIDED === $state['status'] ) {
			return TC_Payment_Result::voided( $hold_ref, $state['amount_minor'], $state['currency'] ); // idempotent
		}
		if ( TC_Payment_Result::CAPTURED === $state['status'] ) {
			return TC_Payment_Result::failed( 'invalid_state', 'Cannot void a hold that has already been captured.', $state['amount_minor'], $state['currency'] );
		}

		$state['status'] = TC_Payment_Result::VOIDED;
		$this->persist( $state );

		return TC_Payment_Result::voided( $hold_ref, $state['amount_minor'], $state['currency'] );
	}

	public function retrieve( string $hold_ref ): TC_Payment_Result {
		$state = $this->read( $hold_ref );
		if ( ! $state ) {
			return TC_Payment_Result::failed( 'not_found', 'No such hold.', 0, 'GBP' );
		}
		return $this->result_from_state( $state );
	}

	/** Persist state under both its idempotency key and its hold reference. */
	private function persist( array $state ): void {
		$this->write( $state['hold_ref'], $state );
		if ( ! empty( $state['idempotency_key'] ) ) {
			$this->write( $state['idempotency_key'], $state );
		}
	}

	private function result_from_state( array $state, bool $replayed = false ): TC_Payment_Result {
		switch ( $state['status'] ) {
			case TC_Payment_Result::CAPTURED:
				return TC_Payment_Result::captured( $state['hold_ref'], $state['amount_minor'], $state['currency'] );
			case TC_Payment_Result::VOIDED:
				return TC_Payment_Result::voided( $state['hold_ref'], $state['amount_minor'], $state['currency'] );
			default:
				return TC_Payment_Result::authorized( $state['hold_ref'], $state['amount_minor'], $state['currency'], [ 'provider' => 'fake', 'replayed' => $replayed ] );
		}
	}

	private function read( string $key ): ?array {
		$key = $this->safe_key( $key );
		if ( function_exists( 'get_option' ) ) {
			$value = get_option( self::STORE_PREFIX . $key, null );
			return is_array( $value ) ? $value : null;
		}
		return self::$mem[ $key ] ?? null;
	}

	private function write( string $key, array $value ): void {
		$key = $this->safe_key( $key );
		if ( function_exists( 'update_option' ) ) {
			update_option( self::STORE_PREFIX . $key, $value, false );
			return;
		}
		self::$mem[ $key ] = $value;
	}

	private function safe_key( string $key ): string {
		return substr( preg_replace( '/[^A-Za-z0-9_]/', '_', $key ), 0, 40 );
	}
}
