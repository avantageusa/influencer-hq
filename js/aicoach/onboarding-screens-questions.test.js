import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createFakeDocument, createEvent } from './fake-dom.js';
import { createFakeClock } from './fake-clock.js';
import { estimateDurationMs } from './commentary.js';
import { createEmitter } from './onboarding-events.js';
import { createStringTable } from './onboarding-strings.js';
import { createApi } from './onboarding-boot.js';
import { SEQUENCE, startStage, nextStage } from './onboarding-sequence.js';
import { createEngine, ADVANCE_MODES } from './onboarding-engine.js';
import {
	QUESTION_STRINGS_EN,
	QUESTIONS,
	PICK_ADVANCE_DELAY_MS,
	pickMain,
	pickSub,
	missingAnswer,
	storedAnswer,
	questionScreens,
	install,
} from './onboarding-screens-questions.js';

const STATE = { stage: '', answers: {}, platforms: [], handles: {}, language: '', completed: false, completed_at: '' };
const question = ( id ) => QUESTIONS.find( ( entry ) => entry.id === id );
// The engine's save queue sends each POST a microtask or more after the call.
const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
const SAVED = { ok: true, status: 200, data: null };
const REJECTED = { ok: false, status: 400, data: { error: 'Invalid answers.' } };

// The engine with this module installed, on the fake DOM; posts and reported errors are recorded.
// respond( body ) is what postJson answers (a value or a promise); it defaults to a success.
// The engine and the screens share one fake clock, so a test moves time with clock.tick().
// stubs are other stages to install as empty MANUAL screens, so a question can advance onto them.
function setup( { stage = 'baccarat_experience', answers = {}, respond = () => SAVED, stubs = [] } = {} ) {
	const document = createFakeDocument();
	const content = document.createElement( 'div' );
	document.body.append( content );
	const events = createEmitter();
	const strings = createStringTable();
	const clock = createFakeClock();
	const api = createApi( { config: { state: { ...STATE, stage, answers }, progressUrl: '/p', completeUrl: '/c', nonce: 'n' }, events, strings } );
	api.shell = { content, say: () => Promise.resolve(), close: () => {} };
	const posts = [];
	const errors = [];
	const engine = createEngine( {
		api,
		sequence: { SEQUENCE, startStage, nextStage },
		document,
		postJson: async ( request ) => {
			posts.push( request.body );
			return respond( request.body );
		},
		fetchFn: () => {},
		historyGuard: { install: () => {}, uninstall: () => {} },
		onError: ( error ) => errors.push( error.message ),
		estimateDurationMs,
		setTimeout: clock.setTimeout,
		clearTimeout: clock.clearTimeout,
	} );
	api.screens = { register: engine.register, ADVANCE_MODES };
	api.save = engine.save;
	install( api, clock );
	stubs.forEach( ( id ) => engine.register( { id, advanceMode: ADVANCE_MODES.MANUAL, render: () => ( {} ) } ) );
	engine.start();
	const screen = () => content.querySelector( 'section' );
	const option = ( group, value ) => screen().querySelector( `button[data-group="${ group }"][data-value="${ value }"]` );
	const checked = ( group ) => screen().querySelectorAll( `button[data-group="${ group }"]` ).filter( ( button ) => 'true' === button.getAttribute( 'aria-checked' ) ).map( ( button ) => button.getAttribute( 'data-value' ) );
	const subGroup = () => screen().querySelector( 'div[data-group="sub"]' );
	const error = () => screen().querySelector( 'p.ihq-onb__error' );
	const tabStops = ( group ) => screen().querySelectorAll( `button[data-group="${ group }"]` ).map( ( button ) => button.getAttribute( 'tabindex' ) );
	return { api, engine, clock, posts, errors, strings, document, screen, option, checked, subGroup, error, tabStops };
}

const labels = ( screen, group ) => screen.querySelectorAll( `button[data-group="${ group }"]` ).map( ( button ) => button.querySelector( '.ihq-onb-q__label' ).textContent );

test( 'registers screens 3, 4, 5 and 12, each a PICK screen', () => {
	const definitions = questionScreens( { advanceModes: ADVANCE_MODES, timers: createFakeClock() } );
	assert.deepEqual( definitions.map( ( definition ) => definition.id ), [ 'baccarat_experience', 'streaming_experience', 'equity_knowledge', 'active_followers' ] );
	assert.deepEqual( definitions.map( ( definition ) => definition.advanceMode ), [ 'pick', 'pick', 'pick', 'pick' ] );
	const registered = [];
	const strings = createStringTable();
	install( { strings, screens: { register: ( definition ) => registered.push( { id: definition.id, advanceMode: definition.advanceMode } ), ADVANCE_MODES } } );
	assert.deepEqual( registered, [
		{ id: 'baccarat_experience', advanceMode: 'pick' },
		{ id: 'streaming_experience', advanceMode: 'pick' },
		{ id: 'equity_knowledge', advanceMode: 'pick' },
		{ id: 'active_followers', advanceMode: 'pick' },
	] );
	assert.equal( strings.t( 'en', 'followersUnder10k' ), 'Under 10,000' );
} );

