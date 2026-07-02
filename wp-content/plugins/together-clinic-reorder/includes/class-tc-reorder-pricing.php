<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Reorder_Pricing {

	public static function get_variation_map() {
		if ( class_exists( 'TC_Variation_Map' ) ) {
			return TC_Variation_Map::all();
		}

		$saved = get_option( 'tc_eligibility_variation_map', [] );
		if ( ! is_array( $saved ) ) {
			return self::defaults();
		}

		return array_replace_recursive( self::defaults(), $saved );
	}

	public static function get_product_id( $treatment, $dose ) {
		if ( class_exists( 'TC_Variation_Map' ) ) {
			return TC_Variation_Map::get_variation_id( $treatment, $dose );
		}

		$map = self::get_variation_map();
		$treatment = self::normalize_treatment( $treatment );
		$dose      = self::normalize_dose( $dose );

		return isset( $map[ $treatment ][ $dose ] ) ? (int) $map[ $treatment ][ $dose ] : 0;
	}

	public static function get_price( $treatment, $dose ) {
		$product_id = self::get_product_id( $treatment, $dose );
		if ( ! $product_id ) {
			return null;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->exists() ) {
			return null;
		}

		return (float) $product->get_price();
	}

	public static function get_formatted_price( $treatment, $dose ) {
		$price = self::get_price( $treatment, $dose );
		if ( $price === null ) {
			return '';
		}
		return wp_strip_all_tags( wc_price( $price ) );
	}

	public static function get_dose_options_with_prices( $treatment ) {
		$treatment = self::normalize_treatment( $treatment );
		$doses     = self::get_doses_for_treatment( $treatment );
		$out       = [];

		foreach ( $doses as $dose ) {
			$price = self::get_price( $treatment, $dose );
			if ( $price === null ) {
				continue;
			}

			$out[] = [
				'dose'            => $dose,
				'price'           => $price,
				'formatted_price' => wp_strip_all_tags( wc_price( $price ) ),
				'product_id'      => self::get_product_id( $treatment, $dose ),
			];
		}

		return $out;
	}

	public static function get_doses_for_treatment( $treatment ) {
		$treatment = self::normalize_treatment( $treatment );
		$map       = self::get_variation_map();
		return isset( $map[ $treatment ] ) ? array_keys( $map[ $treatment ] ) : [];
	}

	public static function normalize_treatment( $treatment ) {
		if ( class_exists( 'TC_Variation_Map' ) ) {
			return TC_Variation_Map::normalize_treatment( $treatment );
		}

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
		if ( class_exists( 'TC_Variation_Map' ) ) {
			return TC_Variation_Map::normalize_dose( $dose );
		}

		$dose = strtolower( trim( (string) $dose ) );
		$dose = str_replace( [ ' ', 'milligrams', 'milligram' ], '', $dose );
		$dose = preg_replace( '/[^0-9\.mg]/', '', $dose );
		if ( $dose !== '' && strpos( $dose, 'mg' ) === false ) {
			$dose .= 'mg';
		}
		return $dose;
	}

	private static function defaults() {
		return [
			'wegovy' => [
				'0.25mg' => 0,
				'0.5mg'  => 0,
				'1mg'    => 0,
				'1.7mg'  => 0,
				'2.4mg'  => 0,
			],
			'mounjaro' => [
				'2.5mg'  => 0,
				'5mg'    => 0,
				'7.5mg'  => 0,
				'10mg'   => 0,
				'12.5mg' => 0,
				'15mg'   => 0,
			],
		];
	}
}
