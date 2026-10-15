<?php
/**
 * Unit test for the portal onboarding frame (ENGR-7565, inc/portal-onboarding-shell.php
 * and template-parts/portal-onboarding.php). No WordPress bootstrap: the script-module,
 * template and URL functions are stubbed to record what happens.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/portal-onboarding-shell.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );
define( '_S_VERSION', '9.9.9' );
const IHQ_AICOACH_MODULE_ID_PREFIX = '@ihq/aicoach/'; // inc/aicoach-modules.php

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

$GLOBALS['theme_dir']  = '';
$GLOBALS['registered'] = array();
$GLOBALS['enqueued']   = array();
$GLOBALS['hooks']      = array();
$GLOBALS['parts']      = array();
$GLOBALS['filters']    = array();

const THEME_URI = 'https://example.test/wp-content/themes/influencer-hq';

function get_template_directory() { return $GLOBALS['theme_dir']; }
function get_template_directory_uri() { return THEME_URI; }
function wp_register_script_module( $id, $src, $deps = array(), $version = false ) {
	$GLOBALS['registered'][] = array( 'id' => $id, 'src' => $src, 'deps' => $deps, 'version' => $version );
}
function wp_enqueue_script_module( $id ) { $GLOBALS['enqueued'][] = $id; }
function add_action( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['hooks'][] = array( $hook, $cb ); }
function apply_filters( $hook, $value ) { return isset( $GLOBALS['filters'][ $hook ] ) ? $GLOBALS['filters'][ $hook ] : $value; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function wp_logout_url( $redirect = '' ) { return 'https://example.test/wp-login.php?action=logout&redirect_to=' . rawurlencode( $redirect ); }
function add_query_arg( $key, $value, $url ) { return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . $key . '=' . $value; }
function get_template_part( $slug, $name = null, $args = array() ) { $GLOBALS['parts'][] = array( $slug, $name, $args ); }

require __DIR__ . '/../inc/portal-onboarding-shell.php';

// A throwaway theme with module files of known modification times.
$theme = sys_get_temp_dir() . '/ihq-onboarding-shell-test-' . getmypid();
mkdir( $theme . '/js/aicoach', 0777, true );
mkdir( $theme . '/css', 0777, true );
mkdir( $theme . '/images/aicoach', 0777, true );
$files = array(
	'commentary'                       => 1700000001,
	'stall-watchdog'                   => 1700000002,
	'onboarding-main'                  => 1700000003,
	'onboarding-narrator'              => 1700000004,
	'onboarding-boot'                  => 1700000005,
	'onboarding-screens-teaching'      => 1700000006,
	'onboarding-addon-language'        => 1700000007,
	'onboarding-narrator.test'         => 1700000008,
	'locales'                          => 1700000009,
);
foreach ( $files as $name => $mtime ) {
	file_put_contents( $theme . '/js/aicoach/' . $name . '.js', '// ' . $name );
	touch( $theme . '/js/aicoach/' . $name . '.js', $mtime );
}
file_put_contents( $theme . '/css/portal-onboarding.css', '/* css */' );
touch( $theme . '/css/portal-onboarding.css', 1700000010 );
file_put_contents( $theme . '/css/portal-onboarding-teaching.css', '/* teaching */' );
touch( $theme . '/css/portal-onboarding-teaching.css', 1700000012 );
file_put_contents( $theme . '/css/portal-onboarding-questions.css', '/* questions */' );
touch( $theme . '/css/portal-onboarding-questions.css', 1700000013 );
file_put_contents( $theme . '/images/aicoach/coach-portrait-v2.webp', 'img' );
touch( $theme . '/images/aicoach/coach-portrait-v2.webp', 1700000011 );
$GLOBALS['theme_dir'] = $theme;

// --- Module discovery ---

check( 'onboarding-*.js modules are found by name, sorted, tests left out, other modules ignored', array( 'onboarding-addon-language', 'onboarding-boot', 'onboarding-main', 'onboarding-narrator', 'onboarding-screens-teaching' ) === ihq_portal_onboarding_module_names( $theme . '/js/aicoach/' ) );
check( 'an empty directory has no modules', array() === ihq_portal_onboarding_module_names( $theme . '/nowhere/' ) );