test( 'every stored value is one the account store accepts, field by field', () => {
	const php = readFileSync( new URL( '../../inc/portal-onboarding-progress.php', import.meta.url ), 'utf8' );
	const body = php.slice( php.indexOf( 'function ihq_portal_onboarding_answer_values()' ) );
	const block = body.slice( body.indexOf( 'return array(' ), body.indexOf( ');\n}' ) );
	const accepted = {};
	for ( const line of block.split( '\n' ) ) {
		const match = /'([a-z_]+)'\s*=>\s*array\(([^)]*)\)/.exec( line );
		if ( match ) {
			accepted[ match[ 1 ] ] = Array.from( match[ 2 ].matchAll( /'([a-z0-9_]+)'/g ), ( value ) => value[ 1 ] );
		}
	}
	for ( const entry of QUESTIONS ) {
		assert.deepEqual( entry.options.map( ( option ) => option.value ), accepted[ entry.field ], entry.field );
		entry.options.filter( ( option ) => option.reveals ).forEach( ( option ) => {
			assert.deepEqual( option.reveals.options.map( ( sub ) => sub.value ), accepted[ option.reveals.field ], option.reveals.field );
		} );
	}
} );

test( 'the engine sequence is the stage list the account store accepts', () => {
	const php = readFileSync( new URL( '../../inc/portal-onboarding-progress.php', import.meta.url ), 'utf8' );
	const body = php.slice( php.indexOf( 'function ihq_portal_onboarding_stages()' ) );
	const list = body.slice( body.indexOf( 'array(' ), body.indexOf( ');' ) );
	assert.deepEqual( Array.from( list.matchAll( /'([a-z_]+)'/g ), ( match ) => match[ 1 ] ), SEQUENCE );
} );

test( 'screen 3: the question, three options with only their label (no XP level), the stored values', () => {
	const { screen } = setup();
	assert.equal( screen().querySelector( 'h2' ).textContent, 'What is your experience with Baccarat?' );
	assert.deepEqual( labels( screen(), 'main' ), [ "I'VE PLAYED A LOT", "I'VE PLAYED SOME", "I'M NEW" ] );
	const contents = screen().querySelectorAll( 'button[data-group="main"]' ).map( ( button ) => Array.from( button.children, ( child ) => child.className ) );
	assert.deepEqual( contents, [ [ 'ihq-onb-q__label' ], [ 'ihq-onb-q__label' ], [ 'ihq-onb-q__label' ] ] );
	assert.deepEqual( question( 'baccarat_experience' ).options.map( ( option ) => Object.keys( option ) ), [ [ 'value', 'labelKey' ], [ 'value', 'labelKey' ], [ 'value', 'labelKey' ] ] );
	assert.equal( screen().querySelector( 'div[data-group="sub"]' ), null );
} );

test( 'screen 4: three options; I\'M NEW TO STREAMING reveals two sub-options', () => {
	const { screen, subGroup } = setup( { stage: 'streaming_experience' } );
	assert.equal( screen().querySelector( 'h2' ).textContent, 'Do you have streaming experience?' );
	assert.deepEqual( labels( screen(), 'main' ), [ "I'VE STREAMED A LOT", "I'VE STREAMED SOME", "I'M NEW TO STREAMING" ] );
	assert.deepEqual( labels( screen(), 'sub' ), [ "I'M READY TO LEARN", 'MAYBE LATER' ] );
	assert.equal( subGroup().hidden, true );
} );

test( 'screen 5: three options; I CURRENTLY OWN EQUITY reveals two sub-options', () => {
	const { screen, subGroup } = setup( { stage: 'equity_knowledge' } );
	assert.equal( screen().querySelector( 'h2' ).textContent, 'How much do you know about equity?' );
	assert.deepEqual( labels( screen(), 'main' ), [ 'I CURRENTLY OWN EQUITY', 'I UNDERSTAND HOW EQUITY WORKS', 'INTERESTED IN EARNING EQUITY' ] );
	assert.deepEqual( labels( screen(), 'sub' ), [ 'I MANAGE IT MYSELF', 'SOMEONE ELSE DOES IT FOR ME' ] );
	assert.equal( subGroup().hidden, true );
} );

test( 'screen 12: five follower options, the last one Under 10,000', () => {
	const { screen } = setup( { stage: 'active_followers' } );
	assert.equal( screen().querySelector( 'h2' ).textContent, 'How many Active Followers do you have?' );
	assert.deepEqual( labels( screen(), 'main' ), [ '100,000+', '50,000+', '25,000+', '10,000+', 'Under 10,000' ] );
} );

test( 'the options are a radio group named by the question', () => {
	const { screen } = setup();
	const group = screen().querySelector( 'div[data-group="main"]' );
	assert.equal( group.getAttribute( 'role' ), 'radiogroup' );
	assert.equal( group.getAttribute( 'aria-labelledby' ), 'ihq-onb-q-baccarat_experience' );
	assert.equal( screen().querySelector( 'h2' ).getAttribute( 'id' ), 'ihq-onb-q-baccarat_experience' );
	screen().querySelectorAll( 'button[data-group="main"]' ).forEach( ( button ) => {
		assert.equal( button.getAttribute( 'role' ), 'radio' );
		assert.equal( button.getAttribute( 'aria-checked' ), 'false' );
		assert.equal( button.getAttribute( 'type' ), 'button' );
	} );
} );

