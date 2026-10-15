<?php
/**
 * Unit test for the portal onboarding kill switch (ENGR-7561,
 * inc/portal-onboarding-switch.php). No WordPress bootstrap: get_option() is
 * stubbed over an in-memory options table.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/portal-onboarding-switch.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

$GLOBALS['wp_options']    = array();
$GLOBALS['option_reads'] = array();
function get_option( $key, $default = false ) {
	$GLOBALS['option_reads'][] = array( $key, $default );
	return array_key_exists( $key, $GLOBALS['wp_options'] ) ? $GLOBALS['wp_options'][ $key ] : $default;
}

// The theme loads the module through the onboarding glob block in functions.php.
// Runs before anything else requires the module, so a missing or broken block
// cannot be masked. functions.php as a whole needs a full WordPress bootstrap,
// so the test runs only that block. get_template_directory() points at a fixture
// theme whose inc/ holds just a symlink to this module: the other onboarding
// modules the glob would also pick up need WordPress stubs this test doesn't
// have. The symlink resolves to the real file, so the require_once below sees
// the same path and doesn't load it twice.
$GLOBALS['fixture_theme'] = sys_get_temp_dir() . '/ihq-onboarding-switch-test-' . getmypid();
$fixture_module           = $GLOBALS['fixture_theme'] . '/inc/portal-onboarding-switch.php';
mkdir( dirname( $fixture_module ), 0700, true );
symlink( realpath( __DIR__ . '/../inc/portal-onboarding-switch.php' ), $fixture_module );
register_shutdown_function(
	function () use ( $fixture_module ) {
		unlink( $fixture_module );
		rmdir( dirname( $fixture_module ) );
		rmdir( $GLOBALS['fixture_theme'] );
	}
);
function get_template_directory() {
	return $GLOBALS['fixture_theme'];
}
$onboarding_loader_pattern = '/foreach\s*\(\s*glob\(\s*get_template_directory\(\)\s*\.\s*\'\/inc\/portal-onboarding-\*\.php\'\s*\).*?\{.*?\}/s';
$functions_source          = file_get_contents( __DIR__ . '/../functions.php' );
$onboarding_loader_count   = preg_match_all( $onboarding_loader_pattern, $functions_source, $onboarding_loader_matches );
check( 'functions.php has exactly one glob loader for inc/portal-onboarding-*.php', 1 === $onboarding_loader_count );
check( 'the switch is not defined before the functions.php loader runs', false === function_exists( 'ihq_portal_onboarding_enabled' ) );
if ( 1 === $onboarding_loader_count ) {
	// Execute the block exactly as written in functions.php.
	eval( $onboarding_loader_matches[0][0] );
}
check( 'running the functions.php loader defines ihq_portal_onboarding_enabled()', true === function_exists( 'ihq_portal_onboarding_enabled' ) );
check( 'running the functions.php loader reads no option', array() === $GLOBALS['option_reads'] );

// No-op when the loader above loaded it; keeps the unit checks below running
// (and reporting) when the loader check failed.
require_once __DIR__ . '/../inc/portal-onboarding-switch.php';

check( 'the option name is the one in the DEPLOYMENT NOTE', 'ihq_portal_onboarding_enabled' === IHQ_PORTAL_ONBOARDING_ENABLED_OPTION );
check( 'the on values are exactly 1, true, yes, on', array( '1', 'true', 'yes', 'on' ) === IHQ_PORTAL_ONBOARDING_ON_VALUES );

// Default after deploy: the option does not exist.
check( 'off when the option has never been set (default after deploy)', false === ihq_portal_onboarding_enabled() );
check( 'reads the option with a false default', array( array( 'ihq_portal_onboarding_enabled', false ) ) === $GLOBALS['option_reads'] );

$on_values = array( '1', 'true', 'TRUE', 'yes', 'on', ' 1 ', 1, true );
foreach ( $on_values as $value ) {
	$GLOBALS['wp_options']['ihq_portal_onboarding_enabled'] = $value;
	check( 'on for ' . var_export( $value, true ), true === ihq_portal_onboarding_enabled() );
}

$off_values = array( '0', '', 'false', 'no', 'off', 'enabled?', '2', 0, 2, false, null, array( '1' ), 1.0 );
foreach ( $off_values as $value ) {
	$GLOBALS['wp_options']['ihq_portal_onboarding_enabled'] = $value;
	check( 'off for ' . var_export( $value, true ), false === ihq_portal_onboarding_enabled() );
}

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
