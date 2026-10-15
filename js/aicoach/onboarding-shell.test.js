import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createFakeDocument, createEvent, build } from './fake-dom.js';
import { createStringTable, SHELL_STRINGS_EN } from './onboarding-strings.js';
import { createShell, part, makeOutsideInert, OPEN_CLASS, INERT_ATTRIBUTE } from './onboarding-shell.js';

// The parts of template-parts/portal-onboarding.php the shell uses.
function markup( document ) {
	return build( document, [ 'div', { id: 'ihq-onboarding' },
		[ 'button', { 'data-onb': 'menu-button', 'aria-expanded': 'false' } ],
		[ 'nav', { 'data-onb': 'menu', hidden: true }, [ 'a', { 'data-onb-string': 'logOut' }, 'Log out' ] ],
		[ 'button', { 'data-onb': 'sound', 'aria-pressed': 'false' } ],
		[ 'button', { 'data-onb': 'globe', 'data-onb-label': 'languageSelect' } ],
		[ 'p', { 'data-onb': 'subtitle' } ],
		[ 'p', { 'data-onb': 'transcript' } ],
		[ 'div', { 'data-onb': 'content' } ],
		[ 'button', { 'data-ihq-save-home': true, 'data-onb-string': 'saveToHomeScreen' }, 'SAVE IHQ TO HOME SCREEN' ],
	] );
}

function fakeNarrator() {
	const calls = [];
	let muted = false;
	return {
		calls,
		play: ( line ) => {
			calls.push( [ 'play', line ] );
			return Promise.resolve( { mode: 'captions' } );
		},
		stop: () => calls.push( [ 'stop' ] ),
		setMuted: ( value ) => {
			calls.push( [ 'setMuted', value ] );
			muted = value;
		},
		isMuted: () => muted,
	};
}

// The Coach page around the layer: a link beside it, and a wrapper it sits in with an
// element that was already inert before the layer opened.
function coachPage( document, root ) {
	const coachLink = build( document, [ 'a', { href: '/coach' }, 'Coach' ] );
	const alreadyInert = build( document, [ 'div', { inert: true } ] );
	const wrapper = build( document, [ 'main', {} ] );
	wrapper.append( alreadyInert, root );
	document.body.append( coachLink, wrapper );
	return { coachLink, alreadyInert, wrapper };
}

function setup( { locale = 'en' } = {} ) {
	const document = createFakeDocument();
	const root = markup( document );
	const page = coachPage( document, root );
	const scrolls = [];
	const window = { scrollTo: ( x, y ) => scrolls.push( [ x, y ] ) };
	const narrator = fakeNarrator();
	const strings = createStringTable();
	strings.add( 'en', SHELL_STRINGS_EN );
	strings.add( 'ja', { logOut: 'ログアウト' } );
	let current = locale;
	const shell = createShell( { root, document, window, narrator, strings, getLocale: () => current } );
	return { document, root, page, window, scrolls, narrator, strings, shell, setLocale: ( next ) => {
		current = next;
	} };
}

test( 'part() finds an element by its data-onb name', () => {
	const document = createFakeDocument();
	const root = markup( document );
	assert.equal( part( root, 'content' ).getAttribute( 'data-onb' ), 'content' );
	assert.equal( part( root, 'nope' ), null );
} );

test( 'opening the shell stops the Coach page behind it from scrolling', () => {
	const { document } = setup();
	assert.equal( OPEN_CLASS, 'ihq-onb-open' );
	assert.equal( document.documentElement.classList.contains( 'ihq-onb-open' ), true );
} );

test( 'the content area is exposed for screens to render into', () => {
	const { root, shell } = setup();
	assert.equal( shell.content, part( root, 'content' ) );
	assert.equal( shell.root, root );
} );

test( 'the hamburger opens and closes the menu with Log out in it', () => {
	const { root } = setup();
	const button = part( root, 'menu-button' );
	const menu = part( root, 'menu' );
	button.click();
	assert.equal( menu.hidden, false );
	assert.equal( button.getAttribute( 'aria-expanded' ), 'true' );
	assert.equal( button.getAttribute( 'aria-label' ), 'Close menu' );
	assert.equal( menu.querySelector( 'a' ).textContent, 'Log out' );
	button.click();
	assert.equal( menu.hidden, true );
	assert.equal( button.getAttribute( 'aria-expanded' ), 'false' );
	assert.equal( button.getAttribute( 'aria-label' ), 'Open menu' );
} );

test( 'Escape closes an open menu and gives focus back to the hamburger', () => {
	const { document, root } = setup();
	const button = part( root, 'menu-button' );
	button.click();
	root.dispatchEvent( createEvent( 'keydown', { key: 'Escape' } ) );
	assert.equal( part( root, 'menu' ).hidden, true );
	assert.equal( document.activeElement, button );
} );

