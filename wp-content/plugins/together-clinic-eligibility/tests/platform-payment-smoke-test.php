<?php
/**
 * Standalone proof of TC_Platform_Sync's payment message
 * (`POST /v1/website-orders/payment`) — no WordPress, no WooCommerce, no
 * Stripe, no network. Run from anywhere:
 *
 *     php wp-content/plugins/together-clinic-eligibility/tests/platform-payment-smoke-test.php
 *
 * WordPress, WooCommerce and the HTTP layer are replaced by the small stubs
 * below, which record every outbound request, scheduled cron event, order
 * note and log line, so the test can assert exactly what would leave the
 * site and when:
 *
 *  - the payload shape and Idempotency-Key match the contract;
 *  - a captured charge sends; a mere authorisation never does;
 *  - an order never sent to the platform (no patient id), or not a review
 *    order at all, is skipped with a debug log and no request;
 *  - once acknowledged, a re-capture or manual resend sends nothing;
 *  - 2xx / 4xx / 5xx / no-response are classified per the contract, with
 *    retries on the same three-attempt WP-Cron schedule as the patient push;
 *  - logs carry ids and status codes only, never order content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/' ); // satisfy the plugin file guards
}

// ---------------------------------------------------------------------------
// Stubs: just enough WordPress / WooCommerce for the code under test.
// ---------------------------------------------------------------------------

$GLOBALS['tc_test'] = [
	'options'   => [],
	'orders'    => [],
	'requests'  => [],
	'responses' => [],
	'scheduled' => [],
	'logs'      => [],
];

class WP_Error {
	private $code; private $message; private $data;
	public function __construct( $code = '', $message = '', $data = '' ) {
		$this->code = $code; $this->message = $message; $this->data = $data;
	}
	public function get_error_message() { return $this->message; }
	public function get_error_data() { return $this->data; }
}

class WC_Order {
	public $id; public $meta = []; public $notes = []; public $transaction_id = '';
	public $total = '0.00'; public $refunded = 0; public $currency = 'GBP';
	public function __construct( $id ) { $this->id = $id; }
	public function get_id() { return $this->id; }
	public function get_meta( $key ) { return $this->meta[ $key ] ?? ''; }
	public function update_meta_data( $key, $value ) { $this->meta[ $key ] = $value; }
	public function delete_meta_data( $key ) { unset( $this->meta[ $key ] ); }
	public function save() { return $this->id; }
	public function add_order_note( $note ) { $this->notes[] = $note; }
	public function get_transaction_id() { return $this->transaction_id; }
	public function get_total() { return $this->total; }
	public function get_total_refunded() { return $this->refunded; }
	public function get_currency() { return $this->currency; }
}

class TC_Log {
	public static function debug( $m, $c = [] ) { $GLOBALS['tc_test']['logs'][] = [ 'debug', $m, $c ]; }
	public static function info( $m, $c = [] )  { $GLOBALS['tc_test']['logs'][] = [ 'info', $m, $c ]; }
	public static function warn( $m, $c = [] )  { $GLOBALS['tc_test']['logs'][] = [ 'warn', $m, $c ]; }
	public static function error( $m, $c = [] ) { $GLOBALS['tc_test']['logs'][] = [ 'error', $m, $c ]; }
}

function get_option( $key, $default = false ) {
	return array_key_exists( $key, $GLOBALS['tc_test']['options'] ) ? $GLOBALS['tc_test']['options'][ $key ] : $default;
}
function untrailingslashit( $s ) { return rtrim( $s, '/\\' ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function absint( $v ) { return abs( (int) $v ); }
function wc_get_order( $id ) { return $GLOBALS['tc_test']['orders'][ (int) $id ] ?? false; }
function wp_schedule_single_event( $ts, $hook, $args = [] ) {
	$GLOBALS['tc_test']['scheduled'][] = [ 'delay' => $ts - time(), 'hook' => $hook, 'args' => $args ];
	return true;
}
function wp_remote_request( $url, $args ) {
	$GLOBALS['tc_test']['requests'][] = [ 'url' => $url, 'args' => $args ];
	$next = array_shift( $GLOBALS['tc_test']['responses'] );
	if ( $next instanceof WP_Error ) {
		return $next;
	}
	return [ 'code' => $next ?? 200, 'body' => '{"status":"recorded"}' ];
}
function wp_remote_retrieve_response_code( $r ) { return $r['code']; }
function wp_remote_retrieve_body( $r ) { return $r['body']; }

require __DIR__ . '/../includes/class-tc-review-status.php';
require __DIR__ . '/../includes/class-tc-review-payment.php';
require __DIR__ . '/../includes/class-tc-platform-sync.php';

$pass = 0;
$fail = 0;
function check( $label, $cond ) {
	global $pass, $fail;
	if ( $cond ) { $pass++; echo "  PASS  $label\n"; }
	else { $fail++; echo "  FAIL  $label\n"; }
}

/** Fresh state between scenarios: config on, no requests/cron/logs, per-request dedupe cleared. */
function reset_world( $enabled = true ) {
	$GLOBALS['tc_test']['options'] = $enabled ? [
		'tc_platform_sync_enabled' => '1',
		'tc_platform_base_url'     => 'https://prescribing-api.example.test/',
		'tc_platform_api_key'      => 'tk_test_harness_only',
	] : [];
	$GLOBALS['tc_test']['orders']    = [];
	$GLOBALS['tc_test']['requests']  = [];
	$GLOBALS['tc_test']['responses'] = [];
	$GLOBALS['tc_test']['scheduled'] = [];
	$GLOBALS['tc_test']['logs']      = [];
	foreach ( [ 'payment_handled' => [], 'in_platform_webhook' => false ] as $prop => $value ) {
		$p = new ReflectionProperty( 'TC_Platform_Sync', $prop );
		if ( PHP_VERSION_ID < 80100 ) { $p->setAccessible( true ); }
		$p->setValue( null, $value );
	}
}

