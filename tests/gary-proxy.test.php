<?php
/**
 * Unit test for inc/gary-proxy.php's registration-locale clamping
 * (ihq_coach_handle_open_session()). No WordPress bootstrap: stubs the
 * handful of WP functions/classes the function touches, and stubs
 * wp_remote_request() to capture the outgoing payload instead of making a
 * real network call to Gary, e.g.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/gary-proxy.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

class WP_REST_Request {
	private $params;
	private $url_params;
	public function __construct( $params = array(), $url_params = array() ) { $this->params = $params; $this->url_params = $url_params; }
	public function get_param( $key ) { return isset( $this->params[ $key ] ) ? $this->params[ $key ] : null; }
	public function get_url_params() { return $this->url_params; }
	public function get_header( $key ) { return null; }
}
class WP_REST_Response {
	public $data;
	public $status;
	public function __construct( $data, $status = 200 ) { $this->data = $data; $this->status = $status; }
}
class WP_Error {
	private $message;
	public function __construct( $code = '', $message = '' ) { $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function wp_check_invalid_utf8( $s, $strip = false ) { return (string) $s; }
function wp_generate_uuid4() { return '00000000-0000-0000-0000-000000000000'; }
function wp_json_encode( $data ) { return json_encode( $data ); }
function add_action( $hook, $cb ) {} // routes are never actually registered in this test

// Same shared validator inc/ihq-env.php provides in the real app (loaded
// before inc/gary-proxy.php in functions.php) — reimplemented here since this
// test has no WordPress bootstrap.
function ihq_env_is_https_url( $url ) {
	if ( ! is_string( $url ) || '' === $url ) {
		return false;
	}
	$parts = parse_url( $url );
	if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
		return false;
	}
	return isset( $parts['scheme'] ) && strtolower( $parts['scheme'] ) === 'https';
}

$GLOBALS['last_remote_request']    = null;
$GLOBALS['remote_request_error']    = false; // set true to simulate a transport failure
$GLOBALS['remote_request_conflict'] = false; // set true to simulate advance's 409
function wp_remote_request( $url, $args ) {
	$GLOBALS['last_remote_request'] = array( 'url' => $url, 'args' => $args );
	if ( $GLOBALS['remote_request_error'] ) {
		return new WP_Error( 'http_request_failed', 'Could not resolve host' );
	}
	if ( false !== strpos( $url, '/registration/scripts' ) ) {
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( array( 'version' => 'test-v1', 'segments' => array( 'intro' => array( 'status' => 'approved' ) ) ) ),
		);
	}
	if ( false !== strpos( $url, '/coach/v1/health' ) ) {
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( array( 'ok' => true, 'registration' => array( 'key_id' => 'ck_test_key_id' ) ) ),
		);
	}
	if ( false !== strpos( $url, '/attest' ) ) {
		$sent = json_decode( $args['body'], true );
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( array( 'released' => true, 'stage' => $sent['stage'] ) ),
		);
	}
	if ( false !== strpos( $url, '/narrate' ) ) {
		$sent = json_decode( $args['body'], true );
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( array( 'say' => array( 'text' => 'approved passage for ' . $sent['script_id'] ), 'structured' => array( 'stage' => $sent['script_id'] ) ) ),
		);
	}
	if ( false !== strpos( $url, '/advance' ) ) {
		if ( $GLOBALS['remote_request_conflict'] ) {
			return array( 'response' => array( 'code' => 409 ), 'body' => json_encode( array( 'error' => array( 'code' => 'stale_registration_stage' ) ) ) );
		}
		$sent = json_decode( $args['body'], true );
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( array( 'structured' => array( 'stage' => 'we_believe_1', 'revision' => $sent['expected_revision'] + 1 ), 'advanced_with_tier' => $sent['tier'] ?? null ) ),
		);
	}
	return array(
		'response' => array( 'code' => 201 ),
		'body'     => json_encode( array( 'session' => array( 'id' => 'cs_test' ) ) ),
	);
}
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }

define( 'GARY_COACH_KEY', 'test-key' );
define( 'GARY_COACH_SECRET', 'test-secret' );
// Fake, test-only value — never the real COACH_REGISTRATION_SECRET.
define( 'COACH_REGISTRATION_SECRET', 'test-registration-secret-at-least-32-chars-long' );

require __DIR__ . '/../inc/gary-proxy.php';

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

/**
 * Opens a session with the given raw locale input and returns the
 * player.locale value that was actually sent to Gary.
 */
function sent_locale( $locale_input ) {
	$GLOBALS['last_remote_request'] = null;
	$request = new WP_REST_Request( array( 'locale' => $locale_input ) );
	ihq_coach_handle_open_session( $request );
	$sent_body = json_decode( $GLOBALS['last_remote_request']['args']['body'], true );
	return $sent_body['player']['locale'];
}

