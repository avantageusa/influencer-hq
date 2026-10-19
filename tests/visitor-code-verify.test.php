<?php
/**
 * ENGR-7634 tests for the visitor-code flow in inc/visitor-intent-handler.php:
 * no logged-out response carries the code (unless the dev/qa debug flag is
 * on), an existing account is signed in only with a code emailed to that
 * account, verify is rate limited per IP, fingerprint and email, and only a
 * hash of the code is stored. No WordPress bootstrap and no live requests:
 *
 *     docker run --name ihq-t -v "$PWD":/t -w /t php:8.2-cli php tests/visitor-code-verify.test.php
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
// IHQ_ENVIRONMENT and IHQ_VISITOR_CODE_DEBUG come from putenv() per case.

const START_SESSION_URL = 'https://api.example.test/qc/account/oauth/start-session';
const SUCCESS_BODY      = '{"success":true,"data":{"AccessToken":"at","IdToken":"it","RefreshToken":"rt","ExpiresIn":3600}}';
const NEW_USER_ID       = 77;
const VICTIM_ID         = 5;
const ADMIN_ID          = 6;
const VICTIM_EMAIL      = 'victim@example.test';
const ADMIN_EMAIL       = 'admin@example.test';
const NEW_EMAIL         = 'newbie@example.test';
const ATTACKER_EMAIL    = 'attacker@evil.example';
const ACCOUNT_URL       = 'https://ihq.example.test/portal/account/';
const LOCKOUT_MESSAGE   = 'You are temporarily locked out, try again in 15 minutes';
const MISMATCH_MESSAGE  = 'That code does not match. Check your communication method and try again.';
const REFUSED_MESSAGE   = 'We could not sign you in with this code. Please sign in from the login page.';

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
	public function set_role( $role ) {}
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

/** Enough of $wpdb for the atomic counter and the advisory locks. */
class FakeWpdb {
	public $options   = 'wp_options';
	public $insert_id = 0;
	public function prepare( $query, ...$args ) { return array( 'query' => $query, 'args' => $args ); }
	public function get_var( $prepared ) {
		if ( strpos( $prepared['query'], 'GET_LOCK' ) !== false ) {
			return $GLOBALS['mutex_available'] ? 1 : 0;
		}
		return (int) ( $GLOBALS['options'][ $prepared['args'][0] ] ?? 0 );
	}
	public function query( $prepared ) {
		if ( strpos( $prepared['query'], 'RELEASE_LOCK' ) !== false ) {
			$GLOBALS['mutex_releases']++;
			return 1;
		}
		if ( strpos( $prepared['query'], 'INSERT INTO' ) === 0 ) {
			$name                         = $prepared['args'][0];
			$GLOBALS['options'][ $name ]  = (int) ( $GLOBALS['options'][ $name ] ?? 0 ) + 1;
			$this->insert_id              = $GLOBALS['options'][ $name ];
			return 1;
		}
		throw new Exception( 'unexpected query ' . $prepared['query'] );
	}
}
$GLOBALS['wpdb'] = new FakeWpdb();

