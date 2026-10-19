import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createFakeDocument, createEvent } from './fake-dom.js';
import { createEmitter } from './onboarding-events.js';
import { createStringTable } from './onboarding-strings.js';
import { createApi } from './onboarding-boot.js';
import { SEQUENCE, startStage, nextStage } from './onboarding-sequence.js';
import { createFakeClock } from './fake-clock.js';
import { estimateDurationMs } from './commentary.js';
import { createEngine, ADVANCE_MODES } from './onboarding-engine.js';
import {
	POST_ACTION_STRINGS_EN,
	POST_ACTION_FIELD,
	POST_ACTIONS,
	PICK_ADVANCE_DELAY_MS,
	storedChoice,
	install,
} from './onboarding-screens-post-action.js';
import { PICK_ADVANCE_DELAY_MS as QUESTIONS_PICK_DELAY_MS } from './onboarding-screens-questions.js';

const STATE = { stage: 'post_action', answers: {}, platforms: [ 'x' ], handles: {}, language: '', drafts: { x: 'Post' }, completed: false, completed_at: '' };

function setup( state = {} ) {
	const document = createFakeDocument();
	const content = document.createElement( 'div' );
	document.body.append( content );
	const events = createEmitter();
	const strings = createStringTable();
	const api = createApi( { config: { state: { ...STATE, ...state }, progressUrl: '/p', completeUrl: '/c', nonce: 'n' }, events, strings } );
	api.shell = { content, say: () => Promise.resolve(), close: () => {} };
	const posts = [];
	const clock = createFakeClock();
	const engine = createEngine( {
		api,
		sequence: { SEQUENCE, startStage, nextStage },
		document,
		postJson: async ( request ) => {
			posts.push( request.body );
			return { ok: true, status: 200, data: null };
		},
		fetchFn: () => {},
		historyGuard: { install: () => {}, uninstall: () => {} },
		estimateDurationMs,
		setTimeout: clock.setTimeout,
		clearTimeout: clock.clearTimeout,
	} );
	api.screens = { register: engine.register, ADVANCE_MODES };
	api.save = engine.save;
	install( api, clock );
	// The screens either side, so the branch and the way on have somewhere to go.
	engine.register( { id: 'draft_posts', advanceMode: ADVANCE_MODES.MANUAL, render: () => ( {} ) } );
	engine.register( { id: 'closing', advanceMode: ADVANCE_MODES.MANUAL, render: () => ( {} ) } );
	engine.start();
	const screen = () => content.querySelector( 'section' );
	const option = ( value ) => screen().querySelector( `button[data-value="${ value }"]` );
	return { document, api, engine, clock, posts, strings, screen, option };
}

// The engine sends each progress save on its serial chain, a microtask or more after api.save().
const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

test( 'the three choices and their stored values are the store\'s', () => {
	assert.equal( POST_ACTION_FIELD, 'post_action_choice' );
	assert.deepEqual( POST_ACTIONS.map( ( action ) => action.value ), [ 'post_now', 'edit_together', 'edit_later' ] );
	const php = readFileSync( new URL( '../../inc/portal-onboarding-progress.php', import.meta.url ), 'utf8' );
	assert.equal( php.includes( "'post_action_choice'        => array( 'post_now', 'edit_together', 'edit_later' )," ), true );
	assert.equal( PICK_ADVANCE_DELAY_MS, QUESTIONS_PICK_DELAY_MS );
} );

test( 'the screen shows the design\'s three sentences as a radio group named by the question, none chosen', () => {
	const { screen, option } = setup();
	const group = screen().querySelector( 'div[role="radiogroup"]' );
	assert.equal( group.getAttribute( 'aria-label' ), 'What would you like to do with your posts?' );
	assert.equal( group.getAttribute( 'data-onb-label' ), 'postActionQuestion' );
	assert.deepEqual( group.querySelectorAll( 'button' ).map( ( button ) => button.textContent ), [
		"Great job, I'll post these now.",
		"Let's edit this together now.",
		"I'm going to edit later today and then post.",
	] );
	POST_ACTIONS.forEach( ( action ) => {
		assert.equal( option( action.value ).getAttribute( 'role' ), 'radio' );
		assert.equal( option( action.value ).getAttribute( 'aria-checked' ), 'false' );
		assert.equal( option( action.value ).getAttribute( 'type' ), 'button' );
		assert.equal( option( action.value ).className, 'ihq-onb-q__option ihq-onb-post-action__option' );
	} );
	assert.deepEqual( group.querySelectorAll( 'button' ).map( ( button ) => button.getAttribute( 'tabindex' ) ), [ '0', '-1', '-1' ] );
	assert.equal( screen().querySelector( 'p' ).hidden, true );
	assert.equal( POST_ACTION_STRINGS_EN.postActionRequired, 'Choose one to continue.' );
} );

