<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Together Health's Prescribing & Consultation Platform hand-off (CD-16
 * item 4). This site pushes; the platform never polls it.
 *
 * Outbound: POST the patient (with the platform's `order` block) then POST
 * their pre-consultation intake. A first order is pushed when its card is
 * authorised (`wc_gateway_stripe_process_response`), never before: an
 * abandoned, unpaid first order never reaches the platform (Ahmed, 18 Sep
 * 2026). A reorder (TC_Reorder_Checkout) is pushed at creation, on
 * `tc_review_order_created`, with `previousExternalReference` naming the
 * order it follows. A failure retries via WP-Cron (three attempts total,
 * backoff) and leaves a manual "Send to prescribing platform" order action
 * for anything that still hasn't synced.
 *
 * Payment: once the money for an order is actually
 * captured (never on a mere card authorisation), POST
 * `/v1/website-orders/payment` so the platform knows the order is paid.
 * Same sender, settings, retry and fail-closed rules as the patient push;
 * idempotent on `_tc_platform_paid_sent_at`. See the "Payment message"
 * section below for which hooks fire it and why.
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

	/** Payment message (`POST /v1/website-orders/payment`). */
	const META_PAID_SENT_AT      = '_tc_platform_paid_sent_at';
	const META_PAID_ATTEMPTS     = '_tc_platform_paid_attempts';
	const META_PAID_LAST_ERROR   = '_tc_platform_paid_last_error';
	const META_PAID_AT           = '_tc_platform_paid_at';
	const META_PAID_AMOUNT_PENCE = '_tc_platform_paid_amount_pence';
	const META_PAID_REFERENCE    = '_tc_platform_paid_reference';

	/** The website order on the platform (`order` block of POST /v1/patients). */
	const META_ORDER_PUSHED_AT     = '_tc_platform_order_pushed_at';
	const META_WEBSITE_ORDER_ID    = '_tc_platform_website_order_id';
	const META_ORDER_PUSH_ATTEMPTS = '_tc_platform_order_push_attempts';

	/** The two assessment payloads: a first order's, and a reorder's. */
	const META_ELIGIBILITY_RAW = '_tc_eligibility_raw';
	const META_REORDER_RAW     = '_rrqr_raw';
	const META_REORDER_PREVIOUS = '_rrqr_previous_order_id';

	/** Set by the Woo Stripe extension on PaymentIntent orders. */
	const STRIPE_INTENT_META = '_stripe_intent_id';

	/** `packages/contracts/src/signature.ts`: WEBHOOK_SIGNATURE_HEADER / TOLERANCE_SECONDS. */
	const SIGNATURE_HEADER    = 'X-Together-Signature';
	const SIGNATURE_TOLERANCE = 300;

	const RETRY_HOOK  = 'tc_platform_sync_retry';
	const MAX_ATTEMPTS = 3;

	const PAYMENT_HOOK = 'tc_platform_payment_send';
	const CANCEL_HOOK  = 'tc_platform_cancellation_send';

	/** Cancellation message, per reason (`<reason>` lower-cased): sent flag, first-seen time, attempts. */
	const META_CANCEL_SENT_PREFIX     = '_tc_platform_cancel_sent_';
	const META_CANCEL_AT_PREFIX       = '_tc_platform_cancel_at_';
	const META_CANCEL_ATTEMPTS_PREFIX = '_tc_platform_cancel_attempts_';
	const META_CANCEL_STATUS_PREFIX   = '_tc_platform_cancel_status_';

	/** TC_Review_Actions' own audit meta, read to recognise the platform's decline. */
	const META_REVIEW_DECISION   = '_tc_review_decision';
	const META_REVIEW_DECIDED_BY = '_tc_review_decided_by';
	const PLATFORM_REVIEWER      = 'Prescribing platform';

	/** Order ids whose capture has already been handled in this request (several hooks see one capture). */
	private static $payment_handled = [];

	/** True while handle_webhook() is acting on a platform decision. See on_payment_captured(). */
	private static $in_platform_webhook = false;

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

		// Payment message: see the "Payment message" section for why these.
		add_action( 'woocommerce_stripe_process_manual_capture', [ __CLASS__, 'on_stripe_manual_capture' ], 10, 2 );
		add_action( 'woocommerce_payment_complete', [ __CLASS__, 'on_payment_complete' ], 20 );
		add_action( 'woocommerce_order_status_processing', [ __CLASS__, 'on_paid_status' ], 20 );
		add_action( 'woocommerce_order_status_completed', [ __CLASS__, 'on_paid_status' ], 20 );
		add_action( self::PAYMENT_HOOK, [ __CLASS__, 'run_payment_retry' ] );
		// Cancellation message: see the "Cancellation message" section.
		add_action( 'woocommerce_order_status_changed', [ __CLASS__, 'on_status_changed' ], 20, 3 );
		add_action( 'woocommerce_trash_order', [ __CLASS__, 'on_trash_order' ] );
		add_action( 'wp_trash_post', [ __CLASS__, 'on_trash_post' ] );
		add_action( 'woocommerce_order_partially_refunded', [ __CLASS__, 'on_partial_refund' ] );
		add_action( self::CANCEL_HOOK, [ __CLASS__, 'run_cancellation' ], 10, 2 );
		// A first order's card hold: the platform takes the order in now.
		add_action( 'wc_gateway_stripe_process_response', [ __CLASS__, 'on_stripe_response' ], 20, 2 );
		add_filter( 'woocommerce_order_actions', [ __CLASS__, 'add_manual_payment_action' ], 10, 2 );
		add_action( 'woocommerce_order_action_tc_platform_manual_payment', [ __CLASS__, 'manual_payment' ] );
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
		// Already synced, but the platform has not taken the order in yet
		// (the card was held after the first push): send just the order.
		if ( $order->get_meta( self::META_SYNCED_AT ) ) {
			$order->update_meta_data( self::META_ORDER_PUSH_ATTEMPTS, 0 );
			$order->save();
			self::push_order_block( $order );
			return;
		}
		self::push( $order, self::raw_payload( $order ) );
	}

	/** The assessment payload this order was created from: a first order's, else a reorder's. */
	private static function raw_payload( WC_Order $order ) {
		foreach ( [ self::META_ELIGIBILITY_RAW, self::META_REORDER_RAW ] as $key ) {
			$raw     = $order->get_meta( $key );
			$decoded = $raw ? json_decode( $raw, true ) : null;
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}
		return [];
	}

	/** A reorder check-in (TC_Reorder_Checkout), not a first eligibility assessment. */
	public static function is_reorder( WC_Order $order ) {
		if ( '' !== (string) $order->get_meta( self::META_ELIGIBILITY_RAW ) ) {
			return false;
		}
		return '' !== (string) $order->get_meta( self::META_REORDER_RAW )
			|| 'tc_reorder_submission' === $order->get_created_via();
	}

	/**
	 * The identity half of a reorder's patient push.
	 *
	 * The reorder check-in asks only name, email and date of birth. The rest
	 * of the patient record (sex at birth, UK nation, phone) is taken from
	 * the first eligibility assessment this reorder descends from, found by
	 * walking `_rrqr_previous_order_id` back (a reorder of a reorder) to the
	 * order that carries `_tc_eligibility_raw`. Name, email and date of birth
	 * the patient gave in this check-in win over the older copy. The address
	 * is the order's own billing address, which the reorder copied from the
	 * previous order. Nothing clinical from the old assessment is carried
	 * over: only these identity keys.
	 */
	public static function reorder_identity_payload( WC_Order $order, array $payload ) {
		$original = [];
		$previous = (int) $order->get_meta( self::META_REORDER_PREVIOUS );
		for ( $hops = 0; $previous && $hops < 10; $hops++ ) {
			$prev = wc_get_order( $previous );
			if ( ! $prev instanceof WC_Order ) {
				break;
			}
			$raw     = $prev->get_meta( self::META_ELIGIBILITY_RAW );
			$decoded = $raw ? json_decode( $raw, true ) : null;
			if ( is_array( $decoded ) ) {
				$original = $decoded;
				break;
			}
			$previous = (int) $prev->get_meta( self::META_REORDER_PREVIOUS );
		}

		$identity = [];
		foreach ( [ 'firstName', 'lastName', 'fullName', 'email', 'dob', 'sex', 'country', 'phone' ] as $key ) {
			if ( isset( $original[ $key ] ) && is_scalar( $original[ $key ] ) && '' !== trim( (string) $original[ $key ] ) ) {
				$identity[ $key ] = $original[ $key ];
			}
		}
		foreach ( [ 'firstName', 'lastName', 'email', 'dob' ] as $key ) {
			if ( isset( $payload[ $key ] ) && is_scalar( $payload[ $key ] ) && '' !== trim( (string) $payload[ $key ] ) ) {
				$identity[ $key ] = $payload[ $key ];
			}
		}
		if ( isset( $identity['firstName'] ) ) {
			unset( $identity['fullName'] );
		}
		return $identity;
	}

	public static function push( WC_Order $order, array $payload ) {
		if ( ! self::is_enabled() ) {
			return;
		}

		// Ahmed, 18 Sep 2026: an abandoned, unpaid first order never reaches
		// the platform. A first order is first pushed when its card is
		// authorised (on_stripe_response), never at creation, never by a
		// retry or the manual action before then. A reorder is pushed at
		// creation: the platform takes in every reorder, and it is paid
		// later by link.
		if ( ! self::is_reorder( $order ) && null === self::order_block( $order ) ) {
			TC_Log::debug( 'platform_push_waiting_for_card_hold', [ 'order_id' => $order->get_id() ] );
			return;
		}

		// A dob that fails to parse is a data problem, not a transient one:
		// fail fast with an order note and schedule no retry (Opus review,
		// minor 6). "Send to prescribing platform" is the only way back,
		// after the date is fixed on the order.
		$is_reorder = self::is_reorder( $order );
		// The patient record is built from identity only; the answers below
		// are always this order's own payload, never an older assessment's.
		$identity = $is_reorder ? self::reorder_identity_payload( $order, $payload ) : $payload;

		$raw_dob = trim( (string) ( $identity['dob'] ?? '' ) );
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

		$patient_body = self::patient_request_body( $order, $identity, $external_reference );
		// The order block, when the platform will take the order in: always
		// for a reorder, and for a first order once its card is held. A
		// first order pushed before that goes as the bare patient push, and
		// its order follows when the card is authorised (on_stripe_response).
		$order_block = self::order_block( $order );
		if ( null !== $order_block ) {
			$patient_body['order'] = $order_block;
		}
		$patient = self::request(
			'POST',
			'/v1/patients',
			$patient_body,
			self::patient_push_key( $order_id, $order_block )
		);

		if ( is_wp_error( $patient ) || empty( $patient['id'] ) ) {
			self::record_failure(
				$order,
				is_wp_error( $patient ) ? $patient->get_error_message() : self::no_patient_message( $patient )
			);
			return;
		}

		$order->update_meta_data( self::META_PATIENT_ID, $patient['id'] );
		$order->save();
		if ( null !== $order_block ) {
			self::record_order_outcome( $order, $patient['order'] ?? null );
		}

		$intake_body = self::pre_consultation_body( $payload, $is_reorder );
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

	/**
	 * @param array $payload    Raw assessment payload (first order or reorder).
	 * @param bool  $is_reorder A reorder check-in asks no GP questions.
	 */
	public static function pre_consultation_body( array $payload, $is_reorder = false ) {
		if ( $is_reorder ) {
			return self::reorder_pre_consultation_body( $payload );
		}
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

	/**
	 * A reorder's intake. Only what the reorder check-in actually asks.
	 *
	 * Consents: only `service`, from `termsAgreed`, which reorder.js sends
	 * when the patient has ticked "You agree to our Terms & Conditions and
	 * Privacy Policy". `gpShare` and `scrAccess` are left OUT, never sent as
	 * false: the check-in asks neither, and the platform writes a GP_SHARE
	 * decline row for an explicit false, which would overwrite the consent the
	 * patient gave at their first order. An absent key records nothing.
	 * `gp` is empty for the same reason, so the practice on file is kept.
	 * The reported weight (`currentWeight`) is the one measurement asked.
	 */
	private static function reorder_pre_consultation_body( array $payload ) {
		$answers = $payload;
		foreach ( self::IDENTITY_KEYS as $key ) {
			unset( $answers[ $key ] );
		}

		$measurements = [];
		if ( isset( $payload['currentWeight'] ) && is_numeric( $payload['currentWeight'] ) && (float) $payload['currentWeight'] > 0 ) {
			$measurements['weightKg'] = (float) $payload['currentWeight'];
		}

		return [
			'answers'      => (object) self::json_safe_answers( $answers ),
			'consents'     => [
				'service' => self::truthy( $payload['termsAgreed'] ?? false ),
			],
			'gp'           => (object) [],
			'measurements' => (object) $measurements,
		];
	}

	/**
	 * The card as the platform's `holdState` reads it, from the Stripe
	 * extension's own record: captured, held (authorised, uncaptured), a hold
	 * released by cancelling, or none.
	 */
	public static function hold_state( WC_Order $order ) {
		$flag = (string) $order->get_meta( TC_Review_Payment::STRIPE_CAPTURED_META );
		if ( 'yes' === $flag ) {
			return 'CAPTURED';
		}
		if ( 'no' === $flag && '' !== (string) $order->get_transaction_id() ) {
			return 'cancelled' === $order->get_status() ? 'RELEASED' : 'AUTHORISED';
		}
		return 'NONE';
	}

	/**
	 * The `order` block of `POST /v1/patients` (packages/contracts
	 * patient-push.ts), or null when the platform would not take the order
	 * in yet: a first order with no card held is answered 202 SKIPPED and
	 * nothing at all is stored, not even the patient. Carries no clinical
	 * content: the answers go to the pre-consultation route only.
	 *
	 * @return array|null
	 */
	public static function order_block( WC_Order $order ) {
		$lane = self::is_reorder( $order ) ? 'REORDER' : 'FIRST_ORDER';
		$hold = self::hold_state( $order );
		if ( 'FIRST_ORDER' === $lane && ! in_array( $hold, [ 'AUTHORISED', 'CAPTURED' ], true ) ) {
			return null;
		}

		// A first order is taken in as AUTHORISED even when the money has
		// already been captured: the capture reaches the platform as the
		// payment message straight after, which moves the platform's order to
		// CAPTURED. A first push saying CAPTURED would be logged there as
		// "captured before decision", which is not what happened when the
		// authorisation was simply missed here.
		if ( 'FIRST_ORDER' === $lane ) {
			$hold = 'AUTHORISED';
		}

		$block = [
			'lane'                => $lane,
			'holdState'           => $hold,
			'externalOrderNumber' => (string) $order->get_order_number(),
		];

		// The order this reorder follows (the first order, or the previous
		// reorder), so the platform can put the reorder on that order's
		// patient (it checks date of birth and surname, else creates a new
		// patient and flags it). Only ever on a REORDER.
		if ( 'REORDER' === $lane ) {
			$previous = (int) $order->get_meta( self::META_REORDER_PREVIOUS );
			if ( $previous > 0 ) {
				$block['previousExternalReference'] = 'tc-order-' . $previous;
			}
		}
		$status = (string) $order->get_status();
		if ( 1 === preg_match( '/^[a-z0-9-]{1,32}$/', $status ) ) {
			$block['websiteStatus'] = $status;
		}
		$created = $order->get_date_created();
		if ( $created ) {
			$block['submittedAt'] = gmdate( 'Y-m-d\TH:i:s\Z', $created->getTimestamp() );
		}

		foreach ( $order->get_items() as $item ) {
			$quantity = max( 1, (int) $item->get_quantity() );
			$product  = [
				'variationId' => (string) ( $item->get_variation_id() ?: $item->get_product_id() ),
				'lineItemId'  => (string) $item->get_id(),
				'name'        => (string) $item->get_name(),
				'quantity'    => $quantity,
				'unitPriceMinor' => self::to_pence( (float) $item->get_total() / $quantity ),
				'currency'    => strtoupper( (string) $order->get_currency() ),
			];
			$wc_product = $item->get_product();
			if ( $wc_product && '' !== (string) $wc_product->get_sku() ) {
				$product['sku'] = (string) $wc_product->get_sku();
			}
			$block['product'] = $product;
			break; // One line item per review order.
		}

		return $block;
	}

	/**
	 * A new Idempotency-Key whenever the order block changes what the push
	 * means: the platform replays a cached answer for a key it has seen, so a
	 * held first order re-pushed under the bare push's key would get back the
	 * bare answer and never be taken in.
	 */
	public static function patient_push_key( $order_id, $order_block ) {
		$key = 'tc-order-' . (int) $order_id . '-patient';
		if ( is_array( $order_block ) ) {
			$key .= '-' . strtolower( $order_block['lane'] . '-' . $order_block['holdState'] );
		}
		return $key;
	}

	private static function no_patient_message( $response ) {
		$result = is_array( $response ) && isset( $response['order']['result'] ) ? (string) $response['order']['result'] : '';
		if ( '' !== $result ) {
			return sanitize_text_field( 'platform returned no patient (order ' . $result . ( isset( $response['order']['matchState'] ) ? ', ' . $response['order']['matchState'] : '' ) . ')' );
		}
		return 'patient sync returned no id';
	}

	/**
	 * What the platform did with the order block. Taken in (CREATED, UPDATED,
	 * UNCHANGED): remembered, so the payment message can find it. Not taken
	 * in (website order intake switched off, or the route not there yet): an
	 * order note, because the payment message will be refused until it is.
	 */
	private static function record_order_outcome( WC_Order $order, $outcome ) {
		$result = is_array( $outcome ) ? (string) ( $outcome['result'] ?? '' ) : '';
		if ( in_array( $result, [ 'CREATED', 'UPDATED', 'UNCHANGED' ], true ) ) {
			$order->update_meta_data( self::META_ORDER_PUSHED_AT, time() );
			$order->update_meta_data( self::META_ORDER_PUSH_ATTEMPTS, 0 );
			if ( ! empty( $outcome['websiteOrderId'] ) ) {
				$order->update_meta_data( self::META_WEBSITE_ORDER_ID, sanitize_text_field( (string) $outcome['websiteOrderId'] ) );
			}
			$order->save();
			return true;
		}
		$reason = is_array( $outcome ) ? sanitize_text_field( (string) ( $outcome['reason'] ?? '' ) ) : '';
		$order->add_order_note( sprintf(
			'Prescribing platform received the patient but did not take in the order (%s). Its payment cannot be recorded there until it does. Use "Send to prescribing platform" once order intake is on.',
			'' !== $result ? $result . ( '' !== $reason ? ', ' . $reason : '' ) : 'no order outcome'
		) );
		TC_Log::warn( 'platform_order_not_taken_in', [
			'order_id' => $order->get_id(),
			'result'   => '' !== $result ? $result : 'none',
		] );
		return false;
	}

	/**
	 * Due: a synced first order whose card is now held, which the platform
	 * has not yet taken in as an order. (A reorder's order goes with its very
	 * first push, so it is never left due once synced.)
	 */
	public static function order_push_due( WC_Order $order ) {
		return '' !== (string) $order->get_meta( self::META_PATIENT_ID )
			&& ! $order->get_meta( self::META_ORDER_PUSHED_AT )
			&& null !== self::order_block( $order );
	}

	/**
	 * POST /v1/patients again, now with the order block, for a first order
	 * already synced before its card was held. Same patient reference, so
	 * the platform's find-or-create returns the same patient. No intake is
	 * re-sent: that went with the first push.
	 *
	 * @param bool $schedule_retry False when the caller (the payment message)
	 *                             runs its own retry, so only one is queued.
	 * @return bool Whether the platform took the order in.
	 */
	public static function push_order_block( WC_Order $order, $schedule_retry = true ) {
		if ( ! self::is_enabled() || ! self::order_push_due( $order ) ) {
			return (bool) $order->get_meta( self::META_ORDER_PUSHED_AT );
		}
		$order_id = $order->get_id();
		$payload  = self::raw_payload( $order );
		$identity = self::is_reorder( $order ) ? self::reorder_identity_payload( $order, $payload ) : $payload;

		$body          = self::patient_request_body( $order, $identity, 'tc-order-' . $order_id );
		$block         = self::order_block( $order );
		$body['order'] = $block;

		$response = self::request( 'POST', '/v1/patients', $body, self::patient_push_key( $order_id, $block ) );
		if ( ! is_wp_error( $response ) && ! empty( $response['id'] ) ) {
			return self::record_order_outcome( $order, $response['order'] ?? null );
		}

		$attempts = (int) $order->get_meta( self::META_ORDER_PUSH_ATTEMPTS ) + 1;
		$order->update_meta_data( self::META_ORDER_PUSH_ATTEMPTS, $attempts );
		$order->update_meta_data(
			self::META_SYNC_LAST_ERROR,
			is_wp_error( $response ) ? sanitize_text_field( $response->get_error_message() ) : self::no_patient_message( $response )
		);
		$order->save();
		$order->add_order_note( sprintf( 'Sending the order to the prescribing platform failed (attempt %d of %d).', $attempts, self::MAX_ATTEMPTS ) );
		TC_Log::warn( 'platform_order_push_failed', [
			'order_id' => $order_id,
			'attempt'  => $attempts,
		] );
		if ( $schedule_retry && $attempts < self::MAX_ATTEMPTS ) {
			$delays = self::retry_delays();
			$delay  = $delays[ $attempts - 1 ] ?? end( $delays );
			wp_schedule_single_event( time() + $delay, self::RETRY_HOOK, [ $order_id ] );
		}
		return false;
	}

	/**
	 * `wc_gateway_stripe_process_response`: fired by the Stripe extension
	 * after every charge response (abstract-wc-stripe-payment-gateway.php,
	 * 11.0.1 line 733), an authorisation included. When a first order's card
	 * has just been held, the platform takes the order in now.
	 */
	public static function on_stripe_response( $response, $order ) {
		if ( $order instanceof WC_Order ) {
			self::maybe_push_held_order( $order->get_id() );
		}
	}

	private static function maybe_push_held_order( $order_id ) {
		if ( ! $order_id || ! self::is_enabled() ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || ! TC_Review_Status::is_treatment_order( $order ) ) {
			return;
		}
		// Held, not captured: a capture takes the order in through the
		// payment message instead (send_payment), so it is pushed once.
		if ( 'AUTHORISED' !== self::hold_state( $order ) ) {
			return;
		}
		if ( ! $order->get_meta( self::META_SYNCED_AT ) ) {
			// The first push of a first order: patient with its order
			// block, then the pre-consultation.
			self::push( $order, self::raw_payload( $order ) );
		} elseif ( self::order_push_due( $order ) ) {
			// Synced by an earlier version before its card was held.
			self::push_order_block( $order );
		}
	}

	/**
	 * Before a payment message: make sure the platform has the patient and
	 * the order, pushing them now if the authorisation was never seen here
	 * (an older gateway, sync switched on after the card was held, or the
	 * authorisation push's retries ran out).
	 *
	 * @return bool Whether the platform now holds the order.
	 */
	private static function ensure_on_platform( WC_Order $order ) {
		if ( ! $order->get_meta( self::META_SYNCED_AT ) ) {
			self::push( $order, self::raw_payload( $order ) );
			if ( ! $order->get_meta( self::META_SYNCED_AT ) ) {
				return false;
			}
		}
		if ( self::order_push_due( $order ) ) {
			return self::push_order_block( $order, false );
		}
		return (bool) $order->get_meta( self::META_ORDER_PUSHED_AT );
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
			// `type` is the platform's stable problem-detail code (RFC 7807),
			// e.g. WEBSITE_ORDER_NOT_FOUND; kept so a caller can tell an
			// unknown order from a route this platform has not deployed yet.
			$type = is_array( $decoded ) && isset( $decoded['type'] ) && is_string( $decoded['type'] ) ? $decoded['type'] : '';
			return new WP_Error( 'tc_platform_http_error', (string) $detail, [ 'status' => $code, 'type' => $type ] );
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
			if ( self::order_push_due( $order ) ) {
				self::push_order_block( $order );
			}
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
		if ( $order->get_meta( self::META_SYNCED_AT ) && ! self::order_push_due( $order ) ) {
			return $actions;
		}
		// An unpaid first order is never sent (no card held yet).
		if ( ! self::is_reorder( $order ) && null === self::order_block( $order ) ) {
			return $actions;
		}

		$actions['tc_platform_manual_sync'] = 'Send to prescribing platform';
		return $actions;
	}

	// ======================================================================
	// Payment message: POST /v1/website-orders/payment
	//
	// Fires when money is actually CAPTURED, never on a card authorisation.
	// All money movement is the WooCommerce Stripe extension's (this plugin
	// has no gateway code), so "captured" means exactly what that extension
	// records: `_stripe_charge_captured` === 'yes' (TC_Review_Payment::
	// is_captured()). An authorisation records 'no' and parks the order
	// on-hold; it never reaches this message. Order status and date_paid are
	// deliberately NOT used: WooCommerce stamps date_paid on any move to
	// processing, including an approval whose capture then fails.
	//
	// Three hooks, because the extension captures on three routes (checked
	// against woocommerce-gateway-stripe 11.0.1):
	//
	//  1. `woocommerce_stripe_process_manual_capture`: the authorised card
	//     captured when the order moves to processing (prescriber approval,
	//     or staff processing an on-hold pay-link authorisation).
	//     WC_Stripe_Order_Handler::capture_payment() fires it after storing
	//     the captured flag, but ALSO when the capture failed, hence the
	//     flag check in payment_skip_reason().
	//  2. `woocommerce_payment_complete`: a charge captured outright at
	//     payment (automatic capture), or a capture made in the Stripe
	//     dashboard arriving by `charge.captured` webhook. The extension
	//     records the flag before calling payment_complete().
	//  3. `woocommerce_order_status_processing` / `_completed` at priority
	//     20, after the extension's own capture at 10, as a safety net if a
	//     gateway version renames hook 1. Same flag check.
	//
	// One capture can trip several of these in one request; $payment_handled
	// and the `_tc_platform_paid_sent_at` flag mean it is sent once.
	// ======================================================================

	public static function on_stripe_manual_capture( $order, $result = null ) {
		$order_id = $order instanceof WC_Order ? $order->get_id() : absint( $order );
		$pence    = null;
		if ( is_object( $result ) ) {
			if ( isset( $result->amount_captured ) && is_numeric( $result->amount_captured ) ) {
				$pence = (int) $result->amount_captured;
			} elseif ( isset( $result->amount ) && is_numeric( $result->amount ) ) {
				$pence = (int) $result->amount;
			}
		}
		self::on_payment_captured( $order_id, $pence );
	}

	public static function on_payment_complete( $order_id ) {
		$order_id = absint( $order_id );
		// Older gateway versions call payment_complete() for an authorisation
		// too: take the held order in, never inside the platform's webhook.
		if ( ! self::$in_platform_webhook ) {
			self::maybe_push_held_order( $order_id );
		}
		self::on_payment_captured( $order_id );
	}

	public static function on_paid_status( $order_id ) {
		self::on_payment_captured( absint( $order_id ) );
	}

	/**
	 * @param int      $order_id
	 * @param int|null $captured_pence What Stripe says it captured, when the hook knows.
	 */
	private static function on_payment_captured( $order_id, $captured_pence = null ) {
		if ( ! $order_id || isset( self::$payment_handled[ $order_id ] ) ) {
			return;
		}
		// Fail closed, exactly like push(): the persistent admin notice
		// already says nothing is being sent.
		if ( ! self::is_enabled() ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$skip = self::payment_skip_reason( $order );
		if ( null !== $skip ) {
			// Not marked handled: an authorisation seen by one hook must not
			// stop the capture a later hook sees in the same request.
			TC_Log::debug( 'platform_payment_skipped', [
				'order_id' => $order_id,
				'reason'   => $skip,
			] );
			return;
		}
		self::$payment_handled[ $order_id ] = true;

		self::record_payment_snapshot( $order, $captured_pence );

		if ( self::$in_platform_webhook ) {
			wp_schedule_single_event( time(), self::PAYMENT_HOOK, [ $order_id ] );
			return;
		}
		self::send_payment( $order );
	}

	/**
	 * Why the payment message must not be sent for this order, or null when
	 * it should be. Pure over the order's meta, so the smoke test can drive
	 * it with a stub order.
	 *
	 * @return string|null
	 */
	public static function payment_skip_reason( WC_Order $order ) {
		if ( ! TC_Review_Status::is_treatment_order( $order ) ) {
			return 'not_review_order';
		}
		// Not yet on the platform is no reason to skip: send_payment() puts
		// the patient and order there first (ensure_on_platform()).
		if ( ! TC_Review_Payment::is_captured( $order ) ) {
			return 'not_captured';
		}
		if ( $order->get_meta( self::META_PAID_SENT_AT ) ) {
			return 'already_sent';
		}
		if ( '' === self::payment_reference( $order ) ) {
			return 'no_payment_reference';
		}
		return null;
	}

	/** Stripe charge id (the order's transaction id once captured), else the PaymentIntent id. */
	private static function payment_reference( WC_Order $order ) {
		$stored = (string) $order->get_meta( self::META_PAID_REFERENCE );
		if ( '' !== $stored ) {
			return $stored;
		}
		$reference = trim( (string) $order->get_transaction_id() );
		if ( '' === $reference ) {
			$reference = trim( (string) $order->get_meta( self::STRIPE_INTENT_META ) );
		}
		return $reference;
	}

	/**
	 * Freezes paidAt / amount / reference at the moment the capture is first
	 * seen, so a retry or a manual resend days later sends the same facts
	 * (and the same Idempotency-Key) rather than whatever the order says by
	 * then (a later refund, an edited total).
	 */
	private static function record_payment_snapshot( WC_Order $order, $captured_pence = null ) {
		if ( '' !== (string) $order->get_meta( self::META_PAID_AT ) ) {
			return;
		}
		if ( null === $captured_pence ) {
			// What WC_Stripe_Order_Handler::capture_payment() asks Stripe to
			// capture: the order total less anything already refunded.
			$captured_pence = self::to_pence( (float) $order->get_total() - (float) $order->get_total_refunded() );
		}
		$order->update_meta_data( self::META_PAID_AT, gmdate( 'Y-m-d\TH:i:s\Z', time() ) );
		$order->update_meta_data( self::META_PAID_AMOUNT_PENCE, (int) $captured_pence );
		$order->update_meta_data( self::META_PAID_REFERENCE, self::payment_reference( $order ) );
		$order->save();
	}

	/** Pounds to integer pence, rounded rather than truncated (19.99 * 100 is 1998.9999…). */
	public static function to_pence( $amount ) {
		return (int) round( (float) $amount * 100 );
	}

	public static function payment_idempotency_key( $order_id, $payment_reference ) {
		return 'tc-paid-' . (int) $order_id . '-' . $payment_reference;
	}

	/**
	 * The contract body, exactly. Pure.
	 *
	 * @param int    $order_id
	 * @param string $paid_at           ISO-8601 UTC.
	 * @param int    $amount_pence
	 * @param string $payment_reference Stripe charge or PaymentIntent id.
	 */
	public static function payment_request_body( $order_id, $paid_at, $amount_pence, $payment_reference ) {
		return [
			// Same namespacing as the patient push (Opus review, M1).
			'externalReference' => 'tc-order-' . (int) $order_id,
			'status'            => 'PAID',
			'paidAt'            => (string) $paid_at,
			'amountPence'       => (int) $amount_pence,
			'currency'          => 'GBP',
			'paymentReference'  => (string) $payment_reference,
		];
	}

	/**
	 * The platform's problem-detail code for "no website order with that
	 * reference" (cmd_api_record_website_order_payment, mapped to 404 by
	 * apps/api's problem-detail.ts). Any OTHER 404 is a route the platform
	 * has not deployed yet (Nest's own NOT_FOUND), not an unknown order.
	 */
	const UNKNOWN_ORDER_TYPE = 'WEBSITE_ORDER_NOT_FOUND';

	/**
	 * The contract's response rules for the payment and cancellation
	 * messages. Pure.
	 *   2xx                          -> 'ok'        (recorded, or already recorded)
	 *   404 WEBSITE_ORDER_NOT_FOUND  -> 'permanent' (unknown order)
	 *   404 anything else            -> 'retry'     (route not deployed yet: the
	 *                                              website released before the API
	 *                                              must not strand a payment)
	 *   409, other 4xx               -> 'permanent'
	 *   5xx, no status               -> 'retry'     (server error, timeout, connection failure)
	 *
	 * @param int|null $status       HTTP status, or null when no response arrived.
	 * @param string   $problem_type The response's problem-detail `type`, if any.
	 */
	public static function classify_payment_status( $status, $problem_type = '' ) {
		if ( null === $status || '' === $status ) {
			return 'retry';
		}
		$status = (int) $status;
		if ( $status >= 200 && $status < 300 ) {
			return 'ok';
		}
		if ( 404 === $status && self::UNKNOWN_ORDER_TYPE !== (string) $problem_type ) {
			return 'retry';
		}
		if ( $status >= 400 && $status < 500 ) {
			return 'permanent';
		}
		return 'retry';
	}

	/** [ status|null, problem type ] from a request() result. */
	private static function response_status( $result ) {
		if ( ! is_wp_error( $result ) ) {
			return [ 200, '' ];
		}
		$data = $result->get_error_data();
		if ( ! is_array( $data ) || ! isset( $data['status'] ) ) {
			return [ null, '' ];
		}
		return [ (int) $data['status'], (string) ( $data['type'] ?? '' ) ];
	}

	public static function send_payment( WC_Order $order ) {
		if ( ! self::is_enabled() ) {
			return;
		}
		$order_id = $order->get_id();

		$skip = self::payment_skip_reason( $order );
		if ( null !== $skip ) {
			TC_Log::debug( 'platform_payment_skipped', [
				'order_id' => $order_id,
				'reason'   => $skip,
			] );
			return;
		}

		// The contract is GBP only. Never relabel another currency's amount.
		if ( 'GBP' !== strtoupper( (string) $order->get_currency() ) ) {
			self::record_payment_permanent_failure( $order, 'order currency is not GBP' );
			return;
		}

		self::record_payment_snapshot( $order );

		// The payment is recorded against the platform's website order. An
		// order the platform does not hold yet (authorisation never seen here)
		// is pushed first, as AUTHORISED, so this message is what records the
		// capture. If that fails, the payment waits for its next retry rather
		// than being refused with a 404.
		if ( ! self::ensure_on_platform( $order ) ) {
			self::record_payment_retryable_failure( $order, 'order not yet taken in by the platform' );
			return;
		}

		$reference = (string) $order->get_meta( self::META_PAID_REFERENCE );
		$body      = self::payment_request_body(
			$order_id,
			(string) $order->get_meta( self::META_PAID_AT ),
			(int) $order->get_meta( self::META_PAID_AMOUNT_PENCE ),
			$reference
		);

		$result = self::request(
			'POST',
			'/v1/website-orders/payment',
			$body,
			self::payment_idempotency_key( $order_id, $reference )
		);

		list( $status, $problem_type ) = self::response_status( $result );

		switch ( self::classify_payment_status( $status, $problem_type ) ) {
			case 'ok':
				$order->update_meta_data( self::META_PAID_SENT_AT, time() );
				$order->update_meta_data( self::META_PAID_ATTEMPTS, 0 );
				$order->delete_meta_data( self::META_PAID_LAST_ERROR );
				$order->save();
				$order->add_order_note( sprintf(
					'Payment recorded on the prescribing platform (%s, reference %s).',
					self::format_pence( $body['amountPence'] ),
					$reference
				) );
				TC_Log::info( 'platform_payment_ok', [ 'order_id' => $order_id ] );
				return;

			case 'permanent':
				self::record_payment_permanent_failure( $order, 'HTTP ' . $status, $status );
				return;

			default:
				self::record_payment_retryable_failure(
					$order,
					is_wp_error( $result ) ? $result->get_error_message() : 'unknown error'
				);
		}
	}

	private static function format_pence( $pence ) {
		return '£' . number_format( ( (int) $pence ) / 100, 2 );
	}

	private static function record_payment_permanent_failure( WC_Order $order, $message, $status = null ) {
		$order->update_meta_data( self::META_PAID_LAST_ERROR, sanitize_text_field( (string) $message ) );
		$order->save();

		if ( 404 === $status ) {
			$note = 'Prescribing platform does not recognise this order (HTTP 404), so the payment was not recorded there. Check the order was sent to the platform, then use "Send payment to prescribing platform".';
		} elseif ( 409 === $status ) {
			$note = 'Prescribing platform refused the payment because the order is cancelled or declined there (HTTP 409). Money has been taken for an order the platform will not fulfil. Review urgently.';
		} elseif ( null !== $status ) {
			$note = sprintf( 'Prescribing platform rejected the payment message (HTTP %d). It will not be retried automatically; fix the cause, then use "Send payment to prescribing platform".', (int) $status );
		} else {
			$note = sprintf( 'Payment was not sent to the prescribing platform: %s. Use "Send payment to prescribing platform" once resolved.', sanitize_text_field( (string) $message ) );
		}
		$order->add_order_note( $note );

		TC_Log::warn( 'platform_payment_failed_permanent', [
			'order_id' => $order->get_id(),
			'status'   => null === $status ? 'none' : (int) $status,
		] );
	}

	private static function record_payment_retryable_failure( WC_Order $order, $message ) {
		$attempts = (int) $order->get_meta( self::META_PAID_ATTEMPTS ) + 1;
		$order->update_meta_data( self::META_PAID_ATTEMPTS, $attempts );
		// Transport/HTTP error text only, never order content.
		$order->update_meta_data( self::META_PAID_LAST_ERROR, sanitize_text_field( (string) $message ) );
		$order->save();

		if ( $attempts < self::MAX_ATTEMPTS ) {
			$order->add_order_note( sprintf( 'Sending the payment to the prescribing platform failed (attempt %d of %d). It will retry automatically.', $attempts, self::MAX_ATTEMPTS ) );
			TC_Log::warn( 'platform_payment_failed', [
				'order_id' => $order->get_id(),
				'attempt'  => $attempts,
			] );
			$delays = self::retry_delays();
			$delay  = $delays[ $attempts - 1 ] ?? end( $delays );
			wp_schedule_single_event( time() + $delay, self::PAYMENT_HOOK, [ $order->get_id() ] );
			return;
		}

		$order->add_order_note( sprintf( 'Sending the payment to the prescribing platform failed %d times and has stopped retrying. Use "Send payment to prescribing platform" once the platform is reachable.', $attempts ) );
		TC_Log::warn( 'platform_payment_failed_permanent', [
			'order_id' => $order->get_id(),
			'status'   => 'retries_exhausted',
		] );
	}

	public static function run_payment_retry( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		// A manual resend, or an earlier retry, may already have landed.
		if ( $order->get_meta( self::META_PAID_SENT_AT ) ) {
			return;
		}
		self::send_payment( $order );
	}

	public static function manual_payment( WC_Order $order ) {
		if ( $order->get_meta( self::META_PAID_SENT_AT ) ) {
			$order->add_order_note( 'Payment is already recorded on the prescribing platform; nothing was sent.' );
			return;
		}
		// A deliberate resend starts a fresh set of three attempts.
		$order->update_meta_data( self::META_PAID_ATTEMPTS, 0 );
		$order->save();
		self::send_payment( $order );
	}

	public static function add_manual_payment_action( $actions, $order = null ) {
		if ( ! $order instanceof WC_Order ) {
			global $theorder;
			$order = $theorder;
		}
		if ( ! $order instanceof WC_Order || ! self::is_enabled() ) {
			return $actions;
		}
		// Only when the message is genuinely due: a synced review order whose
		// money has been captured and not yet acknowledged by the platform.
		if ( null !== self::payment_skip_reason( $order ) ) {
			return $actions;
		}
		$actions['tc_platform_manual_payment'] = 'Send payment to prescribing platform';
		return $actions;
	}

	// ======================================================================
	// Cancellation message: POST /v1/website-orders/cancellation
	//
	// An order the platform holds that is cancelled, fully refunded, failed
	// or trashed here is reported there, once per reason. Never for an order
	// the platform never received, and never as an echo of the platform's
	// own decline (consultation.declined -> TC_Review_Actions::reject()).
	//
	// The status hook only QUEUES the message (WP-Cron, immediately); the
	// send re-reads the order and goes only if it is STILL in that status.
	// Two things in this plugin move an order straight back out again in the
	// same request, and neither may reach the platform as a cancellation:
	// TC_Review_Status::guard_transition() reverts a blocked move off
	// awaiting-review, and TC_Review_Actions::approve() moves a failed
	// capture (the Stripe extension sets "failed") on to the pay link.
	// ======================================================================

	/** WooCommerce status (slug) to the contract's reason. Trash is a cancellation. */
	public static function cancellation_reason( $status ) {
		$map = [
			'cancelled' => 'CANCELLED',
			'refunded'  => 'REFUNDED',
			'failed'    => 'FAILED',
			'trash'     => 'CANCELLED',
		];
		return $map[ (string) $status ] ?? null;
	}

	public static function cancellation_idempotency_key( $order_id, $reason ) {
		return 'tc-cancel-' . (int) $order_id . '-' . $reason;
	}

	/** The contract body, exactly. Pure. */
	public static function cancellation_request_body( $order_id, $reason, $occurred_at ) {
		return [
			'externalReference' => 'tc-order-' . (int) $order_id,
			'reason'            => (string) $reason,
			'occurredAt'        => (string) $occurred_at,
		];
	}

	/** Sent to the platform at all: a synced patient, or an order it took in. */
	private static function on_platform( WC_Order $order ) {
		return (bool) $order->get_meta( self::META_SYNCED_AT )
			|| (bool) $order->get_meta( self::META_ORDER_PUSHED_AT );
	}

	/** Rejected by the platform's own consultation.declined webhook. */
	private static function declined_by_platform( WC_Order $order ) {
		return 'rejected' === (string) $order->get_meta( self::META_REVIEW_DECISION )
			&& self::PLATFORM_REVIEWER === (string) $order->get_meta( self::META_REVIEW_DECIDED_BY );
	}

	/**
	 * Why the cancellation message must not be sent, or null. Pure over the
	 * order's meta and status.
	 *
	 * @return string|null
	 */
	public static function cancellation_skip_reason( WC_Order $order, $reason ) {
		if ( ! TC_Review_Status::is_treatment_order( $order ) ) {
			return 'not_review_order';
		}
		if ( ! self::on_platform( $order ) ) {
			return 'not_on_platform';
		}
		if ( self::$in_platform_webhook || self::declined_by_platform( $order ) ) {
			return 'declined_by_platform';
		}
		if ( $order->get_meta( self::META_CANCEL_SENT_PREFIX . strtolower( $reason ) ) ) {
			return 'already_sent';
		}
		return null;
	}

	public static function on_status_changed( $order_id, $from, $to ) {
		$reason = self::cancellation_reason( $to );
		if ( null !== $reason ) {
			self::queue_cancellation( absint( $order_id ), $reason, (string) $to );
		}
	}

	/** HPOS: the order has just been moved to the trash. */
	public static function on_trash_order( $order_id ) {
		self::queue_cancellation( absint( $order_id ), 'CANCELLED', 'trash' );
	}

	/** Posts storage: fires before the post is trashed, so the expected status is checked at send time. */
	public static function on_trash_post( $post_id ) {
		if ( function_exists( 'get_post_type' ) && 'shop_order' === get_post_type( $post_id ) ) {
			self::queue_cancellation( absint( $post_id ), 'CANCELLED', 'trash' );
		}
	}

	/** A partial refund is not a cancellation: an order note, nothing sent. */
	public static function on_partial_refund( $order_id ) {
		if ( ! self::is_enabled() ) {
			return;
		}
		$order = wc_get_order( absint( $order_id ) );
		if ( ! $order instanceof WC_Order || ! TC_Review_Status::is_treatment_order( $order ) || ! self::on_platform( $order ) ) {
			return;
		}
		$order->add_order_note( 'Partial refund: nothing was sent to the prescribing platform. Only a full refund, a cancellation or a failed order is reported there; tell the prescribing team directly if this changes the supply.' );
	}

	private static function queue_cancellation( $order_id, $reason, $expected_status ) {
		if ( ! $order_id || ! self::is_enabled() ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$skip = self::cancellation_skip_reason( $order, $reason );
		if ( null !== $skip ) {
			TC_Log::debug( 'platform_cancellation_skipped', [
				'order_id' => $order_id,
				'reason'   => $skip,
			] );
			return;
		}
		$key = strtolower( $reason );
		if ( '' === (string) $order->get_meta( self::META_CANCEL_AT_PREFIX . $key ) ) {
			$order->update_meta_data( self::META_CANCEL_AT_PREFIX . $key, gmdate( 'Y-m-d\TH:i:s\Z', time() ) );
		}
		$order->update_meta_data( self::META_CANCEL_STATUS_PREFIX . $key, $expected_status );
		$order->save();
		wp_schedule_single_event( time(), self::CANCEL_HOOK, [ $order_id, $reason ] );
	}

	public static function run_cancellation( $order_id, $reason ) {
		if ( ! self::is_enabled() ) {
			return;
		}
		$order = wc_get_order( absint( $order_id ) );
		if ( ! $order instanceof WC_Order || ! in_array( $reason, [ 'CANCELLED', 'REFUNDED', 'FAILED' ], true ) ) {
			return;
		}
		$key      = strtolower( $reason );
		$expected = (string) $order->get_meta( self::META_CANCEL_STATUS_PREFIX . $key );

		$skip = self::cancellation_skip_reason( $order, $reason );
		if ( null === $skip && '' !== $expected && $expected !== (string) $order->get_status() ) {
			// Moved straight back out (guard revert, failed capture going to
			// the pay link, order restored from the trash): not a cancellation.
			$skip = 'status_moved_on';
			$order->delete_meta_data( self::META_CANCEL_AT_PREFIX . $key );
			$order->delete_meta_data( self::META_CANCEL_STATUS_PREFIX . $key );
			$order->save();
		}
		if ( null !== $skip ) {
			TC_Log::debug( 'platform_cancellation_skipped', [
				'order_id' => $order->get_id(),
				'reason'   => $skip,
			] );
			return;
		}

		$occurred_at = (string) $order->get_meta( self::META_CANCEL_AT_PREFIX . $key );
		if ( '' === $occurred_at ) {
			$occurred_at = gmdate( 'Y-m-d\TH:i:s\Z', time() );
		}
		$result = self::request(
			'POST',
			'/v1/website-orders/cancellation',
			self::cancellation_request_body( $order->get_id(), $reason, $occurred_at ),
			self::cancellation_idempotency_key( $order->get_id(), $reason )
		);
		list( $status, $problem_type ) = self::response_status( $result );

		// The platform never took this order in as an order (a patient-only
		// push from an earlier version): quietly nothing to cancel there.
		if ( 404 === $status && self::UNKNOWN_ORDER_TYPE === $problem_type ) {
			$order->update_meta_data( self::META_CANCEL_SENT_PREFIX . $key, 'not_on_platform' );
			$order->save();
			TC_Log::debug( 'platform_cancellation_unknown_order', [ 'order_id' => $order->get_id() ] );
			return;
		}

		$attempts_key = self::META_CANCEL_ATTEMPTS_PREFIX . $key;
		switch ( self::classify_payment_status( $status, $problem_type ) ) {
			case 'ok':
				$order->update_meta_data( self::META_CANCEL_SENT_PREFIX . $key, time() );
				$order->update_meta_data( $attempts_key, 0 );
				$order->save();
				$order->add_order_note( sprintf( 'Prescribing platform told this order is %s.', strtolower( $reason ) ) );
				TC_Log::info( 'platform_cancellation_ok', [
					'order_id' => $order->get_id(),
					'reason'   => $reason,
				] );
				return;

			case 'permanent':
				$order->add_order_note( sprintf(
					'Prescribing platform rejected the %s message (HTTP %d). It will not be retried; tell the prescribing team directly.',
					strtolower( $reason ),
					(int) $status
				) );
				TC_Log::warn( 'platform_cancellation_failed_permanent', [
					'order_id' => $order->get_id(),
					'reason'   => $reason,
					'status'   => (int) $status,
				] );
				return;

			default:
				$attempts = (int) $order->get_meta( $attempts_key ) + 1;
				$order->update_meta_data( $attempts_key, $attempts );
				$order->save();
				if ( $attempts < self::MAX_ATTEMPTS ) {
					$order->add_order_note( sprintf( 'Telling the prescribing platform this order is %s failed (attempt %d of %d). It will retry automatically.', strtolower( $reason ), $attempts, self::MAX_ATTEMPTS ) );
					TC_Log::warn( 'platform_cancellation_failed', [
						'order_id' => $order->get_id(),
						'reason'   => $reason,
						'attempt'  => $attempts,
					] );
					$delays = self::retry_delays();
					$delay  = $delays[ $attempts - 1 ] ?? end( $delays );
					wp_schedule_single_event( time() + $delay, self::CANCEL_HOOK, [ $order->get_id(), $reason ] );
					return;
				}
				$order->add_order_note( sprintf( 'Telling the prescribing platform this order is %s failed %d times and has stopped retrying. Tell the prescribing team directly.', strtolower( $reason ), $attempts ) );
				TC_Log::warn( 'platform_cancellation_failed_permanent', [
					'order_id' => $order->get_id(),
					'reason'   => $reason,
					'status'   => 'retries_exhausted',
				] );
		}
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
		TC_Review_Actions::set_reviewer_override( self::PLATFORM_REVIEWER );
		// Approving captures the card hold, which would otherwise POST the
		// payment message back to the platform from inside the platform's
		// own webhook delivery. on_payment_captured() defers it to WP-Cron
		// while this is set, so the two requests never wait on each other.
		self::$in_platform_webhook = true;
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
			self::$in_platform_webhook = false;
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
