<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Reorder_Cookie_Store {

	const COOKIE_NAME = 'tc_reorder_data';
	const COOKIE_LIFETIME = DAY_IN_SECONDS;
	const SESSION_KEY = 'tc_reorder_data';

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

		$row = TC_Reorder_DB::get_by_assessment_id( $assessment_id );
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
			setcookie( self::COOKIE_NAME, '', time() - 3600, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), false );
		}
	}

	public static function get_assessment_id() {
		$data = self::get();
		return $data['assessment_id'] ?? '';
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

		$raw  = wp_unslash( $_COOKIE[ self::COOKIE_NAME ] );
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
		return [
			'assessment_id'         => (string) ( $row['assessment_id'] ?? '' ),
			'previousOrderId'       => (int) ( $row['previous_order_id'] ?? 0 ),
			'firstName'             => (string) ( $row['first_name'] ?? '' ),
			'lastName'              => (string) ( $row['last_name'] ?? '' ),
			'email'                 => (string) ( $row['email'] ?? '' ),
			'dob'                   => (string) ( $row['dob'] ?? '' ),
			'currentMedication'     => (string) ( $row['current_medication'] ?? '' ),
			'currentDose'           => (string) ( $row['current_dose'] ?? '' ),
			'selectedDose'          => (string) ( $row['selected_dose'] ?? '' ),
			'currentWeight'         => (float) ( $row['current_weight'] ?? 0 ),
			'hasLostWeight'         => (string) ( $row['has_lost_weight'] ?? '' ),
			'appetiteLasting'       => (string) ( $row['appetite_lasting'] ?? '' ),
			'hasSideEffects'        => (string) ( $row['has_side_effects'] ?? '' ),
			'healthChanged'         => (string) ( $row['health_changed'] ?? '' ),
			'newMedications'        => (string) ( $row['new_medications'] ?? '' ),
			'newMedicationsList'    => (string) ( $row['new_medications_list'] ?? '' ),
			'couldBePregnant'       => (string) ( $row['could_be_pregnant'] ?? '' ),
			'wantsClinicalSupport'  => (string) ( $row['wants_clinical_support'] ?? '' ),
		];
	}
}