test( 'Escape with the menu closed and other keys do nothing', () => {
	const { document, root } = setup();
	root.dispatchEvent( createEvent( 'keydown', { key: 'Escape' } ) );
	assert.equal( document.activeElement, root );
	part( root, 'menu-button' ).click();
	root.dispatchEvent( createEvent( 'keydown', { key: 'Enter' } ) );
	assert.equal( part( root, 'menu' ).hidden, false );
} );

test( 'opening the shell moves focus into the dialog', () => {
	const { document, root } = setup();
	assert.equal( root.getAttribute( 'tabindex' ), '-1' );
	assert.equal( document.activeElement, root );
} );

test( 'opening the shell makes the Coach page behind it inert, and close() gives it back', () => {
	const { root, page, shell } = setup();
	assert.equal( INERT_ATTRIBUTE, 'inert' );
	assert.equal( page.coachLink.hasAttribute( 'inert' ), true );
	assert.equal( page.alreadyInert.hasAttribute( 'inert' ), true );
	assert.equal( page.wrapper.hasAttribute( 'inert' ), false );
	assert.equal( root.hasAttribute( 'inert' ), false );
	shell.close();
	assert.equal( page.coachLink.hasAttribute( 'inert' ), false );
	assert.equal( page.alreadyInert.hasAttribute( 'inert' ), true, 'an element that was inert before stays inert' );
} );

test( 'makeOutsideInert stops at <body> and does nothing for a detached layer', () => {
	const document = createFakeDocument();
	const head = build( document, [ 'head', {} ] );
	document.documentElement.append( head );
	const root = build( document, [ 'div', {} ] );
	document.body.append( root );
	const restore = makeOutsideInert( root, document );
	assert.equal( head.hasAttribute( 'inert' ), false );
	restore();
	const detached = build( document, [ 'div', {} ] );
	makeOutsideInert( detached, document )();
	assert.equal( detached.hasAttribute( 'inert' ), false );
} );

test( 'a click elsewhere in the layer closes the open menu; clicks on the menu or its button do not', () => {
	const { root } = setup();
	const button = part( root, 'menu-button' );
	const menu = part( root, 'menu' );
	button.click();
	menu.querySelector( 'a' ).dispatchEvent( createEvent( 'click' ) );
	assert.equal( menu.hidden, false );
	part( root, 'content' ).click();
	assert.equal( menu.hidden, true );
	assert.equal( button.getAttribute( 'aria-expanded' ), 'false' );
	part( root, 'content' ).click();
	assert.equal( menu.hidden, true );
} );

test( 'focus moving to another control closes the open menu; within the menu or out of the window it stays open', () => {
	const { root } = setup();
	const button = part( root, 'menu-button' );
	const menu = part( root, 'menu' );
	const link = menu.querySelector( 'a' );
	button.click();
	button.dispatchEvent( createEvent( 'focusout', { relatedTarget: link } ) );
	assert.equal( menu.hidden, false );
	link.dispatchEvent( createEvent( 'focusout', { relatedTarget: button } ) );
	assert.equal( menu.hidden, false );
	link.dispatchEvent( createEvent( 'focusout', { relatedTarget: null } ) );
	assert.equal( menu.hidden, false );
	link.dispatchEvent( createEvent( 'focusout', { relatedTarget: part( root, 'sound' ) } ) );
	assert.equal( menu.hidden, true );
	assert.equal( button.getAttribute( 'aria-expanded' ), 'false' );
} );

test( 'focus moving with the menu closed changes nothing', () => {
	const { root } = setup();
	part( root, 'sound' ).dispatchEvent( createEvent( 'focusout', { relatedTarget: part( root, 'globe' ) } ) );
	assert.equal( part( root, 'menu' ).hidden, true );
	assert.equal( part( root, 'menu-button' ).getAttribute( 'aria-expanded' ), 'false' );
} );

test( 'the sound button mutes Sami and unmutes her, and says which', () => {
	const { root, narrator } = setup();
	const sound = part( root, 'sound' );
	sound.click();
	assert.deepEqual( narrator.calls, [ [ 'setMuted', true ] ] );
	assert.equal( sound.getAttribute( 'aria-pressed' ), 'true' );
	assert.equal( sound.getAttribute( 'aria-label' ), 'Unmute Sami' );
	sound.click();
	assert.deepEqual( narrator.calls, [ [ 'setMuted', true ], [ 'setMuted', false ] ] );
	assert.equal( sound.getAttribute( 'aria-pressed' ), 'false' );
	assert.equal( sound.getAttribute( 'aria-label' ), 'Mute Sami' );
} );

