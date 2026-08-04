<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The prescriber's review actions on awaiting-review treatment orders,
 * exposed in the standard WooCommerce "Order actions" box:
 *
 * - Approve & send payment link: order -> pending-payment, patient emailed
 *   the secure order-pay URL. To adjust the dose first, the prescriber edits
 *   the line item natively (the order is editable in awaiting-review), then
 *   approves — the pay link always charges the order's current total.
 * - Reject & cancel: order -> cancelled, patient notified. No payment taken.
 *
 * Also exempts treatment orders from WooCommerce's unpaid-pending auto-cancel
 * (the stock-hold timer); the review cron owns the pay-link expiry instead.
 */
class TC_Review_Actions {

	const META_DECISION    = '_tc_review_decision';
	const META_DECIDED_BY  = '_tc_review_decided_by';
	const META_DECIDED_AT  = '_tc_review_decided_at';
	const META_PAYLINK_AT  = '_tc_review_paylink_sent_at';
	const META_REMINDER_AT = '_tc_review_reminder_sent_at';

	public function __construct() {
		add_filter( 'woocommerce_order_actions', [ $this, 'add_actions' ], 10, 2 );
		add_action( 'woocommerce_order_action_tc_review_approve', [ __CLASS__, 'approve' ] );
		add_action( 'woocommerce_order_action_tc_review_reject', [ __CLASS__, 'reject' ] );
		add_filter( 'woocommerce_cancel_unpaid_order', [ __CLASS__, 'exempt_from_unpaid_auto_cancel' ], 10, 2 );
	}

	public function add_actions( $actions, $order = null ) {
		if ( ! $order instanceof WC_Order ) {
			global $theorder;
			$order = $theorder;
		}

		if ( $order instanceof WC_Order
			&& $order->get_status() === TC_Review_Status::STATUS
			&& TC_Review_Status::is_treatment_order( $order ) ) {
			if ( TC_Review_Payment::is_authorised_uncaptured( $order ) ) {
				$actions['tc_review_approve'] = 'Approve & capture the authorised payment';
				$actions['tc_review_reject']  = 'Reject & release the payment hold (notify patient)';
			} else {
				$actions['tc_review_approve'] = 'Approve & send payment link';
				$actions['tc_review_reject']  = 'Reject & cancel (notify patient)';
			}
		}

		return $actions;
	}

	public static function approve( WC_Order $order ) {
		if ( $order->get_status() !== TC_Review_Status::STATUS ) {
			return;
		}

		$reviewer = self::current_reviewer();

		$order->update_meta_data( self::META_DECISION, 'approved' );
		$order->update_meta_data( self::META_DECIDED_BY, $reviewer );
		$order->update_meta_data( self::META_DECIDED_AT, time() );
		$order->save();

		// Phase 2.5: the patient authorised their card at submission. Moving
		// to processing makes the Stripe extension capture the held funds —
		// no pay link needed. Verified afterwards (PLAYBOOK §4.6): if the
		// capture did not happen (expired/declined authorisation, gateway
		// version drift), the order must not sit in processing unpaid — it
		// falls back to the Phase 1b pay-link path below.
		if ( TC_Review_Payment::is_authorised_uncaptured( $order ) ) {
			TC_Review_Status::allow();
			$order->update_status(
				'processing',
				sprintf( 'Treatment approved by %s. Capturing the payment authorised at submission…', $reviewer )
			);
			TC_Review_Status::disallow();

			$fresh = wc_get_order( $order->get_id() );
			if ( $fresh && TC_Review_Payment::is_captured( $fresh ) ) {
				$fresh->add_order_note( 'Authorised payment captured. WooCommerce has emailed the patient their order confirmation.' );
				TC_Log::info( 'review_approved_captured', [
					'order_id' => $fresh->get_id(),
					'by'       => $reviewer,
					'total'    => $fresh->get_total(),
				] );
				return;
			}

			// Dispatch-risk guard: never leave an uncaptured order looking paid.
			$fresh = $fresh ?: $order;
			TC_Review_Status::allow();
			$fresh->update_status(
				'pending',
				'Capture of the authorised payment FAILED — the authorisation may have expired (7-day limit) or been declined. Falling back to the emailed payment link.'
			);
			TC_Review_Status::disallow();
			$order = $fresh;

			TC_Log::warn( 'review_capture_failed', [
				'order_id' => $order->get_id(),
				'by'       => $reviewer,
			] );
		} else {
			TC_Review_Status::allow();
			$order->update_status(
				'pending',
				sprintf( 'Treatment approved by %s. Payment link emailed to the patient.', $reviewer )
			);
			TC_Review_Status::disallow();
		}

		$order->update_meta_data( self::META_PAYLINK_AT, time() );
		$order->save();

		TC_Review_Emails::send_approved( $order );

		TC_Log::info( 'review_approved', [
			'order_id' => $order->get_id(),
			'by'       => $reviewer,
			'total'    => $order->get_total(),
		] );
	}

	public static function reject( WC_Order $order ) {
		if ( $order->get_status() !== TC_Review_Status::STATUS ) {
			return;
		}

		$reviewer = self::current_reviewer();

		$order->update_meta_data( self::META_DECISION, 'rejected' );
		$order->update_meta_data( self::META_DECIDED_BY, $reviewer );
		$order->update_meta_data( self::META_DECIDED_AT, time() );
		$order->save();

		// Phase 2.5: on -> cancelled the Stripe extension voids an uncaptured
		// authorisation, releasing the held funds. No money was ever captured.
		$note = TC_Review_Payment::is_authorised_uncaptured( $order )
			? sprintf( 'Treatment rejected by %s. Patient notified; the payment authorisation has been released — no money was taken.', $reviewer )
			: sprintf( 'Treatment rejected by %s. Patient notified; no payment was taken.', $reviewer );

		TC_Review_Status::allow();
		$order->update_status( 'cancelled', $note );
		TC_Review_Status::disallow();

		TC_Review_Emails::send_rejected( $order );

		TC_Log::info( 'review_rejected', [
			'order_id' => $order->get_id(),
			'by'       => $reviewer,
		] );
	}

	/**
	 * WooCommerce cancels unpaid pending orders after the stock-hold window.
	 * Approved treatment orders wait up to 7 days for payment by design, so
	 * they are exempt — the review cron enforces the pay-link expiry instead.
	 */
	public static function exempt_from_unpaid_auto_cancel( $cancel, $order ) {
		if ( $order instanceof WC_Order && TC_Review_Status::is_treatment_order( $order ) ) {
			return false;
		}
		return $cancel;
	}

	private static function current_reviewer() {
		$user = wp_get_current_user();
		return ( $user && $user->exists() ) ? $user->display_name : 'system';
	}
}
