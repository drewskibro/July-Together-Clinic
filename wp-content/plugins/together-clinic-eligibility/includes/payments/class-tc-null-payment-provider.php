<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The fail-closed default. When no real provider is configured, the registry
 * hands back this one — every operation fails loudly rather than pretending to
 * take money or silently doing nothing. A patient sees "we can't take payment
 * right now", never a broken half-state, and the team is alerted. This is the
 * §1 rule from the engineering playbook applied to payments: an unavailable
 * payment path is an outage, not a reason to degrade.
 */
class TC_Null_Payment_Provider implements TC_Payment_Provider {

	public function id(): string {
		return 'none';
	}

	public function label(): string {
		return 'No payment provider configured';
	}

	public function is_configured(): bool {
		return false;
	}

	public function authorize( TC_Payment_Request $request ): TC_Payment_Result {
		return TC_Payment_Result::failed(
			'no_provider',
			'No payment provider is configured, so no payment can be taken.',
			$request->amount_minor,
			$request->currency
		);
	}

	public function capture( string $hold_ref, ?int $amount_minor = null ): TC_Payment_Result {
		return TC_Payment_Result::failed( 'no_provider', 'No payment provider is configured.', (int) $amount_minor, 'GBP' );
	}

	public function void( string $hold_ref ): TC_Payment_Result {
		return TC_Payment_Result::failed( 'no_provider', 'No payment provider is configured.', 0, 'GBP' );
	}

	public function retrieve( string $hold_ref ): TC_Payment_Result {
		return TC_Payment_Result::failed( 'no_provider', 'No payment provider is configured.', 0, 'GBP' );
	}
}