// Gary's registration surface accepts only en/en-us/en-gb (confirmed
// 2026-09-16) and 422s everything else — these are the exact cases
// CodeRabbit flagged as untested on PR #32.
check( 'en passes through unchanged', 'en' === sent_locale( 'en' ) );
check( 'en-us passes through unchanged', 'en-us' === sent_locale( 'en-us' ) );
check( 'en-gb passes through unchanged', 'en-gb' === sent_locale( 'en-gb' ) );
check( 'uppercase EN-US normalized to lowercase', 'en-us' === sent_locale( 'EN-US' ) );
check( 'missing locale (null) falls back to en', 'en' === sent_locale( null ) );
check( 'empty string falls back to en', 'en' === sent_locale( '' ) );
check( 'unsupported locale (ja) falls back to en', 'en' === sent_locale( 'ja' ) );
check( 'unsupported locale (zh) falls back to en', 'en' === sent_locale( 'zh' ) );
check( 'unsupported locale (fr) falls back to en', 'en' === sent_locale( 'fr' ) );

// ihq_coach_handle_scripts() — GET /coach/v1/registration/scripts passthrough.
$GLOBALS['remote_request_error'] = false;
$GLOBALS['last_remote_request']  = null;
$scripts_response = ihq_coach_handle_scripts();
check( 'scripts: requests the correct upstream path', false !== strpos( $GLOBALS['last_remote_request']['url'], '/coach/v1/registration/scripts' ) );
check( 'scripts: forwards Gary\'s status code', 200 === $scripts_response->status );
check( 'scripts: forwards Gary\'s body unchanged', 'test-v1' === $scripts_response->data['version'] );
check( 'scripts: forwards nested segment data', 'approved' === $scripts_response->data['segments']['intro']['status'] );

// ihq_coach_handle_scripts() — a transport failure (e.g. DNS/timeout) surfaces
// as a controlled 502, not a fatal error or a silent empty response.
$GLOBALS['remote_request_error'] = true;
$scripts_error_response = ihq_coach_handle_scripts();
check( 'scripts: transport error returns 502', 502 === $scripts_error_response->status );
check( 'scripts: transport error body carries the message', 'Could not resolve host' === $scripts_error_response->data['error'] );
$GLOBALS['remote_request_error'] = false;

// ihq_coach_sign_stage_release() — token shape and claims, matching
// sami-portal-proof.mjs's createSamiStageRelease exactly.
function base64url_decode( $s ) { return base64_decode( strtr( $s, '-_', '+/' ) ); }
$token = ihq_coach_sign_stage_release( 'identity', 'ck_test_key_id', 'player-abc', 'cs_123', array( 'narration_not_required' => true ) );
check( 'sign_stage_release: returns a two-part token', is_string( $token ) && 1 === substr_count( $token, '.' ) );
list( $encoded_claims, $sig ) = explode( '.', $token );
$claims = json_decode( base64url_decode( $encoded_claims ), true );
check( 'sign_stage_release: aud is sami:registration_stage', 'sami:registration_stage' === $claims['aud'] );
check( 'sign_stage_release: carries stage/key_id/player_ref/session_id', 'identity' === $claims['stage'] && 'ck_test_key_id' === $claims['key_id'] && 'player-abc' === $claims['player_ref'] && 'cs_123' === $claims['session_id'] );
check( 'sign_stage_release: carries the verified fact', true === $claims['narration_not_required'] );
check( 'sign_stage_release: exp is 60s after iat', 60 === ( $claims['exp'] - $claims['iat'] ) );
$expected_sig = rtrim( strtr( base64_encode( hash_hmac( 'sha256', $encoded_claims, 'test-registration-secret-at-least-32-chars-long', true ) ), '+/', '-_' ), '=' );
check( 'sign_stage_release: signature matches HMAC-SHA256(secret, encoded_claims)', $sig === $expected_sig );

// ihq_coach_handle_attest_identity() — the full route handler. session_id
// comes from get_url_params() (second constructor arg here), matching the
// real route's regex capture, not the request body.
$GLOBALS['last_remote_request'] = null;
$attest_request  = new WP_REST_Request( array( 'player_ref' => 'player-abc' ), array( 'session_id' => 'cs_123' ) );
$attest_response = ihq_coach_handle_attest_identity( $attest_request );
check( 'attest_identity: posts to the right session\'s attest endpoint', false !== strpos( $GLOBALS['last_remote_request']['url'], '/coach/v1/session/cs_123/attest' ) );
$attest_sent_body = json_decode( $GLOBALS['last_remote_request']['args']['body'], true );
check( 'attest_identity: stage is always identity', 'identity' === $attest_sent_body['stage'] );
check( 'attest_identity: response forwarded to the caller', true === $attest_response->data['released'] );