/**
 * A review-lane order as the real flow leaves it.
 *  $captured: 'yes' (captured), 'no' (authorised only) or '' (no Stripe charge).
 *  $synced:   whether PR #68's patient push stored a platform patient id.
 */
function make_order( $id, $captured = 'yes', $synced = true, $review = true ) {
	$o = new WC_Order( $id );
	if ( $review ) {
		$o->meta['_tc_eligibility_raw'] = '{"selectedTreatment":"x"}';
	}
	if ( $synced ) {
		$o->meta[ TC_Platform_Sync::META_PATIENT_ID ] = 'pat_123';
	}
	if ( '' !== $captured ) {
		$o->meta['_stripe_charge_captured'] = $captured;
		$o->transaction_id                  = 'ch_3Test123';
	}
	$o->total = '199.99';
	$GLOBALS['tc_test']['orders'][ $id ] = $o;
	return $o;
}

function requests() { return $GLOBALS['tc_test']['requests']; }
function scheduled() { return $GLOBALS['tc_test']['scheduled']; }
function logs_named( $name ) {
	return array_values( array_filter( $GLOBALS['tc_test']['logs'], function ( $l ) use ( $name ) { return $l[1] === $name; } ) );
}

// ---------------------------------------------------------------------------
echo "\n— payload shape (the contract, exactly) —\n";
$body = TC_Platform_Sync::payment_request_body( 501, '2026-10-06T09:15:00Z', 19999, 'ch_3Test123' );
check( 'exactly the six contract keys, in contract order',
	array_keys( $body ) === [ 'externalReference', 'status', 'paidAt', 'amountPence', 'currency', 'paymentReference' ] );
check( 'externalReference uses the PR #68 namespacing (tc-order-<id>)', $body['externalReference'] === 'tc-order-501' );
check( 'status is PAID', $body['status'] === 'PAID' );
check( 'paidAt is passed through as ISO-8601 UTC', $body['paidAt'] === '2026-10-06T09:15:00Z' );
check( 'amountPence is an integer, not a float or string', $body['amountPence'] === 19999 );
check( 'currency is GBP', $body['currency'] === 'GBP' );
check( 'paymentReference is the Stripe reference', $body['paymentReference'] === 'ch_3Test123' );
check( 'JSON encodes amountPence as a bare integer',
	false !== strpos( json_encode( $body ), '"amountPence":19999,' ) );
check( '£199.99 is 19999 pence (rounded, not truncated)', TC_Platform_Sync::to_pence( 199.99 ) === 19999 );
check( '£19.99 is 1999 pence (the classic float trap)', TC_Platform_Sync::to_pence( 19.99 ) === 1999 );
check( '£149 is 14900 pence', TC_Platform_Sync::to_pence( '149.00' ) === 14900 );

echo "\n— idempotency key —\n";
check( 'tc-paid-<order id>-<paymentReference>',
	TC_Platform_Sync::payment_idempotency_key( 501, 'ch_3Test123' ) === 'tc-paid-501-ch_3Test123' );
check( 'works with a PaymentIntent reference too',
	TC_Platform_Sync::payment_idempotency_key( 7, 'pi_3Abc' ) === 'tc-paid-7-pi_3Abc' );