test( 'a pick is saved at once and moves on to the closing screen after the pause', async () => {
	const { engine, clock, posts, option } = setup();
	option( 'post_now' ).click();
	assert.equal( option( 'post_now' ).getAttribute( 'aria-checked' ), 'true' );
	assert.equal( option( 'post_now' ).getAttribute( 'tabindex' ), '0' );
	await flush();
	assert.deepEqual( posts, [ { answers: { post_action_choice: 'post_now' } } ] );
	clock.tick( PICK_ADVANCE_DELAY_MS - 1 );
	assert.equal( engine.current(), 'post_action' );
	clock.tick( 1 );
	assert.equal( engine.current(), 'closing' );
	await flush();
	assert.deepEqual( posts, [ { answers: { post_action_choice: 'post_now' } }, { stage: 'closing' } ] );
} );

test( 'edit later also goes on to the closing screen', async () => {
	const { engine, clock, posts, option } = setup();
	option( 'edit_later' ).click();
	clock.tick( PICK_ADVANCE_DELAY_MS );
	assert.equal( engine.current(), 'closing' );
	await flush();
	assert.deepEqual( posts, [ { answers: { post_action_choice: 'edit_later' } }, { stage: 'closing' } ] );
} );

test( 'edit together goes back to the draft posts', async () => {
	const { engine, clock, posts, option } = setup();
	option( 'edit_together' ).click();
	clock.tick( PICK_ADVANCE_DELAY_MS );
	assert.equal( engine.current(), 'draft_posts' );
	await flush();
	assert.deepEqual( posts, [ { answers: { post_action_choice: 'edit_together' } }, { stage: 'draft_posts' } ] );
} );

test( 'a second pick during the pause replaces the choice and restarts the pause', async () => {
	const { engine, clock, posts, option } = setup();
	option( 'edit_together' ).click();
	clock.tick( PICK_ADVANCE_DELAY_MS - 1 );
	option( 'post_now' ).click();
	assert.equal( option( 'edit_together' ).getAttribute( 'aria-checked' ), 'false' );
	clock.tick( PICK_ADVANCE_DELAY_MS - 1 );
	assert.equal( engine.current(), 'post_action' );
	clock.tick( 1 );
	assert.equal( engine.current(), 'closing' );
	await flush();
	assert.deepEqual( posts, [ { answers: { post_action_choice: 'edit_together' } }, { answers: { post_action_choice: 'post_now' } }, { stage: 'closing' } ] );
} );

test( 're-picking the choice during the pause keeps the pause and saves nothing more', async () => {
	const { engine, clock, posts, option } = setup();
	option( 'post_now' ).click();
	clock.tick( PICK_ADVANCE_DELAY_MS - 1 );
	option( 'post_now' ).click();
	clock.tick( 1 );
	assert.equal( engine.current(), 'closing' );
	await flush();
	assert.deepEqual( posts, [ { answers: { post_action_choice: 'post_now' } }, { stage: 'closing' } ] );
} );

test( 'a returning Influencer sees the saved choice; picking it again is the way on, without a new save', async () => {
	const { engine, clock, posts, option } = setup( { answers: { post_action_choice: 'edit_together' } } );
	assert.equal( option( 'edit_together' ).getAttribute( 'aria-checked' ), 'true' );
	assert.equal( option( 'edit_together' ).getAttribute( 'tabindex' ), '0' );
	assert.equal( option( 'post_now' ).getAttribute( 'tabindex' ), '-1' );
	clock.tick( 60 * 1000 );
	assert.equal( engine.current(), 'post_action', 'a stored choice does not move on by itself' );
	option( 'edit_together' ).click();
	clock.tick( PICK_ADVANCE_DELAY_MS );
	assert.equal( engine.current(), 'draft_posts' );
	await flush();
	assert.deepEqual( posts, [ { stage: 'draft_posts' } ] );
} );