test( 'every option of every screen is stored as its enum value when picked', async () => {
	for ( const entry of QUESTIONS ) {
		for ( const option of entry.options ) {
			const { option: find, posts, checked } = setup( { stage: entry.id } );
			find( 'main', option.value ).click();
			await flush();
			assert.deepEqual( posts, [ { answers: { [ entry.field ]: option.value } } ], option.value );
			assert.deepEqual( checked( 'main' ), [ option.value ] );
		}
	}
} );

test( 'none of the four screens advances without a selection, and each shows why', async () => {
	for ( const entry of QUESTIONS ) {
		const { engine, error, posts } = setup( { stage: entry.id } );
		assert.equal( error().hidden, true );
		assert.equal( engine.advance(), false, entry.id );
		assert.equal( engine.current(), entry.id );
		assert.equal( error().hidden, false );
		assert.equal( error().textContent, 'Choose an answer to continue.' );
		assert.equal( error().getAttribute( 'role' ), 'alert' );
		await flush();
		assert.deepEqual( posts, [] );
	}
} );

test( 'a picked answer lets the screen advance and hides the error', () => {
	const { engine, error, option } = setup();
	engine.advance();
	option( 'main', 'played_some' ).click();
	assert.equal( error().hidden, true );
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'streaming_experience' );
} );

test( 'screen 4: the revealed sub-option is required, then stored', async () => {
	const { engine, option, subGroup, error, posts, checked } = setup( { stage: 'streaming_experience' } );
	option( 'main', 'new_to_streaming' ).click();
	assert.equal( subGroup().hidden, false );
	assert.equal( engine.advance(), false );
	assert.equal( error().textContent, 'Choose one of the options below your answer to continue.' );
	option( 'sub', 'new_ready_to_learn' ).click();
	assert.deepEqual( checked( 'sub' ), [ 'new_ready_to_learn' ] );
	await flush();
	assert.deepEqual( posts, [ { answers: { streaming_experience: 'new_to_streaming' } }, { answers: { streaming_learning_intent: 'new_ready_to_learn' } } ] );
	assert.equal( engine.advance(), true );
} );

test( 'screen 4: switching away from I\'M NEW TO STREAMING hides and clears the sub-answer', async () => {
	const { option, subGroup, posts, checked, engine } = setup( { stage: 'streaming_experience' } );
	option( 'main', 'new_to_streaming' ).click();
	option( 'sub', 'new_maybe_later' ).click();
	option( 'main', 'streamed_some' ).click();
	assert.equal( subGroup().hidden, true );
	assert.deepEqual( checked( 'sub' ), [] );
	await flush();
	assert.deepEqual( posts.at( -1 ), { answers: { streaming_experience: 'streamed_some', streaming_learning_intent: null } } );
	option( 'main', 'new_to_streaming' ).click();
	assert.equal( subGroup().hidden, false );
	assert.deepEqual( checked( 'sub' ), [], 'the cleared sub-answer does not come back' );
	assert.equal( engine.advance(), false );
} );

test( 'screen 5: owns equity requires managing, and the stored values', async () => {
	const { option, posts, engine } = setup( { stage: 'equity_knowledge' } );
	option( 'main', 'owns_equity' ).click();
	assert.equal( engine.advance(), false );
	option( 'sub', 'owns_self_managed' ).click();
	option( 'sub', 'owns_managed_by_other' ).click();
	await flush();
	assert.deepEqual( posts, [
		{ answers: { equity_knowledge: 'owns_equity' } },
		{ answers: { equity_management: 'owns_self_managed' } },
		{ answers: { equity_management: 'owns_managed_by_other' } },
	] );
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'active_followers' );
} );

test( 'screen 5: switching away from owning equity clears the management answer', async () => {
	const { option, posts, subGroup } = setup( { stage: 'equity_knowledge' } );
	option( 'main', 'owns_equity' ).click();
	option( 'sub', 'owns_self_managed' ).click();
	option( 'main', 'interested_in_equity' ).click();
	assert.equal( subGroup().hidden, true );
	await flush();
	assert.deepEqual( posts.at( -1 ), { answers: { equity_knowledge: 'interested_in_equity', equity_management: null } } );
} );

test( 'switching between two plain answers does not send a sub-answer', async () => {
	const { option, posts } = setup( { stage: 'equity_knowledge' } );
	option( 'main', 'understands_equity' ).click();
	option( 'main', 'interested_in_equity' ).click();
	await flush();
	assert.deepEqual( posts, [ { answers: { equity_knowledge: 'understands_equity' } }, { answers: { equity_knowledge: 'interested_in_equity' } } ] );
} );

test( 'a returning Influencer sees their saved answers and can advance', () => {
	const { checked, subGroup, engine } = setup( { stage: 'streaming_experience', answers: { streaming_experience: 'new_to_streaming', streaming_learning_intent: 'new_maybe_later' } } );
	assert.deepEqual( checked( 'main' ), [ 'new_to_streaming' ] );
	assert.deepEqual( checked( 'sub' ), [ 'new_maybe_later' ] );
	assert.equal( subGroup().hidden, false );
	assert.equal( engine.advance(), true );
} );

