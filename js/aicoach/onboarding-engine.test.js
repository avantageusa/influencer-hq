import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeDocument } from './fake-dom.js';
import { createEmitter } from './onboarding-events.js';
import { createStringTable } from './onboarding-strings.js';
import { createApi } from './onboarding-boot.js';
import { SEQUENCE, startStage, nextStage } from './onboarding-sequence.js';
import { createFakeClock } from './fake-clock.js';
import { createStallWatchdog } from './stall-watchdog.js';
import { buildSentenceCues, subtitleAt } from './onboarding-subtitles.js';
import { estimateDurationMs } from './commentary.js';
import { createNarrator } from './onboarding-narrator.js';
import { createEngine, ADVANCE_MODES } from './onboarding-engine.js';

const STATE = { stage: '', answers: {}, platforms: [], handles: {}, language: '', completed: false, completed_at: '' };

function setup( { stage = '', clips = {}, saveResult = { ok: true, status: 200, data: null }, completeResult = { ok: true, status: 200, data: { completed: true } }, answer } = {} ) {
	const document = createFakeDocument();
	const content = document.createElement( 'div' );
	document.body.append( content );
	const events = createEmitter();
	const strings = createStringTable();
	strings.add( 'en', { kingsLine: 'For over 500 years.', askLine: 'Tell me.' } );
	strings.add( 'ja', { kingsLine: '五百年。', askLine: '教えて。' } );
	const config = { state: { ...STATE, stage }, progressUrl: '/progress', completeUrl: '/complete', nonce: 'n1', clips };
	const api = createApi( { config, events, strings } );
	const said = [];
	// endLine[ n ]( mode ) settles the nth line Sami was asked to say, as the narrator does
	// when the line has been said or was stopped. Until then it is still playing.
	const endLine = [];
	const closes = [];
	api.shell = {
		content,
		say: ( line ) => {
			said.push( line );
			return new Promise( ( resolve ) => endLine.push( ( mode = 'captions' ) => resolve( { mode } ) ) );
		},
		close: () => closes.push( true ),
	};
	const posts = [];
	// answer( request ), when given, decides when and how each request is answered.
	const postJson = async ( request ) => {
		posts.push( request );
		if ( answer ) {
			return answer( request );
		}
		return '/complete' === request.url ? completeResult : saveResult;
	};
	const guardCalls = [];
	const historyGuard = { install: () => guardCalls.push( 'install' ), uninstall: () => guardCalls.push( 'uninstall' ) };
	const errors = [];
	const fetchFn = () => {};
	const engine = createEngine( {
		api,
		sequence: { SEQUENCE, startStage, nextStage },
		document,
		postJson,
		fetchFn,
		historyGuard,
		onError: ( error ) => errors.push( error.message ),
	} );
	return { document, content, events, api, engine, said, endLine, closes, posts, guardCalls, errors, fetchFn };
}

// A screen that records its lifecycle. valid() decides whether it lets the flow advance;
// advancers[ id ] is the advance() its render context got, for the latest showing.
function screen( id, log, { valid, choice, narration, advanceMode, advancers = {} } = {} ) {
	return {
		id,
		narration,
		advanceMode,
		render( { container, t, text, advance } ) {
			log.push( [ 'render', id ] );
			advancers[ id ] = advance;
			container.append( text( 'h2', 'askLine', 'title' ) );
			container.setAttribute( 'data-seen-line', t( 'askLine' ) );
			return {
				isValid: valid ? () => valid() : undefined,
				showValidation: () => log.push( [ 'validation', id ] ),
				choice: choice ? () => choice() : undefined,
				destroy: () => log.push( [ 'destroy', id ] ),
			};
		},
	};
}

function registerAll( engine, log, overrides = {} ) {
	SEQUENCE.forEach( ( id ) => engine.register( screen( id, log, overrides[ id ] ) ) );
}

const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

test( 'the advance modes are named and frozen', () => {
	assert.deepEqual( ADVANCE_MODES, { NARRATION: 'narration', PICK: 'pick', MANUAL: 'manual' } );
	assert.equal( Object.isFrozen( ADVANCE_MODES ), true );
} );

test( 'register() rejects a definition with an unknown id or no render()', () => {
	const { engine } = setup();
	assert.throws( () => engine.register( { id: 'payout', render: () => ( {} ) } ), /invalid screen definition payout/ );
	assert.throws( () => engine.register( { id: 'equity' } ), /invalid screen definition equity/ );
	assert.throws( () => engine.register( null ), /invalid screen definition/ );
} );

