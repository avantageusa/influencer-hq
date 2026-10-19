<?php
/**
 * Unit test for screen 14's draft routes (ENGR-7570, inc/portal-onboarding-drafts.php).
 * No WordPress bootstrap: user meta, the current user, nonces, filters, the REST classes and
 * Gary (ihq_coach_request()) are stubbed in memory. The real progress store
 * (inc/portal-onboarding-progress.php) runs on the stubbed user meta.
 *
 *     php tests/portal-onboarding-drafts.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

// --- WordPress stubs ---

$GLOBALS['user_meta']     = array();
$GLOBALS['meta_writes']   = array();
$GLOBALS['actions_fired'] = array();
$GLOBALS['hooks']         = array();
$GLOBALS['routes']        = array();
$GLOBALS['filters']       = array();
$GLOBALS['current_user']  = 0;
$GLOBALS['influencers']   = array();
$GLOBALS['gary_calls']    = array();
$GLOBALS['gary_reply']    = null;

function add_action( $hook, $cb ) { $GLOBALS['hooks'][] = array( $hook, $cb ); }
function do_action( $hook, ...$args ) { $GLOBALS['actions_fired'][] = array_merge( array( $hook ), $args ); }
function apply_filters( $hook, $value, ...$args ) {
	return isset( $GLOBALS['filters'][ $hook ] ) ? call_user_func( $GLOBALS['filters'][ $hook ], $value, ...$args ) : $value;
}
function get_user_meta( $user_id, $key, $single = false ) {
	return isset( $GLOBALS['user_meta'][ $user_id ][ $key ] ) ? $GLOBALS['user_meta'][ $user_id ][ $key ] : '';
}
function update_user_meta( $user_id, $key, $value ) {
	$GLOBALS['meta_writes'][]                 = array( $user_id, $key, $value );
	$GLOBALS['user_meta'][ $user_id ][ $key ] = $value;
	return true;
}
function get_current_user_id() { return $GLOBALS['current_user']; }
function is_user_logged_in() { return $GLOBALS['current_user'] > 0; }
function wp_verify_nonce( $nonce, $action ) { return 'wp_rest' === $action && 'good-nonce' === $nonce ? 1 : false; }
function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $s ) ) ); }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) ); }
// The real function only strips invalid UTF-8; the stub marks that it ran.
function wp_check_invalid_utf8( $s, $strip = false ) { return true === $strip ? (string) $s : 'NOT STRIPPED'; }
function wp_salt( $scheme = 'auth' ) { return 'auth' === $scheme ? 'test-auth-salt' : 'WRONG SALT'; }
function register_rest_route( $namespace, $route, $args ) { $GLOBALS['routes'][] = array( $namespace, $route, $args ); }

class WP_Error {
	public $code;
	public $message;
	public $data;
	public function __construct( $code = '', $message = '', $data = array() ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_message() { return $this->message; }
	public function get_error_code() { return $this->code; }
}
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
class WP_REST_Server {
	const READABLE  = 'GET';
	const CREATABLE = 'POST';
}
class WP_REST_Response {
	public $data;
	public $status;
	public $headers = array();
	public function __construct( $data = null, $status = 200 ) { $this->data = $data; $this->status = $status; }
	public function header( $key, $value ) { $this->headers[ $key ] = $value; }
}
class WP_REST_Request {
	private $headers;
	private $json;
	public function __construct( $headers = array(), $json = null ) { $this->headers = $headers; $this->json = $json; }
	public function get_header( $key ) { return isset( $this->headers[ $key ] ) ? $this->headers[ $key ] : null; }
	public function get_json_params() { return $this->json; }
}

// --- Stubs for the other theme modules the routes call ---

function ihq_portal_onboarding_is_influencer( $user_id ) { return in_array( $user_id, $GLOBALS['influencers'], true ); }
function ihq_env_is_https_url( $url ) { return is_string( $url ) && 1 === preg_match( '#^https://[^/\s]+#', $url ); }
function ihq_coach_is_json_list( $value ) { return is_array( $value ) && ( array() === $value || array_keys( $value ) === range( 0, count( $value ) - 1 ) ); }
// Gary: records each call and answers with $GLOBALS['gary_reply'] (an array or a WP_Error).
function ihq_coach_request( $method, $path, $body_array = null, $extra_headers = array(), $timeout = 15 ) {
	$GLOBALS['gary_calls'][] = array( $method, $path, $body_array, $extra_headers, $timeout );
	return $GLOBALS['gary_reply'];
}

require __DIR__ . '/../inc/portal-onboarding-progress.php';
require __DIR__ . '/../inc/portal-onboarding-drafts.php';

// Failures are logged with error_log(); send them to a file the checks can read.
$log_file = tempnam( sys_get_temp_dir(), 'ihq-drafts-log' );
ini_set( 'error_log', $log_file );
// With the log redirected, a fatal error would otherwise end the run without a word.
ini_set( 'display_errors', 'stderr' );
function logged() {
	global $log_file;
	$lines = array();
	foreach ( file( $log_file, FILE_IGNORE_NEW_LINES ) ?: array() as $line ) {
		$lines[] = preg_replace( '/^\[[^\]]*\] /', '', $line );
	}
	return $lines;
}

const USER = 7;
const GUID = 'wpinfluencer_abc123';

function reset_world( $platforms = array( 'instagram', 'x' ), $drafts = array() ) {
	global $log_file;
	file_put_contents( $log_file, '' );
	$GLOBALS['user_meta']     = array( USER => array( 'wp_influencer_guid' => GUID ) );
	$GLOBALS['current_user']  = USER;
	$GLOBALS['influencers']   = array( USER );
	$GLOBALS['filters']       = array();
	if ( array() !== $platforms ) {
		ihq_portal_onboarding_merge( USER, array( 'platforms' => $platforms, 'language' => 'ja' ) );
	}
	if ( array() !== $drafts ) {
		ihq_portal_onboarding_store_drafts( USER, $drafts );
	}
	$GLOBALS['meta_writes']   = array();
	$GLOBALS['actions_fired'] = array();
	$GLOBALS['gary_calls']    = array();
	$GLOBALS['gary_reply']    = null;
}
function gary_answers( $status, $body ) { $GLOBALS['gary_reply'] = array( 'status' => $status, 'body' => $body ); }
function gary_posts( $posts, $extra = array() ) {
	gary_answers( 200, array_merge( array( 'status' => 'draft', 'lang' => 'ja', 'posts' => $posts, 'publishes' => false, 'account_connection_required' => false ), $extra ) );
}
function request( $body, $nonce = 'good-nonce' ) { return new WP_REST_Request( array( 'X-WP-Nonce' => $nonce ), $body ); }
function draft( $body ) { return ihq_portal_onboarding_handle_drafts( request( $body ) ); }
function revise( $body ) { return ihq_portal_onboarding_handle_revise( request( $body ) ); }
function stored_drafts() { return $GLOBALS['user_meta'][ USER ]['_ihq_portal_onboarding_drafts'] ?? null; }
function only_call() { return 1 === count( $GLOBALS['gary_calls'] ) ? $GLOBALS['gary_calls'][0] : null; }

$brief = IHQ_PORTAL_ONBOARDING_DRAFT_BRIEF_DEFAULT;

// --- Contract ---

check( 'Gary paths', '/coach/v1/posting/draft' === IHQ_PORTAL_ONBOARDING_POSTING_DRAFT_PATH && '/coach/v1/posting/revise' === IHQ_PORTAL_ONBOARDING_POSTING_REVISE_PATH );
check( 'at most 8 platforms per request', 8 === IHQ_PORTAL_ONBOARDING_DRAFTS_PER_REQUEST );
check( 'Gary gets 40 seconds', 40 === IHQ_PORTAL_ONBOARDING_DRAFTS_TIMEOUT );
check( 'a typed instruction is at most 500 characters', 500 === IHQ_PORTAL_ONBOARDING_INSTRUCTION_MAX_LENGTH );
check( 'a recording is at most 1 MB', 1048576 === IHQ_PORTAL_ONBOARDING_AUDIO_MAX_BYTES );
check( 'accepted recording types and their extensions', array( 'audio/webm' => 'webm', 'audio/ogg' => 'ogg', 'audio/mp4' => 'm4a' ) === IHQ_PORTAL_ONBOARDING_AUDIO_TYPES );
check( 'error codes and their statuses', array( 'invalid' => 400, 'refused' => 422, 'limit' => 429, 'unavailable' => 503, 'failed' => 502 ) === IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_STATUS );
check( 'error code constants', array( 'invalid', 'refused', 'limit', 'unavailable', 'failed' ) === array( IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_INVALID, IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_REFUSED, IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_LIMIT, IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_UNAVAILABLE, IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_FAILED ) );
check( 'Gary statuses that mean something to the Influencer', array( 422 => 'refused', 429 => 'limit', 502 => 'failed' ) === IHQ_PORTAL_ONBOARDING_GARY_STATUS_ERRORS );
check( 'brief filter name', 'ihq_portal_onboarding_draft_brief' === IHQ_PORTAL_ONBOARDING_DRAFT_BRIEF_FILTER );

// --- Routes ---

check( 'routes are registered on rest_api_init', in_array( array( 'rest_api_init', 'ihq_portal_onboarding_register_drafts_routes' ), $GLOBALS['hooks'], true ) );
ihq_portal_onboarding_register_drafts_routes();
check( 'POST /onboarding/drafts and /onboarding/drafts/revise, both permission-checked', array(
	array( 'ihq/v1', '/onboarding/drafts', array( 'methods' => 'POST', 'callback' => 'ihq_portal_onboarding_handle_drafts', 'permission_callback' => 'ihq_portal_onboarding_drafts_permission_check' ) ),
	array( 'ihq/v1', '/onboarding/drafts/revise', array( 'methods' => 'POST', 'callback' => 'ihq_portal_onboarding_handle_revise', 'permission_callback' => 'ihq_portal_onboarding_drafts_permission_check' ) ),
) === $GLOBALS['routes'] );

// --- Permission: logged in, nonce, Influencer ---

reset_world();
$GLOBALS['current_user'] = 0;
$denied = ihq_portal_onboarding_drafts_permission_check( request( array() ) );
check( 'logged out is 401', $denied instanceof WP_Error && 'ihq_onboarding_logged_out' === $denied->code && array( 'status' => 401 ) === $denied->data );
reset_world();
$denied = ihq_portal_onboarding_drafts_permission_check( request( array(), 'forged' ) );
check( 'a bad nonce is 403', $denied instanceof WP_Error && 'ihq_onboarding_bad_nonce' === $denied->code && array( 'status' => 403 ) === $denied->data );
$GLOBALS['influencers'] = array();
$denied = ihq_portal_onboarding_drafts_permission_check( request( array() ) );
check( 'a logged-in user without the Influencer role is 403', $denied instanceof WP_Error && 'ihq_onboarding_not_influencer' === $denied->code && array( 'status' => 403 ) === $denied->data );
$GLOBALS['influencers'] = array( USER );
check( 'an Influencer with the nonce is allowed', true === ihq_portal_onboarding_drafts_permission_check( request( array() ) ) );

// --- influencer_id: the Braze guid, never an email ---

reset_world();
check( 'influencer_id is wp_influencer_guid', GUID === ihq_portal_onboarding_influencer_id( USER ) );
$GLOBALS['user_meta'][ USER ]['wp_influencer_guid'] = '  ' . GUID . "\n";
check( 'influencer_id is trimmed', GUID === ihq_portal_onboarding_influencer_id( USER ) );
$stand_in = 'wpuser_' . substr( hash_hmac( 'sha256', '7', 'test-auth-salt' ), 0, 24 );
$GLOBALS['user_meta'][ USER ]['wp_influencer_guid'] = 'someone@example.com';
check( 'an email in wp_influencer_guid is replaced by the pseudonymous stand-in', $stand_in === ihq_portal_onboarding_influencer_id( USER ) );
unset( $GLOBALS['user_meta'][ USER ]['wp_influencer_guid'] );
check( 'no wp_influencer_guid gets the same stand-in', $stand_in === ihq_portal_onboarding_influencer_id( USER ) );
check( 'the stand-in is stable for the user and differs between users', $stand_in === ihq_portal_onboarding_influencer_id( '7' ) && $stand_in !== ihq_portal_onboarding_influencer_id( 8 ) );

// --- The brief setting ---

reset_world();
check( 'the default brief and no link', array( 'brief' => $brief, 'link' => '' ) === ihq_portal_onboarding_draft_brief( USER ) );
$GLOBALS['filters']['ihq_portal_onboarding_draft_brief'] = function ( $value, $user_id ) {
	return 7 === $user_id ? array( 'brief' => ' Product brief ', 'link' => ' https://ihq.global/r/abc ' ) : $value;
};
check( 'the filter sets the brief and the link, trimmed, and gets the user id', array( 'brief' => 'Product brief', 'link' => 'https://ihq.global/r/abc' ) === ihq_portal_onboarding_draft_brief( USER ) );
$GLOBALS['filters']['ihq_portal_onboarding_draft_brief'] = function () { return array( 'brief' => '', 'link' => 'http://plain.example' ); };
check( 'an empty brief falls back to the default; a non-https link is dropped', array( 'brief' => $brief, 'link' => '' ) === ihq_portal_onboarding_draft_brief( USER ) );
$GLOBALS['filters']['ihq_portal_onboarding_draft_brief'] = function () { return array( 'brief' => array( 'x' ), 'link' => array( 'https://a.example' ) ); };
check( 'non-string values fall back', array( 'brief' => $brief, 'link' => '' ) === ihq_portal_onboarding_draft_brief( USER ) );
$GLOBALS['filters']['ihq_portal_onboarding_draft_brief'] = function () { return 'brief'; };
check( 'a filter answer that is not an array falls back', array( 'brief' => $brief, 'link' => '' ) === ihq_portal_onboarding_draft_brief( USER ) );

// --- Language ---

$state_ja = array( 'language' => 'ja' );
check( 'a requested language of the seven is used', 'ko' === ihq_portal_onboarding_draft_language( 'ko', $state_ja ) );
check( 'an unknown requested language falls back to the saved one', 'ja' === ihq_portal_onboarding_draft_language( 'fr', $state_ja ) && 'ja' === ihq_portal_onboarding_draft_language( null, $state_ja ) && 'ja' === ihq_portal_onboarding_draft_language( array( 'ko' ), $state_ja ) );
check( 'with no saved language, English', 'en' === ihq_portal_onboarding_draft_language( 'KO', array( 'language' => '' ) ) );

// --- POST /drafts: success ---

reset_world();
gary_posts( array( array( 'platform' => 'instagram', 'text' => "インスタ\n投稿" ), array( 'platform' => 'x', 'text' => 'X の投稿' ) ) );
$res = draft( array( 'platforms' => array( 'instagram', 'x' ), 'lang' => 'ja' ) );
check( 'drafts: one signed POST to /posting/draft with the contract body and the 40 s timeout', array( 'POST', '/coach/v1/posting/draft', array( 'influencer_id' => GUID, 'lang' => 'ja', 'platforms' => array( 'instagram', 'x' ), 'brief' => $brief ), array(), 40 ) === only_call() );
check( 'drafts: 200 with the drafts written', 200 === $res->status && '{"drafts":{"instagram":"インスタ\n投稿","x":"X の投稿"}}' === json_encode( $res->data, JSON_UNESCAPED_UNICODE ) );
check( 'drafts: the answer is never cached', array( 'Cache-Control' => 'no-store, private' ) === $res->headers );
check( 'drafts: stored once in the drafts key', array( array( USER, '_ihq_portal_onboarding_drafts', array( 'instagram' => "インスタ\n投稿", 'x' => 'X の投稿' ) ) ) === $GLOBALS['meta_writes'] );
check( 'drafts: the saved action fires with the drafts', 1 === count( $GLOBALS['actions_fired'] ) && array( 'drafts' => array( 'instagram' => "インスタ\n投稿", 'x' => 'X の投稿' ) ) === $GLOBALS['actions_fired'][0][2] );
check( 'drafts: nothing logged on success', array() === logged() );

reset_world();
$GLOBALS['filters']['ihq_portal_onboarding_draft_brief'] = function () { return array( 'brief' => 'Brief', 'link' => 'https://ihq.global/r/abc' ); };
gary_posts( array( array( 'platform' => 'x', 'text' => 'Post' ) ) );
draft( array( 'platforms' => array( 'x' ), 'lang' => 'en' ) );
check( 'drafts: the link is sent when the setting has one', array( 'influencer_id' => GUID, 'lang' => 'en', 'platforms' => array( 'x' ), 'brief' => 'Brief', 'link' => 'https://ihq.global/r/abc' ) === only_call()[2] );

reset_world();
gary_posts( array( array( 'platform' => 'x', 'text' => 'Post' ) ) );
draft( array( 'platforms' => array( 'x' ), 'lang' => 'xx' ) );
check( 'drafts: an unknown language is sent as the saved onboarding language', 'ja' === only_call()[2]['lang'] );

reset_world( array( 'instagram', 'x', 'line' ), array( 'x' => 'Old X' ) );
gary_posts( array( array( 'platform' => 'instagram', 'text' => 'I' ), array( 'platform' => 'line', 'text' => 'L' ) ) );
$res = draft( array( 'lang' => 'ja' ) );
check( 'drafts: platforms left out means the selected ones without a draft', array( 'instagram', 'line' ) === only_call()[2]['platforms'] && '{"drafts":{"instagram":"I","line":"L"}}' === json_encode( $res->data ) );
check( 'drafts: the earlier draft is kept', array( 'instagram' => 'I', 'x' => 'Old X', 'line' => 'L' ) === ihq_portal_onboarding_get_state( USER )['drafts'] );

reset_world( array( 'x' ), array( 'x' => 'Old X' ) );
$res = draft( array() );
check( 'drafts: nothing left to draft is 200 with no drafts and no Gary call', 200 === $res->status && '{"drafts":{}}' === json_encode( $res->data ) && array() === $GLOBALS['gary_calls'] && array() === $GLOBALS['meta_writes'] );

$nine = array( 'instagram', 'facebook', 'tiktok', 'youtube', 'bilibili', 'douyin', 'kakaotalk', 'line', 'linkedin' );
reset_world( $nine );
gary_posts( array( array( 'platform' => 'instagram', 'text' => 'I' ) ) );
draft( null );
check( 'drafts: with nine to draft, the first eight in selection order', array_slice( $nine, 0, 8 ) === only_call()[2]['platforms'] );

reset_world( array( 'instagram', 'x' ) );
gary_posts( array( array( 'platform' => 'x', 'text' => 'X' ) ) );
$res = draft( array( 'platforms' => array( 'instagram', 'x' ) ) );
check( 'drafts: a reply missing a platform stores and answers the ones that came back', 200 === $res->status && '{"drafts":{"x":"X"}}' === json_encode( $res->data ) && array( 'x' => 'X' ) === stored_drafts() );

// --- POST /drafts: invalid requests reach neither Gary nor the store ---

$invalid_drafts = array(
	'platforms not a list'        => array( array( 'platforms' => array( 'a' => 'x' ) ), 'Invalid platforms.' ),
	'platforms a string'          => array( array( 'platforms' => 'x' ), 'Invalid platforms.' ),
	'platforms empty'             => array( array( 'platforms' => array() ), 'Invalid platforms.' ),
	'platforms null'              => array( array( 'platforms' => null ), 'Invalid platforms.' ),
	'a platform not selected'     => array( array( 'platforms' => array( 'x', 'line' ) ), 'Platform not selected.' ),
	'an unknown platform'         => array( array( 'platforms' => array( 'myspace' ) ), 'Platform not selected.' ),
	'a platform not a string'     => array( array( 'platforms' => array( 1 ) ), 'Platform not selected.' ),
	'a duplicate platform'        => array( array( 'platforms' => array( 'x', 'x' ) ), 'Duplicate platform.' ),
	'more than eight'             => array( array( 'platforms' => array( 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x', 'x' ) ), 'At most 8 platforms per request.' ),
);
foreach ( $invalid_drafts as $label => $case ) {
	reset_world();
	$res = draft( $case[0] );
	check( 'drafts rejects ' . $label . ' with 400 and the reason', 400 === $res->status && array( 'error' => 'invalid', 'message' => $case[1] ) === $res->data );
	check( 'drafts rejects ' . $label . ' without calling Gary or storing', array() === $GLOBALS['gary_calls'] && array() === $GLOBALS['meta_writes'] );
}
reset_world( array() );
$res = draft( array( 'platforms' => array( 'x' ) ) );
check( 'drafts with no platform selected on screen 6 is 400, Gary not called', 400 === $res->status && array( 'error' => 'invalid', 'message' => 'No platforms selected.' ) === $res->data && array() === $GLOBALS['gary_calls'] );
reset_world();
gary_posts( array( array( 'platform' => 'x', 'text' => 'X' ) ) );
$res = ihq_portal_onboarding_handle_drafts( request( 'not json' ) );
check( 'drafts with a body that is not an object drafts the missing platforms', 200 === $res->status && array( 'instagram', 'x' ) === only_call()[2]['platforms'] );

// --- POST /drafts: Gary failures ---

$failures = array(
	'422 posting_refused'          => array( array( 422, array( 'error' => array( 'code' => 'posting_refused', 'message' => 'm' ) ) ), 'refused', 422, '422 posting_refused' ),
	'429 posting_budget_exhausted' => array( array( 429, array( 'error' => 'posting_budget_exhausted' ) ), 'limit', 429, '429 posting_budget_exhausted' ),
	'502 posting_failed'           => array( array( 502, array( 'code' => 'posting_failed' ) ), 'failed', 502, '502 posting_failed' ),
	'503 posting_unavailable'      => array( array( 503, array( 'error' => 'posting_unavailable' ) ), 'unavailable', 503, '503 posting_unavailable' ),
	'401 auth error'               => array( array( 401, array( 'error' => 'invalid_signature' ) ), 'unavailable', 503, '401 invalid_signature' ),
	'409 replay error'             => array( array( 409, array( 'error' => 'replayed_request' ) ), 'unavailable', 503, '409 replayed_request' ),
	'500 with no body'             => array( array( 500, array() ), 'unavailable', 503, '500 ' ),
	'200 without posts'            => array( array( 200, array( 'status' => 'draft' ) ), 'failed', 502, '200 reply without usable posts' ),
	'200 with posts not a list'    => array( array( 200, array( 'posts' => array( 'x' => array( 'platform' => 'x', 'text' => 'X' ) ) ) ), 'failed', 502, '200 reply without usable posts' ),
	'200 with only other platforms' => array( array( 200, array( 'posts' => array( array( 'platform' => 'line', 'text' => 'L' ) ) ) ), 'failed', 502, '200 reply without usable posts' ),
	'200 with empty text'          => array( array( 200, array( 'posts' => array( array( 'platform' => 'x', 'text' => ' ' ) ) ) ), 'failed', 502, '200 reply without usable posts' ),
	'200 with text not a string'   => array( array( 200, array( 'posts' => array( array( 'platform' => 'x', 'text' => array( 'X' ) ) ) ) ), 'failed', 502, '200 reply without usable posts' ),
	'200 with a 5001-character text' => array( array( 200, array( 'posts' => array( array( 'platform' => 'x', 'text' => str_repeat( 'あ', 5001 ) ) ) ) ), 'failed', 502, '200 reply without usable posts' ),
	'200 with a post not an object' => array( array( 200, array( 'posts' => array( 'X' ) ) ), 'failed', 502, '200 reply without usable posts' ),
	'200 with a platform not a string' => array( array( 200, array( 'posts' => array( array( 'platform' => 1, 'text' => 'X' ) ) ) ), 'failed', 502, '200 reply without usable posts' ),
	'199 status'                   => array( array( 199, array( 'posts' => array( array( 'platform' => 'x', 'text' => 'X' ) ) ) ), 'unavailable', 503, '199 ' ),
	'300 status'                   => array( array( 300, array( 'posts' => array( array( 'platform' => 'x', 'text' => 'X' ) ) ) ), 'unavailable', 503, '300 ' ),
);
foreach ( $failures as $label => $case ) {
	reset_world();
	gary_answers( $case[0][0], $case[0][1] );
	$res = draft( array( 'platforms' => array( 'x' ) ) );
	check( 'Gary ' . $label . ' answers ' . $case[2] . ' ' . $case[1], $case[2] === $res->status && array( 'error' => $case[1] ) === $res->data );
	check( 'Gary ' . $label . ' stores and fires nothing', array() === $GLOBALS['meta_writes'] && array() === $GLOBALS['actions_fired'] );
	check( 'Gary ' . $label . ' is logged with the status and code only', array( 'IHQ onboarding drafts: /coach/v1/posting/draft failed: ' . $case[3] ) === logged() );
}
reset_world();
$GLOBALS['gary_reply'] = new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
$res = draft( array( 'platforms' => array( 'x' ) ) );
check( 'a transport error or timeout is 503 unavailable, logged by its code', 503 === $res->status && array( 'error' => 'unavailable' ) === $res->data && array( 'IHQ onboarding drafts: /coach/v1/posting/draft failed: http_request_failed' ) === logged() );
reset_world();
$GLOBALS['gary_reply'] = new WP_Error( 'coach_not_configured', 'GARY_COACH_KEY / GARY_COACH_SECRET are not set.' );
$res = draft( array( 'platforms' => array( 'x' ) ) );
check( 'missing Coach credentials are 503 unavailable', 503 === $res->status && array( 'error' => 'unavailable' ) === $res->data );

reset_world();
gary_posts( array( array( 'platform' => 'x', 'text' => 'First' ), array( 'platform' => 'x', 'text' => 'Second' ), array( 'platform' => 'line', 'text' => 'L' ) ) );
$res = draft( array( 'platforms' => array( 'x' ) ) );
check( 'a repeated platform keeps the first post; platforms not asked for are dropped', '{"drafts":{"x":"First"}}' === json_encode( $res->data ) && array( 'x' => 'First' ) === stored_drafts() );

// Unticked on another device while Gary was writing: the store refuses, nothing is kept.
reset_world();
$res = ihq_portal_onboarding_drafts_success( USER, array( 'posts' => array( 'line' => 'L' ), 'heard' => '' ) );
check( 'a reply for a platform no longer selected is 502 failed and stores nothing', 502 === $res->status && array( 'error' => 'failed' ) === $res->data && array() === $GLOBALS['meta_writes'] );

// --- Gary error codes ---

check( 'error code from { error: string }', 'posting_refused' === ihq_portal_onboarding_gary_error_code( array( 'error' => 'posting_refused' ) ) );
check( 'error code from { error: { code } }', 'posting_failed' === ihq_portal_onboarding_gary_error_code( array( 'error' => array( 'code' => 'posting_failed' ) ) ) );
check( 'error code from { code }', 'replayed' === ihq_portal_onboarding_gary_error_code( array( 'code' => 'replayed' ) ) );
check( 'error code is sanitized for the log', 'badcode' === ihq_portal_onboarding_gary_error_code( array( 'error' => "Bad Code\n" ) ) );
check( 'no error code is empty', '' === ihq_portal_onboarding_gary_error_code( array() ) && '' === ihq_portal_onboarding_gary_error_code( array( 'error' => 5 ) ) && '' === ihq_portal_onboarding_gary_error_code( array( 'error' => array( 'message' => 'm' ) ) ) );

// --- POST /drafts/revise: typed ---

reset_world( array( 'instagram', 'x' ), array( 'instagram' => 'Old insta', 'x' => 'Old X' ) );
gary_posts( array( array( 'platform' => 'x', 'text' => 'Warmer X' ) ) );
$res = revise( array( 'platform' => 'x', 'lang' => 'ja', 'instruction' => '  Make it warmer and shorter.  ', 'text' => 'browser copy, ignored' ) );
check( 'revise: one POST to /posting/revise with the stored draft as the previous text and the instruction trimmed', array( 'POST', '/coach/v1/posting/revise', array( 'influencer_id' => GUID, 'lang' => 'ja', 'posts' => array( array( 'platform' => 'x', 'text' => 'Old X' ) ), 'instruction' => 'Make it warmer and shorter.' ), array(), 40 ) === only_call() );
check( 'revise: 200 with the new draft', 200 === $res->status && '{"drafts":{"x":"Warmer X"}}' === json_encode( $res->data ) );
check( 'revise: only that platform\'s draft is replaced', array( 'instagram' => 'Old insta', 'x' => 'Warmer X' ) === ihq_portal_onboarding_get_state( USER )['drafts'] );

reset_world( array( 'x' ), array( 'x' => 'Old X' ) );
gary_posts( array( array( 'platform' => 'x', 'text' => 'New' ) ) );
revise( array( 'platform' => 'x', 'instruction' => str_repeat( 'あ', 500 ) ) );
check( 'revise: a 500-character multi-byte instruction is accepted (characters, not bytes)', str_repeat( 'あ', 500 ) === only_call()[2]['instruction'] && 'ja' === only_call()[2]['lang'] );

reset_world( array( 'x' ), array( 'x' => 'Old X' ) );
gary_posts( array( array( 'platform' => 'line', 'text' => 'Wrong platform' ) ) );
$res = revise( array( 'platform' => 'x', 'instruction' => 'Shorter' ) );
check( 'revise: a reply for another platform is 502 failed and the draft is kept', 502 === $res->status && array( 'error' => 'failed' ) === $res->data && array( 'x' => 'Old X' ) === stored_drafts() );

reset_world( array( 'x' ), array( 'x' => 'Old X' ) );
gary_answers( 422, array( 'error' => 'posting_refused' ) );
$res = revise( array( 'platform' => 'x', 'instruction' => 'Shorter' ) );
check( 'revise: Gary refusing is 422 refused, the draft is kept, the revise path is logged', 422 === $res->status && array( 'error' => 'refused' ) === $res->data && array( 'x' => 'Old X' ) === stored_drafts() && array( 'IHQ onboarding drafts: /coach/v1/posting/revise failed: 422 posting_refused' ) === logged() );

// --- POST /drafts/revise: spoken ---

$recording = "\x1aE\xdf\xa3 fake webm bytes";
reset_world( array( 'x' ), array( 'x' => 'Old X' ) );
gary_posts( array( array( 'platform' => 'x', 'text' => 'Spoken X' ) ), array( 'heard' => 'もっと短く' ) );
$res = revise( array( 'platform' => 'x', 'lang' => 'ja', 'audio' => array( 'data' => base64_encode( $recording ), 'mime' => 'Audio/WebM;codecs=opus' ) ) );
check( 'revise: a recording goes as audio, its type without the codec, with a file name', array( 'influencer_id' => GUID, 'lang' => 'ja', 'posts' => array( array( 'platform' => 'x', 'text' => 'Old X' ) ), 'audio' => array( 'data' => base64_encode( $recording ), 'mime' => 'audio/webm', 'filename' => 'instruction.webm' ) ) === only_call()[2] );
check( 'revise: no instruction is sent with a recording', ! array_key_exists( 'instruction', only_call()[2] ) );
check( 'revise: heard is passed back with the new draft', 200 === $res->status && '{"drafts":{"x":"Spoken X"},"heard":"もっと短く"}' === json_encode( $res->data, JSON_UNESCAPED_UNICODE ) );

reset_world( array( 'x' ), array( 'x' => 'Old X' ) );
gary_posts( array( array( 'platform' => 'x', 'text' => 'Spoken X' ) ), array( 'heard' => array( 'x' ) ) );
$res = revise( array( 'platform' => 'x', 'audio' => array( 'data' => base64_encode( $recording ), 'mime' => 'audio/mp4' ) ) );
check( 'revise: Safari\'s audio/mp4 goes as instruction.m4a', array( 'data' => base64_encode( $recording ), 'mime' => 'audio/mp4', 'filename' => 'instruction.m4a' ) === only_call()[2]['audio'] );
check( 'revise: a heard that is not a string is left out', '{"drafts":{"x":"Spoken X"}}' === json_encode( $res->data ) );

reset_world( array( 'x' ), array( 'x' => 'Old X' ) );
gary_posts( array( array( 'platform' => 'x', 'text' => 'Ogg X' ) ) );
revise( array( 'platform' => 'x', 'audio' => array( 'data' => base64_encode( str_repeat( 'a', 1048576 ) ), 'mime' => 'audio/ogg' ) ) );
check( 'revise: a 1 MB audio/ogg recording is accepted as instruction.ogg', 'instruction.ogg' === only_call()[2]['audio']['filename'] && 1048576 === strlen( base64_decode( only_call()[2]['audio']['data'] ) ) );

// --- POST /drafts/revise: invalid requests reach neither Gary nor the store ---

$invalid_revisions = array(
	'no platform'                  => array( array( 'instruction' => 'Shorter' ), 'No draft for that platform.' ),
	'platform not a string'        => array( array( 'platform' => array( 'x' ), 'instruction' => 'Shorter' ), 'No draft for that platform.' ),
	'a platform with no draft'     => array( array( 'platform' => 'instagram', 'instruction' => 'Shorter' ), 'No draft for that platform.' ),
	'a platform no longer selected' => array( array( 'platform' => 'line', 'instruction' => 'Shorter' ), 'No draft for that platform.' ),
	'neither instruction nor audio' => array( array( 'platform' => 'x' ), 'Send an instruction or a recording, not both.' ),
	'both instruction and audio'   => array( array( 'platform' => 'x', 'instruction' => 'Shorter', 'audio' => array( 'data' => 'YQ==', 'mime' => 'audio/webm' ) ), 'Send an instruction or a recording, not both.' ),
	'instruction not a string'     => array( array( 'platform' => 'x', 'instruction' => array( 'Shorter' ) ), 'Invalid instruction.' ),
	'instruction null'             => array( array( 'platform' => 'x', 'instruction' => null ), 'Invalid instruction.' ),
	'instruction blank'            => array( array( 'platform' => 'x', 'instruction' => " \n " ), 'Invalid instruction.' ),
	'instruction of 501 characters' => array( array( 'platform' => 'x', 'instruction' => str_repeat( 'あ', 501 ) ), 'Invalid instruction.' ),
	'audio not an object'          => array( array( 'platform' => 'x', 'audio' => 'YQ==' ), 'Invalid recording.' ),
	'audio without data'           => array( array( 'platform' => 'x', 'audio' => array( 'mime' => 'audio/webm' ) ), 'Invalid recording.' ),
	'audio without mime'           => array( array( 'platform' => 'x', 'audio' => array( 'data' => 'YQ==' ) ), 'Invalid recording.' ),
	'audio data not a string'      => array( array( 'platform' => 'x', 'audio' => array( 'data' => array( 'YQ==' ), 'mime' => 'audio/webm' ) ), 'Invalid recording.' ),
	'audio mime not a string'      => array( array( 'platform' => 'x', 'audio' => array( 'data' => 'YQ==', 'mime' => array( 'audio/webm' ) ) ), 'Invalid recording.' ),
	'an unsupported type'          => array( array( 'platform' => 'x', 'audio' => array( 'data' => 'YQ==', 'mime' => 'audio/wav' ) ), 'Unsupported recording type.' ),
	'a type that only starts right' => array( array( 'platform' => 'x', 'audio' => array( 'data' => 'YQ==', 'mime' => 'audio/webmx' ) ), 'Unsupported recording type.' ),
	'data that is not base64'      => array( array( 'platform' => 'x', 'audio' => array( 'data' => 'not base64!', 'mime' => 'audio/webm' ) ), 'Invalid recording.' ),
	'empty data'                   => array( array( 'platform' => 'x', 'audio' => array( 'data' => '', 'mime' => 'audio/webm' ) ), 'Invalid recording.' ),
	'a recording over 1 MB'        => array( array( 'platform' => 'x', 'audio' => array( 'data' => base64_encode( str_repeat( 'a', 1048577 ) ), 'mime' => 'audio/webm' ) ), 'Invalid recording.' ),
);
foreach ( $invalid_revisions as $label => $case ) {
	reset_world( array( 'instagram', 'x' ), array( 'x' => 'Old X' ) );
	// A draft kept for a platform unticked since: stored, but not in the state.
	$GLOBALS['user_meta'][ USER ]['_ihq_portal_onboarding_drafts']['line'] = 'Hidden';
	$res = revise( $case[0] );
	check( 'revise rejects ' . $label . ' with 400 and the reason', 400 === $res->status && array( 'error' => 'invalid', 'message' => $case[1] ) === $res->data );
	check( 'revise rejects ' . $label . ' without calling Gary or storing', array() === $GLOBALS['gary_calls'] && array() === $GLOBALS['meta_writes'] );
}

// --- The module is loaded by functions.php ---

$functions = file_get_contents( __DIR__ . '/../functions.php' );
check( 'functions.php loads every inc/portal-onboarding-*.php module', false !== strpos( $functions, "glob( get_template_directory() . '/inc/portal-onboarding-*.php' )" ) );

unlink( $log_file );
echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
