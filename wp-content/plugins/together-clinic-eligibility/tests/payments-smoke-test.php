<?php
/**
 * Standalone proof of the provider-agnostic payment layer — no WordPress, no
 * Stripe, no database. Run from anywhere:
 *
 *     php wp-content/plugins/together-clinic-eligibility/tests/payments-smoke-test.php
 *
 * Exercises the full auth-then-capture contract against the sandbox provider
 * and the hold-ledger service, asserting the guarantees that make the flow
 * robust: idempotent authorise (no double charge), supersede-on-change (no
 * double hold), illegal-transition rejection, and a fail-closed registry.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/' ); // satisfy the plugin file guards
}
if ( ! defined( 'TC_PAYMENTS_ALLOW_FAKE' ) ) {
	define( 'TC_PAYMENTS_ALLOW_FAKE', true ); // opt the sandbox provider in
}

$base = __DIR__ . '/../includes/payments/';
require $base . 'class-tc-payment-request.php';
require $base . 'class-tc-payment-result.php';
require $base . 'interface-tc-payment-provider.php';
require $base . 'class-tc-null-payment-provider.php';
require $base . 'class-tc-fake-payment-provider.php';
require $base . 'class-tc-payment-providers.php';
require $base . 'class-tc-payment-hold.php';
require $base . 'interface-tc-hold-repository.php';
require $base . 'class-tc-array-hold-repository.php';
require $base . 'class-tc-payment-service.php';
require $base . 'class-tc-price-book.php';

$pass = 0;
$fail = 0;
function check( $label, $cond ) {
	global $pass, $fail;
	if ( $cond ) { $pass++; echo "  PASS  $label\n"; }
	else { $fail++; echo "  FAIL  $label\n"; }
}

$fake = new TC_Fake_Payment_Provider();

echo "\n— is_configured gate —\n";
check( 'sandbox is configured when opted in', $fake->is_configured() === true );

echo "\n— authorise → capture (happy path) —\n";
$req  = TC_Payment_Request::for_submission( 'sub-AAA', 'mounjaro', '2.5mg', 15900, 'GBP' );
$auth = $fake->authorize( $req );
check( 'authorise returns AUTHORIZED', $auth->is_authorized() );
check( 'authorise returns a hold reference', ! empty( $auth->hold_ref ) );
check( 'authorise holds the right amount (15900 pence)', $auth->amount_minor === 15900 );

$auth2 = $fake->authorize( $req );
check( 'idempotent authorise returns the SAME hold (no double charge)', $auth2->hold_ref === $auth->hold_ref );

$cap = $fake->capture( $auth->hold_ref );
check( 'capture returns CAPTURED', $cap->is_captured() );
$cap2 = $fake->capture( $auth->hold_ref );
check( 'idempotent capture stays CAPTURED', $cap2->is_captured() );

$badVoid = $fake->void( $auth->hold_ref );
check( 'voiding a captured hold FAILS (illegal transition)', $badVoid->is_failed() && $badVoid->error_code === 'invalid_state' );

echo "\n— authorise → void (rejection path) —\n";
$req2  = TC_Payment_Request::for_submission( 'sub-BBB', 'wegovy', '0.25mg', 10900, 'GBP' );
$auth3 = $fake->authorize( $req2 );
$void  = $fake->void( $auth3->hold_ref );
check( 'void returns VOIDED', $void->is_voided() );
$void2 = $fake->void( $auth3->hold_ref );
check( 'idempotent void stays VOIDED', $void2->is_voided() );
$badCap = $fake->capture( $auth3->hold_ref );
check( 'capturing a voided hold FAILS', $badCap->is_failed() );

echo "\n— different treatment mints a different hold —\n";
$reqMj = TC_Payment_Request::for_submission( 'sub-CCC', 'mounjaro', '2.5mg', 15900, 'GBP' );
$reqWg = TC_Payment_Request::for_submission( 'sub-CCC', 'wegovy', '0.25mg', 10900, 'GBP' );
check( 'same submission + different treatment → different idempotency key', $reqMj->idempotency_key !== $reqWg->idempotency_key );

echo "\n— forced decline (fail-closed UX hook) —\n";
$reqDecline = TC_Payment_Request::for_submission( 'sub-DDD', 'wegovy', '0.25mg', 10900, 'GBP', null, [ 'test_behavior' => 'decline' ] );
$declined   = $fake->authorize( $reqDecline );
check( 'declined authorise returns FAILED', $declined->is_failed() && $declined->error_code === 'card_declined' );

echo "\n— registry fails closed —\n";
TC_Payment_Providers::bootstrap();
check( 'sandbox is registered', TC_Payment_Providers::get( 'fake' ) instanceof TC_Fake_Payment_Provider );
check( 'no provider configured → active() is the Null provider', TC_Payment_Providers::active() instanceof TC_Null_Payment_Provider );
check( 'has_active() is false when nothing is configured', TC_Payment_Providers::has_active() === false );
$nullAuth = TC_Payment_Providers::active()->authorize( $req );
check( 'Null provider fails loudly (no_provider), never silently succeeds', $nullAuth->is_failed() && $nullAuth->error_code === 'no_provider' );

echo "\n— payment service: authorise + persist —\n";
$repo = new TC_Array_Hold_Repository();
$svc  = new TC_Payment_Service( $fake, $repo );
$active_count = function ( $repo, $sub ) {
	$n = 0;
	foreach ( $repo->all_for_submission( $sub ) as $h ) {
		if ( $h->is_active() ) { $n++; }
	}
	return $n;
};

$reqWg2  = TC_Payment_Request::for_submission( 'sub-svc-1', 'wegovy', '0.25mg', 10900, 'GBP' );
$svcAuth = $svc->authorize_for_submission( 'sub-svc-1', $reqWg2 );
check( 'service authorise returns AUTHORIZED', $svcAuth->is_authorized() );
$activeHold = $svc->active_hold( 'sub-svc-1' );
check( 'a hold record is persisted and active', $activeHold instanceof TC_Payment_Hold && $activeHold->is_active() );
check( 'persisted hold references the same authorisation', $activeHold && $activeHold->hold_ref === $svcAuth->hold_ref );
check( 'persisted hold carries the right amount', $activeHold && $activeHold->amount_minor === 10900 );

echo "\n— payment service: idempotent (no double charge) —\n";
$svc->authorize_for_submission( 'sub-svc-1', $reqWg2 );
check( 'retried authorise does NOT create a second hold', count( $repo->all_for_submission( 'sub-svc-1' ) ) === 1 );

echo "\n— payment service: change treatment supersedes old hold (no double hold) —\n";
$reqMj2   = TC_Payment_Request::for_submission( 'sub-svc-1', 'mounjaro', '2.5mg', 15900, 'GBP' );
$svcAuth2 = $svc->authorize_for_submission( 'sub-svc-1', $reqMj2 );
check( 'switching treatment authorises a NEW hold', $svcAuth2->is_authorized() && $svcAuth2->hold_ref !== $svcAuth->hold_ref );
$activeAfterSwitch = $svc->active_hold( 'sub-svc-1' );
check( 'the new treatment is now the active hold', $activeAfterSwitch && $activeAfterSwitch->hold_ref === $svcAuth2->hold_ref );
check( 'the active hold is the new amount (15900)', $activeAfterSwitch && $activeAfterSwitch->amount_minor === 15900 );
$oldHold = $repo->find_by_ref( $svcAuth->hold_ref );
check( 'the old hold is marked SUPERSEDED (released, not left live)', $oldHold && $oldHold->status === TC_Payment_Hold::STATUS_SUPERSEDED );
check( 'exactly ONE live hold remains on the submission', $active_count( $repo, 'sub-svc-1' ) === 1 );

echo "\n— payment service: capture (prescriber approval) —\n";
$svcCap = $svc->capture_for_submission( 'sub-svc-1' );
check( 'capture returns CAPTURED', $svcCap->is_captured() );
check( 'no hold is "active" once captured', $svc->active_hold( 'sub-svc-1' ) === null );
check( 'captured hold is recorded as captured', $repo->find_by_ref( $svcAuth2->hold_ref )->status === TC_Payment_Hold::STATUS_CAPTURED );

echo "\n— payment service: void (prescriber rejection) —\n";
$reqV = TC_Payment_Request::for_submission( 'sub-svc-2', 'wegovy', '0.25mg', 10900, 'GBP' );
$svc->authorize_for_submission( 'sub-svc-2', $reqV );
$svcVoid = $svc->void_for_submission( 'sub-svc-2' );
check( 'void returns VOIDED', $svcVoid->is_voided() );
check( 'no active hold remains after void', $svc->active_hold( 'sub-svc-2' ) === null );

echo "\n— payment service: capture with nothing to capture —\n";
$svcNone = $svc->capture_for_submission( 'sub-does-not-exist' );
check( 'capturing a submission with no hold FAILS cleanly (no_active_hold)', $svcNone->is_failed() && $svcNone->error_code === 'no_active_hold' );

echo "\n— price book: lookups —\n";
check( 'Wegovy 0.25mg is £109 (10900p)', TC_Price_Book::price_minor( 'wegovy', '0.25mg' ) === 10900 );
check( 'Mounjaro 2.5mg is £159 (15900p)', TC_Price_Book::price_minor( 'mounjaro', '2.5mg' ) === 15900 );
check( 'Wegovy Tablets 25mg is £189 (18900p)', TC_Price_Book::price_minor( 'wegovy-tablets', '25mg' ) === 18900 );
check( 'currency is GBP', TC_Price_Book::currency() === 'GBP' );

echo "\n— price book: normalisation —\n";
check( 'case/space-insensitive ("Wegovy" / "0.25 MG")', TC_Price_Book::price_minor( 'Wegovy', '0.25 MG' ) === 10900 );

echo "\n— price book: fail-closed on an unpriced combination —\n";
check( 'an unpriced dose returns null (never a guessed price)', TC_Price_Book::price_minor( 'wegovy', '1mg' ) === null );
check( 'has_price is false for the unpriced dose', TC_Price_Book::has_price( 'wegovy', '1mg' ) === false );

echo "\n— price book → payment request bridge —\n";
$pbReq = TC_Price_Book::request_for( 'sub-price-1', 'mounjaro', '2.5mg' );
check( 'request_for builds a priced request', $pbReq instanceof TC_Payment_Request && $pbReq->amount_minor === 15900 && $pbReq->currency === 'GBP' );
check( 'the request carries an idempotency key', $pbReq && ! empty( $pbReq->idempotency_key ) );
$pbReqNone = TC_Price_Book::request_for( 'sub-price-1', 'wegovy', '1mg' );
check( 'request_for returns null when unpriced (fail-closed, no £0 charge)', $pbReqNone === null );

echo "\n— price book: completeness check —\n";
$missing = TC_Price_Book::missing_for( [ 'wegovy' => [ '0.25mg', '1mg' ] ] );
check( 'missing_for flags the unpriced rung only', $missing === [ 'wegovy 1mg' ] );

echo "\n========================================\n";
echo "  $pass passed, $fail failed\n";
echo "========================================\n";
exit( $fail === 0 ? 0 : 1 );
