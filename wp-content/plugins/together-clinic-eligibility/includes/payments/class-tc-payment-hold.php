<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The internal ledger row for one authorisation — the durable link between a
 * patient's submission and the money held (or taken, or released) for it.
 *
 * This is what makes the flow recoverable: the provider (Stripe, a sandbox,
 * whatever) holds the funds, but WE own the record of which submission that hold
 * belongs to and what state it's in. If any step downstream hiccups, the truth
 * is here, keyed by an opaque hold reference — never derived from patient data.
 *
 * One submission has at most one ACTIVE (authorized) hold at a time; changing
 * treatment supersedes the old hold rather than stacking a second charge.
 */
class TC_Payment_Hold {

	/** Awaiting client action (SCA/3-D Secure) before the hold is live. */
	const STATUS_PENDING = 'pending';

	/** Funds held, not taken — the prescriber-review window. The only "active" state. */
	const STATUS_AUTHORIZED = 'authorized';

	/** Held funds taken (prescriber approved). */
	const STATUS_CAPTURED = 'captured';

	/** Hold released (prescriber rejected / patient cancelled). */
	const STATUS_VOIDED = 'voided';

	/** Replaced by a newer hold for the same submission (e.g. treatment changed). */
	const STATUS_SUPERSEDED = 'superseded';

	/** Authorisation lapsed before capture (holds expire, typically ~7 days). */
	const STATUS_EXPIRED = 'expired';

	/** The authorise attempt did not succeed. */
	const STATUS_FAILED = 'failed';

	public ?int $id;
	public string $submission_id;
	public string $provider_id;
	public string $hold_ref;
	public string $idempotency_key;
	public string $status;
	public int $amount_minor;
	public string $currency;
	public array $metadata;
	public int $created_at;
	public int $updated_at;

	public function __construct(
		string $submission_id,
		string $provider_id,
		string $hold_ref,
		string $idempotency_key,
		string $status,
		int $amount_minor,
		string $currency,
		array $metadata = [],
		?int $id = null,
		?int $created_at = null,
		?int $updated_at = null
	) {
		$now                   = $created_at ?? time();
		$this->id              = $id;
		$this->submission_id   = $submission_id;
		$this->provider_id     = $provider_id;
		$this->hold_ref        = $hold_ref;
		$this->idempotency_key = $idempotency_key;
		$this->status          = $status;
		$this->amount_minor    = max( 0, $amount_minor );
		$this->currency        = strtoupper( $currency );
		$this->metadata        = $metadata;
		$this->created_at      = $now;
		$this->updated_at      = $updated_at ?? $now;
	}

	/** Build a hold from an authorise result. */
	public static function from_result( string $submission_id, string $provider_id, string $idempotency_key, TC_Payment_Result $result, array $metadata = [] ): self {
		return new self(
			$submission_id,
			$provider_id,
			(string) $result->hold_ref,
			$idempotency_key,
			self::map_status( $result->status ),
			$result->amount_minor,
			$result->currency,
			$metadata
		);
	}

	/** Map a TC_Payment_Result status onto a ledger status. */
	public static function map_status( string $result_status ): string {
		switch ( $result_status ) {
			case TC_Payment_Result::AUTHORIZED:
				return self::STATUS_AUTHORIZED;
			case TC_Payment_Result::REQUIRES_ACTION:
				return self::STATUS_PENDING;
			case TC_Payment_Result::CAPTURED:
				return self::STATUS_CAPTURED;
			case TC_Payment_Result::VOIDED:
				return self::STATUS_VOIDED;
			default:
				return self::STATUS_FAILED;
		}
	}

	/** A live hold — funds reserved, awaiting the prescriber's decision. */
	public function is_active(): bool {
		return self::STATUS_AUTHORIZED === $this->status;
	}

	public function set_status( string $status ): void {
		$this->status     = $status;
		$this->updated_at = time();
	}

	/** Flat row for persistence. */
	public function to_row(): array {
		return [
			'id'              => $this->id,
			'submission_id'   => $this->submission_id,
			'provider_id'     => $this->provider_id,
			'hold_ref'        => $this->hold_ref,
			'idempotency_key' => $this->idempotency_key,
			'status'          => $this->status,
			'amount_minor'    => $this->amount_minor,
			'currency'        => $this->currency,
			'metadata'        => wp_json_encode( $this->metadata ),
			'created_at'      => $this->created_at,
			'updated_at'      => $this->updated_at,
		];
	}

	/** Rebuild from a persisted row. */
	public static function from_row( array $row ): self {
		$metadata = [];
		if ( isset( $row['metadata'] ) && is_string( $row['metadata'] ) && '' !== $row['metadata'] ) {
			$decoded  = json_decode( $row['metadata'], true );
			$metadata = is_array( $decoded ) ? $decoded : [];
		}
		return new self(
			(string) ( $row['submission_id'] ?? '' ),
			(string) ( $row['provider_id'] ?? '' ),
			(string) ( $row['hold_ref'] ?? '' ),
			(string) ( $row['idempotency_key'] ?? '' ),
			(string) ( $row['status'] ?? self::STATUS_FAILED ),
			(int) ( $row['amount_minor'] ?? 0 ),
			(string) ( $row['currency'] ?? 'GBP' ),
			$metadata,
			isset( $row['id'] ) ? (int) $row['id'] : null,
			isset( $row['created_at'] ) ? (int) $row['created_at'] : null,
			isset( $row['updated_at'] ) ? (int) $row['updated_at'] : null
		);
	}
}
