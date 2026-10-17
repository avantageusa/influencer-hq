<?php
/**
 * Unit test for the installable portal (ENGR-7575, inc/portal-install.php): the web app
 * manifest, its REST route, the head tags on portal pages, and the Save button script.
 * No WordPress bootstrap: the functions it calls are stubbed to record what happens.
 *
 *     docker run -v "$PWD":/t -w /t php:8.2-cli php tests/portal-install.test.php
 *
 * Exit code is non-zero on any failure.
 */
define( 'ABSPATH', '/' );
define( '_S_VERSION', '9.9.9' );
const IHQ_AICOACH_MODULE_ID_PREFIX = '@ihq/aicoach/'; // inc/aicoach-modules.php

$fail = 0;
function check( $label, $cond ) { global $fail; echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n"; if ( ! $cond ) { $fail++; } }

const THEME_URI = 'https://dev.example.test/wp-content/themes/influencer-hq';

$GLOBALS['hooks']      = array();
$GLOBALS['routes']     = array();
$GLOBALS['registered'] = array();
$GLOBALS['enqueued']   = array();
$GLOBALS['portal']     = true;
$GLOBALS['theme_dir']  = '';

function add_action( $hook, $cb, $priority = 10 ) { $GLOBALS['hooks'][] = array( $hook, $cb, $priority ); }
function home_url( $path = '' ) { return 'https://dev.example.test' . $path; }
function rest_url( $path ) { return 'https://dev.example.test/wp-json/' . $path; }
function get_template_directory() { return $GLOBALS['theme_dir']; }
function get_template_directory_uri() { return THEME_URI; }
function esc_url( $url ) { return $url; }
function esc_attr( $text ) { return $text; }
function register_rest_route( $namespace, $route, $args ) { $GLOBALS['routes'][] = array( $namespace, $route, $args ); }
function ihq_is_portal_page_template() { return $GLOBALS['portal']; }
function wp_register_script_module( $id, $src, $deps = array(), $version = false ) { $GLOBALS['registered'][] = array( 'id' => $id, 'src' => $src, 'deps' => $deps, 'version' => $version ); }
function wp_enqueue_script_module( $id ) { $GLOBALS['enqueued'][] = $id; }

class WP_REST_Server {
	const READABLE = 'GET';
}
class WP_REST_Response {
	public $data;
	public $status;
	public $headers = array();
	public function __construct( $data = null, $status = 200 ) { $this->data = $data; $this->status = $status; }
	public function header( $key, $value ) { $this->headers[ $key ] = $value; }
}

require __DIR__ . '/../inc/portal-install.php';

// --- Manifest: what Chrome's installability check needs ---

$manifest = ihq_portal_manifest();
check( 'manifest fields', array( 'name', 'short_name', 'id', 'start_url', 'scope', 'display', 'background_color', 'theme_color', 'icons' ) === array_keys( $manifest ) );
check( 'name and short name', 'Influencer HQ' === $manifest['name'] && 'IHQ' === $manifest['short_name'] );
check( 'the start URL is the Coach page of this site', 'https://dev.example.test/portal/portal-home/' === $manifest['start_url'] );
check( 'the scope is the whole site, so the start URL is inside it', 'https://dev.example.test/' === $manifest['scope'] );
check( 'it opens as an app', 'standalone' === $manifest['display'] );
check( 'black theme and splash', '#000000' === $manifest['theme_color'] && '#000000' === $manifest['background_color'] );
check( '192 and 512 px PNG icons', array(
	array( 'src' => THEME_URI . '/images/pwa/ihq-icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' ),
	array( 'src' => THEME_URI . '/images/pwa/ihq-icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' ),
) === $manifest['icons'] );
foreach ( array( 192, 512 ) as $size ) {
	$path = __DIR__ . '/../images/pwa/ihq-icon-' . $size . '.png';
	$info = file_exists( $path ) ? getimagesize( $path ) : false;
	check( 'the ' . $size . ' px icon file exists and is ' . $size . 'x' . $size . ' PNG', false !== $info && $size === $info[0] && $size === $info[1] && IMAGETYPE_PNG === $info[2] );
}

// --- REST route ---

check( 'the route is registered on rest_api_init', in_array( array( 'rest_api_init', 'ihq_portal_install_register_routes', 10 ), $GLOBALS['hooks'], true ) );
ihq_portal_install_register_routes();
check( 'GET /ihq/v1/portal-manifest, public', array( array( 'ihq/v1', '/portal-manifest', array( 'methods' => 'GET', 'callback' => 'ihq_portal_manifest_handle_get', 'permission_callback' => '__return_true' ) ) ) === $GLOBALS['routes'] );
$response = ihq_portal_manifest_handle_get();
check( 'the route answers 200 with the manifest', 200 === $response->status && $manifest === $response->data );
check( 'as application/manifest+json', array( 'Content-Type' => 'application/manifest+json; charset=utf-8' ) === $response->headers );

// --- Head tags, portal pages only ---

check( 'head tags print early in wp_head', in_array( array( 'wp_head', 'ihq_portal_install_head', 2 ), $GLOBALS['hooks'], true ) );
ob_start();
ihq_portal_install_head();
$head = ob_get_clean();
check( 'portal pages link the manifest and the home-screen tags', '<link rel="manifest" href="https://dev.example.test/wp-json/ihq/v1/portal-manifest">' . "\n"
	. '<meta name="theme-color" content="#000000">' . "\n"
	. '<link rel="apple-touch-icon" href="' . THEME_URI . '/images/pwa/ihq-icon-192.png">' . "\n"
	. '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n"
	. '<meta name="apple-mobile-web-app-title" content="IHQ">' . "\n" === $head );

$GLOBALS['portal'] = false;
ob_start();
ihq_portal_install_head();
check( 'other pages get no manifest', '' === ob_get_clean() );

// --- The Save button script, portal pages only ---

$theme = sys_get_temp_dir() . '/ihq-install-test-' . getmypid();
mkdir( $theme . '/js/aicoach', 0777, true );
file_put_contents( $theme . '/js/aicoach/save-home.js', '//' );
touch( $theme . '/js/aicoach/save-home.js', 1700000001 );
file_put_contents( $theme . '/js/aicoach/save-home-page.js', '//' );
touch( $theme . '/js/aicoach/save-home-page.js', 1700000002 );
$GLOBALS['theme_dir'] = $theme;

check( 'the script is enqueued on wp_enqueue_scripts', in_array( array( 'wp_enqueue_scripts', 'ihq_portal_install_enqueue', 10 ), $GLOBALS['hooks'], true ) );
ihq_portal_install_enqueue();
check( 'nothing is enqueued off the portal', array() === $GLOBALS['registered'] && array() === $GLOBALS['enqueued'] );

$GLOBALS['portal'] = true;
ihq_portal_install_enqueue();
check( 'on portal pages: the helpers and the page module, versioned by file time', array(
	array( 'id' => '@ihq/aicoach/save-home', 'src' => THEME_URI . '/js/aicoach/save-home.js', 'deps' => array(), 'version' => '1700000001' ),
	array( 'id' => '@ihq/aicoach/save-home-page', 'src' => THEME_URI . '/js/aicoach/save-home-page.js', 'deps' => array( '@ihq/aicoach/save-home' ), 'version' => '1700000002' ),
) === $GLOBALS['registered'] );
check( 'only the page module is enqueued', array( '@ihq/aicoach/save-home-page' ) === $GLOBALS['enqueued'] );

array_map( 'unlink', glob( $theme . '/js/aicoach/*.js' ) );
rmdir( $theme . '/js/aicoach' );
rmdir( $theme . '/js' );
rmdir( $theme );

$functions = file_get_contents( __DIR__ . '/../functions.php' );
check( 'functions.php loads the module', false !== strpos( $functions, "require_once get_template_directory() . '/inc/portal-install.php';" ) );

echo "\n" . ( 0 === $fail ? 'All checks passed.' : $fail . ' check(s) FAILED.' ) . "\n";
exit( 0 === $fail ? 0 : 1 );
