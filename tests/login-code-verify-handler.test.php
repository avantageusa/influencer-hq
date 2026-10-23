<?php
/**
 * PO-3262 tests for the Login-page code verify AJAX handler,
 * ihq_handle_verify_login_code_ajax() in inc/email-verification-handler.php: the
 * per-IP 3-strike lockout, expiry, single use, the per-IP mutex, and the
 * fail-closed counter. Drives the handler end to end with WordPress, $wpdb and the
 * start-session request stubbed. No WordPress bootstrap and no live requests:
 *
 *     docker run --name ihq-t -v "$PWD":/t -w /t php:8.2-cli php tests/login-code-verify-handler.test.php
 *
 * The stubs follow tests/visitor-code-verify.test.php; this file adds an ordered
 * event log so the mutex release can be checked against the sign-in.
 *
 * Exit code is non-zero on any failure.
 */
ob_start();
ini_set( 'error_log', '/dev/null' );

define( 'ABSPATH', '/' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'IHQ_API_BASE_URL', 'https://api.example.test/qc' );
define( 'IHQ_GAME_PORTAL_BASE_URL', 'https://portal.example.test/av-baccarat' );
define( 'IHQ_INFLUENCER_API_KEY', 'server-key' );

const START_SESSION_URL = 'https://api.example.test/qc/account/oauth/start-session';
const SUCCESS_BODY      = '{"success":true,"data":{"AccessToken":"at","IdToken":"it","RefreshToken":"rt","ExpiresIn":3600,"ssoCode":"sso-1"}}';
const FAILURE_BODY      = '{"success":false,"message":"upstream down"}';
const PORTAL_HOME_URL   = 'https://ihq.example.test/portal/portal-home/';
const INFLUENCER_ID     = 5;
const OTHER_ID          = 9;
const ADMIN_ID          = 6;
const INFLUENCER_EMAIL  = 'influencer@example.test';
const OTHER_EMAIL       = 'other@example.test';
const ADMIN_EMAIL       = 'admin@example.test';
const CLIENT_IP         = '203.0.113.10';
const OTHER_IP          = '198.51.100.7';
const TOKEN             = 'tok_abcdefghijklmnopqrstuvwxyz0123';
const RIGHT_CODE        = '482913';
const WRONG_CODES       = array( '000001', '000002', '000003', '000004' );
const MUTEX_TIMEOUT     = 5;

const NONCE_MESSAGE    = 'Invalid security token';
const SHAPE_MESSAGE    = 'Enter the 6-digit code from your email';
const BUSY_MESSAGE     = 'Please wait a moment and try again.';
const LOCKOUT_MESSAGE  = 'You are temporarily locked out, try again in 15 minutes';
const INVALID_MESSAGE  = 'Invalid or expired code. Request a new one';
const EXPIRED_MESSAGE  = 'This code has expired. Request a new one';
const MISMATCH_MESSAGE = 'That code does not match. Check your email and try again';

class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
class WP_User {
	public $ID;
	public $user_email;
	public $roles;
	public function __construct( $id, $email = '', array $roles = array() ) {
		$this->ID         = $id;
		$this->user_email = $email;
		$this->roles      = $roles;
	}
}
class JsonResponse extends Exception {
	public $success;
	public $data;
	public function __construct( $success, $data ) {
		parent::__construct( 'json' );
		$this->success = $success;
		$this->data    = $data;
	}
}