test( 'register() rejects an unknown advance mode, and registers nothing', () => {
	const { engine } = setup();
	assert.throws( () => engine.register( { id: 'equity', advanceMode: 'continue', render: () => ( {} ) } ), /^Error: Onboarding: unknown advance mode continue for equity$/ );
	assert.throws( () => engine.register( { id: 'equity', advanceMode: null, render: () => ( {} ) } ), /unknown advance mode null for equity/ );
	assert.equal( engine.start(), null );
} );

test( 'register() accepts every named advance mode', () => {
	const { engine } = setup();
	Object.values( ADVANCE_MODES ).forEach( ( advanceMode ) => engine.register( { id: 'equity', advanceMode, render: () => ( {} ) } ) );
	assert.equal( engine.start(), 'equity' );
} );

test( 'start() with no screens installed shows nothing and does not guard history', () => {
	const { engine, guardCalls, said } = setup();
	assert.equal( engine.start(), null );
	assert.equal( engine.current(), null );
	assert.deepEqual( guardCalls, [] );
	assert.deepEqual( said, [] );
	assert.equal( engine.advance(), false );
} );

test( 'a new user starts at screen 1, with the history guard on', () => {
	const { engine, content, guardCalls } = setup();
	const log = [];
	registerAll( engine, log );
	assert.equal( engine.start(), 'game_of_kings' );
	assert.deepEqual( guardCalls, [ 'install' ] );
	assert.deepEqual( log, [ [ 'render', 'game_of_kings' ] ] );
	const section = content.querySelector( 'section' );
	assert.equal( section.getAttribute( 'data-stage' ), 'game_of_kings' );
	assert.equal( section.className, 'ihq-onb-screen' );
} );

test( 'a returning user resumes at the saved stage', () => {
	const { engine } = setup( { stage: 'world_tour' } );
	registerAll( engine, [] );
	assert.equal( engine.start(), 'world_tour' );
} );

test( 'the screen draws into its section, and the engine adds nothing of its own (no CONTINUE)', () => {
	const { engine, content } = setup();
	registerAll( engine, [] );
	engine.start();
	const section = content.querySelector( 'section' );
	assert.equal( section.children.length, 1 );
	assert.equal( section.querySelector( 'button' ), null );
	assert.equal( section.querySelector( 'h2' ).getAttribute( 'data-onb-string' ), 'askLine' );
	assert.equal( section.querySelector( 'h2' ).textContent, 'Tell me.' );
	assert.equal( section.querySelector( 'h2' ).className, 'title' );
} );

test( 'the screen text follows the current language', () => {
	const { engine, content, api } = setup();
	api.setLocale( 'ja' );
	registerAll( engine, [] );
	engine.start();
	assert.equal( content.querySelector( 'h2' ).textContent, '教えて。' );
	assert.equal( content.querySelector( 'section' ).getAttribute( 'data-seen-line' ), '教えて。' );
} );

test( 'advancing moves exactly one screen forward and saves the stage', async () => {
	const { engine, posts, fetchFn, content } = setup();
	const log = [];
	registerAll( engine, log );
	engine.start();
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'modern_era' );
	assert.deepEqual( log, [ [ 'render', 'game_of_kings' ], [ 'destroy', 'game_of_kings' ], [ 'render', 'modern_era' ] ] );
	assert.equal( content.querySelectorAll( 'section' ).length, 1 );
	await flush();
	assert.deepEqual( posts, [ { url: '/progress', nonce: 'n1', body: { stage: 'modern_era' }, fetchFn } ] );
} );

test( 'walking the whole flow visits every screen once, in order, and stops at closing', async () => {
	const { engine, posts } = setup();
	const log = [];
	registerAll( engine, log );
	engine.start();
	while ( engine.advance() ) {
		// keep going
	}
	assert.deepEqual( log.filter( ( entry ) => 'render' === entry[ 0 ] ).map( ( entry ) => entry[ 1 ] ), SEQUENCE );
	assert.equal( engine.current(), 'closing' );
	await flush();
	assert.deepEqual( posts.map( ( post ) => post.body.stage ), SEQUENCE.slice( 1 ) );
} );

