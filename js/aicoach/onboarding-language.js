/**
 * The onboarding's seven languages: which one it opens in, the translations, and the language
 * list under the header globe (ENGR-7572, PO-3063 T10, PO-3202).
 *
 * Opening language: the registration language saved in `_ihq_aicoach_language`, else the
 * browser's language when it is one of the seven (detectLocale() from locales.js), else
 * English.
 *
 * Choosing a language in the globe list switches every onboarding string (the 'locale' event:
 * the shell re-labels the frame, screens re-label themselves, the engine restarts the screen's
 * clip in the new language), keeps the screen and the answers, and saves the choice so the
 * next visit opens in it.
 *
 * Everything is passed in, so it is tested with the fake DOM; the plug-in
 * onboarding-addon-language.js wires it to the page.
 */

/**
 * Translations of onboarding strings, by language and string-table key. A key missing here
 * falls back to English (onboarding-strings.js) and is listed in the PR that adds the copy.
 *
 * Only approved translations go here, as in locales.js: none of the onboarding copy has an
 * approved translation yet, so every language falls back to English except where the landing
 * flow already has approved text for the same words (locales.js I18N_TRANSLATIONS, key
 * `continue`, reused for the engine's CONTINUE button).
 */
export const ONBOARDING_TRANSLATIONS = Object.freeze( {
	zh: Object.freeze( { continue: '继续' } ),
	yue: Object.freeze( { continue: '繼續' } ),
	ja: Object.freeze( { continue: '続ける' } ),
	ko: Object.freeze( { continue: '계속' } ),
	th: Object.freeze( { continue: 'ดำเนินการต่อ' } ),
	vi: Object.freeze( { continue: 'TIẾP TỤC' } ),
} );

/**
 * The language onboarding opens in.
 *
 * @param {Object}   options
 * @param {string}   options.saved        The registration language (`_ihq_aicoach_language`), or ''.
 * @param {string[]} options.browserTags  navigator.languages.
 * @param {string[]} options.supported    The seven codes.
 * @param {Function} options.detectLocale From locales.js: browser tags → a supported code, else 'en'.
 * @return {string} A supported code.
 */
export function initialLocale( { saved, browserTags, supported, detectLocale } ) {
	if ( 'string' === typeof saved && supported.includes( saved ) ) {
		return saved;
	}
	const detected = detectLocale( Array.isArray( browserTags ) ? browserTags : [] );
	return supported.includes( detected ) ? detected : 'en';
}

/**
 * Every key a language lacks (so falls back to English). Pure.
 *
 * @param {Object} strings A string table.
 * @param {string} locale  A language code.
 * @return {string[]} The English keys with no own text in that language.
 */
export function fallbackKeys( strings, locale ) {
	return strings.keys().filter( ( key ) => ! strings.has( locale, key ) );
}

/**
 * The globe's language list.
 *
 * @param {Object}   deps
 * @param {Object}   deps.api          The onboarding API (getLocale, setLocale, events, shell.root).
 * @param {Element}  deps.globe        The globe button.
 * @param {Element}  deps.list         The (empty) list under it.
 * @param {Object[]} deps.locales      SUPPORTED_LOCALES from locales.js: { code, nativeLabel }.
 * @param {Function} deps.saveLanguage ( code ) => void; stores the choice for the next visit.
 * @return {Object} { open, close, isOpen }.
 */
export function createLanguageSelector( { api, globe, list, locales, saveLanguage } ) {
	const document = list.ownerDocument;
	const buttons = locales.map( ( { code, nativeLabel } ) => {
		const item = document.createElement( 'li' );
		const button = document.createElement( 'button' );
		button.setAttribute( 'type', 'button' );
		button.setAttribute( 'lang', code );
		button.setAttribute( 'data-locale', code );
		button.className = 'ihq-onb__language-option';
		button.textContent = nativeLabel;
		button.addEventListener( 'click', () => choose( code ) );
		item.append( button );
		list.append( item );
		return button;
	} );

	function mark() {
		const active = api.getLocale();
		buttons.forEach( ( button ) => {
			const isActive = button.getAttribute( 'data-locale' ) === active;
			button.setAttribute( 'aria-current', isActive ? 'true' : 'false' );
		} );
		if ( api.shell && api.shell.root ) {
			api.shell.root.setAttribute( 'lang', active );
		}
	}

	function setOpen( open ) {
		list.hidden = ! open;
		globe.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
	}

	function choose( code ) {
		setOpen( false );
		globe.focus();
		if ( code === api.getLocale() ) {
			return;
		}
		api.setLocale( code );
		saveLanguage( code );
	}

	globe.addEventListener( 'click', () => setOpen( list.hidden ) );
	list.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' === event.key ) {
			setOpen( false );
			globe.focus();
		}
	} );
	api.events.on( 'locale', mark );
	mark();
	setOpen( false );

	return {
		open: () => setOpen( true ),
		close: () => setOpen( false ),
		isOpen: () => ! list.hidden,
	};
}

/**
 * Sets the opening language, adds the translations and builds the globe list.
 *
 * @param {Object}   api               The onboarding API.
 * @param {Object}   deps              What it needs from the page.
 * @param {Object[]} deps.locales      SUPPORTED_LOCALES.
 * @param {Function} deps.detectLocale From locales.js.
 * @param {string[]} deps.browserTags  navigator.languages.
 * @param {Function} deps.saveLanguage ( code ) => void.
 * @return {Object|null} The selector, or null when the frame has no globe.
 */
export function installLanguage( api, { locales, detectLocale, browserTags, saveLanguage } ) {
	Object.keys( ONBOARDING_TRANSLATIONS ).forEach( ( code ) => api.strings.add( code, ONBOARDING_TRANSLATIONS[ code ] ) );
	const supported = locales.map( ( locale ) => locale.code );
	const saved = api.config.savedLanguage || api.state.language || '';
	api.setLocale( initialLocale( { saved, browserTags, supported, detectLocale } ) );

	const root = api.shell && api.shell.root;
	const globe = root ? root.querySelector( '[data-onb="globe"]' ) : null;
	const list = root ? root.querySelector( '[data-onb="languages"]' ) : null;
	if ( ! globe || ! list ) {
		return null;
	}
	return createLanguageSelector( { api, globe, list, locales, saveLanguage } );
}
