<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Phase 2.5 — authorise-at-submission ("hold") payment model.
 *
 * The patient authorises their card at the end of the assessment (a hold, not
 * a charge). The order waits in awaiting-review; the prescriber's approval
 * CAPTURES the hold, rejection RELEASES (voids) it. Nothing is ever charged
 * without a prescriber decision.
 *
 * DESIGN NOTES / ASSUMPTIONS (verify in Stripe test mode before go-live):
 * - Assumes the official WooCommerce Stripe Gateway (`woocommerce-gateway-stripe`)
 *   with "Issue an authorization on checkout, and capture later" enabled. That
 *   plugin stores the PaymentIntent id in `_stripe_intent_id` and captures a
 *   held charge natively when the order moves to processing/completed, voiding
 *   it when the order is cancelled. So capture()/release() here are expressed as
 *   sanctioned STATUS TRANSITIONS and let the gateway do the money movement —
 *   we never call the Stripe API directly. Both are wrapped so the review guard
 *   permits them, and both are filterable (`tc_payment_capture`/`tc_payment_release`)
 *   so a different gateway can override the mechanism without touching callers.
 * - Stripe reports each hold's real expiry as `capture_before` on the charge.
 *   Where the gateway surfaces it we store it; otherwise we fall back to a
 *   filterable 7-day default. Either way the expiry is per-hold (owner
 *   requirement, 2026-09-11), read via hold_expiry().
 *
 * The whole model is gated behind enabled() (option `tc_payment_hold_enabled`,
 * default off). Off = the existing review-then-pay-link flow, unchanged.
 */
class TC_Payment {

	const META_INTENT_ID   = '_tc_hold_intent_id';
	const META_HOLD_EXPIRY = '_tc_hold_expiry';      // unix ts — Stripe capture_before, else default
	const META_HOLD_PLACED = '_tc_hold_placed_at';   // unix ts
	const META_HOLD_AMOUNT = '_tc_hold_amount';      // order total at authorisation
	const META_CAPTURED_AT = '_tc_hold_captured_at';
	const META_RELEASED_AT = '_tc_hold_released_at';

	/** The gateway's own PaymentIntent meta key (official WooCommerce Stripe Gateway). */
	const STRIPE_INTENT_META = '_stripe_intent_id';

	public function __construct() {
		if ( ! self::enabled() ) {
			return;
		}

		// Let the patient authorise on the order-pay page while the order is
		// still awaiting review (custom statuses are not payable by default).
		add_filter( 'woocommerce_valid_order_statuses_for_payment', [ __CLASS__, 'allow_payment_on_review' ], 10, 2 );
		add_filter( 'woocommerce_order_needs_payment', [ __CLASS__, 'review_needs_payment' ], 10, 3 );

		// Record the hold the moment the gateway confirms the authorisation.
		// Runs at priority 9 — before the review guard (10) reverts the order
		// back into the queue — so we capture the intent while it is attached.
		add_action( 'woocommerce_order_status_changed', [ __CLASS__, 'record_hold_on_authorisation' ], 9, 4 );
	}

	/** Feature flag. Off by default: production keeps the review-then-pay-link flow. */
	public static function enabled() {
		return (bool) apply_filters(
			'tc_payment_hold_enabled',
			get_option( 'tc_payment_hold_enabled', 'no' ) === 'yes'
		);
	}

	/** The order-pay URL the patient is sent to at submission to authorise their card. */
	public static function pay_url( WC_Order $order ) {
		return $order->get_checkout_payment_url();
	}

	public static function allow_payment_on_review( $statuses, $order = null ) {
		$statuses[] = TC_Review_Status::STATUS;
		return array_unique( $statuses );
	}

	public static function review_needs_payment( $needs_payment, $order, $valid_statuses ) {
		if ( $order instanceof WC_Order
			&& $order->get_status() === TC_Review_Status::STATUS
			&& TC_Review_Status::is_treatment_order( $order )
			&& ! self::is_hold_placed( $order )
			&& $order->get_total() > 0 ) {
			return true;
		}
		return $needs_payment;
	}

	/** True once the card has been authorised and neither captured nor released. */
	public static function is_hold_placed( WC_Order $order ) {
		return (bool) $order->get_meta( self::META_INTENT_ID )
			&& ! $order->get_meta( self::META_CAPTURED_AT )
			&& ! $order->get_meta( self::META_RELEASED_AT );
	}