test( 'clicking between the options (not on one) does nothing', async () => {
	const { screen, posts } = setup();
	screen().querySelector( 'div[data-group="main"]' ).click();
	await flush();
	assert.deepEqual( posts, [] );
} );

test( 'pickMain: unknown values and re-picking the current answer change nothing and store nothing', () => {
	const streaming = question( 'streaming_experience' );
	const answer = { main: 'new_to_streaming', sub: 'new_maybe_later' };
	assert.deepEqual( pickMain( streaming, answer, 'nonsense' ), { answer, save: null } );
	assert.deepEqual( pickMain( streaming, answer, 'new_to_streaming' ), { answer: { main: 'new_to_streaming', sub: 'new_maybe_later' }, save: null } );
	const halfAnswered = { main: 'new_to_streaming', sub: '' };
	assert.deepEqual( pickMain( streaming, halfAnswered, 'new_to_streaming' ), { answer: { main: 'new_to_streaming', sub: '' }, save: null } );
	const baccarat = { main: 'new_player', sub: '' };
	assert.deepEqual( pickMain( question( 'baccarat_experience' ), baccarat, 'new_player' ), { answer: { main: 'new_player', sub: '' }, save: null } );
	assert.deepEqual( pickMain( question( 'baccarat_experience' ), { main: '', sub: '' }, 'new_player' ), { answer: { main: 'new_player', sub: '' }, save: { baccarat_experience: 'new_player' } } );
} );

test( 'pickMain: re-picking a plain answer that has a stray stored sub-answer clears it', () => {
	const stray = { main: 'streamed_some', sub: 'new_ready_to_learn' };
	assert.deepEqual( pickMain( question( 'streaming_experience' ), stray, 'streamed_some' ), { answer: { main: 'streamed_some', sub: '' }, save: { streaming_experience: 'streamed_some', streaming_learning_intent: null } } );
} );

test( 're-tapping the selected option sends no second write', async () => {
	const { option, posts, checked } = setup( { stage: 'streaming_experience' } );
	option( 'main', 'new_to_streaming' ).click();
	option( 'sub', 'new_maybe_later' ).click();
	option( 'main', 'new_to_streaming' ).click();
	option( 'sub', 'new_maybe_later' ).click();
	await flush();
	assert.deepEqual( posts, [ { answers: { streaming_experience: 'new_to_streaming' } }, { answers: { streaming_learning_intent: 'new_maybe_later' } } ] );
	assert.deepEqual( checked( 'main' ), [ 'new_to_streaming' ] );
	assert.deepEqual( checked( 'sub' ), [ 'new_maybe_later' ] );
} );

test( 'pickSub: only a sub-option of the picked answer is accepted, and re-picking it stores nothing', () => {
	const streaming = question( 'streaming_experience' );
	assert.deepEqual( pickSub( streaming, { main: 'streamed_some', sub: '' }, 'new_ready_to_learn' ), { answer: { main: 'streamed_some', sub: '' }, save: null } );
	assert.deepEqual( pickSub( streaming, { main: 'new_to_streaming', sub: '' }, 'owns_self_managed' ), { answer: { main: 'new_to_streaming', sub: '' }, save: null } );
	assert.deepEqual( pickSub( streaming, { main: '', sub: '' }, 'new_ready_to_learn' ), { answer: { main: '', sub: '' }, save: null } );
	assert.deepEqual( pickSub( streaming, { main: 'new_to_streaming', sub: 'new_ready_to_learn' }, 'new_ready_to_learn' ), { answer: { main: 'new_to_streaming', sub: 'new_ready_to_learn' }, save: null } );
	assert.deepEqual( pickSub( streaming, { main: 'new_to_streaming', sub: 'new_ready_to_learn' }, 'new_maybe_later' ), { answer: { main: 'new_to_streaming', sub: 'new_maybe_later' }, save: { streaming_learning_intent: 'new_maybe_later' } } );
} );

test( 'missingAnswer names the missing piece', () => {
	const equity = question( 'equity_knowledge' );
	assert.equal( missingAnswer( equity, { main: '', sub: '' } ), 'answerRequired' );
	assert.equal( missingAnswer( equity, { main: 'owns_equity', sub: '' } ), 'subAnswerRequired' );
	assert.equal( missingAnswer( equity, { main: 'owns_equity', sub: 'new_maybe_later' } ), 'subAnswerRequired' );
	assert.equal( missingAnswer( equity, { main: 'owns_equity', sub: 'owns_self_managed' } ), '' );
	assert.equal( missingAnswer( equity, { main: 'understands_equity', sub: '' } ), '' );
	assert.equal( missingAnswer( equity, { main: 'expert', sub: '' } ), 'answerRequired' );
} );

