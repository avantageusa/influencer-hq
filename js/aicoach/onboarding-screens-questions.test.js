import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createFakeDocument } from './fake-dom.js';
import { createEmitter } from './onboarding-events.js';
import { createStringTable } from './onboarding-strings.js';
import { createApi } from './onboarding-boot.js';
import { SEQUENCE, startStage, nextStage } from './onboarding-sequence.js';
import { createEngine, ENGINE_STRINGS_EN } from './onboarding-engine.js';
import {
	QUESTION_STRINGS_EN,
	QUESTIONS,
	pickMain,
	pickSub,
	missingAnswer,
	storedAnswer,
	questionScreens,
	install,
} from './onboarding-screens-questions.js';

const STATE = { stage: '', answers: {}, platforms: [], handles: {}, language: '', completed: false, completed_at: '' };
const question = ( id ) => QUESTIONS.find( ( entry ) => entry.id === id );

// The engine with this module installed, on the fake DOM; posts are recorded.
function setup( { stage = 'baccarat_experience', answers = {} } = {} ) {
	const document = createFakeDocument();
	const content = document.createElement( 'div' );
	document.body.append( content );
	const events = createEmitter();
	const strings = createStringTable();
	strings.add( 'en', ENGINE_STRINGS_EN );
	const api = createApi( { config: { state: { ...STATE, stage, answers }, progressUrl: '/p', completeUrl: '/c', nonce: 'n' }, events, strings } );
	api.shell = { content, say: () => Promise.resolve(), close: () => {} };
	const posts = [];
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
	} );
	api.screens = { register: engine.register };
	api.save = engine.save;
	install( api );
	engine.start();
	const screen = () => content.querySelector( 'section' );
	const option = ( group, value ) => screen().querySelector( `button[data-group="${ group }"][data-value="${ value }"]` );
	const checked = ( group ) => screen().querySelectorAll( `button[data-group="${ group }"]` ).filter( ( button ) => 'true' === button.getAttribute( 'aria-checked' ) ).map( ( button ) => button.getAttribute( 'data-value' ) );
	const subGroup = () => screen().querySelector( 'div[data-group="sub"]' );
	const error = () => screen().querySelector( 'p.ihq-onb__error' );
	return { api, engine, posts, strings, screen, option, checked, subGroup, error };
}

const labels = ( screen, group ) => screen.querySelectorAll( `button[data-group="${ group }"]` ).map( ( button ) => button.querySelector( '.ihq-onb-q__label' ).textContent );

