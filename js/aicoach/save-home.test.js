import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeDocument, createEvent, build } from './fake-dom.js';
import {
	DISMISSED_KEY,
	HIDDEN_CLASS,
	BUTTON_SELECTOR,
	SHEET_TEXT,
	isStandalone,
	isIos,
	chooseAction,
	createSaveHome,
} from './save-home.js';

const ANDROID_CHROME = 'Mozilla/5.0 (Linux; Android 14; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36';
const IPHONE_SAFARI = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';
const IPAD_AS_MAC = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Safari/605.1.15';
const DESKTOP_FIREFOX = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:131.0) Gecko/20100101 Firefox/131.0';

function fakeStorage( initial = {} ) {
	const data = { ...initial };
	return {
		data,
		getItem: ( key ) => ( key in data ? data[ key ] : null ),
		setItem: ( key, value ) => {
			data[ key ] = String( value );
		},
	};
}

function throwingStorage() {
	return {
		getItem: () => {
			throw new Error( 'SecurityError' );
		},
		setItem: () => {
			throw new Error( 'QuotaExceededError' );
		},
	};
}

// A page with two Save buttons (the frame's and a screen's) on the fake DOM.
function setup( { userAgent = ANDROID_CHROME, standalone = false, iosStandalone = undefined, maxTouchPoints = 0, storage = fakeStorage() } = {} ) {
	const document = createFakeDocument();
	// Clicks bubble to <html>; the document listens there.
	document.addEventListener = ( type, listener ) => document.documentElement.addEventListener( type, listener );
	const page = build( document, [ 'div', {},
		[ 'button', { 'data-ihq-save-home': true, id: 'frame-save' }, [ 'span', {}, 'SAVE IHQ TO HOME SCREEN' ] ],
		[ 'button', { 'data-ihq-save-home': true, id: 'closing-save' }, 'SAVE IHQ TO HOME SCREEN' ],
		[ 'button', { id: 'other' }, 'Other' ],
	] );
	document.body.append( page );
	const windowTarget = new EventTarget();
	const window = {
		navigator: { userAgent, standalone: iosStandalone, maxTouchPoints },
		matchMedia: ( query ) => ( { matches: '(display-mode: standalone)' === query && standalone } ),
		addEventListener: windowTarget.addEventListener.bind( windowTarget ),
		fire: ( event ) => windowTarget.dispatchEvent( event ),
	};
	const saveHome = createSaveHome( { document, window, storage } );
	return { document, window, saveHome, storage, button: ( id ) => document.getElementById( id ) };
}

function installPrompt( outcome ) {
	const event = new Event( 'beforeinstallprompt' );
	event.calls = [];
	event.preventDefault = () => event.calls.push( 'preventDefault' );
	event.prompt = () => event.calls.push( 'prompt' );
	event.userChoice = Promise.resolve( { outcome } );
	return event;
}

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

test( 'constants', () => {
	assert.equal( DISMISSED_KEY, 'ihq_save_home_dismissed' );
	assert.equal( HIDDEN_CLASS, 'ihq-save-home-hidden' );
	assert.equal( BUTTON_SELECTOR, '[data-ihq-save-home]' );
	assert.deepEqual( Object.keys( SHEET_TEXT ), [ 'title', 'ios', 'other', 'close' ] );
} );

test( 'isStandalone: display-mode standalone, or iOS navigator.standalone', () => {
	assert.equal( isStandalone( { matchMedia: () => ( { matches: true } ), navigator: {} } ), true );
	assert.equal( isStandalone( { matchMedia: () => ( { matches: false } ), navigator: { standalone: true } } ), true );
	assert.equal( isStandalone( { matchMedia: () => ( { matches: false } ), navigator: { standalone: false } } ), false );
	assert.equal( isStandalone( { matchMedia: undefined, navigator: undefined } ), false );
} );

