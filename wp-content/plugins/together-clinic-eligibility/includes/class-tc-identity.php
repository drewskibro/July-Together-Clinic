<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Automated identity verification via Stripe Identity.
 *
 * Sits ALONGSIDE TC_Secure_Docs rather than replacing it. When Stripe
 * Identity is available the patient is offered the automated check; the
 * manual upload stays as the fallback, so a Stripe outage, an unsupported
 * document or a failed check never leaves a patient unable to proceed.
 *
 * Design notes:
 *
 *  - FAIL CLOSED, quietly. is_enabled() is false unless a Stripe secret key
 *    is actually readable. Nothing is offered to the patient until then, and
 *    the manual upload path is unaffected.
 *  - NO DOCUMENTS TOUCH THIS SERVER. Stripe hosts the capture flow and holds
 *    the evidence; we store a session id and a status. That is the point of
 *    using it — the safest patient document is one we never held.
 *  - Two independent result paths. The return URL check gives the patient
 *    (and the order) an immediate answer; the webhook is authoritative and
 *    catches asynchronous outcomes. Either alone is enough to record a
 *    result, so a missing webhook secret degrades rather than breaks.
 *  - Keys are read from the WooCommerce Stripe gateway, respecting its
 *    test/live toggle, so there is one place to configure Stripe.
 *  - The prescriber panel always states what was checked and when. An
 *    automated pass is evidence for the clinician, never a decision.
 */
class TC_Identity {

	const META_SESSION   = '_tc_idv_session';
	const META_STATUS    = '_tc_idv_status';     // verified | processing | requires_input | canceled
	const META_CHECKED   = '_tc_idv_checked_at';
	const META_LAST_ERR  = '_tc_idv_last_error';

	const OPT_ENABLED    = 'tc_idv_enabled';
	const OPT_WEBHOOK    = 'tc_idv_webhook_secret';

	const API_BASE       = 'https://api.stripe.com/v1/identity/verification_sessions';

	public static function init() {
		add_action( 'admin_post_tc_start_idv', [ __CLASS__, 'handle_start' ] );
		add_action( 'admin_post_nopriv_tc_start_idv', [ __CLASS__, 'handle_start' ] );
		add_action( 'template_redirect', [ __CLASS__, 'maybe_sync_on_return' ] );
		add_action( 'rest_api_init', [ __CLASS__, 'register_webhook' ] );
	}

	/* ---------------------------------------------------------------
	 * Configuration
	 * ------------------------------------------------------------- */

	/**
	 * Stripe secret key, taken from the WooCommerce Stripe gateway so there
	 * is a single source of truth for mode and credentials. Returns '' when
	 * unavailable — callers must treat that as "feature off".
	 */
	public static function secret_key() {
		$key = defined( 'TC_STRIPE_SECRET_KEY' ) ? (string) TC_STRIPE_SECRET_KEY : '';

		if ( ! $key ) {
			$settings = get_option( 'woocommerce_stripe_settings', [] );
			if ( is_array( $settings ) ) {
				$test = isset( $settings['testmode'] ) && 'yes' === $settings['testmode'];
				$key  = $test
					? (string) ( $settings['test_secret_key'] ?? '' )
					: (string) ( $settings['secret_key'] ?? '' );
			}
		}

		return (string) apply_filters( 'tc_idv_secret_key', trim( $key ) );
	}

	/** True only when the feature is switched on AND a key is readable. */
	public static function is_enabled() {
		if ( '1' !== get_option( self::OPT_ENABLED, '1' ) ) {
			return false;
		}
		return self::secret_key() !== '';
	}

	public static function webhook_secret() {
		$secret = defined( 'TC_STRIPE_IDENTITY_WEBHOOK_SECRET' )
			? (string) TC_STRIPE_IDENTITY_WEBHOOK_SECRET
			: (string) get_option( self::OPT_WEBHOOK, '' );

		return trim( $secret );
	}