$attest_bad_request  = new WP_REST_Request( array( 'player_ref' => '' ), array( 'session_id' => '' ) );
$attest_bad_response = ihq_coach_handle_attest_identity( $attest_bad_request );
check( 'attest_identity: missing session_id/player_ref returns 400', 400 === $attest_bad_response->status );

// Regression for the CodeRabbit finding on PR #35: a conflicting body
// session_id must NOT beat the URL's — get_param() would have let it win.
$GLOBALS['last_remote_request'] = null;
$conflict_request  = new WP_REST_Request(
	array( 'player_ref' => 'player-abc', 'session_id' => 'cs_attacker_supplied' ),
	array( 'session_id' => 'cs_123' )
);
$conflict_response = ihq_coach_handle_attest_identity( $conflict_request );
check( 'attest_identity: URL session_id wins over a conflicting body session_id', false !== strpos( $GLOBALS['last_remote_request']['url'], '/coach/v1/session/cs_123/attest' ) );
check( 'attest_identity: the conflicting body session_id is never used', false === strpos( $GLOBALS['last_remote_request']['url'], 'cs_attacker_supplied' ) );
check( 'attest_identity: conflicting request still succeeds using the URL session_id', true === $conflict_response->data['released'] );

// ihq_coach_handle_message() / ihq_coach_handle_close() — same URL-params
// regression as attest_identity, for the two pre-existing routes that had
// the identical get_param() bug (not flagged by CodeRabbit since they
// weren't in the PR #35 diff, but the same fix applies).
$GLOBALS['last_remote_request'] = null;
$message_request = new WP_REST_Request( array( 'text' => 'hello', 'session_id' => 'cs_attacker_supplied' ), array( 'session_id' => 'cs_123' ) );
ihq_coach_handle_message( $message_request );
check( 'message: URL session_id wins over a conflicting body session_id', false !== strpos( $GLOBALS['last_remote_request']['url'], '/coach/v1/session/cs_123/message' ) );

$GLOBALS['last_remote_request'] = null;
$close_request = new WP_REST_Request( array( 'session_id' => 'cs_attacker_supplied' ), array( 'session_id' => 'cs_123' ) );
ihq_coach_handle_close( $close_request );
check( 'close: URL session_id wins over a conflicting body session_id', false !== strpos( $GLOBALS['last_remote_request']['url'], '/coach/v1/session/cs_123/close' ) );

// ihq_coach_handle_narrate() — speaks the current stage's approved passage.
$GLOBALS['last_remote_request'] = null;
$narrate_request  = new WP_REST_Request( array( 'script_id' => 'we_believe_1', 'version' => 'ihq-registration-2026-09-15.v2' ), array( 'session_id' => 'cs_123' ) );
$narrate_response = ihq_coach_handle_narrate( $narrate_request );
check( 'narrate: posts to the right session\'s narrate endpoint', false !== strpos( $GLOBALS['last_remote_request']['url'], '/coach/v1/session/cs_123/narrate' ) );
$narrate_sent_body = json_decode( $GLOBALS['last_remote_request']['args']['body'], true );
check( 'narrate: forwards script_id', 'we_believe_1' === $narrate_sent_body['script_id'] );
check( 'narrate: forwards version', 'ihq-registration-2026-09-15.v2' === $narrate_sent_body['version'] );
check( 'narrate: response forwarded to the caller', 'approved passage for we_believe_1' === $narrate_response->data['say']['text'] );

$narrate_bad_request  = new WP_REST_Request( array( 'script_id' => '' ), array( 'session_id' => 'cs_123' ) );
$narrate_bad_response = ihq_coach_handle_narrate( $narrate_bad_request );
check( 'narrate: missing script_id returns 400', 400 === $narrate_bad_response->status );

// ihq_coach_handle_advance() — moves to the next stage, optimistic concurrency.
$GLOBALS['last_remote_request'] = null;
$advance_request  = new WP_REST_Request( array( 'expected_stage' => 'intro', 'expected_revision' => 0, 'tier' => 5 ), array( 'session_id' => 'cs_123' ) );
$advance_response = ihq_coach_handle_advance( $advance_request );
check( 'advance: posts to the right session\'s advance endpoint', false !== strpos( $GLOBALS['last_remote_request']['url'], '/coach/v1/session/cs_123/advance' ) );
$advance_sent_body = json_decode( $GLOBALS['last_remote_request']['args']['body'], true );
check( 'advance: forwards expected_stage/expected_revision', 'intro' === $advance_sent_body['expected_stage'] && 0 === $advance_sent_body['expected_revision'] );
check( 'advance: forwards a valid tier', 5 === $advance_sent_body['tier'] );
check( 'advance: response forwarded to the caller', 'we_believe_1' === $advance_response->data['structured']['stage'] );

