<?php
/**
 * Unit test for inc/ihq-channel-methods.php (ENGR-8014): influencerhq-api
 * GET /v1/channels/methods read server to server, a method on, a method off,
 * an absent feature ({"methods":null}) and a failed call (last good answer,
 * else today's lists), the 30-second cache, the modal list filter and the 403
 * refusal. No WordPress bootstrap and no live requests:
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/ihq-channel-methods.test.php
 *
 * Exit code is non-zero on any failure.
 */
ob_start();
ini_set( 'error_log', '/dev/null' );

define( 'ABSPATH', '/' );
define( 'INFLUENCER_API_BASE', 'https://api.example.test/qc' );
define( '_S_VERSION', '9.9.9' );

const METHODS_URL = 'https://api.example.test/qc/v1/channels/methods';
const THEME_URI   = 'https://ihq.example.test/wp-content/themes/influencer-hq';
const ALL_OFF     = array(
	'email'     => false,
	'sms'       => false,
	'telegram'  => false,
	'line'      => false,
	'whatsapp'  => false,
	'zalo'      => false,
	'kakaotalk' => false,
	'wechat'    => false,
);

class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
class JsonResponse extends Exception {
	public $success;
	public $data;
	public $status;
	public function __construct( $success, $data, $status ) {
		parent::__construct( 'json' );
		$this->success = $success;
		$this->data    = $data;
		$this->status  = $status;
	}
}

function reset_state() {
	$GLOBALS['requests']   = array();
	$GLOBALS['responses']  = array();
	$GLOBALS['transients'] = array();
	$GLOBALS['options']    = array();
	$GLOBALS['logged_in']  = false;
	$GLOBALS['enqueued']   = array();
	$GLOBALS['theme_dir']  = '/nowhere';
}

// --- WordPress stubs. ---
function add_action( ...$args ) { $GLOBALS['hooks'][] = $args; }
function __( $text ) { return $text; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_remote_get( $url, $args ) {
	$GLOBALS['requests'][] = array( 'url' => $url, 'args' => $args );
	if ( empty( $GLOBALS['responses'] ) ) {
		throw new Exception( 'unexpected request to ' . $url );
	}
	return array_shift( $GLOBALS['responses'] );
}
function wp_remote_retrieve_body( $response ) { return $response['body']; }
function wp_remote_retrieve_response_code( $response ) { return $response['status']; }
function get_transient( $key ) { return $GLOBALS['transients'][ $key ]['value'] ?? false; }
function set_transient( $key, $value, $ttl = 0 ) { $GLOBALS['transients'][ $key ] = array( 'value' => $value, 'ttl' => $ttl ); return true; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ]['value'] ?? $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['options'][ $key ] = array( 'value' => $value, 'autoload' => $autoload ); return true; }
function wp_send_json_error( $data = null, $status = null ) { throw new JsonResponse( false, $data, $status ); }
function is_user_logged_in() { return $GLOBALS['logged_in']; }
function get_template_directory() { return $GLOBALS['theme_dir']; }
function get_template_directory_uri() { return THEME_URI; }
function wp_enqueue_script_module( $id, $src = '', $deps = array(), $version = false ) {
	$GLOBALS['enqueued'][] = array( 'id' => $id, 'src' => $src, 'deps' => $deps, 'version' => $version );
}

$GLOBALS['hooks'] = array();
require __DIR__ . '/../inc/ihq-channel-methods.php';

$fail = 0;
$out  = array();
function check( $label, $actual, $expected ) {
	global $fail, $out;
	$ok    = $actual === $expected;
	$out[] = ( $ok ? 'PASS ' : 'FAIL ' ) . $label;
	if ( ! $ok ) {
		$out[] = '  expected: ' . var_export( $expected, true );
		$out[] = '  actual:   ' . var_export( $actual, true );
		$fail++;
	}
}