	public static function webhook_url() {
		return rest_url( 'tc-identity/v1/webhook' );
	}

	/* ---------------------------------------------------------------
	 * Order state
	 * ------------------------------------------------------------- */

	public static function status( WC_Order $order ) {
		return (string) $order->get_meta( self::META_STATUS );
	}

	public static function is_verified( WC_Order $order ) {
		return 'verified' === self::status( $order );
	}

	/** Identity is settled if Stripe verified it OR a document was uploaded. */
	public static function has_any_identity( WC_Order $order ) {
		if ( self::is_verified( $order ) ) {
			return true;
		}
		return class_exists( 'TC_Secure_Docs' ) && TC_Secure_Docs::has_document( $order );
	}

	public static function start_url( WC_Order $order ) {
		return add_query_arg(
			[
				'action'    => 'tc_start_idv',
				'order_id'  => $order->get_id(),
				'order_key' => $order->get_order_key(),
				'_wpnonce'  => wp_create_nonce( 'tc_start_idv_' . $order->get_id() ),
			],
			admin_url( 'admin-post.php' )
		);
	}

	/* ---------------------------------------------------------------
	 * Stripe API
	 * ------------------------------------------------------------- */

	private static function request( $url, $args = [], $method = 'POST' ) {
		$key = self::secret_key();
		if ( ! $key ) {
			return new WP_Error( 'tc_idv_no_key', 'Stripe is not configured.' );
		}

		$params = [
			'method'  => $method,
			'timeout' => 20,
			'headers' => [
				'Authorization'  => 'Bearer ' . $key,
				'Content-Type'   => 'application/x-www-form-urlencoded',
				'Stripe-Version' => '2024-06-20',
			],
		];

		if ( 'POST' === $method && $args ) {
			$params['body'] = $args;
		}

		$response = wp_remote_request( $url, $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 || ! is_array( $body ) ) {
			$msg = is_array( $body ) && isset( $body['error']['message'] )
				? (string) $body['error']['message']
				: 'Stripe returned HTTP ' . $code;
			return new WP_Error( 'tc_idv_api', $msg );
		}

		return $body;
	}

	/* ---------------------------------------------------------------
	 * Patient: begin verification
	 * ------------------------------------------------------------- */

