import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeDocument, createEvent, build } from './fake-dom.js';
import { SUPPORTED_LOCALES, detectLocale, I18N_TRANSLATIONS } from './locales.js';
import { createEmitter } from './onboarding-events.js';
import { createStringTable, SHELL_STRINGS_EN } from './onboarding-strings.js';
import { createApi } from './onboarding-boot.js';
import { createShell } from './onboarding-shell.js';
import {
	ONBOARDING_TRANSLATIONS,
	SAVE_FAILED_MESSAGE,
	initialLocale,
	fallbackKeys,
	createLanguageSelector,
	installLanguage,
	browserLanguageTags,
	createLanguageSaver,
} from './onboarding-language.js';

const CODES = [ 'en', 'zh', 'yue', 'ja', 'ko', 'th', 'vi' ];
const STATE = { stage: '', answers: {}, platforms: [], handles: {}, language: '', completed: false, completed_at: '' };

// The real shell on the fake DOM, with the frame parts the language list uses.
function setup( { savedLanguage = '', stateLanguage = '', browserTags = [] } = {} ) {
	const document = createFakeDocument();
	const root = build( document, [ 'div', { id: 'ihq-onboarding' },
		[ 'button', { 'data-onb': 'menu-button' } ],
		[ 'nav', { 'data-onb': 'menu', hidden: true }, [ 'a', { 'data-onb-string': 'logOut' }, 'Log out' ] ],
		[ 'button', { 'data-onb': 'sound' } ],
		[ 'button', { 'data-onb': 'globe', 'aria-expanded': 'false', 'data-onb-label': 'languageSelect' } ],
		[ 'ul', { 'data-onb': 'languages', hidden: true } ],
		[ 'p', { 'data-onb': 'subtitle' } ],
		[ 'p', { 'data-onb': 'transcript' } ],
		[ 'div', { 'data-onb': 'content' }, [ 'button', { 'data-onb-string': 'continue' }, 'CONTINUE' ] ],
		[ 'button', { 'data-ihq-save-home': true, 'data-onb-string': 'saveToHomeScreen' }, 'SAVE IHQ TO HOME SCREEN' ],
	] );
	document.body.append( root );
	const events = createEmitter();
	const strings = createStringTable();
	strings.add( 'en', SHELL_STRINGS_EN );
	strings.add( 'en', { continue: 'CONTINUE' } );
	const api = createApi( { config: { state: { ...STATE, language: stateLanguage }, savedLanguage, progressUrl: '/p', nonce: 'n' }, events, strings } );
	const narrator = { play: () => Promise.resolve(), stop: () => {}, setMuted: () => {}, isMuted: () => false };
	api.shell = createShell( { root, document, window: { scrollTo: () => {} }, narrator, strings, getLocale: api.getLocale } );
	events.on( 'locale', () => api.shell.applyStrings() );
	const saves = [];
	const locales = [];
	events.on( 'locale', ( code ) => locales.push( code ) );
	const selector = installLanguage( api, { locales: SUPPORTED_LOCALES, detectLocale, browserTags, saveLanguage: ( code ) => saves.push( code ) } );
	const globe = root.querySelector( '[data-onb="globe"]' );
	const list = root.querySelector( '[data-onb="languages"]' );
	const option = ( code ) => list.querySelector( `button[data-locale="${ code }"]` );
	return { document, root, api, strings, selector, saves, locales, globe, list, option };
}

test( 'translations exist for the six other languages only, and reuse the landing flow approved CONTINUE', () => {
	assert.deepEqual( Object.keys( ONBOARDING_TRANSLATIONS ), [ 'zh', 'yue', 'ja', 'ko', 'th', 'vi' ] );
	for ( const code of Object.keys( ONBOARDING_TRANSLATIONS ) ) {
		assert.equal( ONBOARDING_TRANSLATIONS[ code ].continue, I18N_TRANSLATIONS[ code ].continue, code );
	}
} );

