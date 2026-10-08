<?php
/**
 * ES modules the AI Coach flow imports (ENGR-7066).
 *
 * js/aicoach-coach-flow.js imports the timing/state logic it used to contain
 * from js/aicoach/*.js by bare specifier ("@ihq/aicoach/sequence-hold").
 * Registering them as WordPress script modules (see
 * ihq_aicoach_register_script_modules() for how they get into the import map)
 * makes WordPress print an import map that resolves each specifier to a URL
 * versioned with the file's modification time, so after a deploy a visitor (or a CDN) cannot pair a new
 * entry script with a stale cached module. The entry script itself stays a
 * classic script handle (see ihq_aicoach_enqueue_coach_flow()): it depends on
 * the classic ihq-aicoach-events script and on the AICOACH_SAMI data from
 * wp_localize_script(), neither of which a script module can use.
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Module id prefix; must match the specifiers in js/aicoach-coach-flow.js. */
const IHQ_AICOACH_MODULE_ID_PREFIX = '@ihq/aicoach/';

/**
 * The module files under js/aicoach/ that the flow imports, without extension.
 *
 * @return string[]
 */
function ihq_aicoach_script_module_names() {
	return array( 'sequence-hold', 'stall-watchdog', 'idle-state', 'locales', 'screens' );
}

/**
 * Register the modules the flow imports and enqueue the carrier module that
 * lists them as dependencies.
 *
 * WordPress prints an import map entry only for a module that another enqueued
 * module depends on; merely enqueueing a module prints its <script
 * type="module"> tag but leaves it out of the map (checked on WordPress 7.1.2).
 * So the modules are registered, and js/aicoach/index.js, an empty module, is
 * enqueued with them as its static dependencies. The map WordPress then prints
 * resolves "@ihq/aicoach/sequence-hold" and the others to versioned URLs.
 */
function ihq_aicoach_register_script_modules() {
	$dir = get_template_directory() . '/js/aicoach/';
	$uri = get_template_directory_uri() . '/js/aicoach/';

	$dependency_ids = array();
	foreach ( ihq_aicoach_script_module_names() as $name ) {
		$id      = IHQ_AICOACH_MODULE_ID_PREFIX . $name;
		$path    = $dir . $name . '.js';
		$version = file_exists( $path ) ? (string) filemtime( $path ) : _S_VERSION;

		wp_register_script_module( $id, $uri . $name . '.js', array(), $version );
		$dependency_ids[] = $id;
	}

	$carrier_id      = IHQ_AICOACH_MODULE_ID_PREFIX . 'index';
	$carrier_path    = $dir . 'index.js';
	$carrier_version = file_exists( $carrier_path ) ? (string) filemtime( $carrier_path ) : _S_VERSION;

	wp_register_script_module( $carrier_id, $uri . 'index.js', $dependency_ids, $carrier_version );
	wp_enqueue_script_module( $carrier_id );
}
