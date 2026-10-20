<?php
/**
 * Unit test for the account-scoped portal onboarding store (ENGR-7563,
 * inc/portal-onboarding-progress.php). No WordPress bootstrap: user meta, the
 * current user, nonces, actions and the REST classes are stubbed in memory.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/portal-onboarding-progress.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

// --- WordPress stubs ---

$GLOBALS['user_meta']      = array(); // user_id => [ key => value ]
$GLOBALS['meta_writes']    = array();
$GLOBALS['actions_fired']  = array();
$GLOBALS['hooks']          = array();
$GLOBALS['routes']         = array();
$GLOBALS['current_user']   = 0;
$GLOBALS['valid_nonce']    = 'good-nonce';
$GLOBALS['on_meta_write']  = null; // one-shot callback run just before a write lands, to interleave requests


function add_action( $hook, $cb ) { $GLOBALS['hooks'][] = array( $hook, $cb ); }
function do_action( $hook, ...$args ) { $GLOBALS['actions_fired'][] = array_merge( array( $hook ), $args ); }
function get_user_meta( $user_id, $key, $single = false ) {
	return isset( $GLOBALS['user_meta'][ $user_id ][ $key ] ) ? $GLOBALS['user_meta'][ $user_id ][ $key ] : '';
}
function update_user_meta( $user_id, $key, $value ) {
	$on_write = $GLOBALS['on_meta_write'];
	if ( null !== $on_write ) {
		$GLOBALS['on_meta_write'] = null;
		$on_write( $user_id, $key );
	}
	$GLOBALS['meta_writes'][]                   = array( $user_id, $key, $value );
	$GLOBALS['user_meta'][ $user_id ][ $key ] = $value;
	return true;
}
function get_current_user_id() { return $GLOBALS['current_user']; }
function is_user_logged_in() { return $GLOBALS['current_user'] > 0; }
function wp_verify_nonce( $nonce, $action ) { return 'wp_rest' === $action && $nonce === $GLOBALS['valid_nonce'] ? 1 : false; }
function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $s ) ) ); }
function register_rest_route( $namespace, $route, $args ) { $GLOBALS['routes'][] = array( $namespace, $route, $args ); }

class WP_Error {
	public $code;
	public $message;
	public $data;
	public function __construct( $code = '', $message = '', $data = array() ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_message() { return $this->message; }
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

function reset_world( $user_id = 7 ) {
	$GLOBALS['user_meta']     = array();
	$GLOBALS['meta_writes']   = array();
	$GLOBALS['actions_fired'] = array();
	$GLOBALS['current_user']  = $user_id;
	$GLOBALS['on_meta_write'] = null;
}
function post_request( $body, $nonce = 'good-nonce' ) { return new WP_REST_Request( array( 'X-WP-Nonce' => $nonce ), $body ); }
function stored( $user_id = 7 ) { return $GLOBALS['user_meta'][ $user_id ]['_ihq_portal_onboarding'] ?? null; }
function completed_meta( $user_id = 7 ) { return $GLOBALS['user_meta'][ $user_id ]['_ihq_portal_onboarding_completed_at'] ?? null; }

require __DIR__ . '/../inc/portal-onboarding-progress.php';

$empty_record = array(
	'stage'     => '',
	'answers'   => array(),
	'platforms' => array(),
	'handles'   => array(),
	'language'  => '',
);
$empty_state = array(
	'stage'        => '',
	'answers'      => array(),
	'platforms'    => array(),
	'handles'      => array(),
	'language'     => '',
	'drafts'       => array(),
	'completed'    => false,
	'completed_at' => '',
);

// --- Contract: names and enum values ---

check( 'meta key', '_ihq_portal_onboarding' === IHQ_PORTAL_ONBOARDING_META_KEY );
check( 'completion has its own meta key', '_ihq_portal_onboarding_completed_at' === IHQ_PORTAL_ONBOARDING_COMPLETED_META_KEY );
check( 'completion fields', array( 'completed', 'completed_at' ) === IHQ_PORTAL_ONBOARDING_COMPLETION_FIELDS );
check( 'saved action name', 'ihq_portal_onboarding_saved' === IHQ_PORTAL_ONBOARDING_SAVED_ACTION );
check( 'completed action name', 'ihq_portal_onboarding_completed' === IHQ_PORTAL_ONBOARDING_COMPLETED_ACTION );
check( 'invalid error code and status', 'ihq_onboarding_invalid' === IHQ_PORTAL_ONBOARDING_INVALID_ERROR && 400 === IHQ_PORTAL_ONBOARDING_INVALID_STATUS );
check( 'language meta key is the registration one', '_ihq_aicoach_language' === IHQ_PORTAL_ONBOARDING_LANGUAGE_META_KEY );
check( 'the stages in screen order, Talk more? and the appointment before closing (ENGR-7577)', array( 'game_of_kings', 'modern_era', 'baccarat_experience', 'streaming_experience', 'equity_knowledge', 'social_platforms', 'world_championship', 'world_tour', 'classic', 'equity', 'how_this_works', 'active_followers', 'equity_example', 'draft_posts', 'post_action', 'time_up', 'appointment', 'closing' ) === ihq_portal_onboarding_stages() );
check( 'answer fields and values', array(
	'baccarat_experience'       => array( 'played_a_lot', 'played_some', 'new_player' ),
	'streaming_experience'      => array( 'streamed_a_lot', 'streamed_some', 'new_to_streaming' ),
	'streaming_learning_intent' => array( 'new_ready_to_learn', 'new_maybe_later' ),
	'equity_knowledge'          => array( 'owns_equity', 'understands_equity', 'interested_in_equity' ),
	'equity_management'         => array( 'owns_self_managed', 'owns_managed_by_other' ),
	'active_follower_count'     => array( '100k_plus', '50k_plus', '25k_plus', '10k_plus', 'under_10k' ),
	'post_action_choice'        => array( 'post_now', 'edit_together', 'edit_later' ),
) === ihq_portal_onboarding_answer_values() );
check( 'the 19 platforms', array( 'instagram', 'facebook', 'tiktok', 'youtube', 'bilibili', 'douyin', 'kakaotalk', 'line', 'linkedin', 'pinterest', 'qq', 'reddit', 'rednote', 'snapchat', 'telegram', 'wechat', 'weibo', 'whatsapp', 'x' ) === ihq_portal_onboarding_platforms() );
check( 'seven languages', array( 'en', 'zh', 'yue', 'ja', 'ko', 'th', 'vi' ) === ihq_portal_onboarding_languages() );
check( 'default state', $empty_state === ihq_portal_onboarding_default_state() );
check( 'the record is the state without completion', $empty_record === ihq_portal_onboarding_record( $empty_state ) );

// --- Routes ---

check( 'routes are registered on rest_api_init', array( array( 'rest_api_init', 'ihq_portal_onboarding_register_progress_routes' ) ) === $GLOBALS['hooks'] );
ihq_portal_onboarding_register_progress_routes();
check( 'GET and POST /ihq/v1/onboarding/progress, both permission-checked', array(
	array(
		'ihq/v1',
		'/onboarding/progress',
		array(
			array( 'methods' => 'GET', 'callback' => 'ihq_portal_onboarding_handle_get', 'permission_callback' => 'ihq_portal_onboarding_permission_check' ),
			array( 'methods' => 'POST', 'callback' => 'ihq_portal_onboarding_handle_post', 'permission_callback' => 'ihq_portal_onboarding_permission_check' ),
		),
	),
) === $GLOBALS['routes'] );

// --- Permission: 401 logged out, 403 bad nonce ---

reset_world( 0 );
$denied = ihq_portal_onboarding_permission_check( post_request( array() ) );
check( 'logged-out request is a WP_Error', $denied instanceof WP_Error );
check( 'logged-out request is 401', array( 'status' => 401 ) === $denied->data );
check( 'logged-out error code', 'ihq_onboarding_logged_out' === $denied->code );

reset_world( 7 );
$bad_nonce = ihq_portal_onboarding_permission_check( post_request( array(), 'forged' ) );
check( 'a bad nonce is 403', $bad_nonce instanceof WP_Error && array( 'status' => 403 ) === $bad_nonce->data );
$no_nonce = ihq_portal_onboarding_permission_check( new WP_REST_Request( array(), null ) );
check( 'a missing nonce is 403', $no_nonce instanceof WP_Error && array( 'status' => 403 ) === $no_nonce->data );
check( 'logged in with the nonce is allowed', true === ihq_portal_onboarding_permission_check( post_request( array() ) ) );

// --- GET: empty default for a new user, stored state otherwise ---

reset_world( 7 );
$get = ihq_portal_onboarding_handle_get();
check( 'GET with nothing stored is 200', 200 === $get->status );
check( 'GET is never cached', array( 'Cache-Control' => 'no-store, private' ) === $get->headers );
check( 'GET with nothing stored encodes the empty default, answers and handles as objects', '{"stage":"","answers":{},"platforms":[],"handles":{},"language":"","drafts":{},"completed":false,"completed_at":""}' === json_encode( $get->data ) );
check( 'GET writes nothing', array() === $GLOBALS['meta_writes'] );

$GLOBALS['user_meta'][7]['_ihq_portal_onboarding'] = array( 'stage' => 'classic', 'answers' => array( 'baccarat_experience' => 'played_some' ), 'stray' => 'x' );
$GLOBALS['user_meta'][8]['_ihq_portal_onboarding'] = array( 'stage' => 'closing' );
check( 'GET returns the current user\'s state only, unknown stored keys dropped', '{"stage":"classic","answers":{"baccarat_experience":"played_some"},"platforms":[],"handles":{},"language":"","drafts":{},"completed":false,"completed_at":""}' === json_encode( ihq_portal_onboarding_handle_get()->data ) );

$GLOBALS['user_meta'][7]['_ihq_portal_onboarding'] = 'corrupt';
check( 'a non-array stored value reads as the default', $empty_state === ihq_portal_onboarding_get_state( 7 ) );

// --- POST: every field accepted with every allowed value ---

foreach ( ihq_portal_onboarding_stages() as $stage ) {
	reset_world( 7 );
	$res = ihq_portal_onboarding_handle_post( post_request( array( 'stage' => $stage ) ) );
	check( 'stage ' . $stage . ' is stored', 200 === $res->status && $stage === stored()['stage'] );
}
foreach ( ihq_portal_onboarding_answer_values() as $field => $values ) {
	foreach ( $values as $value ) {
		reset_world( 7 );
		$res = ihq_portal_onboarding_handle_post( post_request( array( 'answers' => array( $field => $value ) ) ) );
		check( $field . '=' . $value . ' is stored', 200 === $res->status && array( $field => $value ) === stored()['answers'] );
	}
}
foreach ( ihq_portal_onboarding_languages() as $language ) {
	reset_world( 7 );
	$res = ihq_portal_onboarding_handle_post( post_request( array( 'language' => $language ) ) );
	check( 'language ' . $language . ' is stored in the record', 200 === $res->status && $language === stored()['language'] );
	check( 'language ' . $language . ' is also saved as _ihq_aicoach_language', $language === $GLOBALS['user_meta'][7]['_ihq_aicoach_language'] );
}

reset_world( 7 );
$all_platforms = ihq_portal_onboarding_platforms();
$all_handles   = array();
foreach ( $all_platforms as $platform ) {
	$all_handles[ $platform ] = '@me_' . $platform;
}
$res = ihq_portal_onboarding_handle_post( post_request( array( 'platforms' => $all_platforms, 'handles' => $all_handles ) ) );
check( 'all 19 platforms with handles are stored', 200 === $res->status && $all_platforms === stored()['platforms'] && $all_handles === stored()['handles'] );

reset_world( 7 );
ihq_portal_onboarding_handle_post( post_request( array( 'platforms' => array( 'x' ), 'handles' => array( 'x' => "  @me\n  " ) ) ) );
check( 'a handle is sanitized before it is stored', array( 'x' => '@me' ) === stored()['handles'] );

reset_world( 7 );
ihq_portal_onboarding_handle_post( post_request( array( 'platforms' => array( 'x' ), 'handles' => array( 'x' => str_repeat( 'é', 190 ) ) ) ) );
check( 'a 190-character multi-byte handle is accepted', 190 === mb_strlen( stored()['handles']['x'] ) );

// --- POST: merge behaviour ---

reset_world( 7 );
ihq_portal_onboarding_handle_post( post_request( array( 'stage' => 'streaming_experience', 'answers' => array( 'baccarat_experience' => 'played_a_lot' ) ) ) );
ihq_portal_onboarding_handle_post( post_request( array( 'answers' => array( 'streaming_experience' => 'new_to_streaming', 'streaming_learning_intent' => 'new_ready_to_learn' ) ) ) );
check( 'answers merge per field; the stage is kept', array( 'stage' => 'streaming_experience', 'answers' => array( 'baccarat_experience' => 'played_a_lot', 'streaming_experience' => 'new_to_streaming', 'streaming_learning_intent' => 'new_ready_to_learn' ), 'platforms' => array(), 'handles' => array(), 'language' => '' ) === stored() );
ihq_portal_onboarding_handle_post( post_request( array( 'answers' => array( 'streaming_experience' => 'streamed_some', 'streaming_learning_intent' => null ) ) ) );
check( 'null clears an answer; a changed answer replaces the old one', array( 'baccarat_experience' => 'played_a_lot', 'streaming_experience' => 'streamed_some' ) === stored()['answers'] );

reset_world( 7 );
ihq_portal_onboarding_handle_post( post_request( array( 'platforms' => array( 'x', 'line' ), 'handles' => array( 'x' => '@a', 'line' => 'b' ) ) ) );
ihq_portal_onboarding_handle_post( post_request( array( 'platforms' => array( 'line' ) ) ) );
check( 'unticking a platform drops its handle', array( 'line' ) === stored()['platforms'] && array( 'line' => 'b' ) === stored()['handles'] );
ihq_portal_onboarding_handle_post( post_request( array( 'handles' => array( 'line' => 'c' ) ) ) );
check( 'handles alone are accepted for platforms already stored', array( 'line' ) === stored()['platforms'] && array( 'line' => 'c' ) === stored()['handles'] );
$GLOBALS['meta_writes']   = array();
$GLOBALS['actions_fired'] = array();
$res = ihq_portal_onboarding_handle_post( post_request( array( 'handles' => array( 'line' => 'd', 'x' => '@z' ) ) ) );
check( 'a handle for a platform that is not selected is a 400 naming it', 400 === $res->status && array( 'error' => 'Handle for a platform that is not selected: x.' ) === $res->data );
check( 'and nothing is stored or fired', array() === $GLOBALS['meta_writes'] && array() === $GLOBALS['actions_fired'] && array( 'line' => 'c' ) === stored()['handles'] );
$res = ihq_portal_onboarding_handle_post( post_request( array( 'platforms' => array( 'x' ), 'handles' => array( 'line' => 'd' ) ) ) );
check( 'a handle for a platform unticked in the same request is a 400', 400 === $res->status && array( 'error' => 'Handle for a platform that is not selected: line.' ) === $res->data && array( 'line' ) === stored()['platforms'] );

reset_world( 7 );
$res = ihq_portal_onboarding_handle_post( post_request( array( 'stage' => 'equity' ) ) );
check( 'POST answers with the full state as JSON', '{"stage":"equity","answers":{},"platforms":[],"handles":{},"language":"","drafts":{},"completed":false,"completed_at":""}' === json_encode( $res->data ) );
check( 'POST is never cached', array( 'Cache-Control' => 'no-store, private' ) === $res->headers );
check( 'POST writes the record once, without completion, and nothing else', array( array( 7, '_ihq_portal_onboarding', array( 'stage' => 'equity', 'answers' => array(), 'platforms' => array(), 'handles' => array(), 'language' => '' ) ) ) === $GLOBALS['meta_writes'] );
check( 'a stored change fires ihq_portal_onboarding_saved with the user, the partial and the state', array( array( 'ihq_portal_onboarding_saved', 7, array( 'stage' => 'equity' ), array( 'stage' => 'equity', 'answers' => array(), 'platforms' => array(), 'handles' => array(), 'language' => '', 'drafts' => array(), 'completed' => false, 'completed_at' => '' ) ) ) === $GLOBALS['actions_fired'] );

// --- POST: every rejected input returns 400 and stores nothing ---

$rejected = array(
	'not an object'                 => 'stage',
	'empty body'                    => array(),
	'null body'                     => null,
	'unknown top-level key'         => array( 'stage' => 'equity', 'admin' => true ),
	'completed is server-only'      => array( 'completed' => true ),
	'completed_at is server-only'   => array( 'completed_at' => '2026-01-01' ),
	'drafts are server-only'        => array( 'drafts' => array( 'x' => 'A post' ) ),
	'unknown stage'                 => array( 'stage' => 'payout' ),
	'stage not a string'            => array( 'stage' => 3 ),
	'answers not an object'         => array( 'answers' => 'played_a_lot' ),
	'answers empty'                 => array( 'answers' => array() ),
	'answers as a list'             => array( 'answers' => array( 'played_a_lot' ) ),
	'unknown answer field'          => array( 'answers' => array( 'favourite_colour' => 'red' ) ),
	'value of another field'        => array( 'answers' => array( 'baccarat_experience' => 'under_10k' ) ),
	'value in the wrong case'       => array( 'answers' => array( 'baccarat_experience' => 'PLAYED_A_LOT' ) ),
	'old follower option 5,000'     => array( 'answers' => array( 'active_follower_count' => '5k' ) ),
	'answer not a string'           => array( 'answers' => array( 'baccarat_experience' => 1 ) ),
	'one bad answer among good'     => array( 'answers' => array( 'baccarat_experience' => 'played_a_lot', 'equity_knowledge' => 'expert' ) ),
	'platforms not a list'          => array( 'platforms' => array( 'a' => 'x' ) ),
	'platforms a string'            => array( 'platforms' => 'x' ),
	'unknown platform'              => array( 'platforms' => array( 'myspace' ) ),
	'platform not on the 19 (kick)' => array( 'platforms' => array( 'kick' ) ),
	'duplicate platform'            => array( 'platforms' => array( 'x', 'x' ) ),
	'handles not an object'         => array( 'handles' => 'x' ),
	'handle for unknown platform'   => array( 'handles' => array( 'myspace' => '@a' ) ),
	'handle under a numeric key'    => array( 'handles' => array( '@a' ) ),
	'handle not a string'           => array( 'handles' => array( 'x' => array( '@a' ) ) ),
	'empty handle'                  => array( 'handles' => array( 'x' => '' ) ),
	'whitespace-only handle'        => array( 'handles' => array( 'x' => '   ' ) ),
	'handle too long'               => array( 'handles' => array( 'x' => str_repeat( 'a', 191 ) ) ),
	'handle for unselected platform' => array( 'handles' => array( 'x' => '@a' ) ),
	'unknown language'              => array( 'language' => 'fr' ),
	'language in upper case'        => array( 'language' => 'EN' ),
	'bad value next to good ones'   => array( 'stage' => 'equity', 'language' => 'xx' ),
);
foreach ( $rejected as $label => $body ) {
	reset_world( 7 );
	$GLOBALS['user_meta'][7]['_ihq_portal_onboarding'] = array( 'stage' => 'classic' );
	$res = ihq_portal_onboarding_handle_post( post_request( $body ) );
	check( 'rejected (' . $label . ') with 400', 400 === $res->status );
	check( 'rejected (' . $label . ') carries an error message', is_array( $res->data ) && isset( $res->data['error'] ) && '' !== $res->data['error'] );
	check( 'rejected (' . $label . ') stores nothing', array() === $GLOBALS['meta_writes'] && array( 'stage' => 'classic' ) === stored() );
	check( 'rejected (' . $label . ') fires nothing', array() === $GLOBALS['actions_fired'] );
}

$validated = ihq_portal_onboarding_validate_partial( array( 'answers' => array( 'favourite_colour' => 'red' ) ) );
check( 'validate_partial names the bad field', array( 'partial' => array(), 'error' => 'Unknown answer: favourite_colour.' ) === $validated );
check( 'validate_partial names unknown keys', array( 'partial' => array(), 'error' => 'Unknown field: admin.' ) === ihq_portal_onboarding_validate_partial( array( 'admin' => 1 ) ) );

// --- PHP API: merge() applies the same allowlist as the POST route ---

reset_world( 7 );
$GLOBALS['user_meta'][7]['_ihq_portal_onboarding'] = array( 'stage' => 'classic' );
$merge_rejected = array(
	'a non-enum answer'                 => array( array( 'answers' => array( 'baccarat_experience' => 'Played a lot' ) ), 'Invalid value for baccarat_experience.' ),
	'answers not an array'              => array( array( 'answers' => 'played_a_lot' ), 'Invalid answers.' ),
	'a partial that is not an array'    => array( 'stage', 'Nothing to save.' ),
	'an empty partial'                  => array( array(), 'Nothing to save.' ),
	'completion is not writable'        => array( array( 'completed' => true ), 'Unknown field: completed.' ),
	'a handle for an unselected platform' => array( array( 'handles' => array( 'x' => '@a' ) ), 'Handle for a platform that is not selected: x.' ),
);
foreach ( $merge_rejected as $label => $case ) {
	$error = ihq_portal_onboarding_merge( 7, $case[0] );
	check( 'merge() rejects ' . $label . ' with a 400 WP_Error', $error instanceof WP_Error && 'ihq_onboarding_invalid' === $error->code && $case[1] === $error->message && array( 'status' => 400 ) === $error->data );
	check( 'merge() stores and fires nothing for ' . $label, array() === $GLOBALS['meta_writes'] && array() === $GLOBALS['actions_fired'] && array( 'stage' => 'classic' ) === stored() );
}
$merged = ihq_portal_onboarding_merge( '7', array( 'platforms' => array( 'x' ), 'handles' => array( 'x' => " @me\n" ) ) );
check( 'merge() sanitizes like the route and returns the full state', array( 'stage' => 'classic', 'answers' => array(), 'platforms' => array( 'x' ), 'handles' => array( 'x' => '@me' ), 'language' => '', 'drafts' => array(), 'completed' => false, 'completed_at' => '' ) === $merged );
check( 'merge() fires the saved action with the sanitized partial', array( array( 'ihq_portal_onboarding_saved', 7, array( 'platforms' => array( 'x' ), 'handles' => array( 'x' => '@me' ) ), $merged ) ) === $GLOBALS['actions_fired'] );

// --- Completion API ---

reset_world( 7 );
check( 'is_complete is false for a new user', false === ihq_portal_onboarding_is_complete( 7 ) );
$done = ihq_portal_onboarding_mark_complete( 7 );
check( 'mark_complete sets the flag in the returned state', true === $done['completed'] && completed_meta() === $done['completed_at'] );
check( 'mark_complete stamps an ISO-8601 UTC time', 1 === preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/', completed_meta() ) );
check( 'mark_complete writes only the completion key', array( array( 7, '_ihq_portal_onboarding_completed_at', $done['completed_at'] ) ) === $GLOBALS['meta_writes'] );
check( 'is_complete is true afterwards', true === ihq_portal_onboarding_is_complete( 7 ) );
check( 'get_state reads completion from its key', array_merge( $empty_state, array( 'completed' => true, 'completed_at' => $done['completed_at'] ) ) === ihq_portal_onboarding_get_state( 7 ) );
check( 'completion fires ihq_portal_onboarding_completed once', array( array( 'ihq_portal_onboarding_completed', 7, $done ) ) === $GLOBALS['actions_fired'] );
$stamp = completed_meta();
$GLOBALS['meta_writes'] = array();
ihq_portal_onboarding_mark_complete( 7 );
check( 'completing again writes nothing, keeps the first time and fires nothing more', array() === $GLOBALS['meta_writes'] && $stamp === completed_meta() && 1 === count( $GLOBALS['actions_fired'] ) );
check( 'a completed flag left in the record is ignored', ( function () {
	$GLOBALS['user_meta'][9]['_ihq_portal_onboarding'] = array( 'completed' => true, 'completed_at' => '2026-01-01T00:00:00+00:00' );
	return false === ihq_portal_onboarding_is_complete( 9 ) && $GLOBALS['empty_state'] === ihq_portal_onboarding_get_state( 9 );
} )() );
check( 'a non-string completion value is not completion', ( function () {
	$GLOBALS['user_meta'][10]['_ihq_portal_onboarding_completed_at'] = array( 'x' );
	return false === ihq_portal_onboarding_is_complete( 10 );
} )() );

// A later answer save keeps completion and does not touch its key.
$GLOBALS['meta_writes'] = array();
$after = ihq_portal_onboarding_merge( 7, array( 'language' => 'ja' ) );
check( 'saving after completion keeps the completed flag', true === $after['completed'] && $stamp === $after['completed_at'] && true === ihq_portal_onboarding_is_complete( 7 ) );
check( 'saving after completion writes the record and the language only', array( array( 7, '_ihq_portal_onboarding', array_merge( $empty_record, array( 'language' => 'ja' ) ) ), array( 7, '_ihq_aicoach_language', 'ja' ) ) === $GLOBALS['meta_writes'] );

// A save that read the record before completion and writes after it cannot undo it.
reset_world( 7 );
$GLOBALS['on_meta_write'] = function ( $user_id, $key ) {
	ihq_portal_onboarding_mark_complete( $user_id );
};
$stale = ihq_portal_onboarding_merge( 7, array( 'stage' => 'closing' ) );
check( 'the interleaved save reports the state it read (not yet complete)', false === $stale['completed'] && 'closing' === $stale['stage'] );
check( 'the interleaved save does not undo the completion', true === ihq_portal_onboarding_is_complete( 7 ) && 'closing' === ihq_portal_onboarding_get_state( 7 )['stage'] );
check( 'completion fired once, then the save', array( 'ihq_portal_onboarding_completed', 'ihq_portal_onboarding_saved' ) === array_column( $GLOBALS['actions_fired'], 0 ) );
$GLOBALS['actions_fired'] = array();
ihq_portal_onboarding_mark_complete( 7 );
check( 'a later completion does not fire again', array() === $GLOBALS['actions_fired'] );

// --- Account scope: the record is user meta, so it is the same for the user anywhere ---

reset_world( 7 );
ihq_portal_onboarding_handle_post( post_request( array( 'stage' => 'world_tour' ) ) );
$GLOBALS['current_user'] = 0; // logout
$GLOBALS['current_user'] = 7; // login again, any browser: no cookie is involved
check( 'state survives logout/login for the same account', 'world_tour' === ihq_portal_onboarding_handle_get()->data['stage'] );
$GLOBALS['current_user'] = 8;
check( 'another account does not see it', '' === ihq_portal_onboarding_handle_get()->data['stage'] );

// --- Drafts (ENGR-7570): server-written, in their own meta key ---

check( 'drafts meta key', '_ihq_portal_onboarding_drafts' === IHQ_PORTAL_ONBOARDING_DRAFTS_META_KEY );
check( 'drafts field name', 'drafts' === IHQ_PORTAL_ONBOARDING_DRAFTS_FIELD );
check( 'a draft is at most 5000 characters', 5000 === IHQ_PORTAL_ONBOARDING_DRAFT_MAX_LENGTH );
check( 'the record leaves the drafts out', array( 'stage' => 'x' ) === ihq_portal_onboarding_record( array( 'stage' => 'x', 'drafts' => array( 'x' => 'A' ), 'completed' => true ) ) );

reset_world( 7 );
ihq_portal_onboarding_merge( 7, array( 'stage' => 'draft_posts', 'platforms' => array( 'instagram', 'x' ) ) );
$GLOBALS['meta_writes']   = array();
$GLOBALS['actions_fired'] = array();
$drafts_state = ihq_portal_onboarding_store_drafts( 7, array( 'x' => "Line one\nLine <two> 🎉" ) );
check( 'store_drafts keeps the text as written and returns the full state', array_merge( $empty_state, array( 'stage' => 'draft_posts', 'platforms' => array( 'instagram', 'x' ), 'drafts' => array( 'x' => "Line one\nLine <two> 🎉" ) ) ) === $drafts_state );
check( 'store_drafts writes only the drafts key', array( array( 7, '_ihq_portal_onboarding_drafts', array( 'x' => "Line one\nLine <two> 🎉" ) ) ) === $GLOBALS['meta_writes'] );
check( 'store_drafts fires the saved action with the drafts as the partial', array( array( 'ihq_portal_onboarding_saved', 7, array( 'drafts' => array( 'x' => "Line one\nLine <two> 🎉" ) ), $drafts_state ) ) === $GLOBALS['actions_fired'] );
check( 'get_state returns the stored drafts', array( 'x' => "Line one\nLine <two> 🎉" ) === ihq_portal_onboarding_get_state( 7 )['drafts'] );

ihq_portal_onboarding_store_drafts( 7, array( 'instagram' => 'Insta post' ) );
check( 'a second store adds a platform and keeps the others, in the order of the platforms', array( 'instagram' => 'Insta post', 'x' => "Line one\nLine <two> 🎉" ) === ihq_portal_onboarding_get_state( 7 )['drafts'] );
ihq_portal_onboarding_store_drafts( 7, array( 'x' => 'Rewritten' ) );
check( 'storing a platform again replaces only that draft', array( 'instagram' => 'Insta post', 'x' => 'Rewritten' ) === ihq_portal_onboarding_get_state( 7 )['drafts'] );

ihq_portal_onboarding_merge( 7, array( 'platforms' => array( 'instagram' ) ) );
check( 'unticking a platform hides its draft from the state', array( 'instagram' => 'Insta post' ) === ihq_portal_onboarding_get_state( 7 )['drafts'] );
check( 'but keeps it stored', array( 'x' => 'Rewritten', 'instagram' => 'Insta post' ) === $GLOBALS['user_meta'][7]['_ihq_portal_onboarding_drafts'] );
ihq_portal_onboarding_merge( 7, array( 'platforms' => array( 'instagram', 'x' ) ) );
check( 'ticking it again brings the draft back', array( 'instagram' => 'Insta post', 'x' => 'Rewritten' ) === ihq_portal_onboarding_get_state( 7 )['drafts'] );
check( 'a progress save leaves the drafts key alone', 'Rewritten' === $GLOBALS['user_meta'][7]['_ihq_portal_onboarding_drafts']['x'] && ! isset( stored()['drafts'] ) );
check( 'the public state has the drafts as an object', '{"instagram":"Insta post","x":"Rewritten"}' === json_encode( ihq_portal_onboarding_public_state( ihq_portal_onboarding_get_state( 7 ) )['drafts'] ) );

$GLOBALS['user_meta'][7]['_ihq_portal_onboarding_drafts'] = array( 'x' => 'Kept', 'myspace' => 'Dropped', 'instagram' => array( 'no' ), 3 => 'numeric' );
check( 'stored drafts are cleaned on read: unknown platforms and non-strings dropped', array( 'x' => 'Kept' ) === ihq_portal_onboarding_stored_drafts( 7 ) );
$GLOBALS['user_meta'][7]['_ihq_portal_onboarding_drafts'] = 'corrupt';
check( 'a non-array drafts meta reads as none', array() === ihq_portal_onboarding_get_state( 7 )['drafts'] );

check( 'a 5000-character multi-byte draft is accepted', '' === ihq_portal_onboarding_validate_drafts( array( 'x' => str_repeat( 'あ', 5000 ) ) )['error'] );
$drafts_rejected = array(
	'not an array'               => array( 'x', 'Invalid drafts.' ),
	'empty'                      => array( array(), 'Invalid drafts.' ),
	'unknown platform'           => array( array( 'myspace' => 'A' ), 'Unknown platform.' ),
	'a numeric key'              => array( array( 'A post' ), 'Unknown platform.' ),
	'text not a string'          => array( array( 'x' => 5 ), 'Invalid draft for x.' ),
	'empty text'                 => array( array( 'x' => '' ), 'Invalid draft for x.' ),
	'whitespace-only text'       => array( array( 'x' => " \n " ), 'Invalid draft for x.' ),
	'5001 multi-byte characters' => array( array( 'x' => str_repeat( 'あ', 5001 ) ), 'Invalid draft for x.' ),
	'one bad among good'         => array( array( 'instagram' => 'A', 'x' => '' ), 'Invalid draft for x.' ),
);
foreach ( $drafts_rejected as $label => $case ) {
	reset_world( 7 );
	ihq_portal_onboarding_merge( 7, array( 'platforms' => array( 'instagram', 'x' ) ) );
	$GLOBALS['meta_writes']   = array();
	$GLOBALS['actions_fired'] = array();
	$error = ihq_portal_onboarding_store_drafts( 7, $case[0] );
	check( 'store_drafts rejects ' . $label . ' with a 400 WP_Error', $error instanceof WP_Error && 'ihq_onboarding_invalid' === $error->code && $case[1] === $error->message && array( 'status' => 400 ) === $error->data );
	check( 'store_drafts stores and fires nothing for ' . $label, array() === $GLOBALS['meta_writes'] && array() === $GLOBALS['actions_fired'] );
}
reset_world( 7 );
ihq_portal_onboarding_merge( 7, array( 'platforms' => array( 'instagram' ) ) );
$GLOBALS['meta_writes'] = array();
$error = ihq_portal_onboarding_store_drafts( 7, array( 'instagram' => 'A', 'x' => 'B' ) );
check( 'a draft for a platform that is not selected is refused, naming it', $error instanceof WP_Error && 'Draft for a platform that is not selected: x.' === $error->message && array() === $GLOBALS['meta_writes'] );

// --- The module is loaded by functions.php ---

$functions = file_get_contents( __DIR__ . '/../functions.php' );
check( 'functions.php loads every inc/portal-onboarding-*.php module', false !== strpos( $functions, "glob( get_template_directory() . '/inc/portal-onboarding-*.php' )" ) );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