check( 'screens and add-ons are plug-ins', ihq_portal_onboarding_is_plugin_module( 'onboarding-screens-teaching' ) && ihq_portal_onboarding_is_plugin_module( 'onboarding-addon-preload' ) );
check( 'core modules are not plug-ins', ! ihq_portal_onboarding_is_plugin_module( 'onboarding-main' ) && ! ihq_portal_onboarding_is_plugin_module( 'onboarding-narrator' ) && ! ihq_portal_onboarding_is_plugin_module( 'onboarding-screens' ) && ! ihq_portal_onboarding_is_plugin_module( 'xonboarding-screens-a' ) );

// --- Registration ---

$plugins = ihq_portal_onboarding_enqueue_modules();
$uri     = THEME_URI . '/js/aicoach/';
check( 'plug-in ids are returned in load order', array( '@ihq/aicoach/onboarding-addon-language', '@ihq/aicoach/onboarding-screens-teaching' ) === $plugins );
check( 'every module is registered, versioned by file time, the entry last with core modules static and plug-ins dynamic', array(
	array( 'id' => '@ihq/aicoach/commentary', 'src' => $uri . 'commentary.js', 'deps' => array(), 'version' => '1700000001' ),
	array( 'id' => '@ihq/aicoach/stall-watchdog', 'src' => $uri . 'stall-watchdog.js', 'deps' => array(), 'version' => '1700000002' ),
	array( 'id' => '@ihq/aicoach/onboarding-addon-language', 'src' => $uri . 'onboarding-addon-language.js', 'deps' => array(), 'version' => '1700000007' ),
	array( 'id' => '@ihq/aicoach/onboarding-boot', 'src' => $uri . 'onboarding-boot.js', 'deps' => array(), 'version' => '1700000005' ),
	array( 'id' => '@ihq/aicoach/onboarding-narrator', 'src' => $uri . 'onboarding-narrator.js', 'deps' => array(), 'version' => '1700000004' ),
	array( 'id' => '@ihq/aicoach/onboarding-screens-teaching', 'src' => $uri . 'onboarding-screens-teaching.js', 'deps' => array(), 'version' => '1700000006' ),
	array(
		'id'      => '@ihq/aicoach/onboarding-main',
		'src'     => $uri . 'onboarding-main.js',
		'deps'    => array(
			'@ihq/aicoach/commentary',
			'@ihq/aicoach/stall-watchdog',
			'@ihq/aicoach/onboarding-boot',
			'@ihq/aicoach/onboarding-narrator',
			array( 'id' => '@ihq/aicoach/onboarding-addon-language', 'import' => 'dynamic' ),
			array( 'id' => '@ihq/aicoach/onboarding-screens-teaching', 'import' => 'dynamic' ),
		),
		'version' => '1700000003',
	),
) === $GLOBALS['registered'] );
check( 'only the entry is enqueued', array( '@ihq/aicoach/onboarding-main' ) === $GLOBALS['enqueued'] );

// --- Config ---

$context = array( 'state' => array( 'stage' => '' ), 'progressUrl' => 'p', 'completeUrl' => 'c', 'nonce' => 'n' );
check( 'the config adds modules, empty clips and images, and the log-out URL back to the Coach page', array(
	'state'       => array( 'stage' => '' ),
	'progressUrl' => 'p',
	'completeUrl' => 'c',
	'nonce'       => 'n',
	'modules'     => array( 'x' ),
	'clips'       => array(),
	'images'      => array(),
	'logoutUrl'   => 'https://example.test/wp-login.php?action=logout&redirect_to=' . rawurlencode( 'https://example.test/portal/portal-home/' ),
) === ihq_portal_onboarding_shell_config( $context, array( 'x' ) ) );
$GLOBALS['filters']['ihq_portal_onboarding_clips']  = array( 'equity' => array( 'en' => 'e.mp4' ) );
$GLOBALS['filters']['ihq_portal_onboarding_images'] = array( 'equity' => 'e.webp' );
$with_assets = ihq_portal_onboarding_shell_config( $context, array() );
check( 'clips and images come from their filters', array( 'equity' => array( 'en' => 'e.mp4' ) ) === $with_assets['clips'] && array( 'equity' => 'e.webp' ) === $with_assets['images'] );
$GLOBALS['filters'] = array();