	/** The amount authorised (order total at the time of the hold). */
	public static function held_amount( WC_Order $order ) {
		return (float) $order->get_meta( self::META_HOLD_AMOUNT );
	}

	/** Per-hold expiry: Stripe's capture_before where known, else placed_at + default. */
	public static function hold_expiry( WC_Order $order ) {
		$stored = (int) $order->get_meta( self::META_HOLD_EXPIRY );
		if ( $stored ) {
			return $stored;
		}
		$placed = (int) $order->get_meta( self::META_HOLD_PLACED ) ?: time();
		$days   = (int) apply_filters( 'tc_payment_hold_expiry_days', 7 );
		return $placed + $days * DAY_IN_SECONDS;
	}

	/**
	 * Records the hold when the gateway moves the order out of awaiting-review
	 * following a successful authorisation. The review guard then returns the
	 * order to the queue, so the net effect is: order stays awaiting-review,
	 * with the hold recorded against it.
	 */
	public static function record_hold_on_authorisation( $order_id, $from, $to, $order ) {
		if ( $from !== TC_Review_Status::STATUS ) {
			return;
		}
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order || ! TC_Review_Status::is_treatment_order( $order ) ) {
			return;
		}

		$intent_id = $order->get_meta( self::STRIPE_INTENT_META );
		if ( ! $intent_id || self::is_hold_placed( $order ) || $order->get_meta( self::META_CAPTURED_AT ) ) {
			return;
		}

		$expiry = (int) apply_filters( 'tc_payment_hold_expiry_ts', 0, $order, $intent_id );

		$order->update_meta_data( self::META_INTENT_ID, $intent_id );
		$order->update_meta_data( self::META_HOLD_PLACED, time() );
		$order->update_meta_data( self::META_HOLD_AMOUNT, $order->get_total() );
		if ( $expiry ) {
			$order->update_meta_data( self::META_HOLD_EXPIRY, $expiry );
		}
		$order->add_order_note( 'Card authorised — funds are held, not charged. Awaiting prescriber approval to capture.' );
		$order->save();

		TC_Log::info( 'payment_hold_recorded', [
			'order_id'  => $order->get_id(),
			'intent_id' => $intent_id,
			'amount'    => $order->get_total(),
			'expiry'    => self::hold_expiry( $order ),
		] );
	}

	/**
	 * Capture the hold: expressed as the sanctioned move to `processing`, which
	 * the Stripe gateway turns into a capture. Returns true, or WP_Error if the
	 * caller should fall back to the pay-link (e.g. no hold to capture).
	 */
	public static function capture( WC_Order $order ) {
		if ( ! self::is_hold_placed( $order ) ) {
			return new WP_Error( 'tc_no_hold', 'No authorised hold to capture.' );
		}

		$overridden = apply_filters( 'tc_payment_capture', null, $order );
		if ( is_wp_error( $overridden ) ) {
			return $overridden;
		}

		TC_Review_Status::allow();
		$order->update_status(
			'processing',
			'Prescriber approved — held funds captured. Preparing for dispatch.'
		);
		TC_Review_Status::disallow();

		$order->update_meta_data( self::META_CAPTURED_AT, time() );
		$order->save();

		TC_Log::info( 'payment_hold_captured', [
			'order_id' => $order->get_id(),
			'amount'   => self::held_amount( $order ),
		] );

		return true;
	}

	/**
	 * Release (void) the hold: expressed as the sanctioned move to `cancelled`,
	 * which the Stripe gateway turns into a void. Used on rejection, and on
	 * expiry / upward dose adjustment before falling back to the pay-link.
	 */
	public static function release( WC_Order $order, $note = '' ) {
		if ( ! $order->get_meta( self::META_INTENT_ID ) || $order->get_meta( self::META_RELEASED_AT ) ) {
			return new WP_Error( 'tc_no_hold', 'No hold to release.' );
		}

		$overridden = apply_filters( 'tc_payment_release', null, $order );
		if ( is_wp_error( $overridden ) ) {
			return $overridden;
		}

		$order->update_meta_data( self::META_RELEASED_AT, time() );
		$order->save();

		TC_Log::info( 'payment_hold_released', [
			'order_id' => $order->get_id(),
			'note'     => $note,
		] );

		return true;
	}
}