function reset_state() {
	$GLOBALS['options']         = array();
	$GLOBALS['transients']      = array();
	$GLOBALS['mail']            = array();
	$GLOBALS['mail_fails']      = false;
	$GLOBALS['auth_cookies']    = array();
	$GLOBALS['requests']        = array();
	$GLOBALS['responses']       = array();
	$GLOBALS['user_meta']       = array();
	$GLOBALS['created_users']   = array();
	$GLOBALS['stored_logins']   = array();
	$GLOBALS['password_seq']    = 0;
	$GLOBALS['mutex_available'] = true;
	$GLOBALS['mutex_releases']  = 0;
	$GLOBALS['turnstile_on']    = false;
	$GLOBALS['turnstile_pass']  = false;
	$GLOBALS['users']           = array(
		VICTIM_EMAIL => new WP_User( VICTIM_ID, VICTIM_EMAIL, array( 'influencer' ) ),
		ADMIN_EMAIL  => new WP_User( ADMIN_ID, ADMIN_EMAIL, array( 'administrator' ) ),
	);
	$_POST                      = array();
	$_COOKIE                    = array();
	$_SERVER['REMOTE_ADDR']     = '203.0.113.10';
	putenv( 'IHQ_ENVIRONMENT=prod' );
	putenv( 'IHQ_VISITOR_CODE_DEBUG' );
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
function sanitize_user( $s, $strict = false ) {
	$s = trim( strip_tags( $s ) );
	if ( $strict ) {
		$s = preg_replace( '|[^a-z0-9 _.\-@]|i', '', $s );
	}
	return trim( $s );
}
// Same shape as WordPress core is_email(): no spaces, a dotted domain.
function is_email( $s ) { return is_string( $s ) && preg_match( '/^[a-z0-9!#$%&\'*+\/=?^_`{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)+$/i', $s ) === 1; }
function email_exists( $s ) { return isset( $GLOBALS['users'][ strtolower( $s ) ] ) ? $GLOBALS['users'][ strtolower( $s ) ]->ID : false; }
function get_user_by( $field, $value ) {
	if ( $field === 'email' ) {
		return $GLOBALS['users'][ strtolower( $value ) ] ?? false;
	}
	return new WP_User( $value );
}
function username_exists( $s ) { return false; }
function wp_create_user( $username, $password, $email ) {
	$GLOBALS['created_users'][]              = array( 'username' => $username, 'email' => $email );
	$GLOBALS['stored_logins'][ NEW_USER_ID ] = sanitize_user( $username, true );
	return NEW_USER_ID;
}
function get_userdata( $id ) { return (object) array( 'user_login' => $GLOBALS['stored_logins'][ $id ] ?? '' ); }
function wp_generate_password( $len = 12 ) { return 'token' . ( ++$GLOBALS['password_seq'] ); }
function wp_salt( $scheme = '' ) { return 'salt-' . $scheme; }
function wp_json_encode( $v, $flags = 0 ) { return json_encode( $v, $flags ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function current_time( $t ) { return '2026-10-15 00:00:00'; }
function get_user_meta( $id, $key, $single = false ) { return $GLOBALS['user_meta'][ $id ][ $key ] ?? ''; }
function update_user_meta( $id, $key, $value ) { $GLOBALS['user_meta'][ $id ][ $key ] = $value; return true; }
function get_option( $key, $default = false ) { return $GLOBALS['options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['options'][ $key ] = $value; return true; }
function delete_option( $key ) { unset( $GLOBALS['options'][ $key ] ); return true; }
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl = 0 ) { $GLOBALS['transients'][ $key ] = $value; return true; }
function delete_transient( $key ) { unset( $GLOBALS['transients'][ $key ] ); return true; }
function wp_cache_delete( ...$args ) { return true; }
function wp_verify_nonce( $nonce, $action ) { return $nonce === 'good-nonce' && $action === 'ihq_visitor_intent_nonce'; }
function wp_mail( $to, $subject, $message, $headers = array() ) {
	if ( $GLOBALS['mail_fails'] ) {
		return false;
	}
	$GLOBALS['mail'][] = array( 'to' => $to, 'message' => $message );
	return true;
}
function wp_set_current_user( $id ) {}
function wp_set_auth_cookie( $id, $remember = false ) { $GLOBALS['auth_cookies'][] = $id; }
function add_query_arg( $key, $value, $url ) { return $url . '?' . $key . '=' . $value; }
function wp_send_json_success( $data = null ) { throw new JsonResponse( true, $data ); }
function wp_send_json_error( $data = null ) { throw new JsonResponse( false, $data ); }
function wp_remote_post( $url, $args ) {
	$GLOBALS['requests'][] = array( 'url' => $url, 'args' => $args );
	$queue                 = &$GLOBALS['responses'][ $url ];
	if ( empty( $queue ) ) {
		throw new Exception( 'unexpected request to ' . $url );
	}
	return array_shift( $queue );
}
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function wp_remote_retrieve_response_code( $r ) { return $r['status']; }
function ihq_portal_account_url() { return ACCOUNT_URL; }
// Turnstile is loaded from other files on a real site; switched per case here.
function ihq_turnstile_is_configured() { return $GLOBALS['turnstile_on']; }
function ihq_portal_turnstile_pass_status() { return array( 'valid' => $GLOBALS['turnstile_pass'] ); }

putenv( 'IHQ_ENVIRONMENT=prod' ); // ihq-env.php checks required keys on load
require __DIR__ . '/../inc/ihq-env.php';
require __DIR__ . '/../inc/ihq-referral-attribution.php';
require __DIR__ . '/../inc/email-verification-handler.php';
require __DIR__ . '/../inc/visitor-intent-handler.php';
require __DIR__ . '/../inc/visitor-intent-logout.php';

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

// --- Helpers that drive the AJAX handlers. ---
function intent_for( $email ) { return array( 'comm_methods' => array( 'email' => $email ) ); }
function call_ajax( $handler, array $post ) {
	$_POST = array_merge( array( 'nonce' => 'good-nonce' ), $post );
	try {
		$handler();
	} catch ( JsonResponse $response ) {
		return array( 'success' => $response->success, 'data' => $response->data );
	}
	throw new Exception( $handler . ' returned without a JSON response' );
}
function issue( $email, $handler = 'ihq_handle_issue_visitor_verification_ajax' ) {
	return call_ajax( $handler, array( 'intent_json' => json_encode( intent_for( $email ) ) ) );
}
function verify( $email, $code ) {
	return call_ajax(
		'ihq_handle_verify_visitor_code_ajax',
		array( 'intent_json' => json_encode( intent_for( $email ) ), 'code' => $code )
	);
}
function mailed_codes() {
	$codes = array();
	foreach ( $GLOBALS['mail'] as $mail ) {
		preg_match( '/letter-spacing:12px[^>]*>(\d{6})</', $mail['message'], $m );
		$codes[] = array( 'to' => $mail['to'], 'code' => $m[1] ?? null );
	}
	return $codes;
}
function last_code() {
	$codes = mailed_codes();
	return end( $codes )['code'];
}
function clear_issue_throttle() {
	foreach ( array_keys( $GLOBALS['transients'] ) as $key ) {
		if ( strpos( $key, 'ihq_visitor_issue_' ) === 0 ) {
			unset( $GLOBALS['transients'][ $key ] );
		}
	}
}
function wrong_code( $code ) { return str_pad( (string) ( ( (int) $code + 1 ) % 1000000 ), 6, '0', STR_PAD_LEFT ); }
function fp_key( $email ) { return ihq_visitor_verification_fingerprint_option_key( ihq_visitor_intent_comm_fingerprint( intent_for( $email ) ) ); }
function error_with( $message ) { return array( 'success' => false, 'data' => array( 'message' => $message ) ); }
function rate_limit_options() {
	return array_values( array_filter( array_keys( $GLOBALS['options'] ), function ( $k ) { return strpos( $k, 'ihq_login_verify_' ) === 0; } ) );
}

// === 1. Issue: the logged-out response carries no code. ===
reset_state();
$issued = issue( VICTIM_EMAIL );
check( 'issue: response has no code or Braze payload', $issued, array( 'success' => true, 'data' => array( 'expires_minutes' => 15, 'reused' => false, 'redirect_url' => ACCOUNT_URL ) ) );
check( 'issue: code emailed to the intent address only', array_column( mailed_codes(), 'to' ), array( VICTIM_EMAIL ) );
check( 'issue: response JSON never contains the mailed code', strpos( json_encode( $issued ), last_code() ), false );

// === 2. Test action is off without the debug flag; nothing is issued or mailed. ===
reset_state();
check( 'test action: refused without the flag', issue( VICTIM_EMAIL, 'ihq_handle_test_registry_braze_ajax' ), error_with( 'Not available.' ) );
check( 'test action: nothing mailed', $GLOBALS['mail'], array() );
check( 'test action: nothing stored', $GLOBALS['options'], array() );

// === 3. Debug flag: only with IHQ_VISITOR_CODE_DEBUG=1 AND a dev/qa environment. ===
foreach ( array( 'dev', 'qa' ) as $env ) {
	reset_state();
	putenv( 'IHQ_ENVIRONMENT=' . $env );
	putenv( 'IHQ_VISITOR_CODE_DEBUG=1' );
	$issued = issue( VICTIM_EMAIL );
	check( "debug $env: issue echoes the mailed code", $issued['data']['registration_code_debug'], last_code() );
	check( "debug $env: issue includes the Braze payload", $issued['data']['braze_track_payload']['attributes'][0]['registration_code'], last_code() );
	clear_issue_throttle();
	$test = issue( VICTIM_EMAIL, 'ihq_handle_test_registry_braze_ajax' );
	check( "debug $env: test action echoes the mailed code", $test['data']['registration_code'], last_code() );
}
foreach ( array( 'prod', 'PROD', '' ) as $env ) {
	reset_state();
	putenv( 'IHQ_ENVIRONMENT=' . $env );
	putenv( 'IHQ_VISITOR_CODE_DEBUG=1' );
	check( "debug flag ignored in env '$env': issue", array_keys( issue( VICTIM_EMAIL )['data'] ), array( 'expires_minutes', 'reused', 'redirect_url' ) );
	clear_issue_throttle();
	check( "debug flag ignored in env '$env': test action", issue( VICTIM_EMAIL, 'ihq_handle_test_registry_braze_ajax' ), error_with( 'Not available.' ) );
}
reset_state();
putenv( 'IHQ_ENVIRONMENT=dev' );
putenv( 'IHQ_VISITOR_CODE_DEBUG=true' );
check( 'debug flag must be exactly "1"', ihq_visitor_code_debug_enabled(), false );

// === 4. Storage: one record per fingerprint, HMAC only, no plaintext. ===
reset_state();
issue( VICTIM_EMAIL );
$code   = last_code();
$record = $GLOBALS['options'][ fp_key( VICTIM_EMAIL ) ];
check( 'storage: only the fingerprint record exists', array_keys( $GLOBALS['options'] ), array( fp_key( VICTIM_EMAIL ) ) );
check( 'storage: record fields', array_keys( $record ), array( 'verify_token', 'code_hash', 'fingerprint', 'delivered_email', 'intent', 'created', 'expires' ) );
check( 'storage: hash is the HMAC of the mailed code', $record['code_hash'], hash_hmac( 'sha256', $code, 'salt-ihq_visitor_verify' . $record['verify_token'] ) );
check( 'storage: delivered_email is the mailed address', $record['delivered_email'], VICTIM_EMAIL );
check( 'storage: plaintext code appears nowhere in options', strpos( serialize( $GLOBALS['options'] ), $code ), false );

// === 5. Legacy record: plaintext fingerprint record and code-keyed record are cleaned up. ===
reset_state();
$GLOBALS['options'][ fp_key( VICTIM_EMAIL ) ]                = array( 'code' => '123456', 'expires' => time() + 600 );
$GLOBALS['options'][ ihq_visitor_verification_code_option_key( '123456' ) ] = array( 'code_hash' => 'x', 'verify_token' => 'y' );
issue( VICTIM_EMAIL );
check( 'legacy: code-keyed record deleted, fingerprint record replaced', array_keys( $GLOBALS['options'] ), array( fp_key( VICTIM_EMAIL ) ) );
check( 'legacy: plaintext code gone', isset( $GLOBALS['options'][ fp_key( VICTIM_EMAIL ) ]['code'] ), false );
check( 'legacy: old code no longer verifies', verify( VICTIM_EMAIL, '123456' ), error_with( MISMATCH_MESSAGE ) );

// === 6. Takeover: a caller who never sees the victim's mail cannot sign in by guessing (one IP). ===
reset_state();
issue( VICTIM_EMAIL );
$code = last_code();
check( 'takeover/IP: guess 1 mismatches', verify( VICTIM_EMAIL, wrong_code( $code ) ), error_with( MISMATCH_MESSAGE ) );
check( 'takeover/IP: guess 2 mismatches', verify( VICTIM_EMAIL, wrong_code( wrong_code( $code ) ) ), error_with( MISMATCH_MESSAGE ) );
check( 'takeover/IP: guess 3 locks the IP', verify( VICTIM_EMAIL, wrong_code( wrong_code( wrong_code( $code ) ) ) ), error_with( LOCKOUT_MESSAGE ) );
check( 'takeover/IP: even the right code is refused while locked', verify( VICTIM_EMAIL, $code ), error_with( LOCKOUT_MESSAGE ) );
check( 'takeover/IP: no auth cookie set', $GLOBALS['auth_cookies'], array() );
check( 'takeover/IP: IP lock transient set', $GLOBALS['transients'][ ihq_login_verify_rate_limit_keys()['lock'] ], 1 );
check( 'takeover/IP: mutex released on every attempt', $GLOBALS['mutex_releases'], 4 );

// === 7. Takeover across many IPs: the per-fingerprint / per-email cap still holds. ===
reset_state();
issue( VICTIM_EMAIL );
$code    = last_code();
$guesses = array( wrong_code( $code ), wrong_code( wrong_code( $code ) ), wrong_code( wrong_code( wrong_code( $code ) ) ) );
foreach ( $guesses as $i => $guess ) {
	$_SERVER['REMOTE_ADDR'] = '198.51.100.' . ( $i + 1 );
	check( 'takeover/IPs: guess ' . ( $i + 1 ) . ' from a fresh IP mismatches', verify( VICTIM_EMAIL, $guess ), error_with( MISMATCH_MESSAGE ) );
}
$_SERVER['REMOTE_ADDR'] = '198.51.100.99';
check( 'takeover/IPs: 4th attempt locked even with the right code', verify( VICTIM_EMAIL, $code ), error_with( LOCKOUT_MESSAGE ) );
check( 'takeover/IPs: no auth cookie set', $GLOBALS['auth_cookies'], array() );
check( 'takeover/IPs: code burnt after the 3rd miss', isset( $GLOBALS['options'][ fp_key( VICTIM_EMAIL ) ] ), false );
$fp_counter    = ihq_visitor_verify_attempt_keys( 'fingerprint', ihq_visitor_intent_comm_fingerprint( intent_for( VICTIM_EMAIL ) ) )['fail'];
$email_counter = ihq_visitor_verify_attempt_keys( 'email', VICTIM_EMAIL )['fail'];
check( 'takeover/IPs: fingerprint counter at 4', $GLOBALS['options'][ $fp_counter ], 4 );
check( 'takeover/IPs: email counter at 4', $GLOBALS['options'][ $email_counter ], 4 );

// Same inbox, different fingerprint (extra comm method): the email counter still blocks.
$_SERVER['REMOTE_ADDR'] = '198.51.100.100';
$other_intent           = array( 'comm_methods' => array( 'email' => VICTIM_EMAIL, 'whatsapp' => '+15550001' ) );
$locked                 = call_ajax( 'ihq_handle_verify_visitor_code_ajax', array( 'intent_json' => json_encode( $other_intent ), 'code' => $code ) );
check( 'takeover/IPs: other fingerprint, same email is locked', $locked, error_with( LOCKOUT_MESSAGE ) );
check( 'takeover/IPs: email counter case-insensitive', ihq_visitor_verify_attempt_keys( 'email', strtolower( 'Victim@Example.test' ) ), ihq_visitor_verify_attempt_keys( 'email', VICTIM_EMAIL ) );

// === 8. Code issued for the victim, verified against another intent: no record, no sign-in. ===
reset_state();
issue( VICTIM_EMAIL );
check( 'cross-intent: victim code under another email is invalid', verify( NEW_EMAIL, last_code() ), error_with( 'Invalid or expired code. Check your communication method and try again.' ) );
check( 'cross-intent: no user created, no cookie', array( $GLOBALS['created_users'], $GLOBALS['auth_cookies'] ), array( array(), array() ) );

// === 9. Legitimate header Login: the influencer types the code from their own inbox. ===
reset_state();
issue( VICTIM_EMAIL );
check( 'existing influencer: signed in', verify( VICTIM_EMAIL, last_code() ), array( 'success' => true, 'data' => array( 'redirect_url' => ACCOUNT_URL . '?ihq_visitor_existing=1', 'existing' => true ) ) );
check( 'existing influencer: auth cookie for that user', $GLOBALS['auth_cookies'], array( VICTIM_ID ) );
check( 'existing influencer: record and counters cleared', array_keys( $GLOBALS['options'] ), array() );
check( 'existing influencer: code is single-use', verify( VICTIM_EMAIL, mailed_codes()[0]['code'] ), error_with( 'Invalid or expired code. Check your communication method and try again.' ) );

// === 10. Existing non-influencer (admin): refused even with the right code. ===
reset_state();
issue( ADMIN_EMAIL );
check( 'existing admin: refused', verify( ADMIN_EMAIL, last_code() ), error_with( REFUSED_MESSAGE ) );
check( 'existing admin: no auth cookie', $GLOBALS['auth_cookies'], array() );

// === 11. Existing user, record delivered elsewhere: refused. ===
reset_state();
issue( VICTIM_EMAIL );
$GLOBALS['options'][ fp_key( VICTIM_EMAIL ) ]['delivered_email'] = 'attacker@example.test';
check( 'delivered elsewhere: refused', verify( VICTIM_EMAIL, last_code() ), error_with( REFUSED_MESSAGE ) );
check( 'delivered elsewhere: no auth cookie', $GLOBALS['auth_cookies'], array() );

// Unit: the existing-account rule.
$victim = $GLOBALS['users'][ VICTIM_EMAIL ];
check( 'rule: same address', ihq_visitor_code_may_sign_in_existing_user( $victim, VICTIM_EMAIL ), true );
check( 'rule: case and spaces ignored', ihq_visitor_code_may_sign_in_existing_user( $victim, ' Victim@Example.TEST ' ), true );
check( 'rule: no delivered address (legacy magic link)', ihq_visitor_code_may_sign_in_existing_user( $victim, '' ), false );
check( 'rule: other address', ihq_visitor_code_may_sign_in_existing_user( $victim, 'other@example.test' ), false );
check( 'rule: not a user', ihq_visitor_code_may_sign_in_existing_user( false, VICTIM_EMAIL ), false );
check( 'rule: admin', ihq_visitor_code_may_sign_in_existing_user( $GLOBALS['users'][ ADMIN_EMAIL ], ADMIN_EMAIL ), false );
check( 'legacy magic link: existing account refused', ihq_complete_visitor_intent_registration( intent_for( VICTIM_EMAIL ) ), array( 'ok' => false, 'message' => REFUSED_MESSAGE ) );

// === 12. New visitor: issue → mailed code → verify creates and signs in the user. ===
reset_state();
$GLOBALS['responses'][ START_SESSION_URL ][] = array( 'status' => 200, 'body' => SUCCESS_BODY );
issue( NEW_EMAIL );
check( 'new visitor: registered', verify( NEW_EMAIL, last_code() ), array( 'success' => true, 'data' => array( 'redirect_url' => ACCOUNT_URL . '?ihq_visitor_registered=1', 'existing' => false ) ) );
check( 'new visitor: user created', $GLOBALS['created_users'], array( array( 'username' => 'newbie', 'email' => NEW_EMAIL ) ) );
check( 'new visitor: auth cookie for the new user', $GLOBALS['auth_cookies'], array( NEW_USER_ID ) );
check( 'new visitor: record and counters cleared', array_keys( $GLOBALS['options'] ), array() );

// New visitor after one typo: success clears the counters and the IP failure.
reset_state();
$GLOBALS['responses'][ START_SESSION_URL ][] = array( 'status' => 200, 'body' => SUCCESS_BODY );
issue( NEW_EMAIL );
verify( NEW_EMAIL, wrong_code( last_code() ) );
$ip_keys    = ihq_login_verify_rate_limit_keys();
$fp_keys    = ihq_visitor_verify_attempt_keys( 'fingerprint', ihq_visitor_intent_comm_fingerprint( intent_for( NEW_EMAIL ) ) );
$email_keys = ihq_visitor_verify_attempt_keys( 'email', NEW_EMAIL );
$daily_keys = ihq_visitor_verify_attempt_keys( 'email_daily', NEW_EMAIL );
$expected   = array( $fp_keys['fail'], $fp_keys['fail_timeout'], $email_keys['fail'], $email_keys['fail_timeout'], $daily_keys['fail'], $daily_keys['fail_timeout'], $ip_keys['fail'], $ip_keys['fail_timeout'] );
check( 'typo: fingerprint, email, daily and IP counters after a miss', rate_limit_options(), $expected );
check( 'typo: each counter at 1', array( $GLOBALS['options'][ $fp_keys['fail'] ], $GLOBALS['options'][ $email_keys['fail'] ], $GLOBALS['options'][ $daily_keys['fail'] ], $GLOBALS['options'][ $ip_keys['fail'] ] ), array( 1, 1, 1, 1 ) );
$daily_ttl = (int) $GLOBALS['options'][ $daily_keys['fail_timeout'] ] - time();
check( 'typo: daily counter lives a day', $daily_ttl > DAY_IN_SECONDS - 10 && $daily_ttl <= DAY_IN_SECONDS, true );
check( 'typo: 15-minute counter lives 15 minutes', (int) $GLOBALS['options'][ $fp_keys['fail_timeout'] ] - time() <= 900, true );
check( 'typo then right code: registered', verify( NEW_EMAIL, last_code() )['success'], true );
check( 'typo then right code: counters cleared', rate_limit_options(), array() );

// === 13. Re-request rotates the code: only the latest works. ===
reset_state();
issue( VICTIM_EMAIL );
clear_issue_throttle();
issue( VICTIM_EMAIL );
$codes = mailed_codes();
if ( $codes[0]['code'] !== $codes[1]['code'] ) {
	check( 'rotate: first code no longer matches', verify( VICTIM_EMAIL, $codes[0]['code'] ), error_with( MISMATCH_MESSAGE ) );
}
check( 'rotate: latest code signs in', verify( VICTIM_EMAIL, $codes[1]['code'] )['success'], true );

// === 14. Expired code. ===
reset_state();
issue( VICTIM_EMAIL );
$GLOBALS['options'][ fp_key( VICTIM_EMAIL ) ]['expires'] = time() - 1;
check( 'expired: refused', verify( VICTIM_EMAIL, last_code() ), error_with( 'This code has expired. Please request a new code from the lander.' ) );
check( 'expired: record deleted', isset( $GLOBALS['options'][ fp_key( VICTIM_EMAIL ) ] ), false );

// === 15. Turnstile: required when configured; no attempt is counted without it. ===
reset_state();
issue( VICTIM_EMAIL );
$GLOBALS['turnstile_on'] = true;
check( 'turnstile: no pass refused', verify( VICTIM_EMAIL, last_code() ), error_with( 'Human verification failed. Please refresh the page and try again.' ) );
check( 'turnstile: no counters touched', rate_limit_options(), array() );
$GLOBALS['turnstile_pass'] = true;
check( 'turnstile: with the pass, signs in', verify( VICTIM_EMAIL, last_code() )['success'], true );

// === 16. Busy mutex and bad input. ===
reset_state();
issue( VICTIM_EMAIL );
$GLOBALS['mutex_available'] = false;
check( 'mutex busy: asked to retry', verify( VICTIM_EMAIL, last_code() ), error_with( 'Please wait a moment and try again.' ) );
check( 'mutex busy: no counters touched', rate_limit_options(), array() );
$GLOBALS['mutex_available'] = true;
check( 'short code refused', verify( VICTIM_EMAIL, '12345' ), error_with( 'Enter the 6-digit code we sent you.' ) );
check( 'bad nonce refused', call_ajax( 'ihq_handle_verify_visitor_code_ajax', array( 'nonce' => 'bad', 'intent_json' => '{}', 'code' => '123456' ) ), error_with( 'Invalid security token or intent data.' ) );
check( 'no record: invalid', verify( NEW_EMAIL, '123456' ), error_with( 'Invalid or expired code. Check your communication method and try again.' ) );

// === 17. Fingerprint collision: the caller's intent cannot redirect the registration (review Major). ===
reset_state();
$GLOBALS['responses'][ START_SESSION_URL ][] = array( 'status' => 200, 'body' => SUCCESS_BODY );
issue( ATTACKER_EMAIL );
$collision_json = '{"comm_methods":{"!email":"' . NEW_EMAIL . '","email":" ' . ATTACKER_EMAIL . '"}}';
$collision      = json_decode( $collision_json, true );
check( 'collision: same fingerprint as the issued intent', ihq_visitor_intent_comm_fingerprint( $collision ), ihq_visitor_intent_comm_fingerprint( intent_for( ATTACKER_EMAIL ) ) );
check( 'collision: posted intent extracts the victim email', ihq_visitor_intent_extract_email( $collision ), NEW_EMAIL );
$result = call_ajax( 'ihq_handle_verify_visitor_code_ajax', array( 'intent_json' => $collision_json, 'code' => last_code() ) );
check( 'collision: verify registers from the stored intent', $result['success'], true );
check( 'collision: account is for the mailed address, not the victim', $GLOBALS['created_users'], array( array( 'username' => 'attacker', 'email' => ATTACKER_EMAIL ) ) );
reset_state();
check( 'collision: new account for another address than delivered is refused', ihq_complete_visitor_intent_registration( intent_for( NEW_EMAIL ), ATTACKER_EMAIL ), array( 'ok' => false, 'message' => REFUSED_MESSAGE ) );
check( 'collision: nothing created', array( $GLOBALS['created_users'], $GLOBALS['auth_cookies'] ), array( array(), array() ) );

// === 18. Long-horizon per-email cap (keyed by the delivered address). ===
reset_state();
issue( VICTIM_EMAIL );
$GLOBALS['options'][ ihq_visitor_verify_attempt_keys( 'email_daily', VICTIM_EMAIL )['fail'] ] = IHQ_VISITOR_VERIFY_DAILY_MAX_FAILURES;
check( 'daily cap: right code refused once 10 misses are on record', verify( VICTIM_EMAIL, last_code() ), error_with( LOCKOUT_MESSAGE ) );
check( 'daily cap: no auth cookie', $GLOBALS['auth_cookies'], array() );
$GLOBALS['options'][ ihq_visitor_verify_attempt_keys( 'email_daily', VICTIM_EMAIL )['fail'] ] = IHQ_VISITOR_VERIFY_DAILY_MAX_FAILURES - 1;
$GLOBALS['options'] = array_intersect_key( $GLOBALS['options'], array( fp_key( VICTIM_EMAIL ) => 1, ihq_visitor_verify_attempt_keys( 'email_daily', VICTIM_EMAIL )['fail'] => 1 ) );
check( 'daily cap: one below the cap still signs in', verify( VICTIM_EMAIL, last_code() )['success'], true );
check( 'daily cap: success clears the daily counter', rate_limit_options(), array() );

// === 19. Issue throttle per email. ===
reset_state();
issue( VICTIM_EMAIL );
check( 'throttle: second code within 45s refused', issue( VICTIM_EMAIL ), error_with( 'Please wait a moment before requesting another code.' ) );
check( 'throttle: only one email sent', count( $GLOBALS['mail'] ), 1 );
check( 'throttle: first code still verifies', verify( VICTIM_EMAIL, last_code() )['success'], true );
check( 'throttle: other emails unaffected', issue( NEW_EMAIL )['success'], true );
reset_state();
$GLOBALS['mail_fails'] = true;
check( 'throttle: failed send reports the error', issue( VICTIM_EMAIL ), error_with( 'We could not send your code. Please try again in a moment.' ) );
check( 'throttle: failed send leaves no throttle', $GLOBALS['transients'], array() );
$GLOBALS['mail_fails'] = false;
check( 'throttle: retry after a failed send goes out', issue( VICTIM_EMAIL )['success'], true );
check( 'throttle: retry mailed once', count( $GLOBALS['mail'] ), 1 );

// === 20. Records without delivered_email or intent (pre-fix shape) are not usable. ===
check( 'record: complete', ihq_visitor_verification_record_is_usable( array( 'code_hash' => 'h', 'verify_token' => 't', 'delivered_email' => VICTIM_EMAIL, 'intent' => array() ) ), true );
check( 'record: no delivered_email', ihq_visitor_verification_record_is_usable( array( 'code_hash' => 'h', 'verify_token' => 't', 'intent' => array() ) ), false );
check( 'record: no intent', ihq_visitor_verification_record_is_usable( array( 'code_hash' => 'h', 'verify_token' => 't', 'delivered_email' => VICTIM_EMAIL ) ), false );
check( 'record: legacy plaintext', ihq_visitor_verification_record_is_usable( array( 'code' => '123456', 'expires' => 1 ) ), false );
check( 'record: not an array', ihq_visitor_verification_record_is_usable( false ), false );

// === 21. Counter keys share the login-code family (cleanup sweep). ===
$keys = ihq_visitor_verify_attempt_keys( 'email', VICTIM_EMAIL );
check( 'keys: fail prefix', strpos( $keys['fail'], 'ihq_login_verify_fail_' ), 0 );
check( 'keys: timeout prefix', strpos( $keys['fail_timeout'], 'ihq_login_verify_fail_timeout_' ), 0 );
check( 'keys: scopes do not collide', ihq_visitor_verify_attempt_keys( 'email', 'x' ) === ihq_visitor_verify_attempt_keys( 'fingerprint', 'x' ), false );
check( 'counters: no email → fingerprint only', count( ihq_visitor_verify_attempt_counters( 'fp', '' ) ), 1 );

// === 22. Sign-in clears the guest login cookies; a failed verify leaves them (ENGR-7260). ===
const GUEST_COOKIES = array(
	'ihq_visitor_intent'      => '{"comm_methods":{"email":"victim@example.test"}}',
	'ihq_visitor_code_issued' => '1',
	'wordpress_test_cookie'   => 'WP Cookie check',
);
reset_state();
issue( VICTIM_EMAIL );
$_COOKIE = GUEST_COOKIES;
check( 'cookies: wrong code refused', verify( VICTIM_EMAIL, wrong_code( last_code() ) ), error_with( MISMATCH_MESSAGE ) );
check( 'cookies: wrong code leaves them for the retry', $_COOKIE, GUEST_COOKIES );
check( 'cookies: right code signs in', verify( VICTIM_EMAIL, last_code() )['success'], true );
check( 'cookies: existing account sign-in clears both, keeps others', $_COOKIE, array( 'wordpress_test_cookie' => 'WP Cookie check' ) );

reset_state();
$GLOBALS['responses'][ START_SESSION_URL ][] = array( 'status' => 200, 'body' => SUCCESS_BODY );
issue( NEW_EMAIL );
$_COOKIE = GUEST_COOKIES;
check( 'cookies: new visitor registered', verify( NEW_EMAIL, last_code() )['success'], true );
check( 'cookies: registration clears both, keeps others', $_COOKIE, array( 'wordpress_test_cookie' => 'WP Cookie check' ) );

reset_state();
issue( ADMIN_EMAIL );
$_COOKIE = GUEST_COOKIES;
check( 'cookies: refused sign-in', verify( ADMIN_EMAIL, last_code() ), error_with( REFUSED_MESSAGE ) );
check( 'cookies: refused sign-in leaves them', $_COOKIE, GUEST_COOKIES );

ob_end_clean();
echo implode( "\n", $out ) . "\n";
echo $fail === 0 ? "ALL PASS\n" : "$fail FAILED\n";
exit( $fail === 0 ? 0 : 1 );
