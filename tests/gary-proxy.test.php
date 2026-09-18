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
	public function __construct( $params = array() ) { $this->params = $params; }
	public function get_param( $key ) { return isset( $this->params[ $key ] ) ? $this->params[ $key ] : null; }
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
$GLOBALS['remote_request_error']   = false; // set true to simulate a transport failure
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

// ihq_coach_handle_attest_identity() — the full route handler.
$GLOBALS['last_remote_request'] = null;
$attest_request  = new WP_REST_Request( array( 'session_id' => 'cs_123', 'player_ref' => 'player-abc' ) );
$attest_response = ihq_coach_handle_attest_identity( $attest_request );
check( 'attest_identity: posts to the right session\'s attest endpoint', false !== strpos( $GLOBALS['last_remote_request']['url'], '/coach/v1/session/cs_123/attest' ) );
$attest_sent_body = json_decode( $GLOBALS['last_remote_request']['args']['body'], true );
check( 'attest_identity: stage is always identity', 'identity' === $attest_sent_body['stage'] );
check( 'attest_identity: response forwarded to the caller', true === $attest_response->data['released'] );

$attest_bad_request  = new WP_REST_Request( array( 'session_id' => '', 'player_ref' => '' ) );
$attest_bad_response = ihq_coach_handle_attest_identity( $attest_bad_request );
check( 'attest_identity: missing session_id/player_ref returns 400', 400 === $attest_bad_response->status );

echo $fail ? "\n$fail FAILED\n" : "\nALL PASS\n";
exit( $fail ? 1 : 0 );
