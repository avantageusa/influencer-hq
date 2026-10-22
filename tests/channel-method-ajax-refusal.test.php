<?php
/**
 * ENGR-8014: the email-code and Telegram-widget AJAX handlers refuse a method
 * influencerhq-api lists as off, with 403 method_disabled and "<method> isn't
 * available right now", after the nonce check and before any other work. A
 * method on, an absent feature ({"methods":null}) and a failed call with no
 * earlier answer let the handler run as today. No WordPress bootstrap and no
 * live requests:
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/channel-method-ajax-refusal.test.php
 *
 * Exit code is non-zero on any failure.
 */
ob_start();
ini_set( 'error_log', '/dev/null' );

define( 'ABSPATH', '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'IHQ_ENVIRONMENT', 'test' );
define( 'IHQ_API_BASE_URL', 'https://api.example.test/qc' );
define( 'IHQ_GAME_PORTAL_BASE_URL', 'https://portal.example.test/av-baccarat' );
define( 'IHQ_INFLUENCER_API_KEY', 'server-key' );

const METHODS_URL = 'https://api.example.test/qc/v1/channels/methods';
const GOOD_NONCE  = 'good-nonce';
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
	$GLOBALS['requests']         = array();
	$GLOBALS['methods_response'] = null;
	$GLOBALS['transients']       = array();
	$GLOBALS['options']          = array();
	$GLOBALS['turnstile_checks'] = 0;
	$_POST                       = array();
	$_SERVER['REMOTE_ADDR']      = '203.0.113.10';
}

// --- WordPress stubs. ---
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function __( $s ) { return $s; }
function esc_html( $s ) { return $s; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function wp_parse_url( $u ) { return parse_url( $u ); }
function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $s ) ) ); }
function sanitize_email( $s ) { return trim( $s ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $s ) ); }
function is_email( $s ) { return is_string( $s ) && preg_match( '/^[^@\s]+@[^@\s]+\.[a-z]+$/i', $s ) === 1; }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function wp_verify_nonce( $nonce, $action ) { return GOOD_NONCE === $nonce; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['options'][ $key ] = $value; return true; }
function delete_option( $key ) { unset( $GLOBALS['options'][ $key ] ); return true; }
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl = 0 ) { $GLOBALS['transients'][ $key ] = $value; return true; }
function delete_transient( $key ) { unset( $GLOBALS['transients'][ $key ] ); return true; }
function wp_send_json_success( $data = null, $status = null ) { throw new JsonResponse( true, $data, $status ); }
function wp_send_json_error( $data = null, $status = null ) { throw new JsonResponse( false, $data, $status ); }
function wp_remote_get( $url, $args ) {
	$GLOBALS['requests'][] = $url;
	if ( METHODS_URL !== $url || null === $GLOBALS['methods_response'] ) {
		throw new Exception( 'unexpected request to ' . $url );
	}
	return $GLOBALS['methods_response'];
}
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function wp_remote_retrieve_response_code( $r ) { return $r['status']; }
// Turnstile is on, so a refused request that got past the gate would be seen consuming a check.
function ihq_turnstile_is_configured() { return true; }
function ihq_turnstile_verify_response( $token ) {
	$GLOBALS['turnstile_checks']++;
	return array( 'success' => true );
}

require __DIR__ . '/../inc/ihq-env.php';
require __DIR__ . '/../inc/ihq-channel-methods.php';
require __DIR__ . '/../inc/placeholder-email.php';
require __DIR__ . '/../inc/email-verification-handler.php';
require __DIR__ . '/../inc/telegram-login-handler.php';

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

/** The state of the switch, as influencerhq-api answers it. */
function route_answers( $state, $method ) {
	if ( 'on' === $state ) {
		$GLOBALS['methods_response'] = array( 'status' => 200, 'body' => json_encode( array( 'methods' => array_merge( ALL_OFF, array( $method => true ) ) ) ) );
		return;
	}
	if ( 'off' === $state ) {
		$GLOBALS['methods_response'] = array( 'status' => 200, 'body' => json_encode( array( 'methods' => array_merge( ALL_OFF, array( 'sms' => true ) ) ) ) );
		return;
	}
	if ( 'absent feature' === $state ) {
		$GLOBALS['methods_response'] = array( 'status' => 200, 'body' => '{"methods":null}' );
		return;
	}
	$GLOBALS['methods_response'] = new WP_Error( 'http_request_failed', 'timed out' );
}

/** Call a handler and return what it answered. */
function answer_of( $handler ) {
	try {
		$handler();
	} catch ( JsonResponse $response ) {
		return array( 'success' => $response->success, 'data' => $response->data, 'status' => $response->status );
	}
	return array( 'no answer' );
}