/** Enough of $wpdb for the atomic counter and the advisory locks; lock calls go to the event log. */
class FakeWpdb {
	public $options    = 'wp_options';
	public $insert_id  = 0;
	public $last_error = '';
	public function prepare( $query, ...$args ) { return array( 'query' => $query, 'args' => $args ); }
	public function get_var( $prepared ) {
		if ( strpos( $prepared['query'], 'GET_LOCK' ) !== false ) {
			$GLOBALS['events'][] = 'GET_LOCK ' . $prepared['args'][0] . ' ' . $prepared['args'][1];
			return $GLOBALS['mutex_available'] ? 1 : 0;
		}
		if ( strpos( $prepared['query'], 'SELECT CAST(option_value AS UNSIGNED)' ) === 0 ) {
			$this->last_error = $GLOBALS['select_error'];
			if ( $this->last_error !== '' ) {
				return null;
			}
			return isset( $GLOBALS['options'][ $prepared['args'][0] ] ) ? (string) $GLOBALS['options'][ $prepared['args'][0] ] : null;
		}
		throw new Exception( 'unexpected get_var ' . $prepared['query'] );
	}
	public function query( $prepared ) {
		if ( strpos( $prepared['query'], 'RELEASE_LOCK' ) !== false ) {
			$GLOBALS['events'][] = 'RELEASE_LOCK ' . $prepared['args'][0];
			return 1;
		}
		if ( strpos( $prepared['query'], 'INSERT INTO' ) === 0 ) {
			$name             = $prepared['args'][0];
			$this->last_error = $GLOBALS['upsert_error'];
			if ( $this->last_error !== '' ) {
				return false;
			}
			if ( ! isset( $GLOBALS['options'][ $name ] ) ) {
				$GLOBALS['options'][ $name ] = 1;
				return 1;
			}
			$GLOBALS['options'][ $name ] = (int) $GLOBALS['options'][ $name ] + 1;
			return 2;
		}
		throw new Exception( 'unexpected query ' . $prepared['query'] );
	}
}
$GLOBALS['wpdb'] = new FakeWpdb();

function reset_state() {
	$GLOBALS['options']          = array();
	$GLOBALS['object_cache']     = array();
	$GLOBALS['upsert_error']     = '';
	$GLOBALS['select_error']     = '';
	$GLOBALS['wpdb']->last_error = '';
	$GLOBALS['transients']       = array();
	$GLOBALS['transient_ttls']   = array();
	$GLOBALS['events']           = array();
	$GLOBALS['nonce_checks']     = array();
	$GLOBALS['requests']         = array();
	$GLOBALS['start_session']    = array( 'status' => 200, 'body' => SUCCESS_BODY );
	$GLOBALS['user_meta']        = array(
		INFLUENCER_ID => array( 'first_name' => 'Ina', 'last_name' => 'Fluence' ),
		OTHER_ID      => array( 'first_name' => 'Otto', 'last_name' => 'Other' ),
	);
	$GLOBALS['mutex_available']  = true;
	$GLOBALS['users']            = array(
		INFLUENCER_ID => new WP_User( INFLUENCER_ID, INFLUENCER_EMAIL, array( 'influencer' ) ),
		OTHER_ID      => new WP_User( OTHER_ID, OTHER_EMAIL, array( 'influencer' ) ),
		ADMIN_ID      => new WP_User( ADMIN_ID, ADMIN_EMAIL, array( 'administrator' ) ),
	);
	$_POST                       = array();
	$_COOKIE                     = array();
	$_SERVER['REMOTE_ADDR']      = CLIENT_IP;
	unset( $_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'] );
	putenv( 'IHQ_ENVIRONMENT=prod' );
}

// --- WordPress stubs. ---
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function remove_action( ...$args ) {}
function __( $s ) { return $s; }
function esc_html( $s ) { return $s; }
function esc_url_raw( $s ) { return (string) $s; }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
function wp_parse_url( $u ) { return parse_url( $u ); }
function is_ssl() { return true; }
function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $s ) ) ); }
function sanitize_email( $s ) { return trim( $s ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $s ) ); }
function get_user_by( $field, $value ) {
	if ( $field === 'email' ) {
		foreach ( $GLOBALS['users'] as $user ) {
			if ( strtolower( $user->user_email ) === strtolower( $value ) ) {
				return $user;
			}
		}
		return false;
	}
	return $GLOBALS['users'][ (int) $value ] ?? false;
}
function wp_salt( $scheme = '' ) { return 'salt-' . $scheme; }
function wp_json_encode( $v, $flags = 0 ) { return json_encode( $v, $flags ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function get_user_meta( $id, $key, $single = false ) { return $GLOBALS['user_meta'][ $id ][ $key ] ?? ''; }
function update_user_meta( $id, $key, $value ) { $GLOBALS['user_meta'][ $id ][ $key ] = $value; return true; }
// Options behave like WordPress with a persistent object cache: $GLOBALS['options']
// is the table, and a miss is remembered in 'notoptions' until the options API
// writes the key. delete_option() adds the key to notoptions (WordPress 6.4+).
function notoptions() { return $GLOBALS['object_cache']['options']['notoptions'] ?? false; }
function forget_notoption( $key ) {
	$notoptions = notoptions();
	if ( is_array( $notoptions ) && isset( $notoptions[ $key ] ) ) {
		unset( $notoptions[ $key ] );
		$GLOBALS['object_cache']['options']['notoptions'] = $notoptions;
	}
}
function get_option( $key, $default = false ) {
	$notoptions = notoptions();
	if ( isset( $notoptions[ $key ] ) ) {
		return $default;
	}
	if ( array_key_exists( $key, $GLOBALS['options'] ) ) {
		return $GLOBALS['options'][ $key ];
	}
	$GLOBALS['object_cache']['options']['notoptions'][ $key ] = true;
	return $default;
}
// Like wpdb, every options write runs a query, and each query resets last_error.
function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['wpdb']->last_error = '';
	$GLOBALS['options'][ $key ]  = $value;
	forget_notoption( $key );
	return true;
}
function delete_option( $key ) {
	if ( ! array_key_exists( $key, $GLOBALS['options'] ) ) {
		return false;
	}
	$GLOBALS['wpdb']->last_error = '';
	unset( $GLOBALS['options'][ $key ] );
	$GLOBALS['object_cache']['options']['notoptions'][ $key ] = true;
	return true;
}
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl = 0 ) {
	$GLOBALS['transients'][ $key ]     = $value;
	$GLOBALS['transient_ttls'][ $key ] = $ttl;
	return true;
}
function delete_transient( $key ) { unset( $GLOBALS['transients'][ $key ], $GLOBALS['transient_ttls'][ $key ] ); return true; }
function wp_cache_get( $key, $group = '' ) { return $GLOBALS['object_cache'][ $group ][ $key ] ?? false; }
function wp_cache_set( $key, $value, $group = '' ) { $GLOBALS['object_cache'][ $group ][ $key ] = $value; return true; }
function wp_cache_delete( $key, $group = '' ) { unset( $GLOBALS['object_cache'][ $group ][ $key ] ); return true; }
function wp_verify_nonce( $nonce, $action ) {
	$GLOBALS['nonce_checks'][] = array( $nonce, $action );
	return $nonce === 'good-nonce' && $action === 'ihq_login_code_nonce';
}
function wp_set_current_user( $id ) { $GLOBALS['events'][] = 'wp_set_current_user ' . $id; }
function wp_set_auth_cookie( $id, $remember = false ) { $GLOBALS['events'][] = 'wp_set_auth_cookie ' . $id . ' ' . var_export( $remember, true ); }
function wp_send_json_success( $data = null ) { throw new JsonResponse( true, $data ); }
function wp_send_json_error( $data = null ) { throw new JsonResponse( false, $data ); }
function wp_remote_post( $url, $args ) {
	$GLOBALS['events'][]   = 'start-session ' . $url;
	$GLOBALS['requests'][] = array( 'url' => $url, 'body' => json_decode( $args['body'], true ) );
	return $GLOBALS['start_session'];
}
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function wp_remote_retrieve_response_code( $r ) { return $r['status']; }
// Loaded from inc/visitor-intent-handler.php on a real site.
function ihq_portal_redirect_url_for_context( $context = 'account' ) {
	$GLOBALS['events'][] = 'redirect ' . $context;
	return PORTAL_HOME_URL;
}

