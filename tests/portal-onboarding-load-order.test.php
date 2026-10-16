<?php
/**
 * Load-order test for the onboarding modules: runs functions.php's own glob block, which loads
 * every inc/portal-onboarding-*.php by file name, against the real inc/ folder. A module that uses
 * another module's constant or function at load time, before that module is loaded, is a fatal
 * error here, as it is on a real site (ENGR-7573: braze.php hooked the store's action constants
 * before progress.php had loaded).
 *
 * At load time the modules only call add_action() and add_filter(), so those are the only stubs.
 *
 *     php tests/portal-onboarding-load-order.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

$GLOBALS['hooks'] = array();
function add_action( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][] = $hook; }
function add_filter( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][] = $hook; }
function get_template_directory() { return dirname( __DIR__ ); }

$loader_pattern   = '/foreach\s*\(\s*glob\(\s*get_template_directory\(\)\s*\.\s*\'\/inc\/portal-onboarding-\*\.php\'\s*\).*?\{.*?\}/s';
$functions_source = file_get_contents( __DIR__ . '/../functions.php' );
$loader_count     = preg_match_all( $loader_pattern, $functions_source, $loader_matches );
check( 'functions.php has exactly one onboarding glob loader', 1 === $loader_count );

$modules = glob( dirname( __DIR__ ) . '/inc/portal-onboarding-*.php' );
check( 'there are onboarding modules to load', count( $modules ) > 0 );

if ( 1 === $loader_count ) {
	// Execute the block exactly as written in functions.php: same files, same order.
	eval( $loader_matches[0][0] );
}

$loaded = array_map( 'realpath', get_included_files() );
foreach ( $modules as $module ) {
	check( 'loaded in glob order without a fatal error: ' . basename( $module ), in_array( realpath( $module ), $loaded, true ) );
}
check( 'every hook name is a non-empty string (no constant resolved to nothing)', array() === array_filter( $GLOBALS['hooks'], function ( $hook ) { return ! is_string( $hook ) || '' === $hook; } ) );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
