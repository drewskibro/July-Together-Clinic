<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The provider-neutral outcome of an authorise / capture / void call.
 *
 * Every processor's raw response is mapped down to this one shape, so the rest
 * of the system — the clinical review queue, the UX, the audit log — never has
 * to know whose API answered. Swapping processors changes which adapter
 * produces this object, and nothing downstream.
 *
 * `raw` keeps the untouched provider payload for audit and debugging; nothing
 * in the app should branch on it.
 */
class TC_Payment_Result {

	/** Funds are held (authorised) but NOT taken. The clinical-review window. */
	const AUTHORIZED = 'authorized';

	/** The client must complete an action (3-D Secure / SCA) before the hold is live. */
	const REQUIRES_ACTION = 'requires_action';

	/** The held funds have been taken. */
	const CAPTURED = 'captured';

	/** The hold has been released; no money moved. */
	const VOIDED = 'voided';

	/** The operation did not succeed. See error_code / error_message. */
	const FAILED = 'failed';

	public string $status;
	public ?string $hold_ref;
	public ?string $client_secret;
	public int $amount_minor;
	public string $currency;
	public ?string $error_code;
	public ?string $error_message;
	public array $raw;

	public function __construct(
		string $status,
		?string $hold_ref,
		int $amount_minor,
		string $currency,
		?string $client_secret = null,
		?string $error_code = null,
		?string $error_message = null,
		array $raw = []
	) {
		$this->status        = $status;
		$this->hold_ref      = $hold_ref;
		$this->amount_minor  = max( 0, $amount_minor );
		$this->currency      = strtoupper( $currency );
		$this->client_secret = $client_secret;
		$this->error_code    = $error_code;
		$this->error_message = $error_message;
		$this->raw           = $raw;
	}

	public static function authorized( string $hold_ref, int $amount_minor, string $currency, array $raw = [] ): self {
		return new self( self::AUTHORIZED, $hold_ref, $amount_minor, $currency, null, null, null, $raw );
	}

	public static function requires_action( string $hold_ref, string $client_secret, int $amount_minor, string $currency, array $raw = [] ): self {
		return new self( self::REQUIRES_ACTION, $hold_ref, $amount_minor, $currency, $client_secret, null, null, $raw );
	}

	public static function captured( string $hold_ref, int $amount_minor, string $currency, array $raw = [] ): self {
		return new self( self::CAPTURED, $hold_ref, $amount_minor, $currency, null, null, null, $raw );
	}

	public static function voided( string $hold_ref, int $amount_minor, string $currency, array $raw = [] ): self {
		return new self( self::VOIDED, $hold_ref, $amount_minor, $currency, null, null, null, $raw );
	}

	public static function failed( string $error_code, string $error_message, int $amount_minor, string $currency, array $raw = [] ): self {
		return new self( self::FAILED, null, $amount_minor, $currency, null, $error_code, $error_message, $raw );
	}

	public function is_authorized(): bool {
		return self::AUTHORIZED === $this->status;
	}

	public function needs_action(): bool {
		return self::REQUIRES_ACTION === $this->status;
	}

	public function is_captured(): bool {
		return self::CAPTURED === $this->status;
	}

	public function is_voided(): bool {
		return self::VOIDED === $this->status;
	}

	public function is_failed(): bool {
		return self::FAILED === $this->status;
	}

	/** True for any non-failed outcome. */
	public function ok(): bool {
		return self::FAILED !== $this->status;
	}
}