putenv( 'IHQ_ENVIRONMENT=prod' ); // ihq-env.php checks required keys on load
require __DIR__ . '/../inc/ihq-env.php';
require __DIR__ . '/../inc/ihq-referral-attribution.php';
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

// --- Helpers that drive the handler. ---
function call_handler( array $post ) {
	$_POST = $post;
	try {
		ihq_handle_verify_login_code_ajax();
	} catch ( JsonResponse $response ) {
		return array( 'success' => $response->success, 'data' => $response->data );
	}
	throw new Exception( 'handler returned without a JSON response' );
}
function verify_code( $code, array $extra = array() ) {
	return call_handler( array_merge( array( 'nonce' => 'good-nonce', 'signup_token' => TOKEN, 'code' => $code ), $extra ) );
}
function error_with( $message ) { return array( 'success' => false, 'data' => array( 'message' => $message ) ); }
function signed_in() { return array( 'success' => true, 'data' => array( 'redirect_url' => PORTAL_HOME_URL ) ); }

// Key names derived here, not through the helpers under test.
function pending_key() { return 'pending_login_code_' . TOKEN; }
function email_map_key( $email = INFLUENCER_EMAIL ) { return 'ihq_pending_login_email_' . md5( strtolower( $email ) ); }
function fail_key( $ip = CLIENT_IP ) { return 'ihq_login_verify_fail_' . md5( $ip ); }
function fail_timeout_key( $ip = CLIENT_IP ) { return 'ihq_login_verify_fail_timeout_' . md5( $ip ); }
function lock_key( $ip = CLIENT_IP ) { return 'ihq_login_verify_lock_' . md5( $ip ); }
function mutex_name( $ip = CLIENT_IP ) { return 'ihq_lv_' . md5( fail_key( $ip ) ); }
function mutex_round_trip( $ip = CLIENT_IP ) {
	return array( 'GET_LOCK ' . mutex_name( $ip ) . ' ' . MUTEX_TIMEOUT, 'RELEASE_LOCK ' . mutex_name( $ip ) );
}
function sign_in_events( $user_id, $ip = CLIENT_IP ) {
	return array_merge(
		mutex_round_trip( $ip ),
		array(
			'wp_set_current_user ' . $user_id,
			'wp_set_auth_cookie ' . $user_id . ' false',
			'start-session ' . START_SESSION_URL,
			'redirect portal_home',
		)
	);
}

