import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeDocument, build } from './fake-dom.js';
import { createEmitter } from './onboarding-events.js';
import { createStringTable } from './onboarding-strings.js';
import { createApi } from './onboarding-boot.js';
import { SEQUENCE, startStage, nextStage } from './onboarding-sequence.js';
import { createEngine, ENGINE_STRINGS_EN } from './onboarding-engine.js';
import { CLOSING_STRINGS_EN, install } from './onboarding-screens-closing.js';

const STATE = { stage: 'closing', answers: {}, platforms: [], handles: {}, language: '', completed: false, completed_at: '' };

function setup( { completeResults = [ { ok: true, status: 200, data: { completed: true } } ], stage = 'closing' } = {} ) {
	const document = createFakeDocument();
	const root = build( document, [ 'div', { id: 'ihq-onboarding' },
		[ 'img', { class: 'ihq-onb__portrait', src: 'https://example.test/sami.webp' } ],
		[ 'div', { 'data-onb': 'content' } ],
		[ 'div', { class: 'ihq-onb__cta' }, [ 'button', { 'data-ihq-save-home': true } ] ],
	] );
	document.body.append( root );
	const content = root.querySelector( '[data-onb="content"]' );
	const events = createEmitter();
	const strings = createStringTable();
	strings.add( 'en', ENGINE_STRINGS_EN );
	const api = createApi( { config: { state: { ...STATE, stage }, progressUrl: '/p', completeUrl: '/complete', nonce: 'n' }, events, strings } );
	const closes = [];
	api.shell = {
		root,
		content,
		say: () => Promise.resolve(),
		close: () => {
			closes.push( true );
			root.remove();
		},
	};
	const posts = [];
	const guard = [];
	const engine = createEngine( {
		api,
		sequence: { SEQUENCE, startStage, nextStage },
		document,
		postJson: async ( request ) => {
			posts.push( request.url );
			return completeResults.shift();
		},
		fetchFn: () => {},
		historyGuard: { install: () => guard.push( 'install' ), uninstall: () => guard.push( 'uninstall' ) },
		onError: () => {},
	} );
	api.screens = { register: engine.register };
	api.complete = engine.complete;
	install( api );
	engine.register( { id: 'post_action', render: () => ( {} ) } );
	engine.start();
	const screen = () => content.querySelector( 'section' );
	return { document, root, engine, posts, closes, guard, screen, api };
}

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

test( 'the closing copy', () => {
	assert.deepEqual( CLOSING_STRINGS_EN, {
		closingAlwaysHere: "I'll always be here for you 24 hours a day, 7 days a week.",
		closingSaveReminder: 'If you have not saved the IHQ portal address already, make sure to do that now.',
		closingContinue: 'CONTINUE TO IHQ PORTAL',
		closingSave: 'SAVE IHQ TO HOME SCREEN',
		closingFailed: 'Something went wrong. Please try again.',
	} );
} );

test( 'the screen shows the enlarged avatar, both message points and both buttons, CONTINUE first', () => {
	const { screen } = setup();
	const avatar = screen().querySelector( 'img.ihq-onb-closing__avatar' );
	assert.equal( avatar.getAttribute( 'src' ), 'https://example.test/sami.webp' );
	assert.equal( avatar.getAttribute( 'alt' ), '' );
	assert.deepEqual( screen().querySelector( '.ihq-onb-closing__message' ).querySelectorAll( 'p' ).map( ( p ) => p.textContent ), [
		"I'll always be here for you 24 hours a day, 7 days a week.",
		'If you have not saved the IHQ portal address already, make sure to do that now.',
	] );
	const buttons = screen().querySelector( '.ihq-onb-closing__actions' ).querySelectorAll( 'button' );
	assert.deepEqual( buttons.map( ( button ) => button.textContent ), [ 'CONTINUE TO IHQ PORTAL', 'SAVE IHQ TO HOME SCREEN' ] );
	assert.equal( buttons[ 1 ].hasAttribute( 'data-ihq-save-home' ), true );
	assert.ok( buttons.every( ( button ) => 'button' === button.getAttribute( 'type' ) ) );
} );

