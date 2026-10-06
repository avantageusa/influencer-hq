<?php
/**
 * ENGR-7017 boundary tests for inc/ihq-platform-token.php and the
 * influencerhq-api handlers in inc/api-ajax-calls.php: an expired platform ID
 * token is refreshed (start-session re-run) before the call, a 401 refreshes
 * once and retries once, a request never refreshes twice, a failed refresh
 * returns today's error, and the share link keeps its new-influencer
 * 404 -> start-session -> retry path. No WordPress bootstrap and no live
 * requests:
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/platform-id-token-refresh.test.php
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

const API_BASE          = 'https://api.example.test/qc';
const START_SESSION_URL = API_BASE . '/account/oauth/start-session';
const PLAYER_ME_URL     = API_BASE . '/account/players/me';
const FULLNAME_URL      = API_BASE . '/account/players/fullname';
const CREATE_URL        = API_BASE . '/rankings/createChallenge';
const JOIN_URL          = API_BASE . '/rankings/joinChallenges';
const DETAILS_URL       = API_BASE . '/rankings/getChallengeDetails/ch-9';
const LIST_URL          = API_BASE . '/rankings/getChallengesForPlayer?authenticatedUser=sub-1';
const USER_ID           = 42;
const SUB               = 'sub-1';
const LINK_URL_WPU      = API_BASE . '/referral/user/influencerhq-wpu-42/link';
const LINK_URL_SUB      = API_BASE . '/referral/user/sub-1/link';
const LINK_URL_SHORT    = API_BASE . '/referral/user/wpu-42/link';
const SHARE_LINK        = 'https://ihq.example.test/r/ann';

function jwt( $sub, $marker ) {
	$payload = rtrim( strtr( base64_encode( json_encode( array( 'sub' => $sub, 'marker' => $marker ) ) ), '+/', '-_' ), '=' );
	return 'header.' . $payload . '.sig-' . $marker;
}
define( 'OLD_TOKEN', jwt( SUB, 'old' ) );
define( 'NEW_TOKEN', jwt( SUB, 'new' ) );
define( 'SESSION_OK', json_encode( array( 'success' => true, 'data' => array( 'AccessToken' => 'at-new', 'IdToken' => NEW_TOKEN, 'RefreshToken' => 'rt-new', 'ExpiresIn' => 3600 ) ) ) );
const SESSION_FAIL = '{"success":false,"message":"boom"}';

class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
class WP_User {
	public $ID;
	public $user_email = 'ann@example.test';
	public $first_name = 'Ann';
	public $last_name  = 'Lee';
	public function __construct( $id ) { $this->ID = $id; }
}
class StopRequest extends Exception {}

function reset_state() {
	$GLOBALS['requests']  = array();
	$GLOBALS['responses'] = array();
	$GLOBALS['user_meta'] = array();
	$GLOBALS['json']      = null;
	$_COOKIE              = array();
	$_POST                = array();
}

// --- WordPress stubs. ---
function add_action( ...$args ) {}
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function wp_parse_url( $u ) { return parse_url( $u ); }
function is_ssl() { return true; }
function wp_unslash( $v ) { return is_string( $v ) ? stripslashes( $v ) : $v; }
function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $s ) ) ); }
function esc_url_raw( $s ) { return $s; }
function wp_json_encode( $v, $flags = 0 ) { return json_encode( $v, $flags ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function current_time( $t ) { return '2026-10-05 00:00:00'; }
function check_ajax_referer( ...$args ) { return true; }
function get_current_user_id() { return USER_ID; }
function get_user_by( $field, $id ) { return new WP_User( $id ); }
function get_userdata( $id ) { return new WP_User( $id ); }
function get_user_meta( $id, $key, $single = false ) { return $GLOBALS['user_meta'][ $id ][ $key ] ?? ''; }
function update_user_meta( $id, $key, $value ) { $GLOBALS['user_meta'][ $id ][ $key ] = $value; return true; }
function wp_send_json_success( $data = null ) { $GLOBALS['json'] = array( 'success' => true, 'data' => $data ); throw new StopRequest( 'json' ); }
function wp_send_json_error( $data = null ) { $GLOBALS['json'] = array( 'success' => false, 'data' => $data ); throw new StopRequest( 'json' ); }

function mock_http( $method, $url, $args ) {
	$GLOBALS['requests'][] = array(
		'method' => $method,
		'url'    => $url,
		'auth'   => $args['headers']['Authorization'] ?? null,
		'body'   => $args['body'] ?? null,
	);
	$queue = &$GLOBALS['responses'][ $url ];
	if ( empty( $queue ) ) {
		throw new Exception( 'unexpected request to ' . $url );
	}
	return array_shift( $queue );
}
function wp_remote_post( $url, $args ) { return mock_http( 'POST', $url, $args ); }
function wp_remote_get( $url, $args ) { return mock_http( 'GET', $url, $args ); }
function wp_remote_request( $url, $args ) { return mock_http( $args['method'] ?? 'GET', $url, $args ); }
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function wp_remote_retrieve_response_code( $r ) { return $r['status']; }

function respond( $url, $status, $body ) { $GLOBALS['responses'][ $url ][] = array( 'status' => $status, 'body' => $body ); }
function respond_transport_error( $url ) { $GLOBALS['responses'][ $url ][] = new WP_Error( 'http_request_failed', 'timed out' ); }

/** Requests as "METHOD url auth" lines; start-session shows no auth (server key). */
function request_log() {
	$lines = array();
	foreach ( $GLOBALS['requests'] as $request ) {
		$line = $request['method'] . ' ' . $request['url'];
		if ( $request['url'] !== START_SESSION_URL ) {
			$line .= ' ' . auth_token_label( $request['auth'] );
		}
		$lines[] = $line;
	}
	return $lines;
}
function auth_token_label( $auth ) {
	if ( $auth === 'Bearer ' . OLD_TOKEN ) {
		return 'old';
	}
	if ( $auth === 'Bearer ' . NEW_TOKEN ) {
		return 'new';
	}
	return var_export( $auth, true );
}
function session_line() { return 'POST ' . START_SESSION_URL; }

