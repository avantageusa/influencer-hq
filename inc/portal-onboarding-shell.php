<?php
/**
 * The portal onboarding frame (ENGR-7565, PO-3063 T3): header, Sami banner, commentary box,
 * content area and the SAVE IHQ TO HOME SCREEN button, as a layer over the Coach page.
 *
 * Rendered when the entry gate (ENGR-7564, inc/portal-onboarding-gate.php) fires
 * `ihq_portal_onboarding_render_layer` for an Influencer who should be onboarded. Nothing
 * here runs otherwise.
 *
 * The browser side is js/aicoach/onboarding-main.js and the onboarding-*.js modules next to
 * it, registered as WordPress script modules so the import map versions each file with its
 * modification time (the arrangement of inc/aicoach-modules.php). Plug-in modules
 * (onboarding-screens-*.js, onboarding-addon-*.js) are found by file name, so a task that
 * adds screens adds a file rather than editing a list here.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Id of the entry module. */
const IHQ_PORTAL_ONBOARDING_ENTRY_MODULE = '@ihq/aicoach/onboarding-main';

/** Landing-flow modules the onboarding reuses unchanged (js/aicoach/). */
const IHQ_PORTAL_ONBOARDING_SHARED_MODULES = array( 'commentary', 'stall-watchdog', 'locales' );

/** File names of plug-in modules, which the entry imports on demand. */
const IHQ_PORTAL_ONBOARDING_PLUGIN_PATTERN = '/^onboarding-(screens|addon)-[a-z0-9-]+$/';

/**
 * Module names (file names without .js) of every onboarding-*.js under js/aicoach/, tests
 * excluded, sorted so the order is the same on every server.
 *
 * @param string $dir Directory to look in, with a trailing slash.
 * @return string[]
 */
function ihq_portal_onboarding_module_names( $dir ) {
	$names = array();
	foreach ( glob( $dir . 'onboarding-*.js' ) ?: array() as $path ) {
		$name = basename( $path, '.js' );
		if ( '.test' === substr( $name, -5 ) ) {
			continue;
		}
		$names[] = $name;
	}
	sort( $names );
	return $names;
}

/**
 * Whether a module is a plug-in (screens or an add-on) rather than part of the core.
 *
 * @param string $name
 * @return bool
 */
function ihq_portal_onboarding_is_plugin_module( $name ) {
	return 1 === preg_match( IHQ_PORTAL_ONBOARDING_PLUGIN_PATTERN, $name );
}

/**
 * Register every module and enqueue the entry. Core and shared modules are static
 * dependencies of the entry; plug-ins are dynamic ones (WordPress puts both in the import
 * map). Classic themes print the map and the module tags in the footer, so this works when
 * called while the page body is being rendered.
 *
 * @return string[] Ids of the plug-in modules, in load order.
 */
function ihq_portal_onboarding_enqueue_modules() {
	$dir = get_template_directory() . '/js/aicoach/';
	$uri = get_template_directory_uri() . '/js/aicoach/';

	$version_of = function ( $name ) use ( $dir ) {
		$path = $dir . $name . '.js';
		return file_exists( $path ) ? (string) filemtime( $path ) : _S_VERSION;
	};

	$static_deps = array();
	$plugin_ids  = array();
	$names       = array_merge( IHQ_PORTAL_ONBOARDING_SHARED_MODULES, ihq_portal_onboarding_module_names( $dir ) );
	foreach ( $names as $name ) {
		$id = IHQ_AICOACH_MODULE_ID_PREFIX . $name;
		if ( IHQ_PORTAL_ONBOARDING_ENTRY_MODULE === $id ) {
			continue;
		}
		wp_register_script_module( $id, $uri . $name . '.js', array(), $version_of( $name ) );
		if ( ihq_portal_onboarding_is_plugin_module( $name ) ) {
			$plugin_ids[] = $id;
			continue;
		}
		$static_deps[] = $id;
	}

	$deps = $static_deps;
	foreach ( $plugin_ids as $plugin_id ) {
		$deps[] = array(
			'id'     => $plugin_id,
			'import' => 'dynamic',
		);
	}
	wp_register_script_module( IHQ_PORTAL_ONBOARDING_ENTRY_MODULE, $uri . 'onboarding-main.js', $deps, $version_of( 'onboarding-main' ) );
	wp_enqueue_script_module( IHQ_PORTAL_ONBOARDING_ENTRY_MODULE );
	return $plugin_ids;
}

/**
 * The config the browser reads from #ihq-onboarding-config: the gate's context plus what
 * the shell adds.
 *
 * @param array    $context    From ihq_portal_onboarding_layer_context() (ENGR-7564).
 * @param string[] $plugin_ids
 * @return array
 */
function ihq_portal_onboarding_shell_config( array $context, array $plugin_ids ) {
	return array_merge(
		$context,
		array(
			'modules'   => $plugin_ids,
			/**
			 * Sami's pre-rendered clips per screen and language: [ stage => [ locale => url ] ].
			 * Empty until the onboarding clips are rendered (PO-3063 T12); every screen then
			 * plays its subtitles at reading pace.
			 */
			'clips'     => apply_filters( 'ihq_portal_onboarding_clips', array() ),
			/** Hero images per screen: [ stage => url ]. */
			'images'    => apply_filters( 'ihq_portal_onboarding_images', array() ),
			'logoutUrl' => wp_logout_url( home_url( '/portal/portal-home/' ) ),
			/** The registration language (ENGR-7572): onboarding opens in it when it is one of the seven. */
			'savedLanguage' => (string) get_user_meta( get_current_user_id(), '_ihq_aicoach_language', true ),
		)
	);
}

/**
 * The URL of Sami's portrait, the one the landing flow shows (it follows the avatar version).
 *
 * @return string
 */
function ihq_portal_onboarding_portrait_url() {
	$file = function_exists( 'ihq_aicoach_portrait_file' ) ? ihq_aicoach_portrait_file() : 'coach-portrait-v2.webp';
	$path = get_template_directory() . '/images/aicoach/' . $file;
	$url  = get_template_directory_uri() . '/images/aicoach/' . $file;
	return file_exists( $path ) ? add_query_arg( 'v', filemtime( $path ), $url ) : $url;
}

/**
 * Renders the layer. Hooked on the gate's action.
 *
 * @param array $context From ihq_portal_onboarding_layer_context().
 * @return void
 */
function ihq_portal_onboarding_render_shell( $context ) {
	if ( ! is_array( $context ) || ! isset( $context['state'] ) ) {
		return;
	}
	$plugin_ids = ihq_portal_onboarding_enqueue_modules();
	$css_path   = get_template_directory() . '/css/portal-onboarding.css';
	get_template_part(
		'template-parts/portal-onboarding',
		null,
		array(
			'config'       => ihq_portal_onboarding_shell_config( $context, $plugin_ids ),
			'portrait_url' => ihq_portal_onboarding_portrait_url(),
			'css_url'      => add_query_arg( 'ver', file_exists( $css_path ) ? filemtime( $css_path ) : _S_VERSION, get_template_directory_uri() . '/css/portal-onboarding.css' ),
		)
	);
}
add_action( 'ihq_portal_onboarding_render_layer', 'ihq_portal_onboarding_render_shell' );
