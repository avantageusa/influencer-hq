<?php
/**
 * ENGR-7511 boundary tests for inc/placeholder-email.php and its consumers:
 * an account created with a stand-in address (<uuid>@chat.invalid, no email
 * given) is not marked email_verified and its address is never sent to Braze
 * (nor looked up there); a real address behaves exactly as before. Runs the
 * real ihq_create_influencer_user_from_registration_data() and
 * ihq_send_influencer_to_braze() with WordPress and HTTP stubbed:
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/placeholder-email.test.php
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
define( 'IHQ_BRAZE_REST_ENDPOINT', 'https://braze.example.test' );
define( 'IHQ_BRAZE_EXPORT_API_KEY', 'export-key' );
define( 'IHQ_BRAZE_TRACK_API_KEY', 'track-key' );

const START_SESSION_URL = 'https://api.example.test/qc/account/oauth/start-session';
const BRAZE_EXPORT_URL  = 'https://braze.example.test/users/export/ids';
const BRAZE_TRACK_URL   = 'https://braze.example.test/users/track';
const NEW_USER_ID       = 77;
const STAND_IN_EMAIL    = '3f2b1c9e-1a2b-4c3d-8e9f-0123456789ab@chat.invalid';
const REAL_EMAIL        = 'ann.lee@example.test';

class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
class WP_User {
	public $ID;
	public function __construct( $id ) { $this->ID = $id; }
	public function set_role( $role ) {}
}

function reset_state() {
	$GLOBALS['requests']  = array();
	$GLOBALS['user_meta'] = array();
	$GLOBALS['user']      = null;
	$_COOKIE              = array();
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
function sanitize_user( $s, $strict = false ) { return trim( strip_tags( $s ) ); }
function is_email( $s ) { return is_string( $s ) && filter_var( $s, FILTER_VALIDATE_EMAIL ) !== false; }
function email_exists( $s ) { return false; }
function username_exists( $s ) { return false; }
function wp_create_user( $username, $password, $email ) {
	$GLOBALS['user'] = (object) array( 'user_login' => $username, 'user_email' => $email, 'display_name' => $username );
	return NEW_USER_ID;
}
function get_userdata( $id ) { return $id === NEW_USER_ID ? $GLOBALS['user'] : false; }
function wp_generate_password( $len = 12 ) { return 'TOKEN123'; }
function wp_json_encode( $v, $flags = 0 ) { return json_encode( $v, $flags ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function current_time( $t ) { return '2026-10-14 00:00:00'; }
function get_user_by( $field, $id ) { return new WP_User( $id ); }
function get_user_meta( $id, $key, $single = false ) { return $GLOBALS['user_meta'][ $id ][ $key ] ?? ''; }
function update_user_meta( $id, $key, $value ) { $GLOBALS['user_meta'][ $id ][ $key ] = $value; return true; }
function wp_remote_post( $url, $args ) {
	$GLOBALS['requests'][] = array( 'url' => $url, 'body' => json_decode( $args['body'], true ) );
	if ( $url === START_SESSION_URL ) {
		return array( 'status' => 200, 'body' => '{"success":true,"data":{"AccessToken":"at","IdToken":"it","RefreshToken":"rt","ExpiresIn":3600}}' );
	}
	if ( $url === BRAZE_EXPORT_URL ) {
		return array( 'status' => 200, 'body' => '{"users":[]}' );
	}
	if ( $url === BRAZE_TRACK_URL ) {
		return array( 'status' => 201, 'body' => '{}' );
	}
	throw new Exception( 'unexpected request to ' . $url );
}
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function wp_remote_retrieve_response_code( $r ) { return $r['status']; }

function request_urls() {
	return array_column( $GLOBALS['requests'], 'url' );
}
function braze_track_attributes() {
	foreach ( $GLOBALS['requests'] as $request ) {
		if ( $request['url'] === BRAZE_TRACK_URL ) {
			$attributes = $request['body']['attributes'][0];
			unset( $attributes['external_id'], $attributes['wp_influencer_guid'] ); // random per run
			return $attributes;
		}
	}
	return null;
}
function create_user( $email ) {
	return ihq_create_influencer_user_from_registration_data(
		array(
			'email'        => $email,
			'first_name'   => 'Telegram',
			'last_name'    => 'User',
			'comm_methods' => array( 'telegram' => '@Avantage123' ),
			'country_iso'  => 'TH',
		)
	);
}

require __DIR__ . '/../inc/placeholder-email.php';
require __DIR__ . '/../inc/ihq-env.php';
require __DIR__ . '/../inc/ihq-referral-attribution.php';
require __DIR__ . '/../inc/braze-integration.php';
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

// --- ihq_is_placeholder_email ---
check( 'helper: stand-in recognised', ihq_is_placeholder_email( STAND_IN_EMAIL ), true );
check( 'helper: case and whitespace ignored', ihq_is_placeholder_email( '  ABC@Chat.Invalid ' ), true );
check( 'helper: real email is not a stand-in', ihq_is_placeholder_email( REAL_EMAIL ), false );
check( 'helper: bare domain is not a stand-in', ihq_is_placeholder_email( '@chat.invalid' ), false );
check( 'helper: look-alike domain is not a stand-in', ihq_is_placeholder_email( 'ann@notchat.invalid' ), false );
check( 'helper: old Telegram fake email is not a stand-in', ihq_is_placeholder_email( 'ann@email_telegram.com' ), false );
check( 'helper: non-string is not a stand-in', ihq_is_placeholder_email( null ), false );

// --- Stand-in: not verified, not sent to Braze, not looked up in Braze. ---
reset_state();
check( 'stand-in: returns the new user id', create_user( STAND_IN_EMAIL ), NEW_USER_ID );
check( 'stand-in: email_verified not written', array_key_exists( 'email_verified', $GLOBALS['user_meta'][ NEW_USER_ID ] ), false );
check( 'stand-in: start-session then Braze track, no Braze email lookup', request_urls(), array( START_SESSION_URL, BRAZE_TRACK_URL ) );
check(
	'stand-in: Braze attributes carry no email',
	braze_track_attributes(),
	array( 'first_name' => 'Telegram', 'last_name' => 'User', 'Language' => 'TH' )
);
check( 'stand-in: start-session still gets the stand-in (account-api needs an email)', $GLOBALS['requests'][0]['body']['payload']['email'], STAND_IN_EMAIL );

// --- Real email: unchanged behaviour. ---
reset_state();
create_user( REAL_EMAIL );
check( 'real: email_verified written', $GLOBALS['user_meta'][ NEW_USER_ID ]['email_verified'], true );
check( 'real: start-session, Braze lookup, Braze track', request_urls(), array( START_SESSION_URL, BRAZE_EXPORT_URL, BRAZE_TRACK_URL ) );
check(
	'real: Braze attributes carry the email',
	braze_track_attributes(),
	array( 'email' => REAL_EMAIL, 'first_name' => 'Telegram', 'last_name' => 'User', 'Language' => 'TH' )
);

ob_end_clean();
echo implode( "\n", $out ) . "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
