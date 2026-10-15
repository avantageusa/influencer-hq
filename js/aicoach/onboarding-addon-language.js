/**
 * Plug-in that gives the onboarding its seven languages and the globe's language list
 * (ENGR-7572). The logic is in onboarding-language.js; this file only connects it to the page:
 * the landing flow's language data (locales.js), the browser's languages, and saving a choice
 * to the account store (POST /ihq/v1/onboarding/progress, which also updates
 * `_ihq_aicoach_language`).
 */
import { SUPPORTED_LOCALES, detectLocale } from '@ihq/aicoach/locales';
import { installLanguage, browserLanguageTags, createLanguageSaver } from '@ihq/aicoach/onboarding-language';

/**
 * @param {Object} api The onboarding API.
 */
export function install( api ) {
	installLanguage( api, {
		locales: SUPPORTED_LOCALES,
		detectLocale,
		browserTags: browserLanguageTags( window.navigator ),
		saveLanguage: createLanguageSaver( {
			fetchFn: ( ...args ) => window.fetch( ...args ),
			url: api.config.progressUrl,
			nonce: api.config.nonce,
			// eslint-disable-next-line no-console -- a failed save must be visible; the switch itself already happened.
			logError: ( ...args ) => console.error( ...args ),
		} ),
	} );
}