/** A logged-in user whose stored token expires at $expires_at ('' = no expiry stored). */
function given_user( $expires_at ) {
	$GLOBALS['user_meta'][ USER_ID ] = array(
		'ihq_id_token'          => OLD_TOKEN,
		'ihq_token_expires'     => $expires_at,
		'ihq_oauth_country_iso' => 'NL',
		'first_name'            => 'Ann',
		'last_name'             => 'Lee',
	);
}
function expired() { return time() - 10; }
function valid() { return time() + 3000; }

function run_handler( $handler ) {
	try {
		$handler();
	} catch ( StopRequest $e ) {
		return $GLOBALS['json'];
	}
	return null;
}

require __DIR__ . '/../inc/ihq-env.php';
require __DIR__ . '/../inc/ihq-referral-attribution.php';
require __DIR__ . '/../inc/email-verification-handler.php';
require __DIR__ . '/../inc/ihq-platform-token.php';
require __DIR__ . '/../inc/api-ajax-calls.php';

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

// --- ihq_platform_id_token_is_expired(): 60 s margin; unknown expiry is not expired. ---
check( 'expired: well before expiry', ihq_platform_id_token_is_expired( 1000, 900 ), false );
check( 'expired: one second before the margin', ihq_platform_id_token_is_expired( 1000, 939 ), false );
check( 'expired: at the margin', ihq_platform_id_token_is_expired( 1000, 940 ), true );
check( 'expired: past expiry', ihq_platform_id_token_is_expired( 1000, 2000 ), true );
check( 'expired: numeric-string meta', ihq_platform_id_token_is_expired( '1000', 940 ), true );
check( 'expired: empty meta', ihq_platform_id_token_is_expired( '', 5000 ), false );
check( 'expired: null meta', ihq_platform_id_token_is_expired( null, 5000 ), false );
check( 'expired: garbage meta', ihq_platform_id_token_is_expired( 'soon', 5000 ), false );

// --- ihq_refresh_influencer_oauth_tokens() now reports success. ---
reset_state();
given_user( expired() );
respond( START_SESSION_URL, 200, SESSION_OK );
check( 'refresh: true on success', ihq_refresh_influencer_oauth_tokens( USER_ID, 'NL' ), true );
check( 'refresh: new token stored', get_user_meta( USER_ID, 'ihq_id_token' ), NEW_TOKEN );
check( 'refresh: expiry pushed out', get_user_meta( USER_ID, 'ihq_token_expires' ) > time() + 3500, true );