function respond( $status, $body ) { $GLOBALS['responses'][] = array( 'status' => $status, 'body' => $body ); }
function respond_error( $message ) { $GLOBALS['responses'][] = new WP_Error( 'http_request_failed', $message ); }
function methods( array $on ) { return array_merge( ALL_OFF, $on ); }
function feature_body( array $on ) { return json_encode( array( 'methods' => methods( $on ) ) ); }
function cached() { return $GLOBALS['transients']['ihq_channel_methods'] ?? null; }
function last_good() { return $GLOBALS['options']['ihq_channel_methods_last_good'] ?? null; }
function refusal_of( callable $call ) {
	try {
		$returned = $call();
	} catch ( JsonResponse $response ) {
		return array( 'success' => $response->success, 'data' => $response->data, 'status' => $response->status );
	}
	return array( 'returned' => $returned );
}

// --- The request ---
reset_state();
respond( 200, feature_body( array( 'email' => true ) ) );
ihq_channel_methods_current();
check(
	'request: GET /v1/channels/methods on the API base, short timeout, no redirects, TLS verified',
	$GLOBALS['requests'],
	array(
		array(
			'url'  => METHODS_URL,
			'args' => array(
				'headers'     => array( 'Accept' => 'application/json' ),
				'timeout'     => 3,
				'redirection' => 0,
				'sslverify'   => true,
			),
		),
	)
);

// --- A method on and a method off ---
reset_state();
respond( 200, feature_body( array( 'email' => true ) ) );
$current = ihq_channel_methods_current();
check( 'feature: the answer is the eight methods', $current, methods( array( 'email' => true ) ) );
check( 'feature: email on is allowed', ihq_channel_method_allowed( 'email', $current ), true );
check( 'feature: telegram off is refused', ihq_channel_method_allowed( 'telegram', $current ), false );
check( 'feature: an unknown method is refused', ihq_channel_method_allowed( 'fax', $current ), false );
check( 'feature: cached for 30 seconds', cached(), array( 'value' => array( 'methods' => methods( array( 'email' => true ) ) ), 'ttl' => 30 ) );
check( 'feature: kept as the last good answer, not autoloaded', last_good(), array( 'value' => array( 'methods' => methods( array( 'email' => true ) ) ), 'autoload' => false ) );

check( 'cache: a second read inside 30 seconds makes no request', array( ihq_channel_methods_current(), count( $GLOBALS['requests'] ) ), array( methods( array( 'email' => true ) ), 1 ) );

// --- Parsing the body ---
check( 'parse: a missing key is off', ihq_channel_methods_parse_body( '{"methods":{"email":true}}' ), array( 'ok' => true, 'methods' => methods( array( 'email' => true ) ) ) );
check( 'parse: a non-boolean value is off', ihq_channel_methods_parse_body( '{"methods":{"email":"true","telegram":1,"line":true}}' ), array( 'ok' => true, 'methods' => methods( array( 'line' => true ) ) ) );
check( 'parse: an empty object is all off', ihq_channel_methods_parse_body( '{"methods":{}}' ), array( 'ok' => true, 'methods' => ALL_OFF ) );
check( 'parse: unknown keys are ignored', ihq_channel_methods_parse_body( '{"methods":{"fax":true,"sms":true}}' ), array( 'ok' => true, 'methods' => methods( array( 'sms' => true ) ) ) );
check( 'parse: null is the absent feature', ihq_channel_methods_parse_body( '{"methods":null}' ), array( 'ok' => true, 'methods' => null ) );
check( 'parse: no methods key is a failure', ihq_channel_methods_parse_body( '{"error":"x"}' ), array( 'ok' => false, 'methods' => null ) );
check( 'parse: methods as a string is a failure', ihq_channel_methods_parse_body( '{"methods":"email"}' ), array( 'ok' => false, 'methods' => null ) );
check( 'parse: not JSON is a failure', ihq_channel_methods_parse_body( '<html>' ), array( 'ok' => false, 'methods' => null ) );
check( 'parse: a JSON scalar is a failure', ihq_channel_methods_parse_body( '"methods"' ), array( 'ok' => false, 'methods' => null ) );