test( 'stage saves are sent one at a time, in order, and the last answer is the state kept', async () => {
	const answers = [];
	const answer = ( request ) => new Promise( ( resolve ) => {
		answers.push( () => resolve( { ok: true, status: 200, data: { ...STATE, stage: request.body.stage } } ) );
	} );
	const { engine, api, posts } = setup( { answer } );
	registerAll( engine, [] );
	engine.start();
	assert.equal( engine.advance(), true );
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'baccarat_experience', 'advancing does not wait for the save' );
	await flush();
	assert.deepEqual( posts.map( ( post ) => post.body ), [ { stage: 'modern_era' } ], 'the second save waits for the first' );
	answers[ 0 ]();
	await flush();
	assert.deepEqual( posts.map( ( post ) => post.body ), [ { stage: 'modern_era' }, { stage: 'baccarat_experience' } ] );
	assert.deepEqual( api.state, { ...STATE, stage: 'modern_era' } );
	answers[ 1 ]();
	await flush();
	assert.deepEqual( api.state, { ...STATE, stage: 'baccarat_experience' } );
} );

// Answers each progress POST only when the test says so: held[ n ]( result ) answers the nth.
function holdSaves() {
	const held = [];
	const answer = ( request ) => new Promise( ( resolve ) => {
		held.push( ( result ) => resolve( result || { ok: true, status: 200, data: { ...STATE, ...request.body } } ) );
	} );
	return { held, answer };
}

test( 'an answer save waits behind a stage save still in flight', async () => {
	const { held, answer } = holdSaves();
	const { engine, api, posts } = setup( { answer } );
	registerAll( engine, [] );
	engine.start();
	engine.advance();
	const answerSaved = engine.save( { answers: { baccarat_experience: 'played_some' } } );
	await flush();
	assert.deepEqual( posts.map( ( post ) => post.body ), [ { stage: 'modern_era' } ] );
	held[ 0 ]();
	await flush();
	assert.deepEqual( posts.map( ( post ) => post.body ), [ { stage: 'modern_era' }, { answers: { baccarat_experience: 'played_some' } } ] );
	held[ 1 ]();
	assert.equal( await answerSaved, true );
	assert.deepEqual( api.state, { ...STATE, answers: { baccarat_experience: 'played_some' } } );
} );

test( 'a stage save from an advance waits behind an answer save still in flight', async () => {
	const { held, answer } = holdSaves();
	const { engine, api, posts } = setup( { stage: 'baccarat_experience', answer } );
	registerAll( engine, [] );
	engine.start();
	const answerSaved = engine.save( { answers: { baccarat_experience: 'played_some' } } );
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'streaming_experience', 'advancing does not wait for the saves' );
	await flush();
	assert.deepEqual( posts.map( ( post ) => post.body ), [ { answers: { baccarat_experience: 'played_some' } } ] );
	held[ 0 ]();
	assert.equal( await answerSaved, true );
	await flush();
	assert.deepEqual( posts.map( ( post ) => post.body ), [ { answers: { baccarat_experience: 'played_some' } }, { stage: 'streaming_experience' } ] );
	held[ 1 ]();
	await flush();
	assert.deepEqual( api.state, { ...STATE, stage: 'streaming_experience' } );
} );

test( 'a failed save answers false to its caller, is reported, and the next save still goes out', async () => {
	const { held, answer } = holdSaves();
	const { engine, api, posts, errors } = setup( { answer } );
	const failed = engine.save( { answers: { baccarat_experience: 'played_some' } } );
	const next = engine.save( { answers: { baccarat_experience: 'played_lots' } } );
	await flush();
	held[ 0 ]( { ok: false, status: 500, data: null } );
	assert.equal( await failed, false );
	await flush();
	assert.deepEqual( posts.map( ( post ) => post.body ), [ { answers: { baccarat_experience: 'played_some' } }, { answers: { baccarat_experience: 'played_lots' } } ] );
	held[ 1 ]();
	assert.equal( await next, true );
	assert.deepEqual( errors, [ 'Onboarding: saving progress failed (500)' ] );
	assert.deepEqual( api.state, { ...STATE, answers: { baccarat_experience: 'played_lots' } } );
} );