	public static function handle_start() {
		$order_id  = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		$order_key = isset( $_GET['order_key'] ) ? sanitize_text_field( wp_unslash( $_GET['order_key'] ) ) : '';
		$order     = $order_id ? wc_get_order( $order_id ) : false;

		// Same trust model as the order-pay page: possession of the order key.
		if ( ! $order || ! hash_equals( (string) $order->get_order_key(), $order_key ) ) {
			wp_die( esc_html__( 'This verification link is not valid.', 'together-clinic-eligibility' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( 'tc_start_idv_' . $order_id );

		$return = self::order_return_url( $order );

		if ( ! self::is_enabled() ) {
			wp_safe_redirect( add_query_arg( 'tc_idv', 'unavailable', $return ) );
			exit;
		}

		$session = self::request( self::API_BASE, [
			'type'                                        => 'document',
			'return_url'                                  => add_query_arg( 'tc_idv', 'done', $return ),
			'metadata[order_id]'                          => (string) $order->get_id(),
			'metadata[order_key]'                         => (string) $order->get_order_key(),
			'options[document][require_matching_selfie]'  => 'true',
			'options[document][require_live_capture]'     => 'true',
		] );

		if ( is_wp_error( $session ) || empty( $session['url'] ) ) {
			TC_Log::error( 'idv_session_create_failed', [
				'order_id' => $order->get_id(),
				'error'    => is_wp_error( $session ) ? $session->get_error_message() : 'no url returned',
			] );
			// Fail soft: the manual upload is still on the page.
			wp_safe_redirect( add_query_arg( 'tc_idv', 'unavailable', $return ) );
			exit;
		}

		$order->update_meta_data( self::META_SESSION, sanitize_text_field( $session['id'] ) );
		$order->update_meta_data( self::META_STATUS, sanitize_text_field( $session['status'] ?? 'processing' ) );
		$order->save();

		TC_Log::info( 'idv_session_created', [
			'order_id'   => $order->get_id(),
			'session_id' => $session['id'],
		] );

		wp_redirect( esc_url_raw( $session['url'] ) ); // Stripe-hosted, off-site.
		exit;
	}

	private static function order_return_url( WC_Order $order ) {
		return $order->get_checkout_order_received_url();
	}

	/* ---------------------------------------------------------------
	 * Result path 1 — patient returns from Stripe
	 * ------------------------------------------------------------- */

	public static function maybe_sync_on_return() {
		if ( ! isset( $_GET['tc_idv'] ) || 'done' !== sanitize_key( wp_unslash( $_GET['tc_idv'] ) ) ) {
			return;
		}
		if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
			return;
		}

		$order_id = absint( get_query_var( 'order-received' ) );
		$order    = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $order ) {
			return;
		}

		self::sync( $order );
	}

	/** Pull the session's current state from Stripe and record it. */
	public static function sync( WC_Order $order ) {
		$session_id = (string) $order->get_meta( self::META_SESSION );
		if ( ! $session_id || ! self::is_enabled() ) {
			return;
		}

		$session = self::request( self::API_BASE . '/' . rawurlencode( $session_id ), [], 'GET' );
		if ( is_wp_error( $session ) ) {
			TC_Log::warn( 'idv_sync_failed', [
				'order_id' => $order->get_id(),
				'error'    => $session->get_error_message(),
			] );
			return;
		}

		self::record( $order, $session );
	}

	/**
	 * Single place where a Stripe session result becomes order state, so the
	 * return path and the webhook cannot diverge.
	 */
	private static function record( WC_Order $order, array $session ) {
		$status = sanitize_text_field( $session['status'] ?? '' );
		if ( ! $status ) {
			return;
		}

		$previous = self::status( $order );

		$order->update_meta_data( self::META_STATUS, $status );
		$order->update_meta_data( self::META_CHECKED, time() );

		$error = '';
		if ( isset( $session['last_error']['reason'] ) ) {
			$error = sanitize_text_field( (string) $session['last_error']['reason'] );
		}
		$order->update_meta_data( self::META_LAST_ERR, $error );

		// One note per state change — the clinical record, not a running log.
		if ( $previous !== $status ) {
			if ( 'verified' === $status ) {
				$order->add_order_note( 'Identity verified automatically by Stripe Identity (document and selfie).' );
			} elseif ( 'requires_input' === $status ) {
				$order->add_order_note( 'Automated identity check could not be completed'
					. ( $error ? ' (' . $error . ')' : '' )
					. '. Confirm identity another way before approving.' );
			}
		}

		$order->save();

		TC_Log::info( 'idv_status_recorded', [
			'order_id' => $order->get_id(),
			'status'   => $status,
		] );
	}

	/* ---------------------------------------------------------------
	 * Result path 2 — webhook (authoritative, handles async outcomes)
	 * ------------------------------------------------------------- */

	public static function register_webhook() {
		register_rest_route( 'tc-identity/v1', '/webhook', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'handle_webhook' ],
			'permission_callback' => '__return_true', // Authenticated by Stripe's signature below.
		] );
	}

	public static function handle_webhook( WP_REST_Request $request ) {
		$secret = self::webhook_secret();
		if ( ! $secret ) {
			// Not configured: refuse rather than trust an unsigned payload.
			return new WP_REST_Response( [ 'error' => 'not configured' ], 400 );
		}

		$payload   = $request->get_body();
		$signature = (string) $request->get_header( 'stripe-signature' );

		if ( ! self::signature_is_valid( $payload, $signature, $secret ) ) {
			TC_Log::warn( 'idv_webhook_bad_signature', [] );
			return new WP_REST_Response( [ 'error' => 'invalid signature' ], 400 );
		}

		$event = json_decode( $payload, true );
		$type  = is_array( $event ) ? ( $event['type'] ?? '' ) : '';

		if ( 0 !== strpos( (string) $type, 'identity.verification_session.' ) ) {
			return new WP_REST_Response( [ 'ignored' => true ], 200 );
		}

		$session  = $event['data']['object'] ?? [];
		$order_id = absint( $session['metadata']['order_id'] ?? 0 );
		$order    = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $order ) {
			return new WP_REST_Response( [ 'ignored' => true ], 200 );
		}

		// Only accept a result for the session this order actually started.
		$known = (string) $order->get_meta( self::META_SESSION );
		if ( $known && isset( $session['id'] ) && ! hash_equals( $known, (string) $session['id'] ) ) {
			TC_Log::warn( 'idv_webhook_session_mismatch', [ 'order_id' => $order_id ] );
			return new WP_REST_Response( [ 'ignored' => true ], 200 );
		}

		self::record( $order, is_array( $session ) ? $session : [] );

		return new WP_REST_Response( [ 'received' => true ], 200 );
	}

	/**
	 * Stripe signature check: HMAC-SHA256 over "<timestamp>.<payload>",
	 * compared in constant time, with a 5-minute replay window.
	 */
	private static function signature_is_valid( $payload, $header, $secret ) {
		if ( ! $header ) {
			return false;
		}

		$timestamp = '';
		$signatures = [];

		foreach ( explode( ',', $header ) as $part ) {
			$pair = explode( '=', trim( $part ), 2 );
			if ( count( $pair ) !== 2 ) {
				continue;
			}
			if ( 't' === $pair[0] ) {
				$timestamp = $pair[1];
			} elseif ( 'v1' === $pair[0] ) {
				$signatures[] = $pair[1];
			}
		}

		if ( ! $timestamp || ! $signatures ) {
			return false;
		}

		if ( abs( time() - (int) $timestamp ) > 300 ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );

		foreach ( $signatures as $candidate ) {
			if ( hash_equals( $expected, $candidate ) ) {
				return true;
			}
		}

		return false;
	}

	/* ---------------------------------------------------------------
	 * Prescriber view
	 * ------------------------------------------------------------- */

	public static function render_admin_panel( WC_Order $order ) {
		if ( ! class_exists( 'TC_Review_Status' ) || ! TC_Review_Status::is_treatment_order( $order ) ) {
			return;
		}

		$status = self::status( $order );
		if ( ! $status ) {
			return; // Nothing attempted — TC_Secure_Docs already reports on the document.
		}

		$at    = (int) $order->get_meta( self::META_CHECKED );
		$when  = $at ? date_i18n( get_option( 'date_format' ) . ' H:i', $at ) : '&mdash;';
		$error = (string) $order->get_meta( self::META_LAST_ERR );

		if ( 'verified' === $status ) {
			printf(
				'<div style="background:#ecfdf5;border-left:4px solid #10b981;padding:10px 14px;margin:12px 0;clear:both;"><strong>Identity verified automatically.</strong> Stripe Identity checked the patient&rsquo;s photo ID and a matching selfie on %s. <em>Confirms who they are &mdash; clinical suitability is still your decision.</em></div>',
				esc_html( $when )
			);
			return;
		}

		if ( 'processing' === $status ) {
			echo '<div style="background:#eff6ff;border-left:4px solid #3b82f6;padding:10px 14px;margin:12px 0;clear:both;"><strong>Identity check in progress.</strong> Stripe is still processing the patient&rsquo;s documents. This usually completes within a few minutes.</div>';
			return;
		}

		printf(
			'<div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:10px 14px;margin:12px 0;clear:both;"><strong>Automated identity check not completed.</strong> Status: %s%s. Confirm identity another way before approving.</div>',
			esc_html( $status ),
			$error ? ' (' . esc_html( $error ) . ')' : ''
		);
	}
}