echo "\n— retry classification by status code —\n";
check( '200 is ok (recorded or already recorded)', TC_Platform_Sync::classify_payment_status( 200 ) === 'ok' );
check( '201 is ok', TC_Platform_Sync::classify_payment_status( 201 ) === 'ok' );
check( '404 (unknown order) is permanent', TC_Platform_Sync::classify_payment_status( 404 ) === 'permanent' );
check( '409 (cancelled/declined) is permanent', TC_Platform_Sync::classify_payment_status( 409 ) === 'permanent' );
check( '400 is permanent', TC_Platform_Sync::classify_payment_status( 400 ) === 'permanent' );
check( '401 is permanent', TC_Platform_Sync::classify_payment_status( 401 ) === 'permanent' );
check( '422 is permanent', TC_Platform_Sync::classify_payment_status( 422 ) === 'permanent' );
check( '500 retries', TC_Platform_Sync::classify_payment_status( 500 ) === 'retry' );
check( '503 retries', TC_Platform_Sync::classify_payment_status( 503 ) === 'retry' );
check( 'no response at all (timeout, DNS, TLS) retries', TC_Platform_Sync::classify_payment_status( null ) === 'retry' );

echo "\n— skip rules —\n";
reset_world();
check( 'captured + synced review order: due', TC_Platform_Sync::payment_skip_reason( make_order( 1 ) ) === null );
check( 'authorised only (_stripe_charge_captured = no): not_captured',
	TC_Platform_Sync::payment_skip_reason( make_order( 2, 'no' ) ) === 'not_captured' );
check( 'no Stripe flag at all: not_captured (fail closed)',
	TC_Platform_Sync::payment_skip_reason( make_order( 3, '' ) ) === 'not_captured' );
check( 'never sent to the platform (no patient id): not_synced',
	TC_Platform_Sync::payment_skip_reason( make_order( 4, 'yes', false ) ) === 'not_synced' );
check( 'not a review order at all: not_review_order',
	TC_Platform_Sync::payment_skip_reason( make_order( 5, 'yes', true, false ) ) === 'not_review_order' );
$acked = make_order( 6 );
$acked->meta[ TC_Platform_Sync::META_PAID_SENT_AT ] = 1759740000;
check( 'already acknowledged: already_sent', TC_Platform_Sync::payment_skip_reason( $acked ) === 'already_sent' );
$noref = make_order( 7 );
$noref->transaction_id = '';
check( 'captured but no charge or intent id: no_payment_reference',
	TC_Platform_Sync::payment_skip_reason( $noref ) === 'no_payment_reference' );
$intent_only = make_order( 8 );
$intent_only->transaction_id = '';
$intent_only->meta['_stripe_intent_id'] = 'pi_3Abc';
check( 'falls back to the PaymentIntent id when no charge id is stored',
	TC_Platform_Sync::payment_skip_reason( $intent_only ) === null );

echo "\n— capture vs authorise, end to end —\n";
reset_world();
make_order( 20, 'no' );
TC_Platform_Sync::on_stripe_manual_capture( wc_get_order( 20 ), (object) [ 'id' => 'ch_x', 'captured' => false ] );
TC_Platform_Sync::on_payment_complete( 20 );
TC_Platform_Sync::on_paid_status( 20 );
check( 'an authorisation seen by every hook sends nothing', count( requests() ) === 0 );
check( '...and schedules nothing', count( scheduled() ) === 0 );

$o = wc_get_order( 20 );
$o->meta['_stripe_charge_captured'] = 'yes'; // the same request then captures
TC_Platform_Sync::on_stripe_manual_capture( $o, (object) [ 'id' => 'ch_3Test123', 'amount_captured' => 19999 ] );
check( 'the capture that follows in the same request IS sent (skip did not mark it handled)', count( requests() ) === 1 );

reset_world();
make_order( 21 );
TC_Platform_Sync::on_stripe_manual_capture( wc_get_order( 21 ), (object) [ 'id' => 'ch_3Test123', 'amount_captured' => 15000 ] );
TC_Platform_Sync::on_payment_complete( 21 );
TC_Platform_Sync::on_paid_status( 21 );
$r = requests();
check( 'one capture tripping all three hooks sends exactly once', count( $r ) === 1 );
check( 'POSTs to {base}/v1/website-orders/payment (trailing slash trimmed)',
	$r[0]['url'] === 'https://prescribing-api.example.test/v1/website-orders/payment' );