/** The record ihq_issue_and_send_login_code_for_influencer() stores, with its email-to-token map. */
function seed_pending( array $overrides = array() ) {
	$record = array_merge(
		array(
			'email'     => INFLUENCER_EMAIL,
			'user_id'   => INFLUENCER_ID,
			'code_hash' => hash_hmac( 'sha256', RIGHT_CODE, 'salt-ihq_login_code' . TOKEN ),
			'expires'   => time() + 600,
			'timestamp' => time(),
		),
		$overrides
	);
	$GLOBALS['options'][ pending_key() ]                     = $record;
	$GLOBALS['options'][ email_map_key( $record['email'] ) ] = TOKEN;
	return $record;
}
function pending_and_map_kept( $email = INFLUENCER_EMAIL ) {
	return isset( $GLOBALS['options'][ pending_key() ], $GLOBALS['options'][ email_map_key( $email ) ] );
}
function counter_options() {
	return array_values( array_filter( array_keys( $GLOBALS['options'] ), function ( $k ) { return strpos( $k, 'ihq_login_verify_' ) === 0; } ) );
}
/** Expected fail_timeout window: the handler stamps time() + 900 somewhere between $before and now. */
function timeout_in_window( $before ) {
	$timeout = (int) $GLOBALS['options'][ fail_timeout_key() ];
	return $timeout >= $before + IHQ_LOGIN_CODE_LOCKOUT_SECONDS && $timeout <= time() + IHQ_LOGIN_CODE_LOCKOUT_SECONDS;
}
function last_logged( $file ) {
	$lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	return preg_replace( '/^\[[^\]]*\] /', '', (string) end( $lines ) );
}

// === 1. Nonce: missing or wrong refuses before the mutex, the lock check or the code. ===
reset_state();
seed_pending();
check( 'nonce missing: refused', call_handler( array( 'signup_token' => TOKEN, 'code' => RIGHT_CODE ) ), error_with( NONCE_MESSAGE ) );
check( 'nonce missing: wp_verify_nonce not reached', $GLOBALS['nonce_checks'], array() );
check( 'nonce wrong: refused', verify_code( RIGHT_CODE, array( 'nonce' => 'stale-nonce' ) ), error_with( NONCE_MESSAGE ) );
check( 'nonce wrong: checked against the login-code action', $GLOBALS['nonce_checks'], array( array( 'stale-nonce', 'ihq_login_code_nonce' ) ) );
check( 'nonce: no mutex, no sign-in, no start-session', $GLOBALS['events'], array() );
check( 'nonce: code not consumed', pending_and_map_kept(), true );
check( 'nonce: no counter written', counter_options(), array() );

