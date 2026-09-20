<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Together Health's Prescribing & Consultation Platform hand-off (CD-09
 * item 4). This site pushes; the platform never polls it.
 *
 * Outbound: on `tc_review_order_created` (TC_Review_Order), POST the
 * patient then POST their pre-consultation intake. A failure retries via
 * WP-Cron (three attempts total, backoff) and leaves a manual "Send to
 * prescribing platform" order action for anything that still hasn't synced.
 *
 * Inbound: `POST /wp-json/tc/v1/platform-webhook`, HMAC-signed exactly as
 * `packages/contracts/src/signature.ts` computes it — verified before the
 * body is ever parsed. `prescription.issued` approves the order;
 * `consultation.declined` rejects it. Idempotent on the event id.
 *
 * Fail closed throughout: disabled or unconfigured means nothing is sent
 * and nothing is accepted, only a persistent admin notice on the order
 * screens saying so — never a partial push, never an unauthenticated
 * webhook route left silently open, never a spoofed WordPress reviewer.
 */
class TC_Platform_Sync {

	const OPTION_ENABLED        = 'tc_platform_sync_enabled';
	const OPTION_BASE_URL       = 'tc_platform_base_url';
	const OPTION_API_KEY        = 'tc_platform_api_key';
	const OPTION_WEBHOOK_SECRET = 'tc_platform_webhook_secret';

	const META_PATIENT_ID     = '_tc_platform_patient_id';
	const META_SYNCED_AT      = '_tc_platform_synced_at';
	const META_SYNC_ATTEMPTS  = '_tc_platform_sync_attempts';
	const META_SYNC_LAST_ERROR = '_tc_platform_sync_last_error';

	/** `packages/contracts/src/signature.ts`: WEBHOOK_SIGNATURE_HEADER / TOLERANCE_SECONDS. */
	const SIGNATURE_HEADER    = 'X-Together-Signature';
	const SIGNATURE_TOLERANCE = 300;

	const RETRY_HOOK  = 'tc_platform_sync_retry';
	const MAX_ATTEMPTS = 3;

	/** Bounded (last 500) list of processed webhook event ids, one site option — never per-order meta. */
	const OPTION_PROCESSED_EVENTS = 'tc_platform_processed_event_ids';
	const PROCESSED_EVENTS_LIMIT  = 500;

	/** Identity fields stripped from the raw payload before it is sent as `answers`. */
	const IDENTITY_KEYS = [
		'firstName', 'lastName', 'fullName', 'email', 'phone', 'dob',
		'addressLine1', 'addressLine2', 'city', 'postcode', 'country',
	];

