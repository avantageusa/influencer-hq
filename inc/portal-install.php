<?php
/**
 * The IHQ portal as an installable web app: SAVE IHQ TO HOME SCREEN (ENGR-7575, PO-3063 T13,
 * PO-3222).
 *
 * - A web app manifest, served by WordPress so its start URL and scope follow the site's own
 *   address on every environment: GET /wp-json/ihq/v1/portal-manifest.
 * - On portal pages, the head links it, with the theme colour and the iOS home-screen icon and
 *   title (printed from wp_head(), which header.php calls).
 * - On portal pages, js/aicoach/save-home-page.js gives every [data-ihq-save-home] button its
 *   behaviour: Android Chrome's install prompt, else a short "Share → Add to Home Screen" sheet,
 *   and the button hides when the portal runs as an installed app or after a dismissal.
 *
 * No service worker and no offline mode (out of scope).
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Colour of the installed app's splash screen and browser chrome: the portal's black. */
const IHQ_PORTAL_INSTALL_THEME_COLOR = '#000000';

/** Icon sizes Chrome's installability check needs (192 and 512 px). */
const IHQ_PORTAL_INSTALL_ICON_SIZES = array( 192, 512 );

/**
 * The manifest.
 *
 * @return array
 */
function ihq_portal_manifest() {
	$icons = array();
	foreach ( IHQ_PORTAL_INSTALL_ICON_SIZES as $size ) {
		$icons[] = array(
			'src'     => get_template_directory_uri() . '/images/pwa/ihq-icon-' . $size . '.png',
			'sizes'   => $size . 'x' . $size,
			'type'    => 'image/png',
			'purpose' => 'any',
		);
	}
	return array(
		'name'             => 'Influencer HQ',
		'short_name'       => 'IHQ',
		'id'               => home_url( '/portal/portal-home/' ),
		'start_url'        => home_url( '/portal/portal-home/' ),
		'scope'            => home_url( '/' ),
		'display'          => 'standalone',
		'background_color' => IHQ_PORTAL_INSTALL_THEME_COLOR,
		'theme_color'      => IHQ_PORTAL_INSTALL_THEME_COLOR,
		'icons'            => $icons,
	);
}

/**
 * GET /ihq/v1/portal-manifest
 *
 * @return WP_REST_Response
 */
function ihq_portal_manifest_handle_get() {
	$response = new WP_REST_Response( ihq_portal_manifest(), 200 );
	$response->header( 'Content-Type', 'application/manifest+json; charset=utf-8' );
	return $response;
}

/**
 * @return void
 */
function ihq_portal_install_register_routes() {
	register_rest_route(
		'ihq/v1',
		'/portal-manifest',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'ihq_portal_manifest_handle_get',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'ihq_portal_install_register_routes' );

/**
 * Whether the current page is a portal page (the templates named page-portal-*.php).
 *
 * @return bool
 */
function ihq_portal_install_on_portal_page() {
	return function_exists( 'ihq_is_portal_page_template' ) && ihq_is_portal_page_template();
}

/**
 * The manifest link and home-screen tags, in the head of portal pages.
 *
 * @return void
 */
function ihq_portal_install_head() {
	if ( ! ihq_portal_install_on_portal_page() ) {
		return;
	}
	printf( '<link rel="manifest" href="%s">' . "\n", esc_url( rest_url( 'ihq/v1/portal-manifest' ) ) );
	printf( '<meta name="theme-color" content="%s">' . "\n", esc_attr( IHQ_PORTAL_INSTALL_THEME_COLOR ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( get_template_directory_uri() . '/images/pwa/ihq-icon-192.png' ) );
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-title" content="IHQ">' . "\n";
}
add_action( 'wp_head', 'ihq_portal_install_head', 2 );

/**
 * The SAVE IHQ TO HOME SCREEN behaviour on portal pages: a script module with its pure helpers
 * as a static dependency, so WordPress puts both in the import map (as in
 * inc/aicoach-appointment-page.php).
 *
 * @return void
 */
function ihq_portal_install_enqueue() {
	if ( ! ihq_portal_install_on_portal_page() ) {
		return;
	}
	$dir = get_template_directory() . '/js/aicoach/';
	$uri = get_template_directory_uri() . '/js/aicoach/';

	$version_of = function ( $name ) use ( $dir ) {
		$path = $dir . $name . '.js';
		return file_exists( $path ) ? (string) filemtime( $path ) : _S_VERSION;
	};

	$helpers_id = IHQ_AICOACH_MODULE_ID_PREFIX . 'save-home';
	$page_id    = IHQ_AICOACH_MODULE_ID_PREFIX . 'save-home-page';
	wp_register_script_module( $helpers_id, $uri . 'save-home.js', array(), $version_of( 'save-home' ) );
	wp_register_script_module( $page_id, $uri . 'save-home-page.js', array( $helpers_id ), $version_of( 'save-home-page' ) );
	wp_enqueue_script_module( $page_id );
}
add_action( 'wp_enqueue_scripts', 'ihq_portal_install_enqueue' );