test( 'storedAnswer reads the store, ignoring anything that is not a string', () => {
	const streaming = question( 'streaming_experience' );
	assert.deepEqual( storedAnswer( streaming, { streaming_experience: 'new_to_streaming', streaming_learning_intent: 'new_ready_to_learn' } ), { main: 'new_to_streaming', sub: 'new_ready_to_learn' } );
	assert.deepEqual( storedAnswer( streaming, { streaming_experience: 3 } ), { main: '', sub: '' } );
	assert.deepEqual( storedAnswer( streaming, undefined ), { main: '', sub: '' } );
	assert.deepEqual( storedAnswer( question( 'active_followers' ), { active_follower_count: 'under_10k', streaming_learning_intent: 'x' } ), { main: 'under_10k', sub: '' } );
} );

test( 'the validation message follows the language', () => {
	const { engine, error, api, strings } = setup();
	strings.add( 'ja', { answerRequired: '回答を選んでください。' } );
	api.setLocale( 'ja' );
	engine.advance();
	assert.equal( error().textContent, '回答を選んでください。' );
	assert.equal( error().getAttribute( 'data-onb-string' ), 'answerRequired' );
} );

test( 'every label is a string-table key', () => {
	for ( const entry of QUESTIONS ) {
		assert.ok( entry.questionKey in QUESTION_STRINGS_EN );
		entry.options.forEach( ( option ) => {
			assert.ok( option.labelKey in QUESTION_STRINGS_EN, option.labelKey );
			( option.reveals ? option.reveals.options : [] ).forEach( ( sub ) => assert.ok( sub.labelKey in QUESTION_STRINGS_EN, sub.labelKey ) );
		} );
	}
} );

test( 'answer saves go out one at a time, in click order, each after the one before has answered', async () => {
	const pending = [];
	const respond = () => new Promise( ( resolve ) => pending.push( resolve ) );
	const { option, posts } = setup( { stage: 'streaming_experience', respond } );
	option( 'main', 'new_to_streaming' ).click();
	option( 'sub', 'new_ready_to_learn' ).click();
	option( 'main', 'streamed_some' ).click();
	await flush();
	assert.deepEqual( posts, [ { answers: { streaming_experience: 'new_to_streaming' } } ] );
	pending[ 0 ]( SAVED );
	await flush();
	assert.deepEqual( posts, [ { answers: { streaming_experience: 'new_to_streaming' } }, { answers: { streaming_learning_intent: 'new_ready_to_learn' } } ] );
	pending[ 1 ]( SAVED );
	await flush();
	assert.deepEqual( posts, [
		{ answers: { streaming_experience: 'new_to_streaming' } },
		{ answers: { streaming_learning_intent: 'new_ready_to_learn' } },
		{ answers: { streaming_experience: 'streamed_some', streaming_learning_intent: null } },
	] );
	assert.equal( pending.length, 3 );
} );

test( 'answers and the stage share the engine queue: answer, then stage, then the next answer, in click order', async () => {
	const pending = [];
	const respond = () => new Promise( ( resolve ) => pending.push( resolve ) );
	const { option, posts, engine } = setup( { stage: 'streaming_experience', respond } );
	option( 'main', 'streamed_a_lot' ).click();
	assert.equal( engine.advance(), true );
	option( 'main', 'understands_equity' ).click();
	await flush();
	assert.deepEqual( posts, [ { answers: { streaming_experience: 'streamed_a_lot' } } ] );
	pending[ 0 ]( SAVED );
	await flush();
	assert.deepEqual( posts, [ { answers: { streaming_experience: 'streamed_a_lot' } }, { stage: 'equity_knowledge' } ] );
	pending[ 1 ]( SAVED );
	await flush();
	assert.deepEqual( posts, [ { answers: { streaming_experience: 'streamed_a_lot' } }, { stage: 'equity_knowledge' }, { answers: { equity_knowledge: 'understands_equity' } } ] );
	pending[ 2 ]( SAVED );
	await flush();
	assert.equal( pending.length, 3 );
} );

test( 'a failed save is reported, keeps the choice on screen, and the next save still goes out', async () => {
	const { option, posts, errors, checked, engine } = setup( { respond: () => REJECTED } );
	option( 'main', 'played_some' ).click();
	await flush();
	assert.deepEqual( errors, [ 'Onboarding: saving progress failed (400)' ] );
	assert.deepEqual( checked( 'main' ), [ 'played_some' ] );
	option( 'main', 'new_player' ).click();
	await flush();
	assert.deepEqual( posts, [ { answers: { baccarat_experience: 'played_some' } }, { answers: { baccarat_experience: 'new_player' } } ] );
	assert.deepEqual( errors, [ 'Onboarding: saving progress failed (400)', 'Onboarding: saving progress failed (400)' ] );
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'streaming_experience' );
} );

test( 'each group has one Tab stop: the selected option, or the first when none is selected', () => {
	const { option, tabStops } = setup( { stage: 'streaming_experience' } );
	assert.deepEqual( tabStops( 'main' ), [ '0', '-1', '-1' ] );
	assert.deepEqual( tabStops( 'sub' ), [ '0', '-1' ] );
	option( 'main', 'new_to_streaming' ).click();
	assert.deepEqual( tabStops( 'main' ), [ '-1', '-1', '0' ] );
	option( 'sub', 'new_maybe_later' ).click();
	assert.deepEqual( tabStops( 'sub' ), [ '-1', '0' ] );
} );

