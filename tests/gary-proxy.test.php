<?php
/**
 * Unit test for inc/gary-proxy.php: the registration-locale choice
 * (ihq_coach_handle_open_session() and the approved-locale lookup behind it,
 * ENGR-7064) and the other proxy routes. No WordPress bootstrap: stubs the
 * handful of WP functions/classes the code touches, and stubs
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
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $s ) ); }
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

// Array-backed stand-ins for the WordPress cache/option APIs, so the approved-
// locale lookup's caching can be asserted without a database.
$GLOBALS['wp_transients']     = array();
$GLOBALS['wp_transient_ttls'] = array();
$GLOBALS['wp_options']        = array();
function get_transient( $key ) { return array_key_exists( $key, $GLOBALS['wp_transients'] ) ? $GLOBALS['wp_transients'][ $key ] : false; }
function set_transient( $key, $value, $ttl = 0 ) { $GLOBALS['wp_transients'][ $key ] = $value; $GLOBALS['wp_transient_ttls'][ $key ] = $ttl; return true; }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['wp_options'] ) ? $GLOBALS['wp_options'][ $key ] : $default; }
function update_option( $key, $value, $autoload = null ) { $GLOBALS['wp_options'][ $key ] = $value; return true; }

$GLOBALS['last_remote_request']    = null;
$GLOBALS['remote_request_error']    = false; // set true to simulate a transport failure
$GLOBALS['remote_request_conflict'] = false; // set true to simulate advance's 409
// The registration manifest the stub serves, and how many times / how it was asked for.
$GLOBALS['scripts_body']            = array( 'version' => 'test-v1', 'segments' => array( 'intro' => array( 'status' => 'approved' ) ) );
$GLOBALS['scripts_status']          = 200;
$GLOBALS['scripts_transport_error'] = false; // transport failure for the manifest ONLY (the session POST still works)
$GLOBALS['scripts_request_count']   = 0;
$GLOBALS['last_scripts_request']    = null;
function wp_remote_request( $url, $args ) {
	$GLOBALS['last_remote_request'] = array( 'url' => $url, 'args' => $args );
	if ( $GLOBALS['remote_request_error'] ) {
		return new WP_Error( 'http_request_failed', 'Could not resolve host' );
	}
	if ( false !== strpos( $url, '/registration/scripts' ) ) {
		++$GLOBALS['scripts_request_count'];
		$GLOBALS['last_scripts_request'] = array( 'url' => $url, 'args' => $args );
		if ( $GLOBALS['scripts_transport_error'] ) {
			return new WP_Error( 'http_request_failed', 'Operation timed out' );
		}
		return array(
			'response' => array( 'code' => $GLOBALS['scripts_status'] ),
			'body'     => json_encode( $GLOBALS['scripts_body'] ),
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
	if ( false !== strpos( $url, '/message' ) ) {
		$sent = json_decode( $args['body'], true );
		return array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( array(
				'say' => array(
					'text'  => 'answer for ' . $sent['text'],
					// A relative path, same shape Gary actually sends — this is
					// what ihq_coach_handle_message() must rewrite to an
					// absolute URL on Gary's own host before returning it.
					'audio' => array( 'id' => 'a1', 'url' => '/coach/v1/audio/a1' ),
				),
			) ),
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

// --- ENGR-7064: the locale Gary is told follows what Gary reports as approved. ---

function reset_locale_state() {
	$GLOBALS['wp_transients']           = array();
	$GLOBALS['wp_transient_ttls']       = array();
	$GLOBALS['wp_options']              = array();
	$GLOBALS['scripts_body']            = array( 'version' => 'test-v1', 'segments' => array( 'intro' => array( 'status' => 'approved' ) ) ); // no `locales` key at all
	$GLOBALS['scripts_status']          = 200;
	$GLOBALS['scripts_transport_error'] = false;
	$GLOBALS['scripts_request_count']   = 0;
	$GLOBALS['last_scripts_request']    = null;
}
// What Gary's manifest looks like once it reports approved locales.
function gary_approves( $registration, $free_text = array( 'en' ) ) {
	$GLOBALS['scripts_body'] = array( 'version' => 'test-v3', 'mode' => 'approved_content', 'locales' => $registration, 'free_text_locales' => $free_text, 'segments' => array() );
}
// A cached read ages out.
function expire_locale_cache() { unset( $GLOBALS['wp_transients'][ IHQ_COACH_LOCALES_TRANSIENT ] ); }

// Gary today (2026-10-05): approved registration languages are ["en"] only.
reset_locale_state();
gary_approves( array( 'en' ) );
check( 'today (Gary approves en only): ja still opens an en session', 'en' === sent_locale( 'ja' ) );
check( 'today: zh still opens an en session', 'en' === sent_locale( 'zh' ) );
check( 'today: en-us still passes through as given (variant of an approved language)', 'en-us' === sent_locale( 'en-us' ) );
check( 'today: en still passes through', 'en' === sent_locale( 'en' ) );

// The day Gary approves a language, it opens by itself (once the cache window passes).
reset_locale_state();
gary_approves( array( 'en', 'ja', 'zh' ) );
check( 'approved ja opens a ja session', 'ja' === sent_locale( 'ja' ) );
check( 'approved zh opens a zh session', 'zh' === sent_locale( 'zh' ) );
check( 'a language Gary has NOT approved (yue) still falls back to en', 'en' === sent_locale( 'yue' ) );
check( 'ko, not approved, falls back to en', 'en' === sent_locale( 'ko' ) );
check( 'uppercase JA is normalised to ja', 'ja' === sent_locale( 'JA' ) );
check( 'a regional variant of an approved language passes through as given (ja-jp)', 'ja-jp' === sent_locale( 'ja-jp' ) );
check( 'a script + region variant of an approved language passes through as given (ja-latn-jp)', 'ja-latn-jp' === sent_locale( 'ja-latn-jp' ) );
check( 'a regional variant of an unapproved language falls back to en (ko-kr)', 'en' === sent_locale( 'ko-kr' ) );

// Malformed or hostile locale values never reach Gary, even with ja approved.
foreach ( array( '', 'e', 'en_US', "ja\nzh", 'ja<script>', 'ja-', '-ja', '123', 'ja--jp', str_repeat( 'x', 40 ), 'ja-' . str_repeat( 'x', 9 ), 'ja-x', 'ja-x-private', 'ja-u-ca-japanese', 'ja-a', 'ja-j-jp' ) as $bad_input ) {
	check( 'malformed locale ' . json_encode( $bad_input ) . ' becomes en', 'en' === sent_locale( $bad_input ) );
}
check( 'null locale becomes en even with ja approved', 'en' === sent_locale( null ) );

// What Gary returns is filtered to the theme's 7 codes, lower-cased, de-duplicated.
reset_locale_state();
gary_approves( array( 'en', 'fr', 'JA', 123, null, 'ja', array( 'zh' ), ' ko ' ), array( 'en', 'ZH', 'xx' ) );
$approved = ihq_coach_approved_locales();
check( 'registration list: unknown codes, non-strings and duplicates dropped; case and whitespace normalised', array( 'en', 'ja', 'ko' ) === $approved['registration'] );
check( 'free_text list is read separately from the registration list', array( 'en', 'zh' ) === $approved['free_text'] );

reset_locale_state();
gary_approves( array( 'en', 'ja' ), array( 'en' ) );
$approved = ihq_coach_approved_locales();
check( 'a language can be approved for sessions but not for free-text questions', array( 'en', 'ja' ) === $approved['registration'] && array( 'en' ) === $approved['free_text'] );

reset_locale_state();
$GLOBALS['scripts_body'] = array( 'locales' => array( 'en', 'ja' ) ); // no free_text_locales key
check( 'a manifest without free_text_locales yields an empty free-text list', array() === ihq_coach_approved_locales()['free_text'] );

// Caching: one read serves every session in the window, with a short timeout.
reset_locale_state();
gary_approves( array( 'en', 'ja' ) );
sent_locale( 'ja' );
sent_locale( 'ja' );
sent_locale( 'zh' );
check( 'three session opens inside the cache window read Gary once', 1 === $GLOBALS['scripts_request_count'] );
check( 'the lookup uses the short timeout, not the 15 s default', IHQ_COACH_LOCALES_FETCH_TIMEOUT === $GLOBALS['last_scripts_request']['args']['timeout'] );
check( 'a successful read is cached for the default 10 minutes', 600 === $GLOBALS['wp_transient_ttls'][ IHQ_COACH_LOCALES_TRANSIENT ] );
check( 'a successful read is also kept as the last-good copy', array( 'en', 'ja' ) === $GLOBALS['wp_options'][ IHQ_COACH_LOCALES_LAST_GOOD_OPTION ]['registration'] );

// Once the cache window passes, a newly approved language is picked up.
gary_approves( array( 'en', 'ja', 'ko' ) );
check( 'before the cache expires the new approval is not seen yet', 'en' === sent_locale( 'ko' ) );
expire_locale_cache();
check( 'after expiry the newly approved language opens', 'ko' === sent_locale( 'ko' ) );
check( 'the expiry caused exactly one more read', 2 === $GLOBALS['scripts_request_count'] );

// The TTL is an option; nonsense falls back to the default.
reset_locale_state();
gary_approves( array( 'en' ) );
$GLOBALS['wp_options'][ IHQ_COACH_LOCALES_TTL_OPTION ] = 120;
ihq_coach_approved_locales();
check( 'ihq_coach_locales_cache_ttl overrides the cache lifetime', 120 === $GLOBALS['wp_transient_ttls'][ IHQ_COACH_LOCALES_TRANSIENT ] );
foreach ( array( 0, -5, 'abc', '' ) as $bad_ttl ) {
	reset_locale_state();
	gary_approves( array( 'en' ) );
	$GLOBALS['wp_options'][ IHQ_COACH_LOCALES_TTL_OPTION ] = $bad_ttl;
	ihq_coach_approved_locales();
	check( 'a non-positive/invalid ttl option (' . json_encode( $bad_ttl ) . ') falls back to 600', 600 === $GLOBALS['wp_transient_ttls'][ IHQ_COACH_LOCALES_TRANSIENT ] );
}

// Gary unreachable, never read before: English only, and the failure is cached briefly.
reset_locale_state();
$GLOBALS['scripts_transport_error'] = true;
check( 'Gary unreachable with no history: ja opens an en session', 'en' === sent_locale( 'ja' ) );
check( 'that fallback is cached for the short failure window (60 s)', 60 === $GLOBALS['wp_transient_ttls'][ IHQ_COACH_LOCALES_TRANSIENT ] );
sent_locale( 'ja' );
sent_locale( 'zh' );
check( 'an outage costs one attempt per window, not one per visitor', 1 === $GLOBALS['scripts_request_count'] );
check( 'a failed read is not stored as the last-good copy', ! array_key_exists( IHQ_COACH_LOCALES_LAST_GOOD_OPTION, $GLOBALS['wp_options'] ) );

// Gary goes down after a good read: the last good value keeps working.
reset_locale_state();
gary_approves( array( 'en', 'ja' ) );
sent_locale( 'ja' );
expire_locale_cache();
$GLOBALS['scripts_transport_error'] = true;
check( 'Gary down after a good read: the last good list still opens ja', 'ja' === sent_locale( 'ja' ) );
check( 'last-good fallback is cached for the short window too', 60 === $GLOBALS['wp_transient_ttls'][ IHQ_COACH_LOCALES_TRANSIENT ] );

// Non-2xx and malformed bodies count as failures, not as "nothing approved".
foreach ( array( 500, 503, 401, 404 ) as $bad_status ) {
	reset_locale_state();
	gary_approves( array( 'en', 'ja' ) );
	sent_locale( 'ja' );
	expire_locale_cache();
	$GLOBALS['scripts_status'] = $bad_status;
	gary_approves( array( 'en' ) ); // even a body that would narrow the list must be ignored on a non-2xx
	check( 'HTTP ' . $bad_status . ' from the manifest keeps the last good list', 'ja' === sent_locale( 'ja' ) );
}
reset_locale_state();
gary_approves( array( 'en', 'ja' ) );
sent_locale( 'ja' );
expire_locale_cache();
$GLOBALS['scripts_body'] = array( 'error' => array( 'code' => 'something_else' ) ); // 200, but no `locales`
check( 'a 200 body with no locales array keeps the last good list', 'ja' === sent_locale( 'ja' ) );
expire_locale_cache();
$GLOBALS['scripts_body'] = array( 'locales' => 'ja' ); // locales present but not an array
check( 'a non-array locales value keeps the last good list', 'ja' === sent_locale( 'ja' ) );
reset_locale_state();
gary_approves( array() ); // Gary genuinely approves nothing: a valid, empty answer
check( 'an empty (but valid) approved list is honoured: everything falls back to en', 'en' === sent_locale( 'ja' ) && 'en' === sent_locale( 'en-us' ) );

// A corrupted last-good option is ignored rather than trusted.
reset_locale_state();
$GLOBALS['scripts_transport_error']                       = true;
$GLOBALS['wp_options'][ IHQ_COACH_LOCALES_LAST_GOOD_OPTION ] = 'garbage';
check( 'a corrupt last-good value falls back to en only', 'en' === sent_locale( 'ja' ) );

// A JSON *object* where a list is expected is a malformed manifest, not a
// successful read (ihq_coach_request() decodes objects to PHP arrays too).
reset_locale_state();
gary_approves( array( 'en', 'ja' ) );
sent_locale( 'ja' );
expire_locale_cache();
$GLOBALS['scripts_body'] = array( 'locales' => array( 'primary' => 'ja', 'secondary' => 'ko' ) ); // JSON object
check( 'object-shaped locales keeps the last good list', 'ja' === sent_locale( 'ja' ) && 'en' === sent_locale( 'ko' ) );
check( 'object-shaped locales does not overwrite the last-good copy', array( 'en', 'ja' ) === $GLOBALS['wp_options'][ IHQ_COACH_LOCALES_LAST_GOOD_OPTION ]['registration'] );
expire_locale_cache();
$GLOBALS['scripts_body'] = array( 'locales' => array( 'en', 'ja', 'ko' ), 'free_text_locales' => array( 'primary' => 'en' ) );
check( 'object-shaped free_text_locales makes the whole manifest malformed: last good kept', 'ja' === sent_locale( 'ja' ) && 'en' === sent_locale( 'ko' ) );
reset_locale_state();
$GLOBALS['scripts_body'] = array( 'locales' => array( 'primary' => 'ja' ) );
check( 'object-shaped locales with no history: en only, nothing stored as last good', 'en' === sent_locale( 'ja' ) && ! array_key_exists( IHQ_COACH_LOCALES_LAST_GOOD_OPTION, $GLOBALS['wp_options'] ) );
reset_locale_state();
$GLOBALS['scripts_body'] = array( 'locales' => array() ); // an empty JSON array is a valid, empty list
check( 'an empty JSON array is a valid list (nothing approved)', array() === ihq_coach_approved_locales()['registration'] );

// Storage is untrusted: a record with the right keys but wrong value types
// must be ignored, never handed to in_array() (a TypeError on PHP 8).
reset_locale_state();
$GLOBALS['wp_transients'][ IHQ_COACH_LOCALES_TRANSIENT ] = array( 'registration' => 'ja', 'free_text' => array( 'en' ) );
gary_approves( array( 'en', 'ko' ) );
check( 'cached record with a non-list value is ignored and the manifest is read instead', 'ko' === sent_locale( 'ko' ) );
reset_locale_state();
$GLOBALS['scripts_transport_error'] = true;
$GLOBALS['wp_options'][ IHQ_COACH_LOCALES_LAST_GOOD_OPTION ] = array( 'registration' => 'ja', 'free_text' => 'en' );
check( 'last-good record with non-list values falls back to en only (no TypeError)', 'en' === sent_locale( 'ja' ) );
reset_locale_state();
$GLOBALS['scripts_transport_error'] = true;
$GLOBALS['wp_options'][ IHQ_COACH_LOCALES_LAST_GOOD_OPTION ] = array( 'registration' => array( 'primary' => 'ja' ), 'free_text' => array( 'en' ) );
check( 'last-good record whose list is an object falls back to en only', 'en' === sent_locale( 'ja' ) );
reset_locale_state();
$GLOBALS['scripts_transport_error'] = true;
$GLOBALS['wp_options'][ IHQ_COACH_LOCALES_LAST_GOOD_OPTION ] = array( 'registration' => array( 'en', 7, array( 'x' ), 'JA', 'fr', null ), 'free_text' => array( 'EN' ) );
$stored = ihq_coach_approved_locales();
check( 'junk inside a stored list is filtered out when it is read back', array( 'en', 'ja' ) === $stored['registration'] && array( 'en' ) === $stored['free_text'] );
reset_locale_state();

// Only the lookup got the shorter timeout: every other call keeps 15 s.
reset_locale_state();
ihq_coach_handle_health();
check( 'ihq_coach_request() default timeout is unchanged for other callers', 15 === $GLOBALS['last_remote_request']['args']['timeout'] );

// The session payload around the locale is untouched.
reset_locale_state();
gary_approves( array( 'en', 'ja' ) );
ihq_coach_handle_open_session( new WP_REST_Request( array( 'locale' => 'ja' ) ) );
$session_body = json_decode( $GLOBALS['last_remote_request']['args']['body'], true );
check( 'session open still sends the pseudonymous player.ref and want set', '00000000-0000-0000-0000-000000000000' === $session_body['player']['ref'] && array( 'text', 'audio', 'video' ) === $session_body['want'] );
check( 'the session open is the last request (the locale lookup happens before it)', false !== strpos( $GLOBALS['last_remote_request']['url'], '/coach/v1/session' ) );
reset_locale_state();

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
$message_sent_body = json_decode( $GLOBALS['last_remote_request']['args']['body'], true );
check( 'message: requests audio and video, not just text (else a generated answer comes back with say.audio/say.video null)', array( 'text', 'audio', 'video' ) === $message_sent_body['want'] );

// PO-3346 — a caller with an already-connected Anam avatar can ask for
// want: ['text'] only on a follow-up question, instead of tying up another
// avatar seat for video it won't use.
$GLOBALS['last_remote_request'] = null;
$message_want_request = new WP_REST_Request( array( 'text' => 'hello again', 'want' => array( 'text' ) ), array( 'session_id' => 'cs_123' ) );
ihq_coach_handle_message( $message_want_request );
$message_want_sent_body = json_decode( $GLOBALS['last_remote_request']['args']['body'], true );
check( 'message: an explicit want is forwarded as given', array( 'text' ) === $message_want_sent_body['want'] );

// Invalid/unknown values are dropped rather than forwarded verbatim to
// Gary; an empty result after filtering falls back to the full default
// set, same as not sending want at all.
$GLOBALS['last_remote_request'] = null;
$message_bad_want_request = new WP_REST_Request( array( 'text' => 'hello', 'want' => array( 'text', 'nonsense', 'text' ) ), array( 'session_id' => 'cs_123' ) );
ihq_coach_handle_message( $message_bad_want_request );
$message_bad_want_sent_body = json_decode( $GLOBALS['last_remote_request']['args']['body'], true );
check( 'message: an unknown want value is dropped, duplicates collapsed', array( 'text' ) === $message_bad_want_sent_body['want'] );

$GLOBALS['last_remote_request'] = null;
$message_empty_want_request = new WP_REST_Request( array( 'text' => 'hello', 'want' => array( 'nonsense' ) ), array( 'session_id' => 'cs_123' ) );
ihq_coach_handle_message( $message_empty_want_request );
$message_empty_want_sent_body = json_decode( $GLOBALS['last_remote_request']['args']['body'], true );
check( 'message: want left with nothing valid falls back to the full default set', array( 'text', 'audio', 'video' ) === $message_empty_want_sent_body['want'] );

// PO-3346 — say.audio.url comes back from Gary as a path relative to
// Gary's own host, not ours; the browser fetches it directly (confirmed
// unauthenticated with Gary, 2026-09-30), so it must be rewritten to an
// absolute URL before this response reaches the browser.
$GLOBALS['last_remote_request'] = null;
$message_audio_request  = new WP_REST_Request( array( 'text' => 'hi' ), array( 'session_id' => 'cs_123' ) );
$message_audio_response = ihq_coach_handle_message( $message_audio_request );
check(
	'message: say.audio.url is rewritten to an absolute URL on Gary\'s own host',
	IHQ_COACH_HOST . '/coach/v1/audio/a1' === $message_audio_response->data['say']['audio']['url']
);

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
