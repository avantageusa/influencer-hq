/**
 * Plug-in that preloads the next onboarding screen's clip and hero image (ENGR-7576). The logic
 * is in onboarding-preload.js; the onboarding entry (ENGR-7565) installs this module.
 */
import { createPreloader } from '@ihq/aicoach/onboarding-preload';

/**
 * @param {Object} api The onboarding API.
 */
export function install( api ) {
	createPreloader( { api, document } );
}