reset_state();
given_user( expired() );
respond( START_SESSION_URL, 500, SESSION_FAIL );
check( 'refresh: false when start-session fails', ihq_refresh_influencer_oauth_tokens( USER_ID, 'NL' ), false );
check( 'refresh: old token kept on failure', get_user_meta( USER_ID, 'ihq_id_token' ), OLD_TOKEN );
check( 'refresh: false for user 0', ihq_refresh_influencer_oauth_tokens( 0, 'NL' ), false );

// --- ihq_refresh_platform_id_token(): stored country, returns the new token or ''. ---
reset_state();
given_user( expired() );
respond( START_SESSION_URL, 200, SESSION_OK );
check( 'platform refresh: returns new token', ihq_refresh_platform_id_token( USER_ID ), NEW_TOKEN );
check(
	'platform refresh: start-session sent with the stored country',
	json_decode( $GLOBALS['requests'][0]['body'], true )['payload']['countryIso'],
	'NL'
);

reset_state();
given_user( expired() );
respond( START_SESSION_URL, 500, SESSION_FAIL );
check( 'platform refresh: "" on failure', ihq_refresh_platform_id_token( USER_ID ), '' );

// --- ihq_platform_session_begin(). ---
reset_state();
given_user( 1000 );
check( 'begin: valid token, no refresh', ihq_platform_session_begin( USER_ID, 900 ), array( 'user_id' => USER_ID, 'id_token' => OLD_TOKEN, 'refreshed' => false ) );
check( 'begin: valid token, no request', $GLOBALS['requests'], array() );

reset_state();
given_user( 1000 );
respond( START_SESSION_URL, 200, SESSION_OK );
check( 'begin: expired token refreshed', ihq_platform_session_begin( USER_ID, 2000 ), array( 'user_id' => USER_ID, 'id_token' => NEW_TOKEN, 'refreshed' => true ) );
check( 'begin: expired token, one start-session', request_log(), array( session_line() ) );

reset_state();
given_user( 1000 );
respond( START_SESSION_URL, 500, SESSION_FAIL );
check( 'begin: failed refresh keeps old token, marks attempted', ihq_platform_session_begin( USER_ID, 2000 ), array( 'user_id' => USER_ID, 'id_token' => OLD_TOKEN, 'refreshed' => true ) );

reset_state();
given_user( '' );
check( 'begin: no stored expiry, no refresh', ihq_platform_session_begin( USER_ID, 2000 ), array( 'user_id' => USER_ID, 'id_token' => OLD_TOKEN, 'refreshed' => false ) );

reset_state();
check( 'begin: no token, no refresh', ihq_platform_session_begin( USER_ID, 2000 ), array( 'user_id' => USER_ID, 'id_token' => '', 'refreshed' => false ) );
check( 'begin: no token, no request', $GLOBALS['requests'], array() );

// --- Profile: GET /account/players/me. ---
$player = '{"firstName":"Ann","lastName":"Lee"}';

reset_state();
given_user( valid() );
respond( PLAYER_ME_URL, 200, $player );
check( 'me valid: success', run_handler( 'ihq_get_player_me_ajax' ), array( 'success' => true, 'data' => array( 'firstName' => 'Ann', 'lastName' => 'Lee' ) ) );
check( 'me valid: no start-session', request_log(), array( 'GET ' . PLAYER_ME_URL . ' old' ) );

reset_state();
given_user( expired() );
respond( START_SESSION_URL, 200, SESSION_OK );
respond( PLAYER_ME_URL, 200, $player );
check( 'me expired: success', run_handler( 'ihq_get_player_me_ajax' ), array( 'success' => true, 'data' => array( 'firstName' => 'Ann', 'lastName' => 'Lee' ) ) );
check( 'me expired: refresh then call with the new token', request_log(), array( session_line(), 'GET ' . PLAYER_ME_URL . ' new' ) );

reset_state();
given_user( expired() );
respond( START_SESSION_URL, 200, SESSION_OK );
respond( PLAYER_ME_URL, 401, '{"message":"Unauthorized"}' );
check(
	'me expired + 401: today\'s error',
	run_handler( 'ihq_get_player_me_ajax' ),
	array( 'success' => false, 'data' => array( 'message' => 'API returned HTTP 401', 'body' => array( 'message' => 'Unauthorized' ), '_sub' => SUB ) )
);
check( 'me expired + 401: no second refresh', request_log(), array( session_line(), 'GET ' . PLAYER_ME_URL . ' new' ) );

