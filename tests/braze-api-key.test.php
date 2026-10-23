<?php
/**
 * Unit test for ENGR-8082: the Braze REST keys come only from wp-config, with no
 * fallback key in the theme. A missing constant returns '' and names itself in the
 * error log, once per request. error_log() goes to a temp file the test reads.
 *
 * Also guards the source: no Braze-shaped key (a UUID literal) may come back into
 * inc/braze-integration.php or functions.php.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/braze-api-key.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

$GLOBALS['log_file'] = tempnam( sys_get_temp_dir(), 'ihq-braze-key-log' );
ini_set( 'log_errors', '1' );
ini_set( 'error_log', $GLOBALS['log_file'] );
register_shutdown_function( function () { unlink( $GLOBALS['log_file'] ); } );

// The lines error_log() wrote, without the timestamp PHP puts in front of each.
function logged() {
	$lines = array_filter( explode( "\n", (string) file_get_contents( $GLOBALS['log_file'] ) ) );
	return array_values( preg_replace( '/^\[[^\]]+\] /', '', $lines ) );
}

require __DIR__ . '/../inc/braze-integration.php';

$missing_export = 'IHQ Braze: IHQ_BRAZE_EXPORT_API_KEY is not set in wp-config.php; Braze calls that need it will fail with 401';
$missing_track  = 'IHQ Braze: IHQ_BRAZE_TRACK_API_KEY is not set in wp-config.php; Braze calls that need it will fail with 401';

// --- Neither constant defined: empty keys, each logged once ---

check( 'export key is empty when IHQ_BRAZE_EXPORT_API_KEY is not defined', '' === ihq_braze_export_api_key() );
check( 'track key is empty when IHQ_BRAZE_TRACK_API_KEY is not defined', '' === ihq_braze_track_api_key() );
check( 'a second read returns empty again', '' === ihq_braze_export_api_key() && '' === ihq_braze_track_api_key() );
check( 'each missing constant is logged by name, once per request', array( $missing_export, $missing_track ) === logged() );

// --- Track defined but empty: still treated as missing (already logged, so no new line) ---

define( 'IHQ_BRAZE_TRACK_API_KEY', '' );
check( 'an empty IHQ_BRAZE_TRACK_API_KEY counts as missing', '' === ihq_braze_track_api_key() );
check( 'no duplicate log line for the same constant', array( $missing_export, $missing_track ) === logged() );

// --- Export defined: the constant's value is returned, nothing more is logged ---

define( 'IHQ_BRAZE_EXPORT_API_KEY', 'export-key-from-wp-config' );
check( 'export key comes from IHQ_BRAZE_EXPORT_API_KEY', 'export-key-from-wp-config' === ihq_braze_export_api_key() );
check( 'reading a configured key logs nothing', array( $missing_export, $missing_track ) === logged() );

// --- The generic reader, for an unknown name ---

check( 'ihq_braze_api_key() returns empty for an undefined constant', '' === ihq_braze_api_key( 'IHQ_BRAZE_TEST_UNDEFINED' ) );
check(
	'and logs that constant by name',
	array( $missing_export, $missing_track, 'IHQ Braze: IHQ_BRAZE_TEST_UNDEFINED is not set in wp-config.php; Braze calls that need it will fail with 401' ) === logged()
);

define( 'IHQ_BRAZE_TEST_EMPTY', '' );
check( 'ihq_braze_api_key() returns empty for a constant defined as an empty string', '' === ihq_braze_api_key( 'IHQ_BRAZE_TEST_EMPTY' ) );
check(
	'and logs it as not set, the same as an undefined one',
	array(
		$missing_export,
		$missing_track,
		'IHQ Braze: IHQ_BRAZE_TEST_UNDEFINED is not set in wp-config.php; Braze calls that need it will fail with 401',
		'IHQ Braze: IHQ_BRAZE_TEST_EMPTY is not set in wp-config.php; Braze calls that need it will fail with 401',
	) === logged()
);

// --- Source guard: no committed key-shaped literal ---

$uuid = '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i';
foreach ( array( 'inc/braze-integration.php', 'functions.php' ) as $file ) {
	$source = (string) file_get_contents( __DIR__ . '/../' . $file );
	check( $file . ' contains no UUID-shaped key literal', 0 === preg_match( $uuid, $source ) );
}

exit( $fail > 0 ? 1 : 0 );
