<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The provider registry and resolver — the one place that decides which
 * processor is live, so switching (or adding a failover) is configuration, not
 * a code change scattered through the flow.
 *
 * active() is deliberately fail-closed: it returns the configured provider only
 * when that provider is actually usable (is_configured() === true); otherwise it
 * returns the Null provider, which fails every operation loudly. There is no
 * code path that silently downgrades to a weaker or fake processor.
 */
class TC_Payment_Providers {

	/** Option holding the active provider id (e.g. 'stripe'). */
	const OPTION_ACTIVE = 'tc_active_payment_provider';

	/** @var TC_Payment_Provider[] keyed by id. */
	private static $providers = [];

	public static function register( TC_Payment_Provider $provider ): void {
		self::$providers[ $provider->id() ] = $provider;
	}

	public static function get( string $id ): ?TC_Payment_Provider {
		return self::$providers[ $id ] ?? null;
	}

	/** @return TC_Payment_Provider[] */
	public static function all(): array {
		return self::$providers;
	}

	/**
	 * The provider that should handle payments right now. Resolves the
	 * configured id (option, overridable via the tc_active_payment_provider
	 * filter), and returns it only if it is registered AND configured.
	 * Otherwise fails closed to the Null provider.
	 */
	public static function active(): TC_Payment_Provider {
		$id = '';
		if ( function_exists( 'get_option' ) ) {
			$id = (string) get_option( self::OPTION_ACTIVE, '' );
		}
		if ( function_exists( 'apply_filters' ) ) {
			$id = (string) apply_filters( 'tc_active_payment_provider', $id );
		}

		$provider = '' !== $id ? self::get( $id ) : null;
		if ( $provider instanceof TC_Payment_Provider && $provider->is_configured() ) {
			return $provider;
		}

		return new TC_Null_Payment_Provider();
	}

	/** True when a real, configured provider is ready to take payments. */
	public static function has_active(): bool {
		return ! ( self::active() instanceof TC_Null_Payment_Provider );
	}

	/** Register the built-in providers. The Stripe adapter registers itself here once it exists. */
	public static function bootstrap(): void {
		self::register( new TC_Fake_Payment_Provider() );
	}
}
