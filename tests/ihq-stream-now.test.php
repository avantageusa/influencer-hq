<?php
/**
 * Unit test for inc/ihq-stream-now.php (ENGR-7346): the STREAM NOW href is
 * IHQ_PUBLISHER_URL plus the stored SSO code, built the way PLAY builds its
 * link, and '' (button not rendered) when logged out or when the constant is
 * unset or not an absolute https:// URL. No WordPress bootstrap:
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/ihq-stream-now.test.php
 *
 * Exit code is non-zero on any failure.
 */
$log_file = tempnam( sys_get_temp_dir(), 'ihq-stream-now-test' );
ini_set( 'error_log', $log_file );
define( 'ABSPATH', '/' );
define( 'IHQ_ENVIRONMENT', 'test' );
define( 'IHQ_API_BASE_URL', 'https://api.example.test/qc' );
define( 'IHQ_GAME_PORTAL_BASE_URL', 'https://portal.example.test/av-baccarat' );
define( 'IHQ_INFLUENCER_API_KEY', 'key' );

function wp_parse_url( $u ) { return parse_url( $u ); }
function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function add_action( ...$args ) {}
function esc_html( $s ) { return $s; }
function wp_die( $m ) { throw new RuntimeException( 'wp_die: ' . $m ); }

// Recording stubs: the stored-code reader (inc/email-verification-handler.php) and WP's add_query_arg.
function ihq_get_hq_sso_code_for_user( $user_id ) {
	$GLOBALS['sso_calls'][] = $user_id;
	return $GLOBALS['sso_code'];
}
function add_query_arg( $args, $url ) {
	$GLOBALS['query_arg_calls'][] = array( $args, $url );
	return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . http_build_query( $args );
}

require __DIR__ . '/../inc/ihq-env.php';
require __DIR__ . '/../inc/ihq-stream-now.php';

$fail = 0;
function check( $label, $actual, $expected ) {
	global $fail;
	$ok = $actual === $expected;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $label . "\n";
	if ( ! $ok ) {
		echo '  expected: ' . var_export( $expected, true ) . "\n  actual:   " . var_export( $actual, true ) . "\n";
		$fail++;
	}
}

const PUBLISHER   = 'https://publisher.example.test';
const USER_ID     = 42;
const SSO_CODE    = 'code-123';
const INVALID_LOG = "[ihq-stream-now] IHQ_PUBLISHER_URL must be an absolute https:// URL; STREAM NOW is hidden.\n";

/**
 * Build the link with IHQ_PUBLISHER_URL set in the environment (false = unset)
 * and a stored code, after clearing the recorded calls and the log.
 */
function stream_now_with( $user_id, $publisher_url, $sso_code = SSO_CODE ) {
	$GLOBALS['sso_calls']       = array();
	$GLOBALS['query_arg_calls'] = array();
	$GLOBALS['sso_code']        = $sso_code;
	file_put_contents( $GLOBALS['log_file'], '' );
	putenv( $publisher_url === false ? 'IHQ_PUBLISHER_URL' : 'IHQ_PUBLISHER_URL=' . $publisher_url );
	return ihq_get_stream_now_url( $user_id );
}

/** What was logged since the last stream_now_with(), without PHP's timestamp prefix. */
function logged() {
	return preg_replace( '/^\[[^\]]*\] /m', '', file_get_contents( $GLOBALS['log_file'] ) );
}

// Happy path (AC1, AC3): publisher URL plus the same stored code PLAY reads.
check( 'configured + code: publisher URL with hqSsoCode', stream_now_with( USER_ID, PUBLISHER ), PUBLISHER . '?hqSsoCode=' . SSO_CODE );
check( 'configured + code: stored code read for this user', $GLOBALS['sso_calls'], array( USER_ID ) );
check( 'configured + code: add_query_arg gets only hqSsoCode and the configured URL', $GLOBALS['query_arg_calls'], array( array( array( 'hqSsoCode' => SSO_CODE ), PUBLISHER ) ) );
check( 'configured + code: nothing logged', logged(), '' );

// AC4: no stored code -> the bare publisher URL, which shows the publisher's sign-in screen.
check( 'no stored code: publisher URL without hqSsoCode', stream_now_with( USER_ID, PUBLISHER, '' ), PUBLISHER );
check( 'no stored code: stored code was looked up', $GLOBALS['sso_calls'], array( USER_ID ) );
check( 'no stored code: add_query_arg not called', $GLOBALS['query_arg_calls'], array() );

// The configured URL is used as written: no path appended, trailing slash kept.
check( 'path and trailing slash kept', stream_now_with( USER_ID, 'https://publisher.example.test/app/' ), 'https://publisher.example.test/app/?hqSsoCode=' . SSO_CODE );
check( 'path and trailing slash kept: add_query_arg gets the URL unchanged', $GLOBALS['query_arg_calls'], array( array( array( 'hqSsoCode' => SSO_CODE ), 'https://publisher.example.test/app/' ) ) );

// AC6: logged-out visitor (user id 0) -> no link, no lookup, no log, even when misconfigured.
foreach ( array( 'user id 0' => 0, 'negative user id' => -1 ) as $label => $user_id ) {
	check( "$label: no link", stream_now_with( $user_id, PUBLISHER ), '' );
	check( "$label: stored code not read", $GLOBALS['sso_calls'], array() );
	check( "$label: add_query_arg not called", $GLOBALS['query_arg_calls'], array() );
}
check( 'logged out + invalid URL: no link', stream_now_with( 0, 'http://publisher.example.test' ), '' );
check( 'logged out + invalid URL: nothing logged', logged(), '' );

// AC5: constant absent -> no link, silently (absent is the normal "off" state and the kill switch).
foreach ( array( 'unset' => false, 'empty string' => '' ) as $label => $publisher_url ) {
	check( "$label: no link", stream_now_with( USER_ID, $publisher_url ), '' );
	check( "$label: stored code not read", $GLOBALS['sso_calls'], array() );
	check( "$label: nothing logged", logged(), '' );
}

// AC5: set but not an absolute https:// URL -> no link, and the log says why the button is gone.
$invalid_urls = array(
	'http scheme' => 'http://publisher.example.test',
	'no host'     => 'https:///publisher',
	'not a URL'   => 'not a url',
);
foreach ( $invalid_urls as $label => $publisher_url ) {
	check( "$label: no link", stream_now_with( USER_ID, $publisher_url ), '' );
	check( "$label: stored code not read", $GLOBALS['sso_calls'], array() );
	check( "$label: add_query_arg not called", $GLOBALS['query_arg_calls'], array() );
	check( "$label: logged once, naming the constant", logged(), INVALID_LOG );
}

// Optional key: unset or invalid never becomes a site-wide configuration error (no wp_die at load).
putenv( 'IHQ_PUBLISHER_URL' );
check( 'unset IHQ_PUBLISHER_URL is not a configuration problem', ihq_env_problems(), array() );
putenv( 'IHQ_PUBLISHER_URL=http://publisher.example.test' );
check( 'http IHQ_PUBLISHER_URL is not a configuration problem', ihq_env_problems(), array() );

// Resolution order: the wp-config constant wins over the environment variable.
define( 'IHQ_PUBLISHER_URL', 'https://const-publisher.example.test' );
check( 'constant wins over env', stream_now_with( USER_ID, PUBLISHER ), 'https://const-publisher.example.test?hqSsoCode=' . SSO_CODE );

putenv( 'IHQ_PUBLISHER_URL' );
unlink( $log_file );

echo $fail === 0 ? "ALL PASS\n" : "$fail FAILED\n";
exit( $fail === 0 ? 0 : 1 );
