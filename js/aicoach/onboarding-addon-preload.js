/**
 * Plug-in that preloads the next onboarding screen's clip and hero image (ENGR-7576). The logic
 * is in onboarding-preload.js; the onboarding entry (ENGR-7565) installs this module.
 */
import { createPreloader } from '@ihq/aicoach/onboarding-preload';

/**
 * The entry calls install( api ) (installModules in onboarding-boot.js); the options exist so a
 * test can pass its fake document.
 *
 * @param {Object}   api                The onboarding API.
 * @param {Object}   [options]
 * @param {Document} [options.document] Defaults to the page's document.
 * @return {Object} The preloader.
 */
export function install( api, options = {} ) {
	const targetDocument = options.document || document;
	return createPreloader( { api, document: targetDocument } );
}
