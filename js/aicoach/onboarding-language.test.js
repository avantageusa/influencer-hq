import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeDocument, createEvent, build } from './fake-dom.js';
import { SUPPORTED_LOCALES, detectLocale, I18N_TRANSLATIONS } from './locales.js';
import { createEmitter } from './onboarding-events.js';
import { createStringTable, SHELL_STRINGS_EN } from './onboarding-strings.js';
import { createApi } from './onboarding-boot.js';
import { createShell } from './onboarding-shell.js';
import { ONBOARDING_TRANSLATIONS, initialLocale, fallbackKeys, createLanguageSelector, installLanguage } from './onboarding-language.js';

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
	assert.deepEqual( locales.filter( ( code ) => 'ko' !== code ), [] );
} );

test( 'switching keeps the screen content (position and answers)', () => {
	const { option, root } = setup();
	const content = root.querySelector( '[data-onb="content"]' );
	const before = content.children[ 0 ];
	option( 'vi' ).click();
	assert.equal( content.children[ 0 ], before );
	assert.equal( before.textContent, 'TIẾP TỤC' );
} );

test( 'every shell key is either translated or explicitly falls back to English, per language', () => {
	const { strings } = setup();
	for ( const code of [ 'zh', 'yue', 'ja', 'ko', 'th', 'vi' ] ) {
		const missing = fallbackKeys( strings, code );
		assert.deepEqual( missing, Object.keys( SHELL_STRINGS_EN ), code );
		missing.forEach( ( key ) => assert.equal( strings.t( code, key ), SHELL_STRINGS_EN[ key ], `${ code }.${ key }` ) );
		assert.equal( strings.has( code, 'continue' ), true, code );
	}
	assert.deepEqual( fallbackKeys( strings, 'en' ), [] );
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