test( 'showMuted reflects a mute the narrator was forced into (autoplay)', () => {
	const { root, shell } = setup();
	shell.showMuted( true );
	assert.equal( part( root, 'sound' ).getAttribute( 'aria-pressed' ), 'true' );
} );

test( 'say() plays the line and gives a screen reader the whole script once', async () => {
	const { root, narrator, shell } = setup();
	const result = await shell.say( { script: 'Hello. I am Sami.', clipUrl: 'c.mp4' } );
	assert.deepEqual( result, { mode: 'captions' } );
	assert.deepEqual( narrator.calls, [ [ 'play', { script: 'Hello. I am Sami.', clipUrl: 'c.mp4' } ] ] );
	assert.equal( part( root, 'transcript' ).textContent, 'Hello. I am Sami.' );
} );

test( 'showSubtitle writes the sentence in the commentary box', () => {
	const { root, shell } = setup();
	shell.showSubtitle( 'Hello.' );
	assert.equal( part( root, 'subtitle' ).textContent, 'Hello.' );
	shell.showSubtitle( '' );
	assert.equal( part( root, 'subtitle' ).textContent, '' );
} );

test( 'stopSpeaking stops the narrator', () => {
	const { narrator, shell } = setup();
	shell.stopSpeaking();
	assert.deepEqual( narrator.calls, [ [ 'stop' ] ] );
} );

test( 'applyStrings re-labels text and aria-labels in the current language, English where missing', () => {
	const { root, shell, setLocale } = setup();
	setLocale( 'ja' );
	shell.applyStrings();
	assert.equal( part( root, 'menu' ).querySelector( 'a' ).textContent, 'ログアウト' );
	assert.equal( root.querySelector( '[data-ihq-save-home]' ).textContent, 'SAVE IHQ TO HOME SCREEN' );
	assert.equal( part( root, 'globe' ).getAttribute( 'aria-label' ), 'Select language' );
	assert.equal( part( root, 'menu-button' ).getAttribute( 'aria-label' ), 'Open menu' );
	assert.equal( part( root, 'sound' ).getAttribute( 'aria-label' ), 'Mute Sami' );
	assert.equal( part( root, 'menu' ).hidden, true );
} );

test( 'close() stops Sami, removes the layer and leaves the Coach page at its top', () => {
	const { document, root, scrolls, narrator, shell } = setup();
	shell.close();
	assert.deepEqual( narrator.calls, [ [ 'stop' ] ] );
	assert.equal( document.documentElement.contains( root ), false );
	assert.equal( document.documentElement.classList.contains( 'ihq-onb-open' ), false );
	assert.deepEqual( scrolls, [ [ 0, 0 ] ] );
} );

// Every value of an attribute in the template, sorted.
function attributeValues( template, attribute ) {
	const pattern = new RegExp( ' ' + attribute + '="([^"]*)"', 'g' );
	return Array.from( template.matchAll( pattern ), ( match ) => match[ 1 ] ).sort();
}

test( 'the template has exactly the parts and strings the shell looks for', () => {
	const template = readFileSync( new URL( '../../template-parts/portal-onboarding.php', import.meta.url ), 'utf8' );
	assert.deepEqual( attributeValues( template, 'data-onb' ), [ 'content', 'globe', 'languages', 'menu', 'menu-button', 'sound', 'subtitle', 'transcript', 'video' ] );
	assert.deepEqual( attributeValues( template, 'data-onb-string' ), [ 'logOut', 'saveToHomeScreen' ] );
	assert.deepEqual( attributeValues( template, 'data-onb-label' ), [ 'languageSelect' ] );
	assert.equal( template.match( / data-ihq-save-home[ >]/g ).length, 1 );
	assert.equal( template.match( / id="ihq-onboarding-config"/g ).length, 1 );
	assert.equal( /header-login-link|portal-header-auth-trigger|header-help-btn|header-logout-btn/.test( template ), false, 'no Login button, Help link or account control in the onboarding header' );
	const keys = [ ...attributeValues( template, 'data-onb-string' ), ...attributeValues( template, 'data-onb-label' ) ];
	assert.deepEqual( keys.filter( ( key ) => ! ( key in SHELL_STRINGS_EN ) ), [] );
} );

test( 'the stylesheet adds no media query width other than the portal 768/1025 px ones', () => {
	const css = readFileSync( new URL( '../../css/portal-onboarding.css', import.meta.url ), 'utf8' );
	const queries = css.match( /@media[^{]+/g ).map( ( query ) => query.trim() );
	assert.deepEqual( queries, [ '@media (min-width: 768px)', '@media (min-width: 1025px)' ] );
} );
