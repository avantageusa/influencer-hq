<?php
/**
 * Unit test for PO-3106 (FR-15): the AI Coach language preference reaching
 * Braze as language_preference, and the ordering fix that makes it possible.
 *
 * Checked at two different levels, deliberately:
 *
 *  1. ihq_send_influencer_to_braze() (inc/braze-integration.php) is
 *     exercised for real — stubbed WP functions + a wp_remote_post() that
 *     captures the outgoing payload instead of calling real Braze. This is
 *     the actual behavior: does the Braze payload carry language_preference
 *     when the AI Coach language meta is set, and omit the key entirely
 *     (never send an empty string) for every other registration path that
 *     never sets it.
 *
 *  2. A structural check on inc/email-verification-handler.php's real
 *     source: inside ihq_create_influencer_user_from_registration_data(),
 *     the _ihq_aicoach_language meta write must appear BEFORE the
 *     ihq_send_influencer_to_braze() call. That function's own dependency
 *     tree (wp_create_user, OAuth session start, username generation, ...)
 *     is large enough that stubbing all of it just to reach two lines whose
 *     ORDER is what actually matters would be a fragile integration fixture
 *     for a regression that's really about statement order, not behavior a
 *     mock can observe any more directly than reading the source. This is
 *     the smaller, more honest test for that specific bug.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/aicoach-language-braze.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

// --- Part 1: ordering, checked against the real source file ---

$source   = file_get_contents( __DIR__ . '/../inc/email-verification-handler.php' );
$fn_start = strpos( $source, 'function ihq_create_influencer_user_from_registration_data' );
check( 'ihq_create_influencer_user_from_registration_data() exists in the source', false !== $fn_start );

$fn_end  = strpos( $source, "\nfunction ", $fn_start + 1 );
$fn_body = substr( $source, $fn_start, $fn_end - $fn_start );

$meta_write_pos = strpos( $fn_body, '_ihq_aicoach_language' );
$braze_call_pos = strpos( $fn_body, 'ihq_send_influencer_to_braze(' );
check( 'the function writes _ihq_aicoach_language meta', false !== $meta_write_pos );
check( 'the function calls ihq_send_influencer_to_braze()', false !== $braze_call_pos );
check( 'the language meta is written BEFORE ihq_send_influencer_to_braze() runs (the actual bug this ticket fixed)', $meta_write_pos < $braze_call_pos );

// --- Part 2: ihq_send_influencer_to_braze()'s payload shape, exercised for real ---

class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
function is_email( $email ) { return false !== strpos( (string) $email, '@' ); }
function sanitize_email( $email ) { return trim( (string) $email ); }
function wp_json_encode( $data ) { return json_encode( $data ); }
// error_log() is a real PHP built-in (writes to stderr) — no stub needed or
// possible; its output during this run is harmless noise, not a failure.

$GLOBALS['user_meta']  = array(); // user_id => [ key => value ]
$GLOBALS['test_users'] = array(); // user_id => stdClass( user_email, display_name )

function get_user_meta( $user_id, $key, $single = false ) {
	return isset( $GLOBALS['user_meta'][ $user_id ][ $key ] ) ? $GLOBALS['user_meta'][ $user_id ][ $key ] : '';
}
function update_user_meta( $user_id, $key, $value ) {
	$GLOBALS['user_meta'][ $user_id ][ $key ] = $value;
}
function get_userdata( $user_id ) {
	return isset( $GLOBALS['test_users'][ $user_id ] ) ? $GLOBALS['test_users'][ $user_id ] : false;
}
function make_test_user( $id, $email ) {
	$u               = new stdClass();
	$u->user_email   = $email;
	$u->display_name = 'Test User';
	$GLOBALS['test_users'][ $id ] = $u;
}

$GLOBALS['last_braze_track_body'] = null;
function wp_remote_post( $url, $args ) {
	if ( false !== strpos( $url, '/users/export/ids' ) ) {
		// No existing Braze user for any test email — exercises the
		// "mint a new guid" branch, same as any first-time registration.
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( array( 'users' => array() ) ),
		);
	}
	if ( false !== strpos( $url, '/users/track' ) ) {
		$GLOBALS['last_braze_track_body'] = json_decode( $args['body'], true );
		return array( 'response' => array( 'code' => 201 ), 'body' => '{}' );
	}
	return new WP_Error( 'unexpected_url', 'Unhandled URL in test: ' . $url );
}
function wp_remote_retrieve_body( $r ) { return is_array( $r ) ? $r['body'] : ''; }
function wp_remote_retrieve_response_code( $r ) { return is_array( $r ) ? $r['response']['code'] : 0; }

require __DIR__ . '/../inc/braze-integration.php';

// Case A: a user with a saved AI Coach language preference.
make_test_user( 101, 'withlang@example.com' );
update_user_meta( 101, '_ihq_aicoach_language', 'ja' );
$GLOBALS['last_braze_track_body'] = null;
ihq_send_influencer_to_braze( 101 );
check(
	'language_preference is included, with the right value, when the AI Coach language meta is set',
	'ja' === ( $GLOBALS['last_braze_track_body']['attributes'][0]['language_preference'] ?? null )
);

// Case B: a user with no AI Coach language meta at all — every non-AI-Coach
// registration path (visitor-intent-handler, telegram-login-handler,
// email-verification-handler's own direct calls) looks exactly like this.
make_test_user( 102, 'nolang@example.com' );
$GLOBALS['last_braze_track_body'] = null;
ihq_send_influencer_to_braze( 102 );
check(
	'language_preference key is omitted entirely (not sent as an empty string) when no language was captured',
	! array_key_exists( 'language_preference', $GLOBALS['last_braze_track_body']['attributes'][0] )
);
check(
	'the existing Language (country) field is untouched by this change',
	array_key_exists( 'Language', $GLOBALS['last_braze_track_body']['attributes'][0] )
);

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
