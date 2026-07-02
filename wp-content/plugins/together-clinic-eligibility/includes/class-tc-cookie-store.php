<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Cookie_Store {

	const COOKIE_NAME = 'tc_eligibility_data';
	const COOKIE_LIFETIME = DAY_IN_SECONDS;
	const SESSION_KEY = 'tc_eligibility_data';

	private static $request_cache = null;

	public static function get() {
		if ( self::$request_cache !== null ) {
			return self::$request_cache;
		}

		$session_data = self::get_from_session();
		if ( ! empty( $session_data ) && ! empty( $session_data['assessment_id'] ) ) {
			self::$request_cache = $session_data;
			return $session_data;
		}

		$assessment_id = self::read_assessment_id_from_cookie();
		if ( ! $assessment_id ) {
			self::$request_cache = [];
			return [];
		}

		$row = TC_DB::get_by_assessment_id( $assessment_id );
		if ( ! $row ) {
			self::$request_cache = [];
			return [];
		}

		$data = self::hydrate_from_row( $row );
		self::save_to_session( $data );
		self::$request_cache = $data;
		return $data;
	}

	public static function save_to_session( array $data ) {
		self::$request_cache = $data;

		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		if ( ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		WC()->session->set( self::SESSION_KEY, $data );
	}

	public static function clear() {
		self::$request_cache = null;

		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( self::SESSION_KEY, null );
		}

		if ( ! headers_sent() ) {
			// Clear with the same scope the front-end JS used to set the cookie
			// (path=/, host-only, no explicit domain). Using COOKIEPATH /
			// COOKIE_DOMAIN here can leave the cookie alive on sub-directory or
			// custom-domain installs where those differ from what the JS wrote.
			setcookie( self::COOKIE_NAME, '', time() - 3600, '/', '', is_ssl(), false );
		}
	}

	public static function get_field( $key, $default = '' ) {
		$data = self::get();
		return $data[ $key ] ?? $default;
	}

	public static function get_assessment_id() {
		$data = self::get();
		return $data['assessment_id'] ?? '';
	}

	public static function split_full_name( $full_name ) {
		$full_name = trim( (string) $full_name );
		if ( $full_name === '' ) {
			return [ '', '' ];
		}
		$parts = preg_split( '/\s+/', $full_name );
		$first = array_shift( $parts );
		$last  = implode( ' ', $parts );
		return [ $first, $last ];
	}

	private static function get_from_session() {
		if ( function_exists( 'WC' ) && WC()->session ) {
			$data = WC()->session->get( self::SESSION_KEY );
			if ( is_array( $data ) && ! empty( $data ) ) {
				return $data;
			}
		}
		return [];
	}

	private static function read_assessment_id_from_cookie() {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return '';
		}

		$raw = wp_unslash( $_COOKIE[ self::COOKIE_NAME ] );

		$data = json_decode( $raw, true );
		if ( is_array( $data ) && ! empty( $data['assessment_id'] ) && wp_is_uuid( $data['assessment_id'] ) ) {
			return $data['assessment_id'];
		}

		if ( wp_is_uuid( $raw ) ) {
			return $raw;
		}

		return '';
	}

	private static function hydrate_from_row( $row ) {
		$decode_json = function ( $val ) {
			if ( ! $val ) {
				return [];
			}
			$decoded = json_decode( $val, true );
			return is_array( $decoded ) ? $decoded : [];
		};

		$columns = [
			'assessment_id'       => (string) ( $row['assessment_id'] ?? '' ),
			'firstName'           => (string) ( $row['first_name'] ?? '' ),
			'lastName'            => (string) ( $row['last_name'] ?? '' ),
			'fullName'            => trim( ( $row['first_name'] ?? '' ) . ' ' . ( $row['last_name'] ?? '' ) ),
			'email'               => (string) ( $row['email'] ?? '' ),
			'phone'               => (string) ( $row['phone'] ?? '' ),
			'dob'                 => (string) ( $row['dob'] ?? '' ),
			'userType'            => (string) ( $row['user_type'] ?? 'new' ),
			'provider'            => (string) ( $row['provider'] ?? '' ),
			'currentMedication'   => (string) ( $row['current_medication'] ?? '' ),
			'currentDose'         => (string) ( $row['current_dose'] ?? '' ),
			'ageBand'             => (string) ( $row['age_band'] ?? '' ),
			'ethnicity'           => (string) ( $row['ethnicity'] ?? '' ),
			'sex'                 => (string) ( $row['sex'] ?? '' ),
			'weightKg'            => (float) ( $row['weight_kg'] ?? 0 ),
			'heightCm'            => (float) ( $row['height_cm'] ?? 0 ),
			'bmi'                 => (float) ( $row['bmi'] ?? 0 ),
			'pregnant'            => (string) ( $row['pregnant'] ?? '' ),
			'breastfeeding'       => (string) ( $row['breastfeeding'] ?? '' ),
			'conceive'            => (string) ( $row['conceive'] ?? '' ),
			'diabetes'            => (string) ( $row['diabetes'] ?? '' ),
			'conditions'          => $decode_json( $row['conditions'] ?? null ),
			'bariatricDetails'    => (string) ( $row['bariatric_details'] ?? '' ),
			'weightConditions'    => $decode_json( $row['weight_conditions'] ?? null ),
			'mentalHealthDetails' => (string) ( $row['mental_health_details'] ?? '' ),
			'otherConditions'     => (string) ( $row['other_conditions'] ?? '' ),
			'otherConditionsList' => (string) ( $row['other_conditions'] ?? '' ),
			'prevMeds'            => $decode_json( $row['prev_meds'] ?? null ),
			'currentMeds'         => (string) ( $row['current_meds'] ?? '' ),
			'currentMedsList'     => (string) ( $row['current_meds_list'] ?? '' ),
			'allergies'           => (string) ( $row['allergies'] ?? '' ),
			'allergiesList'       => (string) ( $row['allergies_list'] ?? '' ),
			'goalWeight'          => (string) ( $row['goal_weight'] ?? '' ),
			'addressLine1'        => (string) ( $row['address_line1'] ?? '' ),
			'addressLine2'        => (string) ( $row['address_line2'] ?? '' ),
			'city'                => (string) ( $row['city'] ?? '' ),
			'postcode'            => (string) ( $row['postcode'] ?? '' ),
			'country'             => (string) ( $row['country'] ?? 'United Kingdom' ),
			'gpName'              => (string) ( $row['gp_name'] ?? '' ),
			'gpPostcode'          => (string) ( $row['gp_postcode'] ?? '' ),
			'gpConsentShare'      => ! empty( $row['gp_consent_share'] ),
			'gpConsentSCR'        => ! empty( $row['gp_consent_scr'] ),
			'selectedTreatment'   => (string) ( $row['selected_treatment'] ?? '' ),
			'selectedDose'        => (string) ( $row['selected_dose'] ?? '' ),
			'termsAgreed'         => ! empty( $row['terms_agreed'] ),
		];

		// The raw client payload is the complete, authoritative snapshot of the
		// submission. The individual columns are a lossy projection of it — they
		// drop prevWeights / bariatricRecent and flatten otherConditionsList — so
		// rehydrate from raw_payload when it is present and fall back to the
		// column-derived values for anything it does not carry.
		$payload = $decode_json( $row['raw_payload'] ?? null );
		if ( ! empty( $payload ) ) {
			$merged = array_merge( $columns, $payload );
			// assessment_id is server-managed and never part of the client payload.
			$merged['assessment_id'] = $columns['assessment_id'];
			return $merged;
		}

		return $columns;
	}
}