test( 'opening language: the registration language first', () => {
	assert.equal( initialLocale( { saved: 'ko', browserTags: [ 'ja-JP' ], supported: CODES, detectLocale } ), 'ko' );
} );

test( 'opening language: the browser language when nothing is saved and it is supported', () => {
	assert.equal( initialLocale( { saved: '', browserTags: [ 'ja-JP', 'en-US' ], supported: CODES, detectLocale } ), 'ja' );
	assert.equal( initialLocale( { saved: '', browserTags: [ 'zh-HK' ], supported: CODES, detectLocale } ), 'yue' );
	assert.equal( initialLocale( { saved: '', browserTags: [ 'fr-FR', 'vi' ], supported: CODES, detectLocale } ), 'vi' );
} );

test( 'opening language: English when neither is usable', () => {
	assert.equal( initialLocale( { saved: '', browserTags: [ 'fr-FR', 'de' ], supported: CODES, detectLocale } ), 'en' );
	assert.equal( initialLocale( { saved: 'fr', browserTags: [], supported: CODES, detectLocale } ), 'en' );
	assert.equal( initialLocale( { saved: undefined, browserTags: undefined, supported: CODES, detectLocale } ), 'en' );
	assert.equal( initialLocale( { saved: '', browserTags: [ 'x' ], supported: CODES, detectLocale: () => 'klingon' } ), 'en' );
} );

test( 'an unsupported saved language falls through to the browser', () => {
	assert.equal( initialLocale( { saved: 'es', browserTags: [ 'th-TH' ], supported: CODES, detectLocale } ), 'th' );
} );

test( 'onboarding opens in the registration language, then the store language, then the browser', () => {
	assert.equal( setup( { savedLanguage: 'ja', browserTags: [ 'ko' ] } ).api.getLocale(), 'ja' );
	assert.equal( setup( { stateLanguage: 'vi', browserTags: [ 'ko' ] } ).api.getLocale(), 'vi' );
	assert.equal( setup( { browserTags: [ 'ko-KR' ] } ).api.getLocale(), 'ko' );
	assert.equal( setup( { browserTags: [ 'pt-BR' ] } ).api.getLocale(), 'en' );
} );

test( 'the opening language is not saved again (it is not a choice)', () => {
	const { saves } = setup( { browserTags: [ 'ko-KR' ] } );
	assert.deepEqual( saves, [] );
} );

test( 'the globe lists the seven languages, each in its own language, the active one marked', () => {
	const { list, option, root } = setup( { savedLanguage: 'th' } );
	const buttons = list.querySelectorAll( 'button' );
	assert.deepEqual( buttons.map( ( button ) => button.textContent ), [ 'English', '普通话', '廣東話', '日本語', '한국어', 'ไทย', 'Tiếng Việt' ] );
	assert.deepEqual( buttons.map( ( button ) => button.getAttribute( 'lang' ) ), CODES );
	assert.deepEqual( buttons.map( ( button ) => button.getAttribute( 'aria-current' ) ), [ 'false', 'false', 'false', 'false', 'false', 'true', 'false' ] );
	assert.equal( option( 'th' ).getAttribute( 'type' ), 'button' );
	assert.equal( root.getAttribute( 'lang' ), 'th' );
	assert.equal( list.querySelectorAll( 'li' ).length, 7 );
} );

test( 'the globe opens and closes the list; Escape closes it and returns focus', () => {
	const { globe, list, document, selector } = setup();
	assert.equal( list.hidden, true );
	globe.click();
	assert.equal( list.hidden, false );
	assert.equal( globe.getAttribute( 'aria-expanded' ), 'true' );
	assert.equal( selector.isOpen(), true );
	globe.click();
	assert.equal( list.hidden, true );
	assert.equal( globe.getAttribute( 'aria-expanded' ), 'false' );
	selector.open();
	list.dispatchEvent( createEvent( 'keydown', { key: 'Escape' } ) );
	assert.equal( list.hidden, true );
	assert.equal( document.activeElement, globe );
	selector.open();
	list.dispatchEvent( createEvent( 'keydown', { key: 'Tab' } ) );
	assert.equal( list.hidden, false );
	selector.close();
	assert.equal( list.hidden, true );
} );