$advance_no_tier_request = new WP_REST_Request( array( 'expected_stage' => 'we_believe_1', 'expected_revision' => 1 ), array( 'session_id' => 'cs_123' ) );
ihq_coach_handle_advance( $advance_no_tier_request );
$advance_no_tier_sent = json_decode( $GLOBALS['last_remote_request']['args']['body'], true );
check( 'advance: tier omitted when not applicable', ! array_key_exists( 'tier', $advance_no_tier_sent ) );

// An out-of-enum tier is REJECTED (400), not silently dropped — dropping it
// used to mean Gary saw the request as missing a tier entirely at
// time_selection (time_tier_required), hiding that the caller actually sent
// one, just an invalid value (review feedback from Dejan Arsic on PR #36).
$advance_invalid_tier_request = new WP_REST_Request( array( 'expected_stage' => 'time_selection', 'expected_revision' => 2, 'tier' => 7 ), array( 'session_id' => 'cs_123' ) );
check( 'advance: an out-of-enum tier (7) returns 400, not silently dropped', 400 === ihq_coach_handle_advance( $advance_invalid_tier_request )->status );

$advance_bad_request  = new WP_REST_Request( array( 'expected_stage' => '' ), array( 'session_id' => 'cs_123' ) );
$advance_bad_response = ihq_coach_handle_advance( $advance_bad_request );
check( 'advance: missing expected_stage/expected_revision returns 400', 400 === $advance_bad_response->status );

// Regression for the CodeRabbit finding on PR #36: a fractional
// expected_revision/tier must be REJECTED (400), never silently truncated
// and forwarded as if it were the integer 0/5/etc.
$advance_fractional_revision_string = new WP_REST_Request( array( 'expected_stage' => 'intro', 'expected_revision' => '0.9' ), array( 'session_id' => 'cs_123' ) );
check( 'advance: fractional expected_revision string (e.g. "0.9") returns 400, not truncated to 0', 400 === ihq_coach_handle_advance( $advance_fractional_revision_string )->status );

$advance_fractional_revision_float = new WP_REST_Request( array( 'expected_stage' => 'intro', 'expected_revision' => 0.9 ), array( 'session_id' => 'cs_123' ) );
check( 'advance: fractional expected_revision as a JSON float returns 400', 400 === ihq_coach_handle_advance( $advance_fractional_revision_float )->status );

$advance_negative_revision = new WP_REST_Request( array( 'expected_stage' => 'intro', 'expected_revision' => -1 ), array( 'session_id' => 'cs_123' ) );
check( 'advance: negative expected_revision returns 400', 400 === ihq_coach_handle_advance( $advance_negative_revision )->status );

$advance_fractional_tier = new WP_REST_Request( array( 'expected_stage' => 'time_selection', 'expected_revision' => 3, 'tier' => '5.9' ), array( 'session_id' => 'cs_123' ) );
check( 'advance: fractional tier ("5.9") returns 400, never silently truncated to 5', 400 === ihq_coach_handle_advance( $advance_fractional_tier )->status );

// A well-formed, still-valid request keeps working after tightening validation.
$GLOBALS['last_remote_request'] = null;
$advance_valid_revision_string = new WP_REST_Request( array( 'expected_stage' => 'intro', 'expected_revision' => '0', 'tier' => '10' ), array( 'session_id' => 'cs_123' ) );
$advance_valid_revision_response = ihq_coach_handle_advance( $advance_valid_revision_string );
check( 'advance: a plain-digit-string revision ("0") and tier ("10") are still accepted', 200 === $advance_valid_revision_response->status );
$advance_valid_revision_sent = json_decode( $GLOBALS['last_remote_request']['args']['body'], true );
check( 'advance: string revision/tier still forwarded as real integers', 0 === $advance_valid_revision_sent['expected_revision'] && 10 === $advance_valid_revision_sent['tier'] );

// advance's 409 (stale/skipped/duplicate) passes through unchanged, not
// swallowed or retried — the caller is expected to re-read the session.
$GLOBALS['remote_request_conflict'] = true;
$advance_conflict_request  = new WP_REST_Request( array( 'expected_stage' => 'intro', 'expected_revision' => 0 ), array( 'session_id' => 'cs_123' ) );
$advance_conflict_response = ihq_coach_handle_advance( $advance_conflict_request );
check( 'advance: a 409 conflict passes through as-is', 409 === $advance_conflict_response->status );
$GLOBALS['remote_request_conflict'] = false;

echo $fail ? "\n$fail FAILED\n" : "\nALL PASS\n";
exit( $fail ? 1 : 0 );
