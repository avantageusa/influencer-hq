<?php
/**
 * ENGR-6966 boundary tests for inc/email-verification-handler.php: the
 * start-session payload carries referrerCode from the ihq_ref cookie (or an
 * explicit stored code) until a start-session succeeds, the email-link flow
 * stores and replays the code, and the emailed link is minified with a raw
 * fallback. No WordPress bootstrap and no live requests:
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/oauth-start-session-referrer.test.php
 *
 * Exit code is non-zero on any failure.
 */
ob_start(); // keep headers unsent so the real cookie-expiry path runs quietly
ini_set( 'error_log', '/dev/null' );

define( 'ABSPATH', '/' );
define( 'IHQ_ENVIRONMENT', 'test' );
define( 'IHQ_API_BASE_URL', 'https://api.example.test/qc' );
define( 'IHQ_GAME_PORTAL_BASE_URL', 'https://portal.example.test/av-baccarat' );
define( 'IHQ_INFLUENCER_API_KEY', 'server-key' );

const START_SESSION_URL = 'https://api.example.test/qc/account/oauth/start-session';
const MINIFY_URL        = 'https://api.example.test/qc/minify';
const SUCCESS_BODY      = '{"success":true,"data":{"AccessToken":"at","IdToken":"it","RefreshToken":"rt","ExpiresIn":3600}}';

class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
class WP_User {
	public $ID;
	public $user_email = 'ann@example.test';
	public function __construct( $id ) { $this->ID = $id; }
	public function set_role( $role ) {}
}
class StopRequest extends Exception {}

function reset_state() {
	$GLOBALS['requests']  = array();
	$GLOBALS['responses'] = array();
	$GLOBALS['options']   = array();
	$GLOBALS['user_meta'] = array();
	$GLOBALS['mail']      = array();
	$GLOBALS['json']      = null;
	$GLOBALS['redirect']  = null;
	$_COOKIE              = array();
	$_POST                = array();
	$_GET                 = array();
}

