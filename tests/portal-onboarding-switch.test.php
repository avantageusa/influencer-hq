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

// The theme loads the module. Runs before anything else requires the module,
// so a missing or broken line in functions.php cannot be masked. functions.php
// as a whole needs a full WordPress bootstrap, so the test runs only its
// require_once statement for this module, with get_template_directory()
// stubbed to the theme root.
function get_template_directory() {
	return dirname( __DIR__ );
}
$switch_require_pattern = '/require_once\s+get_template_directory\(\)\s*\.\s*\'\/inc\/portal-onboarding-switch\.php\'\s*;/';
$functions_source       = file_get_contents( __DIR__ . '/../functions.php' );
$switch_require_count   = preg_match_all( $switch_require_pattern, $functions_source, $switch_require_matches );
check( 'functions.php has exactly one require_once for inc/portal-onboarding-switch.php', 1 === $switch_require_count );
check( 'the switch is not defined before the functions.php line runs', false === function_exists( 'ihq_portal_onboarding_enabled' ) );
if ( 1 === $switch_require_count ) {
	// Execute the statement exactly as written in functions.php.
	eval( $switch_require_matches[0][0] );
}
check( 'running the functions.php line defines ihq_portal_onboarding_enabled()', true === function_exists( 'ihq_portal_onboarding_enabled' ) );
check( 'running the functions.php line reads no option', array() === $GLOBALS['option_reads'] );

// No-op when the line above loaded it; keeps the unit checks below running
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
