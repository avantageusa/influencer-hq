import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeDocument, createEvent, build } from './fake-dom.js';
import {
	DISMISSED_KEY,
	INSTALLED_KEY,
	HIDDEN_CLASS,
	BUTTON_SELECTOR,
	SAVE_HOME_ACTION,
	SHEET_TEXT,
	SHEET_STYLE,
	isStandalone,
	isIos,
	chooseAction,
	createSaveHome,
	mountSaveHome,
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
	// Events bubble to <html>; the document listens there.
	document.addEventListener = ( type, listener ) => document.documentElement.addEventListener( type, listener );
	document.removeEventListener = ( type, listener ) => document.documentElement.removeEventListener( type, listener );
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

// A captured prompt the browser refuses (Chrome throws InvalidStateError for a stale event).
function staleInstallPrompt() {
	const event = installPrompt( 'dismissed' );
	event.prompt = () => {
		event.calls.push( 'prompt' );
		throw new Error( 'InvalidStateError' );
	};
	return event;
}

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

test( 'constants', () => {
	assert.equal( DISMISSED_KEY, 'ihq_save_home_dismissed' );
	assert.equal( INSTALLED_KEY, 'ihq_save_home_installed' );
	assert.deepEqual( SAVE_HOME_ACTION, { prompt: 'prompt', sheetIos: 'sheet-ios', sheetOther: 'sheet-other' } );
	assert.equal( Object.isFrozen( SAVE_HOME_ACTION ), true );
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
	assert.equal( chooseAction( { hasPrompt: true, ios: false } ), SAVE_HOME_ACTION.prompt );
	assert.equal( chooseAction( { hasPrompt: true, ios: true } ), SAVE_HOME_ACTION.prompt );
	assert.equal( chooseAction( { hasPrompt: false, ios: true } ), SAVE_HOME_ACTION.sheetIos );
	assert.equal( chooseAction( { hasPrompt: false, ios: false } ), SAVE_HOME_ACTION.sheetOther );
} );

test( 'Android Chrome: the button opens the native install prompt; installing is remembered on the device', async () => {
	const { window, button, saveHome, document, storage } = setup();
	const prompt = installPrompt( 'accepted' );
	window.fire( prompt );
	assert.deepEqual( prompt.calls, [ 'preventDefault' ] );
	button( 'frame-save' ).click();
	await flush();
	assert.deepEqual( prompt.calls, [ 'preventDefault', 'prompt' ] );
	assert.equal( saveHome.sheet(), null );
	assert.equal( document.documentElement.classList.contains( HIDDEN_CLASS ), true, 'installed: the buttons hide' );
	assert.deepEqual( storage.data, { ihq_save_home_installed: '1' } );
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
	assert.equal( await saveHome.handleClick(), SAVE_HOME_ACTION.prompt );
	assert.equal( await saveHome.handleClick(), SAVE_HOME_ACTION.sheetOther );
} );

test( 'a prompt the browser refuses falls back to the sheet, and nothing is remembered', async () => {
	const { window, saveHome, storage } = setup();
	const prompt = staleInstallPrompt();
	window.fire( prompt );
	assert.equal( await saveHome.handleClick(), SAVE_HOME_ACTION.sheetOther );
	assert.deepEqual( prompt.calls, [ 'preventDefault', 'prompt' ] );
	assert.equal( saveHome.sheet().querySelector( 'p' ).textContent, SHEET_TEXT.other );
	assert.equal( saveHome.isHidden(), false );
	assert.deepEqual( storage.data, {} );
} );

test( 'a refused prompt on iPadOS falls back to the Share sheet', async () => {
	const { window, saveHome } = setup( { userAgent: IPAD_AS_MAC, maxTouchPoints: 5 } );
	window.fire( staleInstallPrompt() );
	assert.equal( await saveHome.handleClick(), SAVE_HOME_ACTION.sheetIos );
	assert.equal( saveHome.sheet().querySelector( 'p' ).textContent, SHEET_TEXT.ios );
} );

test( 'a userChoice that rejects falls back to the sheet', async () => {
	const { window, saveHome, storage } = setup();
	const prompt = installPrompt( 'dismissed' );
	prompt.userChoice = Promise.reject( new Error( 'AbortError' ) );
	window.fire( prompt );
	assert.equal( await saveHome.handleClick(), SAVE_HOME_ACTION.sheetOther );
	assert.equal( saveHome.isHidden(), false );
	assert.deepEqual( storage.data, {} );
} );

test( 'a click on the button with a refused prompt opens the sheet instead of failing silently', async () => {
	const { window, button, saveHome } = setup();
	window.fire( staleInstallPrompt() );
	button( 'frame-save' ).click();
	await flush();
	assert.equal( saveHome.sheet().querySelector( 'p' ).textContent, SHEET_TEXT.other );
} );

test( 'a prompt with no userChoice result counts as a dismissal', async () => {
	const { window, saveHome, storage } = setup();
	const prompt = installPrompt( 'dismissed' );
	prompt.userChoice = Promise.resolve( undefined );
	window.fire( prompt );
	assert.equal( await saveHome.handleClick(), SAVE_HOME_ACTION.prompt );
	assert.equal( saveHome.isHidden(), true );
	assert.deepEqual( storage.data, { ihq_save_home_dismissed: '1' } );
} );

test( 'iOS Safari: the button opens the Share → Add to Home Screen sheet', async () => {
	const { button, saveHome, document } = setup( { userAgent: IPHONE_SAFARI } );
	button( 'frame-save' ).click();
	await flush();
	const sheet = saveHome.sheet();
	assert.ok( sheet );
	assert.equal( sheet.getAttribute( 'role' ), 'dialog' );
	assert.equal( sheet.getAttribute( 'aria-modal' ), null, 'not modal: no backdrop, no focus trap' );
	assert.equal( sheet.getAttribute( 'aria-labelledby' ), 'ihq-save-home-title' );
	assert.equal( sheet.querySelector( 'h2' ).textContent, 'Save IHQ to your Home Screen' );
	assert.equal( sheet.querySelector( 'p' ).textContent, 'Tap the Share button, then Add to Home Screen.' );
	assert.equal( document.activeElement, sheet.querySelector( 'button' ) );
	assert.equal( document.body.contains( sheet ), true );
	assert.equal( saveHome.isHidden(), false, 'opening the sheet does not hide the buttons yet' );
} );

test( 'other browsers without a prompt get the menu instructions', async () => {
	const { saveHome } = setup( { userAgent: DESKTOP_FIREFOX } );
	assert.equal( await saveHome.handleClick(), SAVE_HOME_ACTION.sheetOther );
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
	const { saveHome, storage } = setup( { userAgent: IPHONE_SAFARI } );
	await saveHome.handleClick();
	saveHome.sheet().dispatchEvent( createEvent( 'keydown', { key: 'Escape' } ) );
	assert.equal( saveHome.sheet(), null );
	assert.equal( saveHome.isHidden(), true );
	assert.deepEqual( storage.data, { ihq_save_home_dismissed: '1' } );
} );

test( 'Escape closes the sheet while focus is elsewhere on the page', async () => {
	const { saveHome, button, document } = setup( { userAgent: IPHONE_SAFARI } );
	await saveHome.handleClick();
	const sheet = saveHome.sheet();
	button( 'other' ).focus();
	button( 'other' ).dispatchEvent( createEvent( 'keydown', { key: 'Escape' } ) );
	assert.equal( saveHome.sheet(), null );
	assert.equal( document.body.contains( sheet ), false );
	assert.equal( saveHome.isHidden(), true );
} );

test( 'once the sheet is closed, Escape no longer does anything', async () => {
	const { saveHome, storage, document } = setup( { userAgent: IPHONE_SAFARI } );
	await saveHome.handleClick();
	saveHome.sheet().querySelector( 'button' ).click();
	delete storage.data.ihq_save_home_dismissed;
	document.documentElement.dispatchEvent( createEvent( 'keydown', { key: 'Escape' } ) );
	assert.deepEqual( storage.data, {} );
} );

test( 'before the sheet opens, Escape does nothing', () => {
	const { saveHome, storage, document } = setup( { userAgent: IPHONE_SAFARI } );
	document.documentElement.dispatchEvent( createEvent( 'keydown', { key: 'Escape' } ) );
	assert.equal( saveHome.isHidden(), false );
	assert.deepEqual( storage.data, {} );
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

test( 'after an install on this device the buttons stay hidden in a later browser tab', () => {
	assert.equal( setup( { storage: fakeStorage( { ihq_save_home_installed: '1' } ) } ).saveHome.isHidden(), true );
	assert.equal( setup( { storage: fakeStorage( { ihq_save_home_installed: '0' } ) } ).saveHome.isHidden(), false );
} );

test( 'blocked storage: nothing throws, the buttons hide for this page view', async () => {
	const { saveHome } = setup( { userAgent: IPHONE_SAFARI, storage: throwingStorage() } );
	assert.equal( saveHome.isHidden(), false );
	await saveHome.handleClick();
	saveHome.sheet().querySelector( 'button' ).click();
	assert.equal( saveHome.isHidden(), true );
} );

test( 'the appinstalled event hides the buttons and is remembered on the device', () => {
	const { window, saveHome, storage } = setup();
	window.fire( new Event( 'appinstalled' ) );
	assert.equal( saveHome.isHidden(), true );
	assert.deepEqual( storage.data, { ihq_save_home_installed: '1' } );
} );

test( 'blocked storage: an install still hides the buttons for this page view', () => {
	const { window, saveHome } = setup( { storage: throwingStorage() } );
	window.fire( new Event( 'appinstalled' ) );
	assert.equal( saveHome.isHidden(), true );
} );

test( 'clicks elsewhere do nothing', async () => {
	const { button, saveHome } = setup( { userAgent: IPHONE_SAFARI } );
	button( 'other' ).click();
	await flush();
	assert.equal( saveHome.sheet(), null );
} );

test( 'the sheet style: one class on <html> hides every Save button, so buttons added later are covered', () => {
	assert.equal(
		SHEET_STYLE,
		`
.ihq-save-home-hidden [data-ihq-save-home] { display: none !important; }
.ihq-save-home-sheet { position: fixed; right: 16px; bottom: 16px; left: 16px; z-index: 100001; max-width: 420px; margin: 0 auto; padding: 20px; border: 1px solid #b7962f; background: #111; color: #fff; font-family: inherit; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6); }
.ihq-save-home-sheet h2 { margin: 0 0 8px; font-size: 1.125rem; }
.ihq-save-home-sheet p { margin: 0 0 16px; line-height: 1.5; }
.ihq-save-home-sheet button { width: 100%; padding: 12px; border: 0; background: #148942; color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
`
	);
} );

// A page for mountSaveHome(): the fake document plus <head>, and a window whose localStorage is given.
function pageSetup( localStorageGetter ) {
	const document = createFakeDocument();
	document.addEventListener = ( type, listener ) => document.documentElement.addEventListener( type, listener );
	document.removeEventListener = ( type, listener ) => document.documentElement.removeEventListener( type, listener );
	document.head = document.createElement( 'head' );
	document.documentElement.append( document.head );
	document.body.append( build( document, [ 'button', { 'data-ihq-save-home': true, id: 'save' }, 'SAVE IHQ TO HOME SCREEN' ] ) );
	const windowTarget = new EventTarget();
	const window = {
		navigator: { userAgent: IPHONE_SAFARI, maxTouchPoints: 0 },
		matchMedia: () => ( { matches: false } ),
		addEventListener: windowTarget.addEventListener.bind( windowTarget ),
	};
	Object.defineProperty( window, 'localStorage', { get: localStorageGetter } );
	return { document, window };
}

test( 'mountSaveHome adds the style to <head> and remembers a dismissal in localStorage', () => {
	const storage = fakeStorage();
	const { document, window } = pageSetup( () => storage );
	const saveHome = mountSaveHome( { document, window } );
	const styles = document.head.querySelectorAll( 'style' );
	assert.equal( styles.length, 1 );
	assert.equal( styles[ 0 ].textContent, SHEET_STYLE );
	document.getElementById( 'save' ).click();
	saveHome.sheet().querySelector( 'button' ).click();
	assert.equal( saveHome.isHidden(), true );
	assert.deepEqual( storage.data, { ihq_save_home_dismissed: '1' } );
} );

test( 'mountSaveHome reads an earlier dismissal from localStorage', () => {
	const { document, window } = pageSetup( () => fakeStorage( { ihq_save_home_dismissed: '1' } ) );
	assert.equal( mountSaveHome( { document, window } ).isHidden(), true );
} );

test( 'mountSaveHome works when reading localStorage throws (site data blocked)', () => {
	const { document, window } = pageSetup( () => {
		throw new Error( 'SecurityError' );
	} );
	const saveHome = mountSaveHome( { document, window } );
	assert.equal( saveHome.isHidden(), false );
	document.getElementById( 'save' ).click();
	saveHome.sheet().querySelector( 'button' ).click();
	assert.equal( saveHome.isHidden(), true );
} );

test( 'mountSaveHome works when localStorage is missing', () => {
	const { document, window } = pageSetup( () => null );
	const saveHome = mountSaveHome( { document, window } );
	assert.equal( saveHome.isHidden(), false );
	document.getElementById( 'save' ).click();
	saveHome.sheet().querySelector( 'button' ).click();
	assert.equal( saveHome.isHidden(), true );
} );
