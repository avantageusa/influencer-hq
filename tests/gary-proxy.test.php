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

$GLOBALS['last_remote_request'] = null;
function wp_remote_request( $url, $args ) {
	$GLOBALS['last_remote_request'] = array( 'url' => $url, 'args' => $args );
	return array(
		'response' => array( 'code' => 201 ),
		'body'     => json_encode( array( 'session' => array( 'id' => 'cs_test' ) ) ),
	);
}
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code']; }
function wp_remote_retrieve_body( $response ) { return $response['body']; }

define( 'GARY_COACH_KEY', 'test-key' );
define( 'GARY_COACH_SECRET', 'test-secret' );

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

echo $fail ? "\n$fail FAILED\n" : "\nALL PASS\n";
exit( $fail ? 1 : 0 );