reset_state();
given_user( expired() );
respond( START_SESSION_URL, 500, SESSION_FAIL );
respond( PLAYER_ME_URL, 401, '{"message":"Unauthorized"}' );
check(
	'me expired + refresh fails: today\'s error',
	run_handler( 'ihq_get_player_me_ajax' ),
	array( 'success' => false, 'data' => array( 'message' => 'API returned HTTP 401', 'body' => array( 'message' => 'Unauthorized' ), '_sub' => SUB ) )
);
check( 'me expired + refresh fails: old token, one refresh, no retry', request_log(), array( session_line(), 'GET ' . PLAYER_ME_URL . ' old' ) );

reset_state();
given_user( valid() );
respond( PLAYER_ME_URL, 401, '{"message":"Unauthorized"}' );
respond( START_SESSION_URL, 200, SESSION_OK );
respond( PLAYER_ME_URL, 200, $player );
check( 'me 401: retried successfully', run_handler( 'ihq_get_player_me_ajax' ), array( 'success' => true, 'data' => array( 'firstName' => 'Ann', 'lastName' => 'Lee' ) ) );
check( 'me 401: call, refresh, one retry with the new token', request_log(), array( 'GET ' . PLAYER_ME_URL . ' old', session_line(), 'GET ' . PLAYER_ME_URL . ' new' ) );

reset_state();
given_user( valid() );
respond( PLAYER_ME_URL, 401, '{"message":"Unauthorized"}' );
respond( START_SESSION_URL, 200, SESSION_OK );
respond( PLAYER_ME_URL, 401, '{"message":"still"}' );
check(
	'me 401 twice: reports the retry\'s 401',
	run_handler( 'ihq_get_player_me_ajax' ),
	array( 'success' => false, 'data' => array( 'message' => 'API returned HTTP 401', 'body' => array( 'message' => 'still' ), '_sub' => SUB ) )
);
check( 'me 401 twice: retried once only', request_log(), array( 'GET ' . PLAYER_ME_URL . ' old', session_line(), 'GET ' . PLAYER_ME_URL . ' new' ) );

reset_state();
given_user( valid() );
respond( PLAYER_ME_URL, 401, '{"message":"Unauthorized"}' );
respond( START_SESSION_URL, 500, SESSION_FAIL );
check(
	'me 401 + refresh fails: today\'s error',
	run_handler( 'ihq_get_player_me_ajax' ),
	array( 'success' => false, 'data' => array( 'message' => 'API returned HTTP 401', 'body' => array( 'message' => 'Unauthorized' ), '_sub' => SUB ) )
);
check( 'me 401 + refresh fails: no retry', request_log(), array( 'GET ' . PLAYER_ME_URL . ' old', session_line() ) );

reset_state();
given_user( valid() );
respond( PLAYER_ME_URL, 500, '{"message":"down"}' );
check(
	'me 500: today\'s error',
	run_handler( 'ihq_get_player_me_ajax' ),
	array( 'success' => false, 'data' => array( 'message' => 'API returned HTTP 500', 'body' => array( 'message' => 'down' ), '_sub' => SUB ) )
);
check( 'me 500: no refresh for a non-401', request_log(), array( 'GET ' . PLAYER_ME_URL . ' old' ) );

reset_state();
given_user( valid() );
respond( PLAYER_ME_URL, 403, '{"message":"Forbidden"}' );
run_handler( 'ihq_get_player_me_ajax' );
check( 'me 403: no refresh for a non-401', request_log(), array( 'GET ' . PLAYER_ME_URL . ' old' ) );

reset_state();
given_user( valid() );
respond_transport_error( PLAYER_ME_URL );
check( 'me transport error: today\'s error', run_handler( 'ihq_get_player_me_ajax' ), array( 'success' => false, 'data' => array( 'message' => 'timed out' ) ) );
check( 'me transport error: no refresh', request_log(), array( 'GET ' . PLAYER_ME_URL . ' old' ) );

reset_state();
check( 'me no token: today\'s error', run_handler( 'ihq_get_player_me_ajax' ), array( 'success' => false, 'data' => array( 'message' => 'No IHQ session token — run SSO first.' ) ) );
check( 'me no token: no request', $GLOBALS['requests'], array() );

reset_state();
given_user( '' );
respond( PLAYER_ME_URL, 200, $player );
run_handler( 'ihq_get_player_me_ajax' );
check( 'me no stored expiry: no proactive refresh', request_log(), array( 'GET ' . PLAYER_ME_URL . ' old' ) );