check( 'method is POST', $r[0]['args']['method'] === 'POST' );
check( 'same Bearer API key header as /v1/patients', $r[0]['args']['headers']['Authorization'] === 'Bearer tk_test_harness_only' );
check( 'Idempotency-Key header is tc-paid-<id>-<ref>', $r[0]['args']['headers']['Idempotency-Key'] === 'tc-paid-21-ch_3Test123' );
$sent = json_decode( $r[0]['args']['body'], true );
check( 'amountPence is what Stripe says it captured (amount_captured), not recomputed', $sent['amountPence'] === 15000 );
check( 'paidAt is ISO-8601 UTC with a Z', 1 === preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $sent['paidAt'] ) );
check( 'the body carries nothing beyond the six contract keys', count( $sent ) === 6 );
$o = wc_get_order( 21 );
check( 'acknowledgement flag stored', (bool) $o->get_meta( TC_Platform_Sync::META_PAID_SENT_AT ) );
check( 'order note records it with amount and reference only',
	end( $o->notes ) === 'Payment recorded on the prescribing platform (£150.00, reference ch_3Test123).' );

echo "\n— idempotency: no double send —\n";
reset_world();
make_order( 22 );
TC_Platform_Sync::on_payment_complete( 22 );
check( 'first capture sends', count( requests() ) === 1 );
// A later request: per-request dedupe gone, only the stored flag remains.
$keep = $GLOBALS['tc_test']['orders'];
reset_world();
$GLOBALS['tc_test']['orders'] = $keep;
TC_Platform_Sync::on_payment_complete( 22 );        // a re-capture / webhook replay in a later request
TC_Platform_Sync::on_paid_status( 22 );             // e.g. processing -> completed later
TC_Platform_Sync::run_payment_retry( 22 );          // a stale retry event
TC_Platform_Sync::manual_payment( wc_get_order( 22 ) ); // a staff resend
check( 'once acknowledged, nothing is ever sent again', count( requests() ) === 0 );
check( 'the manual resend says so in a note',
	end( wc_get_order( 22 )->notes ) === 'Payment is already recorded on the prescribing platform; nothing was sent.' );
check( 'the manual action is no longer offered',
	! isset( TC_Platform_Sync::add_manual_payment_action( [], wc_get_order( 22 ) )['tc_platform_manual_payment'] ) );

echo "\n— never sent to the platform: skipped silently with a debug log —\n";
reset_world();
make_order( 23, 'yes', false );
TC_Platform_Sync::on_payment_complete( 23 );
check( 'no request', count( requests() ) === 0 );
check( 'no order note', count( wc_get_order( 23 )->notes ) === 0 );
$skips = logs_named( 'platform_payment_skipped' );
check( 'one debug-level skip log with the reason', count( $skips ) === 1 && $skips[0][0] === 'debug' && $skips[0][2]['reason'] === 'not_synced' );
check( 'the manual action is not offered', TC_Platform_Sync::add_manual_payment_action( [], wc_get_order( 23 ) ) === [] );

echo "\n— fail closed when disabled or unconfigured —\n";
reset_world( false );
make_order( 24 );
TC_Platform_Sync::on_payment_complete( 24 );
check( 'nothing sent while the platform is not configured', count( requests() ) === 0 && count( scheduled() ) === 0 );
check( 'no manual action offered while disabled', TC_Platform_Sync::add_manual_payment_action( [], wc_get_order( 24 ) ) === [] );

echo "\n— inside the platform's own webhook: deferred, not sent inline —\n";
reset_world();
make_order( 25 );
$flag = new ReflectionProperty( 'TC_Platform_Sync', 'in_platform_webhook' );
if ( PHP_VERSION_ID < 80100 ) { $flag->setAccessible( true ); }
$flag->setValue( null, true );
TC_Platform_Sync::on_stripe_manual_capture( wc_get_order( 25 ), (object) [ 'id' => 'ch_3Test123', 'amount_captured' => 19999 ] );
$flag->setValue( null, false );
check( 'no request made from inside the webhook delivery', count( requests() ) === 0 );
$s = scheduled();
check( 'an immediate WP-Cron send is scheduled instead',
	count( $s ) === 1 && $s[0]['hook'] === TC_Platform_Sync::PAYMENT_HOOK && $s[0]['args'] === [ 25 ] && $s[0]['delay'] <= 0 );
check( 'the captured amount is frozen at capture time for that later send',
	(int) wc_get_order( 25 )->get_meta( TC_Platform_Sync::META_PAID_AMOUNT_PENCE ) === 19999 );