test( 'a save that rejects rejects only for its caller and does not block the queue', async () => {
	const answers = [
		() => Promise.reject( new Error( 'boom' ) ),
		() => Promise.resolve( { ok: true, status: 200, data: null } ),
	];
	const { engine, posts } = setup( { answer: () => answers.shift()() } );
	const first = engine.save( { stage: 'modern_era' } );
	const second = engine.save( { stage: 'baccarat_experience' } );
	await assert.rejects( first, /boom/ );
	assert.equal( await second, true );
	assert.deepEqual( posts.map( ( post ) => post.body ), [ { stage: 'modern_era' }, { stage: 'baccarat_experience' } ] );
} );

test( 'complete() is still sent at once, without waiting for the save queue', () => {
	const { held, answer } = holdSaves();
	const { engine, posts } = setup( { stage: 'closing', answer } );
	registerAll( engine, [] );
	engine.start();
	engine.complete();
	assert.deepEqual( posts.map( ( post ) => post.url ), [ '/complete' ] );
	assert.equal( held.length, 1 );
} );

test( 'a question screen with no valid answer does not advance and shows its validation state', async () => {
	const { engine, posts } = setup( { stage: 'baccarat_experience' } );
	const log = [];
	let answered = false;
	registerAll( engine, log, { baccarat_experience: { valid: () => answered } } );
	engine.start();
	assert.equal( engine.advance(), false );
	assert.equal( engine.current(), 'baccarat_experience' );
	assert.deepEqual( log, [ [ 'render', 'baccarat_experience' ], [ 'validation', 'baccarat_experience' ] ] );
	await flush();
	assert.deepEqual( posts, [] );
	answered = true;
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'streaming_experience' );
} );

test( 'an invalid screen with no showValidation still does not advance', () => {
	const { engine } = setup( { stage: 'baccarat_experience' } );
	engine.register( {
		id: 'baccarat_experience',
		render: () => ( { isValid: () => false } ),
	} );
	engine.register( screen( 'streaming_experience', [] ) );
	engine.start();
	assert.equal( engine.advance(), false );
} );

test( 'the post action edit option returns to the drafts; the other two go to closing', async () => {
	for ( const [ choice, expected ] of [ [ 'edit_together', 'draft_posts' ], [ 'post_now', 'closing' ], [ 'edit_later', 'closing' ] ] ) {
		const { engine, posts } = setup( { stage: 'post_action' } );
		registerAll( engine, [], { post_action: { choice: () => choice } } );
		engine.start();
		engine.advance();
		assert.equal( engine.current(), expected, choice );
		await flush();
		assert.deepEqual( posts.map( ( post ) => post.body ), [ { stage: expected } ] );
	}
} );

test( 'screens that are not installed (drafts, post action) are passed over', () => {
	const { engine } = setup( { stage: 'equity_example' } );
	SEQUENCE.filter( ( id ) => ! [ 'draft_posts', 'post_action' ].includes( id ) ).forEach( ( id ) => engine.register( screen( id, [] ) ) );
	engine.start();
	engine.advance();
	assert.equal( engine.current(), 'closing' );
} );

test( 'Sami says the screen line with the clip in the current language, captions when none', () => {
	const { engine, said, api } = setup( { clips: { game_of_kings: { en: 'kings-en.mp4', ja: 'kings-ja.mp4' } } } );
	registerAll( engine, [], { game_of_kings: { narration: 'kingsLine' } } );
	engine.start();
	assert.deepEqual( said, [ { script: 'For over 500 years.', clipUrl: 'kings-en.mp4' } ] );
	engine.advance();
	assert.deepEqual( said[ 1 ], { script: '', clipUrl: '' } );
	api.setLocale( 'ja' );
	assert.deepEqual( said[ 2 ], { script: '', clipUrl: '' } );
} );

test( 'a language change restarts the current screen line in the new language and keeps the position', () => {
	const { engine, said, api } = setup( { clips: { game_of_kings: { en: 'kings-en.mp4', ja: 'kings-ja.mp4' } } } );
	const log = [];
	registerAll( engine, log, { game_of_kings: { narration: 'kingsLine' } } );
	engine.start();
	api.setLocale( 'ja' );
	assert.deepEqual( said, [ { script: 'For over 500 years.', clipUrl: 'kings-en.mp4' }, { script: '五百年。', clipUrl: 'kings-ja.mp4' } ] );
	assert.equal( engine.current(), 'game_of_kings' );
	assert.deepEqual( log, [ [ 'render', 'game_of_kings' ] ] );
} );

test( 'a language change before the engine starts says nothing', () => {
	const { said, api } = setup();
	api.setLocale( 'ja' );
	assert.deepEqual( said, [] );
} );