test( 'arrow keys move the choice and focus to the next or previous option, wrapping around', async () => {
	const { option, checked, posts, document, tabStops } = setup( { stage: 'active_followers' } );
	const press = ( value, key ) => {
		const event = createEvent( 'keydown', { key } );
		option( 'main', value ).dispatchEvent( event );
		return event;
	};
	const down = press( '100k_plus', 'ArrowDown' );
	assert.equal( down.defaultPrevented, true );
	assert.deepEqual( checked( 'main' ), [ '50k_plus' ] );
	assert.equal( document.activeElement, option( 'main', '50k_plus' ) );
	assert.deepEqual( tabStops( 'main' ), [ '-1', '0', '-1', '-1', '-1' ] );
	press( '50k_plus', 'ArrowRight' );
	assert.deepEqual( checked( 'main' ), [ '25k_plus' ] );
	press( '25k_plus', 'ArrowUp' );
	assert.deepEqual( checked( 'main' ), [ '50k_plus' ] );
	press( '50k_plus', 'ArrowLeft' );
	assert.deepEqual( checked( 'main' ), [ '100k_plus' ] );
	press( '100k_plus', 'ArrowUp' );
	assert.deepEqual( checked( 'main' ), [ 'under_10k' ] );
	assert.equal( document.activeElement, option( 'main', 'under_10k' ) );
	press( 'under_10k', 'ArrowDown' );
	assert.deepEqual( checked( 'main' ), [ '100k_plus' ] );
	await flush();
	assert.deepEqual( posts.map( ( body ) => body.answers.active_follower_count ), [ '50k_plus', '25k_plus', '50k_plus', '100k_plus', 'under_10k', '100k_plus' ] );
} );

test( 'arrow keys in the revealed group pick a sub-option', async () => {
	const { option, checked, posts, document } = setup( { stage: 'equity_knowledge' } );
	option( 'main', 'owns_equity' ).click();
	option( 'sub', 'owns_self_managed' ).dispatchEvent( createEvent( 'keydown', { key: 'ArrowDown' } ) );
	assert.deepEqual( checked( 'sub' ), [ 'owns_managed_by_other' ] );
	assert.deepEqual( checked( 'main' ), [ 'owns_equity' ] );
	assert.equal( document.activeElement, option( 'sub', 'owns_managed_by_other' ) );
	await flush();
	assert.deepEqual( posts, [ { answers: { equity_knowledge: 'owns_equity' } }, { answers: { equity_management: 'owns_managed_by_other' } } ] );
} );

test( 'other keys, and keys on the group itself, change nothing', async () => {
	const { option, screen, checked, posts, document } = setup();
	for ( const key of [ 'Tab', 'Enter', ' ', 'a', 'Home' ] ) {
		const event = createEvent( 'keydown', { key } );
		option( 'main', 'played_some' ).dispatchEvent( event );
		assert.equal( event.defaultPrevented, false, key );
	}
	const onGroup = createEvent( 'keydown', { key: 'ArrowDown' } );
	screen().querySelector( 'div[data-group="main"]' ).dispatchEvent( onGroup );
	assert.equal( onGroup.defaultPrevented, false );
	assert.deepEqual( checked( 'main' ), [] );
	assert.equal( document.activeElement, null );
	await flush();
	assert.deepEqual( posts, [] );
} );

test( 'the revealed group is named by the option that reveals it, not by the question', () => {
	const cases = [
		{ stage: 'streaming_experience', value: 'new_to_streaming', label: "I'M NEW TO STREAMING" },
		{ stage: 'equity_knowledge', value: 'owns_equity', label: 'I CURRENTLY OWN EQUITY' },
	];
	for ( const { stage, value, label } of cases ) {
		const { screen, subGroup, option } = setup( { stage } );
		const labelId = 'ihq-onb-q-' + stage + '-' + value;
		assert.equal( subGroup().getAttribute( 'aria-labelledby' ), labelId );
		assert.equal( subGroup().getAttribute( 'role' ), 'radiogroup' );
		assert.equal( screen().querySelector( '#' + labelId ), option( 'main', value ).querySelector( '.ihq-onb-q__label' ) );
		assert.equal( screen().querySelector( '#' + labelId ).textContent, label );
		assert.equal( screen().querySelectorAll( '.ihq-onb-q__label[id]' ).length, 1 );
	}
} );

