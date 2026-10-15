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

require __DIR__ . '/../inc/portal-onboarding-switch.php';

check( 'the option name is the one in the DEPLOYMENT NOTE', 'ihq_portal_onboarding_enabled' === IHQ_PORTAL_ONBOARDING_ENABLED_OPTION );

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

// The theme loads the module.
$functions = file_get_contents( __DIR__ . '/../functions.php' );
check( 'functions.php loads every inc/portal-onboarding-*.php module, this one included', false !== strpos( $functions, "glob( get_template_directory() . '/inc/portal-onboarding-*.php' )" ) );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
