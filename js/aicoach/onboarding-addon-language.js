/**
 * Plug-in that gives the onboarding its seven languages and the globe's language list
 * (ENGR-7572). The logic is in onboarding-language.js; this file only connects it to the page:
 * the landing flow's language data (locales.js), the browser's languages, and saving a choice
 * to the account store (POST /ihq/v1/onboarding/progress, which also updates
 * `_ihq_aicoach_language`).
 */
import { SUPPORTED_LOCALES, detectLocale } from '@ihq/aicoach/locales';
import { installLanguage } from '@ihq/aicoach/onboarding-language';

/**
 * @param {Object} api The onboarding API.
 */
export function install( api ) {
	installLanguage( api, {
		locales: SUPPORTED_LOCALES,
		detectLocale,
		browserTags: window.navigator.languages || [ window.navigator.language ],
		saveLanguage: ( code ) => {
			window.fetch( api.config.progressUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': api.config.nonce },
				body: JSON.stringify( { language: code } ),
			} ).catch( ( error ) => {
				// eslint-disable-next-line no-console -- a failed save must be visible; the switch itself already happened.
				console.error( error );
			} );
		},
	} );
}
