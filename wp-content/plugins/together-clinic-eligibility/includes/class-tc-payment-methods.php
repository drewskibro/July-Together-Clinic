<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The payment surface for treatment orders on the WooCommerce order-pay page.
 *
 * Phase 2.5 authorises the patient's card at submission and captures only on
 * the prescriber's approval. That model is entirely Stripe-specific: the Woo
 * Stripe extension moves money by reacting to order-status transitions
 * (capture on -> processing, void on -> cancelled), and TC_Review_Payment reads
 * Stripe charge meta to steer those transitions. Any non-Stripe method takes no
 * authorisation at all — a treatment order paid by Cash on Delivery could never
 * be captured on approval nor released on rejection, and would let an unfunded
 * prescription reach a prescriber. Stripe's own APMs (Amazon Pay, Klarna, …)
 * either capture on a different timeline or hold for an unconfirmed window.
 *
 * So on the order-pay page for an awaiting-review treatment order this class:
 *
 *  1. restricts the available gateways to the card gateway only — fail closed:
 *     if the card gateway is unavailable the order simply cannot be paid, it is
 *     never downgraded to an unsafe method; and
 *  2. tells the patient, in plain English, that this is a hold, not a charge.
 *
 * Every callback is gated on the order-pay endpoint AND the awaiting-review
 * status AND is_treatment_order, so the normal cart checkout, the block
 * checkout, admin, and any non-treatment order are never touched.
 */
class TC_Payment_Methods {

	public function __construct() {
		add_filter( 'woocommerce_available_payment_gateways', [ $this, 'restrict_gateways' ], 100 );
		add_action( 'before_woocommerce_pay_form', [ $this, 'warn_if_no_gateway' ], 5, 3 );
		add_action( 'before_woocommerce_pay_form', [ $this, 'hold_panel' ], 10, 3 );
		add_action( 'woocommerce_pay_order_before_submit', [ $this, 'hold_note' ] );
	}

	/**
	 * A treatment order may only be paid with a gateway that authorises now and
	 * captures later on prescriber approval — in practice the Stripe card
	 * gateway ('stripe'), which also carries the Apple Pay / Google Pay / Link
	 * express buttons (they ride on the card gateway, so keeping it keeps them).
	 * Cash on Delivery and the separate Stripe APM gateways (stripe_amazon_pay,
	 * stripe_klarna, …) are removed. The allowlist is filterable so a method can
	 * be added once its authorisation-hold behaviour has been verified live —
	 * Amazon Pay is deliberately excluded until its 7-day hold window is proven.
	 */
	public function restrict_gateways( $gateways ) {
		$order = $this->order_pay_order();
		if ( ! $this->is_gated_order( $order ) ) {
			return $gateways;
		}

		$allowed = apply_filters( 'tc_treatment_order_gateways', [ 'stripe' ], $order );

		foreach ( array_keys( $gateways ) as $id ) {
			if ( ! in_array( $id, $allowed, true ) ) {
				unset( $gateways[ $id ] );
			}
		}

		return $gateways;
	}

	/**
	 * Fail-closed guard. If the card gateway is unavailable the filtered list is
	 * empty; we never restore an unsafe method to fill the gap. Instead we say
	 * so plainly and log it loudly, so the outage is visible rather than a bare
	 * "no payment methods" page.
	 */
	public function warn_if_no_gateway( $order, $order_button_text, $available_gateways ) {
		if ( ! $this->is_gated_order( $order ) || ! empty( $available_gateways ) ) {
			return;
		}

		wc_print_notice(
			'Card payment is temporarily unavailable, so we cannot authorise your treatment right now. Nothing has been charged. Please try again shortly, or contact us and we will complete your order.',
			'error'
		);

		if ( class_exists( 'TC_Log' ) ) {
			TC_Log::error( 'treatment_pay_no_gateway', [ 'order_id' => $order->get_id() ] );
		}
	}

	/**
	 * Reassure the patient, above the pay form, that confirming places a hold
	 * and does not take payment until a prescriber has approved the treatment.
	 */
	public function hold_panel( $order, $order_button_text, $available_gateways ) {
		// Not on a treatment order, or the fail-closed case where no gateway is
		// available (warn_if_no_gateway has already shown the outage notice —
		// reassuring about a hold we cannot take would only contradict it).
		if ( ! $this->is_gated_order( $order ) || empty( $available_gateways ) ) {
			return;
		}
		?>
		<div style="background:#f7f4f9;border-left:4px solid #7d76ba;padding:16px 20px;margin:0 0 24px;border-radius:6px;">
			<h2 style="margin:0 0 8px;font-size:1.15em;">This is an authorisation, not a payment</h2>
			<p style="margin:0 0 12px;">When you confirm below, we place a temporary <strong>hold</strong> on your card for the amount shown &mdash; the money is not taken yet. Your order is then sent to one of our prescribers to review.</p>
			<ul style="margin:0;padding-left:20px;">
				<li>If the prescriber approves your treatment, the held amount is charged and your order is dispensed.</li>
				<li>If they are unable to approve it, the hold is released in full and you are charged nothing.</li>
				<li>If we have not completed the review within 7 days, the hold expires automatically and no payment is taken &mdash; you are welcome to submit your order again.</li>
			</ul>
		</div>
		<?php
	}

	/** A short reminder immediately above the pay button. */
	public function hold_note() {
		$order = $this->order_pay_order();
		if ( ! $this->is_gated_order( $order ) ) {
			return;
		}
		echo '<p style="font-size:0.9em;color:#555;margin:0 0 12px;">By confirming, you authorise a hold on your card. No money is taken until a prescriber approves your treatment; the hold is released if it is declined, or after 7 days.</p>';
	}

	/**
	 * The WC_Order being paid on the order-pay endpoint, or null outside it.
	 * The available-gateways filter and the pay-button hook are handed no order,
	 * so we resolve it from the endpoint ourselves — and return null everywhere
	 * else (cart checkout, Store API / block checkout, admin, update_order_review
	 * AJAX) so nothing there is ever touched.
	 */
	private function order_pay_order() {
		if ( is_admin() ) {
			return null;
		}
		if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-pay' ) ) {
			return null;
		}

		$order_id = absint( get_query_var( 'order-pay' ) );
		if ( ! $order_id ) {
			global $wp;
			if ( ! empty( $wp->query_vars['order-pay'] ) ) {
				$order_id = absint( $wp->query_vars['order-pay'] );
			}
		}
		if ( ! $order_id ) {
			return null;
		}

		$order = wc_get_order( $order_id );
		return $order instanceof WC_Order ? $order : null;
	}

	/**
	 * True only for a treatment order still in prescriber review. Both gates
	 * matter: status alone would re-restrict a reorder that has already left
	 * review; is_treatment_order alone would catch a treatment order now in
	 * processing/cancelled if its pay page were ever re-shown.
	 */
	private function is_gated_order( $order ) {
		return $order instanceof WC_Order
			&& $order->get_status() === TC_Review_Status::STATUS
			&& TC_Review_Status::is_treatment_order( $order );
	}
}
