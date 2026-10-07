<?php
/**
 * Unit test for inc/aicoach-modules.php (ENGR-7066). No WordPress bootstrap:
 * the script-module functions are stubbed to record what gets registered and
 * enqueued, e.g.
 *
 *     php tests/aicoach-modules.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );
define( '_S_VERSION', '9.9.9' );

$GLOBALS['theme_dir'] = '';
$GLOBALS['registered'] = array();
$GLOBALS['enqueued']   = array();

function get_template_directory() { return $GLOBALS['theme_dir']; }
function get_template_directory_uri() { return 'https://example.test/wp-content/themes/influencer-hq'; }
function wp_register_script_module( $id, $src, $deps = array(), $version = false ) {
	$GLOBALS['registered'][] = array( 'id' => $id, 'src' => $src, 'deps' => $deps, 'version' => $version );
}
function wp_enqueue_script_module( $id ) { $GLOBALS['enqueued'][] = $id; }

require __DIR__ . '/../inc/aicoach-modules.php';

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }
function reset_calls() { $GLOBALS['registered'] = array(); $GLOBALS['enqueued'] = array(); }

// A throwaway theme directory with the module files and known modification times.
$theme = sys_get_temp_dir() . '/ihq-modules-test-' . getmypid();
mkdir( $theme . '/js/aicoach', 0777, true );
$mtimes = array( 'sequence-hold' => 1700000001, 'stall-watchdog' => 1700000002, 'idle-state' => 1700000003, 'index' => 1700000004 );
foreach ( $mtimes as $name => $mtime ) {
	file_put_contents( $theme . '/js/aicoach/' . $name . '.js', '// ' . $name );
	touch( $theme . '/js/aicoach/' . $name . '.js', $mtime );
}
$GLOBALS['theme_dir'] = $theme;

check( 'module names are the three files the flow imports', array( 'sequence-hold', 'stall-watchdog', 'idle-state' ) === ihq_aicoach_script_module_names() );
check( 'id prefix matches the bare specifiers in aicoach-coach-flow.js', '@ihq/aicoach/' === IHQ_AICOACH_MODULE_ID_PREFIX );

// Every specifier the entry script imports from our modules must be registered.
$entry = file_get_contents( __DIR__ . '/../js/aicoach-coach-flow.js' );
preg_match_all( "#from '(@ihq/aicoach/[a-z-]+)'#", $entry, $imports );
$expected_ids = array_map( function ( $n ) { return '@ihq/aicoach/' . $n; }, ihq_aicoach_script_module_names() );
sort( $imports[1] );
$sorted_expected = $expected_ids;
sort( $sorted_expected );
check( 'the entry script imports exactly the registered modules', $imports[1] === $sorted_expected );

reset_calls();
ihq_aicoach_register_script_modules();

check( 'four modules registered', 4 === count( $GLOBALS['registered'] ) );
check( 'the three modules are registered first, with no dependencies', array(
	array( 'id' => '@ihq/aicoach/sequence-hold', 'src' => 'https://example.test/wp-content/themes/influencer-hq/js/aicoach/sequence-hold.js', 'deps' => array(), 'version' => '1700000001' ),
	array( 'id' => '@ihq/aicoach/stall-watchdog', 'src' => 'https://example.test/wp-content/themes/influencer-hq/js/aicoach/stall-watchdog.js', 'deps' => array(), 'version' => '1700000002' ),
	array( 'id' => '@ihq/aicoach/idle-state', 'src' => 'https://example.test/wp-content/themes/influencer-hq/js/aicoach/idle-state.js', 'deps' => array(), 'version' => '1700000003' ),
) === array_slice( $GLOBALS['registered'], 0, 3 ) );
check( 'the carrier module depends on all three, so WordPress puts them in the import map', array(
	'id'      => '@ihq/aicoach/index',
	'src'     => 'https://example.test/wp-content/themes/influencer-hq/js/aicoach/index.js',
	'deps'    => array( '@ihq/aicoach/sequence-hold', '@ihq/aicoach/stall-watchdog', '@ihq/aicoach/idle-state' ),
	'version' => '1700000004',
) === $GLOBALS['registered'][3] );
check( 'only the carrier is enqueued', array( '@ihq/aicoach/index' ) === $GLOBALS['enqueued'] );

// A missing file falls back to the theme version instead of failing.
unlink( $theme . '/js/aicoach/idle-state.js' );
reset_calls();
ihq_aicoach_register_script_modules();
check( 'missing file uses the theme version', '9.9.9' === $GLOBALS['registered'][2]['version'] );
check( 'the other modules keep their file versions', '1700000001' === $GLOBALS['registered'][0]['version'] && '1700000002' === $GLOBALS['registered'][1]['version'] );

// Calling it again registers the same set again (WordPress ignores duplicates by id).
reset_calls();
ihq_aicoach_register_script_modules();
check( 'repeat call is the same four registrations and one enqueue', 4 === count( $GLOBALS['registered'] ) && 1 === count( $GLOBALS['enqueued'] ) );

array_map( 'unlink', glob( $theme . '/js/aicoach/*.js' ) );
rmdir( $theme . '/js/aicoach' ); rmdir( $theme . '/js' ); rmdir( $theme );

echo $fail ? "\n$fail FAILED\n" : "\nALL PASS\n";
exit( $fail ? 1 : 0 );
