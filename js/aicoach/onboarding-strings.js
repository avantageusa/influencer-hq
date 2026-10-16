/**
 * The onboarding string table (ENGR-7565; translations and the language selector are
 * ENGR-7572).
 *
 * Every piece of onboarding text is looked up here by key, so it can be translated in one
 * place. Each part of the onboarding (the shell, each group of screens) adds its own English
 * table with add(); translations are added per language the same way. A key missing in a
 * language falls back to English, like t() in locales.js.
 */

/** The shell's own text: the header controls, the menu and the Save button. */
export const SHELL_STRINGS_EN = {
	menuOpen: 'Open menu',
	menuClose: 'Close menu',
	logOut: 'Log out',
	soundMute: 'Mute Sami',
	soundUnmute: 'Unmute Sami',
	languageSelect: 'Select language',
	saveToHomeScreen: 'SAVE IHQ TO HOME SCREEN',
};

/** The language every key exists in. */
export const FALLBACK_LOCALE = 'en';

/**
 * @return {Object} { add, t, keys, has }.
 */
export function createStringTable() {
	const tables = new Map();

	/**
	 * Adds (or overrides) strings for a language.
	 *
	 * @param {string}                 locale
	 * @param {Object<string, string>} strings
	 */
	function add( locale, strings ) {
		const existing = tables.get( locale ) || {};
		tables.set( locale, { ...existing, ...strings } );
	}

	/**
	 * @param {string} locale
	 * @param {string} key
	 * @return {string} The text in that language, else in English, else ''.
	 */
	function t( locale, key ) {
		const own = tables.get( locale ) || {};
		if ( own[ key ] ) {
			return own[ key ];
		}
		const fallback = tables.get( FALLBACK_LOCALE ) || {};
		return fallback[ key ] || '';
	}

	/**
	 * @param {string} [locale] Defaults to English, the full set.
	 * @return {string[]} The keys that language has.
	 */
	function keys( locale = FALLBACK_LOCALE ) {
		return Object.keys( tables.get( locale ) || {} );
	}

	/**
	 * Whether a language has its own text for a key (no fallback).
	 *
	 * @param {string} locale
	 * @param {string} key
	 * @return {boolean} True when it does.
	 */
	function has( locale, key ) {
		const own = tables.get( locale ) || {};
		return Boolean( own[ key ] );
	}

	return { add, t, keys, has };
}