	public function __construct() {
		add_action( 'tc_review_order_created', [ __CLASS__, 'on_order_created' ], 10, 2 );
		add_action( self::RETRY_HOOK, [ __CLASS__, 'run_retry' ] );
		add_action( 'admin_notices', [ __CLASS__, 'sync_disabled_notice' ] );
		add_filter( 'woocommerce_order_actions', [ __CLASS__, 'add_manual_sync_action' ], 10, 2 );
		add_action( 'woocommerce_order_action_tc_platform_manual_sync', [ __CLASS__, 'manual_sync' ] );
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	// ======================================================================
	// Configuration (constant-preferred, option fallback — same convention
	// as TC_Stripe_Payment_Provider::credential()).
	// ======================================================================

	public static function is_enabled() {
		return '1' === get_option( self::OPTION_ENABLED, '0' )
			&& '' !== self::base_url()
			&& '' !== self::api_key();
	}

	public static function base_url() {
		return untrailingslashit( self::credential( 'TC_PLATFORM_BASE_URL', self::OPTION_BASE_URL ) );
	}

	public static function api_key() {
		return self::credential( 'TC_PLATFORM_API_KEY', self::OPTION_API_KEY );
	}

	public static function webhook_secret() {
		return self::credential( 'TC_PLATFORM_WEBHOOK_SECRET', self::OPTION_WEBHOOK_SECRET );
	}

	private static function credential( $constant, $option ) {
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

	/**
	 * Delay before retry N (1-indexed by the attempt that just failed).
	 * A method, not a class constant: a class constant's expression is
	 * evaluated when the file is parsed, so `5 * MINUTE_IN_SECONDS` would
	 * fatal-error the moment this file loads outside WordPress (Opus
	 * review, minor 8) — tests/platform-sync-smoke-test.php included.
	 */
	private static function retry_delays() {
		$minute = defined( 'MINUTE_IN_SECONDS' ) ? MINUTE_IN_SECONDS : 60;
		return [ 5 * $minute, 30 * $minute ];
	}

	/**
	 * `tc-order-<id>` -> `<id>`, or null when the reference is not shaped
	 * that way at all. Namespaced rather than a bare order id (Opus review,
	 * M1): externalReference is unique only per tenant, not per API key, so
	 * an unnamespaced numeric id would let any OTHER key-holder in the same
	 * tenant reach this same patient through the upsert on `POST
	 * /v1/patients` just by guessing a small integer.
	 */
	private static function order_id_from_external_reference( $external_reference ) {
		if ( 1 === preg_match( '/^tc-order-(\d+)$/', (string) $external_reference, $matches ) ) {
			return (int) $matches[1];
		}
		return null;
	}

	/**
	 * Persistent admin notice on the order screens (and the eligibility
	 * settings screen) while sync is off or unconfigured — CLAUDE.md's
	 * fail-closed rule: no silent no-op, always visible until resolved.
	 */
	public static function sync_disabled_notice() {
		if ( self::is_enabled() ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return;
		}
		$relevant = false !== strpos( $screen->id, 'shop_order' )
			|| false !== strpos( $screen->id, 'shop-order' )
			|| false !== strpos( $screen->id, 'wc-orders' )
			|| false !== strpos( $screen->id, 'tc-eligibility' );
		if ( ! $relevant ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p><strong>Together Clinic:</strong> orders are not being sent to the prescribing platform. Configure it under <a href="%s">WooCommerce &rarr; Eligibility &rarr; Prescribing platform</a> and tick &ldquo;Send orders to the prescribing platform&rdquo;.</p></div>',
			esc_url( admin_url( 'admin.php?page=' . TC_Settings::MENU_SLUG ) )
		);
	}

	// ======================================================================
	// Outbound push
	// ======================================================================

	public static function on_order_created( $order, $payload ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		self::push( $order, is_array( $payload ) ? $payload : [] );
	}

	public static function manual_sync( WC_Order $order ) {
		self::push( $order, self::raw_payload( $order ) );
	}

	private static function raw_payload( WC_Order $order ) {
		$raw = $order->get_meta( TC_Checkout::ORDER_META_RAW );
		$decoded = $raw ? json_decode( $raw, true ) : null;
		return is_array( $decoded ) ? $decoded : [];
	}

	public static function push( WC_Order $order, array $payload ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		// A dob that fails to parse is a data problem, not a transient one:
		// fail fast with an order note and schedule no retry (Opus review,
		// minor 6). "Send to prescribing platform" is the only way back,
		// after the date is fixed on the order.
		$raw_dob = trim( (string) ( $payload['dob'] ?? '' ) );
		if ( '' !== $raw_dob && '' === self::normalise_dob( $raw_dob ) ) {
			$order->add_order_note( sprintf(
				'Prescribing platform sync stopped: the date of birth on this assessment ("%s") could not be read. Correct it and use "Send to prescribing platform" to retry.',
				sanitize_text_field( $raw_dob )
			) );
			$order->save();
			TC_Log::warn( 'platform_sync_unparseable_dob', [ 'order_id' => $order->get_id() ] );
			return;
		}

		$order_id           = $order->get_id();
		// Namespaced, not a bare order id (Opus review, M1) — see
		// order_id_from_external_reference()'s own comment.
		$external_reference = 'tc-order-' . $order_id;

		$patient_body = self::patient_request_body( $order, $payload, $external_reference );
		$patient      = self::request(
			'POST',
			'/v1/patients',
			$patient_body,
			'tc-order-' . $order_id . '-patient'
		);

		if ( is_wp_error( $patient ) || empty( $patient['id'] ) ) {
			self::record_failure(
				$order,
				is_wp_error( $patient ) ? $patient->get_error_message() : 'patient sync returned no id'
			);
			return;
		}

		$order->update_meta_data( self::META_PATIENT_ID, $patient['id'] );
		$order->save();

		$intake_body = self::pre_consultation_body( $payload );
		$intake      = self::request(
			'POST',
			'/v1/patients/' . rawurlencode( (string) $patient['id'] ) . '/pre-consultation',
			$intake_body,
			'tc-order-' . $order_id . '-intake'
		);

		if ( is_wp_error( $intake ) ) {
			self::record_failure( $order, $intake->get_error_message() );
			return;
		}

		$order->update_meta_data( self::META_SYNCED_AT, time() );
		$order->update_meta_data( self::META_SYNC_ATTEMPTS, 0 );
		$order->delete_meta_data( self::META_SYNC_LAST_ERROR );
		$order->save();
		$order->add_order_note( 'Synced to the prescribing platform.' );

		TC_Log::info( 'platform_sync_ok', [
			'order_id'   => $order_id,
			'patient_id' => $patient['id'],
		] );
	}

	/**
	 * @param WC_Order $order
	 * @param array    $payload             Raw eligibility assessment payload.
	 * @param string   $external_reference  `tc-order-<id>`, the platform's patient key.
	 */
	private static function patient_request_body( WC_Order $order, array $payload, $external_reference ) {
		$first_name = $payload['firstName'] ?? '';
		$last_name  = $payload['lastName'] ?? '';
		if ( ! $first_name && ! empty( $payload['fullName'] ) ) {
			list( $first_name, $last_name ) = TC_Cookie_Store::split_full_name( $payload['fullName'] );
		}
		$legal_name = trim( $first_name . ' ' . $last_name );
		if ( '' === $legal_name ) {
			$legal_name = $order->get_formatted_billing_full_name();
		}

		$address_lines = array_values( array_filter( [
			(string) ( $payload['addressLine1'] ?? $order->get_billing_address_1() ),
			(string) ( $payload['addressLine2'] ?? $order->get_billing_address_2() ),
			(string) ( $payload['city'] ?? $order->get_billing_city() ),
		], static function ( $line ) {
			return '' !== trim( $line );
		} ) );
		if ( empty( $address_lines ) ) {
			$address_lines = [ 'Not recorded' ];
		}

		list( $country, $country_needs_review ) = self::normalise_country( $payload['country'] ?? '' );
		if ( $country_needs_review ) {
			$order->add_order_note( 'Country could not be matched to a UK nation for the prescribing platform sync; defaulted to England. Please verify the patient\'s address.' );
		}

		$body = [
			'externalReference' => $external_reference,
			'legalName'         => $legal_name,
			'dateOfBirth'       => self::normalise_dob( $payload['dob'] ?? '' ),
			'sexAtBirth'        => (string) ( $payload['sex'] ?? 'not recorded' ),
			'email'             => (string) ( $payload['email'] ?? $order->get_billing_email() ),
			'addressLines'      => $address_lines,
			'postcode'          => (string) ( $payload['postcode'] ?? $order->get_billing_postcode() ),
			'country'           => $country,
		];

		$phone = (string) ( $payload['phone'] ?? $order->get_billing_phone() );
		if ( '' !== trim( $phone ) ) {
			$body['phone'] = $phone;
		}

		return $body;
	}

	/** @param array $payload Raw eligibility assessment payload. */
	private static function pre_consultation_body( array $payload ) {
		$answers = $payload;
		foreach ( self::IDENTITY_KEYS as $key ) {
			unset( $answers[ $key ] );
		}

		$gp = [];
		if ( ! empty( $payload['gpName'] ) ) {
			$gp['name'] = (string) $payload['gpName'];
		}
		if ( ! empty( $payload['gpPostcode'] ) ) {
			$gp['postcode'] = (string) $payload['gpPostcode'];
		}

		$measurements = [];
		if ( isset( $payload['heightCm'] ) && is_numeric( $payload['heightCm'] ) ) {
			$measurements['heightCm'] = (float) $payload['heightCm'];
		}
		if ( isset( $payload['weightKg'] ) && is_numeric( $payload['weightKg'] ) ) {
			$measurements['weightKg'] = (float) $payload['weightKg'];
		}

		return [
			// (object): PHP encodes an empty [] as a JSON array, and the
			// platform's zod schema requires an object for `answers`, `gp`
			// and `measurements` even when nothing is captured for them —
			// this is what json_safe_answers() and the two builders above
			// can validly produce (no GP details, no reported height/weight).
			'answers'      => (object) self::json_safe_answers( $answers ),
			// Exactly the three questions this site actually asks (Opus
			// review, B2): terms_agreed, gp_consent_share, gp_consent_scr.
			// There is no gpRecords question at all, so that key is never
			// sent — the platform records nothing for a consent key that is
			// absent, rather than coercing a question nobody was asked into
			// a recorded decline. service defaults to false, never true,
			// when termsAgreed is somehow absent: it must never be assumed
			// given.
			'consents'     => [
				'service'   => self::truthy( $payload['termsAgreed'] ?? false ),
				'gpShare'   => self::truthy( $payload['gpConsentShare'] ?? false ),
				'scrAccess' => isset( $payload['gpConsentSCR'] ) ? self::truthy( $payload['gpConsentSCR'] ) : false,
			],
			'gp'           => (object) $gp,
			'measurements' => (object) $measurements,
		];
	}

	/** Coerces a payload sub-array to the platform's `answers` shape (string|boolean|number values only). */
	private static function json_safe_answers( array $answers ) {
		$safe = [];
		foreach ( $answers as $key => $value ) {
			if ( is_string( $value ) || is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
				$safe[ $key ] = $value;
			} elseif ( null === $value ) {
				continue;
			} else {
				$safe[ $key ] = wp_json_encode( $value );
			}
		}
		return $safe;
	}

	private static function truthy( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		$value = strtolower( trim( (string) $value ) );
		return in_array( $value, [ '1', 'true', 'yes', 'on' ], true );
	}

	/** United Kingdom / blank / unrecognised all default to ENGLAND, flagged for review. */
	private static function normalise_country( $raw ) {
		$map = [
			'england'          => 'ENGLAND',
			'scotland'         => 'SCOTLAND',
			'wales'            => 'WALES',
			'northern ireland' => 'NORTHERN_IRELAND',
		];
		$key = strtolower( trim( (string) $raw ) );
		if ( isset( $map[ $key ] ) ) {
			return [ $map[ $key ], false ];
		}
		return [ 'ENGLAND', true ];
	}

	/** The site does not guarantee an ISO date in `dob`; normalise or fail loudly (never send garbage). */
	private static function normalise_dob( $dob ) {
		$dob = trim( (string) $dob );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dob ) ) {
			return $dob;
		}
		if ( '' === $dob ) {
			return '';
		}
		try {
			$date = new DateTime( $dob );
			return $date->format( 'Y-m-d' );
		} catch ( Exception $e ) {
			return '';
		}
	}

