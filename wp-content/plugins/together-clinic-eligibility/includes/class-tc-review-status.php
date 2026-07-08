<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The "Awaiting prescriber review" order status.
 *
 * Treatment orders are created in this status at assessment submission and may
 * only leave it via the sanctioned review actions (approve / adjust / reject).
 * A custom status (rather than core "pending") is used deliberately: WooCommerce
 * auto-cancels unpaid pending orders after the stock-hold window, and a clinical
 * review queue must never be emptied by an inventory timer.
 */
class TC_Review_Status {

	const STATUS     = 'awaiting-review';
	const STATUS_KEY = 'wc-awaiting-review';

	const FLAGS_META = '_tc_review_flags';

	/** Set true by sanctioned review actions for the duration of their transition. */
	private static $transition_allowed = false;

	/** Re-entrancy guard while the guard itself reverts an order. */
	private static $reverting = false;

	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_post_status' ] );
		add_filter( 'wc_order_statuses', [ __CLASS__, 'add_order_status' ] );
		add_filter( 'woocommerce_register_shop_order_post_statuses', [ __CLASS__, 'register_with_wc' ] );
		add_filter( 'wc_order_is_editable', [ __CLASS__, 'make_editable' ], 10, 2 );
		add_action( 'woocommerce_order_status_changed', [ __CLASS__, 'guard_transition' ], 10, 4 );
	}

	public static function register_post_status() {
		register_post_status( self::STATUS_KEY, [
			'label'                     => 'Awaiting prescriber review',
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: number of orders */
			'label_count'               => _n_noop(
				'Awaiting prescriber review <span class="count">(%s)</span>',
				'Awaiting prescriber review <span class="count">(%s)</span>'
			),
		] );
	}

	public static function add_order_status( $statuses ) {
		$updated = [];
		foreach ( $statuses as $key => $label ) {
			$updated[ $key ] = $label;
			if ( 'wc-pending' === $key ) {
				$updated[ self::STATUS_KEY ] = 'Awaiting prescriber review';
			}
		}
		if ( ! isset( $updated[ self::STATUS_KEY ] ) ) {
			$updated[ self::STATUS_KEY ] = 'Awaiting prescriber review';
		}
		return $updated;
	}

	public static function register_with_wc( $statuses ) {
		$statuses[ self::STATUS_KEY ] = [
			'label'                     => 'Awaiting prescriber review',
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop(
				'Awaiting prescriber review <span class="count">(%s)</span>',
				'Awaiting prescriber review <span class="count">(%s)</span>'
			),
		];
		return $statuses;
	}

	public static function make_editable( $editable, $order ) {
		if ( $order && $order->get_status() === self::STATUS ) {
			return true;
		}
		return $editable;
	}

	/**
	 * Sanctioned review actions (Phase 1b: approve / adjust / reject) wrap their
	 * status change in allow()/disallow() so the guard lets them through.
	 */
	public static function allow() {
		self::$transition_allowed = true;
	}

	public static function disallow() {
		self::$transition_allowed = false;
	}

	/**
	 * Treatment orders must not leave awaiting-review except via the review
	 * actions. Anything else (manual admin status edits, other plugins) is
	 * reverted with an order note, so an unreviewed prescription can never
	 * slip into fulfilment.
	 */
	public static function guard_transition( $order_id, $from, $to, $order ) {
		if ( self::$reverting || self::$transition_allowed ) {
			return;
		}

		if ( $from !== self::STATUS || $to === self::STATUS ) {
			return;
		}

		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return;
			}
		}

		if ( ! self::is_treatment_order( $order ) ) {
			return;
		}

		// Emergency escape hatch, e.g. if the review actions are unavailable.
		if ( apply_filters( 'tc_review_allow_transition', false, $order, $to ) ) {
			return;
		}

		self::$reverting = true;
		$order->update_status(
			self::STATUS,
			sprintf(
				'Status change to "%s" blocked: treatment orders can only leave prescriber review via the review actions.',
				$to
			)
		);
		self::$reverting = false;

		if ( class_exists( 'TC_Log' ) ) {
			TC_Log::warn( 'review_transition_blocked', [
				'order_id' => $order_id,
				'from'     => $from,
				'to'       => $to,
			] );
		}
	}

	public static function is_treatment_order( WC_Order $order ) {
		return (bool) ( $order->get_meta( '_tc_eligibility_raw' ) || $order->get_meta( '_rrqr_raw' ) );
	}
}
