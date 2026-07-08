<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Reorder_Ajax {

	const NONCE_ACTION = 'tc_reorder';

	public function __construct() {
		$actions = [
			'tc_reorder_save_partial'   => 'save_partial',
			'tc_reorder_save'           => 'save',
			'rrqr_add_to_cart'          => 'add_to_cart',
			'tc_reorder_add_to_cart'    => 'add_to_cart',
		];

		foreach ( $actions as $hook => $method ) {
			add_action( 'wp_ajax_' . $hook,        [ $this, $method ] );
			add_action( 'wp_ajax_nopriv_' . $hook, [ $this, 'require_login' ] );
		}
	}

	public function require_login() {
		wp_send_json_error( [ 'message' => 'You must be logged in to reorder.' ], 401 );
	}

	public function save_partial() {
		$this->verify_nonce();
		$user_id = $this->require_authenticated_user();

		$data    = $this->json_body();
		$prefill = TC_Reorder_Prefill::for_user( $user_id );

		if ( ! $prefill || ! $prefill['has_previous_order'] ) {
			wp_send_json_error( [ 'message' => 'No previous qualifying order found on your account.' ], 403 );
		}

		$assessment_id = TC_Reorder_DB::insert_partial( $user_id, $prefill['previous_order_id'], [
			'firstName' => $prefill['first_name'],
			'lastName'  => $prefill['last_name'],
			'email'     => $prefill['email'],
		] );

		TC_Reorder_Log::info( 'partial_saved', [
			'assessment_id'     => $assessment_id,
			'user_id'           => $user_id,
			'previous_order_id' => $prefill['previous_order_id'],
		] );

		wp_send_json_success( [ 'assessment_id' => $assessment_id ] );
	}

	public function save() {
		$this->verify_nonce();
		$user_id = $this->require_authenticated_user();

		if ( function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		$payload = $this->json_body();
		$assessment_id = sanitize_text_field( $payload['assessment_id'] ?? '' );

		if ( ! $assessment_id || ! wp_is_uuid( $assessment_id ) ) {
			wp_send_json_error( [ 'message' => 'Missing assessment_id.' ], 400 );
		}

		$existing = TC_Reorder_DB::get_by_assessment_id( $assessment_id );
		if ( ! $existing ) {
			wp_send_json_error( [ 'message' => 'Assessment not found.' ], 404 );
		}

		if ( (int) $existing['user_id'] !== (int) $user_id ) {
			TC_Reorder_Log::warn( 'save_user_mismatch', [
				'assessment_id'    => $assessment_id,
				'expected_user_id' => $existing['user_id'],
				'actual_user_id'   => $user_id,
			] );
			wp_send_json_error( [ 'message' => 'Permission denied.' ], 403 );
		}

		$payload = $this->normalize_payload( $payload );

		$prefill = TC_Reorder_Prefill::for_user( $user_id );
		$rules   = TC_Reorder_Rules::evaluate( $payload, $prefill );

		if ( ! $rules['ok'] ) {
			TC_Reorder_DB::update_complete( $assessment_id, $payload, 'blocked', $rules['code'] );

			TC_Reorder_Log::info( 'submission_blocked', [
				'assessment_id' => $assessment_id,
				'user_id'       => $user_id,
				'code'          => $rules['code'],
			] );

			$response = [
				'ok'     => false,
				'code'   => $rules['code'],
				'reason' => $rules['reason'],
			];

			if ( $rules['code'] === 'medication_mismatch' ) {
				$response['redirect'] = $this->assessment_url( true );
			}

			wp_send_json_success( $response );
		}

		TC_Reorder_DB::update_complete( $assessment_id, $payload, 'complete' );

		TC_Reorder_Cookie_Store::save_to_session( [
			'assessment_id'   => $assessment_id,
			'previousOrderId' => $prefill['previous_order_id'],
			'firstName'       => $payload['firstName'],
			'lastName'        => $payload['lastName'],
			'email'           => $payload['email'],
			'currentMedication' => $payload['currentMedication'],
			'currentDose'     => $payload['currentDose'],
			'selectedDose'    => $payload['selectedDose'],
		] );

		// Review-first model: create the order here, at submission, in the
		// awaiting-review status. Payment happens later via the pay link the
		// prescriber's approval sends — there is no cart or checkout step.
		$order = TC_Reorder_Checkout::create_from_submission( $payload, $assessment_id, $user_id, $prefill );
		if ( is_wp_error( $order ) ) {
			wp_send_json_error( [ 'message' => $order->get_error_message() ], 500 );
		}

		TC_Reorder_Emails::send_patient_confirmation( $payload, $assessment_id );
		TC_Reorder_Emails::send_clinician_notification( $payload, $assessment_id, $prefill, $order );

		TC_Reorder_Log::info( 'submission_complete', [
			'assessment_id' => $assessment_id,
			'user_id'       => $user_id,
			'order_id'      => $order->get_id(),
			'treatment'     => $payload['currentMedication'],
			'selected_dose' => $payload['selectedDose'],
		] );

		nocache_headers();

		wp_send_json_success( [
			'ok'            => true,
			'review'        => true,
			'assessment_id' => $assessment_id,
			'order_id'      => $order->get_id(),
			'nonce'         => wp_create_nonce( self::NONCE_ACTION ),
		] );
	}

	public function add_to_cart() {
		$this->verify_nonce();
		$user_id = $this->require_authenticated_user();

		if ( function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( [ 'message' => 'WooCommerce not available.' ], 500 );
		}

		$cookie = TC_Reorder_Cookie_Store::get();
		if ( empty( $cookie ) || empty( $cookie['currentMedication'] ) || empty( $cookie['selectedDose'] ) ) {
			TC_Reorder_Log::warn( 'add_to_cart_no_data', [
				'has_cookie'  => isset( $_COOKIE[ TC_Reorder_Cookie_Store::COOKIE_NAME ] ) ? 'yes' : 'no',
				'has_session' => ( function_exists( 'WC' ) && WC()->session && WC()->session->has_session() ) ? 'yes' : 'no',
			] );
			wp_send_json_error( [ 'message' => 'No reorder data found. Please start the reorder again.' ], 400 );
		}

		$treatment = $cookie['currentMedication'];
		$dose      = $cookie['selectedDose'];
		$product_id = TC_Reorder_Pricing::get_product_id( $treatment, $dose );

		if ( ! $product_id ) {
			TC_Reorder_Log::error( 'add_to_cart_product_missing', [
				'treatment' => $treatment,
				'dose'      => $dose,
			] );
			wp_send_json_error( [
				'message' => sprintf( 'No product configured for %s %s. Please contact us.', $treatment, $dose ),
			], 500 );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->exists() ) {
			TC_Reorder_Log::error( 'add_to_cart_product_not_found', [ 'product_id' => $product_id ] );
			wp_send_json_error( [ 'message' => 'Product not found.' ], 404 );
		}

		WC()->cart->empty_cart();

		$cart_item_data = [
			'rrqr_data' => [
				'reorder'           => true,
				'assessment_id'     => (string) ( $cookie['assessment_id'] ?? '' ),
				'previous_order_id' => (int) ( $cookie['previousOrderId'] ?? 0 ),
				'user_id'           => (int) $user_id,
				'treatment'         => $treatment,
				'dose'              => $dose,
			],
		];

		if ( $product->is_type( 'variation' ) ) {
			$parent_id        = $product->get_parent_id();
			$variation_id_arg = $product->get_id();
		} else {
			$parent_id        = $product->get_id();
			$variation_id_arg = 0;
		}

		$key = WC()->cart->add_to_cart( $parent_id, 1, $variation_id_arg, [], $cart_item_data );

		if ( ! $key ) {
			TC_Reorder_Log::error( 'add_to_cart_failed', [
				'product_id'   => $parent_id,
				'variation_id' => $variation_id_arg,
			] );
			wp_send_json_error( [ 'message' => 'Could not add product to cart.' ], 500 );
		}

		TC_Reorder_Log::info( 'add_to_cart_ok', [
			'product_id'    => $parent_id,
			'variation_id'  => $variation_id_arg,
			'treatment'     => $treatment,
			'dose'          => $dose,
			'assessment_id' => $cookie['assessment_id'] ?? '',
		] );

		wp_send_json_success( [
			'cart_url'     => wc_get_cart_url(),
			'checkout_url' => wc_get_checkout_url(),
		] );
	}

	private function verify_nonce() {
		$nonce = $_REQUEST['nonce'] ?? $_SERVER['HTTP_X_TC_NONCE'] ?? '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_send_json_error( [ 'message' => 'Security check failed. Please refresh and try again.' ], 403 );
		}
	}

	private function require_authenticated_user() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => 'You must be logged in to reorder.' ], 401 );
		}
		return (int) get_current_user_id();
	}

	private function json_body() {
		$raw  = file_get_contents( 'php://input' );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			$data = $_POST;
		}
		return $data;
	}

	private function normalize_payload( array $p ) {
		$p['firstName']           = sanitize_text_field( $p['firstName'] ?? '' );
		$p['lastName']            = sanitize_text_field( $p['lastName'] ?? '' );
		$p['email']               = sanitize_email( $p['email'] ?? '' );
		$p['dob']                 = sanitize_text_field( $p['dob'] ?? '' );
		$p['currentMedication']   = TC_Reorder_Pricing::normalize_treatment( $p['currentMedication'] ?? '' );
		$p['currentDose']         = TC_Reorder_Pricing::normalize_dose( $p['currentDose'] ?? '' );
		$p['selectedDose']        = TC_Reorder_Pricing::normalize_dose( $p['selectedDose'] ?? $p['currentDose'] ?? '' );
		$p['currentWeight']       = (float) ( $p['currentWeight'] ?? 0 );
		$p['hasLostWeight']       = sanitize_text_field( $p['hasLostWeight'] ?? '' );
		$p['appetiteLasting']     = sanitize_text_field( $p['appetiteLasting'] ?? '' );
		$p['hasSideEffects']      = sanitize_text_field( $p['hasSideEffects'] ?? '' );
		$p['healthChanged']       = sanitize_text_field( $p['healthChanged'] ?? '' );
		$p['newMedications']      = sanitize_text_field( $p['newMedications'] ?? '' );
		$p['newMedicationsList']  = sanitize_textarea_field( $p['newMedicationsList'] ?? '' );
		$p['couldBePregnant']     = sanitize_text_field( $p['couldBePregnant'] ?? '' );
		$p['wantsClinicalSupport'] = sanitize_text_field( $p['wantsClinicalSupport'] ?? '' );

		return $p;
	}

	private function assessment_url( $force_assessment = false ) {
		$page_id = (int) get_option( 'tc_eligibility_assessment_page_id', 0 );
		$url = $page_id ? get_permalink( $page_id ) : home_url( '/weight-loss-eligibility/' );
		if ( $force_assessment ) {
			$url = add_query_arg( 'force_assessment', '1', $url );
		}
		return $url;
	}
}