test( 'each screen announces itself and the screen after it (for preloading)', () => {
	const { engine, events } = setup();
	const seen = [];
	events.on( 'screen', ( detail ) => seen.push( detail ) );
	registerAll( engine, [] );
	engine.start();
	engine.advance();
	assert.deepEqual( seen, [ { stage: 'game_of_kings', next: 'modern_era' }, { stage: 'modern_era', next: 'baccarat_experience' } ] );
} );

test( 'the closing screen announces no next screen', () => {
	const { engine, events } = setup( { stage: 'closing' } );
	const seen = [];
	events.on( 'screen', ( detail ) => seen.push( detail ) );
	registerAll( engine, [] );
	engine.start();
	assert.deepEqual( seen, [ { stage: 'closing', next: null } ] );
} );

test( 'save() posts the partial and keeps the state the store answers with', async () => {
	const stored = { ...STATE, answers: { baccarat_experience: 'played_some' } };
	const { engine, posts, api, errors, fetchFn } = setup( { saveResult: { ok: true, status: 200, data: stored } } );
	assert.equal( await engine.save( { answers: { baccarat_experience: 'played_some' } } ), true );
	assert.deepEqual( posts, [ { url: '/progress', nonce: 'n1', body: { answers: { baccarat_experience: 'played_some' } }, fetchFn } ] );
	assert.deepEqual( api.state, stored );
	assert.deepEqual( errors, [] );
} );

test( 'a failed save is reported and never stops the flow', async () => {
	const { engine, errors, api } = setup( { saveResult: { ok: false, status: 0, data: null } } );
	registerAll( engine, [] );
	engine.start();
	assert.equal( engine.advance(), true );
	await flush();
	assert.deepEqual( errors, [ 'Onboarding: saving progress failed (0)' ] );
	assert.equal( engine.current(), 'modern_era' );
	assert.deepEqual( api.state, { ...STATE } );
	assert.equal( await engine.save( { stage: 'equity' } ), false );
} );

test( 'a save answered without a body keeps the state', async () => {
	const { engine, api } = setup( { saveResult: { ok: true, status: 200, data: null } } );
	await engine.save( { stage: 'equity' } );
	assert.deepEqual( api.state, { ...STATE } );
} );

test( 'complete() marks onboarding complete, lifts the history guard and closes the layer', async () => {
	const { engine, posts, closes, guardCalls, fetchFn } = setup( { stage: 'closing' } );
	const log = [];
	registerAll( engine, log );
	engine.start();
	assert.equal( await engine.complete(), true );
	assert.deepEqual( posts, [ { url: '/complete', nonce: 'n1', fetchFn } ] );
	assert.deepEqual( guardCalls, [ 'install', 'uninstall' ] );
	assert.deepEqual( closes, [ true ] );
	assert.deepEqual( log, [ [ 'render', 'closing' ], [ 'destroy', 'closing' ] ] );
	assert.equal( engine.current(), null );
	assert.equal( engine.advance(), false );
} );

test( 'a failed completion is reported and keeps the layer open', async () => {
	const { engine, closes, guardCalls, errors } = setup( { stage: 'closing', completeResult: { ok: false, status: 403, data: null } } );
	registerAll( engine, [] );
	engine.start();
	assert.equal( await engine.complete(), false );
	assert.deepEqual( closes, [] );
	assert.deepEqual( guardCalls, [ 'install' ] );
	assert.deepEqual( errors, [ 'Onboarding: marking complete failed (403)' ] );
	assert.equal( engine.current(), 'closing' );
} );

test( 'a screen whose render returns nothing still works', () => {
	const { engine } = setup();
	engine.register( { id: 'game_of_kings', render: () => undefined } );
	engine.register( { id: 'modern_era', render: () => undefined } );
	engine.start();
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'modern_era' );
} );

test( 'a screen that leaves out its advance mode moves on when its line has been said', async () => {
	const { engine, endLine, posts, said } = setup();
	registerAll( engine, [], { game_of_kings: { narration: 'kingsLine' } } );
	engine.start();
	await flush();
	assert.equal( engine.current(), 'game_of_kings', 'not while Sami is still speaking' );
	endLine[ 0 ]( 'clip' );
	await flush();
	assert.equal( engine.current(), 'modern_era' );
	assert.deepEqual( posts.map( ( post ) => post.body ), [ { stage: 'modern_era' } ] );
	assert.equal( said.length, 2 );
} );

