<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Stripe adapter — one implementation of TC_Payment_Provider, using the
 * PaymentIntents REST API with manual capture. No Stripe SDK: plain HTTP via the
 * WordPress HTTP API, so there's nothing to keep in sync and the surface is
 * small and auditable.
 *
 * The auth-then-capture mapping:
 *   authorize()  create a PaymentIntent (capture_method=manual) → a hold once it
 *                reaches `requires_capture`; `requires_action` hands a
 *                client_secret back for the browser to complete SCA/3-D Secure.
 *   capture()    POST /payment_intents/{id}/capture   → `succeeded`
 *   void()       POST /payment_intents/{id}/cancel     → `canceled`
 *   retrieve()   GET  /payment_intents/{id}            → current status
 *
 * The hold reference is the PaymentIntent id (pi_…). Every write carries an
 * Idempotency-Key so a retried authorise can never place a second hold.
 *
 * Credentials come from constants (preferred — keep secrets out of the DB) or
 * options. is_configured() is honest: no secret key → false, so the fail-closed
 * registry never routes real money through an unconfigured Stripe.
 *
 * Webhook verification and event→status mapping are implemented and unit-tested
 * here (they need no network); the network calls run for real once the client's
 * Stripe test keys are in place.
 */
class TC_Stripe_Payment_Provider implements TC_Payment_Provider {

	const API_BASE          = 'https://api.stripe.com/v1';
	const API_VERSION       = '2024-06-20';
	const OPTION_SECRET     = 'tc_stripe_secret_key';
	const OPTION_PUBLISHABLE = 'tc_stripe_publishable_key';
	const OPTION_WEBHOOK    = 'tc_stripe_webhook_secret';

	public function id(): string {
		return 'stripe';
	}

	public function label(): string {
		return 'Stripe';
	}

	public function is_configured(): bool {
		return '' !== $this->secret_key();
	}

	/** Publishable key for the browser-side Payment Element. */
	public function publishable_key(): string {
		return $this->credential( 'TC_STRIPE_PUBLISHABLE_KEY', self::OPTION_PUBLISHABLE );
	}

	public function authorize( TC_Payment_Request $request ): TC_Payment_Result {
		$params = [
			'amount'         => $request->amount_minor,
			'currency'       => strtolower( $request->currency ),
			'capture_method' => 'manual',
			'description'    => $request->description,
			'metadata[submission_id]' => $request->submission_id,
		];
		foreach ( $request->metadata as $key => $value ) {
			if ( is_scalar( $value ) ) {
				$params[ 'metadata[' . $key . ']' ] = (string) $value;
			}
		}

		if ( $request->payment_method ) {
			// Server-side confirm with a payment method the client already tokenised.
			$params['payment_method'] = $request->payment_method;
			$params['confirm']        = 'true';
			$params['automatic_payment_methods[enabled]']         = 'true';
			$params['automatic_payment_methods[allow_redirects]'] = 'never';
		} else {
			// Create only; the browser confirms via the Payment Element + client_secret.
			$params['automatic_payment_methods[enabled]'] = 'true';
		}

		$response = $this->post( '/payment_intents', $params, $request->idempotency_key );
		return $this->result_from_intent( $response, $request->amount_minor, $request->currency );
	}

	public function capture( string $hold_ref, ?int $amount_minor = null ): TC_Payment_Result {
		$params = [];
		if ( null !== $amount_minor ) {
			$params['amount_to_capture'] = max( 0, (int) $amount_minor );
		}
		$response = $this->post( '/payment_intents/' . rawurlencode( $hold_ref ) . '/capture', $params, 'cap_' . $hold_ref );
		return $this->result_from_intent( $response, (int) $amount_minor, 'GBP' );
	}

	public function void( string $hold_ref ): TC_Payment_Result {
		$response = $this->post( '/payment_intents/' . rawurlencode( $hold_ref ) . '/cancel', [], 'void_' . $hold_ref );
		return $this->result_from_intent( $response, 0, 'GBP' );
	}

	public function retrieve( string $hold_ref ): TC_Payment_Result {
		$response = $this->get( '/payment_intents/' . rawurlencode( $hold_ref ) );
		return $this->result_from_intent( $response, 0, 'GBP' );
	}

	/**
	 * Verify a Stripe webhook signature and return the decoded event, or null.
	 * Implements Stripe's scheme: signed_payload = "{t}.{raw body}", compared
	 * (constant-time) against the v1 HMAC-SHA256, with a timestamp tolerance to
	 * reject replays. Testable without the network.
	 */
	public function verify_webhook( string $payload, string $signature_header, ?int $now = null, int $tolerance = 300 ): ?array {
		$secret = $this->credential( 'TC_STRIPE_WEBHOOK_SECRET', self::OPTION_WEBHOOK );
		if ( '' === $secret || '' === $signature_header ) {
			return null;
		}

		$timestamp   = null;
		$signatures  = [];
		foreach ( explode( ',', $signature_header ) as $part ) {
			$pair = array_pad( explode( '=', trim( $part ), 2 ), 2, '' );
			if ( 't' === $pair[0] ) {
				$timestamp = $pair[1];
			} elseif ( 'v1' === $pair[0] ) {
				$signatures[] = $pair[1];
			}
		}
		if ( null === $timestamp || '' === $timestamp || empty( $signatures ) ) {
			return null;
		}

		$expected = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
		$matched  = false;
		foreach ( $signatures as $candidate ) {
			if ( hash_equals( $expected, $candidate ) ) {
				$matched = true;
				break;
			}
		}
		if ( ! $matched ) {
			return null;
		}

		$now = null === $now ? time() : $now;
		if ( abs( $now - (int) $timestamp ) > $tolerance ) {
			return null; // replay outside tolerance
		}

		$event = json_decode( $payload, true );
		return is_array( $event ) ? $event : null;
	}

