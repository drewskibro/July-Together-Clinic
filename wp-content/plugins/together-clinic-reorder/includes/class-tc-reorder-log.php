<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TC_Reorder_Log {

	const PREFIX = '[tc-reorder]';

	public static function info( $msg, $context = [] ) {
		self::write( 'info', $msg, $context );
	}

	public static function warn( $msg, $context = [] ) {
		self::write( 'warn', $msg, $context );
	}

	public static function error( $msg, $context = [] ) {
		self::write( 'error', $msg, $context );
	}

	private static function write( $level, $msg, $context ) {
		$parts = [ self::PREFIX, strtoupper( $level ), $msg ];

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
