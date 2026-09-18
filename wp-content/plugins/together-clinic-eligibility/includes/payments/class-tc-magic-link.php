<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Signed, expiring, purpose-scoped access tokens — the identity model that
 * retires the login/ownership class of failure.
 *
 * The old flow proved who you were with a WordPress account + WooCommerce order
 * ownership, and any mismatch (staff testing, a shared device, an auto-created
 * account nobody's logged into) dead-ended at "This order cannot be paid for."
 * Here, access to a submission's pay/resume page is carried by a token in the
 * link — the same bearer model as a Stripe/guest pay link, but self-contained
 * and stateless:
 *
 *   token = base64url(payload) . base64url(HMAC-SHA256(payload, secret))
 *   payload = { sub: submission id, pur: purpose, exp: unix expiry, nnc: nonce }
 *
 * Whoever holds the link can act on that ONE submission, for that ONE purpose,
 * until it expires — no login, from any device. Forgery needs the server
 * secret; tampering breaks the signature; the scope and expiry bound the blast
 * radius. The pay page exposes only product + price, never clinical data.
 *
 * Fail-closed: with no secret available, issue() returns '' and verify()
 * returns null — it never falls back to an empty/guessable key.
 */
class TC_Magic_Link {

	/** Matches the authorisation-hold window: a pay link is good for as long as the hold can live. */
	const DEFAULT_TTL = 604800; // 7 days, in seconds

	const PURPOSE_PAY    = 'pay';
	const PURPOSE_RESUME = 'resume';

	const QUERY_ARG = 'tc_token';

	/**
	 * Mint a token for a submission + purpose. Returns '' if no signing secret is
	 * configured (fail-closed — never issue an unverifiable token).
	 */
	public static function issue( string $submission_id, string $purpose, ?int $ttl = null, ?int $now = null ): string {
		if ( '' === self::secret() ) {
			return '';
		}
		$now = null === $now ? time() : $now;
		$ttl = null === $ttl ? self::DEFAULT_TTL : max( 60, $ttl );

		$payload = [
			'sub' => $submission_id,
			'pur' => $purpose,
			'exp' => $now + $ttl,
			'nnc' => self::nonce(),
		];

		$body = self::b64url_encode( self::json_encode( $payload ) );
		return $body . '.' . self::sign( $body );
	}

	/**
	 * Verify a token for the expected purpose. Returns the submission id on
	 * success, or null for any failure (bad signature, wrong purpose, expired,
	 * malformed, no secret). Constant-time signature check.
	 */
	public static function verify( string $token, string $purpose, ?int $now = null ): ?string {
		if ( '' === self::secret() ) {
			return null;
		}
		$now = null === $now ? time() : $now;

		$parts = explode( '.', $token );
		if ( 2 !== count( $parts ) ) {
			return null;
		}
		list( $body, $sig ) = $parts;

		if ( ! hash_equals( self::sign( $body ), $sig ) ) {
			return null;
		}

		$json = self::b64url_decode( $body );
		if ( null === $json ) {
			return null;
		}
		$payload = json_decode( $json, true );
		if ( ! is_array( $payload ) ) {
			return null;
		}

		if ( ( $payload['pur'] ?? '' ) !== $purpose ) {
			return null;
		}
		if ( ! isset( $payload['exp'] ) || $now > (int) $payload['exp'] ) {
			return null;
		}

		$submission_id = $payload['sub'] ?? '';
		return ( is_string( $submission_id ) && '' !== $submission_id ) ? $submission_id : null;
	}

	/** Build a magic link by appending the token to a base URL. */
	public static function url_for( string $base_url, string $submission_id, string $purpose, ?int $ttl = null ): string {
		$token = self::issue( $submission_id, $purpose, $ttl );
		if ( '' === $token ) {
			return $base_url;
		}
		$separator = ( false !== strpos( $base_url, '?' ) ) ? '&' : '?';
		return $base_url . $separator . self::QUERY_ARG . '=' . rawurlencode( $token );
	}

	/** Verify the token on the current request for a purpose, or null. */
	public static function from_request( string $purpose ): ?string {
		if ( empty( $_GET[ self::QUERY_ARG ] ) ) {
			return null;
		}
		$token = is_string( $_GET[ self::QUERY_ARG ] ) ? wp_unslash( $_GET[ self::QUERY_ARG ] ) : '';
		return self::verify( (string) $token, $purpose );
	}

	private static function sign( string $body ): string {
		return self::b64url_encode( hash_hmac( 'sha256', $body, self::secret(), true ) );
	}

	private static function secret(): string {
		if ( defined( 'TC_MAGIC_LINK_SECRET' ) && TC_MAGIC_LINK_SECRET ) {
			return (string) TC_MAGIC_LINK_SECRET;
		}
		if ( function_exists( 'wp_salt' ) ) {
			// Namespaced so the token key isn't the raw salt reused elsewhere.
			return hash_hmac( 'sha256', 'tc_magic_link', wp_salt( 'auth' ) );
		}
		return '';
	}

	private static function nonce(): string {
		if ( function_exists( 'wp_generate_password' ) ) {
			return wp_generate_password( 12, false );
		}
		return bin2hex( random_bytes( 6 ) );
	}

	private static function b64url_encode( string $data ): string {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	private static function b64url_decode( string $data ): ?string {
		$decoded = base64_decode( strtr( $data, '-_', '+/' ), true );
		return false === $decoded ? null : $decoded;
	}

	/** wp_json_encode() under WordPress, json_encode() otherwise — stays testable outside WP. */
	private static function json_encode( array $data ): string {
		return (string) ( function_exists( 'wp_json_encode' ) ? wp_json_encode( $data ) : json_encode( $data ) );
	}
}