// === 2. Token / code shape: refused before the mutex; non-digits are stripped. ===
$bad_shapes = array(
	'token missing' => array( 'nonce' => 'good-nonce', 'code' => RIGHT_CODE ),
	'token empty'   => array( 'nonce' => 'good-nonce', 'signup_token' => '', 'code' => RIGHT_CODE ),
	'code missing'  => array( 'nonce' => 'good-nonce', 'signup_token' => TOKEN ),
	'code 5 digits' => array( 'nonce' => 'good-nonce', 'signup_token' => TOKEN, 'code' => '48291' ),
	'code 7 digits' => array( 'nonce' => 'good-nonce', 'signup_token' => TOKEN, 'code' => '4829130' ),
	'code letters'  => array( 'nonce' => 'good-nonce', 'signup_token' => TOKEN, 'code' => 'abcdef' ),
);
foreach ( $bad_shapes as $label => $post ) {
	reset_state();
	seed_pending();
	check( "shape $label: refused", call_handler( $post ), error_with( SHAPE_MESSAGE ) );
	check( "shape $label: no mutex, no sign-in", $GLOBALS['events'], array() );
	check( "shape $label: code not consumed, no counter", array( pending_and_map_kept(), counter_options() ), array( true, array() ) );
}
reset_state();
seed_pending();
check( 'shape: separators stripped, code accepted', verify_code( '48-29 13' ), signed_in() );
check( 'shape: separators stripped, signed in', $GLOBALS['events'], sign_in_events( INFLUENCER_ID ) );

// === 3. Mutex busy: refused without touching the lock check, the code or the counter. ===
reset_state();
seed_pending();
$GLOBALS['mutex_available'] = false;
check( 'mutex busy: refused', verify_code( RIGHT_CODE ), error_with( BUSY_MESSAGE ) );
check( 'mutex busy: one GET_LOCK, nothing to release, no sign-in', $GLOBALS['events'], array( 'GET_LOCK ' . mutex_name() . ' ' . MUTEX_TIMEOUT ) );
check( 'mutex busy: code not consumed, no counter', array( pending_and_map_kept(), counter_options() ), array( true, array() ) );

// === 4. Already locked: the right code is refused and nothing is counted or consumed. ===
reset_state();
seed_pending();
$GLOBALS['transients'][ lock_key() ] = 1;
check( 'locked: right code refused with the lockout message', verify_code( RIGHT_CODE ), error_with( LOCKOUT_MESSAGE ) );
check( 'locked: wrong code refused with the lockout message', verify_code( WRONG_CODES[0] ), error_with( LOCKOUT_MESSAGE ) );
check( 'locked: mutex released each time, no sign-in', $GLOBALS['events'], array_merge( mutex_round_trip(), mutex_round_trip() ) );
check( 'locked: code not consumed', pending_and_map_kept(), true );
check( 'locked: no counter written', counter_options(), array() );
check( 'locked: lock still set', $GLOBALS['transients'], array( lock_key() => 1 ) );

// === 5. No pending code for the token: invalid, nothing counted. ===
reset_state();
check( 'unknown token: refused', verify_code( RIGHT_CODE ), error_with( INVALID_MESSAGE ) );
check( 'unknown token: mutex released, no sign-in', $GLOBALS['events'], mutex_round_trip() );
check( 'unknown token: no counter written', counter_options(), array() );
reset_state();
$GLOBALS['options'][ pending_key() ] = array( 'email' => '', 'code_hash' => 'x', 'expires' => time() + 600 );
check( 'record without email: refused', verify_code( RIGHT_CODE ), error_with( INVALID_MESSAGE ) );
check( 'record without email: mutex released', $GLOBALS['events'], mutex_round_trip() );
reset_state();
$GLOBALS['options'][ pending_key() ] = 'not-an-array';
check( 'record not an array: refused', verify_code( RIGHT_CODE ), error_with( INVALID_MESSAGE ) );
check( 'record not an array: mutex released', $GLOBALS['events'], mutex_round_trip() );

// === 6. Expired: checked before the compare, so a wrong code is not counted. ===
foreach ( array( 'wrong' => WRONG_CODES[0], 'right' => RIGHT_CODE ) as $label => $code ) {
	reset_state();
	seed_pending( array( 'expires' => time() - 1 ) );
	check( "expired, $label code: expired message", verify_code( $code ), error_with( EXPIRED_MESSAGE ) );
	check( "expired, $label code: record and email map deleted", array_key_exists( pending_key(), $GLOBALS['options'] ) || array_key_exists( email_map_key(), $GLOBALS['options'] ), false );
	check( "expired, $label code: not counted", counter_options(), array() );
	check( "expired, $label code: mutex released, no sign-in", $GLOBALS['events'], mutex_round_trip() );
}
// expires is the last valid second (`time() > expires`). Retried until seed and verify
// fall in the same second, so the boundary is exact rather than timing-dependent.
do {
	reset_state();
	$second = time();
	seed_pending( array( 'expires' => $second ) );
	$response = verify_code( RIGHT_CODE );
} while ( time() !== $second );
check( 'expiry boundary: code still valid in its last second', $response, signed_in() );