test( 'arrow keys move the choice like a pick, wrapping, and move focus with it', async () => {
	const { document, clock, engine, posts, option, screen } = setup();
	const group = screen().querySelector( 'div[role="radiogroup"]' );
	const down = createEvent( 'keydown', { key: 'ArrowDown' } );
	option( 'post_now' ).dispatchEvent( down );
	assert.equal( down.defaultPrevented, true );
	assert.equal( option( 'edit_together' ).getAttribute( 'aria-checked' ), 'true' );
	assert.equal( document.activeElement, option( 'edit_together' ) );
	option( 'post_now' ).dispatchEvent( createEvent( 'keydown', { key: 'ArrowUp' } ) );
	assert.equal( option( 'edit_later' ).getAttribute( 'aria-checked' ), 'true', 'up from the first wraps to the last' );
	option( 'edit_later' ).dispatchEvent( createEvent( 'keydown', { key: 'ArrowRight' } ) );
	assert.equal( option( 'post_now' ).getAttribute( 'aria-checked' ), 'true', 'right from the last wraps to the first' );
	option( 'post_now' ).dispatchEvent( createEvent( 'keydown', { key: 'ArrowLeft' } ) );
	assert.equal( option( 'edit_later' ).getAttribute( 'aria-checked' ), 'true' );
	const other = createEvent( 'keydown', { key: 'Tab' } );
	option( 'edit_later' ).dispatchEvent( other );
	assert.equal( other.defaultPrevented, false );
	assert.equal( option( 'edit_later' ).getAttribute( 'aria-checked' ), 'true' );
	group.dispatchEvent( createEvent( 'keydown', { key: 'ArrowDown' } ) );
	assert.equal( option( 'edit_later' ).getAttribute( 'aria-checked' ), 'true', 'a key on the group itself, not an option, does nothing' );
	group.dispatchEvent( createEvent( 'click' ) );
	assert.equal( option( 'edit_later' ).getAttribute( 'aria-checked' ), 'true', 'a click between the options does nothing' );
	clock.tick( PICK_ADVANCE_DELAY_MS );
	assert.equal( engine.current(), 'closing' );
	await flush();
	assert.deepEqual( posts, [
		{ answers: { post_action_choice: 'edit_together' } },
		{ answers: { post_action_choice: 'edit_later' } },
		{ answers: { post_action_choice: 'post_now' } },
		{ answers: { post_action_choice: 'edit_later' } },
		{ stage: 'closing' },
	] );
} );

test( 'asking to move on with no choice shows the error and stays; a pick hides it', () => {
	const { engine, screen, option } = setup();
	assert.equal( engine.advance(), false );
	const error = screen().querySelector( 'p' );
	assert.equal( error.hidden, false );
	assert.equal( error.textContent, 'Choose one to continue.' );
	assert.equal( error.getAttribute( 'role' ), 'alert' );
	assert.equal( engine.current(), 'post_action' );
	option( 'edit_later' ).click();
	assert.equal( error.hidden, true );
} );

test( 'leaving the screen during the pause cancels it', () => {
	const { engine, clock, option } = setup();
	option( 'edit_together' ).click();
	assert.equal( clock.pendingCount(), 1 );
	engine.advance();
	assert.equal( engine.current(), 'draft_posts' );
	assert.equal( clock.pendingCount(), 0 );
} );

test( 'storedChoice: only one of the three', () => {
	assert.equal( storedChoice( { post_action_choice: 'post_now' } ), 'post_now' );
	assert.equal( storedChoice( { post_action_choice: 'edit_together' } ), 'edit_together' );
	assert.equal( storedChoice( { post_action_choice: 'edit_later' } ), 'edit_later' );
	assert.equal( storedChoice( { post_action_choice: 'publish' } ), '' );
	assert.equal( storedChoice( {} ), '' );
	assert.equal( storedChoice( null ), '' );
	assert.equal( storedChoice( 'post_now' ), '' );
} );

test( 'an unknown stored choice shows none chosen', () => {
	const { option } = setup( { answers: { post_action_choice: 'publish' } } );
	POST_ACTIONS.forEach( ( action ) => assert.equal( option( action.value ).getAttribute( 'aria-checked' ), 'false' ) );
} );

test( 'install registers the English copy and screen 15 as a PICK screen', () => {
	const registered = [];
	const added = [];
	install( {
		strings: { add: ( locale, table ) => added.push( [ locale, table ] ) },
		screens: { register: ( definition ) => registered.push( definition ), ADVANCE_MODES },
	}, createFakeClock() );
	assert.deepEqual( added, [ [ 'en', POST_ACTION_STRINGS_EN ] ] );
	assert.equal( registered.length, 1 );
	assert.equal( registered[ 0 ].id, 'post_action' );
	assert.equal( registered[ 0 ].advanceMode, ADVANCE_MODES.PICK );
	assert.equal( typeof registered[ 0 ].render, 'function' );
} );