test( 'a NARRATION screen moves on when its line has been said', async () => {
	const { engine, endLine } = setup();
	registerAll( engine, [], { game_of_kings: { advanceMode: ADVANCE_MODES.NARRATION } } );
	engine.start();
	endLine[ 0 ]();
	await flush();
	assert.equal( engine.current(), 'modern_era' );
} );

test( 'PICK and MANUAL screens never move on by themselves, and do on their own advance()', async () => {
	for ( const advanceMode of [ ADVANCE_MODES.PICK, ADVANCE_MODES.MANUAL ] ) {
		const advancers = {};
		const { engine, endLine, posts } = setup();
		registerAll( engine, [], { game_of_kings: { advanceMode, advancers, narration: 'kingsLine' } } );
		engine.start();
		endLine[ 0 ]( 'clip' );
		await flush();
		assert.equal( engine.current(), 'game_of_kings', advanceMode );
		assert.deepEqual( posts, [], advanceMode );
		assert.equal( advancers.game_of_kings(), true, advanceMode );
		assert.equal( engine.current(), 'modern_era', advanceMode );
		await flush();
		assert.deepEqual( posts.map( ( post ) => post.body ), [ { stage: 'modern_era' } ], advanceMode );
	}
} );

test( 'a screen advance() called twice moves the flow once (a second pick, a late timer)', async () => {
	const advancers = {};
	const { engine, posts } = setup( { stage: 'baccarat_experience' } );
	registerAll( engine, [], { baccarat_experience: { advanceMode: ADVANCE_MODES.PICK, advancers } } );
	engine.start();
	const firstAdvance = advancers.baccarat_experience;
	assert.equal( firstAdvance(), true );
	assert.equal( firstAdvance(), false );
	assert.equal( engine.current(), 'streaming_experience' );
	await flush();
	assert.deepEqual( posts.map( ( post ) => post.body ), [ { stage: 'streaming_experience' } ] );
} );

test( 'a screen advance() still respects isValid() and shows the validation state', () => {
	const advancers = {};
	const log = [];
	const { engine } = setup( { stage: 'baccarat_experience' } );
	registerAll( engine, log, { baccarat_experience: { advanceMode: ADVANCE_MODES.PICK, advancers, valid: () => false } } );
	engine.start();
	assert.equal( advancers.baccarat_experience(), false );
	assert.equal( engine.current(), 'baccarat_experience' );
	assert.deepEqual( log, [ [ 'render', 'baccarat_experience' ], [ 'validation', 'baccarat_experience' ] ] );
} );

test( 'a manual advance racing the line ending moves the flow once (advance first)', async () => {
	const { engine, endLine, posts } = setup();
	registerAll( engine, [] );
	engine.start();
	assert.equal( engine.advance(), true );
	// Moving on stops the line, and the narrator settles it.
	endLine[ 0 ]();
	await flush();
	assert.equal( engine.current(), 'modern_era' );
	assert.deepEqual( posts.map( ( post ) => post.body ), [ { stage: 'modern_era' } ] );
} );

test( 'a manual advance racing the line ending moves the flow once (line first)', async () => {
	const advancers = {};
	const { engine, endLine, posts } = setup();
	registerAll( engine, [], { game_of_kings: { advancers } } );
	engine.start();
	const kingsAdvance = advancers.game_of_kings;
	endLine[ 0 ]();
	await flush();
	assert.equal( engine.current(), 'modern_era' );
	assert.equal( kingsAdvance(), false, 'screen 1 is gone, so its advance() does nothing' );
	assert.equal( engine.current(), 'modern_era' );
	await flush();
	assert.deepEqual( posts.map( ( post ) => post.body ), [ { stage: 'modern_era' } ] );
} );

test( 'a line stopped by a language change does not move the flow; the new line does', async () => {
	const { engine, endLine, api, posts } = setup();
	registerAll( engine, [], { game_of_kings: { narration: 'kingsLine' } } );
	engine.start();
	api.setLocale( 'ja' );
	endLine[ 0 ]();
	await flush();
	assert.equal( engine.current(), 'game_of_kings' );
	assert.deepEqual( posts, [] );
	endLine[ 1 ]();
	await flush();
	assert.equal( engine.current(), 'modern_era' );
} );

