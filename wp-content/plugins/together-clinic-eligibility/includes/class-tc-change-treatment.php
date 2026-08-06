<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Change my treatment" on the order-pay page.
 *
 * The order is created at assessment submission and the patient is sent
 * straight to the WooCommerce order-pay page (an authorisation, not a charge).
 * That page is post-commit — WooCommerce gives it no "back" — so a patient who
 * realises they picked the wrong treatment there has no way to change it.
 *
 * This class adds that escape hatch WITHOUT touching the payment flow. On the
 * pay page of an awaiting-review treatment order it offers the other
 * (purchasable) treatments; choosing one:
 *
 *   1. rebuilds the assessment payload from the order's own stored snapshot
 *      (`_tc_eligibility_raw`) — no dependency on the browser session, so it
 *      works even from the emailed pay link hours later;
 *   2. creates a fresh awaiting-review order for the new treatment via the
 *      canonical TC_Review_Order::create_from_assessment(), starting at that
 *      treatment's safe default dose and flagging the switch for the prescriber;
 *   3. cancels the original order — the Stripe extension voids any uncaptured
 *      authorisation on the -> cancelled transition, so a held card is released;
 *   4. redirects the patient to the new order's pay page.
 *
 * The new order is created BEFORE the old one is cancelled, so a failure never
 * leaves the patient with no order. Every money movement is still performed by
 * the Stripe extension reacting to status transitions — this class only steers
 * order creation and cancellation, never the gateway (PLAYBOOK §1.2/§4.2).
 */
class TC_Change_Treatment {

	const ACTION = 'tc_change_treatment';

	public function __construct() {
		// Renders outside the WooCommerce pay <form> (this hook fires before it
		// opens), so the standalone POST form below is never a nested form.
		add_action( 'before_woocommerce_pay_form', [ $this, 'render_change_ui' ], 20, 3 );
		add_action( 'admin_post_' . self::ACTION, [ $this, 'handle' ] );
		add_action( 'admin_post_nopriv_' . self::ACTION, [ $this, 'handle' ] );
	}

