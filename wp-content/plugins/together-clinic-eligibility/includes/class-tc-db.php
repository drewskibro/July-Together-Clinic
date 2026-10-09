<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_DB {

	const TABLE = 'tc_eligibility_submissions';
	const SCHEMA_VERSION_OPTION = 'tc_eligibility_db_version';
	const SCHEMA_VERSION = '1.2.0';

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	public static function create_table() {
		global $wpdb;
		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			assessment_id CHAR(36) NOT NULL,
			status VARCHAR(32) NOT NULL DEFAULT 'partial',
			user_id BIGINT UNSIGNED NULL DEFAULT NULL,
			order_id BIGINT UNSIGNED NULL DEFAULT NULL,
			first_name VARCHAR(120) NOT NULL DEFAULT '',
			last_name VARCHAR(120) NOT NULL DEFAULT '',
			email VARCHAR(190) NOT NULL DEFAULT '',
			phone VARCHAR(40) NOT NULL DEFAULT '',
			dob VARCHAR(20) NOT NULL DEFAULT '',
			user_type VARCHAR(20) NOT NULL DEFAULT 'new',
			provider VARCHAR(60) NOT NULL DEFAULT '',
			current_medication VARCHAR(40) NOT NULL DEFAULT '',
			current_dose VARCHAR(20) NOT NULL DEFAULT '',
			age_band VARCHAR(20) NOT NULL DEFAULT '',
			ethnicity VARCHAR(80) NOT NULL DEFAULT '',
			sex VARCHAR(10) NOT NULL DEFAULT '',
			weight_kg DECIMAL(6,2) NOT NULL DEFAULT 0,
			height_cm DECIMAL(6,2) NOT NULL DEFAULT 0,
			bmi DECIMAL(5,2) NOT NULL DEFAULT 0,
			diabetes VARCHAR(20) NOT NULL DEFAULT '',
			pregnant VARCHAR(10) NOT NULL DEFAULT '',
			breastfeeding VARCHAR(10) NOT NULL DEFAULT '',
			conceive VARCHAR(10) NOT NULL DEFAULT '',
			conditions LONGTEXT NULL,
			bariatric_details LONGTEXT NULL,
			weight_conditions LONGTEXT NULL,
			mental_health_details LONGTEXT NULL,
			other_conditions LONGTEXT NULL,
			prev_meds LONGTEXT NULL,
			prev_weights LONGTEXT NULL,
			current_meds VARCHAR(40) NOT NULL DEFAULT '',
			current_meds_list LONGTEXT NULL,
			allergies VARCHAR(10) NOT NULL DEFAULT '',
			allergies_list LONGTEXT NULL,
			goal_weight VARCHAR(20) NOT NULL DEFAULT '',
			address_line1 VARCHAR(190) NOT NULL DEFAULT '',
			address_line2 VARCHAR(190) NOT NULL DEFAULT '',
			city VARCHAR(120) NOT NULL DEFAULT '',
			postcode VARCHAR(20) NOT NULL DEFAULT '',
			country VARCHAR(60) NOT NULL DEFAULT 'United Kingdom',
			gp_name VARCHAR(190) NOT NULL DEFAULT '',
			gp_postcode VARCHAR(20) NOT NULL DEFAULT '',
			gp_consent_share TINYINT(1) NOT NULL DEFAULT 0,
			gp_consent_scr TINYINT(1) NOT NULL DEFAULT 0,
			selected_treatment VARCHAR(40) NOT NULL DEFAULT '',
			selected_dose VARCHAR(20) NOT NULL DEFAULT '',
			ineligible_reason TEXT NULL,
			terms_agreed TINYINT(1) NOT NULL DEFAULT 0,
			rules_version VARCHAR(30) NOT NULL DEFAULT '',
			start_weight_kg DECIMAL(6,2) NOT NULL DEFAULT 0,
			start_bmi DECIMAL(5,2) NOT NULL DEFAULT 0,
			last_dose_date VARCHAR(20) NOT NULL DEFAULT '',
			could_conceive VARCHAR(10) NOT NULL DEFAULT '',
			contraception VARCHAR(20) NOT NULL DEFAULT '',
			consent_contraception TINYINT(1) NOT NULL DEFAULT 0,
			consent_id_video TINYINT(1) NOT NULL DEFAULT 0,
			consent_lifestyle TINYINT(1) NOT NULL DEFAULT 0,
			s1a_falls VARCHAR(10) NOT NULL DEFAULT '',
			s1a_fracture VARCHAR(10) NOT NULL DEFAULT '',
			s1a_meds_count VARCHAR(20) NOT NULL DEFAULT '',
			s1a_bp_water VARCHAR(10) NOT NULL DEFAULT '',
			s1a_kidney_test VARCHAR(20) NOT NULL DEFAULT '',
			s1a_egfr_date VARCHAR(10) NOT NULL DEFAULT '',
			s1a_egfr_result VARCHAR(10) NOT NULL DEFAULT '',
			s1a_prisma LONGTEXT NULL,
			s1a_prisma_score VARCHAR(5) NOT NULL DEFAULT '',
			clinical_flags LONGTEXT NULL,
			raw_payload LONGTEXT NULL,
			ip_address VARCHAR(45) NOT NULL DEFAULT '',
			user_agent VARCHAR(255) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_assessment_id (assessment_id),
			KEY idx_email (email),
			KEY idx_status (status),
			KEY idx_order_id (order_id),
			KEY idx_user_id (user_id),
			KEY idx_created_at (created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION );
	}

	public static function maybe_upgrade() {
		if ( get_option( self::SCHEMA_VERSION_OPTION ) !== self::SCHEMA_VERSION ) {
			self::create_table();
		}
	}

	public static function insert_partial( array $data ) {
		global $wpdb;

		$assessment_id = wp_generate_uuid4();
		$row           = [
			'assessment_id' => $assessment_id,
			'status'        => 'partial',
			'first_name'    => sanitize_text_field( $data['firstName'] ?? '' ),
			'last_name'     => sanitize_text_field( $data['lastName'] ?? '' ),
			'email'         => sanitize_email( $data['email'] ?? '' ),
			'phone'         => sanitize_text_field( $data['phone'] ?? '' ),
			'ip_address'    => self::client_ip(),
			'user_agent'    => substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 ),
		];

		$wpdb->insert( self::table_name(), $row );
		return $assessment_id;
	}

	public static function update_complete( $assessment_id, array $payload, $eligibility ) {
		global $wpdb;

		$row = [
			'status'                => $eligibility['eligible'] ? 'eligible' : 'ineligible',
			'first_name'            => sanitize_text_field( $payload['firstName'] ?? '' ),
			'last_name'             => sanitize_text_field( $payload['lastName'] ?? '' ),
			'email'                 => sanitize_email( $payload['email'] ?? '' ),
			'phone'                 => sanitize_text_field( $payload['phone'] ?? '' ),
			'dob'                   => sanitize_text_field( $payload['dob'] ?? '' ),
			'user_type'             => sanitize_text_field( $payload['userType'] ?? 'new' ),
			'provider'              => sanitize_text_field( $payload['provider'] ?? '' ),
			'current_medication'    => sanitize_text_field( $payload['currentMedication'] ?? '' ),
			'current_dose'          => sanitize_text_field( $payload['currentDose'] ?? '' ),
			'age_band'              => sanitize_text_field( $payload['ageBand'] ?? '' ),
			'ethnicity'             => sanitize_text_field( $payload['ethnicity'] ?? '' ),
			'sex'                   => sanitize_text_field( $payload['sex'] ?? '' ),
			'weight_kg'             => (float) ( $payload['weightKg'] ?? 0 ),
			'height_cm'             => (float) ( $payload['heightCm'] ?? 0 ),
			'bmi'                   => (float) ( $payload['bmi'] ?? 0 ),
			'diabetes'              => sanitize_text_field( $payload['diabetes'] ?? '' ),
			'pregnant'              => sanitize_text_field( $payload['pregnant'] ?? '' ),
			'breastfeeding'         => sanitize_text_field( $payload['breastfeeding'] ?? '' ),
			'conceive'              => sanitize_text_field( $payload['conceive'] ?? '' ),
			'conditions'            => wp_json_encode( $payload['conditions'] ?? [] ),
			'bariatric_details'     => sanitize_textarea_field( $payload['bariatricDetails'] ?? '' ),
			'weight_conditions'     => wp_json_encode( $payload['weightConditions'] ?? [] ),
			'mental_health_details' => sanitize_textarea_field( $payload['mentalHealthDetails'] ?? '' ),
			'other_conditions'      => sanitize_textarea_field( $payload['otherConditions'] ?? '' ),
			'prev_meds'             => wp_json_encode( $payload['prevMeds'] ?? [] ),
			'prev_weights'          => wp_json_encode( $payload['prevWeights'] ?? new stdClass() ),
			'current_meds'          => sanitize_text_field( $payload['currentMeds'] ?? '' ),
			'current_meds_list'     => sanitize_textarea_field( $payload['currentMedsList'] ?? '' ),
			'allergies'             => sanitize_text_field( $payload['allergies'] ?? '' ),
			'allergies_list'        => sanitize_textarea_field( $payload['allergiesList'] ?? '' ),
			'goal_weight'           => sanitize_text_field( $payload['goalWeight'] ?? '' ),
			'address_line1'         => sanitize_text_field( $payload['addressLine1'] ?? '' ),
			'address_line2'         => sanitize_text_field( $payload['addressLine2'] ?? '' ),
			'city'                  => sanitize_text_field( $payload['city'] ?? '' ),
			'postcode'              => strtoupper( sanitize_text_field( $payload['postcode'] ?? '' ) ),
			'country'               => sanitize_text_field( $payload['country'] ?? 'United Kingdom' ),
			'gp_name'               => sanitize_text_field( $payload['gpName'] ?? '' ),
			'gp_postcode'           => strtoupper( sanitize_text_field( $payload['gpPostcode'] ?? '' ) ),
			'gp_consent_share'      => ! empty( $payload['gpConsentShare'] ) ? 1 : 0,
			'gp_consent_scr'        => ! empty( $payload['gpConsentSCR'] ) ? 1 : 0,
			'selected_treatment'    => sanitize_text_field( $payload['selectedTreatment'] ?? '' ),
			'selected_dose'         => sanitize_text_field( $payload['selectedDose'] ?? '' ),
			'ineligible_reason'     => $eligibility['eligible'] ? null : sanitize_text_field( $eligibility['reason'] ),
			'terms_agreed'          => ! empty( $payload['termsAgreed'] ) ? 1 : 0,
			'rules_version'         => sanitize_text_field( $payload['rulesVersion'] ?? '' ),
			'start_weight_kg'       => (float) ( $payload['startWeightKg'] ?? 0 ),
			'start_bmi'             => (float) ( $payload['startBmi'] ?? 0 ),
			'last_dose_date'        => sanitize_text_field( $payload['lastDoseDate'] ?? '' ),
			'could_conceive'        => sanitize_text_field( $payload['couldConceive'] ?? '' ),
			'contraception'         => sanitize_text_field( $payload['contraception'] ?? '' ),
			'consent_contraception' => ! empty( $payload['consentContraception'] ) ? 1 : 0,
			'consent_id_video'      => ! empty( $payload['consentIdVideo'] ) ? 1 : 0,
			'consent_lifestyle'     => ! empty( $payload['consentLifestyle'] ) ? 1 : 0,
			's1a_falls'             => sanitize_text_field( $payload['s1aFalls'] ?? '' ),
			's1a_fracture'          => sanitize_text_field( $payload['s1aFracture'] ?? '' ),
			's1a_meds_count'        => sanitize_text_field( $payload['s1aMedsCount'] ?? '' ),
			's1a_bp_water'          => sanitize_text_field( $payload['s1aBpWater'] ?? '' ),
			's1a_kidney_test'       => sanitize_text_field( $payload['s1aKidneyTest'] ?? '' ),
			's1a_egfr_date'         => sanitize_text_field( $payload['s1aEgfrDate'] ?? '' ),
			's1a_egfr_result'       => sanitize_text_field( $payload['s1aEgfrResult'] ?? '' ),
			's1a_prisma'            => wp_json_encode( $payload['s1aPrisma'] ?? [] ),
			's1a_prisma_score'      => isset( $payload['s1aPrismaScore'] ) ? (string) (int) $payload['s1aPrismaScore'] : '',
			'clinical_flags'        => wp_json_encode( $payload['clinicalFlags'] ?? [] ),
			'raw_payload'           => wp_json_encode( $payload ),
		];

		$wpdb->update( self::table_name(), $row, [ 'assessment_id' => $assessment_id ] );
	}

	public static function attach_order( $assessment_id, $order_id ) {
		global $wpdb;
		$wpdb->update(
			self::table_name(),
			[ 'order_id' => (int) $order_id, 'status' => 'order_placed' ],
			[ 'assessment_id' => $assessment_id ]
		);
	}

	public static function attach_user( $assessment_id, $user_id ) {
		global $wpdb;
		$wpdb->update(
			self::table_name(),
			[ 'user_id' => (int) $user_id ],
			[ 'assessment_id' => $assessment_id ]
		);
	}

	public static function get_by_assessment_id( $assessment_id ) {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE assessment_id = %s', $assessment_id ),
			ARRAY_A
		);
	}

	/**
	 * Most recent recorded height for a patient, from their eligibility
	 * assessments. Used by the reorder BMI floor (rules WM-2026-10-v1 onwards).
	 */
	public static function latest_height_for_user( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return 0.0;
		}
		return (float) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT height_cm FROM ' . self::table_name() . ' WHERE user_id = %d AND height_cm > 0 ORDER BY created_at DESC LIMIT 1',
				$user_id
			)
		);
	}

	/**
	 * The assessment that started a patient's Orlistat treatment, for the
	 * 12-week stop rule (rules O4.2, WM-2026-10-v3). Prefers the latest
	 * assessment that chose Orlistat and created an order; falls back to the
	 * latest assessment with an order (a patient may have changed to Orlistat
	 * on the pay page).
	 *
	 * @return array|null { weight_kg, start_weight_kg, user_type, current_medication, selected_treatment, created_at }
	 */
	public static function orlistat_start_for_user( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return null;
		}
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT weight_kg, start_weight_kg, user_type, current_medication, selected_treatment, created_at FROM " . self::table_name() . "
				 WHERE user_id = %d AND order_id IS NOT NULL AND weight_kg > 0
				 ORDER BY ( selected_treatment = 'orlistat' ) DESC, created_at DESC LIMIT 1",
				$user_id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function purge_stale( $days = 30 ) {
		global $wpdb;
		$table = self::table_name();
		$days  = max( 1, (int) $days );

		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table}
				 WHERE status IN ('partial','ineligible','eligible')
				   AND order_id IS NULL
				   AND created_at < (NOW() - INTERVAL %d DAY)",
				$days
			)
		);
	}

	public static function count_by_status() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT status, COUNT(*) AS n FROM ' . self::table_name() . ' GROUP BY status', ARRAY_A );
		$out  = [];
		foreach ( (array) $rows as $r ) {
			$out[ $r['status'] ] = (int) $r['n'];
		}
		return $out;
	}

	private static function client_ip() {
		foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ] as $k ) {
			if ( ! empty( $_SERVER[ $k ] ) ) {
				$ip = trim( explode( ',', $_SERVER[ $k ] )[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}
		return '';
	}
}
