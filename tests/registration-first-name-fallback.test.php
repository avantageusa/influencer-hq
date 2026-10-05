<?php
/**
 * ENGR-7016 boundary tests for inc/email-verification-handler.php: a
 * registration with no first name (visitor-intent collects none) stores the
 * new WordPress username as first_name and sends it as payload.firstName on
 * start-session; a real first name is unchanged. No WordPress bootstrap and no
 * live requests:
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/registration-first-name-fallback.test.php
 *
 * Exit code is non-zero on any failure.
 */
ob_start();
ini_set( 'error_log', '/dev/null' );

define( 'ABSPATH', '/' );
define( 'IHQ_ENVIRONMENT', 'test' );
define( 'IHQ_API_BASE_URL', 'https://api.example.test/qc' );
define( 'IHQ_GAME_PORTAL_BASE_URL', 'https://portal.example.test/av-baccarat' );
define( 'IHQ_INFLUENCER_API_KEY', 'server-key' );

const START_SESSION_URL = 'https://api.example.test/qc/account/oauth/start-session';
const SUCCESS_BODY      = '{"success":true,"data":{"AccessToken":"at","IdToken":"it","RefreshToken":"rt","ExpiresIn":3600}}';
const NEW_USER_ID       = 77;

class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
class WP_User {
	public $ID;
	public $user_email = 'ann.lee@example.test';
	public function __construct( $id ) { $this->ID = $id; }
	public function set_role( $role ) {}
}

function reset_state() {
	$GLOBALS['requests']        = array();
	$GLOBALS['responses']       = array();
	$GLOBALS['user_meta']       = array();
	$GLOBALS['created_users']   = array();
	$GLOBALS['taken_usernames'] = array();
	$_COOKIE                    = array();
}

