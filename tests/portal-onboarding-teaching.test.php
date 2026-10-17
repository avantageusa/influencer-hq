<?php
/**
 * Unit test for the teaching screens' hero images (ENGR-7567, inc/portal-onboarding-teaching.php).
 * No WordPress bootstrap: the theme and filter functions are stubbed.
 *
 *     php tests/portal-onboarding-teaching.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

$GLOBALS['theme_dir'] = '';
$GLOBALS['filters']   = array();

const THEME_URI  = 'https://example.test/wp-content/themes/influencer-hq';
const DESIGN_URI = THEME_URI . '/images/aicoach/design/';

function get_template_directory() { return $GLOBALS['theme_dir']; }
function get_template_directory_uri() { return THEME_URI; }
function add_filter( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['filters'][] = array( $hook, $cb, $priority, $args ); }
function add_query_arg( $key, $value, $url ) { return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . $key . '=' . $value; }

require __DIR__ . '/../inc/portal-onboarding-teaching.php';

check( 'the images are added on the shell\'s images filter, once', array( array( 'ihq_portal_onboarding_images', 'ihq_portal_onboarding_teaching_images', 10, 1 ) ) === $GLOBALS['filters'] );
check( 'heroes are mapped for screens 1 and 2 only', array(
	'game_of_kings' => 'onboarding-game-of-kings.webp',
	'modern_era'    => 'onboarding-modern-era.webp',
) === IHQ_PORTAL_ONBOARDING_TEACHING_HEROES );

// The real theme: both files ship.
$GLOBALS['theme_dir'] = realpath( __DIR__ . '/..' );
foreach ( IHQ_PORTAL_ONBOARDING_TEACHING_HEROES as $stage => $file ) {
	check( $file . ' ships in images/aicoach/design/', is_file( $GLOBALS['theme_dir'] . '/images/aicoach/design/' . $file ) );
}

// A throwaway theme with hero files of known modification times.
$theme = sys_get_temp_dir() . '/ihq-onboarding-teaching-test-' . getmypid();
mkdir( $theme . '/images/aicoach/design', 0777, true );
file_put_contents( $theme . '/images/aicoach/design/onboarding-game-of-kings.webp', 'img' );
touch( $theme . '/images/aicoach/design/onboarding-game-of-kings.webp', 1700000021 );
file_put_contents( $theme . '/images/aicoach/design/onboarding-modern-era.webp', 'img' );
touch( $theme . '/images/aicoach/design/onboarding-modern-era.webp', 1700000022 );
$GLOBALS['theme_dir'] = $theme;

$images = ihq_portal_onboarding_teaching_images( array() );
check( 'screens 1 and 2 get their hero, versioned by file time', array(
	'game_of_kings' => DESIGN_URI . 'onboarding-game-of-kings.webp?v=1700000021',
	'modern_era'    => DESIGN_URI . 'onboarding-modern-era.webp?v=1700000022',
) === $images );
check( 'screens 7, 8 and 9 get no hero (the design reuses screen 2\'s picture there)', array() === array_intersect_key( $images, array_flip( array( 'world_championship', 'world_tour', 'classic' ) ) ) );

check( 'other screen groups\' images are kept', array(
	'name'          => 'https://cdn.test/name.webp',
	'game_of_kings' => DESIGN_URI . 'onboarding-game-of-kings.webp?v=1700000021',
	'modern_era'    => DESIGN_URI . 'onboarding-modern-era.webp?v=1700000022',
) === ihq_portal_onboarding_teaching_images( array( 'name' => 'https://cdn.test/name.webp' ) ) );
check( 'a value that is not an array is replaced, not merged into', array(
	'game_of_kings' => DESIGN_URI . 'onboarding-game-of-kings.webp?v=1700000021',
	'modern_era'    => DESIGN_URI . 'onboarding-modern-era.webp?v=1700000022',
) === ihq_portal_onboarding_teaching_images( 'not an array' ) );

unlink( $theme . '/images/aicoach/design/onboarding-modern-era.webp' );
check( 'a missing file leaves its screen without a hero', array(
	'game_of_kings' => DESIGN_URI . 'onboarding-game-of-kings.webp?v=1700000021',
) === ihq_portal_onboarding_teaching_images( array() ) );

unlink( $theme . '/images/aicoach/design/onboarding-game-of-kings.webp' );
check( 'with no files, the images pass through unchanged', array( 'name' => 'n.webp' ) === ihq_portal_onboarding_teaching_images( array( 'name' => 'n.webp' ) ) );

rmdir( $theme . '/images/aicoach/design' );
rmdir( $theme . '/images/aicoach' );
rmdir( $theme . '/images' );
rmdir( $theme );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