test( 'the engine adds no CONTINUE of its own here', () => {
	const { screen } = setup();
	assert.equal( screen().querySelector( '[data-onb="continue"]' ), null );
} );

test( 'the frame Save button is hidden while the closing screen shows, so there is one', () => {
	const { root } = setup();
	assert.equal( root.querySelector( '.ihq-onb__cta' ).hidden, true );
} );

test( 'CONTINUE TO IHQ PORTAL marks onboarding complete and closes the layer onto the Coach page', async () => {
	const { screen, posts, closes, guard, document, root } = setup();
	screen().querySelector( '[data-onb="complete"]' ).click();
	await flush();
	assert.deepEqual( posts, [ '/complete' ] );
	assert.deepEqual( closes, [ true ] );
	assert.deepEqual( guard, [ 'install', 'uninstall' ] );
	assert.equal( document.body.contains( root ), false );
	assert.equal( root.querySelector( '.ihq-onb__cta' ).hidden, false, 'the frame Save button is restored on leaving' );
} );

test( 'CONTINUE is disabled while completing, so it cannot be sent twice', () => {
	const { screen, posts } = setup();
	const button = screen().querySelector( '[data-onb="complete"]' );
	button.click();
	assert.equal( button.disabled, true );
	button.click();
	assert.equal( posts.length, 1 );
} );

test( 'a failed completion keeps the user on the screen with a message, and CONTINUE works again', async () => {
	const { screen, closes, posts, engine } = setup( { completeResults: [ { ok: false, status: 500, data: null }, { ok: true, status: 200, data: {} } ] } );
	const button = screen().querySelector( '[data-onb="complete"]' );
	button.click();
	await flush();
	const error = screen().querySelector( 'p.ihq-onb__error' );
	assert.equal( error.hidden, false );
	assert.equal( error.textContent, 'Something went wrong. Please try again.' );
	assert.equal( error.getAttribute( 'role' ), 'alert' );
	assert.equal( button.disabled, false );
	assert.deepEqual( closes, [] );
	assert.equal( engine.current(), 'closing' );
	button.click();
	assert.equal( error.hidden, true );
	await flush();
	assert.deepEqual( posts, [ '/complete', '/complete' ] );
	assert.deepEqual( closes, [ true ] );
} );

test( 'SAVE IHQ TO HOME SCREEN leaves the user on this screen', () => {
	const { screen, posts, closes, engine } = setup();
	screen().querySelector( '.ihq-onb-closing__actions' ).querySelector( '[data-ihq-save-home]' ).click();
	assert.deepEqual( posts, [] );
	assert.deepEqual( closes, [] );
	assert.equal( engine.current(), 'closing' );
} );

test( 'reaching the closing screen from the post action screen', () => {
	const { engine, screen } = setup( { stage: 'post_action' } );
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'closing' );
	assert.ok( screen().querySelector( '[data-onb="complete"]' ) );
} );

test( 'without a frame (no root) the screen still renders', () => {
	const document = createFakeDocument();
	const content = document.createElement( 'div' );
	const strings = createStringTable();
	const registered = [];
	install( { strings, screens: { register: ( definition ) => registered.push( definition ) } } );
	assert.equal( registered.length, 1 );
	assert.equal( registered[ 0 ].id, 'closing' );
	assert.equal( registered[ 0 ].hideContinue, true );
	const container = document.createElement( 'section' );
	content.append( container );
	const text = ( tag, key, className ) => {
		const element = document.createElement( tag );
		element.textContent = strings.t( 'en', key );
		if ( className ) {
			element.className = className;
		}
		return element;
	};
	const controller = registered[ 0 ].render( { container, api: { shell: {}, strings }, text } );
	assert.equal( container.querySelector( 'img' ).getAttribute( 'src' ), '' );
	assert.doesNotThrow( () => controller.destroy() );
} );