function refused( $method ) {
	return array(
		'success' => false,
		'data'    => array( 'error' => 'method_disabled', 'method' => $method, 'message' => ucfirst( $method ) . " isn't available right now" ),
		'status'  => 403,
	);
}

function today( $message ) {
	return array( 'success' => false, 'data' => array( 'message' => $message ), 'status' => null );
}

const INVALID_EMAIL      = 'Invalid email address';
const ENTER_CODE         = 'Enter the 6-digit code from your email';
const CODE_EXPIRED       = 'This code has expired. Request a new one';
const TELEGRAM_UNSET     = 'Telegram login is not configured';
const TELEGRAM_EXPIRED   = 'Telegram session expired. Please authenticate again.';
const PENDING_TOKEN      = 'pending-token';

$cases = array(
	array(
		'name'    => 'ihq_send_login_code',
		'handler' => 'ihq_handle_send_login_code_ajax',
		'method'  => 'email',
		'post'    => array( 'email' => 'not-an-email' ),
		'today'   => INVALID_EMAIL,
	),
	array(
		'name'    => 'ihq_verify_login_code',
		'handler' => 'ihq_handle_verify_login_code_ajax',
		'method'  => 'email',
		'post'    => array( 'signup_token' => '', 'code' => '' ),
		'today'   => ENTER_CODE,
	),
	array(
		'name'    => 'ihq_send_registration_code by email',
		'handler' => 'ihq_handle_send_registration_code_ajax',
		'method'  => 'email',
		'post'    => array( 'email' => 'not-an-email', 'comm_primary' => 'email' ),
		'today'   => INVALID_EMAIL,
	),
	array(
		'name'    => 'ihq_send_registration_code by Telegram',
		'handler' => 'ihq_handle_send_registration_code_ajax',
		'method'  => 'telegram',
		'post'    => array( 'email' => 'not-an-email', 'comm_primary' => 'telegram' ),
		'today'   => INVALID_EMAIL,
	),
	array(
		'name'    => 'ihq_send_registration_code, Telegram primary with other methods, goes by email',
		'handler' => 'ihq_handle_send_registration_code_ajax',
		'method'  => 'email',
		'post'    => array( 'email' => 'not-an-email', 'comm_primary' => 'telegram', 'comm_methods' => '{"line":"me"}' ),
		'today'   => INVALID_EMAIL,
	),
	array(
		'name'    => 'ihq_verify_registration_code of an emailed code',
		'handler' => 'ihq_handle_verify_registration_code_ajax',
		'method'  => 'email',
		'post'    => array( 'signup_token' => PENDING_TOKEN, 'code' => '123456' ),
		'pending' => array( 'email' => 'ann@example.test', 'telegram_user_id' => 0, 'expires' => 0 ),
		'today'   => CODE_EXPIRED,
	),
	array(
		'name'    => 'ihq_verify_registration_code of a code sent by Telegram',
		'handler' => 'ihq_handle_verify_registration_code_ajax',
		'method'  => 'telegram',
		'post'    => array( 'signup_token' => PENDING_TOKEN, 'code' => '123456' ),
		'pending' => array( 'email' => 'ann@chat.invalid', 'telegram_user_id' => 4242, 'expires' => 0 ),
		'today'   => CODE_EXPIRED,
	),
	array(
		'name'    => 'ihq_verify_registration_code of a record without a Telegram id',
		'handler' => 'ihq_handle_verify_registration_code_ajax',
		'method'  => 'email',
		'post'    => array( 'signup_token' => PENDING_TOKEN, 'code' => '123456' ),
		'pending' => array( 'email' => 'ann@example.test', 'expires' => 0 ),
		'today'   => CODE_EXPIRED,
	),
	array(
		'name'    => 'ihq_telegram_login_nonce',
		'handler' => 'ihq_handle_telegram_login_nonce_ajax',
		'method'  => 'telegram',
		'post'    => array(),
		'today'   => TELEGRAM_UNSET,
	),
	array(
		'name'    => 'ihq_verify_telegram_id_token',
		'handler' => 'ihq_handle_verify_telegram_id_token_ajax',
		'method'  => 'telegram',
		'post'    => array( 'id_token' => 'x.y.z' ),
		'today'   => TELEGRAM_UNSET,
	),
	array(
		'name'    => 'ihq_register_telegram_user',
		'handler' => 'ihq_handle_register_telegram_user_ajax',
		'method'  => 'telegram',
		'post'    => array( 'telegram_session_token' => 'missing' ),
		'today'   => TELEGRAM_EXPIRED,
	),
	array(
		'name'    => 'ihq_login_telegram_user',
		'handler' => 'ihq_handle_login_telegram_user_ajax',
		'method'  => 'telegram',
		'post'    => array( 'telegram_session_token' => 'missing' ),
		'today'   => TELEGRAM_EXPIRED,
	),
);

