<?php
/**
 * Standalone proof of TC_Platform_Sync's payment message
 * (`POST /v1/website-orders/payment`), with no WordPress, no WooCommerce, no
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
 *  - a captured order the platform does not hold yet is pushed there first
 *    (as AUTHORISED), then paid; one that is not a review order at all is
 *    skipped with a debug log and no request;
 *  - a first order is never pushed before its card is held (Ahmed, 18 Sep);
 *    a reorder is pushed at creation with previousExternalReference;
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

class WC_Order_Item_Stub {
	public $id = 9001; public $name = 'Treatment pen'; public $variation_id = 3312; public $product_id = 3300;
	public $quantity = 1; public $total = '199.99'; public $sku = 'TC-PEN-1';
	public function get_id() { return $this->id; }
	public function get_name() { return $this->name; }
	public function get_variation_id() { return $this->variation_id; }
	public function get_product_id() { return $this->product_id; }
	public function get_quantity() { return $this->quantity; }
	public function get_total() { return $this->total; }
	public function get_product() { $sku = $this->sku; return new class( $sku ) { private $s; public function __construct( $s ) { $this->s = $s; } public function get_sku() { return $this->s; } }; }
}

class WC_Order {
	public $id; public $meta = []; public $notes = []; public $transaction_id = '';
	public $total = '0.00'; public $refunded = 0; public $currency = 'GBP';
	public $status = 'awaiting-review'; public $created_via = 'tc_eligibility_assessment'; public $items;
	public $billing = [ 'address_1' => '1 Test Street', 'address_2' => '', 'city' => 'Testtown',
		'postcode' => 'SK9 1AA', 'email' => 'patient@example.test', 'phone' => '', 'name' => 'Test Patient' ];
	public function __construct( $id ) { $this->id = $id; $this->items = [ new WC_Order_Item_Stub() ]; }
	public function get_status() { return $this->status; }
	public function get_order_number() { return (string) $this->id; }
	public function get_date_created() { return new DateTime( '2026-10-06T08:00:00Z' ); }
	public function get_items() { return $this->items; }
	public function get_created_via() { return $this->created_via; }
	public function get_billing_address_1() { return $this->billing['address_1']; }
	public function get_billing_address_2() { return $this->billing['address_2']; }
	public function get_billing_city() { return $this->billing['city']; }
	public function get_billing_postcode() { return $this->billing['postcode']; }
	public function get_billing_email() { return $this->billing['email']; }
	public function get_billing_phone() { return $this->billing['phone']; }
	public function get_formatted_billing_full_name() { return $this->billing['name']; }
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
/**
 * Queued responses: an int status, [ status, body array ], or a WP_Error.
 * With nothing queued, each route answers as the platform does on success.
 */