test( 'isIos: iPhone, iPad, iPod and iPadOS posing as a Mac with touch', () => {
	assert.equal( isIos( { userAgent: IPHONE_SAFARI } ), true );
	assert.equal( isIos( { userAgent: 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)' } ), true );
	assert.equal( isIos( { userAgent: IPAD_AS_MAC, maxTouchPoints: 5 } ), true );
	assert.equal( isIos( { userAgent: IPAD_AS_MAC, maxTouchPoints: 0 } ), false );
	assert.equal( isIos( { userAgent: ANDROID_CHROME } ), false );
	assert.equal( isIos( {} ), false );
	assert.equal( isIos( undefined ), false );
} );

test( 'chooseAction: the prompt when offered, else the sheet for the platform', () => {
	assert.equal( chooseAction( { hasPrompt: true, ios: false } ), 'prompt' );
	assert.equal( chooseAction( { hasPrompt: true, ios: true } ), 'prompt' );
	assert.equal( chooseAction( { hasPrompt: false, ios: true } ), 'sheet-ios' );
	assert.equal( chooseAction( { hasPrompt: false, ios: false } ), 'sheet-other' );
} );

test( 'Android Chrome: the button opens the native install prompt', async () => {
	const { window, button, saveHome, document } = setup();
	const prompt = installPrompt( 'accepted' );
	window.fire( prompt );
	assert.deepEqual( prompt.calls, [ 'preventDefault' ] );
	button( 'frame-save' ).click();
	await flush();
	assert.deepEqual( prompt.calls, [ 'preventDefault', 'prompt' ] );
	assert.equal( saveHome.sheet(), null );
	assert.equal( document.documentElement.classList.contains( HIDDEN_CLASS ), true, 'installed: the buttons hide' );
} );

test( 'a click on the label inside the button counts', async () => {
	const { window, button } = setup();
	const prompt = installPrompt( 'accepted' );
	window.fire( prompt );
	button( 'frame-save' ).querySelector( 'span' ).click();
	await flush();
	assert.deepEqual( prompt.calls, [ 'preventDefault', 'prompt' ] );
} );

test( 'dismissing the install prompt hides the buttons and is remembered on the device', async () => {
	const { window, button, saveHome, storage } = setup();
	window.fire( installPrompt( 'dismissed' ) );
	button( 'closing-save' ).click();
	await flush();
	assert.equal( saveHome.isHidden(), true );
	assert.deepEqual( storage.data, { ihq_save_home_dismissed: '1' } );
} );

test( 'the prompt is used once; a second click shows the sheet', async () => {
	const { window, saveHome } = setup();
	window.fire( installPrompt( 'dismissed' ) );
	assert.equal( await saveHome.handleClick(), 'prompt' );
	assert.equal( await saveHome.handleClick(), 'sheet-other' );
} );

test( 'iOS Safari: the button opens the Share → Add to Home Screen sheet', async () => {
	const { button, saveHome, document } = setup( { userAgent: IPHONE_SAFARI } );
	button( 'frame-save' ).click();
	await flush();
	const sheet = saveHome.sheet();
	assert.ok( sheet );
	assert.equal( sheet.getAttribute( 'role' ), 'dialog' );
	assert.equal( sheet.getAttribute( 'aria-modal' ), 'true' );
	assert.equal( sheet.getAttribute( 'aria-labelledby' ), 'ihq-save-home-title' );
	assert.equal( sheet.querySelector( 'h2' ).textContent, 'Save IHQ to your Home Screen' );
	assert.equal( sheet.querySelector( 'p' ).textContent, 'Tap the Share button, then Add to Home Screen.' );
	assert.equal( document.activeElement, sheet.querySelector( 'button' ) );
	assert.equal( document.body.contains( sheet ), true );
	assert.equal( saveHome.isHidden(), false, 'opening the sheet does not hide the buttons yet' );
} );

test( 'other browsers without a prompt get the menu instructions', async () => {
	const { saveHome } = setup( { userAgent: DESKTOP_FIREFOX } );
	assert.equal( await saveHome.handleClick(), 'sheet-other' );
	assert.equal( saveHome.sheet().querySelector( 'p' ).textContent, "Open your browser's menu, then choose Add to Home Screen (or Install app)." );
} );

test( 'closing the sheet removes it, hides the buttons and remembers the dismissal', async () => {
	const { saveHome, storage, document } = setup( { userAgent: IPHONE_SAFARI } );
	await saveHome.handleClick();
	const sheet = saveHome.sheet();
	sheet.querySelector( 'button' ).click();
	assert.equal( saveHome.sheet(), null );
	assert.equal( document.body.contains( sheet ), false );
	assert.equal( saveHome.isHidden(), true );
	assert.deepEqual( storage.data, { ihq_save_home_dismissed: '1' } );
} );

test( 'Escape closes the sheet the same way', async () => {
	const { saveHome } = setup( { userAgent: IPHONE_SAFARI } );
	await saveHome.handleClick();
	saveHome.sheet().dispatchEvent( createEvent( 'keydown', { key: 'Escape' } ) );
	assert.equal( saveHome.sheet(), null );
	assert.equal( saveHome.isHidden(), true );
} );

test( 'other keys leave the sheet open; a second click does not open a second sheet', async () => {
	const { saveHome, document } = setup( { userAgent: IPHONE_SAFARI } );
	await saveHome.handleClick();
	saveHome.sheet().dispatchEvent( createEvent( 'keydown', { key: 'Enter' } ) );
	await saveHome.handleClick();
	assert.equal( document.body.querySelectorAll( '.ihq-save-home-sheet' ).length, 1 );
} );

test( 'in standalone mode (installed app) the buttons are hidden from the start', () => {
	assert.equal( setup( { standalone: true } ).saveHome.isHidden(), true );
	assert.equal( setup( { userAgent: IPHONE_SAFARI, iosStandalone: true } ).saveHome.isHidden(), true );
	assert.equal( setup().saveHome.isHidden(), false );
} );

test( 'after a dismissal on this device the buttons stay hidden on the next page', () => {
	assert.equal( setup( { storage: fakeStorage( { ihq_save_home_dismissed: '1' } ) } ).saveHome.isHidden(), true );
	assert.equal( setup( { storage: fakeStorage( { ihq_save_home_dismissed: '0' } ) } ).saveHome.isHidden(), false );
} );

test( 'blocked storage: nothing throws, the buttons hide for this page view', async () => {
	const { saveHome } = setup( { userAgent: IPHONE_SAFARI, storage: throwingStorage() } );
	assert.equal( saveHome.isHidden(), false );
	await saveHome.handleClick();
	saveHome.sheet().querySelector( 'button' ).click();
	assert.equal( saveHome.isHidden(), true );
} );

test( 'the appinstalled event hides the buttons', () => {
	const { window, saveHome } = setup();
	window.fire( new Event( 'appinstalled' ) );
	assert.equal( saveHome.isHidden(), true );
} );

test( 'clicks elsewhere do nothing', async () => {
	const { button, saveHome } = setup( { userAgent: IPHONE_SAFARI } );
	button( 'other' ).click();
	await flush();
	assert.equal( saveHome.sheet(), null );
} );

test( 'the page wiring hides every Save button with one class, so buttons added later are covered', async () => {
	const { readFileSync } = await import( 'node:fs' );
	const page = readFileSync( new URL( './save-home-page.js', import.meta.url ), 'utf8' );
	assert.ok( page.includes( '.${ HIDDEN_CLASS } [data-ihq-save-home] { display: none !important; }' ) );
	assert.ok( page.includes( "import { createSaveHome, HIDDEN_CLASS } from '@ihq/aicoach/save-home';" ) );
} );
