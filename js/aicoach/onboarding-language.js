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

/** The key that closes the language list. */
const ESCAPE_KEY = 'Escape';

/**
 * What the console shows when a language choice was not saved. A fixed message plus the HTTP
 * status at most: the error object or the response can carry request details.
 */
export const SAVE_FAILED_MESSAGE = 'IHQ onboarding: the language choice was not saved.';

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

	// Escape closes the list whether focus is on the globe (just clicked) or inside the list.
	// It stops there, so the shell's own Escape handler on the root does not also move focus.
	function closeOnEscape( event ) {
		if ( ESCAPE_KEY !== event.key || list.hidden ) {
			return;
		}
		event.stopPropagation();
		setOpen( false );
		globe.focus();
	}

	globe.addEventListener( 'click', () => setOpen( list.hidden ) );
	globe.addEventListener( 'keydown', closeOnEscape );
	list.addEventListener( 'keydown', closeOnEscape );
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
 * The browser's preferred language tags. Pure.
 *
 * @param {Object}   navigator           window.navigator, or a replacement in tests.
 * @param {string[]} [navigator.languages] The preferred tags, most preferred first.
 * @param {string}   [navigator.language]  The UI language, for browsers without `languages`.
 * @return {string[]} The tags; empty when the browser reports none.
 */
export function browserLanguageTags( navigator ) {
	if ( Array.isArray( navigator.languages ) && navigator.languages.length > 0 ) {
		return [ ...navigator.languages ];
	}
	if ( 'string' === typeof navigator.language && '' !== navigator.language ) {
		return [ navigator.language ];
	}
	return [];
}

/**
 * Saves language choices to the account store, one request at a time in the order they were
 * made, so a quick second choice cannot be overwritten by a slower first request finishing
 * last. A refused save (an HTTP error such as an expired nonce) and a network failure are both
 * logged; the switch on screen has already happened either way.
 *
 * @param {Object}   options
 * @param {Function} options.fetchFn  fetch, or a replacement in tests.
 * @param {string}   options.url      The progress route (POST /ihq/v1/onboarding/progress).
 * @param {string}   options.nonce    The REST nonce.
 * @param {Function} options.logError console.error, or a replacement in tests.
 * @return {Function} ( code ) => Promise that settles (never rejects) once that choice is sent.
 */
export function createLanguageSaver( { fetchFn, url, nonce, logError } ) {
	let queue = Promise.resolve();

	async function send( code ) {
		let response;
		try {
			response = await fetchFn( url, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
				body: JSON.stringify( { language: code } ),
			} );
		} catch ( error ) {
			logError( SAVE_FAILED_MESSAGE );
			return;
		}
		if ( ! response.ok ) {
			logError( SAVE_FAILED_MESSAGE, response.status );
		}
	}

	return ( code ) => {
		queue = queue.then( () => send( code ) );
		return queue;
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