	// ======================================================================
	// HTTP
	// ======================================================================

	/**
	 * @return array|WP_Error Decoded JSON body on 2xx, WP_Error otherwise.
	 */
	private static function request( $method, $path, array $body, $idempotency_key ) {
		$response = wp_remote_request( self::base_url() . $path, [
			'method'    => $method,
			'timeout'   => 15,
			'sslverify' => true,
			'headers'   => [
				'Authorization'   => 'Bearer ' . self::api_key(),
				'Content-Type'    => 'application/json',
				'Idempotency-Key' => $idempotency_key,
			],
			'body'      => wp_json_encode( $body ),
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code    = (int) wp_remote_retrieve_response_code( $response );
		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$detail = is_array( $decoded ) && ! empty( $decoded['title'] ) ? $decoded['title'] : ( 'HTTP ' . $code );
			return new WP_Error( 'tc_platform_http_error', (string) $detail, [ 'status' => $code ] );
		}

		return is_array( $decoded ) ? $decoded : [];
	}

	// ======================================================================
	// Retry (WP-Cron, three attempts total, backoff)
	// ======================================================================

	private static function record_failure( WC_Order $order, $message ) {
		$attempts = (int) $order->get_meta( self::META_SYNC_ATTEMPTS ) + 1;
		$order->update_meta_data( self::META_SYNC_ATTEMPTS, $attempts );
		// No health content here — the message is an HTTP/transport error,
		// never the clinical payload.
		$order->update_meta_data( self::META_SYNC_LAST_ERROR, sanitize_text_field( (string) $message ) );
		$order->add_order_note( sprintf( 'Prescribing platform sync failed (attempt %d of %d).', $attempts, self::MAX_ATTEMPTS ) );
		$order->save();

		TC_Log::warn( 'platform_sync_failed', [
			'order_id' => $order->get_id(),
			'attempt'  => $attempts,
		] );

		if ( $attempts < self::MAX_ATTEMPTS ) {
			$delays = self::retry_delays();
			$delay  = $delays[ $attempts - 1 ] ?? end( $delays );
			wp_schedule_single_event( time() + $delay, self::RETRY_HOOK, [ $order->get_id() ] );
		}
	}

	public static function run_retry( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		// A manual push, or a previous retry that this scheduled event lost
		// the race to, may already have succeeded.
		if ( $order->get_meta( self::META_SYNCED_AT ) ) {
			return;
		}
		self::push( $order, self::raw_payload( $order ) );
	}

	public static function add_manual_sync_action( $actions, $order = null ) {
		if ( ! $order instanceof WC_Order ) {
			global $theorder;
			$order = $theorder;
		}
		if ( ! $order instanceof WC_Order ) {
			return $actions;
		}
		if ( ! self::is_enabled() || ! TC_Review_Status::is_treatment_order( $order ) ) {
			return $actions;
		}
		if ( $order->get_meta( self::META_SYNCED_AT ) ) {
			return $actions;
		}

		$actions['tc_platform_manual_sync'] = 'Send to prescribing platform';
		return $actions;
	}

	// ======================================================================
	// Inbound webhook
	// ======================================================================

	public static function register_routes() {
		register_rest_route( 'tc/v1', '/platform-webhook', [
			'methods'             => 'POST',
			'callback'            => [ __CLASS__, 'handle_webhook' ],
			// Authenticated by the HMAC signature inside the callback, over
			// the exact raw body, before anything is parsed — not by
			// WordPress's cookie/nonce auth, which a server-to-server
			// delivery carries none of.
			'permission_callback' => '__return_true',
		] );
	}

	public static function handle_webhook( WP_REST_Request $request ) {
		$secret = self::webhook_secret();
		if ( '' === $secret ) {
			return new WP_REST_Response( [ 'error' => 'not_configured' ], 401 );
		}

		$raw_body = (string) $request->get_body();
		$header   = (string) $request->get_header( self::SIGNATURE_HEADER );

		if ( ! self::verify_signature( $header, $raw_body, $secret ) ) {
			TC_Log::warn( 'platform_webhook_rejected', [ 'reason' => 'signature' ] );
			return new WP_REST_Response( [ 'error' => 'invalid_signature' ], 401 );
		}

		$event = json_decode( $raw_body, true );
		if ( ! is_array( $event ) || empty( $event['type'] ) || empty( $event['id'] ) ) {
			TC_Log::warn( 'platform_webhook_rejected', [ 'reason' => 'malformed' ] );
			return new WP_REST_Response( [ 'status' => 'ignored' ], 202 );
		}

		$type = (string) $event['type'];
		if ( ! in_array( $type, [ 'prescription.issued', 'consultation.declined' ], true ) ) {
			return new WP_REST_Response( [ 'status' => 'ignored' ], 202 );
		}

		$data                = is_array( $event['data'] ?? null ) ? $event['data'] : [];
		$external_reference  = isset( $data['externalReference'] ) ? (string) $data['externalReference'] : '';
		$order_id            = self::order_id_from_external_reference( $external_reference );
		$order               = ( null !== $order_id ) ? wc_get_order( $order_id ) : false;

		if ( ! $order instanceof WC_Order ) {
			TC_Log::warn( 'platform_webhook_unresolved_order', [
				'type'                => $type,
				'external_reference'  => $external_reference,
			] );
			return new WP_REST_Response( [ 'status' => 'ignored' ], 202 );
		}

		$event_id = (string) $event['id'];
		if ( self::event_already_processed( $event_id ) ) {
			return new WP_REST_Response( [ 'status' => 'duplicate' ], 200 );
		}

		// The order has moved on since it was pushed (approved, rejected or
		// cancelled some other way) — the platform's decision arrived too
		// late to apply. Never silently dropped (Opus review, M2): a loud
		// order note plus a warn log, and the event is still marked
		// processed and answered 200 so the platform does not retry forever.
		if ( TC_Review_Status::STATUS !== $order->get_status() ) {
			$note = ( 'consultation.declined' === $type )
				? 'Prescribing platform declined treatment after this order was approved. Review urgently.'
				: sprintf(
					'Prescribing platform issued a prescription for an order that is no longer awaiting review (currently "%s"). Review urgently.',
					$order->get_status()
				);
			$order->add_order_note( $note );

			TC_Log::warn( 'platform_webhook_unexpected_order_state', [
				'type'     => $type,
				'order_id' => $order->get_id(),
				'status'   => $order->get_status(),
			] );

			self::mark_event_processed( $event_id );
			return new WP_REST_Response( [ 'status' => 'ok' ], 200 );
		}

		// Marked processed only after the action below actually runs (Opus
		// review, minor 2): a crash between marking and acting would
		// otherwise permanently swallow a legitimate delivery.
		TC_Review_Actions::set_reviewer_override( 'Prescribing platform' );
		try {
			if ( 'prescription.issued' === $type ) {
				TC_Review_Actions::approve( $order );
			} else {
				TC_Review_Actions::reject( $order );
			}
		} finally {
			// Always cleared, success or exception (Opus review, minor 1):
			// this is a static override on a shared class and must never
			// leak into an unrelated request that happens to reuse this
			// worker process.
			TC_Review_Actions::set_reviewer_override( null );
		}
		self::mark_event_processed( $event_id );

		TC_Log::info( 'platform_webhook_processed', [
			'type'     => $type,
			'order_id' => $order->get_id(),
		] );

		return new WP_REST_Response( [ 'status' => 'ok' ], 200 );
	}

	/**
	 * `t=<timestamp>,v1=<hex hmac>`, HMAC-SHA256 over `<timestamp>.<body>`,
	 * constant-time comparison, five-minute replay tolerance — exactly
	 * `packages/contracts/src/signature.ts`'s `verifyWebhookSignature`.
	 * Pure and WordPress-free bar `hash_equals`/`hash_hmac` (core PHP), so
	 * `tests/platform-sync-smoke-test.php` exercises it with no WP bootstrap.
	 *
	 * @param string   $header
	 * @param string   $body
	 * @param string   $secret
	 * @param int|null $now    Overrides `time()` — used only by the smoke
	 *                         test, against a fixed vector; every real
	 *                         caller in this class omits it.
	 */
	public static function verify_signature( $header, $body, $secret, $now = null ) {
		$header = trim( (string) $header );
		if ( '' === $header || '' === (string) $secret ) {
			return false;
		}
		if ( ! preg_match( '/^t=(\d+),v1=([0-9a-f]{64})$/', $header, $matches ) ) {
			return false;
		}
		$timestamp = (int) $matches[1];
		$signature = $matches[2];
		$now       = null === $now ? time() : (int) $now;

		if ( abs( $now - $timestamp ) > self::SIGNATURE_TOLERANCE ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $timestamp . '.' . $body, $secret );
		return hash_equals( $expected, $signature );
	}

	/**
	 * Dedupe list lives in one site option, not per-order meta (Opus
	 * review, minor 3): an event id is meaningful platform-wide, and a
	 * single bounded list is simpler to reason about and to inspect than
	 * one growing list per order.
	 */
	private static function event_already_processed( $event_id ) {
		$ids = (array) get_option( self::OPTION_PROCESSED_EVENTS, [] );
		return in_array( $event_id, $ids, true );
	}

	private static function mark_event_processed( $event_id ) {
		$ids   = (array) get_option( self::OPTION_PROCESSED_EVENTS, [] );
		$ids[] = $event_id;
		$ids   = array_slice( array_values( array_unique( $ids ) ), -self::PROCESSED_EVENTS_LIMIT );
		// 'no', not the boolean false: this plugin's floor is WP 6.4, and
		// update_option()'s $autoload only reliably accepts the yes/no
		// strings before WP 6.6 started also taking a bool.
		update_option( self::OPTION_PROCESSED_EVENTS, $ids, 'no' );
	}
}
