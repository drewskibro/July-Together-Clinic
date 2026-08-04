<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Patient-facing emails for the prescriber-review lifecycle:
 * approved (carries the pay link), rejected, payment reminder, link expired.
 */
class TC_Review_Emails {

	public static function send_approved( WC_Order $order ) {
		$email = $order->get_billing_email();
		if ( ! is_email( $email ) ) {
			return false;
		}

		$first  = $order->get_billing_first_name() ?: 'there';
		$items  = self::item_summary( $order );
		$total  = wp_strip_all_tags( wc_price( $order->get_total() ) );
		$expiry = self::expiry_date( $order );

		$subject = '[Together Clinic] Your treatment is approved — complete your order';

		$body  = sprintf( '<p>Hi %s,</p>', esc_html( $first ) );
		$body .= sprintf( '<p>Good news &mdash; our prescriber has reviewed and approved your treatment: <strong>%s</strong>.</p>', esc_html( $items ) );
		$body .= sprintf( '<p>To complete your order, please pay securely using the link below. The total is <strong>%s</strong>.</p>', esc_html( $total ) );
		$body .= self::button( $order->get_checkout_payment_url(), 'Pay securely now' );
		$body .= sprintf( '<p style="font-size:13px;color:#6b7280;">This payment link is valid until %s. Once you have paid, your medication will be dispatched with free next-day delivery.</p>', esc_html( $expiry ) );
		$body .= self::footer();

		return self::send( $order, $email, $subject, $body, 'approved' );
	}

	public static function send_rejected( WC_Order $order ) {
		$email = $order->get_billing_email();
		if ( ! is_email( $email ) ) {
			return false;
		}

		$first   = $order->get_billing_first_name() ?: 'there';
		$booking = self::booking_url();

		$subject = '[Together Clinic] About your recent order';

		$body  = sprintf( '<p>Hi %s,</p>', esc_html( $first ) );
		$body .= '<p>Thank you for your recent submission. After reviewing your answers, our prescriber has decided this treatment is not suitable to supply at the moment, so your order has been cancelled. <strong>No payment has been taken.</strong></p>';
		$body .= '<p>This is a clinical safety decision and it is not necessarily permanent. Our team would be happy to talk it through and discuss what options may be right for you.</p>';
		$body .= self::button( $booking, 'Book a consultation' );
		$body .= self::footer();

		return self::send( $order, $email, $subject, $body, 'rejected' );
	}

	public static function send_reminder( WC_Order $order ) {
		$email = $order->get_billing_email();
		if ( ! is_email( $email ) ) {
			return false;
		}

		$first  = $order->get_billing_first_name() ?: 'there';
		$items  = self::item_summary( $order );
		$expiry = self::expiry_date( $order );

		$subject = '[Together Clinic] Your payment link is waiting';

		$body  = sprintf( '<p>Hi %s,</p>', esc_html( $first ) );
		$body .= sprintf( '<p>A quick reminder &mdash; your approved treatment (<strong>%s</strong>) is ready and waiting for payment.</p>', esc_html( $items ) );
		$body .= self::button( $order->get_checkout_payment_url(), 'Pay securely now' );
		$body .= sprintf( '<p style="font-size:13px;color:#6b7280;">This payment link is valid until %s. If you no longer wish to proceed, you can simply ignore this email &mdash; no payment will be taken.</p>', esc_html( $expiry ) );
		$body .= self::footer();

		return self::send( $order, $email, $subject, $body, 'reminder' );
	}

	public static function send_expired( WC_Order $order ) {
		$email = $order->get_billing_email();
		if ( ! is_email( $email ) ) {
			return false;
		}

		$first = $order->get_billing_first_name() ?: 'there';

		$subject = '[Together Clinic] Your payment link has expired';

		$body  = sprintf( '<p>Hi %s,</p>', esc_html( $first ) );
		$body .= '<p>The payment link for your approved treatment has now expired, and your order has been cancelled. <strong>No payment has been taken.</strong></p>';
		$body .= '<p>If you would still like to go ahead, just reply to this email or contact us and we will set it up again for you.</p>';
		$body .= self::footer();

		return self::send( $order, $email, $subject, $body, 'expired' );
	}

	private static function item_summary( WC_Order $order ) {
		$names = [];
		foreach ( $order->get_items() as $item ) {
			$names[] = $item->get_name();
		}
		return implode( ', ', $names );
	}

	private static function expiry_date( WC_Order $order ) {
		$sent_at = (int) $order->get_meta( '_tc_review_paylink_sent_at' );
		if ( ! $sent_at ) {
			$sent_at = time();
		}
		$days = (int) apply_filters( 'tc_review_expiry_days', 7 );
		return date_i18n( get_option( 'date_format' ), $sent_at + $days * DAY_IN_SECONDS );
	}

	private static function booking_url() {
		$url = get_option( 'tc_eligibility_calendly_returning', '' );
		return $url ?: home_url( '/contact/' );
	}

	private static function button( $url, $label ) {
		return sprintf(
			'<p style="margin:24px 0;"><a href="%s" style="display:inline-block;background:#8e88d0;color:#ffffff;padding:14px 28px;text-decoration:none;border-radius:8px;font-weight:600;">%s</a></p>',
			esc_url( $url ),
			esc_html( $label )
		);
	}

	private static function footer() {
		return sprintf(
			'<p style="margin-top:32px;">If you have any questions, contact us at <a href="mailto:%1$s">%1$s</a>.</p><p>Best wishes,<br>The Together Clinic team</p>',
			esc_attr( sanitize_email( get_option( 'tc_eligibility_from_email', 'care@togetherclinic.co.uk' ) ) )
		);
	}

	private static function send( WC_Order $order, $email, $subject, $body, $kind ) {
		$from_email = sanitize_email( get_option( 'tc_eligibility_from_email', 'care@togetherclinic.co.uk' ) );
		$from_name  = sanitize_text_field( get_option( 'tc_eligibility_from_name', 'Together Clinic' ) );

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		if ( $from_email ) {
			$headers[] = sprintf( 'From: %s <%s>', $from_name ?: 'Together Clinic', $from_email );
			$headers[] = sprintf( 'Reply-To: %s', $from_email );
		}

		if ( function_exists( 'WC' ) && WC()->mailer() && method_exists( WC()->mailer(), 'wrap_message' ) ) {
			$body = WC()->mailer()->wrap_message( '', $body );
		}

		$sent = wp_mail( $email, $subject, $body, $headers );

		TC_Log::info( 'review_email_' . $kind . '_' . ( $sent ? 'sent' : 'failed' ), [
			'order_id' => $order->get_id(),
			'to'       => $email,
		] );

		return $sent;
	}
}