// === 7. 1st and 2nd wrong code: mismatch message, counter at 1 then 2, no lock. ===
reset_state();
seed_pending();
foreach ( array( 1, 2 ) as $attempt ) {
	$before = time();
	check( "wrong code $attempt: mismatch message", verify_code( WRONG_CODES[ $attempt - 1 ] ), error_with( MISMATCH_MESSAGE ) );
	check( "wrong code $attempt: counter", $GLOBALS['options'][ fail_key() ], $attempt );
	check( "wrong code $attempt: counter expiry is lockout window from now", timeout_in_window( $before ), true );
	check( "wrong code $attempt: not locked", $GLOBALS['transients'], array() );
}
check( 'wrong codes 1-2: only the IP counter rows written', counter_options(), array( fail_key(), fail_timeout_key() ) );
check( 'wrong codes 1-2: code kept for a retry', pending_and_map_kept(), true );
check( 'wrong codes 1-2: mutex released each time, no sign-in', $GLOBALS['events'], array_merge( mutex_round_trip(), mutex_round_trip() ) );

// === 8. 3rd wrong code: lockout message, lock set for 15 minutes, fail rows deleted. ===
check( 'wrong code 3: lockout message', verify_code( WRONG_CODES[2] ), error_with( LOCKOUT_MESSAGE ) );
check( 'wrong code 3: lock transient set', $GLOBALS['transients'], array( lock_key() => 1 ) );
check( 'wrong code 3: lock lasts the lockout window', $GLOBALS['transient_ttls'][ lock_key() ], IHQ_LOGIN_CODE_LOCKOUT_SECONDS );
check( 'wrong code 3: fail rows deleted', counter_options(), array() );
check( 'wrong code 3: code kept (as built)', pending_and_map_kept(), true );
check( 'after lockout: right code refused', verify_code( RIGHT_CODE ), error_with( LOCKOUT_MESSAGE ) );
check( 'after lockout: still no counter rows', counter_options(), array() );
check( 'wrong codes 1-3 + locked retry: mutex released 4 times, no sign-in', $GLOBALS['events'], array_merge( mutex_round_trip(), mutex_round_trip(), mutex_round_trip(), mutex_round_trip() ) );

// === 9. The lock is per IP: another IP signs in with the same code. ===
$_SERVER['REMOTE_ADDR'] = OTHER_IP;
$GLOBALS['events']      = array();
check( 'other IP: right code signs in', verify_code( RIGHT_CODE ), signed_in() );
check( 'other IP: its own mutex, signed in', $GLOBALS['events'], sign_in_events( INFLUENCER_ID, OTHER_IP ) );
check( 'other IP: first IP still locked', $GLOBALS['transients'], array( lock_key() => 1 ) );

// === 10. Right code within the limits: signed in, counters cleared, code consumed. ===
reset_state();
seed_pending();
verify_code( WRONG_CODES[0] );
verify_code( WRONG_CODES[1] );
$GLOBALS['transients'][ fail_key() ] = 2; // legacy transient counter from the first PO-3262 revision
$GLOBALS['events']                   = array();
check( 'right code: signed in', verify_code( RIGHT_CODE, array( 'country_iso' => 'br' ) ), signed_in() );
check( 'right code: mutex released before sign-in, then start-session', $GLOBALS['events'], sign_in_events( INFLUENCER_ID ) );
check( 'right code: fail rows deleted', counter_options(), array() );
check( 'right code: lock and legacy transients cleared', $GLOBALS['transients'], array() );
check( 'right code: record and email map deleted', array_key_exists( pending_key(), $GLOBALS['options'] ) || array_key_exists( email_map_key(), $GLOBALS['options'] ), false );
check(
	'right code: start-session payload',
	$GLOBALS['requests'],
	array(
		array(
			'url'  => START_SESSION_URL,
			'body' => array(
				'oauthLoginType' => 'InfluencerHq',
				'payload'        => array(
					'id'         => 'wpu-' . INFLUENCER_ID,
					'firstName'  => 'Ina',
					'lastName'   => 'Fluence',
					'email'      => INFLUENCER_EMAIL,
					'countryIso' => 'BR',
				),
			),
		),
	)
);
check( 'right code: tokens stored', array( $GLOBALS['user_meta'][ INFLUENCER_ID ]['ihq_access_token'], $GLOBALS['user_meta'][ INFLUENCER_ID ]['ihq_sso_code'] ), array( 'at', 'sso-1' ) );
$GLOBALS['events'] = array();
check( 'single use: same code again refused', verify_code( RIGHT_CODE ), error_with( INVALID_MESSAGE ) );
check( 'single use: no second sign-in or start-session', $GLOBALS['events'], mutex_round_trip() );
check( 'single use: replay not counted', counter_options(), array() );