// The declarations of the one rule whose selector is exactly `selector`, in order.
function ruleDeclarations( css, selector ) {
	const rules = Array.from( css.matchAll( /([^{}]+)\{([^{}]*)\}/g ) ).filter( ( match ) => match[ 1 ].replace( /\/\*[\s\S]*?\*\//g, '' ).trim() === selector );
	assert.equal( rules.length, 1, 'one rule for ' + selector );
	const body = rules[ 0 ][ 2 ].replace( /\/\*[\s\S]*?\*\//g, '' );
	return body.split( ';' ).map( ( declaration ) => declaration.trim() ).filter( ( declaration ) => '' !== declaration );
}

test( 'the question and its answers share one type size, at every width', () => {
	const css = readFileSync( new URL( '../../css/portal-onboarding-questions.css', import.meta.url ), 'utf8' );
	assert.equal( css.includes( '@media' ), false );
	assert.equal( css.includes( 'diamond' ), false );
	const tokens = ruleDeclarations( css, '.ihq-onb' ).filter( ( declaration ) => /^--ihq-onb-q-(font-size|line-height):/.test( declaration ) );
	assert.deepEqual( tokens, [ '--ihq-onb-q-font-size: 14px', '--ihq-onb-q-line-height: 18px' ] );
	const typeOf = ( selector ) => ruleDeclarations( css, selector ).filter( ( declaration ) => /^(font-size|font-weight|line-height):/.test( declaration ) );
	const shared = [ 'font-size: var(--ihq-onb-q-font-size)', 'font-weight: 800', 'line-height: var(--ihq-onb-q-line-height)' ];
	assert.deepEqual( typeOf( '.ihq-onb-q__question' ), shared );
	assert.deepEqual( typeOf( '.ihq-onb-q__option' ), shared );
} );

test( 'a pick advances the screen after the pause, not before', async () => {
	assert.equal( PICK_ADVANCE_DELAY_MS, 500 );
	for ( const entry of QUESTIONS ) {
		const plain = entry.options.find( ( candidate ) => ! candidate.reveals );
		const { option, engine, clock, posts } = setup( { stage: entry.id, stubs: [ 'equity_example' ] } );
		option( 'main', plain.value ).click();
		assert.equal( clock.pendingCount(), 1, entry.id );
		clock.tick( PICK_ADVANCE_DELAY_MS - 1 );
		assert.equal( engine.current(), entry.id );
		clock.tick( 1 );
		const next = nextStage( { current: entry.id, isAvailable: ( id ) => 'equity_example' === id || QUESTIONS.some( ( candidate ) => candidate.id === id ) } );
		assert.equal( engine.current(), next, entry.id );
		assert.equal( clock.pendingCount(), 0 );
		await flush();
		assert.deepEqual( posts, [ { answers: { [ entry.field ]: plain.value } }, { stage: next } ], entry.id );
	}
} );

test( 'screens 4 and 5: a pick that reveals the follow-up does not advance; the follow-up pick does', async () => {
	const cases = [
		{ stage: 'streaming_experience', reveal: 'new_to_streaming', follow: 'new_maybe_later', field: 'streaming_experience', subField: 'streaming_learning_intent', next: 'equity_knowledge' },
		{ stage: 'equity_knowledge', reveal: 'owns_equity', follow: 'owns_self_managed', field: 'equity_knowledge', subField: 'equity_management', next: 'active_followers' },
	];
	for ( const { stage, reveal, follow, field, subField, next } of cases ) {
		const { option, engine, clock, posts, error } = setup( { stage } );
		option( 'main', reveal ).click();
		assert.equal( clock.pendingCount(), 0, stage );
		clock.tick( PICK_ADVANCE_DELAY_MS * 4 );
		assert.equal( engine.current(), stage );
		assert.equal( error().hidden, true, 'waiting for the follow-up shows no error' );
		option( 'sub', follow ).click();
		clock.tick( PICK_ADVANCE_DELAY_MS - 1 );
		assert.equal( engine.current(), stage );
		clock.tick( 1 );
		assert.equal( engine.current(), next );
		await flush();
		assert.deepEqual( posts, [ { answers: { [ field ]: reveal } }, { answers: { [ subField ]: follow } }, { stage: next } ], stage );
	}
} );

test( 'a plain pick then a revealing pick during the pause cancels the advance', () => {
	const { option, engine, clock } = setup( { stage: 'streaming_experience' } );
	option( 'main', 'streamed_some' ).click();
	clock.tick( PICK_ADVANCE_DELAY_MS - 100 );
	option( 'main', 'new_to_streaming' ).click();
	assert.equal( clock.pendingCount(), 0 );
	clock.tick( PICK_ADVANCE_DELAY_MS * 4 );
	assert.equal( engine.current(), 'streaming_experience' );
} );

test( 're-picking the same answer during the pause neither saves nor restarts the pause', async () => {
	const { option, engine, clock, posts } = setup();
	option( 'main', 'played_some' ).click();
	clock.tick( PICK_ADVANCE_DELAY_MS - 100 );
	option( 'main', 'played_some' ).click();
	assert.equal( clock.pendingCount(), 1 );
	clock.tick( 100 );
	assert.equal( engine.current(), 'streaming_experience' );
	assert.equal( clock.pendingCount(), 0 );
	await flush();
	assert.deepEqual( posts, [ { answers: { baccarat_experience: 'played_some' } }, { stage: 'streaming_experience' } ] );
} );

test( 're-picking the follow-up during the pause keeps the one pause', () => {
	const { option, engine, clock } = setup( { stage: 'equity_knowledge' } );
	option( 'main', 'owns_equity' ).click();
	option( 'sub', 'owns_self_managed' ).click();
	clock.tick( PICK_ADVANCE_DELAY_MS - 100 );
	option( 'sub', 'owns_self_managed' ).click();
	option( 'main', 'owns_equity' ).click();
	assert.equal( clock.pendingCount(), 1 );
	clock.tick( 100 );
	assert.equal( engine.current(), 'active_followers' );
} );

test( 'a returning Influencer re-picking their saved answer moves on after the pause, with no answer save', async () => {
	const { option, engine, clock, posts } = setup( { stage: 'streaming_experience', answers: { streaming_experience: 'new_to_streaming', streaming_learning_intent: 'new_maybe_later' } } );
	assert.equal( clock.pendingCount(), 0, 'a saved answer alone does not advance' );
	option( 'sub', 'new_maybe_later' ).click();
	clock.tick( PICK_ADVANCE_DELAY_MS - 1 );
	assert.equal( engine.current(), 'streaming_experience' );
	clock.tick( 1 );
	assert.equal( engine.current(), 'equity_knowledge' );
	await flush();
	assert.deepEqual( posts, [ { stage: 'equity_knowledge' } ] );
} );

test( 'a re-pick of a half-answered revealing option does not advance', () => {
	const { option, engine, clock } = setup( { stage: 'equity_knowledge', answers: { equity_knowledge: 'owns_equity' } } );
	option( 'main', 'owns_equity' ).click();
	assert.equal( clock.pendingCount(), 0 );
	clock.tick( PICK_ADVANCE_DELAY_MS * 4 );
	assert.equal( engine.current(), 'equity_knowledge' );
} );

test( 'changing the pick during the pause saves it and restarts the pause', async () => {
	const { option, engine, clock, posts, checked } = setup();
	option( 'main', 'played_some' ).click();
	clock.tick( PICK_ADVANCE_DELAY_MS - 100 );
	option( 'main', 'new_player' ).click();
	assert.deepEqual( checked( 'main' ), [ 'new_player' ] );
	assert.equal( clock.pendingCount(), 1 );
	clock.tick( PICK_ADVANCE_DELAY_MS - 1 );
	assert.equal( engine.current(), 'baccarat_experience' );
	clock.tick( 1 );
	assert.equal( engine.current(), 'streaming_experience' );
	await flush();
	assert.deepEqual( posts, [ { answers: { baccarat_experience: 'played_some' } }, { answers: { baccarat_experience: 'new_player' } }, { stage: 'streaming_experience' } ] );
} );

test( 'an arrow-key move is a pick: it advances after the pause, and each further move restarts it', async () => {
	const { option, engine, clock, posts } = setup( { stage: 'active_followers', stubs: [ 'equity_example' ] } );
	const press = ( value, key ) => option( 'main', value ).dispatchEvent( createEvent( 'keydown', { key } ) );
	press( '100k_plus', 'ArrowDown' );
	clock.tick( PICK_ADVANCE_DELAY_MS - 100 );
	press( '50k_plus', 'ArrowDown' );
	clock.tick( PICK_ADVANCE_DELAY_MS - 100 );
	press( '25k_plus', 'ArrowDown' );
	assert.equal( clock.pendingCount(), 1 );
	clock.tick( PICK_ADVANCE_DELAY_MS - 1 );
	assert.equal( engine.current(), 'active_followers' );
	clock.tick( 1 );
	assert.equal( engine.current(), 'equity_example' );
	await flush();
	assert.deepEqual( posts, [
		{ answers: { active_follower_count: '50k_plus' } },
		{ answers: { active_follower_count: '25k_plus' } },
		{ answers: { active_follower_count: '10k_plus' } },
		{ stage: 'equity_example' },
	] );
} );

test( 'leaving the screen during the pause clears the timer (destroy), so nothing fires later', () => {
	const { option, engine, clock } = setup();
	option( 'main', 'played_some' ).click();
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'streaming_experience' );
	assert.equal( clock.pendingCount(), 0 );
	clock.tick( PICK_ADVANCE_DELAY_MS * 4 );
	assert.equal( engine.current(), 'streaming_experience' );
} );

test( 'destroy() cancels the pending advance', () => {
	const clock = createFakeClock();
	const document = createFakeDocument();
	const container = document.createElement( 'section' );
	document.body.append( container );
	const strings = createStringTable();
	strings.add( 'en', QUESTION_STRINGS_EN );
	const api = { state: { answers: {} }, strings, getLocale: () => 'en', save: () => Promise.resolve( true ) };
	const text = ( tag, key, className ) => {
		const element = document.createElement( tag );
		element.textContent = strings.t( 'en', key );
		element.className = className || '';
		return element;
	};
	const advances = [];
	const [ baccarat ] = questionScreens( { advanceModes: ADVANCE_MODES, timers: clock } );
	const controller = baccarat.render( { container, api, t: ( key ) => strings.t( 'en', key ), text, advance: () => advances.push( 'advance' ) } );
	container.querySelector( 'button[data-value="played_some"]' ).click();
	assert.equal( clock.pendingCount(), 1 );
	controller.destroy();
	assert.equal( clock.pendingCount(), 0 );
	clock.tick( PICK_ADVANCE_DELAY_MS * 4 );
	assert.deepEqual( advances, [] );
} );

test( 'after a failed save the screen still advances after the pause', async () => {
	const { option, engine, clock, errors } = setup( { respond: () => REJECTED } );
	option( 'main', 'played_some' ).click();
	await flush();
	assert.deepEqual( errors, [ 'Onboarding: saving progress failed (400)' ] );
	clock.tick( PICK_ADVANCE_DELAY_MS );
	assert.equal( engine.current(), 'streaming_experience' );
} );