	/**
	 * The switch offer, shown under the "authorisation, not a payment" panel.
	 * Only for an awaiting-review treatment order that still needs payment and
	 * has a card gateway available (matching hold_panel: no offer during a
	 * card-payment outage, where switching could not help anyway).
	 */
	public function render_change_ui( $order, $order_button_text, $available_gateways ) {
		if ( ! $this->is_changeable( $order ) || empty( $available_gateways ) ) {
			return;
		}

		$alternatives = $this->alternatives_for( $order );
		if ( empty( $alternatives ) ) {
			return;
		}

		$action_url = admin_url( 'admin-post.php' );
		?>
		<div class="tc-change-treatment" style="background:#ffffff;border:1px solid #ece8f6;border-radius:8px;padding:16px 20px;margin:0 0 24px;">
			<p style="margin:0 0 12px;font-size:0.95em;"><strong>Prefer a different treatment?</strong> You can switch before you pay &mdash; nothing has been charged yet.</p>
			<form method="post" action="<?php echo esc_url( $action_url ); ?>" style="display:flex;flex-wrap:wrap;gap:10px;margin:0;">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
				<input type="hidden" name="order_id" value="<?php echo esc_attr( $order->get_id() ); ?>" />
				<input type="hidden" name="order_key" value="<?php echo esc_attr( $order->get_order_key() ); ?>" />
				<?php wp_nonce_field( self::ACTION . '_' . $order->get_id() ); ?>
				<?php foreach ( $alternatives as $alt ) : ?>
					<button type="submit" name="treatment" value="<?php echo esc_attr( $alt['id'] ); ?>"
						style="cursor:pointer;background:#f7f4f9;color:#4a4470;border:1px solid #d8d5e6;border-radius:8px;padding:10px 16px;font-size:0.95em;font-weight:600;">
						Switch to <?php echo esc_html( $alt['label'] ); ?><?php if ( $alt['price_html'] !== '' ) : ?> &mdash; <?php echo wp_kses_post( $alt['price_html'] ); ?><?php endif; ?>
					</button>
				<?php endforeach; ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle a switch. Fail-closed: any validation miss returns the patient to
	 * their current pay page with a notice and changes nothing.
	 */
	public function handle() {
		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

		// CSRF. The pay page is never edge-cached, so this nonce is always fresh.
		check_admin_referer( self::ACTION . '_' . $order_id );

		$order = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $this->is_changeable( $order ) ) {
			$this->fail( $order instanceof WC_Order ? $order : null, 'This order can no longer be changed. If you need help, please contact us.' );
		}

		// Ownership: the order key is the same secret WooCommerce uses to gate the
		// pay page itself — proves the requester holds this order, guests included.
		$posted_key = isset( $_POST['order_key'] ) ? wp_unslash( $_POST['order_key'] ) : '';
		if ( ! is_string( $posted_key ) || ! hash_equals( $order->get_order_key(), $posted_key ) ) {
			wp_die( 'Not allowed.', 'Not allowed', [ 'response' => 403 ] );
		}

		$current = $this->current_treatment( $order );
		$new     = isset( $_POST['treatment'] ) ? TC_Variation_Map::normalize_treatment( sanitize_key( wp_unslash( $_POST['treatment'] ) ) ) : '';

		$ladders = TC_Dose_Ladder::LADDERS;
		if ( ! isset( $ladders[ $new ] ) || $new === $current ) {
			$this->fail( $order, 'Please choose a different treatment.' );
		}

		$new_dose = TC_Dose_Ladder::starter( $new );
		if ( ! $new_dose || ! TC_Dose_Ladder::is_available( $new, $new_dose ) ) {
			$this->fail( $order, 'That treatment is not available right now. Please contact us and we will help.' );
		}

		// Rebuild the assessment from the order's own snapshot — independent of
		// the browser session, so this works from the emailed pay link too.
		$payload = json_decode( (string) $order->get_meta( TC_Checkout::ORDER_META_RAW ), true );
		if ( ! is_array( $payload ) || empty( $payload ) ) {
			$this->fail( $order, 'We could not load your assessment to change it. Please contact us and we will complete your order.' );
		}

		$assessment_id = (string) $order->get_meta( TC_Checkout::ORDER_META_ASSESSMENT_ID );
		if ( $assessment_id === '' ) {
			$assessment_id = (string) ( $payload['assessment_id'] ?? '' );
		}

		$old_label = TC_Variation_Map::treatment_label( $current ) ?: 'your previous treatment';
		$new_label = TC_Variation_Map::treatment_label( $new );

		$payload['selectedTreatment'] = $new;
		$payload['selectedDose']      = $new_dose;
		$payload['assessment_id']     = $assessment_id;

		// create_from_assessment's attach step reads TC_Cookie_Store::get(); this
		// primes its request cache so the new order carries the updated payload
		// even where the WC session is not loaded (admin-post.php).
		TC_Cookie_Store::save_to_session( $payload );

		$flags = [
			'treatment_changed' => sprintf(
				'Patient changed treatment on the pay page from %s to %s before paying. Started at the default %s dose (%s) — confirm or adjust before approval.',
				$old_label,
				$new_label,
				$new_label,
				$new_dose
			),
		];

		$user_id = (int) $order->get_customer_id();

		// Create the replacement FIRST — only cancel the original once it exists,
		// so a failure never leaves the patient with no order.
		$new_order = TC_Review_Order::create_from_assessment( $payload, $assessment_id, $user_id, $flags );
		if ( is_wp_error( $new_order ) ) {
			$this->fail( $order, $new_order->get_error_message() );
		}

		// Cancel the original. On -> cancelled the Stripe extension voids any
		// uncaptured authorisation, releasing a held card. The review-status
		// guard only permits sanctioned transitions, so wrap it like the
		// prescriber reject action does.
		TC_Review_Status::allow();
		$order->update_status(
			'cancelled',
			sprintf(
				'Patient switched to %1$s before paying; this %2$s order was cancelled and replaced by order #%3$d. Any authorisation hold has been released — no payment was taken.',
				$new_label,
				$old_label,
				$new_order->get_id()
			)
		);
		TC_Review_Status::disallow();

		// Keep the prescriber's review queue pointed at the order that will
		// actually be paid — notify on the new order exactly as a fresh eligible
		// submission would (no patient re-confirmation; they already have theirs).
		if ( class_exists( 'TC_Eligibility_Rules' ) && class_exists( 'TC_Emails' ) ) {
			$eligibility = TC_Eligibility_Rules::evaluate( $payload );
			TC_Emails::send_clinician_notification( $payload, $assessment_id, $eligibility, $new_order->get_id() );
		}

		if ( class_exists( 'TC_Log' ) ) {
			TC_Log::info( 'treatment_changed_by_patient', [
				'old_order_id' => $order->get_id(),
				'new_order_id' => $new_order->get_id(),
				'from'         => $current,
				'to'           => $new,
				'user_id'      => $user_id,
			] );
		}

		wp_safe_redirect( $new_order->get_checkout_payment_url() );
		exit;
	}