test( 'Escape with focus still on the globe (just clicked) closes the list', () => {
	const { globe, list, document } = setup();
	globe.click();
	globe.focus();
	const escape = createEvent( 'keydown', { key: 'Escape' } );
	globe.dispatchEvent( escape );
	assert.equal( list.hidden, true );
	assert.equal( globe.getAttribute( 'aria-expanded' ), 'false' );
	assert.equal( document.activeElement, globe );
	assert.equal( escape.propagationStopped, true );
} );

test( 'Escape on the globe with the list closed is left to the shell', () => {
	const { globe, list } = setup();
	const escape = createEvent( 'keydown', { key: 'Escape' } );
	globe.dispatchEvent( escape );
	assert.equal( list.hidden, true );
	assert.equal( escape.propagationStopped, false );
	const tab = createEvent( 'keydown', { key: 'Tab' } );
	globe.click();
	globe.dispatchEvent( tab );
	assert.equal( list.hidden, false );
	assert.equal( tab.propagationStopped, false );
} );

test( 'Escape that closes the list does not reach the shell, so focus stays on the globe', () => {
	const { globe, list, root, document } = setup();
	const menu = root.querySelector( '[data-onb="menu"]' );
	const menuButton = root.querySelector( '[data-onb="menu-button"]' );
	globe.click();
	menuButton.click();
	assert.equal( list.hidden, false );
	assert.equal( menu.hidden, false );
	list.dispatchEvent( createEvent( 'keydown', { key: 'Escape' } ) );
	assert.equal( list.hidden, true );
	assert.equal( document.activeElement, globe );
	assert.equal( menu.hidden, false, 'the shell handler did not run' );
	assert.equal( menuButton.getAttribute( 'aria-expanded' ), 'true' );
} );

test( 'choosing a language switches the onboarding text, marks it, closes the list and saves it', () => {
	const { globe, option, api, root, saves, locales, list } = setup();
	globe.click();
	option( 'ja' ).click();
	assert.equal( api.getLocale(), 'ja' );
	assert.deepEqual( locales, [ 'ja' ] );
	assert.deepEqual( saves, [ 'ja' ] );
	assert.equal( list.hidden, true );
	assert.equal( option( 'ja' ).getAttribute( 'aria-current' ), 'true' );
	assert.equal( option( 'en' ).getAttribute( 'aria-current' ), 'false' );
	assert.equal( root.getAttribute( 'lang' ), 'ja' );
	assert.equal( root.querySelector( '[data-onb-string="continue"]' ).textContent, '続ける' );
	assert.equal( root.querySelector( '[data-ihq-save-home]' ).textContent, 'SAVE IHQ TO HOME SCREEN', 'no approved translation: English' );
} );

test( 'choosing the active language changes nothing and saves nothing', () => {
	const { option, saves, locales } = setup( { savedLanguage: 'ko' } );
	option( 'ko' ).click();
	assert.deepEqual( saves, [] );
	assert.deepEqual( locales, [ 'ko' ], 'only the opening emit' );
} );

test( 'switching keeps the screen content (position and answers)', () => {
	const { option, root } = setup();
	const content = root.querySelector( '[data-onb="content"]' );
	const before = content.children[ 0 ];
	option( 'vi' ).click();
	assert.equal( content.children[ 0 ], before );
	assert.equal( before.textContent, 'TIẾP TỤC' );
} );