// --- WordPress stubs. ---
function add_action( ...$args ) {}
function remove_action( ...$args ) {}
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function wp_parse_url( $u ) { return parse_url( $u ); }
function esc_html( $s ) { return $s; }
function esc_url( $s ) { return $s; }
function esc_url_raw( $s ) { return $s; }
function wp_die( $m ) { throw new StopRequest( 'wp_die: ' . $m ); }
function wp_unslash( $v ) { return stripslashes( $v ); }
function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( $s ) ) ); }
function sanitize_email( $s ) { return trim( $s ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $s ) ); }
function sanitize_user( $s ) { return $s; }
function is_email( $s ) { return strpos( $s, '@' ) !== false; }
function email_exists( $s ) { return false; }
function username_exists( $s ) { return false; }
function wp_create_user( $u, $p, $e ) { return 77; }
// ENGR-7016 reads the stored login back after creation; false keeps this test on the requested username.
function get_userdata( $id ) { return false; }
function wp_generate_password( $len = 12 ) { return 'TOKEN123'; }
function wp_verify_nonce( $n, $a ) { return $n === 'good'; }
function wp_json_encode( $v, $flags = 0 ) { return json_encode( $v, $flags ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function is_ssl() { return true; }
function current_time( $t ) { return '2026-10-02 00:00:00'; }
function home_url( $path = '' ) { return 'https://ihq.example.test' . $path; }
function add_query_arg( ...$args ) {
	if ( is_array( $args[0] ) ) {
		return $args[1] . '?' . http_build_query( $args[0] );
	}
	return $args[2] . '?' . http_build_query( array( $args[0] => $args[1] ) );
}
function get_option( $name ) { return $GLOBALS['options'][ $name ] ?? false; }
function update_option( $name, $value, $autoload = null ) { $GLOBALS['options'][ $name ] = $value; return true; }
function delete_option( $name ) { unset( $GLOBALS['options'][ $name ] ); return true; }
function get_user_meta( $id, $key, $single = false ) { return $GLOBALS['user_meta'][ $id ][ $key ] ?? ''; }
function update_user_meta( $id, $key, $value ) { $GLOBALS['user_meta'][ $id ][ $key ] = $value; return true; }
function get_user_by( $field, $id ) { return new WP_User( $id ); }
function wp_set_current_user( $id ) {}
function wp_set_auth_cookie( $id, $remember ) {}
function wp_redirect( $url ) { $GLOBALS['redirect'] = $url; throw new StopRequest( 'redirect' ); }
function wp_send_json_success( $data = null ) { $GLOBALS['json'] = array( 'success' => true, 'data' => $data ); throw new StopRequest( 'json' ); }
function wp_send_json_error( $data = null ) { $GLOBALS['json'] = array( 'success' => false, 'data' => $data ); throw new StopRequest( 'json' ); }
function wp_mail( $to, $subject, $message, $headers ) { $GLOBALS['mail'][] = array( 'to' => $to, 'message' => $message ); return true; }
function wp_remote_post( $url, $args ) {
	$GLOBALS['requests'][] = array( 'url' => $url, 'args' => $args );
	$queue = &$GLOBALS['responses'][ $url ];
	if ( empty( $queue ) ) {
		throw new Exception( 'unexpected request to ' . $url );
	}
	return array_shift( $queue );
}
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function wp_remote_retrieve_response_code( $r ) { return $r['status']; }

function respond( $url, $status, $body ) { $GLOBALS['responses'][ $url ][] = array( 'status' => $status, 'body' => $body ); }
function requests_to( $url ) {
	return array_values( array_filter( $GLOBALS['requests'], function ( $r ) use ( $url ) { return $r['url'] === $url; } ) );
}
function start_session_payloads() {
	return array_map(
		function ( $r ) { return json_decode( $r['args']['body'], true )['payload']; },
		requests_to( START_SESSION_URL )
	);
}
function run_request( callable $handler ) {
	try {
		$handler();
	} catch ( StopRequest $e ) {
		return $e->getMessage();
	}
	return 'returned';
}

require __DIR__ . '/../inc/ihq-env.php';
require __DIR__ . '/../inc/ihq-referral-attribution.php';
require __DIR__ . '/../inc/ihq-url-minify.php';
require __DIR__ . '/../inc/email-verification-handler.php';

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

$base_payload = array(
	'id'         => 'wpu-5',
	'firstName'  => 'Ann',
	'lastName'   => 'Lee',
	'email'      => 'ann@example.test',
	'countryIso' => 'NL',
);
$success_data = array( 'AccessToken' => 'at', 'IdToken' => 'it', 'RefreshToken' => 'rt', 'ExpiresIn' => 3600 );

// --- No cookie: payload identical to today, no referrerCode key. ---
reset_state();
respond( START_SESSION_URL, 200, SUCCESS_BODY );
$result = ihq_register_oauth_user( 5, 'Ann', 'Lee', 'ann@example.test', 'nl' );
check( 'no cookie: returns data', $result, $success_data );
check( 'no cookie: payload unchanged', start_session_payloads(), array( $base_payload ) );
check(
	'no cookie: request envelope unchanged',
	requests_to( START_SESSION_URL )[0]['args'],
	array(
		'headers'   => array( 'Authorization' => 'milos_testing', 'Content-Type' => 'application/json', 'x-api-key' => 'server-key' ),
		'body'      => json_encode( array( 'oauthLoginType' => 'InfluencerHq', 'payload' => $base_payload ) ),
		'timeout'   => 30,
		'sslverify' => true,
	)
);

// --- Cookie present: referrerCode sent, cookie cleared on success. ---
reset_state();
$_COOKIE = array( 'ihq_ref' => 'alice', 'other' => 'keep' );
respond( START_SESSION_URL, 200, SUCCESS_BODY );
$result = ihq_register_oauth_user( 5, 'Ann', 'Lee', 'ann@example.test', 'nl' );
check( 'cookie: returns data', $result, $success_data );
check( 'cookie: referrerCode sent', start_session_payloads(), array( $base_payload + array( 'referrerCode' => 'alice' ) ) );
check( 'cookie: cleared after success, other cookies kept', $_COOKIE, array( 'other' => 'keep' ) );

// --- Cookie is sanitised and capped before it is sent. ---
reset_state();
$_COOKIE = array( 'ihq_ref' => '<b>' . str_repeat( 'q', 70 ) . '</b>' );
respond( START_SESSION_URL, 200, SUCCESS_BODY );
ihq_register_oauth_user( 5, 'Ann', 'Lee', 'ann@example.test', 'nl' );
check( 'cookie: sanitised + 64 cap', start_session_payloads(), array( $base_payload + array( 'referrerCode' => str_repeat( 'q', 64 ) ) ) );

// --- Cookie that sanitises to nothing: no referrerCode key. ---
reset_state();
$_COOKIE = array( 'ihq_ref' => '<i></i>' );
respond( START_SESSION_URL, 200, SUCCESS_BODY );
ihq_register_oauth_user( 5, 'Ann', 'Lee', 'ann@example.test', 'nl' );
check( 'empty-after-sanitise cookie: payload unchanged', start_session_payloads(), array( $base_payload ) );
check( 'empty-after-sanitise cookie: still cleared on success', $_COOKIE, array() );

// --- Explicit code wins over the cookie. ---
reset_state();
$_COOKIE = array( 'ihq_ref' => 'alice' );
respond( START_SESSION_URL, 200, SUCCESS_BODY );
ihq_register_oauth_user( 5, 'Ann', 'Lee', 'ann@example.test', 'nl', array(), array(), array( 'referrer_code' => 'bob' ) );
check( 'explicit: wins over cookie', start_session_payloads(), array( $base_payload + array( 'referrerCode' => 'bob' ) ) );
check( 'explicit: cookie still cleared on success', $_COOKIE, array() );

// --- Failures keep the cookie for the retry. ---
$failures = array(
	'success false' => array( 200, '{"success":false,"error":"nope"}' ),
	'HTTP 500'      => array( 500, '{"message":"boom"}' ),
	'no data'       => array( 200, '{"success":true,"data":{}}' ),
	'success truthy not true' => array( 200, '{"success":1,"data":{"AccessToken":"at"}}' ),
	'not json'      => array( 502, '<html>bad gateway</html>' ),
);
foreach ( $failures as $label => $response ) {
	reset_state();
	$_COOKIE = array( 'ihq_ref' => 'alice' );
	respond( START_SESSION_URL, $response[0], $response[1] );
	$result = ihq_register_oauth_user( 5, 'Ann', 'Lee', 'ann@example.test', 'nl' );
	check( "failure ($label): returns false", $result, false );
	check( "failure ($label): code was sent", start_session_payloads(), array( $base_payload + array( 'referrerCode' => 'alice' ) ) );
	check( "failure ($label): cookie kept", $_COOKIE, array( 'ihq_ref' => 'alice' ) );
}
reset_state();
$_COOKIE = array( 'ihq_ref' => 'alice' );
$GLOBALS['responses'][ START_SESSION_URL ][] = new WP_Error( 'http', 'timeout' );
check( 'failure (WP_Error): returns false', ihq_register_oauth_user( 5, 'Ann', 'Lee', 'ann@example.test', 'nl' ), false );
check( 'failure (WP_Error): cookie kept', $_COOKIE, array( 'ihq_ref' => 'alice' ) );

// --- Fail, then retry succeeds: both carry the code, cleared after the second. ---
reset_state();
$_COOKIE = array( 'ihq_ref' => 'alice' );
respond( START_SESSION_URL, 500, '{}' );
respond( START_SESSION_URL, 200, SUCCESS_BODY );
ihq_register_oauth_user( 5, 'Ann', 'Lee', 'ann@example.test', 'nl' );
ihq_register_oauth_user( 5, 'Ann', 'Lee', 'ann@example.test', 'nl' );
check(
	'retry: both attempts carry the code',
	start_session_payloads(),
	array( $base_payload + array( 'referrerCode' => 'alice' ), $base_payload + array( 'referrerCode' => 'alice' ) )
);
check( 'retry: cleared after the success', $_COOKIE, array() );

// --- AI Coach: create then sign-in in one request; the second call does not resend. ---
reset_state();
$_COOKIE = array( 'ihq_ref' => 'alice' );
respond( START_SESSION_URL, 200, SUCCESS_BODY );
respond( START_SESSION_URL, 200, SUCCESS_BODY );
$GLOBALS['user_meta'][77] = array( 'first_name' => 'Ann', 'last_name' => 'Lee' );
$user_id = ihq_create_influencer_user_from_registration_data(
	array( 'email' => 'ann@example.test', 'first_name' => 'Ann', 'last_name' => 'Lee', 'country_iso' => 'NL' )
);
ihq_refresh_influencer_oauth_tokens( $user_id, 'NL' );
$aicoach_base = array( 'id' => 'wpu-77' ) + $base_payload;
check(
	'AI Coach double call: only the first carries the code',
	start_session_payloads(),
	array( $aicoach_base + array( 'referrerCode' => 'alice' ), $aicoach_base )
);

// --- Registration helper forwards an explicit referrer_code. ---
reset_state();
$_COOKIE = array( 'ihq_ref' => 'alice' );
respond( START_SESSION_URL, 200, SUCCESS_BODY );
ihq_create_influencer_user_from_registration_data(
	array( 'email' => 'ann@example.test', 'first_name' => 'Ann', 'last_name' => 'Lee', 'country_iso' => 'NL', 'referrer_code' => 'stored' )
);
check( 'create helper: explicit referrer_code forwarded', start_session_payloads(), array( $aicoach_base + array( 'referrerCode' => 'stored' ) ) );

// --- Lander email form: code stored in the pending record, minified link emailed. ---
$raw_link = 'https://ihq.example.test/portal/portal-home/?verify_token=TOKEN123&action=verify_email&welcome=true';
function submit_lander_form() {
	$_POST = array( 'nonce' => 'good', 'email' => 'new@example.test', 'first_name' => 'New', 'last_name' => 'User', 'country_iso' => 'NL' );
	return run_request( 'handle_verification_email' );
}

reset_state();
$_COOKIE = array( 'ihq_ref' => ' <b>erin</b> ' );
respond( MINIFY_URL, 200, '{"shortUrl":"https://s.example.test/r/abc123","shortCode":"abc123","qrCodeSvg":"<svg/>"}' );
check( 'email form: request ends with JSON success', submit_lander_form(), 'json' );
check( 'email form: JSON success', $GLOBALS['json'], array( 'success' => true, 'data' => 'Verification email sent successfully' ) );
check( 'email form: sanitised code stored in pending record', $GLOBALS['options']['pending_registration_TOKEN123']['referrer_code'], 'erin' );
check( 'email form: cookie not cleared by the email step', $_COOKIE, array( 'ihq_ref' => ' <b>erin</b> ' ) );
check(
	'email form: minify called with the raw verify link',
	requests_to( MINIFY_URL )[0]['args'],
	array(
		'headers'     => array( 'Content-Type' => 'application/json' ),
		'body'        => json_encode( array( 'originalUrl' => $raw_link ) ),
		'timeout'     => 5,
		'redirection' => 0,
		'sslverify'   => true,
	)
);
$message = $GLOBALS['mail'][0]['message'];
check( 'email form: short link appears in both email spots', substr_count( $message, 'https://s.example.test/r/abc123' ), 3 );
check( 'email form: raw link not emailed', strpos( $message, 'verify_token=' ), false );

reset_state();
respond( MINIFY_URL, 503, '{"message":"down"}' );
submit_lander_form();
check( 'email form, no cookie: empty code stored', $GLOBALS['options']['pending_registration_TOKEN123']['referrer_code'], '' );
check( 'email form, minify down: raw link emailed', substr_count( $GLOBALS['mail'][0]['message'], $raw_link ), 3 );
check( 'email form, minify down: still JSON success', $GLOBALS['json']['success'], true );

// --- verify_email in another browser: stored code is sent with no cookie present. ---
reset_state();
$GLOBALS['options']['pending_registration_TOKEN123'] = array(
	'email'         => 'new@example.test',
	'password'      => '',
	'first_name'    => 'New',
	'last_name'     => 'User',
	'country_iso'   => 'NL',
	'referrer_code' => 'erin',
	'expires'       => time() + 3600,
);
$_GET = array( 'action' => 'verify_email', 'verify_token' => 'TOKEN123' );
respond( START_SESSION_URL, 200, SUCCESS_BODY );
check( 'verify link: request redirects', run_request( 'handle_email_verification_and_user_creation' ), 'redirect' );
check(
	'verify link: stored code sent from a browser without the cookie',
	start_session_payloads(),
	array( array( 'id' => 'wpu-77', 'firstName' => 'New', 'lastName' => 'User', 'email' => 'new@example.test', 'countryIso' => 'NL', 'referrerCode' => 'erin' ) )
);
check( 'verify link: pending record deleted', isset( $GLOBALS['options']['pending_registration_TOKEN123'] ), false );

// --- verify_email for a record written before this change (no referrer_code key). ---
reset_state();
$GLOBALS['options']['pending_registration_TOKEN123'] = array(
	'email' => 'old@example.test', 'password' => '', 'first_name' => 'Old', 'last_name' => 'User', 'country_iso' => 'NL', 'expires' => time() + 3600,
);
$_GET = array( 'action' => 'verify_email', 'verify_token' => 'TOKEN123' );
respond( START_SESSION_URL, 200, SUCCESS_BODY );
run_request( 'handle_email_verification_and_user_creation' );
check(
	'verify link, legacy record: payload unchanged',
	start_session_payloads(),
	array( array( 'id' => 'wpu-77', 'firstName' => 'Old', 'lastName' => 'User', 'email' => 'old@example.test', 'countryIso' => 'NL' ) )
);

ob_end_clean();
echo implode( "\n", $out ) . "\n";
echo $fail === 0 ? "ALL PASS\n" : "$fail FAILED\n";
exit( $fail === 0 ? 0 : 1 );
