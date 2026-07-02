<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Reorder_DB {

	const TABLE = 'tc_reorder_submissions';
	const SCHEMA_VERSION_OPTION = 'tc_reorder_db_version';
	const SCHEMA_VERSION = '1.0.0';

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
			previous_order_id BIGINT UNSIGNED NULL DEFAULT NULL,
			order_id BIGINT UNSIGNED NULL DEFAULT NULL,
			first_name VARCHAR(120) NOT NULL DEFAULT '',
			last_name VARCHAR(120) NOT NULL DEFAULT '',
			email VARCHAR(190) NOT NULL DEFAULT '',
			dob VARCHAR(20) NOT NULL DEFAULT '',
			current_medication VARCHAR(40) NOT NULL DEFAULT '',
			current_dose VARCHAR(20) NOT NULL DEFAULT '',
			selected_dose VARCHAR(20) NOT NULL DEFAULT '',
			current_weight DECIMAL(6,2) NOT NULL DEFAULT 0,
			has_lost_weight VARCHAR(10) NOT NULL DEFAULT '',
			appetite_lasting VARCHAR(10) NOT NULL DEFAULT '',
			has_side_effects VARCHAR(10) NOT NULL DEFAULT '',
			health_changed VARCHAR(10) NOT NULL DEFAULT '',
			new_medications VARCHAR(10) NOT NULL DEFAULT '',
			new_medications_list TEXT NULL,
			could_be_pregnant VARCHAR(10) NOT NULL DEFAULT '',
			wants_clinical_support VARCHAR(10) NOT NULL DEFAULT '',
			block_reason VARCHAR(40) NOT NULL DEFAULT '',
			raw_payload LONGTEXT NULL,
			ip_address VARCHAR(45) NOT NULL DEFAULT '',
			user_agent VARCHAR(255) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_assessment_id (assessment_id),
			KEY idx_user_id (user_id),
			KEY idx_status (status),
			KEY idx_order_id (order_id),
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

	public static function insert_partial( $user_id, $previous_order_id, array $data ) {
		global $wpdb;
		$assessment_id = wp_generate_uuid4();

		$wpdb->insert( self::table_name(), [
			'assessment_id'     => $assessment_id,
			'status'            => 'partial',
			'user_id'           => (int) $user_id,
			'previous_order_id' => (int) $previous_order_id,
			'first_name'        => sanitize_text_field( $data['firstName'] ?? '' ),
			'last_name'         => sanitize_text_field( $data['lastName'] ?? '' ),
			'email'             => sanitize_email( $data['email'] ?? '' ),
			'ip_address'        => self::client_ip(),
			'user_agent'        => substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 ),
		] );

		return $assessment_id;
	}

	public static function update_complete( $assessment_id, array $payload, $status = 'complete', $block_reason = '' ) {
		global $wpdb;
		$wpdb->update( self::table_name(), [
			'status'                 => $status,
			'first_name'             => sanitize_text_field( $payload['firstName'] ?? '' ),
			'last_name'              => sanitize_text_field( $payload['lastName'] ?? '' ),
			'email'                  => sanitize_email( $payload['email'] ?? '' ),
			'dob'                    => sanitize_text_field( $payload['dob'] ?? '' ),
			'current_medication'     => sanitize_text_field( $payload['currentMedication'] ?? '' ),
			'current_dose'           => sanitize_text_field( $payload['currentDose'] ?? '' ),
			'selected_dose'          => sanitize_text_field( $payload['selectedDose'] ?? '' ),
			'current_weight'         => (float) ( $payload['currentWeight'] ?? 0 ),
			'has_lost_weight'        => sanitize_text_field( $payload['hasLostWeight'] ?? '' ),
			'appetite_lasting'       => sanitize_text_field( $payload['appetiteLasting'] ?? '' ),
			'has_side_effects'       => sanitize_text_field( $payload['hasSideEffects'] ?? '' ),
			'health_changed'         => sanitize_text_field( $payload['healthChanged'] ?? '' ),
			'new_medications'        => sanitize_text_field( $payload['newMedications'] ?? '' ),
			'new_medications_list'   => sanitize_textarea_field( $payload['newMedicationsList'] ?? '' ),
			'could_be_pregnant'      => sanitize_text_field( $payload['couldBePregnant'] ?? '' ),
			'wants_clinical_support' => sanitize_text_field( $payload['wantsClinicalSupport'] ?? '' ),
			'block_reason'           => sanitize_text_field( $block_reason ),
			'raw_payload'            => wp_json_encode( $payload ),
		], [ 'assessment_id' => $assessment_id ] );
	}

	public static function attach_order( $assessment_id, $order_id ) {
		global $wpdb;
		$wpdb->update( self::table_name(),
			[ 'order_id' => (int) $order_id, 'status' => 'order_placed' ],
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

	public static function count_by_status() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT status, COUNT(*) AS n FROM ' . self::table_name() . ' GROUP BY status', ARRAY_A );
		$out  = [];
		foreach ( (array) $rows as $r ) {
			$out[ $r['status'] ] = (int) $r['n'];
		}
		return $out;
	}

	public static function purge_stale( $days = 30 ) {
		global $wpdb;
		$table = self::table_name();
		$days  = max( 1, (int) $days );
		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE status IN ('partial','blocked') AND order_id IS NULL AND created_at < (NOW() - INTERVAL %d DAY)",
				$days
			)
		);
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