// Goes through every English key registered in the table, not a fixed list, so a screen
// table added to setup() (ENGR-7567/7568/7569/7571) is covered without editing this test.
test( 'every registered key is either translated or explicitly falls back to English, per language', () => {
	const { strings } = setup();
	const englishKeys = strings.keys( 'en' );
	const translated = [ 'continue' ];
	const expectedFallback = englishKeys.filter( ( key ) => ! translated.includes( key ) );
	assert.deepEqual( englishKeys, [ ...Object.keys( SHELL_STRINGS_EN ), 'continue' ] );
	for ( const code of [ 'zh', 'yue', 'ja', 'ko', 'th', 'vi' ] ) {
		const missing = fallbackKeys( strings, code );
		assert.deepEqual( missing, expectedFallback, code );
		for ( const key of englishKeys ) {
			const expected = missing.includes( key ) ? strings.t( 'en', key ) : ONBOARDING_TRANSLATIONS[ code ][ key ];
			assert.equal( strings.t( code, key ), expected, `${ code }.${ key }` );
		}
	}
	assert.deepEqual( fallbackKeys( strings, 'en' ), [] );
} );

test( 'a key registered later in English only is reported as falling back in every language', () => {
	const { strings } = setup();
	strings.add( 'en', { laterKey: 'Later' } );
	for ( const code of [ 'zh', 'yue', 'ja', 'ko', 'th', 'vi' ] ) {
		assert.deepEqual( fallbackKeys( strings, code ), [ ...Object.keys( SHELL_STRINGS_EN ), 'laterKey' ], code );
		assert.equal( strings.t( code, 'laterKey' ), 'Later', code );
	}
} );

test( 'without a globe in the frame, the language is still set and no list is built', () => {
	const events = createEmitter();
	const strings = createStringTable();
	const api = createApi( { config: { state: STATE, savedLanguage: 'zh' }, events, strings } );
	api.shell = { root: createFakeDocument().createElement( 'div' ) };
	assert.equal( installLanguage( api, { locales: SUPPORTED_LOCALES, detectLocale, browserTags: [], saveLanguage: () => {} } ), null );
	assert.equal( api.getLocale(), 'zh' );
	const bare = createApi( { config: { state: STATE }, events: createEmitter(), strings: createStringTable() } );
	assert.equal( installLanguage( bare, { locales: SUPPORTED_LOCALES, detectLocale, browserTags: [ 'th' ], saveLanguage: () => {} } ), null );
	assert.equal( bare.getLocale(), 'th' );
} );

test( 'createLanguageSelector works without a shell root', () => {
	const document = createFakeDocument();
	const globe = document.createElement( 'button' );
	const list = document.createElement( 'ul' );
	const api = createApi( { config: { state: STATE }, events: createEmitter(), strings: createStringTable() } );
	const selector = createLanguageSelector( { api, globe, list, locales: SUPPORTED_LOCALES, saveLanguage: () => {} } );
	assert.equal( selector.isOpen(), false );
	assert.equal( list.querySelectorAll( 'button' ).length, 7 );
} );

test( 'browser tags: navigator.languages when it has any', () => {
	assert.deepEqual( browserLanguageTags( { languages: [ 'ja-JP', 'en' ], language: 'ko' } ), [ 'ja-JP', 'en' ] );
} );

test( 'browser tags: navigator.language when languages is missing or empty', () => {
	assert.deepEqual( browserLanguageTags( { language: 'th-TH' } ), [ 'th-TH' ] );
	assert.deepEqual( browserLanguageTags( { languages: [], language: 'vi' } ), [ 'vi' ] );
} );

test( 'browser tags: none when the browser reports none', () => {
	assert.deepEqual( browserLanguageTags( {} ), [] );
	assert.deepEqual( browserLanguageTags( { languages: [], language: '' } ), [] );
	assert.deepEqual( browserLanguageTags( { languages: 'en', language: 7 } ), [] );
} );

test( 'browser tags: a copy, not the navigator array', () => {
	const languages = [ 'ko' ];
	const tags = browserLanguageTags( { languages } );
	assert.notEqual( tags, languages );
	assert.deepEqual( tags, [ 'ko' ] );
} );