test( 'registers screens 3, 4, 5 and 12', () => {
	assert.deepEqual( questionScreens().map( ( definition ) => definition.id ), [ 'baccarat_experience', 'streaming_experience', 'equity_knowledge', 'active_followers' ] );
	const registered = [];
	const strings = createStringTable();
	install( { strings, screens: { register: ( definition ) => registered.push( definition.id ) } } );
	assert.deepEqual( registered, [ 'baccarat_experience', 'streaming_experience', 'equity_knowledge', 'active_followers' ] );
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

test( 'screen 3: the question, three options with 3/2/1 diamonds, the stored values', () => {
	const { screen } = setup();
	assert.equal( screen().querySelector( 'h2' ).textContent, 'What is your experience with Baccarat?' );
	assert.deepEqual( labels( screen(), 'main' ), [ "I'VE PLAYED A LOT", "I'VE PLAYED SOME", "I'M NEW" ] );
	const lit = screen().querySelectorAll( 'button[data-group="main"]' ).map( ( button ) => button.querySelectorAll( '.is-on' ).length );
	assert.deepEqual( lit, [ 3, 2, 1 ] );
	screen().querySelectorAll( '.ihq-onb-q__diamonds' ).forEach( ( level ) => {
		assert.equal( level.getAttribute( 'aria-hidden' ), 'true' );
		assert.equal( level.children.length, 3 );
	} );
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

test( 'every option of every screen is stored as its enum value when picked', () => {
	for ( const entry of QUESTIONS ) {
		for ( const option of entry.options ) {
			const { option: find, posts, checked } = setup( { stage: entry.id } );
			find( 'main', option.value ).click();
			assert.deepEqual( posts, [ { answers: { [ entry.field ]: option.value } } ], option.value );
			assert.deepEqual( checked( 'main' ), [ option.value ] );
		}
	}
} );

test( 'none of the four screens advances without a selection, and each shows why', () => {
	for ( const entry of QUESTIONS ) {
		const { engine, error, posts } = setup( { stage: entry.id } );
		assert.equal( error().hidden, true );
		assert.equal( engine.advance(), false, entry.id );
		assert.equal( engine.current(), entry.id );
		assert.equal( error().hidden, false );
		assert.equal( error().textContent, 'Choose an answer to continue.' );
		assert.equal( error().getAttribute( 'role' ), 'alert' );
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

test( 'screen 4: the revealed sub-option is required, then stored', () => {
	const { engine, option, subGroup, error, posts, checked } = setup( { stage: 'streaming_experience' } );
	option( 'main', 'new_to_streaming' ).click();
	assert.equal( subGroup().hidden, false );
	assert.equal( engine.advance(), false );
	assert.equal( error().textContent, 'Choose one of the options below your answer to continue.' );
	option( 'sub', 'new_ready_to_learn' ).click();
	assert.deepEqual( checked( 'sub' ), [ 'new_ready_to_learn' ] );
	assert.deepEqual( posts, [ { answers: { streaming_experience: 'new_to_streaming' } }, { answers: { streaming_learning_intent: 'new_ready_to_learn' } } ] );
	assert.equal( engine.advance(), true );
} );

test( 'screen 4: switching away from I\'M NEW TO STREAMING hides and clears the sub-answer', () => {
	const { option, subGroup, posts, checked, engine } = setup( { stage: 'streaming_experience' } );
	option( 'main', 'new_to_streaming' ).click();
	option( 'sub', 'new_maybe_later' ).click();
	option( 'main', 'streamed_some' ).click();
	assert.equal( subGroup().hidden, true );
	assert.deepEqual( checked( 'sub' ), [] );
	assert.deepEqual( posts.at( -1 ), { answers: { streaming_experience: 'streamed_some', streaming_learning_intent: null } } );
	option( 'main', 'new_to_streaming' ).click();
	assert.equal( subGroup().hidden, false );
	assert.deepEqual( checked( 'sub' ), [], 'the cleared sub-answer does not come back' );
	assert.equal( engine.advance(), false );
} );

test( 'screen 5: owns equity requires managing, and the stored values', () => {
	const { option, posts, engine } = setup( { stage: 'equity_knowledge' } );
	option( 'main', 'owns_equity' ).click();
	assert.equal( engine.advance(), false );
	option( 'sub', 'owns_self_managed' ).click();
	option( 'sub', 'owns_managed_by_other' ).click();
	assert.deepEqual( posts, [
		{ answers: { equity_knowledge: 'owns_equity' } },
		{ answers: { equity_management: 'owns_self_managed' } },
		{ answers: { equity_management: 'owns_managed_by_other' } },
	] );
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'active_followers' );
} );

test( 'screen 5: switching away from owning equity clears the management answer', () => {
	const { option, posts, subGroup } = setup( { stage: 'equity_knowledge' } );
	option( 'main', 'owns_equity' ).click();
	option( 'sub', 'owns_self_managed' ).click();
	option( 'main', 'interested_in_equity' ).click();
	assert.equal( subGroup().hidden, true );
	assert.deepEqual( posts.at( -1 ), { answers: { equity_knowledge: 'interested_in_equity', equity_management: null } } );
} );

test( 'switching between two plain answers does not send a sub-answer', () => {
	const { option, posts } = setup( { stage: 'equity_knowledge' } );
	option( 'main', 'understands_equity' ).click();
	option( 'main', 'interested_in_equity' ).click();
	assert.deepEqual( posts, [ { answers: { equity_knowledge: 'understands_equity' } }, { answers: { equity_knowledge: 'interested_in_equity' } } ] );
} );

test( 'a returning Influencer sees their saved answers and can advance', () => {
	const { checked, subGroup, engine } = setup( { stage: 'streaming_experience', answers: { streaming_experience: 'new_to_streaming', streaming_learning_intent: 'new_maybe_later' } } );
	assert.deepEqual( checked( 'main' ), [ 'new_to_streaming' ] );
	assert.deepEqual( checked( 'sub' ), [ 'new_maybe_later' ] );
	assert.equal( subGroup().hidden, false );
	assert.equal( engine.advance(), true );
} );

test( 'clicking between the options (not on one) does nothing', () => {
	const { screen, posts } = setup();
	screen().querySelector( 'div[data-group="main"]' ).click();
	assert.deepEqual( posts, [] );
} );

test( 'pickMain: unknown values change nothing; re-picking the revealing option keeps the sub-answer', () => {
	const streaming = question( 'streaming_experience' );
	const answer = { main: 'new_to_streaming', sub: 'new_maybe_later' };
	assert.deepEqual( pickMain( streaming, answer, 'nonsense' ), { answer, save: null } );
	assert.deepEqual( pickMain( streaming, answer, 'new_to_streaming' ), { answer: { main: 'new_to_streaming', sub: 'new_maybe_later' }, save: { streaming_experience: 'new_to_streaming' } } );
	assert.deepEqual( pickMain( question( 'baccarat_experience' ), { main: '', sub: '' }, 'new_player' ), { answer: { main: 'new_player', sub: '' }, save: { baccarat_experience: 'new_player' } } );
} );

test( 'pickSub: only a sub-option of the picked answer is accepted', () => {
	const streaming = question( 'streaming_experience' );
	assert.deepEqual( pickSub( streaming, { main: 'streamed_some', sub: '' }, 'new_ready_to_learn' ), { answer: { main: 'streamed_some', sub: '' }, save: null } );
	assert.deepEqual( pickSub( streaming, { main: 'new_to_streaming', sub: '' }, 'owns_self_managed' ), { answer: { main: 'new_to_streaming', sub: '' }, save: null } );
	assert.deepEqual( pickSub( streaming, { main: '', sub: '' }, 'new_ready_to_learn' ), { answer: { main: '', sub: '' }, save: null } );
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