// --- WordPress stubs. ---
function add_action( ...$args ) {}
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function wp_parse_url( $u ) { return parse_url( $u ); }
function is_ssl() { return true; }
function wp_unslash( $v ) { return stripslashes( $v ); }
function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( $s ) ) ); }
function sanitize_email( $s ) { return trim( $s ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $s ) ); }
function sanitize_user( $s ) { return preg_replace( '/[^a-z0-9_.\-]/i', '', $s ); }
function is_email( $s ) { return is_string( $s ) && strpos( $s, '@' ) !== false; }
function email_exists( $s ) { return false; }
function username_exists( $s ) { return in_array( $s, $GLOBALS['taken_usernames'], true ); }
function wp_create_user( $username, $password, $email ) {
	$GLOBALS['created_users'][] = array( 'username' => $username, 'email' => $email );
	return NEW_USER_ID;
}
function wp_generate_password( $len = 12 ) { return 'TOKEN123'; }
function wp_json_encode( $v, $flags = 0 ) { return json_encode( $v, $flags ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function current_time( $t ) { return '2026-10-05 00:00:00'; }
function get_user_by( $field, $id ) { return new WP_User( $id ); }
function get_user_meta( $id, $key, $single = false ) { return $GLOBALS['user_meta'][ $id ][ $key ] ?? ''; }
function update_user_meta( $id, $key, $value ) { $GLOBALS['user_meta'][ $id ][ $key ] = $value; return true; }
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
function start_session_payloads() {
	$payloads = array();
	foreach ( $GLOBALS['requests'] as $request ) {
		if ( $request['url'] === START_SESSION_URL ) {
			$payloads[] = json_decode( $request['args']['body'], true )['payload'];
		}
	}
	return $payloads;
}
function name_meta() {
	$meta = $GLOBALS['user_meta'][ NEW_USER_ID ] ?? array();
	return array(
		'first_name' => $meta['first_name'] ?? null,
		'last_name'  => $meta['last_name'] ?? null,
	);
}
function create_user( array $registration ) {
	respond( START_SESSION_URL, 200, SUCCESS_BODY );
	return ihq_create_influencer_user_from_registration_data( $registration + array( 'country_iso' => 'NL' ) );
}
function expected_payload( $first_name, $last_name ) {
	return array(
		'id'         => 'wpu-' . NEW_USER_ID,
		'firstName'  => $first_name,
		'lastName'   => $last_name,
		'email'      => 'ann.lee@example.test',
		'countryIso' => 'NL',
	);
}

require __DIR__ . '/../inc/ihq-env.php';
require __DIR__ . '/../inc/ihq-referral-attribution.php';
require __DIR__ . '/../inc/email-verification-handler.php';
require __DIR__ . '/../inc/visitor-intent-handler.php';

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

// --- Helper: the fallback rule on its own. ---
check( 'helper: real name returned', ihq_registration_first_name_or_username( 'Ann', 'ann.lee' ), 'Ann' );
check( 'helper: real name not trimmed', ihq_registration_first_name_or_username( ' Ann ', 'ann.lee' ), ' Ann ' );
check( 'helper: empty -> username', ihq_registration_first_name_or_username( '', 'ann.lee' ), 'ann.lee' );
check( 'helper: whitespace -> username', ihq_registration_first_name_or_username( " \t\n ", 'ann.lee' ), 'ann.lee' );
check( 'helper: null -> username', ihq_registration_first_name_or_username( null, 'ann.lee' ), 'ann.lee' );
check( 'helper: "0" is a name', ihq_registration_first_name_or_username( '0', 'ann.lee' ), '0' );
check( 'helper: non-string name returned as a string', ihq_registration_first_name_or_username( 42, 'ann.lee' ), '42' );

// --- Empty first name: username stored and sent; last name left as given. ---
reset_state();
$user_id = create_user( array( 'email' => 'ann.lee@example.test', 'first_name' => '', 'last_name' => '' ) );
check( 'empty: returns the new user id', $user_id, NEW_USER_ID );
check( 'empty: user created with the email local part', $GLOBALS['created_users'], array( array( 'username' => 'ann.lee', 'email' => 'ann.lee@example.test' ) ) );
check( 'empty: first_name meta = username, last_name not written', name_meta(), array( 'first_name' => 'ann.lee', 'last_name' => null ) );
check( 'empty: start-session sends the username as firstName', start_session_payloads(), array( expected_payload( 'ann.lee', '' ) ) );

// --- Whitespace-only first name is treated as empty. ---
reset_state();
create_user( array( 'email' => 'ann.lee@example.test', 'first_name' => "  \t ", 'last_name' => '' ) );
check( 'whitespace: first_name meta = username', name_meta(), array( 'first_name' => 'ann.lee', 'last_name' => null ) );
check( 'whitespace: start-session sends the username', start_session_payloads(), array( expected_payload( 'ann.lee', '' ) ) );

// --- No first_name key at all. ---
reset_state();
create_user( array( 'email' => 'ann.lee@example.test' ) );
check( 'missing key: first_name meta = username', name_meta(), array( 'first_name' => 'ann.lee', 'last_name' => null ) );
check( 'missing key: start-session sends the username', start_session_payloads(), array( expected_payload( 'ann.lee', '' ) ) );

// --- Username already taken: the de-duplicated username is the one used. ---
reset_state();
$GLOBALS['taken_usernames'] = array( 'ann.lee', 'ann.lee1' );
create_user( array( 'email' => 'ann.lee@example.test', 'first_name' => '', 'last_name' => '' ) );
check( 'taken: user created as ann.lee2', $GLOBALS['created_users'][0]['username'], 'ann.lee2' );
check( 'taken: first_name meta = de-duplicated username', name_meta(), array( 'first_name' => 'ann.lee2', 'last_name' => null ) );
check( 'taken: start-session sends the de-duplicated username', start_session_payloads(), array( expected_payload( 'ann.lee2', '' ) ) );

// --- Real names: unchanged. ---
reset_state();
create_user( array( 'email' => 'ann.lee@example.test', 'first_name' => 'Ann', 'last_name' => 'Lee' ) );
check( 'real name: meta unchanged', name_meta(), array( 'first_name' => 'Ann', 'last_name' => 'Lee' ) );
check( 'real name: start-session unchanged', start_session_payloads(), array( expected_payload( 'Ann', 'Lee' ) ) );

reset_state();
create_user( array( 'email' => 'ann.lee@example.test', 'first_name' => 'Ann', 'last_name' => '' ) );
check( 'first name only: last_name still not written', name_meta(), array( 'first_name' => 'Ann', 'last_name' => null ) );
check( 'first name only: lastName still empty', start_session_payloads(), array( expected_payload( 'Ann', '' ) ) );

// --- Later SSO refresh reads the stored first name, so it keeps sending the username. ---
reset_state();
create_user( array( 'email' => 'ann.lee@example.test', 'first_name' => '', 'last_name' => '' ) );
respond( START_SESSION_URL, 200, SUCCESS_BODY );
ihq_refresh_influencer_oauth_tokens( NEW_USER_ID, 'NL' );
check(
	'refresh: second start-session also sends the username',
	start_session_payloads(),
	array( expected_payload( 'ann.lee', '' ), expected_payload( 'ann.lee', '' ) )
);

// --- Visitor-intent end to end: the form has no name field. ---
reset_state();
$intent       = array(
	'comm_methods'   => array( 'email' => 'ann.lee@example.test' ),
	'social_handles' => array( 'instagram' => '@annlee' ),
	'country_iso'    => 'NL',
);
$registration = ihq_build_registration_data_from_visitor_intent( $intent );
check( 'visitor-intent: builder still passes no name', array( $registration['first_name'], $registration['last_name'] ), array( '', '' ) );
respond( START_SESSION_URL, 200, SUCCESS_BODY );
ihq_create_influencer_user_from_registration_data( $registration );
check( 'visitor-intent: first_name meta = username', name_meta(), array( 'first_name' => 'ann.lee', 'last_name' => null ) );
check( 'visitor-intent: start-session sends the username as firstName', start_session_payloads(), array( expected_payload( 'ann.lee', '' ) ) );

ob_end_clean();
echo implode( "\n", $out ) . "\n";
echo $fail === 0 ? "ALL PASS\n" : "$fail FAILED\n";
exit( $fail === 0 ? 0 : 1 );