	/**
	 * A treatment order still in prescriber review that has not yet been paid or
	 * authorised. Switching is a pre-authorisation affordance: once the card is
	 * authorised the order no longer needs payment, so this returns false and the
	 * offer disappears (a switch then would mean voiding a live hold — handled by
	 * contacting support instead).
	 */
	private function is_changeable( $order ) {
		return $order instanceof WC_Order
			&& $order->get_status() === TC_Review_Status::STATUS
			&& TC_Review_Status::is_treatment_order( $order )
			// Eligibility-assessment orders only. is_treatment_order() is also true
			// for reorders (`_rrqr_raw`), but this feature rebuilds from the
			// eligibility snapshot (`_tc_eligibility_raw`) and its dose ladders —
			// switching a reorder here would be meaningless, so scope it out.
			&& (bool) $order->get_meta( TC_Checkout::ORDER_META_RAW )
			&& $order->needs_payment();
	}

	/**
	 * The treatment on the order, read from its stored assessment snapshot and
	 * normalised to a canonical id.
	 */
	private function current_treatment( WC_Order $order ) {
		$raw = json_decode( (string) $order->get_meta( TC_Checkout::ORDER_META_RAW ), true );
		$treatment = is_array( $raw ) ? ( $raw['selectedTreatment'] ?? '' ) : '';
		return TC_Variation_Map::normalize_treatment( (string) $treatment );
	}

	/**
	 * The other treatments the patient can switch to — only those with a
	 * purchasable starter dose, so an unmapped treatment is silently omitted
	 * rather than offered as a dead option.
	 */
	private function alternatives_for( WC_Order $order ) {
		$current = $this->current_treatment( $order );
		$out     = [];

		foreach ( array_keys( TC_Dose_Ladder::LADDERS ) as $treatment ) {
			if ( $treatment === $current ) {
				continue;
			}
			$starter = TC_Dose_Ladder::starter( $treatment );
			if ( ! $starter || ! TC_Dose_Ladder::is_available( $treatment, $starter ) ) {
				continue;
			}

			$product_id = TC_Variation_Map::get_variation_id( $treatment, $starter );
			$product    = $product_id ? wc_get_product( $product_id ) : null;
			$price_html = '';
			if ( $product && $product->get_price() !== '' ) {
				$price_html = wc_price( $product->get_price() ) . '/mo';
			}

			$out[] = [
				'id'         => $treatment,
				'label'      => TC_Variation_Map::treatment_label( $treatment ),
				'price_html' => $price_html,
			];
		}

		return $out;
	}

	/**
	 * Abort a switch without changing anything: notice + back to the pay page.
	 */
	private function fail( $order, $message ) {
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $message, 'error' );
		}
		$url = ( $order instanceof WC_Order ) ? $order->get_checkout_payment_url() : home_url( '/' );
		wp_safe_redirect( $url );
		exit;
	}
}