// === 11. Stale counter from an earlier window restarts at 1. ===
reset_state();
seed_pending();
$GLOBALS['options'][ fail_key() ]         = 2;
$GLOBALS['options'][ fail_timeout_key() ] = (string) ( time() - 1 );
check( 'stale counter: mismatch, not lockout', verify_code( WRONG_CODES[0] ), error_with( MISMATCH_MESSAGE ) );
check( 'stale counter: restarted at 1', $GLOBALS['options'][ fail_key() ], 1 );
check( 'stale counter: not locked', $GLOBALS['transients'], array() );

// === 12. Account resolution: user_id first, email fallback, influencer role required. ===
reset_state();
seed_pending( array( 'user_id' => ADMIN_ID, 'email' => OTHER_EMAIL ) );
check( 'user_id not an influencer: falls back to the email', verify_code( RIGHT_CODE ), signed_in() );
check( 'user_id not an influencer: signed in as the email owner', $GLOBALS['events'], sign_in_events( OTHER_ID ) );
reset_state();
seed_pending( array( 'user_id' => 0 ) );
check( 'no user_id: email lookup signs in', verify_code( RIGHT_CODE ), signed_in() );
check( 'no user_id: signed in as the email owner', $GLOBALS['events'], sign_in_events( INFLUENCER_ID ) );
reset_state();
seed_pending( array( 'user_id' => ADMIN_ID, 'email' => ADMIN_EMAIL ) );
check( 'no influencer account: refused', verify_code( RIGHT_CODE ), error_with( INVALID_MESSAGE ) );
check( 'no influencer account: record and email map deleted', array_key_exists( pending_key(), $GLOBALS['options'] ) || array_key_exists( email_map_key( ADMIN_EMAIL ), $GLOBALS['options'] ), false );
check( 'no influencer account: mutex released, no sign-in', $GLOBALS['events'], mutex_round_trip() );
check( 'no influencer account: not counted', counter_options(), array() );

// === 13. Start-session fails: the WordPress session still stands (as built). ===
reset_state();
seed_pending();
$GLOBALS['start_session'] = array( 'status' => 502, 'body' => FAILURE_BODY );
check( 'start-session fails: still signed in', verify_code( RIGHT_CODE ), signed_in() );
check( 'start-session fails: same sign-in sequence', $GLOBALS['events'], sign_in_events( INFLUENCER_ID ) );
check( 'start-session fails: no tokens stored', isset( $GLOBALS['user_meta'][ INFLUENCER_ID ]['ihq_access_token'] ), false );
check( 'start-session fails: code still consumed', array_key_exists( pending_key(), $GLOBALS['options'] ), false );

// === 14. Counter DB error fails closed: the first wrong code locks the IP. ===
foreach ( array( 'upsert_error' => 'Deadlock found', 'select_error' => 'Lost connection' ) as $which => $error ) {
	reset_state();
	seed_pending();
	$log = tempnam( sys_get_temp_dir(), 'ihq-log' );
	ini_set( 'error_log', $log );
	$GLOBALS[ $which ] = $error;
	check( "$which: first wrong code gets the lockout message", verify_code( WRONG_CODES[0] ), error_with( LOCKOUT_MESSAGE ) );
	check( "$which: lock set for the lockout window", array( $GLOBALS['transients'], $GLOBALS['transient_ttls'] ), array( array( lock_key() => 1 ), array( lock_key() => IHQ_LOGIN_CODE_LOCKOUT_SECONDS ) ) );
	check( "$which: fail rows deleted", counter_options(), array() );
	check( "$which: error logged", last_logged( $log ), 'IHQ login-code failure counter ' . fail_key() . ' not counted: ' . $error );
	check( "$which: mutex released, no sign-in", $GLOBALS['events'], mutex_round_trip() );
	ini_set( 'error_log', '/dev/null' );
	unlink( $log );
}

ob_end_clean();
echo implode( "\n", $out ) . "\n";
echo $fail === 0 ? "ALL PASS\n" : "$fail FAILED\n";
exit( $fail === 0 ? 0 : 1 );