foreach ( $cases as $case ) {
	foreach ( array( 'off', 'on', 'absent feature', 'failed call' ) as $state ) {
		reset_state();
		route_answers( $state, $case['method'] );
		$_POST = array_merge( array( 'nonce' => GOOD_NONCE ), $case['post'] );
		if ( isset( $case['pending'] ) ) {
			update_option( 'pending_reg_code_' . PENDING_TOKEN, $case['pending'] );
		}
		$expected = 'off' === $state ? refused( $case['method'] ) : today( $case['today'] );
		check( $case['name'] . ', ' . $case['method'] . ' ' . $state, answer_of( $case['handler'] ), $expected );
		check( $case['name'] . ', ' . $case['method'] . ' ' . $state . ': asked influencerhq-api once', $GLOBALS['requests'], array( METHODS_URL ) );
		if ( 'off' === $state ) {
			check( $case['name'] . ' refused: no Turnstile check spent', $GLOBALS['turnstile_checks'], 0 );
		}
	}

	reset_state();
	route_answers( 'off', $case['method'] );
	$_POST = array_merge( $case['post'], array( 'nonce' => 'bad' ) );
	check(
		$case['name'] . ': a bad nonce is refused first, without asking influencerhq-api',
		array( answer_of( $case['handler'] ), $GLOBALS['requests'] ),
		array( array( 'success' => false, 'data' => array( 'message' => 'Invalid security token' ), 'status' => null ), array() )
	);
}

// The other method's switch does not matter.
reset_state();
route_answers( 'on', 'telegram' );
$_POST = array( 'nonce' => GOOD_NONCE, 'email' => 'not-an-email' );
check( 'ihq_send_login_code ignores telegram\'s switch', answer_of( 'ihq_handle_send_login_code_ajax' ), refused( 'email' ) );

reset_state();
route_answers( 'on', 'email' );
$_POST = array( 'nonce' => GOOD_NONCE );
check( 'ihq_telegram_login_nonce ignores email\'s switch', answer_of( 'ihq_handle_telegram_login_nonce_ajax' ), refused( 'telegram' ) );

// With Telegram on, a Telegram registration still takes the Telegram path it took before.
reset_state();
route_answers( 'on', 'telegram' );
$_POST = array( 'nonce' => GOOD_NONCE, 'email' => 'ann@example.test', 'first_name' => 'Ann', 'last_name' => 'Lee', 'comm_primary' => 'telegram' );
check( 'ihq_send_registration_code by Telegram, telegram on: the Telegram path runs', answer_of( 'ihq_handle_send_registration_code_ajax' ), today( 'Please enter your Telegram username' ) );

// A refused verify keeps the pending code, so the visitor can finish once the method is back.
reset_state();
route_answers( 'off', 'email' );
update_option( 'pending_reg_code_' . PENDING_TOKEN, array( 'email' => 'ann@example.test', 'telegram_user_id' => 0, 'expires' => 0 ) );
$_POST = array( 'nonce' => GOOD_NONCE, 'signup_token' => PENDING_TOKEN, 'code' => '123456' );
answer_of( 'ihq_handle_verify_registration_code_ajax' );
check( 'refused verify: the pending registration is kept', isset( $GLOBALS['options'][ 'pending_reg_code_' . PENDING_TOKEN ] ), true );

// Delivery-method helpers.
check( 'delivery: telegram primary, no other method', ihq_registration_code_delivery_method( 'telegram', array() ), 'telegram' );
check( 'delivery: telegram primary with other methods', ihq_registration_code_delivery_method( 'telegram', array( 'line' => 'me' ) ), 'email' );
check( 'delivery: email primary', ihq_registration_code_delivery_method( 'email', array() ), 'email' );
check( 'pending delivery: a Telegram id is telegram', ihq_pending_registration_delivery_method( array( 'telegram_user_id' => 1 ) ), 'telegram' );
check( 'pending delivery: id 0 is email', ihq_pending_registration_delivery_method( array( 'telegram_user_id' => 0 ) ), 'email' );
check( 'pending delivery: a negative id is email', ihq_pending_registration_delivery_method( array( 'telegram_user_id' => -1 ) ), 'email' );
check( 'pending delivery: no id is email', ihq_pending_registration_delivery_method( array() ), 'email' );

ob_end_clean();
echo implode( "\n", $out ) . "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
