<?php
/**
 * ENGR-7511 boundary tests for inc/aicoach-register.php: AI Coach
 * create-account accepts any one communication channel (PO-3099). With no
 * email among the channels, the account gets an undeliverable stand-in
 * address (<uuid>@chat.invalid); a real email is still used when given; no
 * channel at all is still an error. No WordPress bootstrap and no live
 * requests:
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/aicoach-register-channels.test.php
 *
 * Exit code is non-zero on any failure.
 */
ob_start();
ini_set( 'error_log', '/dev/null' );

define( 'ABSPATH', '/' );

const FIXED_UUID      = '3f2b1c9e-1a2b-4c3d-8e9f-0123456789ab';
const NEW_USER_ID     = 77;
const PORTAL_REDIRECT = 'https://ihq.example.test/portal/portal-home/';

class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
class WP_REST_Server {
	const CREATABLE = 'POST';
}
class WP_REST_Request {
	private $params;
	public function __construct( array $params ) { $this->params = $params; }
	public function get_json_params() { return $this->params; }
	public function get_params() { return $this->params; }
	public function get_header( $name ) { return 'nonce'; }
}
class WP_REST_Response {
	public $data;
	public $status;
	public function __construct( $data, $status ) {
		$this->data   = $data;
		$this->status = $status;
	}
}

function reset_state() {
	$GLOBALS['created_registrations'] = array();
	$GLOBALS['signed_in']             = array();
	$GLOBALS['transients']            = array();
	$GLOBALS['user_meta']             = array();
	$GLOBALS['user_by_email_lookups'] = array();
	$GLOBALS['actions']               = array();
	unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
}