// --- Profile: PATCH /account/players/fullname. ---
reset_state();
given_user( expired() );
$_POST = array( 'firstName' => 'Annie', 'lastName' => 'Lee' );
respond( START_SESSION_URL, 200, SESSION_OK );
respond( FULLNAME_URL, 200, '{}' );
check( 'fullname expired: success', run_handler( 'ihq_update_fullname_ajax' ), array( 'success' => true, 'data' => array( 'firstName' => 'Annie', 'lastName' => 'Lee' ) ) );
check( 'fullname expired: refresh then PATCH with the new token', request_log(), array( session_line(), 'PATCH ' . FULLNAME_URL . ' new' ) );
check( 'fullname expired: body unchanged', json_decode( $GLOBALS['requests'][1]['body'], true ), array( 'firstName' => 'Annie', 'lastName' => 'Lee' ) );
check( 'fullname expired: WP meta synced', array( get_user_meta( USER_ID, 'first_name' ), get_user_meta( USER_ID, 'last_name' ) ), array( 'Annie', 'Lee' ) );

reset_state();
given_user( valid() );
$_POST = array( 'firstName' => 'Annie', 'lastName' => 'Lee' );
respond( FULLNAME_URL, 401, '{}' );
respond( START_SESSION_URL, 200, SESSION_OK );
respond( FULLNAME_URL, 204, '' );
check( 'fullname 401: retried successfully', run_handler( 'ihq_update_fullname_ajax' ), array( 'success' => true, 'data' => array( 'firstName' => 'Annie', 'lastName' => 'Lee' ) ) );
check( 'fullname 401: PATCH, refresh, one retry', request_log(), array( 'PATCH ' . FULLNAME_URL . ' old', session_line(), 'PATCH ' . FULLNAME_URL . ' new' ) );
check( 'fullname 401: retry sends the same body', $GLOBALS['requests'][2]['body'], $GLOBALS['requests'][0]['body'] );

// --- Challenges: /rankings/*. ---
reset_state();
given_user( expired() );
$_POST = array( 'name' => 'Duel', 'scheduledStartDateTime' => '2026-10-06T00:00:00.000Z', 'challengedPlayers' => 'bob' );
respond( START_SESSION_URL, 200, SESSION_OK );
respond( CREATE_URL, 200, '{"challengeId":"ch-9"}' );
$created = run_handler( 'create_challenge_ajax' );
check( 'create expired: success', array( $created['success'], $created['data']['challengeId'] ), array( true, 'ch-9' ) );
check( 'create expired: refresh then POST with the new token', request_log(), array( session_line(), 'POST ' . CREATE_URL . ' new' ) );
check( 'create expired: body carries the sub', json_decode( $GLOBALS['requests'][1]['body'], true )['authenticatedUser'], SUB );

reset_state();
given_user( valid() );
respond( LIST_URL, 401, '{"message":"Unauthorized"}' );
respond( START_SESSION_URL, 200, SESSION_OK );
respond( LIST_URL, 200, '{"challenges":[]}' );
$listed = run_handler( 'get_challenges_for_player_ajax' );
check( 'list 401: success after retry', array( $listed['success'], $listed['data']['challenges'], $listed['data']['_debug']['http_status'] ), array( true, array(), 200 ) );
check( 'list 401: GET, refresh, one retry', request_log(), array( 'GET ' . LIST_URL . ' old', session_line(), 'GET ' . LIST_URL . ' new' ) );

reset_state();
given_user( valid() );
respond( LIST_URL, 401, '{"message":"Unauthorized"}' );
respond( START_SESSION_URL, 500, SESSION_FAIL );
$listed = run_handler( 'get_challenges_for_player_ajax' );
check( 'list 401 + refresh fails: today\'s error', array( $listed['success'], $listed['data']['message'], $listed['data']['_debug']['http_status'] ), array( false, 'Unauthorized', 401 ) );

reset_state();
given_user( expired() );
$_POST = array( 'challengeId' => 'ch-9' );
respond( START_SESSION_URL, 200, SESSION_OK );
respond( DETAILS_URL, 200, '{"challengeId":"ch-9"}' );
$details = run_handler( 'get_challenge_details_ajax' );
check( 'details expired: success', $details['success'], true );
check( 'details expired: refresh then GET with the new token', request_log(), array( session_line(), 'GET ' . DETAILS_URL . ' new' ) );