test( 'a line stopped by complete() closing the layer moves nothing', async () => {
	const { engine, endLine, posts } = setup( { stage: 'closing' } );
	registerAll( engine, [] );
	engine.start();
	assert.equal( await engine.complete(), true );
	endLine[ 0 ]();
	await flush();
	assert.equal( engine.current(), null );
	assert.deepEqual( posts.map( ( post ) => post.url ), [ '/complete' ] );
} );

// The engine on the real narrator, with a fake <video> and clock, so the narrator's own
// finish points (clip end, caption timer, fallback after an error) are what moves the flow.
function setupNarrated( { clips = {}, estimate = () => 1000 } = {} ) {
	const harness = setup( { clips } );
	const clock = createFakeClock();
	const video = new EventTarget();
	video.duration = Number.NaN;
	video.currentTime = 0;
	video.play = () => Promise.resolve();
	video.pause = () => {};
	video.fire = ( type ) => video.dispatchEvent( new Event( type ) );
	const narrator = createNarrator( {
		video,
		onSubtitle: () => {},
		buildSentenceCues,
		subtitleAt,
		estimateDurationMs: estimate,
		createStallWatchdog,
		setTimeout: clock.setTimeout,
		clearTimeout: clock.clearTimeout,
		now: clock.now,
	} );
	harness.api.shell.say = ( line ) => narrator.play( line );
	return { ...harness, clock, video, narrator };
}

const KINGS_CLIPS = { game_of_kings: { en: 'kings-en.mp4', ja: 'kings-ja.mp4' } };

test( 'narrator: the clip ending moves the flow on', async () => {
	const { engine, video } = setupNarrated( { clips: KINGS_CLIPS } );
	registerAll( engine, [], { game_of_kings: { narration: 'kingsLine' } } );
	engine.start();
	await flush();
	assert.equal( engine.current(), 'game_of_kings' );
	video.fire( 'ended' );
	await flush();
	assert.equal( engine.current(), 'modern_era' );
} );

test( 'narrator: the caption-only fallback timer ending moves the flow on', async () => {
	const { engine, clock } = setupNarrated();
	registerAll( engine, [], { game_of_kings: { narration: 'kingsLine' }, modern_era: { advanceMode: ADVANCE_MODES.MANUAL } } );
	engine.start();
	clock.tick( 999 );
	await flush();
	assert.equal( engine.current(), 'game_of_kings' );
	clock.tick( 1 );
	await flush();
	assert.equal( engine.current(), 'modern_era' );
} );

test( 'narrator: a clip that fails falls back to captions, and their end moves the flow on', async () => {
	const { engine, clock, video } = setupNarrated( { clips: KINGS_CLIPS } );
	registerAll( engine, [], { game_of_kings: { narration: 'kingsLine' }, modern_era: { advanceMode: ADVANCE_MODES.MANUAL } } );
	engine.start();
	video.fire( 'error' );
	await flush();
	assert.equal( engine.current(), 'game_of_kings' );
	clock.tick( 1000 );
	await flush();
	assert.equal( engine.current(), 'modern_era' );
} );

test( 'narrator: a screen with no script and no clip moves on as soon as it is shown (reading time 0)', async () => {
	const { engine } = setupNarrated( { estimate: estimateDurationMs } );
	engine.register( screen( 'game_of_kings', [] ) );
	engine.register( screen( 'modern_era', [], { advanceMode: ADVANCE_MODES.MANUAL } ) );
	engine.start();
	assert.equal( estimateDurationMs( '' ), 0 );
	assert.equal( engine.current(), 'game_of_kings', 'not inside start()' );
	await flush();
	assert.equal( engine.current(), 'modern_era' );
} );

test( 'narrator: a clip stopped by a language change does not move the flow; the new clip ending does', async () => {
	const { engine, api, video, posts } = setupNarrated( { clips: KINGS_CLIPS } );
	registerAll( engine, [], { game_of_kings: { narration: 'kingsLine' } } );
	engine.start();
	api.setLocale( 'ja' );
	await flush();
	assert.equal( engine.current(), 'game_of_kings' );
	assert.equal( video.src, 'kings-ja.mp4' );
	video.fire( 'ended' );
	await flush();
	assert.equal( engine.current(), 'modern_era' );
	assert.deepEqual( posts.map( ( post ) => post.body ), [ { stage: 'modern_era' } ] );
} );