// --- WordPress stubs. ---
function add_filter( ...$args ) {}
function __( $text, $domain = '' ) { return $text; }
function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( $s ) ) ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $s ) ); }
function sanitize_email( $s ) { return trim( $s ); }
// Close enough to WordPress: '@Avantage123' and '+66812345678' are not emails.
function is_email( $s ) { return is_string( $s ) && filter_var( $s, FILTER_VALIDATE_EMAIL ) !== false; }
function wp_generate_uuid4() { return FIXED_UUID; }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function is_user_logged_in() { return false; }
function home_url( $path = '' ) { return 'https://ihq.example.test' . $path; }
function trailingslashit( $s ) { return rtrim( $s, '/' ) . '/'; }
function get_transient( $key ) { return $GLOBALS['transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['transients'][ $key ] = $value; return true; }
function get_user_by( $field, $value ) {
	$GLOBALS['user_by_email_lookups'][] = array( $field, $value );
	return false;
}
function update_user_meta( $id, $key, $value ) { $GLOBALS['user_meta'][ $id ][ $key ] = $value; return true; }

// --- Theme functions the handler calls. ---
function ihq_portal_redirect_url_for_context( $context ) { return PORTAL_REDIRECT; }
function ihq_get_client_ip_for_rate_limit() { return '203.0.113.9'; }
function ihq_get_cloudflare_country_iso_alpha2() { return 'TH'; }
function ihq_create_influencer_user_from_registration_data( array $registration ) {
	$GLOBALS['created_registrations'][] = $registration;
	return NEW_USER_ID;
}
function wp_set_current_user( $id ) {}
// The auth cookie WordPress would send to the browser, handed to whoever listens, and a nonce
// that - like the real one - depends on the session token read from $_COOKIE.
const LOGGED_IN_COOKIE = 'wordpress_logged_in_test';
function add_action( $tag, $callback, ...$rest ) { $GLOBALS['actions'][ $tag ][] = $callback; }
function remove_action( $tag, $callback ) {
	foreach ( $GLOBALS['actions'][ $tag ] ?? array() as $i => $registered ) {
		if ( $registered === $callback ) { unset( $GLOBALS['actions'][ $tag ][ $i ] ); }
	}
}
function wp_create_nonce( $action ) { return 'nonce-of-' . $action . '-for-' . ( $_COOKIE[ LOGGED_IN_COOKIE ] ?? 'no-session' ); }
function wp_set_auth_cookie( $id, $remember ) {
	$GLOBALS['signed_in'][] = $id;
	foreach ( $GLOBALS['actions']['set_logged_in_cookie'] ?? array() as $callback ) { $callback( 'cookie-of-' . $id ); }
}

require __DIR__ . '/../inc/placeholder-email.php';
require __DIR__ . '/../inc/aicoach-register.php';

$failures = 0;
function check( $label, $actual, $expected ) {
	global $failures;
	if ( $actual === $expected ) {
		fwrite( STDOUT, "ok   - $label\n" );
		return;
	}
	++$failures;
	fwrite( STDOUT, "FAIL - $label\n  expected: " . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true ) . "\n" );
}

function base_params( array $channels ) {
	return array(
		'firstName' => 'Telegram',
		'lastName'  => 'User',
		'channels'  => $channels,
		'language'  => 'en',
	);
}

$placeholder = FIXED_UUID . '@chat.invalid';

// --- ihq_aicoach_placeholder_email ---
check( 'placeholder email is <uuid>@chat.invalid', ihq_aicoach_placeholder_email(), $placeholder );

// --- ihq_aicoach_parse_register_payload ---
check(
	'Telegram only (the reported case) parses with the stand-in email',
	ihq_aicoach_parse_register_payload( base_params( array( array( 'channel' => 'telegram', 'value' => '@Avantage123' ) ) ) ),
	array(
		'value' => array(
			'email'        => $placeholder,
			'first_name'   => 'Telegram',
			'last_name'    => 'User',
			'username'     => '',
			'comm_methods' => array( 'telegram' => '@Avantage123' ),
			'language'     => 'en',
			'duration'     => '',
		),
	)
);

check(
	'WhatsApp only parses with the stand-in email',
	ihq_aicoach_parse_register_payload( base_params( array( array( 'channel' => 'whatsapp', 'value' => '+66812345678' ) ) ) )['value']['email'],
	$placeholder
);

check(
	'a real email is still used when one is given alongside Telegram',
	ihq_aicoach_parse_register_payload(
		base_params(
			array(
				array( 'channel' => 'telegram', 'value' => '@Avantage123' ),
				array( 'channel' => 'email', 'value' => 'ann.lee@example.test' ),
			)
		)
	)['value']['email'],
	'ann.lee@example.test'
);

check(
	'comm_methods map with no email also gets the stand-in email',
	ihq_aicoach_parse_register_payload( array( 'comm_methods' => array( 'line' => 'U0123456789abcdef0123456789abcdef' ) ) )['value']['email'],
	$placeholder
);

$no_channel_error = array( 'error' => 'Please select at least one communication method.' );
check( 'no channels is an error', ihq_aicoach_parse_register_payload( base_params( array() ) ), $no_channel_error );
check(
	'only empty or unknown channels is an error',
	ihq_aicoach_parse_register_payload(
		base_params(
			array(
				array( 'channel' => 'telegram', 'value' => '   ' ),
				array( 'channel' => 'myspace', 'value' => 'tom' ),
			)
		)
	),
	$no_channel_error
);

$invalid_email_error = array( 'error' => 'Please enter a valid email address.' );
check(
	'an invalid Email channel on its own is an error, not a stand-in',
	ihq_aicoach_parse_register_payload( base_params( array( array( 'channel' => 'email', 'value' => 'abc@' ) ) ) ),
	$invalid_email_error
);
check(
	'an invalid Email channel next to Telegram is still an error',
	ihq_aicoach_parse_register_payload(
		base_params(
			array(
				array( 'channel' => 'telegram', 'value' => '@Avantage123' ),
				array( 'channel' => 'email', 'value' => 'abc@' ),
			)
		)
	),
	$invalid_email_error
);

// --- ihq_aicoach_handle_create_account: invalid Email channel is a 400. ---
reset_state();
$response = ihq_aicoach_handle_create_account(
	new WP_REST_Request( base_params( array( array( 'channel' => 'email', 'value' => 'abc@' ) ) ) )
);
check( 'invalid email: 400', $response->status, 400 );
check( 'invalid email: error body', $response->data, array( 'success' => false, 'error' => 'Please enter a valid email address.' ) );
check( 'invalid email: no account created', $GLOBALS['created_registrations'], array() );
check( 'invalid email: nobody signed in', $GLOBALS['signed_in'], array() );

// --- ihq_aicoach_handle_create_account: Telegram only creates and signs in. ---
reset_state();
$response = ihq_aicoach_handle_create_account(
	new WP_REST_Request( base_params( array( array( 'channel' => 'telegram', 'value' => '@Avantage123' ) ) ) )
);
check( 'Telegram only: 200', $response->status, 200 );
check( 'Telegram only: redirects to the portal and hands over a nonce made for the new session', $response->data, array( 'success' => true, 'redirectUrl' => PORTAL_REDIRECT, 'restNonce' => 'nonce-of-wp_rest-for-cookie-of-' . NEW_USER_ID ) );
check(
	'Telegram only: account created with the stand-in email and the Telegram channel',
	$GLOBALS['created_registrations'],
	array(
		array(
			'email'          => $placeholder,
			'first_name'     => 'Telegram',
			'last_name'      => 'User',
			'comm_methods'   => array( 'telegram' => '@Avantage123' ),
			'challenge_type' => 'maybe_later',
			'country_iso'    => 'TH',
			'language'       => 'en',
		),
	)
);
check( 'Telegram only: existing-account lookup used the stand-in email', $GLOBALS['user_by_email_lookups'], array( array( 'email', $placeholder ) ) );
check( 'Telegram only: visitor signed in', $GLOBALS['signed_in'], array( NEW_USER_ID ) );
check( 'Telegram only: the new session cookie was exposed to this request for the nonce', $_COOKIE[ LOGGED_IN_COOKIE ] ?? null, 'cookie-of-' . NEW_USER_ID );
check( 'Telegram only: the listener that exposes it was removed again', array_values( $GLOBALS['actions']['set_logged_in_cookie'] ?? array() ), array() );

// --- ihq_aicoach_handle_create_account: no channel is a 400 and creates nothing. ---
reset_state();
$response = ihq_aicoach_handle_create_account( new WP_REST_Request( base_params( array() ) ) );
check( 'no channel: 400', $response->status, 400 );
check( 'no channel: error body', $response->data, array( 'success' => false, 'error' => 'Please select at least one communication method.' ) );
check( 'no channel: no account created', $GLOBALS['created_registrations'], array() );
check( 'no channel: nobody signed in', $GLOBALS['signed_in'], array() );
check( 'no channel: throttle not set', $GLOBALS['transients'], array() );

ob_end_clean();
exit( $failures > 0 ? 1 : 0 );
