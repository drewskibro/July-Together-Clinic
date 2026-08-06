<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The single source of truth for what a treatment + dose costs.
 *
 * This is what retires the "no product configured" class of failure. In the old
 * flow, price lived on a WooCommerce product that had to be created and mapped
 * to a (treatment, dose) by hand — and the moment a mapping was missing, the
 * whole checkout dead-ended. Here price is plain config we own end-to-end:
 * complete by construction, versioned in code, overridable per-site, and — this
 * is the important part — **fail-closed**. An unpriced combination returns null,
 * never a guessed or zero price, so the caller shows "we can't price this right
 * now" instead of silently charging the wrong amount or £0.
 *
 * Amounts are in the currency's minor unit (pence). The built-in defaults carry
 * the initial-authorisation (starting-dose) prices for each treatment; a site
 * can extend or override the whole book via the `tc_price_book` option/filter
 * (e.g. a one-time sync from the current catalogue), and completeness against
 * the dose ladder can be checked with missing_for().
 *
 * Deliberately self-contained: no WordPress and no WooCommerce dependency, so it
 * is fully unit-testable and can never be broken by a missing product.
 */
class TC_Price_Book {

	const OPTION_KEY = 'tc_price_book';
	const CURRENCY   = 'GBP';

	/** Built-in seed: the starting-dose price for each treatment (minor units). */
	public static function defaults(): array {
		return [
			'wegovy'         => [ '0.25mg' => 10900 ],                                      // £109 starter
			'mounjaro'       => [ '2.5mg' => 15900 ],                                       // £159 starter
			'wegovy-tablets' => [ '1.5mg' => 9900, '4mg' => 11900, '9mg' => 12900, '25mg' => 18900 ], // £99 / £119 / £129 / £189
		];
	}

	/** The effective price book: defaults, extended by any saved override, then filtered. */
	public static function all(): array {
		$book = self::defaults();

		if ( function_exists( 'get_option' ) ) {
			$saved = get_option( self::OPTION_KEY, [] );
			if ( is_array( $saved ) ) {
				$book = self::merge( $book, $saved );
			}
		}

		if ( function_exists( 'apply_filters' ) ) {
			$book = apply_filters( 'tc_price_book', $book );
		}

		return is_array( $book ) ? $book : self::defaults();
	}

	/**
	 * Price for a treatment + dose in minor units, or null when it is not
	 * configured. Null is the fail-closed signal — never substitute a default.
	 */
	public static function price_minor( $treatment, $dose ): ?int {
		$treatment = self::norm_treatment( $treatment );
		$dose      = self::norm_dose( $dose );
		$book      = self::all();

		if ( isset( $book[ $treatment ][ $dose ] ) && is_numeric( $book[ $treatment ][ $dose ] ) ) {
			$price = (int) $book[ $treatment ][ $dose ];
			return $price > 0 ? $price : null;
		}
		return null;
	}

	public static function has_price( $treatment, $dose ): bool {
		return null !== self::price_minor( $treatment, $dose );
	}

	public static function currency(): string {
		$currency = self::CURRENCY;
		if ( function_exists( 'apply_filters' ) ) {
			$currency = (string) apply_filters( 'tc_price_book_currency', $currency );
		}
		return strtoupper( $currency );
	}

	/**
	 * Build the payment request for a submission's chosen treatment + dose,
	 * priced from the book. Returns null when the price is not configured, so
	 * the caller fails closed (offer to follow up) rather than charging a wrong
	 * or zero amount.
	 */
	public static function request_for( string $submission_id, string $treatment, string $dose, ?string $payment_method = null, array $metadata = [] ): ?TC_Payment_Request {
		$amount = self::price_minor( $treatment, $dose );
		if ( null === $amount ) {
			return null;
		}
		return TC_Payment_Request::for_submission(
			$submission_id,
			self::norm_treatment( $treatment ),
			self::norm_dose( $dose ),
			$amount,
			self::currency(),
			$payment_method,
			$metadata
		);
	}

	/**
	 * Which required (treatment, dose) combinations have no price yet — feed it
	 * the dose ladder (e.g. TC_Dose_Ladder::LADDERS) to surface gaps before they
	 * can strand a patient. Empty array = the book fully covers what was asked.
	 *
	 * @param array $required [ treatment => [ dose, dose, … ] ]
	 * @return string[] human-readable "treatment dose" labels that are unpriced
	 */
	public static function missing_for( array $required ): array {
		$missing = [];
		foreach ( $required as $treatment => $doses ) {
			if ( ! is_array( $doses ) ) {
				continue;
			}
			foreach ( $doses as $dose ) {
				if ( ! self::has_price( $treatment, $dose ) ) {
					$missing[] = self::norm_treatment( $treatment ) . ' ' . self::norm_dose( $dose );
				}
			}
		}
		return $missing;
	}

	/** Merge a saved/override book onto the base, normalising its keys. */
	private static function merge( array $base, array $override ): array {
		foreach ( $override as $treatment => $doses ) {
			if ( ! is_array( $doses ) ) {
				continue;
			}
			$treatment = self::norm_treatment( $treatment );
			foreach ( $doses as $dose => $price ) {
				if ( ! is_numeric( $price ) ) {
					continue;
				}
				$dose = self::norm_dose( $dose );
				if ( '' !== $dose ) {
					$base[ $treatment ][ $dose ] = (int) $price;
				}
			}
		}
		return $base;
	}

	private static function norm_treatment( $treatment ): string {
		return strtolower( trim( (string) $treatment ) );
	}

	/** Mirrors the dose ladder's normalisation so keys line up: "0.25 MG" → "0.25mg". */
	private static function norm_dose( $dose ): string {
		$dose = strtolower( trim( (string) $dose ) );
		$dose = str_replace( [ ' ', 'milligrams', 'milligram' ], '', $dose );
		$dose = preg_replace( '/[^0-9\.mg]/', '', $dose );
		if ( '' !== $dose && false === strpos( $dose, 'mg' ) ) {
			$dose .= 'mg';
		}
		return $dose;
	}
}