check( 'the portrait falls back to the landing flow file, versioned', THEME_URI . '/images/aicoach/coach-portrait-v2.webp?v=1700000011' === ihq_portal_onboarding_portrait_url() );

// --- Rendering ---

check( 'the shell renders on the gate\'s action', in_array( array( 'ihq_portal_onboarding_render_layer', 'ihq_portal_onboarding_render_shell' ), $GLOBALS['hooks'], true ) );

$GLOBALS['registered'] = array();
$GLOBALS['enqueued']   = array();
ihq_portal_onboarding_render_shell( 'not a context' );
ihq_portal_onboarding_render_shell( array( 'nonce' => 'n' ) );
check( 'a malformed context renders nothing and enqueues nothing', array() === $GLOBALS['parts'] && array() === $GLOBALS['enqueued'] );

ihq_portal_onboarding_render_shell( $context );
check( 'a context renders the template part once', 1 === count( $GLOBALS['parts'] ) && 'template-parts/portal-onboarding' === $GLOBALS['parts'][0][0] && null === $GLOBALS['parts'][0][1] );
$args = $GLOBALS['parts'][0][2];
check( 'the template gets the config, the portrait and the stylesheets', array( 'config', 'portrait_url', 'css_urls' ) === array_keys( $args ) && $args['config'] === ihq_portal_onboarding_shell_config( $context, $plugins ) );
check( 'stylesheets: the frame first, then each screen group in name order, versioned', array(
	THEME_URI . '/css/portal-onboarding.css?ver=1700000010',
	THEME_URI . '/css/portal-onboarding-questions.css?ver=1700000013',
	THEME_URI . '/css/portal-onboarding-teaching.css?ver=1700000012',
) === $args['css_urls'] );
check( 'rendering enqueues the entry', array( '@ihq/aicoach/onboarding-main' ) === $GLOBALS['enqueued'] );

array_map( 'unlink', glob( $theme . '/js/aicoach/*.js' ) );
array_map( 'unlink', glob( $theme . '/css/*.css' ) );
unlink( $theme . '/images/aicoach/coach-portrait-v2.webp' );
rmdir( $theme . '/js/aicoach' );
rmdir( $theme . '/js' );
rmdir( $theme . '/css' );
rmdir( $theme . '/images/aicoach' );
rmdir( $theme . '/images' );
rmdir( $theme );

// --- The template: header parts, no Login/Help/account control, config script ---

$template = file_get_contents( __DIR__ . '/../template-parts/portal-onboarding.php' );
foreach ( array( 'menu-button', 'menu', 'sound', 'globe', 'video', 'subtitle', 'transcript', 'content' ) as $part ) {
	check( 'template has data-onb="' . $part . '"', false !== strpos( $template, 'data-onb="' . $part . '"' ) );
}
check( 'template has the Save button for ENGR-7575', false !== strpos( $template, 'data-ihq-save-home' ) );
check( 'template carries the Log out link in the menu', false !== strpos( $template, '$ihq_onb_logout' ) );
check( 'template has no Login, Help or account control', 0 === preg_match( '/header-login-link|portal-header-auth-trigger|header-help-btn|header-logout-btn/', $template ) );
check( 'template prints the config as JSON safe inside a script tag', false !== strpos( $template, 'wp_json_encode( $ihq_onb_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES )' ) );

$css = file_get_contents( __DIR__ . '/../css/portal-onboarding.css' );
preg_match_all( '/@media[^{]+/', $css, $queries );
check( 'the frame stylesheet uses only the existing 768/1025 px queries', array( '@media (min-width: 768px) ', '@media (min-width: 1025px) ' ) === $queries[0] );
foreach ( glob( __DIR__ . '/../css/portal-onboarding-*.css' ) ?: array() as $group_css ) {
	preg_match_all( '/@media\s*\(([^)]*)\)/', file_get_contents( $group_css ), $group_queries );
	check( basename( $group_css ) . ' uses no width query other than the portal 768/1025 px ones', array() === array_diff( $group_queries[1], array( 'min-width: 768px', 'min-width: 1025px', 'prefers-reduced-motion: reduce' ) ) );
}

$functions = file_get_contents( __DIR__ . '/../functions.php' );
check( 'functions.php loads every inc/portal-onboarding-*.php module', false !== strpos( $functions, "glob( get_template_directory() . '/inc/portal-onboarding-*.php' )" ) );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
