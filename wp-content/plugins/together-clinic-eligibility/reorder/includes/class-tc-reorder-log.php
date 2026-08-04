<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reorder-lane logging. A thin prefix wrapper over the shared TC_Log
 * formatter — output is byte-identical to the standalone implementation
 * this replaced.
 */
class TC_Reorder_Log {

	const PREFIX = '[tc-reorder]';

	public static function info( $msg, $context = [] ) {
		TC_Log::channel( self::PREFIX, 'info', $msg, $context );
	}

	public static function warn( $msg, $context = [] ) {
		TC_Log::channel( self::PREFIX, 'warn', $msg, $context );
	}

	public static function error( $msg, $context = [] ) {
		TC_Log::channel( self::PREFIX, 'error', $msg, $context );
	}
}
