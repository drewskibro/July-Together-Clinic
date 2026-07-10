<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Variation_Map {

	const OPTION_KEY = 'tc_eligibility_variation_map';

	public static function defaults() {
		// Dose keys and their order come from the canonical ladder, so the
		// settings screen, pricing and the ±1 gate can never disagree on what
		// doses exist or what "one step" means.
		$defaults = [];
		foreach ( TC_Dose_Ladder::LADDERS as $treatment => $doses ) {
			$defaults[ $treatment ] = array_fill_keys( $doses, 0 );
		}
		return $defaults;
	}

	public static function all() {
		$saved = get_option( self::OPTION_KEY, [] );
		if ( ! is_array( $saved ) ) {
			$saved = [];
		}

		$map = array_replace_recursive( self::defaults(), $saved );
		return apply_filters( 'tc_eligibility_variation_map', $map );
	}

	public static function get_variation_id( $treatment, $dose ) {
		$treatment = self::normalize_treatment( $treatment );
		$dose      = self::normalize_dose( $dose );
		$map       = self::all();

		return isset( $map[ $treatment ][ $dose ] ) ? (int) $map[ $treatment ][ $dose ] : 0;
	}

	public static function get_doses( $treatment ) {
		$treatment = self::normalize_treatment( $treatment );
		$map       = self::all();

		return isset( $map[ $treatment ] ) ? array_keys( $map[ $treatment ] ) : [];
	}

	public static function save( array $map ) {
		$clean = [];
		foreach ( self::defaults() as $treatment => $doses ) {
			$clean[ $treatment ] = [];
			foreach ( $doses as $dose => $default_id ) {
				$value                          = isset( $map[ $treatment ][ $dose ] ) ? (int) $map[ $treatment ][ $dose ] : 0;
				$clean[ $treatment ][ $dose ] = max( 0, $value );
			}
		}
		update_option( self::OPTION_KEY, $clean, false );
	}

	public static function normalize_treatment( $treatment ) {
		$treatment = strtolower( trim( (string) $treatment ) );
		if ( strpos( $treatment, 'mounjaro' ) !== false || strpos( $treatment, 'tirzepatide' ) !== false ) {
			return 'mounjaro';
		}
		if ( strpos( $treatment, 'wegovy' ) !== false || strpos( $treatment, 'semaglutide' ) !== false ) {
			return 'wegovy';
		}
		return $treatment;
	}

	public static function normalize_dose( $dose ) {
		$dose = strtolower( trim( (string) $dose ) );
		$dose = str_replace( [ ' ', 'milligrams', 'milligram' ], '', $dose );
		$dose = preg_replace( '/[^0-9\.mg]/', '', $dose );
		if ( $dose !== '' && strpos( $dose, 'mg' ) === false ) {
			$dose .= 'mg';
		}
		return $dose;
	}

	public static function qualifying_variation_ids() {
		$out = [];
		foreach ( self::all() as $doses ) {
			foreach ( $doses as $variation_id ) {
				if ( $variation_id > 0 ) {
					$out[] = (int) $variation_id;
				}
			}
		}
		return array_values( array_unique( $out ) );
	}

	public static function expected_sku_map() {
		return apply_filters( 'tc_eligibility_expected_sku_map', [
			'wegovy' => [
				'0.25mg' => 'WG-0.25',
				'0.5mg'  => 'WG-0.5',
				'1mg'    => 'WG-1',
				'1.7mg'  => 'WG-1.7',
				'2.4mg'  => 'WG-2.4',
			],
			'mounjaro' => [
				'2.5mg'  => 'MJ-2.5',
				'5mg'    => 'MJ-5',
				'7.5mg'  => 'MJ-7.5',
				'10mg'   => 'MJ-10',
				'12.5mg' => 'MJ-12.5',
				'15mg'   => 'MJ-15',
			],
		] );
	}

	public static function autodetect_from_skus() {
		if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
			return [ 'found' => 0, 'expected' => 0, 'missing' => [] ];
		}

		$map     = self::all();
		$found   = 0;
		$expected = 0;
		$missing = [];

		foreach ( self::expected_sku_map() as $treatment => $skus ) {
			foreach ( $skus as $dose => $sku ) {
				$expected++;
				$product_id = wc_get_product_id_by_sku( $sku );
				if ( $product_id ) {
					$map[ $treatment ][ $dose ] = (int) $product_id;
					$found++;
				} else {
					$missing[] = $sku;
				}
			}
		}

		self::save( $map );

		return [ 'found' => $found, 'expected' => $expected, 'missing' => $missing ];
	}
}