	/**
	 * Map a verified webhook event onto a ledger outcome (hold_ref + status), or
	 * null for event types we don't act on. The PaymentIntent id is the hold_ref.
	 */
	public function event_to_result( array $event ): ?TC_Payment_Result {
		$type   = $event['type'] ?? '';
		$intent = $event['data']['object'] ?? [];
		if ( ! is_array( $intent ) || empty( $intent['id'] ) ) {
			return null;
		}
		$id       = (string) $intent['id'];
		$amount   = (int) ( $intent['amount'] ?? 0 );
		$currency = strtoupper( (string) ( $intent['currency'] ?? 'GBP' ) );

		switch ( $type ) {
			case 'payment_intent.amount_capturable_updated':
				return TC_Payment_Result::authorized( $id, $amount, $currency, $intent );
			case 'payment_intent.succeeded':
				return TC_Payment_Result::captured( $id, (int) ( $intent['amount_received'] ?? $amount ), $currency, $intent );
			case 'payment_intent.canceled':
				return TC_Payment_Result::voided( $id, $amount, $currency, $intent );
			case 'payment_intent.payment_failed':
				$err = $intent['last_payment_error'] ?? [];
				return TC_Payment_Result::failed( (string) ( $err['code'] ?? 'payment_failed' ), (string) ( $err['message'] ?? 'Payment failed.' ), $amount, $currency, $intent );
			default:
				return null;
		}
	}

	/** Map a PaymentIntent response (or transport/API error) to a TC_Payment_Result. */
	private function result_from_intent( $response, int $fallback_amount, string $fallback_currency ): TC_Payment_Result {
		if ( is_wp_error( $response ) ) {
			return TC_Payment_Result::failed( 'network_error', $response->get_error_message(), $fallback_amount, $fallback_currency );
		}

		$code = (int) ( $response['code'] ?? 0 );
		$body = is_array( $response['body'] ?? null ) ? $response['body'] : [];

		if ( $code >= 400 || isset( $body['error'] ) ) {
			$error = is_array( $body['error'] ?? null ) ? $body['error'] : [];
			return TC_Payment_Result::failed(
				(string) ( $error['code'] ?? ( 'http_' . $code ) ),
				(string) ( $error['message'] ?? 'Stripe request failed.' ),
				$fallback_amount,
				$fallback_currency,
				$body
			);
		}

		$id       = (string) ( $body['id'] ?? '' );
		$amount   = (int) ( $body['amount'] ?? $fallback_amount );
		$currency = strtoupper( (string) ( $body['currency'] ?? $fallback_currency ) );
		$status   = (string) ( $body['status'] ?? '' );

		switch ( $status ) {
			case 'requires_capture':
				return TC_Payment_Result::authorized( $id, $amount, $currency, $body );
			case 'succeeded':
				return TC_Payment_Result::captured( $id, (int) ( $body['amount_received'] ?? $amount ), $currency, $body );
			case 'canceled':
				return TC_Payment_Result::voided( $id, $amount, $currency, $body );
			case 'requires_action':
			case 'requires_confirmation':
			case 'requires_payment_method':
			case 'processing':
				return TC_Payment_Result::requires_action( $id, (string) ( $body['client_secret'] ?? '' ), $amount, $currency, $body );
			default:
				return TC_Payment_Result::failed( 'unexpected_status', 'Unexpected PaymentIntent status: ' . $status, $amount, $currency, $body );
		}
	}

	private function post( string $path, array $params, ?string $idempotency_key = null ) {
		$headers = $this->headers();
		if ( $idempotency_key ) {
			$headers['Idempotency-Key'] = $idempotency_key;
		}
		$response = wp_remote_post( self::API_BASE . $path, [
			'headers' => $headers,
			'body'    => http_build_query( $params, '', '&' ),
			'timeout' => 30,
		] );
		return $this->normalize( $response );
	}

	private function get( string $path ) {
		$response = wp_remote_get( self::API_BASE . $path, [
			'headers' => $this->headers(),
			'timeout' => 30,
		] );
		return $this->normalize( $response );
	}

	private function headers(): array {
		return [
			'Authorization'  => 'Bearer ' . $this->secret_key(),
			'Stripe-Version' => self::API_VERSION,
			'Content-Type'   => 'application/x-www-form-urlencoded',
		];
	}

	private function normalize( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return [
			'code' => (int) wp_remote_retrieve_response_code( $response ),
			'body' => is_array( $body ) ? $body : [],
		];
	}

	private function secret_key(): string {
		return $this->credential( 'TC_STRIPE_SECRET_KEY', self::OPTION_SECRET );
	}

	private function credential( string $constant, string $option ): string {
		if ( defined( $constant ) && constant( $constant ) ) {
			return trim( (string) constant( $constant ) );
		}
		if ( function_exists( 'get_option' ) ) {
			$value = get_option( $option, '' );
			if ( is_string( $value ) ) {
				return trim( $value );
			}
		}
		return '';
	}
}