// A fetch replacement whose answers the test hands out one at a time.
function deferredFetch() {
	const calls = [];
	const fetchFn = ( url, init ) =>
		new Promise( ( resolve, reject ) => {
			calls.push( { url, init, resolve, reject } );
		} );
	return { calls, fetchFn };
}

function saverWith( fetchFn ) {
	const errors = [];
	const save = createLanguageSaver( { fetchFn, url: '/wp-json/ihq/v1/onboarding/progress', nonce: 'nonce-1', logError: ( ...args ) => errors.push( args ) } );
	return { save, errors };
}

const flush = () => new Promise( ( resolve ) => setImmediate( resolve ) );

test( 'saving sends the choice to the progress route with the nonce, and logs nothing on success', async () => {
	const requests = [];
	const { save, errors } = saverWith( ( url, init ) => {
		requests.push( { url, init } );
		return Promise.resolve( { ok: true, status: 200 } );
	} );
	assert.equal( await save( 'ja' ), undefined );
	assert.deepEqual( requests, [
		{
			url: '/wp-json/ihq/v1/onboarding/progress',
			init: {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': 'nonce-1' },
				body: '{"language":"ja"}',
			},
		},
	] );
	assert.deepEqual( errors, [] );
} );

test( 'a refused save (HTTP error, e.g. an expired nonce) is logged with its status only', async () => {
	const { save, errors } = saverWith( () => Promise.resolve( { ok: false, status: 403, body: 'secret' } ) );
	assert.equal( await save( 'ko' ), undefined );
	assert.deepEqual( errors, [ [ SAVE_FAILED_MESSAGE, 403 ] ] );
} );

test( 'a network failure is logged with the fixed message, not the error object', async () => {
	const { save, errors } = saverWith( () => Promise.reject( new Error( 'Failed to fetch https://x/?token=abc' ) ) );
	assert.equal( await save( 'th' ), undefined );
	assert.deepEqual( errors, [ [ SAVE_FAILED_MESSAGE ] ] );
} );

test( 'a fetch that throws synchronously is logged the same way', async () => {
	const { save, errors } = saverWith( () => {
		throw new TypeError( 'bad' );
	} );
	assert.equal( await save( 'vi' ), undefined );
	assert.deepEqual( errors, [ [ SAVE_FAILED_MESSAGE ] ] );
} );

test( 'quick successive choices are sent one at a time, in the order they were made', async () => {
	const { calls, fetchFn } = deferredFetch();
	const { save, errors } = saverWith( fetchFn );
	const first = save( 'ja' );
	const second = save( 'ko' );
	await flush();
	assert.deepEqual( calls.map( ( call ) => call.init.body ), [ '{"language":"ja"}' ], 'the second waits for the first' );
	calls[ 0 ].resolve( { ok: true, status: 200 } );
	await first;
	await flush();
	assert.deepEqual( calls.map( ( call ) => call.init.body ), [ '{"language":"ja"}', '{"language":"ko"}' ] );
	calls[ 1 ].resolve( { ok: true, status: 200 } );
	await second;
	assert.deepEqual( errors, [] );
} );

test( 'a failed save does not stop the next one', async () => {
	const { calls, fetchFn } = deferredFetch();
	const { save, errors } = saverWith( fetchFn );
	const first = save( 'ja' );
	const second = save( 'zh' );
	await flush();
	calls[ 0 ].reject( new Error( 'offline' ) );
	await first;
	await flush();
	assert.equal( calls.length, 2 );
	assert.equal( calls[ 1 ].init.body, '{"language":"zh"}' );
	calls[ 1 ].resolve( { ok: false, status: 500 } );
	await second;
	assert.deepEqual( errors, [ [ SAVE_FAILED_MESSAGE ], [ SAVE_FAILED_MESSAGE, 500 ] ] );
} );

test( 'the failure message is fixed text', () => {
	assert.equal( SAVE_FAILED_MESSAGE, 'IHQ onboarding: the language choice was not saved.' );
} );
