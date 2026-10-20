<?php
/**
 * Unit test for inc/portal-onboarding-appointment.php (ENGR-7577): the onboarding's
 * appointment route, keyed to the logged-in Influencer, and the Join redirect to the portal
 * Coach page. No WordPress bootstrap: the WP
 * functions the files touch are stubbed. The time rules themselves are the landing flow's
 * (tests/aicoach-appointment.test.php).
 *
 *     php tests/portal-onboarding-appointment.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );
define( 'YEAR_IN_SECONDS', 365 * 24 * 60 * 60 );
define( 'DAY_IN_SECONDS', 24 * 60 * 60 );
define( 'MINUTE_IN_SECONDS', 60 );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

// Hooks: actions and filters are recorded, and filters run in the order they were added.
$GLOBALS['actions'] = array();
$GLOBALS['filters'] = array();
function add_action( $hook, $callback, $priority = 10, $args = 1 ) { $GLOBALS['actions'][] = array( $hook, $callback, $priority, $args ); }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { $GLOBALS['filters'][ $hook ][] = array( $callback, $priority, $args ); }
function apply_filters( $hook, $value, ...$args ) {
	foreach ( $GLOBALS['filters'][ $hook ] ?? array() as $filter ) {
		$value = call_user_func( $filter[0], $value, ...$args );
	}
	return $value;
}
$GLOBALS['done'] = array();
function do_action( $hook, ...$args ) { $GLOBALS['done'][] = array( $hook, $args ); }

function wp_unslash( $s ) { return $s; }
function __( $text, $domain = '' ) { return $text; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $s ) ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_salt( $scheme = 'auth' ) { return 'salt-for-' . $scheme; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
$GLOBALS['wp_pages'] = array( 'appointment' => (object) array( 'post_status' => 'publish', 'permalink' => 'https://example.test/appointment/' ) );
function get_page_by_path( $path ) { return $GLOBALS['wp_pages'][ $path ] ?? null; }
function get_permalink( $page ) { return $page->permalink; }
function add_query_arg( $key, $value, $url ) { return $url . '?' . rawurlencode( $key ) . '=' . rawurlencode( $value ); }
function wp_generate_uuid4() {
	static $n = 0;
	$n++;
	return sprintf( '00000000-0000-4000-8000-%012d', $n );
}
function wp_verify_nonce( $nonce, $action ) { return 'good-nonce' === $nonce; }
function is_user_logged_in() { return true; }

$GLOBALS['wp_options'] = array();
$GLOBALS['wp_options_failing_prefix'] = null;
function get_option( $key, $default = false ) { return $GLOBALS['wp_options'][ $key ] ?? $default; }
function update_option( $key, $value, $autoload = true ) {
	if ( null !== $GLOBALS['wp_options_failing_prefix'] && 0 === strpos( $key, $GLOBALS['wp_options_failing_prefix'] ) ) {
		return false;
	}
	$GLOBALS['wp_options'][ $key ] = $value;
	return true;
}
function delete_option( $key ) { unset( $GLOBALS['wp_options'][ $key ] ); return true; }

// User meta; keys listed in user_meta_failing do not save, like a write that did not go through.
$GLOBALS['user_meta']         = array();
$GLOBALS['user_meta_failing'] = array();
function get_user_meta( $user_id, $key, $single = false ) { return $GLOBALS['user_meta'][ $user_id ][ $key ] ?? ''; }
function update_user_meta( $user_id, $key, $value ) {
	if ( in_array( $key, $GLOBALS['user_meta_failing'], true ) ) {
		return false;
	}
	$GLOBALS['user_meta'][ $user_id ][ $key ] = $value;
	return true;
}
$GLOBALS['current_user'] = 7;
function get_current_user_id() { return $GLOBALS['current_user']; }

$GLOBALS['wp_transients'] = array();
function get_transient( $key ) { return $GLOBALS['wp_transients'][ $key ] ?? false; }
function set_transient( $key, $value, $ttl ) { $GLOBALS['wp_transients'][ $key ] = $value; return true; }

function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
class WP_Error {
	private $code;
	private $message;
	public function __construct( $code = '', $message = '', $data = null ) { $this->code = $code; $this->message = $message; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
class WP_REST_Server { const CREATABLE = 'POST'; const READABLE = 'GET'; }
class WP_REST_Response {
	public $data;
	public $status;
	public $headers = array();
	public function __construct( $data = null, $status = 200 ) { $this->data = $data; $this->status = $status; }
	public function header( $name, $value ) { $this->headers[ $name ] = $value; }
}
class WP_REST_Request {
	private $json;
	public function __construct( $json ) { $this->json = $json; }
	public function get_json_params() { return $this->json; }
}
$GLOBALS['routes'] = array();
function register_rest_route( $namespace, $route, $args ) { $GLOBALS['routes'][] = array( $namespace, $route, $args ); }

require __DIR__ . '/../inc/aicoach-progress.php';
require __DIR__ . '/../inc/aicoach-appointment.php';
require __DIR__ . '/../inc/aicoach-appointment-page.php';
require __DIR__ . '/../inc/portal-onboarding-appointment.php';

const META = '_ihq_portal_onboarding_appointment';
$now = gmmktime( 12, 0, 0, 10, 20, 2026 );

// --- hooks ---

$hooks = array_map( function ( $a ) { return array( $a[0], $a[1] ); }, $GLOBALS['actions'] );
check( 'the route is registered on rest_api_init', in_array( array( 'rest_api_init', 'ihq_portal_onboarding_appointment_register_route' ), $hooks, true ) );
check( 'nothing listens for onboarding completion: the link stays valid after it', array() === array_filter( $hooks, function ( $hook ) { return 'ihq_portal_onboarding_completed' === $hook[0]; } ) );
check( 'the Join redirect filter takes the redirect and the record', array( array( 'ihq_portal_onboarding_appointment_join_redirect', 10, 2 ) ) === $GLOBALS['filters']['ihq_aicoach_appointment_join_redirect'] );

ihq_portal_onboarding_appointment_register_route();
check( 'POST /ihq/v1/onboarding/appointment, behind the onboarding permission check', array( array( 'ihq/v1', '/onboarding/appointment', array( 'methods' => 'POST', 'callback' => 'ihq_portal_onboarding_appointment_handle_post', 'permission_callback' => 'ihq_portal_onboarding_permission_check' ) ) ) === $GLOBALS['routes'] );

// --- get() ---

check( 'no appointment saved reads as none', null === ihq_portal_onboarding_appointment_get( 7 ) );
$GLOBALS['user_meta'][7][ META ] = 'garbage';
check( 'a stored value that is not a record reads as none', null === ihq_portal_onboarding_appointment_get( 7 ) );
$GLOBALS['user_meta'][7][ META ] = array( 'id' => 5 );
check( 'a record without a text id reads as none', null === ihq_portal_onboarding_appointment_get( 7 ) );
$GLOBALS['user_meta'] = array();

// --- create() ---

$first = ihq_portal_onboarding_appointment_create( 7, $now + 3600, 'Europe/Belgrade', $now );
$first_record = array( 'id' => '00000000-0000-4000-8000-000000000001', 'startsAt' => $now + 3600, 'timeZone' => 'Europe/Belgrade', 'createdAt' => '2026-10-20T12:00:00+00:00' );
check( 'create returns the record', $first_record === $first );
check( 'the record is saved on the account', array( 7 => array( META => $first_record ) ) === $GLOBALS['user_meta'] );
check( 'its option names the Influencer, not a ref', array( 'ihq_aicoach_appointment_' . $first['id'] => array_merge( $first_record, array( 'userId' => 7 ) ) ) === $GLOBALS['wp_options'] );

$second = ihq_portal_onboarding_appointment_create( 7, $now + 1800, 'UTC', $now );
check( 'a new appointment replaces the last on the account', $second === $GLOBALS['user_meta'][7][ META ] );
check( 'and the last one\'s option is gone, so its link reads as invalid', array( 'ihq_aicoach_appointment_' . $second['id'] ) === array_keys( $GLOBALS['wp_options'] ) );
check( 'the replaced link is invalid on the link page', 'invalid' === ihq_aicoach_appointment_page_context( ihq_aicoach_appointment_make_token( $first['id'], $now + 3600 ), $now )['state'] );

ihq_portal_onboarding_appointment_create( 8, $now + 3600, 'UTC', $now );
check( 'another Influencer\'s appointment leaves this one alone', $second === $GLOBALS['user_meta'][7][ META ] && 2 === count( $GLOBALS['wp_options'] ) );

$options_before = $GLOBALS['wp_options'];
$meta_before    = $GLOBALS['user_meta'];
$GLOBALS['wp_options_failing_prefix'] = 'ihq_aicoach_appointment_';
$failed = ihq_portal_onboarding_appointment_create( 7, $now + 3600, 'UTC', $now );
$GLOBALS['wp_options_failing_prefix'] = null;
check( 'a failed option write is an error', is_wp_error( $failed ) && 'appointment_save_failed' === $failed->get_error_code() );
check( 'and changes nothing', $options_before === $GLOBALS['wp_options'] && $meta_before === $GLOBALS['user_meta'] );

$GLOBALS['user_meta_failing'] = array( META );
$failed = ihq_portal_onboarding_appointment_create( 7, $now + 3600, 'UTC', $now );
$GLOBALS['user_meta_failing'] = array();
check( 'a failed account write is an error', is_wp_error( $failed ) && 'appointment_account_save_failed' === $failed->get_error_code() );
check( 'and removes the new option, keeping the old appointment', $options_before === $GLOBALS['wp_options'] && $meta_before === $GLOBALS['user_meta'] );

// --- the route ---

$GLOBALS['wp_options']    = array();
$GLOBALS['user_meta']     = array();
$GLOBALS['wp_transients'] = array();
$post = function ( $body ) { return ihq_portal_onboarding_appointment_handle_post( new WP_REST_Request( $body ) ); };

$response = $post( array( 'choice' => 'in-30-minutes' ) );
check( 'a valid request is answered with 201, a link and an ISO start, never cached', 201 === $response->status && array( 'link', 'startsAt' ) === array_keys( $response->data ) && array( 'Cache-Control' => 'no-store, private' ) === $response->headers );
$verified = ihq_aicoach_appointment_verify_token( rawurldecode( explode( '?t=', $response->data['link'] )[1] ) );
check( 'the link is the link page with a token that verifies', 0 === strpos( $response->data['link'], 'https://example.test/appointment/?t=' ) && is_array( $verified ) );
check( 'the start is about 30 minutes from the server clock', abs( strtotime( $response->data['startsAt'] ) - ( time() + 1800 ) ) <= 5 );
check( 'the appointment is the current Influencer\'s', $verified['id'] === $GLOBALS['user_meta'][7][ META ]['id'] && 7 === $GLOBALS['wp_options'][ 'ihq_aicoach_appointment_' . $verified['id'] ]['userId'] );
check( 'nothing is written to the landing flow\'s cookie progress', array( 'ihq_aicoach_appointment_' . $verified['id'] ) === array_keys( $GLOBALS['wp_options'] ) && ! isset( $_COOKIE['ihq_aicoach_ref'] ) );

$before = array( $GLOBALS['wp_options'], $GLOBALS['user_meta'] );
$response = $post( array( 'choice' => 'other', 'date' => '2020-01-01', 'time' => '10:00', 'timeZone' => 'UTC' ) );
check( 'a past time is 400 with its code', 400 === $response->status && array( 'errors' => array( 'time-in-past' ) ) === $response->data );
check( 'missing fields come back as their codes', array( 'errors' => array( 'date-missing', 'time-zone-missing', 'time-missing' ) ) === $post( array( 'choice' => 'other' ) )->data );
check( 'no choice is choice-missing', array( 'errors' => array( 'choice-missing' ) ) === $post( null )->data );
check( 'refused requests store nothing', $before === array( $GLOBALS['wp_options'], $GLOBALS['user_meta'] ) );

$saved_page = $GLOBALS['wp_pages']['appointment'];
unset( $GLOBALS['wp_pages']['appointment'] );
$response = $post( array( 'choice' => 'in-an-hour' ) );
$GLOBALS['wp_pages']['appointment'] = $saved_page;
check( 'with no link page the answer is 500 and nothing is stored', 500 === $response->status && array( 'error' => 'The appointment page is not available.' ) === $response->data && $before === array( $GLOBALS['wp_options'], $GLOBALS['user_meta'] ) );

$GLOBALS['user_meta_failing'] = array( META );
$response = $post( array( 'choice' => 'in-an-hour' ) );
$GLOBALS['user_meta_failing'] = array();
check( 'a failed save is 500 with no link', 500 === $response->status && array( 'error' => 'The appointment could not be saved.' ) === $response->data && $before === array( $GLOBALS['wp_options'], $GLOBALS['user_meta'] ) );

$GLOBALS['wp_transients'] = array();
for ( $i = 0; $i < IHQ_AICOACH_PROGRESS_RATE_LIMIT_MAX_WRITES; $i++ ) {
	$post( array( 'choice' => 'in-an-hour' ) );
}
$limited = $post( array( 'choice' => 'in-an-hour' ) );
check( 'past the write limit the answer is 429', 429 === $limited->status && array( 'error' => 'Too many requests. Please slow down.' ) === $limited->data );

// --- Join ---

check( 'an onboarding record joins at the portal Coach page', 'https://example.test/portal/portal-home/' === ihq_portal_onboarding_appointment_join_redirect( null, array( 'id' => 'x', 'userId' => 7 ) ) );
check( 'a landing record is left to the landing flow', null === ihq_portal_onboarding_appointment_join_redirect( null, array( 'id' => 'x', 'ref' => 'r' ) ) );
check( 'an earlier decision on a landing record is kept', 'https://elsewhere.test/' === ihq_portal_onboarding_appointment_join_redirect( 'https://elsewhere.test/', array( 'id' => 'x' ) ) );
check( 'a userId that is not a number is not an onboarding record', null === ihq_portal_onboarding_appointment_join_redirect( null, array( 'id' => 'x', 'userId' => '7' ) ) );
check( 'a record that is not an array is left alone', null === ihq_portal_onboarding_appointment_join_redirect( null, 'x' ) );

$GLOBALS['wp_options'] = array();
$GLOBALS['user_meta']  = array();
$start  = $now + 3600;
$record = ihq_portal_onboarding_appointment_create( 7, $start, 'UTC', $now );
$token  = ihq_aicoach_appointment_make_token( $record['id'], $start );
$join   = function ( $at ) use ( $token ) {
	return ihq_aicoach_appointment_join_decision( array( 't' => $token, '_wpnonce' => 'good-nonce' ), 'https://example.test/appointment/', 'https://example.test/ai-coach/', $at, false );
};
check( 'on the link page, Join inside the window leads to the portal Coach page with no ref', array( 'ref' => null, 'redirect' => 'https://example.test/portal/portal-home/' ) === $join( $start ) );
check( 'outside the window it shows the page again', array( 'ref' => null, 'redirect' => 'https://example.test/appointment/?t=' . rawurlencode( $token ) ) === $join( $start - 3600 ) );

// --- completing the onboarding ---

ihq_portal_onboarding_mark_complete( 7 );
check( 'completing the onboarding leaves the link joinable', 'join' === ihq_aicoach_appointment_page_context( $token, $start )['state'] );
check( 'and Join still leads to the portal Coach page', array( 'ref' => null, 'redirect' => 'https://example.test/portal/portal-home/' ) === $join( $start ) );

echo $fail ? "\n$fail FAILED\n" : "\nALL PASS\n";
exit( $fail ? 1 : 0 );
