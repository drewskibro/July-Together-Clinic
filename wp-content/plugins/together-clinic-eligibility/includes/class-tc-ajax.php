<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Ajax {

	const NONCE_ACTION = 'tc_eligibility';

	public function __construct() {
		$actions = [
			'tc_eligibility_save_partial' => 'save_partial',
			'tc_eligibility_save'         => 'save',
			'tc_eligibility_add_to_cart'  => 'add_to_cart',
			'tc_eligibility_ineligible'   => 'ineligible',
			'tc_eligibility_get_doses'    => 'get_doses',
		];

		foreach ( $actions as $hook => $method ) {
			add_action( 'wp_ajax_' . $hook,        [ $this, $method ] );
			add_action( 'wp_ajax_nopriv_' . $hook, [ $this, $method ] );
		}
	}

	public function save_partial() {
		$this->verify_nonce();

		$data = $this->json_body();

		$first_name = sanitize_text_field( $data['firstName'] ?? '' );
		$last_name  = sanitize_text_field( $data['lastName'] ?? '' );
		$email      = sanitize_email( $data['email'] ?? '' );
		$phone      = sanitize_text_field( $data['phone'] ?? '' );

		if ( ! $first_name || ! $last_name || ! is_email( $email ) || ! $phone ) {
			wp_send_json_error( [ 'message' => 'Missing or invalid required fields.' ], 400 );
		}

		$assessment_id = TC_DB::insert_partial( [
			'firstName' => $first_name,
			'lastName'  => $last_name,
			'email'     => $email,
			'phone'     => $phone,
		] );

		TC_Log::info( 'partial_saved', [ 'assessment_id' => $assessment_id, 'email' => $email ] );

		wp_send_json_success( [ 'assessment_id' => $assessment_id ] );
	}

	public function save() {
		$this->verify_nonce();

		if ( function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		$payload = $this->json_body();

		$assessment_id = isset( $payload['assessment_id'] ) ? sanitize_text_field( $payload['assessment_id'] ) : '';
		if ( ! $assessment_id || ! wp_is_uuid( $assessment_id ) ) {
			wp_send_json_error( [ 'message' => 'Missing assessment_id.' ], 400 );
		}

		$existing = TC_DB::get_by_assessment_id( $assessment_id );
		if ( ! $existing ) {
			wp_send_json_error( [ 'message' => 'Assessment not found.' ], 404 );
		}

		$payload = $this->normalize_payload( $payload );

		$eligibility = TC_Eligibility_Rules::evaluate( $payload );

		TC_DB::update_complete( $assessment_id, $payload, $eligibility );

		if ( ! $eligibility['eligible'] ) {
			TC_Log::info( 'submission_ineligible', [
				'assessment_id' => $assessment_id,
				'reason'        => $eligibility['reason'],
				'bmi'           => $payload['bmi'] ?? 0,
				'age_band'      => $payload['ageBand'] ?? '',
				'ethnicity'     => $payload['ethnicity'] ?? '',
			] );

			TC_Emails::send_clinician_notification( $payload, $assessment_id, $eligibility );

			wp_send_json_success( [
				'eligible' => false,
				'reason'   => $eligibility['reason'],
			] );
		}

		$user_id = TC_Account::ensure_account_for( $payload, $assessment_id );
		if ( $user_id ) {
			TC_DB::attach_user( $assessment_id, $user_id );
		}

		TC_Cookie_Store::save_to_session( array_merge( $payload, [ 'assessment_id' => $assessment_id ] ) );

		// Review-first model: the order is created here, at submission, in the
		// awaiting-review status. Payment happens later via the pay link the
		// prescriber's approval sends — there is no cart or checkout step.
		$order    = TC_Review_Order::create_from_assessment( $payload, $assessment_id, $user_id );
		$order_id = 0;
		if ( is_wp_error( $order ) ) {
			wp_send_json_error( [ 'message' => $order->get_error_message() ], 500 );
		} else {
			$order_id = $order->get_id();
		}

		TC_Emails::send_patient_confirmation( $payload, $assessment_id );
		TC_Emails::send_clinician_notification( $payload, $assessment_id, $eligibility, $order_id );

		TC_Log::info( 'submission_eligible', [
			'assessment_id' => $assessment_id,
			'user_id'       => $user_id,
			'order_id'      => $order_id,
			'treatment'     => $payload['selectedTreatment'] ?? '',
			'dose'          => $payload['selectedDose'] ?? '',
		] );

		nocache_headers();

		wp_send_json_success( [
			'eligible'      => true,
			'assessment_id' => $assessment_id,
			'order_id'      => $order_id,
			'nonce'         => wp_create_nonce( self::NONCE_ACTION ),
		] );
	}

	public function add_to_cart() {
		$this->verify_nonce();

		if ( function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( [ 'message' => 'WooCommerce not available.' ], 500 );
		}

		$cookie = TC_Cookie_Store::get();
		if ( empty( $cookie ) || empty( $cookie['selectedTreatment'] ) ) {
			TC_Log::warn( 'add_to_cart_no_data', [
				'has_cookie'  => isset( $_COOKIE[ TC_Cookie_Store::COOKIE_NAME ] ) ? 'yes' : 'no',
				'has_session' => ( function_exists( 'WC' ) && WC()->session && WC()->session->has_session() ) ? 'yes' : 'no',
				'has_data'    => ! empty( $cookie ) ? 'yes' : 'no',
			] );
			wp_send_json_error( [ 'message' => 'No eligibility data found. Please complete the assessment again.' ], 400 );
		}

		$treatment = $cookie['selectedTreatment'];
		$dose      = $cookie['selectedDose'] ?? '';
		if ( ! $dose ) {
			$dose = $treatment === 'wegovy' ? '0.25mg' : '2.5mg';
		}

		$variation_id = TC_Variation_Map::get_variation_id( $treatment, $dose );
		if ( ! $variation_id ) {
			TC_Log::error( 'add_to_cart_variation_missing', [
				'treatment' => $treatment,
				'dose'      => $dose,
			] );
			wp_send_json_error( [
				'message' => sprintf( 'No product configured for %s %s. Please contact us.', $treatment, $dose ),
			], 500 );
		}

		$product = wc_get_product( $variation_id );
		if ( ! $product || ! $product->exists() ) {
			TC_Log::error( 'add_to_cart_variation_not_found', [ 'variation_id' => $variation_id ] );
			wp_send_json_error( [ 'message' => 'Product not found.' ], 404 );
		}

		WC()->cart->empty_cart();

		if ( $product->is_type( 'variation' ) ) {
			$parent_id        = $product->get_parent_id();
			$variation_id_arg = $product->get_id();
		} else {
			$parent_id        = $product->get_id();
			$variation_id_arg = 0;
		}

		$key = WC()->cart->add_to_cart( $parent_id, 1, $variation_id_arg );

		if ( ! $key ) {
			TC_Log::error( 'add_to_cart_failed', [
				'product_id'   => $parent_id,
				'variation_id' => $variation_id_arg,
				'product_type' => $product->get_type(),
			] );
			wp_send_json_error( [ 'message' => 'Could not add product to cart.' ], 500 );
		}

		TC_Log::info( 'add_to_cart_ok', [
			'product_id'   => $parent_id,
			'variation_id' => $variation_id_arg,
			'product_type' => $product->get_type(),
			'treatment'    => $treatment,
			'dose'         => $dose,
		] );

		wp_send_json_success( [
			'cart_url'     => wc_get_cart_url(),
			'checkout_url' => wc_get_checkout_url(),
		] );
	}

	public function ineligible() {
		$this->verify_nonce();

		$data         = $this->json_body();
		$assessment_id = sanitize_text_field( $data['assessment_id'] ?? '' );
		$reason        = sanitize_text_field( $data['reason'] ?? '' );

		if ( $assessment_id && wp_is_uuid( $assessment_id ) ) {
			global $wpdb;
			$wpdb->update(
				TC_DB::table_name(),
				[
					'status'            => 'ineligible',
					'ineligible_reason' => $reason,
				],
				[ 'assessment_id' => $assessment_id ]
			);

			TC_Log::info( 'ineligible_recorded', [
				'assessment_id' => $assessment_id,
				'reason'        => $reason,
			] );
		}

		wp_send_json_success();
	}

	public function get_doses() {
		$treatment = sanitize_text_field( $_GET['treatment'] ?? '' );
		$treatment = TC_Variation_Map::normalize_treatment( $treatment );

		wp_send_json_success( [
			'doses' => TC_Variation_Map::get_doses( $treatment ),
		] );
	}

	private function verify_nonce() {
		$nonce = $_REQUEST['nonce'] ?? $_SERVER['HTTP_X_TC_NONCE'] ?? '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			wp_send_json_error( [ 'message' => 'Security check failed. Please refresh and try again.' ], 403 );
		}
	}

	private function json_body() {
		$raw = file_get_contents( 'php://input' );
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
		$p['phone']               = sanitize_text_field( $p['phone'] ?? '' );
		$p['dob']                 = sanitize_text_field( $p['dob'] ?? '' );
		$p['userType']            = in_array( $p['userType'] ?? '', [ 'new', 'switching' ], true ) ? $p['userType'] : 'new';
		$p['provider']            = sanitize_text_field( $p['provider'] ?? '' );
		$p['currentMedication']   = TC_Variation_Map::normalize_treatment( $p['currentMedication'] ?? '' );
		$p['currentDose']         = TC_Variation_Map::normalize_dose( $p['currentDose'] ?? '' );
		$p['ageBand']             = sanitize_text_field( $p['ageBand'] ?? '' );
		$p['ethnicity']           = sanitize_text_field( $p['ethnicity'] ?? '' );
		$p['sex']                 = in_array( $p['sex'] ?? '', [ 'male', 'female' ], true ) ? $p['sex'] : '';
		$p['weightKg']            = (float) ( $p['weightKg'] ?? 0 );
		$p['heightCm']            = (float) ( $p['heightCm'] ?? 0 );
		$p['bmi']                 = (float) ( $p['bmi'] ?? 0 );
		$p['diabetes']            = sanitize_text_field( $p['diabetes'] ?? '' );
		$p['pregnant']            = sanitize_text_field( $p['pregnant'] ?? '' );
		$p['breastfeeding']       = sanitize_text_field( $p['breastfeeding'] ?? '' );
		$p['conceive']            = sanitize_text_field( $p['conceive'] ?? '' );
		$p['conditions']          = $this->clean_string_array( $p['conditions'] ?? [] );
		$p['weightConditions']    = $this->clean_string_array( $p['weightConditions'] ?? [] );
		$p['prevMeds']            = $this->clean_string_array( $p['prevMeds'] ?? [] );
		$p['bariatricDetails']    = sanitize_textarea_field( $p['bariatricDetails'] ?? '' );
		$p['mentalHealthDetails'] = sanitize_textarea_field( $p['mentalHealthDetails'] ?? '' );
		$p['otherConditions']     = sanitize_textarea_field( $p['otherConditions'] ?? '' );
		$p['currentMedsList']     = sanitize_textarea_field( $p['currentMedsList'] ?? $p['currentMeds'] ?? '' );
		$p['allergiesList']       = sanitize_textarea_field( $p['allergiesList'] ?? $p['allergies'] ?? '' );
		$p['goalWeight']          = sanitize_text_field( $p['goalWeight'] ?? '' );
		$p['addressLine1']        = sanitize_text_field( $p['addressLine1'] ?? '' );
		$p['addressLine2']        = sanitize_text_field( $p['addressLine2'] ?? '' );
		$p['city']                = sanitize_text_field( $p['city'] ?? '' );
		$p['postcode']            = strtoupper( sanitize_text_field( $p['postcode'] ?? '' ) );
		$p['country']             = sanitize_text_field( $p['country'] ?? 'United Kingdom' );
		$p['gpName']              = sanitize_text_field( $p['gpName'] ?? '' );
		$p['gpPostcode']          = strtoupper( sanitize_text_field( $p['gpPostcode'] ?? '' ) );
		$p['gpConsentShare']      = ! empty( $p['gpConsentShare'] );
		$p['gpConsentSCR']        = ! empty( $p['gpConsentSCR'] );
		$p['selectedTreatment']   = TC_Variation_Map::normalize_treatment( $p['selectedTreatment'] ?? '' );
		$p['selectedDose']        = TC_Variation_Map::normalize_dose( $p['selectedDose'] ?? '' );
		$p['termsAgreed']         = ! empty( $p['termsAgreed'] );

		return $p;
	}

	private function clean_string_array( $list ) {
		if ( ! is_array( $list ) ) {
			return [];
		}
		return array_values( array_filter( array_map( 'sanitize_text_field', $list ), function ( $v ) {
			return $v !== '';
		} ) );
	}

}
