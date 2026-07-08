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
			$actions['tc_review_approve'] = 'Approve & send payment link';
			$actions['tc_review_reject']  = 'Reject & cancel (notify patient)';
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
		$order->update_meta_data( self::META_PAYLINK_AT, time() );
		$order->save();

		TC_Review_Status::allow();
		$order->update_status(
			'pending',
			sprintf( 'Treatment approved by %s. Payment link emailed to the patient.', $reviewer )
		);
		TC_Review_Status::disallow();

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

		TC_Review_Status::allow();
		$order->update_status(
			'cancelled',
			sprintf( 'Treatment rejected by %s. Patient notified; no payment was taken.', $reviewer )
		);
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
