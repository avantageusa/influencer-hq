<?php
/**
 * Unit test for the onboarding Braze sync (ENGR-7573, inc/portal-onboarding-braze.php),
 * driven through the real account store (inc/portal-onboarding-progress.php) so the
 * actions fire as they do in production. Braze is mocked: wp_remote_post() records the
 * request and returns whatever the test sets. error_log() goes to a temp file the test reads.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/portal-onboarding-braze.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

$GLOBALS['user_meta'] = array();
$GLOBALS['actions']   = array();
$GLOBALS['requests']  = array();
$GLOBALS['events']    = array();
$GLOBALS['response']  = array( 'code' => 201, 'body' => '{"message":"success"}' );
$GLOBALS['log_file']  = tempnam( sys_get_temp_dir(), 'ihq-braze-log' );
ini_set( 'log_errors', '1' );
ini_set( 'error_log', $GLOBALS['log_file'] );
register_shutdown_function( function () { unlink( $GLOBALS['log_file'] ); } );

class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
function add_action( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['actions'][ $hook ][] = array( $cb, $priority, $args ); }
function do_action( $hook, ...$args ) {
	foreach ( $GLOBALS['actions'][ $hook ] ?? array() as $entry ) {
		call_user_func_array( $entry[0], array_slice( $args, 0, $entry[2] ) );
	}
}
function get_user_meta( $user_id, $key, $single = false ) { return $GLOBALS['user_meta'][ $user_id ][ $key ] ?? ''; }
function update_user_meta( $user_id, $key, $value ) { $GLOBALS['user_meta'][ $user_id ][ $key ] = $value; return true; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function wp_json_encode( $data ) { return json_encode( $data ); }
function ihq_braze_rest_endpoint() { return 'https://rest.braze.test'; }
function ihq_braze_track_api_key() { return 'track-key'; }
function wp_remote_post( $url, $args ) {
	$GLOBALS['requests'][] = array( 'url' => $url, 'args' => $args );
	$GLOBALS['events'][]   = 'send';
	return $GLOBALS['response'];
}
function wp_remote_retrieve_response_code( $response ) { return $response['code']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }

require __DIR__ . '/../inc/portal-onboarding-progress.php';
require __DIR__ . '/../inc/portal-onboarding-braze.php';

function reset_world() {
	$GLOBALS['user_meta'] = array( 7 => array( 'wp_influencer_guid' => 'wpinfluencer_abc' ) );
	$GLOBALS['requests']  = array();
	$GLOBALS['events']    = array();
	$GLOBALS['response']  = array( 'code' => 201, 'body' => '{"message":"success"}' );
	$queue                = &ihq_portal_onboarding_braze_queue();
	$queue                = array();
	file_put_contents( $GLOBALS['log_file'], '' );
}
// One request: store the update, then run WordPress's shutdown, where the queue is sent.
function save( $partial, $user_id = 7 ) {
	$result = ihq_portal_onboarding_validate_partial( $partial );
	if ( '' !== $result['error'] ) {
		throw new Exception( $result['error'] );
	}
	$state = ihq_portal_onboarding_merge( $user_id, $result['partial'] );
	do_action( 'shutdown' );
	return $state;
}
// The lines error_log() wrote, without the timestamp PHP puts in front of each.
function logged() {
	$lines = array_filter( explode( "\n", (string) file_get_contents( $GLOBALS['log_file'] ) ) );
	return array_values( preg_replace( '/^\[[^\]]+\] /', '', $lines ) );
}
function sent( $index = 0 ) {
	if ( ! isset( $GLOBALS['requests'][ $index ] ) ) {
		return null;
	}
	return json_decode( $GLOBALS['requests'][ $index ]['args']['body'], true );
}
function null_handles_except( array $set ) {
	$out = array();
	foreach ( ihq_portal_onboarding_platforms() as $platform ) {
		$out[ 'social_handle_' . $platform ] = $set[ $platform ] ?? null;
	}
	return $out;
}

// --- Hooks ---

check( 'listens to saves with all three arguments, at priority 20', array( array( 'ihq_portal_onboarding_braze_on_saved', 20, 3 ) ) === $GLOBALS['actions'][ IHQ_PORTAL_ONBOARDING_SAVED_ACTION ] );
check( 'listens to completion', array( array( 'ihq_portal_onboarding_braze_on_completed', 20, 1 ) ) === $GLOBALS['actions'][ IHQ_PORTAL_ONBOARDING_COMPLETED_ACTION ] );
check( 'sends the queue last on shutdown', array( array( 'ihq_portal_onboarding_braze_on_shutdown', PHP_INT_MAX, 0 ) ) === $GLOBALS['actions']['shutdown'] );
check( 'the save action is the one the store fires', 'ihq_portal_onboarding_saved' === IHQ_PORTAL_ONBOARDING_SAVED_ACTION );
check( 'the completion action is the one the store fires', 'ihq_portal_onboarding_completed' === IHQ_PORTAL_ONBOARDING_COMPLETED_ACTION );

// --- The send waits for shutdown ---

reset_world();
ihq_portal_onboarding_merge( 7, array( 'answers' => array( 'baccarat_experience' => 'played_some' ) ) );
check( 'a save sends nothing while the request is still running', array() === $GLOBALS['requests'] );
check( 'it queues the attributes for the user', array( array( 'user_id' => 7, 'attributes' => array( 'baccarat_experience' => 'played_some' ) ) ) === ihq_portal_onboarding_braze_queue() );
ihq_portal_onboarding_merge( 7, array( 'language' => 'ko' ) );
do_action( 'shutdown' );
check( 'shutdown sends every queued update, in order', array( array( 'attributes' => array( array( 'external_id' => 'wpinfluencer_abc', 'baccarat_experience' => 'played_some' ) ) ), array( 'attributes' => array( array( 'external_id' => 'wpinfluencer_abc', 'language_preference' => 'ko' ) ) ) ) === array( sent( 0 ), sent( 1 ) ) );
check( 'and empties the queue', array() === ihq_portal_onboarding_braze_queue() );
check( 'with no fastcgi_finish_request() (CLI, mod_php) it sends without ending the response first', array( 'send', 'send' ) === $GLOBALS['events'] );
$GLOBALS['requests'] = array();
do_action( 'shutdown' );
check( 'a second shutdown sends nothing again', array() === $GLOBALS['requests'] );

// --- Every answer sends its attribute with its enum value ---

foreach ( ihq_portal_onboarding_answer_values() as $field => $values ) {
	foreach ( $values as $value ) {
		reset_world();
		save( array( 'answers' => array( $field => $value ) ) );
		check( $field . '=' . $value . ' sends exactly that attribute for the user', array( 'attributes' => array( array( 'external_id' => 'wpinfluencer_abc', $field => $value ) ) ) === sent() );
	}
}

reset_world();
save( array( 'answers' => array( 'baccarat_experience' => 'played_a_lot' ) ) );
$request = $GLOBALS['requests'][0];
check( 'the request goes to /users/track', 'https://rest.braze.test/users/track' === $request['url'] );
check( 'with the track key and JSON', array( 'Content-Type' => 'application/json', 'Authorization' => 'Bearer track-key' ) === $request['args']['headers'] );
check( 'with a short timeout so a slow Braze cannot hold the save', 5 === $request['args']['timeout'] );
check( 'one request per save', 1 === count( $GLOBALS['requests'] ) );

reset_world();
save( array( 'answers' => array( 'streaming_experience' => 'streamed_some', 'streaming_learning_intent' => null ) ) );
check( 'a cleared sub-answer is sent as null (removes it in Braze)', array( 'attributes' => array( array( 'external_id' => 'wpinfluencer_abc', 'streaming_experience' => 'streamed_some', 'streaming_learning_intent' => null ) ) ) === sent() );

// --- Platforms and handles ---

reset_world();
save( array( 'platforms' => array( 'x', 'youtube' ), 'handles' => array( 'x' => '@me', 'youtube' => 'youtube.com/@me' ) ) );
check( 'platforms send social_platforms and one social_handle_<platform> each, null where none', array( 'attributes' => array( array_merge( array( 'external_id' => 'wpinfluencer_abc', 'social_platforms' => array( 'x', 'youtube' ) ), null_handles_except( array( 'x' => '@me', 'youtube' => 'youtube.com/@me' ) ) ) ) ) === sent() );
check( 'there is a handle attribute for each of the 19 platforms', 19 === count( preg_grep( '/^social_handle_/', array_keys( sent()['attributes'][0] ) ) ) );

$GLOBALS['requests'] = array();
save( array( 'platforms' => array( 'youtube' ) ) );
check( 'unticking a platform sends its handle as null and keeps the others', array( 'attributes' => array( array_merge( array( 'external_id' => 'wpinfluencer_abc', 'social_platforms' => array( 'youtube' ) ), null_handles_except( array( 'youtube' => 'youtube.com/@me' ) ) ) ) ) === sent() );

$GLOBALS['requests'] = array();
save( array( 'platforms' => array() ) );
check( 'no platforms sends an empty array and every handle as null', array( 'attributes' => array( array_merge( array( 'external_id' => 'wpinfluencer_abc', 'social_platforms' => array() ), null_handles_except( array() ) ) ) ) === sent() );

// --- Language ---

reset_world();
save( array( 'language' => 'ja' ) );
check( 'a language sends language_preference', array( 'attributes' => array( array( 'external_id' => 'wpinfluencer_abc', 'language_preference' => 'ja' ) ) ) === sent() );

// --- Stage-only saves send nothing ---

reset_world();
save( array( 'stage' => 'equity' ) );
check( 'a stage-only save sends nothing', array() === $GLOBALS['requests'] );

reset_world();
save( array( 'stage' => 'equity', 'answers' => array( 'active_follower_count' => 'under_10k' ) ) );
check( 'a stage with an answer sends only the answer', array( 'attributes' => array( array( 'external_id' => 'wpinfluencer_abc', 'active_follower_count' => 'under_10k' ) ) ) === sent() );

// --- Completion ---

$completed_payload = array( 'attributes' => array( array( 'external_id' => 'wpinfluencer_abc', 'onboarding_completed' => true ) ) );

reset_world();
ihq_portal_onboarding_mark_complete( 7 );
do_action( 'shutdown' );
check( 'completion sends onboarding_completed', $completed_payload === sent() );
$GLOBALS['requests'] = array();
ihq_portal_onboarding_mark_complete( 7 );
do_action( 'shutdown' );
check( 'completing again sends nothing more', array() === $GLOBALS['requests'] );

// Two concurrent completions can both fire the action (user meta has no compare-and-set).
// The sync is idempotent: both send the same attribute value, so Braze ends in the same state.
reset_world();
do_action( IHQ_PORTAL_ONBOARDING_COMPLETED_ACTION, 7, array( 'completed_at' => '2026-10-15T10:00:00+00:00' ) );
do_action( IHQ_PORTAL_ONBOARDING_COMPLETED_ACTION, 7, array( 'completed_at' => '2026-10-15T10:00:01+00:00' ) );
do_action( 'shutdown' );
check( 'a duplicate completion sends the identical payload, nothing time- or count-based', array( $completed_payload, $completed_payload ) === array( sent( 0 ), sent( 1 ) ) );

// --- Failure never blocks the onboarding ---

reset_world();
$GLOBALS['response'] = new WP_Error( 'http_request_failed', 'timed out' );
$state = save( array( 'answers' => array( 'equity_knowledge' => 'owns_equity' ) ) );
check( 'a Braze network error still stores the answer', 'owns_equity' === $GLOBALS['user_meta'][7]['_ihq_portal_onboarding']['answers']['equity_knowledge'] );
check( 'and the save returns normally', 'owns_equity' === $state['answers']['equity_knowledge'] );
check( 'and the network error is logged', array( 'IHQ Braze onboarding: API error for user 7: timed out' ) === logged() );
file_put_contents( $GLOBALS['log_file'], '' );
check( 'send reports the network failure', false === ihq_portal_onboarding_braze_send( 7, array( 'x' => 1 ) ) );

reset_world();
$GLOBALS['response'] = array( 'code' => 500, 'body' => 'oops' );
save( array( 'answers' => array( 'equity_management' => 'owns_self_managed' ) ) );
check( 'a Braze error status still stores the answer', 'owns_self_managed' === $GLOBALS['user_meta'][7]['_ihq_portal_onboarding']['answers']['equity_management'] );
check( 'and the 500 is logged with its code only, never the response body', array( 'IHQ Braze onboarding: response 500 for user 7' ) === logged() );
file_put_contents( $GLOBALS['log_file'], '' );
check( 'send reports a 500', false === ihq_portal_onboarding_braze_send( 7, array( 'x' => 1 ) ) );
file_put_contents( $GLOBALS['log_file'], '' );
$GLOBALS['response'] = array( 'code' => 400, 'body' => 'bad social_handle_x=@me' );
check( 'send reports a 400', false === ihq_portal_onboarding_braze_send( 7, array( 'x' => 1 ) ) );
check( 'and logs it with its code only, never the response body', array( 'IHQ Braze onboarding: response 400 for user 7' ) === logged() );
file_put_contents( $GLOBALS['log_file'], '' );
$GLOBALS['response'] = array( 'code' => 199, 'body' => '' );
check( 'send reports a status below 200 as a failure', false === ihq_portal_onboarding_braze_send( 7, array( 'x' => 1 ) ) );
$GLOBALS['response'] = array( 'code' => 300, 'body' => '' );
check( 'send reports a 300 as a failure', false === ihq_portal_onboarding_braze_send( 7, array( 'x' => 1 ) ) );
file_put_contents( $GLOBALS['log_file'], '' );
$GLOBALS['response'] = array( 'code' => 200, 'body' => 'ok' );
check( 'send reports success on 200', true === ihq_portal_onboarding_braze_send( 7, array( 'x' => 1 ) ) );
$GLOBALS['response'] = array( 'code' => 299, 'body' => 'ok' );
check( 'send reports success on 299', true === ihq_portal_onboarding_braze_send( 7, array( 'x' => 1 ) ) );
check( 'a 2xx logs nothing', array() === logged() );

reset_world();
unset( $GLOBALS['user_meta'][7]['wp_influencer_guid'] );
save( array( 'answers' => array( 'baccarat_experience' => 'new_player' ) ) );
check( 'a user without a Braze external_id is skipped, not sent under a made-up id', array() === $GLOBALS['requests'] );
check( 'and the skip is logged', array( 'IHQ Braze onboarding: skipped, no external_id for user 7' ) === logged() );
check( 'and the answer is still stored', 'new_player' === $GLOBALS['user_meta'][7]['_ihq_portal_onboarding']['answers']['baccarat_experience'] );

reset_world();
check( 'send with nothing to send reports false', false === ihq_portal_onboarding_braze_send( 7, array() ) );
check( 'and sends nothing', array() === $GLOBALS['requests'] );
ihq_portal_onboarding_braze_enqueue( 7, array() );
check( 'nothing to send queues nothing', array() === ihq_portal_onboarding_braze_queue() );
ihq_portal_onboarding_braze_on_saved( 7, 'bad', array() );
check( 'a malformed call queues nothing', array() === ihq_portal_onboarding_braze_queue() );
do_action( 'shutdown' );
check( 'a request that queued nothing does not end the response early', array() === $GLOBALS['events'] );

// --- PHP-FPM: the response is ended before Braze is called ---

check( 'fastcgi_finish_request() is absent in this CLI run, so the branch below is new', false === function_exists( 'fastcgi_finish_request' ) );
if ( ! function_exists( 'fastcgi_finish_request' ) ) {
	function fastcgi_finish_request() { $GLOBALS['events'][] = 'finish'; return true; }
}
reset_world();
save( array( 'answers' => array( 'post_action_choice' => 'post_now' ) ) );
check( 'with fastcgi_finish_request() the response is ended first, then Braze is called', array( 'finish', 'send' ) === $GLOBALS['events'] );
check( 'and the payload is unchanged', array( 'attributes' => array( array( 'external_id' => 'wpinfluencer_abc', 'post_action_choice' => 'post_now' ) ) ) === sent() );
reset_world();
do_action( 'shutdown' );
check( 'an empty queue never ends the response early', array() === $GLOBALS['events'] );

// --- The pure mapping ---

check( 'attributes: empty for a stage-only partial', array() === ihq_portal_onboarding_braze_attributes( array( 'stage' => 'classic' ), ihq_portal_onboarding_default_state() ) );
check( 'attributes: handles alone also send the platform picture', array_merge( array( 'social_platforms' => array( 'qq' ) ), null_handles_except( array( 'qq' => '1' ) ) ) === ihq_portal_onboarding_braze_attributes( array( 'handles' => array( 'qq' => '1' ) ), array( 'platforms' => array( 'qq' ), 'handles' => array( 'qq' => '1' ) ) ) );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