// --- An absent feature keeps today's lists ---
reset_state();
respond( 200, '{"methods":null}' );
$current = ihq_channel_methods_current();
check( 'absent feature: null', $current, null );
check( 'absent feature: every method is allowed', array( ihq_channel_method_allowed( 'email', $current ), ihq_channel_method_allowed( 'telegram', $current ), ihq_channel_method_allowed( 'wechat', $current ) ), array( true, true, true ) );
check( 'absent feature: cached for 30 seconds', cached(), array( 'value' => array( 'methods' => null ), 'ttl' => 30 ) );
check( 'absent feature: replaces the last good answer', last_good(), array( 'value' => array( 'methods' => null ), 'autoload' => false ) );

// --- A failed call ---
$failures = array(
	'transport error' => function () { respond_error( 'timed out' ); },
	'HTTP 404'        => function () { respond( 404, '{"message":"Not Found"}' ); },
	'HTTP 500'        => function () { respond( 500, '{"methods":null}' ); },
	'HTTP 199'        => function () { respond( 199, feature_body( array( 'telegram' => true ) ) ); },
	'HTTP 300'        => function () { respond( 300, feature_body( array( 'telegram' => true ) ) ); },
	'bad body'        => function () { respond( 200, 'not json' ); },
);
foreach ( $failures as $label => $fail_with ) {
	reset_state();
	$fail_with();
	check( 'failed call (' . $label . '), no earlier answer: today\'s lists', ihq_channel_methods_current(), null );
	check( 'failed call (' . $label . '), no earlier answer: cached 30 seconds, last good untouched', array( cached(), last_good() ), array( array( 'value' => array( 'methods' => null ), 'ttl' => 30 ), null ) );

	reset_state();
	update_option( 'ihq_channel_methods_last_good', array( 'methods' => methods( array( 'telegram' => true ) ) ), false );
	$fail_with();
	check( 'failed call (' . $label . ') after a good one: the last good answer', ihq_channel_methods_current(), methods( array( 'telegram' => true ) ) );
	check( 'failed call (' . $label . ') after a good one: cached 30 seconds', cached(), array( 'value' => array( 'methods' => methods( array( 'telegram' => true ) ) ), 'ttl' => 30 ) );
}
check( 'HTTP 201 counts as an answer', ( function () { reset_state(); respond( 201, feature_body( array( 'zalo' => true ) ) ); return ihq_channel_methods_current(); } )(), methods( array( 'zalo' => true ) ) );
check( 'HTTP 299 counts as an answer', ( function () { reset_state(); respond( 299, feature_body( array( 'zalo' => true ) ) ); return ihq_channel_methods_current(); } )(), methods( array( 'zalo' => true ) ) );

reset_state();
update_option( 'ihq_channel_methods_last_good', array( 'methods' => null ), false );
respond_error( 'timed out' );
check( 'failed call after an absent feature: still today\'s lists', ihq_channel_methods_current(), null );

reset_state();
update_option( 'ihq_channel_methods_last_good', 'garbage', false );
respond_error( 'timed out' );
check( 'failed call with a malformed stored answer: today\'s lists', ihq_channel_methods_current(), null );

reset_state();
update_option( 'ihq_channel_methods_last_good', array( 'email' => true ), false );
respond_error( 'timed out' );
check( 'failed call with a stored answer missing its methods key: today\'s lists', ihq_channel_methods_current(), null );

reset_state();
$GLOBALS['transients']['ihq_channel_methods'] = array( 'value' => 'garbage', 'ttl' => 30 );
respond( 200, feature_body( array( 'wechat' => true ) ) );
check( 'a malformed cache entry is ignored and refreshed', ihq_channel_methods_current(), methods( array( 'wechat' => true ) ) );

reset_state();
$GLOBALS['transients']['ihq_channel_methods'] = array( 'value' => array( 'email' => true ), 'ttl' => 30 );
respond( 200, feature_body( array( 'kakaotalk' => true ) ) );
check( 'a cache entry missing its methods key is ignored and refreshed', ihq_channel_methods_current(), methods( array( 'kakaotalk' => true ) ) );

