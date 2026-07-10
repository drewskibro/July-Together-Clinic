<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Log {

	const PREFIX = '[tc-eligibility]';

	public static function info( $msg, $context = [] ) {
		self::channel( self::PREFIX, 'info', $msg, $context );
	}

	public static function warn( $msg, $context = [] ) {
		self::channel( self::PREFIX, 'warn', $msg, $context );
	}

	public static function error( $msg, $context = [] ) {
		self::channel( self::PREFIX, 'error', $msg, $context );
	}

	/**
	 * The single log-formatting implementation. Lane-specific log classes
	 * (TC_Reorder_Log) delegate here with their own prefix.
	 */
	public static function channel( $prefix, $level, $msg, $context = [] ) {
		$parts = [ $prefix, strtoupper( $level ), $msg ];

		if ( ! empty( $context ) ) {
			$flat = [];
			foreach ( $context as $k => $v ) {
				if ( is_scalar( $v ) || is_null( $v ) ) {
					$flat[] = $k . '=' . ( is_null( $v ) ? 'null' : (string) $v );
				} else {
					$flat[] = $k . '=' . wp_json_encode( $v );
				}
			}
			$parts[] = implode( ' ', $flat );
		}

		error_log( implode( ' ', $parts ) );
	}
}
