<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Phase 2.5 — authorise-at-submission (BUILD-BRIEF-v3 §9, Option B).
 *
 * The patient is sent to the core order-pay URL immediately after the
 * assessment creates their awaiting-review order. With the Stripe gateway's
 * "issue an authorisation on checkout, and capture later" setting enabled,
 * that payment is an authorisation: the card is held, nothing is captured.
 * The prescriber's approval captures it; rejection releases it.
 *
 * Deliberately contains NO gateway code (PLAYBOOK §1.2/§4.2): all money
 * movement is performed by the Stripe extension reacting to order status
 * transitions — capture on -> processing, void on -> cancelled. This class
 * only steers statuses:
 *
 *  - makes awaiting-review payable, so the order-pay URL works pre-review;
 *  - pins the order to awaiting-review when payment completes BEFORE a
 *    prescriber decision (the charge sits authorised on the intent);
 *  - routes payment completing AFTER approval (the emailed pay-link
 *    fallback lane) straight to processing, so the gateway captures it
 *    without a second manual step.
 *
 * Fail-safe: if the gateway's capture meta is absent or says anything but
 * "authorised, uncaptured", every caller degrades to the Phase 1b pay-link
 * behaviour. The feature can only fall back to emailing a payment link —
 * never to dispatching an unpaid order.
 */
class TC_Review_Payment {

	/** Set by the Woo Stripe extension: 'no' while authorised, 'yes' once captured. */
	const STRIPE_CAPTURED_META = '_stripe_charge_captured';

	const META_AUTHORISED_AT = '_tc_authorised_at';

	public static function init() {
		add_filter( 'woocommerce_valid_order_statuses_for_payment', [ __CLASS__, 'make_review_status_payable' ], 10, 2 );
		add_filter( 'woocommerce_payment_complete_order_status', [ __CLASS__, 'route_payment_complete_status' ], 20, 3 );
		add_action( 'woocommerce_payment_complete', [ __CLASS__, 'note_authorisation' ] );
	}

	/**
	 * Treatment orders must be payable while awaiting prescriber review, so
	 * the order-pay URL returned at submission works immediately.
	 */
	public static function make_review_status_payable( $statuses, $order ) {
		if ( $order instanceof WC_Order && TC_Review_Status::is_treatment_order( $order ) ) {
			$statuses[] = TC_Review_Status::STATUS;
		}
		return $statuses;
	}

	/**
	 * Where an order lands when its payment completes.
	 *
	 * Pre-decision (authorise-at-submission lane): stay in awaiting-review.
	 * Returning the current status makes WC_Order::payment_complete() a
	 * status no-op, so the review-status transition guard never engages and
	 * the authorised charge simply sits on the order until the prescriber
	 * acts.
	 *
	 * Post-approval (emailed pay-link fallback lane): go straight to
	 * processing — the Stripe extension captures on that transition, so the
	 * fallback lane needs no manual capture click either.
	 *
	 * Runs after the Stripe extension's own filter (priority 20) on treatment
	 * orders only; everything else keeps the gateway's behaviour.
	 */
	public static function route_payment_complete_status( $status, $order_id, $order ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order || ! TC_Review_Status::is_treatment_order( $order ) ) {
			return $status;
		}

		$decision = $order->get_meta( TC_Review_Actions::META_DECISION );

		if ( ! $decision && $order->get_status() === TC_Review_Status::STATUS ) {
			return TC_Review_Status::STATUS;
		}

		if ( 'approved' === $decision ) {
			return 'processing';
		}

		return $status;
	}

	/**
	 * Record the authorisation loudly on the order the moment it happens, so
	 * the review queue shows which orders already hold funds.
	 */
	public static function note_authorisation( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order
			|| ! TC_Review_Status::is_treatment_order( $order )
			|| $order->get_status() !== TC_Review_Status::STATUS
			|| $order->get_meta( TC_Review_Actions::META_DECISION ) ) {
			return;
		}

		$order->update_meta_data( self::META_AUTHORISED_AT, time() );
		$order->save();
		$order->add_order_note(
			'Card authorised — funds held, nothing captured. The prescriber\'s approval will capture the payment; rejection will release the hold. Authorisations expire after 7 days.'
		);

		if ( class_exists( 'TC_Log' ) ) {
			TC_Log::info( 'review_payment_authorised', [
				'order_id' => $order->get_id(),
				'total'    => $order->get_total(),
			] );
		}
	}

	/**
	 * True only when the gateway reports an authorised, uncaptured charge.
	 * Absent or unexpected meta returns false, which every caller treats as
	 * "behave exactly like Phase 1b" — the fail-safe default.
	 */
	public static function is_authorised_uncaptured( WC_Order $order ) {
		return 'no' === $order->get_meta( self::STRIPE_CAPTURED_META )
			&& (bool) $order->get_transaction_id();
	}

	/** True once the gateway reports the charge captured. */
	public static function is_captured( WC_Order $order ) {
		return 'yes' === $order->get_meta( self::STRIPE_CAPTURED_META );
	}
}