reset_state();
given_user( valid() );
$_POST = array( 'challengeId' => 'ch-9', 'teamName' => 'Red' );
respond( JOIN_URL, 401, '{}' );
respond( START_SESSION_URL, 200, SESSION_OK );
respond( JOIN_URL, 200, '{"joined":true}' );
$joined = run_handler( 'join_challenges_ajax' );
check( 'join 401: success after retry', array( $joined['success'], $joined['data']['joined'] ), array( true, true ) );
check( 'join 401: POST, refresh, one retry', request_log(), array( 'POST ' . JOIN_URL . ' old', session_line(), 'POST ' . JOIN_URL . ' new' ) );
check( 'join 401: retry sends the same body', $GLOBALS['requests'][2]['body'], $GLOBALS['requests'][0]['body'] );

reset_state();
given_user( valid() );
$_POST = array( 'challengeId' => 'ch-9' );
respond( JOIN_URL, 200, '{"joined":true}' );
run_handler( 'join_challenges_ajax' );
check( 'join valid: no start-session', request_log(), array( 'POST ' . JOIN_URL . ' old' ) );

// --- Share link: GET /referral/user/{id}/link. ---
$link_ok    = '{"url":"' . SHARE_LINK . '"}';
$link_found = array( 'success' => true, 'data' => array( 'url' => SHARE_LINK, 'follower_url' => SHARE_LINK, 'influencer_url' => SHARE_LINK ) );
function all_candidates_404() {
	respond( LINK_URL_WPU, 404, '{"message":"not found"}' );
	respond( LINK_URL_SUB, 404, '{"message":"not found"}' );
	respond( LINK_URL_SHORT, 404, '{"message":"not found"}' );
}

// New influencer: token valid, link 404 until start-session provisions the referral.
reset_state();
given_user( valid() );
all_candidates_404();
respond( START_SESSION_URL, 200, SESSION_OK );
respond( LINK_URL_WPU, 200, $link_ok );
check( 'link new influencer: link shown', run_handler( 'get_referral_link_ajax' ), $link_found );
check(
	'link new influencer: 404s, start-session, retry with the new token',
	request_log(),
	array(
		'GET ' . LINK_URL_WPU . ' old',
		'GET ' . LINK_URL_SUB . ' old',
		'GET ' . LINK_URL_SHORT . ' old',
		session_line(),
		'GET ' . LINK_URL_WPU . ' new',
	)
);

reset_state();
given_user( expired() );
respond( START_SESSION_URL, 200, SESSION_OK );
respond( LINK_URL_WPU, 200, $link_ok );
check( 'link expired: link shown', run_handler( 'get_referral_link_ajax' ), $link_found );
check( 'link expired: refresh first, fetch with the new token', request_log(), array( session_line(), 'GET ' . LINK_URL_WPU . ' new' ) );

reset_state();
given_user( expired() );
respond( START_SESSION_URL, 200, SESSION_OK );
all_candidates_404();
check(
	'link expired + 404: today\'s error',
	run_handler( 'get_referral_link_ajax' ),
	array( 'success' => false, 'data' => array( 'message' => 'API returned HTTP 404', 'body' => array( 'message' => 'not found' ) ) )
);
check(
	'link expired + 404: one start-session, no second round',
	request_log(),
	array( session_line(), 'GET ' . LINK_URL_WPU . ' new', 'GET ' . LINK_URL_SUB . ' new', 'GET ' . LINK_URL_SHORT . ' new' )
);

reset_state();
given_user( valid() );
respond( LINK_URL_WPU, 401, '{}' );
respond( LINK_URL_SUB, 401, '{}' );
respond( LINK_URL_SHORT, 401, '{}' );
respond( START_SESSION_URL, 200, SESSION_OK );
respond( LINK_URL_WPU, 200, $link_ok );
check( 'link 401: link shown after retry', run_handler( 'get_referral_link_ajax' ), $link_found );
check( 'link 401: one start-session, retry with the new token', array_slice( request_log(), 3 ), array( session_line(), 'GET ' . LINK_URL_WPU . ' new' ) );

reset_state();
given_user( valid() );
respond( LINK_URL_WPU, 200, $link_ok );
check( 'link valid: link shown', run_handler( 'get_referral_link_ajax' ), $link_found );
check( 'link valid: no start-session', request_log(), array( 'GET ' . LINK_URL_WPU . ' old' ) );

ob_end_clean();
echo implode( "\n", $out ) . "\n";
echo $fail === 0 ? "ALL PASS\n" : "$fail FAILED\n";
exit( $fail === 0 ? 0 : 1 );
