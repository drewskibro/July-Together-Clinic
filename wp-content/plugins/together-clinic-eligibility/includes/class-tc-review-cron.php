<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Daily housekeeping for the review-then-pay lifecycle:
 * - one payment reminder for approved-but-unpaid orders (~48h after the link)
 * - pay-link expiry (~7 days): cancel + notify, no payment ever taken
 * - an SLA digest to clinician recipients of orders still awaiting review
 *
 * The schedule self-heals on init because deploys here are file copies (SCP)
 * that never fire activation hooks.
 */
class TC_Review_Cron {

	const HOOK = 'tc_review_daily';

	public function __construct() {
		add_action( self::HOOK, [ __CLASS__, 'run' ] );
		add_action( 'init', [ __CLASS__, 'schedule' ] );
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	public static function unschedule() {
		$ts = wp_next_scheduled( self::HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::HOOK );
		}
	}

	public static function run() {
		self::process_unpaid_paylinks();
		self::send_review_queue_digest();
	}

	private static function process_unpaid_paylinks() {
		$reminder_after = (int) apply_filters( 'tc_review_reminder_hours', 48 ) * HOUR_IN_SECONDS;
		$expire_after   = (int) apply_filters( 'tc_review_expiry_days', 7 ) * DAY_IN_SECONDS;
		$now            = time();

		$orders = wc_get_orders( [
			'status' => 'pending',
			'limit'  => -1,
		] );

		$reminded = 0;
		$expired  = 0;

		foreach ( $orders as $order ) {
			if ( ! self::is_treatment_order( $order ) ) {
				continue;
			}

			$sent_at = (int) $order->get_meta( TC_Review_Actions::META_PAYLINK_AT );
			if ( ! $sent_at ) {
				continue;
			}

			if ( $now - $sent_at >= $expire_after ) {
				$order->update_status(
					'cancelled',
					'Payment link expired without payment. Order cancelled automatically; patient notified. No payment was taken.'
				);
				TC_Review_Emails::send_expired( $order );
				$expired++;
				continue;
			}

			$already_reminded = (int) $order->get_meta( TC_Review_Actions::META_REMINDER_AT );
			if ( ! $already_reminded && ( $now - $sent_at >= $reminder_after ) ) {
				TC_Review_Emails::send_reminder( $order );
				$order->update_meta_data( TC_Review_Actions::META_REMINDER_AT, $now );
				$order->add_order_note( 'Payment reminder emailed to the patient.' );
				$order->save();
				$reminded++;
			}
		}

		if ( $reminded || $expired ) {
			TC_Log::info( 'review_cron_paylinks', [ 'reminded' => $reminded, 'expired' => $expired ] );
		}
	}

	private static function send_review_queue_digest() {
		$older_than = (int) apply_filters( 'tc_review_digest_hours', 24 ) * HOUR_IN_SECONDS;

		$orders = wc_get_orders( [
			'status' => TC_Review_Status::STATUS,
			'limit'  => -1,
		] );

		$stale = [];
		foreach ( $orders as $order ) {
			$created = $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0;
			if ( $created && ( time() - $created ) >= $older_than ) {
				$stale[] = $order;
			}
		}

		if ( empty( $stale ) ) {
			return;
		}

		$recipients = TC_Emails::clinician_recipients();
		if ( empty( $recipients ) ) {
			return;
		}

		$subject = sprintf( '[Together Clinic] %d order(s) awaiting prescriber review', count( $stale ) );

		$body = '<p>The following orders have been awaiting prescriber review for more than 24 hours. Patients cannot pay until each is approved.</p>';
		$body .= '<table cellspacing="0" cellpadding="6" border="1" style="width:100%;font-size:13px;border-collapse:collapse;">';
		$body .= '<tr><th align="left">Order</th><th align="left">Patient</th><th align="left">Submitted</th><th align="left"></th></tr>';
		foreach ( $stale as $order ) {
			$body .= sprintf(
				'<tr><td>#%s</td><td>%s</td><td>%s</td><td><a href="%s">Review &rarr;</a></td></tr>',
				esc_html( $order->get_order_number() ),
				esc_html( $order->get_formatted_billing_full_name() ),
				esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( 'j M Y H:i' ) : '' ),
				esc_url( $order->get_edit_order_url() )
			);
		}
		$body .= '</table>';

		if ( function_exists( 'WC' ) && WC()->mailer() && method_exists( WC()->mailer(), 'wrap_message' ) ) {
			$body = WC()->mailer()->wrap_message( '', $body );
		}

		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
		$sent    = wp_mail( $recipients, $subject, $body, $headers );

		TC_Log::info( 'review_cron_digest_' . ( $sent ? 'sent' : 'failed' ), [ 'stale' => count( $stale ) ] );
	}

	private static function is_treatment_order( WC_Order $order ) {
		return in_array( $order->get_created_via(), [ 'tc_eligibility_assessment', 'tc_reorder_submission' ], true )
			|| TC_Review_Status::is_treatment_order( $order );
	}
}
