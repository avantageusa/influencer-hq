/**
 * Whether the portal onboarding can open on this visit (ENGR-7566).
 *
 * The engine passes over a screen whose module is not installed, so that screens still
 * waiting on their task (draft posts and post action, ENGR-7570) do not block the flow. That
 * must not happen to a screen whose module the server listed and that then failed to load:
 * the flow is forward-only, so the Influencer would never see that screen. When a listed
 * screen module fails, or no screen is installed at all, the layer is closed for this visit
 * instead. Nothing is saved, and the next visit tries again from the saved stage.
 */

/** Ids of plug-in modules that register screens (onboarding-screens-*.js). Add-ons are optional. */
export const SCREEN_MODULE_PATTERN = /(^|\/)onboarding-screens-[a-z0-9-]+$/;

function reportError( error ) {
	// eslint-disable-next-line no-console -- an onboarding that cannot open must be visible, not swallowed.
	console.error( error );
}

/**
 * The listed screen modules that did not install.
 *
 * @param {Object}   options
 * @param {string[]} options.listed    Module ids from config.modules.
 * @param {string[]} options.installed The ids installModules() reported as installed.
 * @return {string[]} The failed screen module ids, in listed order.
 */
export function failedScreenModules( { listed, installed } ) {
	const screenModules = ( Array.isArray( listed ) ? listed : [] ).filter( ( id ) => SCREEN_MODULE_PATTERN.test( id ) );
	return screenModules.filter( ( id ) => ! installed.includes( id ) );
}

/**
 * Opens the onboarding at the saved stage, or closes the layer when it cannot be shown.
 *
 * @param {Object}   deps
 * @param {Object}   deps.engine    From createEngine().
 * @param {Object}   deps.shell     From createShell().
 * @param {string[]} deps.listed    Module ids from config.modules.
 * @param {string[]} deps.installed The ids installModules() reported as installed.
 * @param {Function} deps.ready     Tells the modules the onboarding is ready; called only when it opens.
 * @param {Function} [deps.onError] Told why the layer was closed. Defaults to console.error.
 * @return {string|null} The stage shown, or null when the layer was closed.
 */
export function launch( { engine, shell, listed, installed, ready, onError = reportError } ) {
	const failed = failedScreenModules( { listed, installed } );
	if ( failed.length > 0 ) {
		onError( new Error( 'Onboarding: screen modules failed to load (' + failed.join( ', ' ) + '); closed until the next visit' ) );
		shell.close();
		return null;
	}
	ready();
	const first = engine.start();
	if ( null === first ) {
		onError( new Error( 'Onboarding: no screen is installed; closed until the next visit' ) );
		shell.close();
		return null;
	}
	return first;
}
