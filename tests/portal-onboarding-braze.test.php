<?php
/**
 * Unit test for the onboarding Braze sync (ENGR-7573, inc/portal-onboarding-braze.php),
 * driven through the real account store (inc/portal-onboarding-progress.php) so the
 * actions fire as they do in production. Braze is mocked: wp_remote_post() records the
 * request and returns whatever the test sets.
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
$GLOBALS['response']  = array( 'code' => 201, 'body' => '{"message":"success"}' );

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
	return $GLOBALS['response'];
}
function wp_remote_retrieve_response_code( $response ) { return $response['code']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }

// error_log() is a PHP built-in and cannot be stubbed; its stderr output is harmless here.
// The failure checks look at the return value and at the request instead.

require __DIR__ . '/../inc/portal-onboarding-progress.php';
require __DIR__ . '/../inc/portal-onboarding-braze.php';

function reset_world() {
	$GLOBALS['user_meta'] = array( 7 => array( 'wp_influencer_guid' => 'wpinfluencer_abc' ) );
	$GLOBALS['requests']  = array();
	$GLOBALS['response']  = array( 'code' => 201, 'body' => '{"message":"success"}' );
}
function save( $partial, $user_id = 7 ) {
	$result = ihq_portal_onboarding_validate_partial( $partial );
	if ( '' !== $result['error'] ) {
		throw new Exception( $result['error'] );
	}
	return ihq_portal_onboarding_merge( $user_id, $result['partial'] );
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

check( 'listens to saves with all three arguments, after the store', array( array( 'ihq_portal_onboarding_braze_on_saved', 20, 3 ) ) === $GLOBALS['actions']['ihq_portal_onboarding_saved'] );
check( 'listens to completion', array( array( 'ihq_portal_onboarding_braze_on_completed', 20, 1 ) ) === $GLOBALS['actions']['ihq_portal_onboarding_completed'] );

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
check( 'unticking a platform sends its handle as null', null === sent()['attributes'][0]['social_handle_x'] && array( 'youtube' ) === sent()['attributes'][0]['social_platforms'] && 'youtube.com/@me' === sent()['attributes'][0]['social_handle_youtube'] );

$GLOBALS['requests'] = array();
save( array( 'platforms' => array() ) );
check( 'no platforms sends an empty array', array() === sent()['attributes'][0]['social_platforms'] );

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

reset_world();
ihq_portal_onboarding_mark_complete( 7 );
check( 'completion sends onboarding_completed', array( 'attributes' => array( array( 'external_id' => 'wpinfluencer_abc', 'onboarding_completed' => true ) ) ) === sent() );
$GLOBALS['requests'] = array();
ihq_portal_onboarding_mark_complete( 7 );
check( 'completing again sends nothing more', array() === $GLOBALS['requests'] );

// --- Failure never blocks the onboarding ---

reset_world();
$GLOBALS['response'] = new WP_Error( 'http_request_failed', 'timed out' );
$state = save( array( 'answers' => array( 'equity_knowledge' => 'owns_equity' ) ) );
check( 'a Braze network error still stores the answer', 'owns_equity' === $GLOBALS['user_meta'][7]['_ihq_portal_onboarding']['answers']['equity_knowledge'] );
check( 'and the save returns normally', 'owns_equity' === $state['answers']['equity_knowledge'] );
check( 'send reports the network failure', false === ihq_portal_onboarding_braze_send( 7, array( 'x' => 1 ) ) );

reset_world();
$GLOBALS['response'] = array( 'code' => 500, 'body' => 'oops' );
save( array( 'answers' => array( 'equity_management' => 'owns_self_managed' ) ) );
check( 'a Braze error status still stores the answer', 'owns_self_managed' === $GLOBALS['user_meta'][7]['_ihq_portal_onboarding']['answers']['equity_management'] );
check( 'send reports a 500', false === ihq_portal_onboarding_braze_send( 7, array( 'x' => 1 ) ) );
$GLOBALS['response'] = array( 'code' => 400, 'body' => 'bad' );
check( 'send reports a 400', false === ihq_portal_onboarding_braze_send( 7, array( 'x' => 1 ) ) );
$GLOBALS['response'] = array( 'code' => 201, 'body' => 'ok' );
check( 'send reports success on 2xx', true === ihq_portal_onboarding_braze_send( 7, array( 'x' => 1 ) ) );

reset_world();
unset( $GLOBALS['user_meta'][7]['wp_influencer_guid'] );
save( array( 'answers' => array( 'baccarat_experience' => 'new_player' ) ) );
check( 'a user without a Braze external_id is skipped, not sent under a made-up id', array() === $GLOBALS['requests'] );
check( 'and the answer is still stored', 'new_player' === $GLOBALS['user_meta'][7]['_ihq_portal_onboarding']['answers']['baccarat_experience'] );

reset_world();
check( 'nothing to send sends nothing', false === ihq_portal_onboarding_braze_send( 7, array() ) && array() === $GLOBALS['requests'] );
ihq_portal_onboarding_braze_on_saved( 7, 'bad', array() );
check( 'a malformed call sends nothing', array() === $GLOBALS['requests'] );

// --- The pure mapping ---

check( 'attributes: empty for a stage-only partial', array() === ihq_portal_onboarding_braze_attributes( array( 'stage' => 'classic' ), ihq_portal_onboarding_default_state() ) );
check( 'attributes: handles alone also send the platform picture', array_merge( array( 'social_platforms' => array( 'qq' ) ), null_handles_except( array( 'qq' => '1' ) ) ) === ihq_portal_onboarding_braze_attributes( array( 'handles' => array( 'qq' => '1' ) ), array( 'platforms' => array( 'qq' ), 'handles' => array( 'qq' => '1' ) ) ) );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