// --- The modal's code-delivery list ---
$today = array(
	array( 'id' => 'email', 'label' => 'Email', 'available' => true ),
	array( 'id' => 'line', 'label' => 'LINE', 'available' => false ),
	array( 'id' => 'telegram', 'label' => 'Telegram', 'available' => false ),
);
check( 'list: absent feature or failed call keeps today\'s list', ihq_channel_methods_filter_list( $today, null ), $today );
check( 'list: only the methods on, in order, fields kept', ihq_channel_methods_filter_list( $today, methods( array( 'email' => true, 'telegram' => true ) ) ), array( $today[0], $today[2] ) );
check( 'list: all off is empty', ihq_channel_methods_filter_list( $today, ALL_OFF ), array() );

// --- The refusal ---
check( 'message: email', ihq_channel_method_unavailable_message( 'email' ), "Email isn't available right now" );
check( 'message: telegram', ihq_channel_method_unavailable_message( 'telegram' ), "Telegram isn't available right now" );
check(
	'labels: the eight methods as on the buttons',
	array_map( 'ihq_channel_method_label', IHQ_CHANNEL_METHODS ),
	array( 'Email', 'SMS', 'Telegram', 'LINE', 'WhatsApp', 'Zalo', 'KakaoTalk', 'WeChat' )
);
check( 'labels: an unknown method shows its id', ihq_channel_method_label( 'fax' ), 'fax' );
check( 'refusal body', ihq_channel_method_refusal( 'telegram' ), array( 'error' => 'method_disabled', 'method' => 'telegram', 'message' => "Telegram isn't available right now" ) );

reset_state();
respond( 200, feature_body( array( 'email' => true ) ) );
check( 'refuse_if_off: a method on is not refused', refusal_of( function () { return ihq_channel_method_refuse_if_off( 'email' ); } ), array( 'returned' => false ) );
check(
	'refuse_if_off: a method off answers 403 method_disabled',
	refusal_of( function () { return ihq_channel_method_refuse_if_off( 'telegram' ); } ),
	array( 'success' => false, 'data' => array( 'error' => 'method_disabled', 'method' => 'telegram', 'message' => "Telegram isn't available right now" ), 'status' => 403 )
);
reset_state();
respond( 200, '{"methods":null}' );
check( 'refuse_if_off: an absent feature refuses nothing', refusal_of( function () { return ihq_channel_method_refuse_if_off( 'telegram' ); } ), array( 'returned' => false ) );
reset_state();
respond_error( 'timed out' );
check( 'refuse_if_off: a failed call with no earlier answer refuses nothing', refusal_of( function () { return ihq_channel_method_refuse_if_off( 'email' ); } ), array( 'returned' => false ) );

// --- The script module ---
check( 'enqueue: hooked on wp_enqueue_scripts', in_array( array( 'wp_enqueue_scripts', 'ihq_channel_methods_enqueue' ), $GLOBALS['hooks'], true ), true );
reset_state();
ihq_channel_methods_enqueue();
check(
	'enqueue: a logged-out visitor gets the module (theme version when the file is missing)',
	$GLOBALS['enqueued'],
	array( array( 'id' => '@ihq/channel-methods', 'src' => THEME_URI . '/js/ihq-channel-methods.js', 'deps' => array(), 'version' => '9.9.9' ) )
);
reset_state();
$GLOBALS['theme_dir'] = dirname( __DIR__ );
ihq_channel_methods_enqueue();
check( 'enqueue: the version is the file time when the file exists', $GLOBALS['enqueued'][0]['version'], (string) filemtime( dirname( __DIR__ ) . '/js/ihq-channel-methods.js' ) );
reset_state();
$GLOBALS['logged_in'] = true;
ihq_channel_methods_enqueue();
check( 'enqueue: a logged-in user does not', $GLOBALS['enqueued'], array() );

ob_end_clean();
echo implode( "\n", $out ) . "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