function wp_remote_request( $url, $args ) {
	$GLOBALS['tc_test']['requests'][] = [ 'url' => $url, 'args' => $args ];
	$next = array_shift( $GLOBALS['tc_test']['responses'] );
	if ( $next instanceof WP_Error ) {
		return $next;
	}
	if ( is_array( $next ) ) {
		return [ 'code' => $next[0], 'body' => json_encode( $next[1] ) ];
	}
	$sent = json_decode( $args['body'], true );
	if ( preg_match( '#/v1/patients$#', $url ) ) {
		$body = [ 'id' => 'pat_new', 'externalReference' => $sent['externalReference'] ];
		if ( isset( $sent['order'] ) ) {
			$body['order'] = [ 'result' => 'CREATED', 'websiteOrderId' => '0b0e5a8e-1111-4c1d-9a0b-000000000001',
				'lane' => $sent['order']['lane'], 'holdState' => $sent['order']['holdState'], 'matchState' => 'CREATED' ];
		}
	} elseif ( false !== strpos( $url, '/pre-consultation' ) ) {
		$body = [ 'preConsultationResponseId' => 'pcr_1', 'patientId' => 'pat_new', 'idempotent' => false ];
	} else {
		$body = [ 'result' => 'RECORDED' ];
	}
	return [ 'code' => $next ?? 201, 'body' => json_encode( $body ) ];
}
function wp_remote_retrieve_response_code( $r ) { return $r['code']; }
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function add_query_arg() { return ''; }

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
function make_order( $id, $captured = 'yes', $synced = true, $review = true, $taken_in = true ) {
	$o = new WC_Order( $id );
	if ( $review ) {
		$o->meta['_tc_eligibility_raw'] = '{"selectedTreatment":"x"}';
	}
	if ( $synced ) {
		$o->meta[ TC_Platform_Sync::META_PATIENT_ID ] = 'pat_123';
		$o->meta[ TC_Platform_Sync::META_SYNCED_AT ]  = 1759740000;
		if ( $taken_in ) {
			$o->meta[ TC_Platform_Sync::META_ORDER_PUSHED_AT ] = 1759740000;
		}
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
echo "\n== payload shape (the contract, exactly) ==\n";
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

echo "\n== idempotency key ==\n";
check( 'tc-paid-<order id>-<paymentReference>',
	TC_Platform_Sync::payment_idempotency_key( 501, 'ch_3Test123' ) === 'tc-paid-501-ch_3Test123' );
check( 'works with a PaymentIntent reference too',
	TC_Platform_Sync::payment_idempotency_key( 7, 'pi_3Abc' ) === 'tc-paid-7-pi_3Abc' );

echo "\n== retry classification by status code ==\n";
check( '200 is ok (recorded or already recorded)', TC_Platform_Sync::classify_payment_status( 200 ) === 'ok' );
check( '201 is ok', TC_Platform_Sync::classify_payment_status( 201 ) === 'ok' );
check( '404 WEBSITE_ORDER_NOT_FOUND (unknown order) is permanent',
	TC_Platform_Sync::classify_payment_status( 404, 'WEBSITE_ORDER_NOT_FOUND' ) === 'permanent' );
check( '404 NOT_FOUND (route not deployed on the platform yet) retries',
	TC_Platform_Sync::classify_payment_status( 404, 'NOT_FOUND' ) === 'retry' );
check( '404 with no problem-detail body at all retries', TC_Platform_Sync::classify_payment_status( 404 ) === 'retry' );
check( '409 (cancelled/declined) is permanent', TC_Platform_Sync::classify_payment_status( 409 ) === 'permanent' );
check( '400 is permanent', TC_Platform_Sync::classify_payment_status( 400 ) === 'permanent' );
check( '401 is permanent', TC_Platform_Sync::classify_payment_status( 401 ) === 'permanent' );
check( '422 is permanent', TC_Platform_Sync::classify_payment_status( 422 ) === 'permanent' );
check( '500 retries', TC_Platform_Sync::classify_payment_status( 500 ) === 'retry' );
check( '503 retries', TC_Platform_Sync::classify_payment_status( 503 ) === 'retry' );
check( 'no response at all (timeout, DNS, TLS) retries', TC_Platform_Sync::classify_payment_status( null ) === 'retry' );

echo "\n== skip rules ==\n";
reset_world();
check( 'captured + synced review order: due', TC_Platform_Sync::payment_skip_reason( make_order( 1 ) ) === null );
check( 'authorised only (_stripe_charge_captured = no): not_captured',
	TC_Platform_Sync::payment_skip_reason( make_order( 2, 'no' ) ) === 'not_captured' );
check( 'no Stripe flag at all: not_captured (fail closed)',
	TC_Platform_Sync::payment_skip_reason( make_order( 3, '' ) ) === 'not_captured' );
check( 'captured but not yet on the platform: still due (pushed there first)',
	TC_Platform_Sync::payment_skip_reason( make_order( 4, 'yes', false ) ) === null );
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

echo "\n== capture vs authorise, end to end ==\n";
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

echo "\n== idempotency: no double send ==\n";
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

echo "\n== not a review order: skipped silently with a debug log ==\n";
reset_world();
make_order( 23, 'yes', false, false );
TC_Platform_Sync::on_payment_complete( 23 );
check( 'no request', count( requests() ) === 0 );
check( 'no order note', count( wc_get_order( 23 )->notes ) === 0 );
$skips = logs_named( 'platform_payment_skipped' );
check( 'debug-level skip log with the reason', count( $skips ) >= 1 && $skips[0][0] === 'debug' && $skips[0][2]['reason'] === 'not_review_order' );
check( 'the manual action is not offered', TC_Platform_Sync::add_manual_payment_action( [], wc_get_order( 23 ) ) === [] );

echo "\n== captured, authorisation never seen: patient, intake and order pushed, then payment ==\n";
reset_world();
make_order( 24, 'yes', false );
TC_Platform_Sync::on_payment_complete( 24 );
$r = requests();
check( 'three requests: /v1/patients, pre-consultation, payment',
	count( $r ) === 3 && preg_match( '#/v1/patients$#', $r[0]['url'] ) && false !== strpos( $r[1]['url'], '/pre-consultation' )
	&& false !== strpos( $r[2]['url'], '/v1/website-orders/payment' ) );
check( 'the order goes in as FIRST_ORDER / AUTHORISED, never CAPTURED (no false "captured before decision")',
	json_decode( $r[0]['args']['body'], true )['order']['holdState'] === 'AUTHORISED' );

echo "\n== fail closed when disabled or unconfigured ==\n";
reset_world( false );
make_order( 24 );
TC_Platform_Sync::on_payment_complete( 24 );
check( 'nothing sent while the platform is not configured', count( requests() ) === 0 && count( scheduled() ) === 0 );
check( 'no manual action offered while disabled', TC_Platform_Sync::add_manual_payment_action( [], wc_get_order( 24 ) ) === [] );

echo "\n== inside the platform's own webhook: deferred, not sent inline ==\n";
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

echo "\n== 5xx / timeout: three attempts on the PR #68 schedule ==\n";
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

echo "\n== 404 / 409 / other 4xx: permanent, no retry ==\n";
foreach ( [ 404 => 'does not recognise this order', 409 => 'cancelled or declined', 422 => 'rejected the payment message (HTTP 422)' ] as $code => $phrase ) {
	reset_world();
	make_order( 30 + $code );
	$type = 404 === $code ? 'WEBSITE_ORDER_NOT_FOUND' : ( 409 === $code ? 'ORDER_CANCELLED' : 'VALIDATION_ERROR' );
	$GLOBALS['tc_test']['responses'] = [ [ $code, [ 'type' => $type, 'title' => 'Refused.', 'status' => $code ] ] ];
	TC_Platform_Sync::on_payment_complete( 30 + $code );
	$o = wc_get_order( 30 + $code );
	check( "$code: one request, no retry scheduled", count( requests() ) === 1 && count( scheduled() ) === 0 );
	check( "$code: order note explains it", false !== strpos( end( $o->notes ), $phrase ) );
	$warn = logs_named( 'platform_payment_failed_permanent' );
	check( "$code: warning log with the status code", count( $warn ) === 1 && $warn[0][0] === 'warn' && $warn[0][2]['status'] === $code );
	check( "$code: not acknowledged", ! $o->get_meta( TC_Platform_Sync::META_PAID_SENT_AT ) );
}

echo "\n== 404 from a platform without the route yet: retried, payment not stranded ==\n";
reset_world();
make_order( 45 );
$GLOBALS['tc_test']['responses'] = [ [ 404, [ 'type' => 'NOT_FOUND', 'title' => 'Cannot POST /v1/website-orders/payment', 'status' => 404 ] ] ];
TC_Platform_Sync::on_payment_complete( 45 );
check( 'a route-missing 404 schedules the normal retry', count( scheduled() ) === 1 && scheduled()[0]['hook'] === TC_Platform_Sync::PAYMENT_HOOK && scheduled()[0]['delay'] === 300 );
check( 'no permanent-failure log', count( logs_named( 'platform_payment_failed_permanent' ) ) === 0 );
TC_Platform_Sync::run_payment_retry( 45 );
check( 'once the platform deploys the route, the retry records the payment', (bool) wc_get_order( 45 )->get_meta( TC_Platform_Sync::META_PAID_SENT_AT ) );

echo "\n== non-GBP order: never relabelled as GBP ==\n";
reset_world();
$eur = make_order( 40 );
$eur->currency = 'EUR';
TC_Platform_Sync::on_payment_complete( 40 );
check( 'nothing sent', count( requests() ) === 0 );
check( 'permanent-failure note and warning', count( logs_named( 'platform_payment_failed_permanent' ) ) === 1 );


echo "\n== no health information in logs ==\n";
// patient_id is the platform's own record id, which PR #68's platform_sync_ok already logs.
$allowed = [ 'order_id', 'reason', 'status', 'attempt', 'patient_id', 'result' ];
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
check( 'every log line carries only ids, codes and counts (no order content)', $extra === [] );

// ===========================================================================
// Reorders reach the platform exactly like a first order, and the order
// block that lets the payment message find the order.
// ===========================================================================

/** A first order as PR #68's push left it: eligibility payload, nothing on the platform yet. */
function make_first_order_raw( $id, array $raw ) {
	$o = new WC_Order( $id );
	$o->meta['_tc_eligibility_raw'] = json_encode( $raw );
	$o->total = '199.99';
	$GLOBALS['tc_test']['orders'][ $id ] = $o;
	return $o;
}

/** A reorder exactly as TC_Reorder_Checkout::create_from_submission leaves it. */
function make_reorder( $id, array $payload, $previous_id ) {
	$o = new WC_Order( $id );
	$o->created_via = 'tc_reorder_submission';
	$o->meta['_rrqr_raw'] = json_encode( $payload );
	$o->meta['_rrqr_previous_order_id'] = $previous_id;
	$o->total = '179.00';
	$o->items[0]->total = '179.00';
	$GLOBALS['tc_test']['orders'][ $id ] = $o;
	return $o;
}

$first_raw = [
	'firstName' => 'Test', 'lastName' => 'Patient', 'email' => 'patient@example.test',
	'dob' => '1980-02-03', 'sex' => 'female', 'country' => 'Scotland', 'phone' => '07700900000',
	'addressLine1' => 'Old Address 1', 'postcode' => 'EH1 1AA',
	'termsAgreed' => true, 'gpConsentShare' => true, 'gpConsentSCR' => true,
	'medicalConditions' => 'CLINICAL_MARKER_FIRST_ORDER', 'heightCm' => 170, 'weightKg' => 100,
];
$reorder_payload = [
	'assessment_id' => '6b0f0e1c-0000-4000-8000-00000000abcd',
	'firstName' => 'Test', 'lastName' => 'Patient', 'email' => 'patient@example.test', 'dob' => '1980-02-03',
	'currentMedication' => 'mounjaro', 'currentDose' => '5mg', 'selectedDose' => '7.5mg',
	'currentWeight' => 92.5, 'hasSideEffects' => 'no', 'healthChanged' => 'no', 'termsAgreed' => true,
];

echo "\n== reorder: pushed like a first order (patient, order, pre-consultation) ==\n";
reset_world();
make_first_order_raw( 60, $first_raw );
$ro = make_reorder( 61, $reorder_payload, 60 );
TC_Platform_Sync::on_order_created( $ro, $reorder_payload );
$r = requests();
check( 'two requests: /v1/patients then /v1/patients/{id}/pre-consultation',
	count( $r ) === 2
	&& $r[0]['url'] === 'https://prescribing-api.example.test/v1/patients'
	&& $r[1]['url'] === 'https://prescribing-api.example.test/v1/patients/pat_new/pre-consultation' );
$pb = json_decode( $r[0]['args']['body'], true );
check( 'externalReference is tc-order-<reorder id> (the API offers no patient-level key)', $pb['externalReference'] === 'tc-order-61' );
check( 'order block: lane REORDER, holdState NONE (every reorder is taken in)',
	$pb['order']['lane'] === 'REORDER' && $pb['order']['holdState'] === 'NONE' );
check( 'order block: websiteStatus, order number and submittedAt',
	$pb['order']['websiteStatus'] === 'awaiting-review' && $pb['order']['externalOrderNumber'] === '61'
	&& $pb['order']['submittedAt'] === '2026-10-06T08:00:00Z' );
check( 'order block: product with variation, line item, sku, quantity and pence price',
	$pb['order']['product'] === [ 'variationId' => '3312', 'lineItemId' => '9001', 'name' => 'Treatment pen',
		'quantity' => 1, 'unitPriceMinor' => 17900, 'currency' => 'GBP', 'sku' => 'TC-PEN-1' ] );
check( 'Idempotency-Key names the lane and hold', $r[0]['args']['headers']['Idempotency-Key'] === 'tc-order-61-patient-reorder-none' );
check( 'previousExternalReference is the order the reorder follows', $pb['order']['previousExternalReference'] === 'tc-order-60' );
check( 'pushed at creation, before any payment (the platform takes in every reorder)', $pb['order']['holdState'] === 'NONE' );
check( 'patient: sex and UK nation come from the first assessment the reorder descends from',
	$pb['sexAtBirth'] === 'female' && $pb['country'] === 'SCOTLAND' );
check( 'patient: date of birth and email from this check-in', $pb['dateOfBirth'] === '1980-02-03' && $pb['email'] === 'patient@example.test' );
check( 'patient: address is the reorder\'s own billing address, not the old assessment\'s',
	$pb['addressLines'][0] === '1 Test Street' && $pb['postcode'] === 'SK9 1AA' );
check( 'no clinical answer from the first assessment is carried into the reorder push',
	false === strpos( $r[0]['args']['body'] . $r[1]['args']['body'], 'CLINICAL_MARKER_FIRST_ORDER' ) );
$ib = json_decode( $r[1]['args']['body'], true );
check( 'pre-consultation consents: service only, from the ticked terms box', $ib['consents'] === [ 'service' => true ] );
check( 'gpShare and scrAccess are absent, never sent as false (would overwrite the first order\'s consent)',
	! array_key_exists( 'gpShare', $ib['consents'] ) && ! array_key_exists( 'scrAccess', $ib['consents'] ) );
check( 'gp is an empty object, so the practice on file is kept', $ib['gp'] === [] && false !== strpos( $r[1]['args']['body'], '"gp":{}' ) );
check( 'measurements: the reported current weight', $ib['measurements'] === [ 'weightKg' => 92.5 ] );
check( 'answers are the reorder check-in\'s own, identity keys stripped',
	$ib['answers']['selectedDose'] === '7.5mg' && ! isset( $ib['answers']['email'] ) && ! isset( $ib['answers']['dob'] ) );
check( 'pre-consultation Idempotency-Key unchanged from PR #68', $r[1]['args']['headers']['Idempotency-Key'] === 'tc-order-61-intake' );
$ro = wc_get_order( 61 );
check( 'patient id stored, synced, and the order recorded as taken in',
	$ro->get_meta( TC_Platform_Sync::META_PATIENT_ID ) === 'pat_new' && $ro->get_meta( TC_Platform_Sync::META_SYNCED_AT )
	&& $ro->get_meta( TC_Platform_Sync::META_ORDER_PUSHED_AT )
	&& $ro->get_meta( TC_Platform_Sync::META_WEBSITE_ORDER_ID ) === '0b0e5a8e-1111-4c1d-9a0b-000000000001' );

echo "\n== reorder: its payment message then works through the same code ==\n";
$GLOBALS['tc_test']['requests'] = [];
$ro->meta['_stripe_charge_captured'] = 'yes';
$ro->transaction_id = 'ch_3Reorder';
TC_Platform_Sync::on_stripe_manual_capture( $ro, (object) [ 'id' => 'ch_3Reorder', 'amount_captured' => 17900 ] );
$r = requests();
check( 'exactly one request, the payment message (the order is already on the platform)',
	count( $r ) === 1 && false !== strpos( $r[0]['url'], '/v1/website-orders/payment' ) );
$pay = json_decode( $r[0]['args']['body'], true );
check( 'payment names the reorder by tc-order-<id>', $pay['externalReference'] === 'tc-order-61' && $pay['amountPence'] === 17900 );
check( 'acknowledged', (bool) $ro->get_meta( TC_Platform_Sync::META_PAID_SENT_AT ) );

echo "\n== reorder: consent is never inferred ==\n";
reset_world();
make_first_order_raw( 62, $first_raw );
$no_terms = $reorder_payload;
unset( $no_terms['termsAgreed'] );
TC_Platform_Sync::on_order_created( make_reorder( 63, $no_terms, 62 ), $no_terms );
$ib = json_decode( requests()[1]['args']['body'], true );
check( 'no termsAgreed in the payload sends service: false (the platform then refuses, fail closed)', $ib['consents'] === [ 'service' => false ] );

echo "\n== reorder of a reorder: identity found two orders back ==\n";
reset_world();
make_first_order_raw( 70, $first_raw );
make_reorder( 71, $reorder_payload, 70 );
$ro2 = make_reorder( 72, [ 'email' => 'new@example.test' ], 71 );
$id = TC_Platform_Sync::reorder_identity_payload( $ro2, [ 'email' => 'new@example.test' ] );
check( 'walks _rrqr_previous_order_id back to the eligibility assessment',
	$id['sex'] === 'female' && $id['country'] === 'Scotland' && $id['dob'] === '1980-02-03' );
check( 'this check-in\'s own email wins', $id['email'] === 'new@example.test' );
check( 'only identity keys are taken from the old assessment', array_diff( array_keys( $id ), [ 'firstName', 'lastName', 'email', 'dob', 'sex', 'country', 'phone' ] ) === [] );
check( 'previousExternalReference names the order it directly follows (the previous reorder)',
	TC_Platform_Sync::order_block( $ro2 )['previousExternalReference'] === 'tc-order-71' );
$orphan = make_reorder( 73, $reorder_payload, 0 );
check( 'no previous order recorded: the field is left out, never invented',
	! array_key_exists( 'previousExternalReference', TC_Platform_Sync::order_block( $orphan ) ) );
check( 'never on a first order',
	! array_key_exists( 'previousExternalReference', (array) TC_Platform_Sync::order_block( make_order( 74 ) ) ) );

echo "\n== reorder retry reads the reorder payload ==\n";
reset_world();
make_first_order_raw( 64, $first_raw );
make_reorder( 65, $reorder_payload, 64 );
$GLOBALS['tc_test']['responses'] = [ 503 ];
TC_Platform_Sync::on_order_created( wc_get_order( 65 ), $reorder_payload );
check( 'a 503 on the patient push schedules the PR #68 retry', count( scheduled() ) === 1 && scheduled()[0]['hook'] === 'tc_platform_sync_retry' );
TC_Platform_Sync::run_retry( 65 );
$r = requests();
check( 'the retry pushes the reorder again from _rrqr_raw, with its order block',
	count( $r ) === 3 && json_decode( $r[1]['args']['body'], true )['order']['lane'] === 'REORDER' );

echo "\n== first order: nothing reaches the platform before the card is held ==\n";
reset_world();
$fo = make_first_order_raw( 80, $first_raw );
TC_Platform_Sync::on_order_created( $fo, $first_raw );
check( 'no request at creation (abandoned, unpaid first orders never reach the platform)', count( requests() ) === 0 );
check( 'no retry queued, nothing recorded as a failure', count( scheduled() ) === 0 && ! $fo->get_meta( TC_Platform_Sync::META_SYNC_ATTEMPTS ) );
TC_Platform_Sync::run_retry( 80 );
check( 'a stray retry sends nothing either', count( requests() ) === 0 );
check( '"Send to prescribing platform" is not offered for an unpaid first order', TC_Platform_Sync::add_manual_sync_action( [], $fo ) === [] );
TC_Platform_Sync::on_payment_complete( 80 );
check( 'a payment_complete with no card held sends nothing', count( requests() ) === 0 );

echo "\n== first order: card authorised, first push (patient, order, pre-consultation) ==\n";
$fo->meta['_stripe_charge_captured'] = 'no';
$fo->transaction_id = 'ch_3Held';
TC_Platform_Sync::on_stripe_response( (object) [ 'id' => 'ch_3Held', 'captured' => false ], $fo );
$r = requests();
check( 'two requests: /v1/patients then pre-consultation',
	count( $r ) === 2 && preg_match( '#/v1/patients$#', $r[0]['url'] ) && $r[1]['url'] === 'https://prescribing-api.example.test/v1/patients/pat_new/pre-consultation' );
$pb = json_decode( $r[0]['args']['body'], true );
check( 'order block FIRST_ORDER / AUTHORISED on tc-order-<id>',
	$pb['externalReference'] === 'tc-order-80' && $pb['order']['lane'] === 'FIRST_ORDER' && $pb['order']['holdState'] === 'AUTHORISED' );
check( 'Idempotency-Key names lane and hold', $r[0]['args']['headers']['Idempotency-Key'] === 'tc-order-80-patient-first_order-authorised' );
$ib = json_decode( $r[1]['args']['body'], true );
check( 'first-order consents as PR #68 sends them (service, gpShare, scrAccess)', $ib['consents'] === [ 'service' => true, 'gpShare' => true, 'scrAccess' => true ] );
check( 'synced and recorded as taken in', $fo->get_meta( TC_Platform_Sync::META_SYNCED_AT ) && $fo->get_meta( TC_Platform_Sync::META_ORDER_PUSHED_AT ) );
TC_Platform_Sync::on_stripe_response( (object) [], $fo );
check( 'a second gateway response sends nothing more', count( requests() ) === 2 );
$GLOBALS['tc_test']['requests'] = [];
$fo->meta['_stripe_charge_captured'] = 'yes';
TC_Platform_Sync::on_stripe_manual_capture( $fo, (object) [ 'id' => 'ch_3Held', 'amount_captured' => 19999 ] );
check( 'the capture then sends only the payment message',
	count( requests() ) === 1 && false !== strpos( requests()[0]['url'], '/v1/website-orders/payment' ) );

echo "\n== first order synced by an earlier version, hold never seen: order before payment ==\n";
reset_world();
make_order( 90, 'yes', true, true, false );
TC_Platform_Sync::on_payment_complete( 90 );
$r = requests();
check( 'order pushed first as FIRST_ORDER / AUTHORISED, then the payment records the capture',
	count( $r ) === 2 && json_decode( $r[0]['args']['body'], true )['order']['holdState'] === 'AUTHORISED'
	&& false !== strpos( $r[1]['url'], '/v1/website-orders/payment' ) );
reset_world();
make_order( 91, 'yes', true, true, false );
$GLOBALS['tc_test']['responses'] = [ 503 ];
TC_Platform_Sync::on_payment_complete( 91 );
$r = requests();
check( 'if that order push fails, no payment is sent (it would only 404)', count( $r ) === 1 );
check( 'one retry queued, the payment\'s own', count( scheduled() ) === 1 && scheduled()[0]['hook'] === TC_Platform_Sync::PAYMENT_HOOK );

echo "\n== order not taken in (intake switched off on the platform) ==\n";
reset_world();
make_first_order_raw( 92, $first_raw );
$ro = make_reorder( 93, $reorder_payload, 92 );
$GLOBALS['tc_test']['responses'] = [ [ 201, [ 'id' => 'pat_9', 'order' => [ 'result' => 'NOT_TAKEN_IN', 'reason' => 'FEATURE_NOT_ENABLED' ] ] ] ];
TC_Platform_Sync::on_order_created( $ro, $reorder_payload );
check( 'patient still synced', wc_get_order( 93 )->get_meta( TC_Platform_Sync::META_PATIENT_ID ) === 'pat_9' );
check( 'order not marked taken in', ! wc_get_order( 93 )->get_meta( TC_Platform_Sync::META_ORDER_PUSHED_AT ) );
check( 'an order note says the payment cannot be recorded yet',
	count( array_filter( wc_get_order( 93 )->notes, function ( $n ) { return false !== strpos( $n, 'did not take in the order (NOT_TAKEN_IN, FEATURE_NOT_ENABLED)' ); } ) ) === 1 );
check( '"Send to prescribing platform" stays offered so staff can push the order later',
	isset( TC_Platform_Sync::add_manual_sync_action( [], wc_get_order( 93 ) )['tc_platform_manual_sync'] ) );

echo "\n== a parked or skipped push (202, no patient) is a failure, retried ==\n";
reset_world();
make_first_order_raw( 94, $first_raw );
$ro = make_reorder( 95, $reorder_payload, 94 );
$GLOBALS['tc_test']['responses'] = [ [ 202, [ 'externalReference' => 'tc-order-95', 'order' => [ 'result' => 'UNCHANGED', 'matchState' => 'PARKED' ] ] ] ];
TC_Platform_Sync::on_order_created( $ro, $reorder_payload );
check( 'no pre-consultation sent without a patient', count( requests() ) === 1 );
check( 'retry scheduled and the reason recorded without health data',
	count( scheduled() ) === 1 && wc_get_order( 95 )->get_meta( TC_Platform_Sync::META_SYNC_LAST_ERROR ) === 'platform returned no patient (order UNCHANGED, PARKED)' );

// ===========================================================================
// Cancellation message: POST /v1/website-orders/cancellation
// ===========================================================================

/** Run every queued cancellation event once, as WP-Cron would. */
function run_cancellation_queue() {
	$queued = array_values( array_filter( scheduled(), function ( $e ) { return $e['hook'] === TC_Platform_Sync::CANCEL_HOOK; } ) );
	$GLOBALS['tc_test']['scheduled'] = array_values( array_filter( scheduled(), function ( $e ) { return $e['hook'] !== TC_Platform_Sync::CANCEL_HOOK; } ) );
	foreach ( $queued as $e ) {
		TC_Platform_Sync::run_cancellation( $e['args'][0], $e['args'][1] );
	}
	return count( $queued );
}
function cancel_requests() {
	return array_values( array_filter( requests(), function ( $r ) { return false !== strpos( $r['url'], '/v1/website-orders/cancellation' ); } ) );
}

echo "\n== cancellation: payload, key and reasons ==\n";
$cb = TC_Platform_Sync::cancellation_request_body( 501, 'CANCELLED', '2026-10-06T10:00:00Z' );
check( 'exactly the three contract keys', $cb === [ 'externalReference' => 'tc-order-501', 'reason' => 'CANCELLED', 'occurredAt' => '2026-10-06T10:00:00Z' ] );
check( 'Idempotency-Key is tc-cancel-<id>-<reason>', TC_Platform_Sync::cancellation_idempotency_key( 501, 'REFUNDED' ) === 'tc-cancel-501-REFUNDED' );
check( 'cancelled, refunded and failed map to their reasons; trash is a cancellation',
	TC_Platform_Sync::cancellation_reason( 'cancelled' ) === 'CANCELLED' && TC_Platform_Sync::cancellation_reason( 'refunded' ) === 'REFUNDED'
	&& TC_Platform_Sync::cancellation_reason( 'failed' ) === 'FAILED' && TC_Platform_Sync::cancellation_reason( 'trash' ) === 'CANCELLED' );
check( 'any other status is not a cancellation',
	null === TC_Platform_Sync::cancellation_reason( 'processing' ) && null === TC_Platform_Sync::cancellation_reason( 'pending' ) );

echo "\n== cancellation: cancelled order on the platform is reported once ==\n";
reset_world();
$co = make_order( 100, 'no' );
$co->status = 'cancelled';
TC_Platform_Sync::on_status_changed( 100, 'awaiting-review', 'cancelled' );
check( 'queued on WP-Cron, not sent inline', count( requests() ) === 0 && count( scheduled() ) === 1 && scheduled()[0]['args'] === [ 100, 'CANCELLED' ] );
run_cancellation_queue();
$r = cancel_requests();
check( 'one POST to /v1/website-orders/cancellation', count( $r ) === 1 && $r[0]['url'] === 'https://prescribing-api.example.test/v1/website-orders/cancellation' );
check( 'same Bearer API key', $r[0]['args']['headers']['Authorization'] === 'Bearer tk_test_harness_only' );
check( 'Idempotency-Key tc-cancel-100-CANCELLED', $r[0]['args']['headers']['Idempotency-Key'] === 'tc-cancel-100-CANCELLED' );
$cb = json_decode( $r[0]['args']['body'], true );
check( 'body: tc-order-100, CANCELLED, ISO-8601 UTC occurredAt',
	$cb['externalReference'] === 'tc-order-100' && $cb['reason'] === 'CANCELLED'
	&& 1 === preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $cb['occurredAt'] ) && count( $cb ) === 3 );
check( 'sent flag stored and an order note added',
	(bool) $co->get_meta( TC_Platform_Sync::META_CANCEL_SENT_PREFIX . 'cancelled' ) && end( $co->notes ) === 'Prescribing platform told this order is cancelled.' );
TC_Platform_Sync::on_status_changed( 100, 'pending', 'cancelled' );
TC_Platform_Sync::on_trash_order( 100 );
check( 'cancelled again, or trashed afterwards: nothing queued (once per reason)', count( scheduled() ) === 0 );

echo "\n== cancellation: full refund, failed and trash ==\n";
reset_world();
$ref = make_order( 101 );
$ref->status = 'refunded';
TC_Platform_Sync::on_status_changed( 101, 'processing', 'refunded' );
run_cancellation_queue();
check( 'full refund (status refunded) sends REFUNDED', json_decode( cancel_requests()[0]['args']['body'], true )['reason'] === 'REFUNDED' );
$ref->status = 'cancelled';
TC_Platform_Sync::on_status_changed( 101, 'refunded', 'cancelled' );
run_cancellation_queue();
check( 'a different reason later is its own message', count( cancel_requests() ) === 2 && json_decode( cancel_requests()[1]['args']['body'], true )['reason'] === 'CANCELLED' );
reset_world();
$fa = make_order( 102, '' );
$fa->status = 'failed';
TC_Platform_Sync::on_status_changed( 102, 'pending', 'failed' );
run_cancellation_queue();
check( 'failed sends FAILED', json_decode( cancel_requests()[0]['args']['body'], true )['reason'] === 'FAILED' );
reset_world();
$tr = make_order( 103 );
$tr->status = 'trash';
TC_Platform_Sync::on_trash_order( 103 );
run_cancellation_queue();
check( 'trash sends CANCELLED', json_decode( cancel_requests()[0]['args']['body'], true )['reason'] === 'CANCELLED' );

echo "\n== cancellation: partial refund sends nothing, adds a note ==\n";
reset_world();
$pr = make_order( 104 );
$pr->status = 'processing';
TC_Platform_Sync::on_partial_refund( 104 );
check( 'nothing queued or sent', count( scheduled() ) === 0 && count( requests() ) === 0 );
check( 'an order note says so', false !== strpos( end( $pr->notes ), 'Partial refund: nothing was sent to the prescribing platform' ) );

echo "\n== cancellation: never for an order the platform never received ==\n";
reset_world();
$ns = make_first_order_raw( 105, $first_raw );
$ns->status = 'cancelled';
TC_Platform_Sync::on_status_changed( 105, 'awaiting-review', 'cancelled' );
check( 'abandoned first order (never pushed): nothing queued', count( scheduled() ) === 0 && count( requests() ) === 0 );
check( 'debug log only', logs_named( 'platform_cancellation_skipped' )[0][0] === 'debug' && logs_named( 'platform_cancellation_skipped' )[0][2]['reason'] === 'not_on_platform' );
TC_Platform_Sync::on_partial_refund( 105 );
check( 'no partial-refund note either', count( $ns->notes ) === 0 );
reset_world();
$nr = make_order( 106, 'yes', true, false );
$nr->status = 'cancelled';
TC_Platform_Sync::on_status_changed( 106, 'processing', 'cancelled' );
check( 'not a review order: nothing queued', count( scheduled() ) === 0 );

echo "\n== cancellation: the platform's own decline is never echoed back ==\n";
reset_world();
$dp = make_order( 107, 'no' );
$dp->status = 'cancelled';
$flag = new ReflectionProperty( 'TC_Platform_Sync', 'in_platform_webhook' );
if ( PHP_VERSION_ID < 80100 ) { $flag->setAccessible( true ); }
$flag->setValue( null, true );   // consultation.declined -> reject() -> cancelled, inside the webhook
TC_Platform_Sync::on_status_changed( 107, 'awaiting-review', 'cancelled' );
$flag->setValue( null, false );
check( 'inside the platform webhook: nothing queued', count( scheduled() ) === 0 );
$dp->meta['_tc_review_decision']   = 'rejected';
$dp->meta['_tc_review_decided_by'] = 'Prescribing platform';
TC_Platform_Sync::on_trash_order( 107 );
TC_Platform_Sync::on_status_changed( 107, 'cancelled', 'cancelled' );
check( 'later, the recorded platform rejection still stops it (e.g. the order is trashed)', count( scheduled() ) === 0 );
reset_world();
$wp = make_order( 108, 'no' );
$wp->status = 'cancelled';
$wp->meta['_tc_review_decision']   = 'rejected';
$wp->meta['_tc_review_decided_by'] = 'Jane Pharmacist';
TC_Platform_Sync::on_status_changed( 108, 'awaiting-review', 'cancelled' );
run_cancellation_queue();
check( 'a rejection by a WordPress prescriber IS reported', count( cancel_requests() ) === 1 );

echo "\n== cancellation: a status that does not stick is not reported ==\n";
reset_world();
$gv = make_order( 109, 'no' );
$gv->status = 'cancelled';
TC_Platform_Sync::on_status_changed( 109, 'awaiting-review', 'cancelled' );
$gv->status = 'awaiting-review';   // TC_Review_Status::guard_transition() reverts it
run_cancellation_queue();
check( 'guard-reverted cancel: nothing sent', count( cancel_requests() ) === 0 );
$fc = make_order( 110, 'no' );
$fc->status = 'failed';
TC_Platform_Sync::on_status_changed( 110, 'processing', 'failed' );
$fc->status = 'pending';           // approve(): failed capture falls back to the pay link
run_cancellation_queue();
check( 'failed capture that moved on to the pay link: nothing sent', count( cancel_requests() ) === 0 );
$fc->status = 'failed';
TC_Platform_Sync::on_status_changed( 110, 'pending', 'failed' );
run_cancellation_queue();
check( 'a later genuine failure is still reported', count( cancel_requests() ) === 1 );

echo "\n== cancellation: responses ==\n";
reset_world();
$c404 = make_order( 111 );
$c404->meta[ TC_Platform_Sync::META_ORDER_PUSHED_AT ] = '';
$c404->status = 'cancelled';
$GLOBALS['tc_test']['responses'] = [ [ 404, [ 'type' => 'WEBSITE_ORDER_NOT_FOUND', 'title' => 'No such order.', 'status' => 404 ] ] ];
TC_Platform_Sync::on_status_changed( 111, 'awaiting-review', 'cancelled' );
run_cancellation_queue();
check( '404 unknown order: quiet (no note, no warning, no retry)',
	count( $c404->notes ) === 0 && count( logs_named( 'platform_cancellation_failed_permanent' ) ) === 0 && count( scheduled() ) === 0 );
check( '...and not tried again', $c404->get_meta( TC_Platform_Sync::META_CANCEL_SENT_PREFIX . 'cancelled' ) === 'not_on_platform' );

reset_world();
$c5 = make_order( 112 );
$c5->status = 'cancelled';
$GLOBALS['tc_test']['responses'] = [ 503, [ 404, [ 'type' => 'NOT_FOUND', 'title' => 'Cannot POST', 'status' => 404 ] ], 500 ];
TC_Platform_Sync::on_status_changed( 112, 'processing', 'cancelled' );
run_cancellation_queue();
check( '503: retry in 5 minutes', count( scheduled() ) === 1 && scheduled()[0]['delay'] === 300 && scheduled()[0]['hook'] === TC_Platform_Sync::CANCEL_HOOK );
run_cancellation_queue();
check( 'route-missing 404: retry in 30 minutes', count( scheduled() ) === 1 && scheduled()[0]['delay'] === 1800 );
run_cancellation_queue();
check( '500 on the third attempt: stops, note and warning',
	count( scheduled() ) === 0 && false !== strpos( end( $c5->notes ), 'failed 3 times and has stopped retrying' )
	&& count( logs_named( 'platform_cancellation_failed_permanent' ) ) === 1 );
check( 'all three with the same Idempotency-Key',
	count( array_unique( array_map( function ( $r ) { return $r['args']['headers']['Idempotency-Key']; }, cancel_requests() ) ) ) === 1 );

reset_world();
$c4 = make_order( 113 );
$c4->status = 'refunded';
$GLOBALS['tc_test']['responses'] = [ [ 422, [ 'type' => 'VALIDATION_ERROR', 'title' => 'Invalid.', 'status' => 422 ] ] ];
TC_Platform_Sync::on_status_changed( 113, 'processing', 'refunded' );
run_cancellation_queue();
check( 'other 4xx: permanent, note and warning, no retry',
	count( scheduled() ) === 0 && false !== strpos( end( $c4->notes ), 'rejected the refunded message (HTTP 422)' )
	&& logs_named( 'platform_cancellation_failed_permanent' )[0][2]['status'] === 422 );

reset_world( false );
$cd = make_order( 114 );
$cd->status = 'cancelled';
TC_Platform_Sync::on_status_changed( 114, 'processing', 'cancelled' );
check( 'disabled or unconfigured: nothing queued', count( scheduled() ) === 0 );

echo "\n========================================\n";
echo "  $pass passed, $fail failed\n";
echo "========================================\n";
exit( $fail === 0 ? 0 : 1 );