TC_Platform_Sync::run_payment_retry( 25 );
check( 'the cron run then sends it', count( requests() ) === 1 );

echo "\n— 5xx / timeout: three attempts on the PR #68 schedule —\n";
reset_world();
make_order( 26 );
$GLOBALS['tc_test']['responses'] = [ 503, new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ), 500 ];
TC_Platform_Sync::on_payment_complete( 26 );
$s = scheduled();
check( 'attempt 1 (503) schedules a retry in 5 minutes', count( $s ) === 1 && $s[0]['delay'] === 300 && $s[0]['hook'] === TC_Platform_Sync::PAYMENT_HOOK );
TC_Platform_Sync::run_payment_retry( 26 );
$s = scheduled();
check( 'attempt 2 (timeout, no status) schedules a retry in 30 minutes', count( $s ) === 2 && $s[1]['delay'] === 1800 );
TC_Platform_Sync::run_payment_retry( 26 );
check( 'attempt 3 (500) schedules nothing more', count( scheduled() ) === 2 );
check( 'three requests in all, all with the same Idempotency-Key',
	count( requests() ) === 3
	&& count( array_unique( array_map( function ( $r ) { return $r['args']['headers']['Idempotency-Key']; }, requests() ) ) ) === 1 );
check( 'retries resend the same frozen body', count( array_unique( array_map( function ( $r ) { return $r['args']['body']; }, requests() ) ) ) === 1 );
$o = wc_get_order( 26 );
check( 'final order note says retries stopped and points at the manual action',
	false !== strpos( end( $o->notes ), 'failed 3 times and has stopped retrying' ) );
check( 'a warning log marks it permanent', count( logs_named( 'platform_payment_failed_permanent' ) ) === 1 );
check( 'not acknowledged, so the manual action is offered',
	isset( TC_Platform_Sync::add_manual_payment_action( [], $o )['tc_platform_manual_payment'] ) );
$GLOBALS['tc_test']['responses'] = [ 200 ];
TC_Platform_Sync::manual_payment( $o );
check( 'the manual resend succeeds and acknowledges', (bool) $o->get_meta( TC_Platform_Sync::META_PAID_SENT_AT ) );

echo "\n— 404 / 409 / other 4xx: permanent, no retry —\n";
foreach ( [ 404 => 'does not recognise this order', 409 => 'cancelled or declined', 422 => 'rejected the payment message (HTTP 422)' ] as $code => $phrase ) {
	reset_world();
	make_order( 30 + $code );
	$GLOBALS['tc_test']['responses'] = [ $code ];
	TC_Platform_Sync::on_payment_complete( 30 + $code );
	$o = wc_get_order( 30 + $code );
	check( "$code: one request, no retry scheduled", count( requests() ) === 1 && count( scheduled() ) === 0 );
	check( "$code: order note explains it", false !== strpos( end( $o->notes ), $phrase ) );
	$warn = logs_named( 'platform_payment_failed_permanent' );
	check( "$code: warning log with the status code", count( $warn ) === 1 && $warn[0][0] === 'warn' && $warn[0][2]['status'] === $code );
	check( "$code: not acknowledged", ! $o->get_meta( TC_Platform_Sync::META_PAID_SENT_AT ) );
}

echo "\n— non-GBP order: never relabelled as GBP —\n";
reset_world();
$eur = make_order( 40 );
$eur->currency = 'EUR';
TC_Platform_Sync::on_payment_complete( 40 );
check( 'nothing sent', count( requests() ) === 0 );
check( 'permanent-failure note and warning', count( logs_named( 'platform_payment_failed_permanent' ) ) === 1 );

echo "\n— no health information in logs —\n";
$allowed = [ 'order_id', 'reason', 'status', 'attempt' ];
reset_world();
make_order( 50 );
make_order( 51, 'yes', false );
$GLOBALS['tc_test']['responses'] = [ 503 ];
TC_Platform_Sync::on_payment_complete( 50 );
TC_Platform_Sync::on_payment_complete( 51 );
$GLOBALS['tc_test']['responses'] = [ 200 ];
TC_Platform_Sync::run_payment_retry( 50 );
$extra = [];
foreach ( $GLOBALS['tc_test']['logs'] as $l ) {
	$extra = array_merge( $extra, array_diff( array_keys( $l[2] ), $allowed ) );
}
check( 'every payment log line carries only order_id / reason / status / attempt', $extra === [] );

echo "\n========================================\n";
echo "  $pass passed, $fail failed\n";
echo "========================================\n";
exit( $fail === 0 ? 0 : 1 );
