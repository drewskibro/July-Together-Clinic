<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * An immutable request to authorise a payment — everything a provider needs to
 * place a hold, expressed in provider-neutral terms.
 *
 * Amounts are in the currency's MINOR unit (pence for GBP) — the unit every
 * processor's API actually speaks, so there is no rounding drift between us and
 * them. Never store or pass a float here.
 *
 * The idempotency_key is the safety rail against double-charging: a provider
 * MUST treat two authorise() calls with the same key as the same operation and
 * return the same hold, never a second one. It is derived from the submission +
 * treatment + dose + amount, so an honest retry (network blip, double-click) is
 * idempotent, but a genuine change of treatment produces a new key and a new
 * hold.
 */
class TC_Payment_Request {

	/** @var int Amount in the currency's minor unit (e.g. pence). */
	public int $amount_minor;

	/** @var string ISO 4217 currency code, upper-case (e.g. "GBP"). */
	public string $currency;

	/** @var string Stable key that makes a retry a no-op rather than a second charge. */
	public string $idempotency_key;

	/** @var string The assessment/submission UUID this payment belongs to. */
	public string $submission_id;

	/** @var string|null Provider-side payment-method token from the client (e.g. a Stripe PaymentMethod id); null until the client tokenises a card. */
	public ?string $payment_method;

	/** @var string Human-readable description shown on the statement / dashboard. */
	public string $description;

	/** @var array Provider-neutral metadata (treatment, dose, order ref …) for audit and reconciliation. */
	public array $metadata;

	public function __construct(
		int $amount_minor,
		string $currency,
		string $idempotency_key,
		string $submission_id,
		?string $payment_method = null,
		string $description = '',
		array $metadata = []
	) {
		$this->amount_minor    = max( 0, $amount_minor );
		$this->currency        = strtoupper( $currency );
		$this->idempotency_key = $idempotency_key;
		$this->submission_id   = $submission_id;
		$this->payment_method  = $payment_method;
		$this->description     = $description;
		$this->metadata        = $metadata;
	}

	/**
	 * Build a request whose idempotency key is stable for a given
	 * (submission, treatment, dose, amount) — so retries collapse onto one hold,
	 * while a real change of treatment or price mints a fresh key (and a fresh
	 * hold), never silently reusing the old authorisation.
	 */
	public static function for_submission(
		string $submission_id,
		string $treatment,
		string $dose,
		int $amount_minor,
		string $currency = 'GBP',
		?string $payment_method = null,
		array $metadata = []
	): self {
		$fingerprint     = implode( '|', [ $submission_id, strtolower( $treatment ), strtolower( $dose ), $amount_minor, strtoupper( $currency ) ] );
		$idempotency_key = 'tcpay_' . hash( 'sha256', $fingerprint );

		$description = trim( sprintf( '%s %s', ucfirst( $treatment ), $dose ) );

		$metadata = array_merge(
			[
				'submission_id' => $submission_id,
				'treatment'     => $treatment,
				'dose'          => $dose,
			],
			$metadata
		);

		return new self( $amount_minor, $currency, $idempotency_key, $submission_id, $payment_method, $description, $metadata );
	}
}
