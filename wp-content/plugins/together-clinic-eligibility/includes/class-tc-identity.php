<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Phase 2.5 — identity verification with Stripe Identity.
 *
 * As the final step after payment, a first-time patient verifies their identity
 * (photo ID document + selfie/liveness) on Stripe's hosted page. On return the
 * verified name + date of birth are cross-checked against what the patient
 * entered in the assessment, and age 18+ is enforced. Any problem — a failed
 * check, a name/DOB mismatch, or an under-18 ID — writes a prescriber review
 * flag; it NEVER auto-rejects. The prescriber decides; a rejection releases the
 * card hold as usual (owner spec, 2026-09-11).
 *
 * Scope: once per patient (a verified patient is not re-checked, and reorders
 * never trigger it — only first orders from the eligibility lane).
 *
 * ASSUMPTIONS (verify in Stripe test mode before go-live):
 * - Reuses the WooCommerce Stripe Gateway's secret key (same Stripe account),
 *   read from `woocommerce_stripe_settings`; filter `tc_identity_secret_key`
 *   overrides if a newer gateway stores keys elsewhere.
 * - Talks to the Stripe REST API directly via wp_remote_request (no dependency
 *   on the gateway's bundled PHP SDK).
 * - Results are taken both on the hosted-page return and from a signed webhook
 *   (identity.verification_session.*) at /wp-json/tc-identity/v1/webhook.
 *
 * Biometric note: the selfie is special-category data. Session creation is
 * gated behind an explicit on-page consent, recorded against the order, and the
 * privacy notice must cover it (Stripe as processor, explicit consent as the
 * lawful basis) — see docs/privacy-identity-verification.md, pending owner
 * sign-off.
 *
 * Gated behind enabled() (option `tc_identity_enabled`, default off).
 */
class TC_Identity {

	const META_SESSION_ID = '_tc_identity_session_id';
	const META_STATUS     = '_tc_identity_status';   // created|processing|requires_input|verified|mismatch|under_18|canceled
	const META_CHECKED_AT = '_tc_identity_checked_at';
	const META_CONSENT_AT = '_tc_identity_consent_at';
	const META_REPORT     = '_tc_identity_report';   // json: verified vs entered comparison

	/** Set once, on the WP user, when they pass — satisfies "once per patient". */
	const USER_VERIFIED_AT = '_tc_identity_verified_at';

	const NONCE = 'tc_identity_start';

	public function __construct() {
		if ( ! self::enabled() ) {
			return;
		}

		add_action( 'woocommerce_thankyou', [ __CLASS__, 'render_thankyou_panel' ], 5 );
		add_action( 'template_redirect', [ __CLASS__, 'handle_return' ] );
		add_action( 'wp_ajax_tc_identity_start', [ __CLASS__, 'ajax_start' ] );
		add_action( 'wp_ajax_nopriv_tc_identity_start', [ __CLASS__, 'ajax_start' ] );
		add_action( 'rest_api_init', function () {
			register_rest_route( 'tc-identity/v1', '/webhook', [
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'webhook' ],
				'permission_callback' => '__return_true',
			] );
		} );
	}

	public static function enabled() {
		return (bool) apply_filters(
			'tc_identity_enabled',
			get_option( 'tc_identity_enabled', 'no' ) === 'yes'
		);
	}

	/**
	 * A first-time, unverified patient on an eligibility-lane order needs to
	 * verify. Verified patients and reorders are skipped.
	 */
	public static function needs_verification( WC_Order $order ) {
		if ( ! self::enabled() ) {
			return false;
		}
		if ( $order->get_created_via() !== 'tc_eligibility_assessment' ) {
			return false;
		}
		if ( ! TC_Review_Status::is_treatment_order( $order ) ) {
			return false;
		}
		if ( $order->get_meta( self::META_STATUS ) === 'verified' ) {
			return false;
		}
		$uid = $order->get_customer_id();
		if ( $uid && get_user_meta( $uid, self::USER_VERIFIED_AT, true ) ) {
			return false;
		}
		return true;
	}

	/* --------------------------------------------------------------------- */
	/* Patient-facing: consent + start on the order-received page            */
	/* --------------------------------------------------------------------- */

	public static function render_thankyou_panel( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! self::needs_verification( $order ) ) {
			return;
		}

		$nonce = wp_create_nonce( self::NONCE );
		$key   = $order->get_order_key();
		?>
		<section class="tc-identity" style="margin:24px 0;padding:20px;border:1px solid #e5e7eb;border-radius:10px;background:#faf9fd;">
			<h2 style="margin-top:0;">One last step: verify your identity</h2>
			<p>Because your treatment is a prescription medicine, we need to confirm your identity before our prescriber can approve it. It takes about a minute — you'll photograph your photo ID and take a quick selfie. This is handled securely by our verification partner, Stripe.</p>
			<label style="display:block;margin:16px 0;">
				<input type="checkbox" id="tc-identity-consent" />
				I consent to identity verification, including a facial (biometric) match between my selfie and my photo ID, processed by Stripe on Together Clinic's behalf. See our <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" target="_blank" rel="noopener">privacy notice</a>.
			</label>
			<button id="tc-identity-start" type="button" disabled
				style="display:inline-block;background:#8e88d0;color:#fff;padding:14px 28px;border:0;border-radius:8px;font-weight:600;cursor:pointer;opacity:.5;">
				Verify my identity
			</button>
			<p id="tc-identity-error" style="color:#b91c1c;margin-top:12px;" hidden></p>
			<script>
			(function () {
				var consent = document.getElementById('tc-identity-consent');
				var btn = document.getElementById('tc-identity-start');
				var err = document.getElementById('tc-identity-error');
				consent.addEventListener('change', function () {
					btn.disabled = !consent.checked;
					btn.style.opacity = consent.checked ? '1' : '.5';
				});
				btn.addEventListener('click', function () {
					btn.disabled = true; btn.textContent = 'Starting secure verification…';
					var body = new URLSearchParams();
					body.append('action', 'tc_identity_start');
					body.append('nonce', <?php echo wp_json_encode( $nonce ); ?>);
					body.append('order_id', <?php echo (int) $order_id; ?>);
					body.append('order_key', <?php echo wp_json_encode( $key ); ?>);
					fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
						method: 'POST', credentials: 'same-origin', body: body
					}).then(function (r) { return r.json(); }).then(function (res) {
						if (res && res.success && res.data && res.data.url) {
							window.location.href = res.data.url;
						} else {
							throw new Error((res && res.data && res.data.message) || 'Could not start verification.');
						}
					}).catch(function (e) {
						btn.disabled = false; btn.textContent = 'Verify my identity';
						err.hidden = false; err.textContent = e.message || 'Something went wrong. Please try again.';
					});
				});
			})();
			</script>
		</section>
		<?php
	}

	public static function ajax_start() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), self::NONCE ) ) {
			wp_send_json_error( [ 'message' => 'Your session has expired. Please refresh and try again.' ], 403 );
		}

		$order_id  = isset( $_POST['order_id'] ) ? (int) $_POST['order_id'] : 0;
		$order_key = isset( $_POST['order_key'] ) ? sanitize_text_field( wp_unslash( $_POST['order_key'] ) ) : '';
		$order     = $order_id ? wc_get_order( $order_id ) : null;

		if ( ! $order || ! hash_equals( (string) $order->get_order_key(), $order_key ) ) {
			wp_send_json_error( [ 'message' => 'Order not found.' ], 404 );
		}
		if ( ! self::needs_verification( $order ) ) {
			wp_send_json_success( [ 'url' => $order->get_checkout_order_received_url() ] );
		}

		// Record explicit consent (biometric lawful basis) before starting.
		$order->update_meta_data( self::META_CONSENT_AT, time() );
		$order->save();

		$session = self::create_session( $order );
		if ( is_wp_error( $session ) ) {
			wp_send_json_error( [ 'message' => 'We could not start verification just now. Please try again shortly.' ], 502 );
		}

		wp_send_json_success( [ 'url' => $session['url'] ?? '' ] );
	}

	/**
	 * Runs on the order-received page after the patient returns from Stripe;
	 * pulls the authoritative result and updates the order.
	 */
	public static function handle_return() {
		if ( empty( $_GET['tc_identity_return'] ) ) {
			return;
		}
		$order = wc_get_order( (int) $_GET['tc_identity_return'] );
		if ( ! $order ) {
			return;
		}
		$session_id = $order->get_meta( self::META_SESSION_ID );
		if ( ! $session_id ) {
			return;
		}
		$session = self::retrieve( $session_id );
		if ( ! is_wp_error( $session ) ) {
			self::process_session( $session );
		}
	}

	/* --------------------------------------------------------------------- */
	/* Stripe API                                                            */
	/* --------------------------------------------------------------------- */

	public static function create_session( WC_Order $order ) {
		$return_url = add_query_arg( 'tc_identity_return', $order->get_id(), $order->get_checkout_order_received_url() );

		$params = [
			'type'       => 'document',
			'options'    => [
				'document' => [
					'require_matching_selfie' => 'true',
					'require_live_capture'    => 'true',
					'allowed_types'           => [ 'driving_license', 'passport', 'id_card' ],
				],
			],
			'metadata'   => [
				'order_id'      => (string) $order->get_id(),
				'assessment_id' => (string) $order->get_meta( '_tc_eligibility_assessment_id' ),
				'user_id'       => (string) $order->get_customer_id(),
			],
			'return_url' => $return_url,
		];

		$res = self::api( 'POST', 'identity/verification_sessions', $params );
		if ( is_wp_error( $res ) ) {
			TC_Log::error( 'identity_session_create_failed', [
				'order_id' => $order->get_id(),
				'error'    => $res->get_error_message(),
			] );
			return $res;
		}

		$order->update_meta_data( self::META_SESSION_ID, $res['id'] ?? '' );
		$order->update_meta_data( self::META_STATUS, $res['status'] ?? 'created' );
		$order->add_order_note( 'Identity verification started (Stripe Identity).' );
		$order->save();

		TC_Log::info( 'identity_session_created', [ 'order_id' => $order->get_id(), 'session' => $res['id'] ?? '' ] );

		return $res;
	}

	public static function retrieve( $session_id, array $expand = [ 'verified_outputs' ] ) {
		$path = 'identity/verification_sessions/' . rawurlencode( $session_id );
		if ( $expand ) {
			$path .= '?' . http_build_query( [ 'expand' => $expand ], '', '&' );
		}
		return self::api( 'GET', $path );
	}

	/**
	 * Applies a retrieved session's outcome to the order: records status, and on
	 * a verified result cross-checks name + DOB against the assessment and
	 * enforces 18+. Problems become a prescriber flag; a clean pass marks the
	 * patient verified (once-per-patient).
	 */
	public static function process_session( array $session ) {
		$order_id = isset( $session['metadata']['order_id'] ) ? (int) $session['metadata']['order_id'] : 0;
		$order    = $order_id ? wc_get_order( $order_id ) : null;
		if ( ! $order ) {
			return;
		}

		$status = (string) ( $session['status'] ?? '' );
		$order->update_meta_data( self::META_CHECKED_AT, time() );

		if ( $status !== 'verified' ) {
			$order->update_meta_data( self::META_STATUS, $status ?: 'unknown' );
			if ( in_array( $status, [ 'requires_input', 'canceled' ], true ) ) {
				self::flag( $order, sprintf( 'Identity check did not complete (%s) — the patient could not verify. Please confirm identity another way before approving.', $status ) );
			}
			$order->save();
			return;
		}

		$vo        = isset( $session['verified_outputs'] ) && is_array( $session['verified_outputs'] ) ? $session['verified_outputs'] : [];
		$id_first  = strtolower( trim( (string) ( $vo['first_name'] ?? '' ) ) );
		$id_last   = strtolower( trim( (string) ( $vo['last_name'] ?? '' ) ) );
		$dob       = isset( $vo['dob'] ) && is_array( $vo['dob'] ) ? $vo['dob'] : [];
		$id_dob    = ( $dob ) ? sprintf( '%04d-%02d-%02d', (int) ( $dob['year'] ?? 0 ), (int) ( $dob['month'] ?? 0 ), (int) ( $dob['day'] ?? 0 ) ) : '';

		$exp_first = strtolower( trim( (string) $order->get_meta( '_tc_elig_firstName' ) ) );
		$exp_last  = strtolower( trim( (string) $order->get_meta( '_tc_elig_lastName' ) ) );
		$exp_dob   = trim( (string) $order->get_meta( '_tc_elig_dob' ) );

		$mismatch = [];
		if ( $exp_first && $id_first && $exp_first !== $id_first ) {
			$mismatch[] = 'first name';
		}
		if ( $exp_last && $id_last && $exp_last !== $id_last ) {
			$mismatch[] = 'last name';
		}
		if ( $exp_dob && $id_dob && $exp_dob !== $id_dob ) {
			$mismatch[] = 'date of birth';
		}

		$age      = self::age_from_iso( $id_dob );
		$under_18 = ( $age !== null && $age < 18 );

		$order->update_meta_data( self::META_REPORT, wp_json_encode( [
			'id_name'  => trim( $id_first . ' ' . $id_last ),
			'id_dob'   => $id_dob,
			'entered'  => trim( $exp_first . ' ' . $exp_last ) . ' / ' . $exp_dob,
			'mismatch' => $mismatch,
			'age'      => $age,
		] ) );

		if ( $mismatch || $under_18 ) {
			$parts = [];
			if ( $mismatch ) {
				$parts[] = 'the ID does not match the assessment (' . implode( ', ', $mismatch ) . ')';
			}
			if ( $under_18 ) {
				$parts[] = 'the ID shows the patient is under 18';
			}
			self::flag( $order, 'Identity verified by Stripe, but ' . implode( '; ', $parts ) . '. Please review before approving.' );
			$order->update_meta_data( self::META_STATUS, $under_18 ? 'under_18' : 'mismatch' );
			TC_Log::warn( 'identity_flagged', [ 'order_id' => $order->get_id(), 'mismatch' => $mismatch, 'under_18' => $under_18 ] );
		} else {
			$order->update_meta_data( self::META_STATUS, 'verified' );
			if ( $order->get_customer_id() ) {
				update_user_meta( $order->get_customer_id(), self::USER_VERIFIED_AT, time() );
			}
			$order->add_order_note( 'Identity verified: ID document + selfie confirmed, name and date of birth match the assessment.' );
			TC_Log::info( 'identity_verified', [ 'order_id' => $order->get_id() ] );
		}

		$order->save();
	}

	public static function webhook( WP_REST_Request $request ) {
		$payload = $request->get_body();
		$sig     = $request->get_header( 'stripe_signature' );

		if ( ! self::verify_signature( $payload, (string) $sig ) ) {
			return new WP_REST_Response( [ 'ok' => false ], 400 );
		}

		$event = json_decode( $payload, true );
		$type  = $event['type'] ?? '';

		if ( strpos( (string) $type, 'identity.verification_session.' ) === 0 ) {
			$obj = $event['data']['object'] ?? [];
			$id  = $obj['id'] ?? '';
			if ( $id ) {
				$full = self::retrieve( $id );
				if ( ! is_wp_error( $full ) ) {
					self::process_session( $full );
				}
			}
		}

		return new WP_REST_Response( [ 'ok' => true ], 200 );
	}

	/* --------------------------------------------------------------------- */
	/* Helpers                                                               */
	/* --------------------------------------------------------------------- */

	private static function flag( WC_Order $order, $message ) {
		$flags = $order->get_meta( TC_Review_Status::FLAGS_META );
		if ( ! is_array( $flags ) ) {
			$flags = [];
		}
		$flags['identity_check'] = $message;
		$order->update_meta_data( TC_Review_Status::FLAGS_META, $flags );
	}

	private static function age_from_iso( $iso ) {
		if ( ! $iso || $iso === '0000-00-00' ) {
			return null;
		}
		try {
			$dob = new DateTime( $iso );
			$now = new DateTime( 'now' );
			return (int) $dob->diff( $now )->y;
		} catch ( Exception $e ) {
			return null;
		}
	}

	private static function secret_key() {
		$override = (string) apply_filters( 'tc_identity_secret_key', '' );
		if ( $override ) {
			return $override;
		}
		$settings = get_option( 'woocommerce_stripe_settings', [] );
		if ( ! is_array( $settings ) ) {
			return '';
		}
		$test = isset( $settings['testmode'] ) && $settings['testmode'] === 'yes';
		return (string) ( $test ? ( $settings['test_secret_key'] ?? '' ) : ( $settings['secret_key'] ?? '' ) );
	}

	private static function webhook_secret() {
		return (string) apply_filters( 'tc_identity_webhook_secret', get_option( 'tc_identity_webhook_secret', '' ) );
	}

	private static function api( $method, $path, array $params = [] ) {
		$key = self::secret_key();
		if ( ! $key ) {
			return new WP_Error( 'tc_identity_no_key', 'Stripe secret key is not available.' );
		}

		$args = [
			'method'  => $method,
			'timeout' => 20,
			'headers' => [
				'Authorization'  => 'Bearer ' . $key,
				'Content-Type'   => 'application/x-www-form-urlencoded',
				'Stripe-Version' => '2024-06-20',
			],
		];
		if ( $params ) {
			$args['body'] = http_build_query( $params, '', '&' );
		}

		$response = wp_remote_request( 'https://api.stripe.com/v1/' . ltrim( $path, '/' ), $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$message = $data['error']['message'] ?? ( 'Stripe API error (HTTP ' . $code . ')' );
			return new WP_Error( 'tc_identity_api', $message );
		}

		return is_array( $data ) ? $data : [];
	}

	private static function verify_signature( $payload, $header ) {
		$secret = self::webhook_secret();
		if ( ! $secret || ! $header ) {
			// No secret configured: fail closed so unsigned calls can't drive state.
			return false;
		}

		$timestamp = '';
		$signature = '';
		foreach ( explode( ',', $header ) as $part ) {
			$kv = explode( '=', trim( $part ), 2 );
			if ( count( $kv ) !== 2 ) {
				continue;
			}
			if ( $kv[0] === 't' ) {
				$timestamp = $kv[1];
			} elseif ( $kv[0] === 'v1' ) {
				$signature = $kv[1];
			}
		}
		if ( ! $timestamp || ! $signature ) {
			return false;
		}

		// Reject events older than 5 minutes (replay protection).
		if ( abs( time() - (int) $timestamp ) > 300 ) {
			return false;
		}

		$expected = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );
		return hash_equals( $expected, $signature );
	}
}
