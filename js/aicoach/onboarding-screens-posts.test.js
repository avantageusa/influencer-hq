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
import { postJson } from './onboarding-request.js';
import { SOCIAL_STRINGS_EN } from './onboarding-screens-social.js';
import {
	POSTS_STRINGS_EN,
	DRAFTS_PER_REQUEST,
	REQUEST_TIMEOUT_MS,
	COPY_CONFIRMATION_MS,
	INSTRUCTION_MAX_LENGTH,
	MAX_RECORDING_MS,
	RECORDING_TYPES,
	ERROR_KEYS,
	selectedPlatforms,
	cleanDrafts,
	batches,
	errorOf,
	withTimeout,
	bytesToBase64,
	createDraftClient,
	createVoiceRecorder,
	install,
} from './onboarding-screens-posts.js';

const PHP = readFileSync( new URL( '../../inc/portal-onboarding-drafts.php', import.meta.url ), 'utf8' );

const STATE = { stage: 'draft_posts', answers: {}, platforms: [ 'instagram', 'x' ], handles: {}, language: 'ja', drafts: {}, completed: false, completed_at: '' };
const CONFIG = { progressUrl: '/p', completeUrl: '/c', draftsUrl: '/drafts', draftsReviseUrl: '/drafts/revise', nonce: 'n' };

// A fetch that answers only when the test says so.
function createFakeFetch() {
	const calls = [];
	function fetchFn( url, options ) {
		return new Promise( ( resolve, reject ) => {
			calls.push( { url, options, body: JSON.parse( options.body ), resolve, reject } );
		} );
	}
	function respond( index, status, data ) {
		calls[ index ].resolve( { ok: status >= 200 && status < 300, status, json: async () => data } );
	}
	return { fetchFn, calls, respond };
}

// A recorder whose sessions the test controls.
function createFakeRecorder( supported = true ) {
	const sessions = [];
	const recorder = {
		supported,
		failure: null,
		recording: { data: 'QUJD', mime: 'audio/webm;codecs=opus' },
		start() {
			if ( recorder.failure ) {
				return Promise.reject( recorder.failure );
			}
			const session = {
				stopped: 0,
				cancelled: 0,
				stop() {
					session.stopped += 1;
					return Promise.resolve( recorder.recording );
				},
				cancel() {
					session.cancelled += 1;
				},
			};
			sessions.push( session );
			return Promise.resolve( session );
		},
		sessions,
	};
	return recorder;
}

function setup( { state = {}, clipboard, share, recorder = createFakeRecorder() } = {} ) {
	const document = createFakeDocument();
	const content = document.createElement( 'div' );
	document.body.append( content );
	const events = createEmitter();
	const strings = createStringTable();
	strings.add( 'en', SOCIAL_STRINGS_EN );
	const api = createApi( { config: { ...CONFIG, state: { ...STATE, ...state } }, events, strings } );
	const said = [];
	api.shell = {
		content,
		say: ( line ) => {
			said.push( line );
			return Promise.resolve();
		},
		close: () => {},
	};
	const saves = [];
	const clock = createFakeClock();
	const engine = createEngine( {
		api,
		sequence: { SEQUENCE, startStage, nextStage },
		document,
		postJson: async ( request ) => {
			saves.push( request.body );
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
	api.postJson = postJson;
	const copied = [];
	const shared = [];
	const fetch = createFakeFetch();
	const deps = {
		fetchFn: fetch.fetchFn,
		timers: clock,
		clipboard: undefined === clipboard ? async ( text ) => copied.push( text ) : clipboard,
		share: undefined === share ? async ( data ) => shared.push( data ) : share,
		recorder,
	};
	install( api, deps );
	// Screen 15 stand-in, choosing "Let's edit this together now" when the flow moves on from it.
	engine.register( { id: 'post_action', advanceMode: ADVANCE_MODES.MANUAL, render: () => ( { choice: () => 'edit_together' } ) } );
	engine.start();
	const screen = () => content.querySelector( 'section' );
	const panel = ( key ) => screen().querySelector( `article[data-platform="${ key }"]` );
	const part = ( key, className ) => panel( key ).querySelector( '.' + className );
	const draftText = ( key ) => part( key, 'ihq-onb-posts__draft' ).textContent;
	const status = ( key ) => panel( key ).querySelector( 'p.ihq-onb-posts__status' );
	const error = ( key ) => panel( key ).querySelector( 'p.ihq-onb__error' );
	const formStatus = ( key ) => part( key, 'ihq-onb-posts__change' ).querySelector( 'p.ihq-onb-posts__status' );
	const formError = ( key ) => part( key, 'ihq-onb-posts__change' ).querySelector( 'p.ihq-onb__error' );
	const input = ( key ) => part( key, 'ihq-onb-posts__input' );
	const type = ( key, value ) => {
		input( key ).value = value;
	};
	const pressContinue = () => screen().querySelector( '.ihq-onb-posts__continue' ).click();
	return { document, api, engine, clock, saves, said, copied, shared, fetch, recorder, strings, events, screen, panel, part, draftText, status, error, formStatus, formError, input, type, pressContinue };
}

// Queued requests and engine saves run a few microtasks after the call that starts them.
const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

const STORED = { instagram: 'Insta draft', x: "X draft\nline two" };

// --- Contract with the server ---

test( 'the limits and codes are the server\'s', () => {
	assert.equal( DRAFTS_PER_REQUEST, 8 );
	assert.equal( PHP.includes( 'const IHQ_PORTAL_ONBOARDING_DRAFTS_PER_REQUEST = 8;' ), true );
	assert.equal( INSTRUCTION_MAX_LENGTH, 500 );
	assert.equal( PHP.includes( 'const IHQ_PORTAL_ONBOARDING_INSTRUCTION_MAX_LENGTH = 500;' ), true );
	assert.equal( PHP.includes( 'const IHQ_PORTAL_ONBOARDING_DRAFTS_TIMEOUT = 40;' ), true );
	assert.equal( REQUEST_TIMEOUT_MS, 45000, 'a little longer than the server waits for Gary' );
	assert.deepEqual( RECORDING_TYPES, [ 'audio/webm', 'audio/ogg', 'audio/mp4' ] );
	RECORDING_TYPES.forEach( ( type ) => assert.match( PHP, new RegExp( `'${ type }'\\s+=> '` ), type ) );
	assert.deepEqual( Object.keys( ERROR_KEYS ), [ 'refused', 'limit', 'unavailable', 'failed', 'invalid', 'timeout' ] );
	const statuses = PHP.slice( PHP.indexOf( 'const IHQ_PORTAL_ONBOARDING_DRAFTS_ERROR_STATUS' ) );
	const statusList = statuses.slice( 0, statuses.indexOf( ');' ) );
	assert.deepEqual( Array.from( statusList.matchAll( /'([a-z]+)'\s+=>/g ), ( match ) => match[ 1 ] ), [ 'invalid', 'refused', 'limit', 'unavailable', 'failed' ] );
	assert.equal( COPY_CONFIRMATION_MS, 2000 );
	assert.equal( MAX_RECORDING_MS, 60000 );
	Object.values( ERROR_KEYS ).forEach( ( key ) => assert.equal( 'string' === typeof POSTS_STRINGS_EN[ key ] && '' !== POSTS_STRINGS_EN[ key ], true, key ) );
} );

// --- Panels ---

test( 'one panel per saved platform, in order, with its name and stored draft; no request', async () => {
	const { screen, panel, draftText, fetch, part } = setup( { state: { platforms: [ 'instagram', 'x' ], drafts: STORED } } );
	await flush();
	assert.equal( screen().querySelector( 'h2' ).textContent, "I'll help you draft messages to your followers" );
	assert.deepEqual( screen().querySelectorAll( 'article' ).map( ( element ) => element.getAttribute( 'data-platform' ) ), [ 'instagram', 'x' ] );
	assert.equal( panel( 'instagram' ).querySelector( 'h3' ).textContent, 'Instagram' );
	assert.equal( panel( 'x' ).querySelector( 'h3' ).textContent, 'X' );
	assert.equal( panel( 'x' ).querySelector( 'h3' ).getAttribute( 'data-onb-string' ), 'platform_x' );
	assert.equal( panel( 'x' ).getAttribute( 'aria-labelledby' ), panel( 'x' ).querySelector( 'h3' ).id );
	assert.equal( draftText( 'instagram' ), 'Insta draft' );
	assert.equal( draftText( 'x' ), "X draft\nline two" );
	assert.equal( part( 'x', 'ihq-onb-posts__draft' ).hidden, false );
	assert.equal( part( 'x', 'ihq-onb-posts__draft' ).hasAttribute( 'data-onb-string' ), false, 'a language change must not overwrite the draft' );
	assert.equal( part( 'x', 'ihq-onb-posts__actions' ).hidden, false );
	assert.deepEqual( fetch.calls, [] );
} );

test( 'a draft is text, not a field: the only field is the hidden change request', () => {
	const { screen, part } = setup( { state: { drafts: STORED } } );
	assert.equal( part( 'x', 'ihq-onb-posts__draft' ).tagName, 'P' );
	assert.equal( screen().querySelectorAll( 'textarea' ).length, 0 );
	const fields = screen().querySelectorAll( 'input' );
	assert.equal( fields.length, 2 );
	fields.forEach( ( field ) => assert.equal( field.parentNode.parentNode.hidden, true ) );
} );

test( 'no panel for a platform that is not selected, even with a draft', () => {
	const { screen } = setup( { state: { platforms: [ 'x' ], drafts: { x: 'X', line: 'Line' } } } );
	assert.deepEqual( screen().querySelectorAll( 'article' ).map( ( element ) => element.getAttribute( 'data-platform' ) ), [ 'x' ] );
} );

test( 'no platforms saved: a message and CONTINUE, no request', async () => {
	const { screen, fetch, engine, pressContinue } = setup( { state: { platforms: [] } } );
	await flush();
	assert.equal( screen().querySelector( 'p' ).textContent, "You didn't choose any social media sites, so there are no posts to draft. You can add them on your Profile page later." );
	assert.equal( screen().querySelectorAll( 'article' ).length, 0 );
	assert.deepEqual( fetch.calls, [] );
	pressContinue();
	assert.equal( engine.current(), 'post_action' );
} );

// --- Drafting ---

test( 'missing drafts are asked for in one request in the current language, and fill their panels', async () => {
	const { fetch, panel, status, draftText, part, api } = setup( { state: { platforms: [ 'instagram', 'x', 'line' ], drafts: { x: 'X stored' } } } );
	assert.equal( status( 'instagram' ).textContent, 'Sami is writing your post…' );
	assert.equal( status( 'instagram' ).hidden, false );
	assert.equal( panel( 'instagram' ).getAttribute( 'aria-busy' ), 'true' );
	assert.equal( part( 'instagram', 'ihq-onb-posts__actions' ).hidden, true, 'nothing to copy yet' );
	assert.equal( panel( 'x' ).hasAttribute( 'aria-busy' ), false );
	await flush();
	assert.equal( fetch.calls.length, 1 );
	assert.equal( fetch.calls[ 0 ].url, '/drafts' );
	assert.equal( fetch.calls[ 0 ].options.method, 'POST' );
	assert.equal( fetch.calls[ 0 ].options.headers[ 'X-WP-Nonce' ], 'n' );
	assert.deepEqual( fetch.calls[ 0 ].body, { platforms: [ 'instagram', 'line' ], lang: 'ja' } );
	fetch.respond( 0, 200, { drafts: { instagram: 'New insta', line: 'New line' } } );
	await flush();
	assert.equal( draftText( 'instagram' ), 'New insta' );
	assert.equal( draftText( 'line' ), 'New line' );
	assert.equal( draftText( 'x' ), 'X stored' );
	assert.equal( status( 'instagram' ).hidden, true );
	assert.equal( status( 'instagram' ).textContent, '' );
	assert.equal( panel( 'instagram' ).hasAttribute( 'aria-busy' ), false );
	assert.equal( part( 'instagram', 'ihq-onb-posts__actions' ).hidden, false );
	assert.deepEqual( api.state.drafts, { x: 'X stored', instagram: 'New insta', line: 'New line' } );
} );

test( 'more than eight missing drafts go in batches of eight, one request at a time', async () => {
	const platforms = [ 'instagram', 'facebook', 'tiktok', 'youtube', 'bilibili', 'douyin', 'kakaotalk', 'line', 'linkedin', 'pinterest' ];
	const { fetch, draftText } = setup( { state: { platforms } } );
	await flush();
	assert.equal( fetch.calls.length, 1 );
	assert.deepEqual( fetch.calls[ 0 ].body.platforms, platforms.slice( 0, 8 ) );
	const first = {};
	platforms.slice( 0, 8 ).forEach( ( key ) => {
		first[ key ] = 'Post ' + key;
	} );
	fetch.respond( 0, 200, { drafts: first } );
	await flush();
	assert.equal( fetch.calls.length, 2 );
	assert.deepEqual( fetch.calls[ 1 ].body, { platforms: [ 'linkedin', 'pinterest' ], lang: 'ja' } );
	fetch.respond( 1, 200, { drafts: { linkedin: 'L', pinterest: 'P' } } );
	await flush();
	assert.equal( draftText( 'instagram' ), 'Post instagram' );
	assert.equal( draftText( 'pinterest' ), 'P' );
} );

test( 'a platform left out of the reply fails with Try again, and Try again asks for it alone', async () => {
	const { fetch, error, part, draftText, status } = setup();
	await flush();
	fetch.respond( 0, 200, { drafts: { instagram: 'Insta' } } );
	await flush();
	assert.equal( draftText( 'instagram' ), 'Insta' );
	assert.equal( error( 'x' ).hidden, false );
	assert.equal( error( 'x' ).textContent, 'Something went wrong writing this post. Please try again.' );
	assert.equal( error( 'x' ).getAttribute( 'data-onb-string' ), 'postsErrorFailed' );
	assert.equal( error( 'x' ).getAttribute( 'role' ), 'alert' );
	assert.equal( status( 'x' ).hidden, true );
	const retry = part( 'x', 'ihq-onb-posts__retry' );
	assert.equal( retry.hidden, false );
	assert.equal( retry.textContent, 'Try again' );
	retry.click();
	assert.equal( error( 'x' ).hidden, true );
	assert.equal( retry.hidden, true );
	assert.equal( status( 'x' ).textContent, 'Sami is writing your post…' );
	await flush();
	assert.deepEqual( fetch.calls[ 1 ].body, { platforms: [ 'x' ], lang: 'ja' } );
	fetch.respond( 1, 200, { drafts: { x: 'X at last' } } );
	await flush();
	assert.equal( draftText( 'x' ), 'X at last' );
	assert.equal( error( 'x' ).hidden, true );
} );

const SERVER_ERRORS = [
	[ 422, 'refused', "Sami couldn't write that. Please try again, or ask for something different." ],
	[ 429, 'limit', 'Sami has written all the posts she can for today. Please try again tomorrow.' ],
	[ 503, 'unavailable', "Sami can't write posts right now. Please try again in a few minutes." ],
	[ 502, 'failed', 'Something went wrong writing this post. Please try again.' ],
	[ 400, 'invalid', "Sami couldn't use that request. Please try again." ],
	[ 500, 'something_else', "Sami can't write posts right now. Please try again in a few minutes." ],
	[ 403, undefined, "Sami can't write posts right now. Please try again in a few minutes." ],
];
for ( const [ httpStatus, code, message ] of SERVER_ERRORS ) {
	test( `a ${ httpStatus } ${ code } shows its message in the panels asked for, and the stored draft stays usable`, async () => {
		const { fetch, error, part, draftText, copied } = setup( { state: { drafts: { instagram: 'Insta' } } } );
		await flush();
		fetch.respond( 0, httpStatus, undefined === code ? null : { error: code } );
		await flush();
		assert.equal( error( 'x' ).textContent, message );
		assert.equal( part( 'x', 'ihq-onb-posts__retry' ).hidden, false );
		assert.equal( error( 'instagram' ).hidden, true );
		assert.equal( draftText( 'instagram' ), 'Insta' );
		part( 'instagram', 'ihq-onb-posts__copy' ).click();
		await flush();
		assert.deepEqual( copied, [ 'Insta' ] );
	} );
}

test( 'no answer within the timeout shows the timeout message', async () => {
	const { fetch, error, clock } = setup( { state: { platforms: [ 'x' ] } } );
	await flush();
	assert.equal( fetch.calls.length, 1 );
	clock.tick( REQUEST_TIMEOUT_MS - 1 );
	await flush();
	assert.equal( error( 'x' ).hidden, true );
	clock.tick( 1 );
	await flush();
	assert.equal( error( 'x' ).textContent, "Sami didn't answer in time. Please check your connection and try again." );
} );

test( 'a network failure shows the timeout message too', async () => {
	const { fetch, error } = setup( { state: { platforms: [ 'x' ] } } );
	await flush();
	fetch.calls[ 0 ].reject( new TypeError( 'Failed to fetch' ) );
	await flush();
	assert.equal( error( 'x' ).getAttribute( 'data-onb-string' ), 'postsErrorTimeout' );
} );

test( 'CONTINUE moves on at any time, a request in flight included; its late answer is still kept', async () => {
	const { fetch, engine, pressContinue, api, saves } = setup( { state: { platforms: [ 'x' ] } } );
	await flush();
	pressContinue();
	assert.equal( engine.current(), 'post_action' );
	fetch.respond( 0, 200, { drafts: { x: 'Late X' } } );
	await flush();
	assert.deepEqual( api.state.drafts, { x: 'Late X' } );
	assert.deepEqual( saves, [ { stage: 'post_action' } ] );
} );

test( 'coming back to the screen (edit together) shows the drafts without asking again, even after a stale state', async () => {
	const { fetch, engine, api, draftText, pressContinue } = setup( { state: { platforms: [ 'x' ] } } );
	await flush();
	fetch.respond( 0, 200, { drafts: { x: 'Fresh X' } } );
	await flush();
	pressContinue();
	assert.equal( engine.current(), 'post_action' );
	// A progress save that answered with the state from before the draft.
	api.state = { ...api.state, drafts: {} };
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'draft_posts' );
	await flush();
	assert.equal( draftText( 'x' ), 'Fresh X' );
	assert.equal( fetch.calls.length, 1 );
} );

// --- Copy and Share ---

test( 'Copy puts only that draft on the clipboard and confirms, for COPY_CONFIRMATION_MS', async () => {
	const { part, copied, status, clock } = setup( { state: { drafts: STORED } } );
	const copy = part( 'x', 'ihq-onb-posts__copy' );
	assert.equal( copy.textContent, 'Copy' );
	assert.equal( copy.getAttribute( 'type' ), 'button' );
	copy.click();
	await flush();
	assert.deepEqual( copied, [ "X draft\nline two" ] );
	assert.equal( status( 'x' ).textContent, 'Copied' );
	assert.equal( status( 'x' ).getAttribute( 'role' ), 'status' );
	assert.equal( status( 'x' ).hidden, false );
	assert.equal( status( 'instagram' ).hidden, true );
	clock.tick( COPY_CONFIRMATION_MS - 1 );
	assert.equal( status( 'x' ).hidden, false );
	copy.click();
	await flush();
	clock.tick( COPY_CONFIRMATION_MS - 1 );
	assert.equal( status( 'x' ).hidden, false, 'a second copy restarts the confirmation' );
	clock.tick( 1 );
	assert.equal( status( 'x' ).hidden, true );
	assert.equal( status( 'x' ).textContent, '' );
} );

test( 'a refused clipboard, or none, says to copy by hand', async () => {
	const refused = setup( { state: { drafts: STORED }, clipboard: async () => {
		throw new Error( 'denied' );
	} } );
	refused.part( 'x', 'ihq-onb-posts__copy' ).click();
	await flush();
	assert.equal( refused.status( 'x' ).textContent, "Couldn't copy. Select the text and copy it instead." );
	const none = setup( { state: { drafts: STORED }, clipboard: null } );
	none.part( 'x', 'ihq-onb-posts__copy' ).click();
	await flush();
	assert.equal( none.status( 'x' ).getAttribute( 'data-onb-string' ), 'postsCopyFailed' );
} );

test( 'Share opens the share sheet with only that draft', async () => {
	const { part, shared, status } = setup( { state: { drafts: STORED } } );
	const share = part( 'instagram', 'ihq-onb-posts__share' );
	assert.equal( share.hidden, false );
	assert.equal( share.textContent, 'Share' );
	share.click();
	await flush();
	assert.deepEqual( shared, [ { text: 'Insta draft' } ] );
	assert.equal( status( 'instagram' ).hidden, true );
} );

test( 'closing the share sheet says nothing; a share that fails says to copy instead', async () => {
	const cancelled = setup( { state: { drafts: STORED }, share: async () => {
		throw Object.assign( new Error( 'cancel' ), { name: 'AbortError' } );
	} } );
	cancelled.part( 'x', 'ihq-onb-posts__share' ).click();
	await flush();
	assert.equal( cancelled.status( 'x' ).hidden, true );
	const failed = setup( { state: { drafts: STORED }, share: async () => {
		throw Object.assign( new Error( 'no' ), { name: 'NotAllowedError' } );
	} } );
	failed.part( 'x', 'ihq-onb-posts__share' ).click();
	await flush();
	assert.equal( failed.status( 'x' ).textContent, "Couldn't open sharing on this device. Use Copy instead." );
} );

test( 'without a share sheet the Share button is hidden', () => {
	const { part } = setup( { state: { drafts: STORED }, share: null } );
	assert.equal( part( 'x', 'ihq-onb-posts__share' ).hidden, true );
	assert.equal( part( 'x', 'ihq-onb-posts__copy' ).hidden, false );
} );

// --- Asking for a change, typed ---

test( 'Ask Sami to change this opens and closes that panel\'s request, focusing the field', () => {
	const { document, part, input } = setup( { state: { drafts: STORED } } );
	const toggle = part( 'x', 'ihq-onb-posts__change-toggle' );
	const form = part( 'x', 'ihq-onb-posts__change' );
	assert.equal( toggle.textContent, 'Ask Sami to change this' );
	assert.equal( toggle.getAttribute( 'aria-controls' ), form.id );
	assert.equal( toggle.getAttribute( 'aria-expanded' ), 'false' );
	assert.equal( form.hidden, true );
	toggle.click();
	assert.equal( form.hidden, false );
	assert.equal( toggle.getAttribute( 'aria-expanded' ), 'true' );
	assert.equal( document.activeElement, input( 'x' ) );
	assert.equal( part( 'instagram', 'ihq-onb-posts__change' ).hidden, true );
	assert.equal( form.querySelector( 'label' ).textContent, 'What should Sami change?' );
	assert.equal( form.querySelector( 'label' ).getAttribute( 'for' ), input( 'x' ).id );
	assert.equal( input( 'x' ).getAttribute( 'placeholder' ), 'For example: make it shorter and warmer' );
	assert.equal( input( 'x' ).getAttribute( 'maxlength' ), '500' );
	toggle.click();
	assert.equal( form.hidden, true );
	assert.equal( toggle.getAttribute( 'aria-expanded' ), 'false' );
} );

test( 'a typed change replaces only that draft, stores it in the state, and Sami answers in captions', async () => {
	const { fetch, part, input, type, draftText, formStatus, said, api } = setup( { state: { drafts: STORED } } );
	part( 'x', 'ihq-onb-posts__change-toggle' ).click();
	type( 'x', '  Make it warmer and shorter.  ' );
	part( 'x', 'ihq-onb-posts__send' ).click();
	assert.equal( formStatus( 'x' ).textContent, 'Sami is rewriting your post…' );
	assert.equal( input( 'x' ).disabled, true );
	assert.equal( part( 'x', 'ihq-onb-posts__send' ).disabled, true );
	assert.equal( part( 'x', 'ihq-onb-posts__mic' ).disabled, true );
	await flush();
	assert.equal( fetch.calls.length, 1 );
	assert.equal( fetch.calls[ 0 ].url, '/drafts/revise' );
	assert.deepEqual( fetch.calls[ 0 ].body, { platform: 'x', lang: 'ja', instruction: 'Make it warmer and shorter.' } );
	assert.equal( draftText( 'x' ), "X draft\nline two", 'the old draft stays until the new one arrives' );
	fetch.respond( 0, 200, { drafts: { x: 'Warmer X' } } );
	await flush();
	assert.equal( draftText( 'x' ), 'Warmer X' );
	assert.equal( draftText( 'instagram' ), 'Insta draft' );
	assert.equal( input( 'x' ).value, '' );
	assert.equal( input( 'x' ).disabled, false );
	assert.equal( formStatus( 'x' ).hidden, true );
	assert.equal( part( 'x', 'ihq-onb-posts__heard' ).hidden, true, 'nothing was heard for a typed change' );
	assert.deepEqual( said, [ { script: '', clipUrl: '' }, { script: "Here's the new version." } ] );
	assert.deepEqual( api.state.drafts, { instagram: 'Insta draft', x: 'Warmer X' } );
} );

test( 'Enter in the field sends too; other keys do not', async () => {
	const { fetch, input, type } = setup( { state: { drafts: STORED } } );
	type( 'x', 'Shorter' );
	const other = createEvent( 'keydown', { key: 'a' } );
	input( 'x' ).dispatchEvent( other );
	await flush();
	assert.equal( fetch.calls.length, 0 );
	assert.equal( other.defaultPrevented, false );
	const enter = createEvent( 'keydown', { key: 'Enter' } );
	input( 'x' ).dispatchEvent( enter );
	assert.equal( enter.defaultPrevented, true );
	await flush();
	assert.deepEqual( fetch.calls[ 0 ].body, { platform: 'x', lang: 'ja', instruction: 'Shorter' } );
} );

test( 'an empty change request asks for one and sends nothing', async () => {
	const { fetch, part, type, formError } = setup( { state: { drafts: STORED } } );
	type( 'x', '   ' );
	part( 'x', 'ihq-onb-posts__send' ).click();
	await flush();
	assert.deepEqual( fetch.calls, [] );
	assert.equal( formError( 'x' ).textContent, 'Type or say what Sami should change.' );
	assert.equal( formError( 'x' ).hidden, false );
} );

test( 'a failed change keeps the draft as it was and usable, with the message in the request', async () => {
	const { fetch, part, type, draftText, formError, formStatus, input, copied, said } = setup( { state: { drafts: STORED } } );
	type( 'x', 'Shorter' );
	part( 'x', 'ihq-onb-posts__send' ).click();
	await flush();
	fetch.respond( 0, 429, { error: 'limit' } );
	await flush();
	assert.equal( draftText( 'x' ), "X draft\nline two" );
	assert.equal( formError( 'x' ).textContent, 'Sami has written all the posts she can for today. Please try again tomorrow.' );
	assert.equal( formStatus( 'x' ).hidden, true );
	assert.equal( input( 'x' ).disabled, false );
	assert.equal( input( 'x' ).value, 'Shorter', 'the request is kept to send again' );
	assert.equal( said.length, 1, 'Sami says nothing new' );
	part( 'x', 'ihq-onb-posts__copy' ).click();
	await flush();
	assert.deepEqual( copied, [ "X draft\nline two" ] );
} );

test( 'a change answered without that platform\'s draft is a failure', async () => {
	const { fetch, part, type, draftText, formError } = setup( { state: { drafts: STORED } } );
	type( 'x', 'Shorter' );
	part( 'x', 'ihq-onb-posts__send' ).click();
	await flush();
	fetch.respond( 0, 200, { drafts: { instagram: 'Wrong one' } } );
	await flush();
	assert.equal( draftText( 'x' ), "X draft\nline two" );
	assert.equal( draftText( 'instagram' ), 'Insta draft' );
	assert.equal( formError( 'x' ).getAttribute( 'data-onb-string' ), 'postsErrorFailed' );
} );

test( 'a change answered after leaving the screen is kept, but Sami does not answer it on the next screen', async () => {
	const { fetch, part, type, said, pressContinue, engine, api } = setup( { state: { drafts: STORED } } );
	type( 'x', 'Shorter' );
	part( 'x', 'ihq-onb-posts__send' ).click();
	await flush();
	pressContinue();
	assert.equal( engine.current(), 'post_action' );
	const saidBefore = said.length;
	fetch.respond( 0, 200, { drafts: { x: 'Late X' } } );
	await flush();
	assert.equal( said.length, saidBefore );
	assert.deepEqual( api.state.drafts, { instagram: 'Insta draft', x: 'Late X' } );
} );

test( 'changes in two panels go one at a time', async () => {
	const { fetch, part, type, draftText } = setup( { state: { drafts: STORED } } );
	type( 'x', 'One' );
	part( 'x', 'ihq-onb-posts__send' ).click();
	type( 'instagram', 'Two' );
	part( 'instagram', 'ihq-onb-posts__send' ).click();
	await flush();
	assert.equal( fetch.calls.length, 1 );
	fetch.respond( 0, 200, { drafts: { x: 'X1' } } );
	await flush();
	assert.equal( fetch.calls.length, 2 );
	assert.deepEqual( fetch.calls[ 1 ].body, { platform: 'instagram', lang: 'ja', instruction: 'Two' } );
	fetch.respond( 1, 200, { drafts: { instagram: 'I2' } } );
	await flush();
	assert.equal( draftText( 'x' ), 'X1' );
	assert.equal( draftText( 'instagram' ), 'I2' );
} );

// --- Asking for a change, spoken ---

test( 'the mic records on the first tap and sends on the second, with what Sami heard shown', async () => {
	const { recorder, fetch, part, formStatus, draftText, said } = setup( { state: { drafts: STORED } } );
	const mic = part( 'x', 'ihq-onb-posts__mic' );
	assert.equal( mic.hidden, false );
	assert.equal( mic.getAttribute( 'aria-label' ), 'Tell Sami what to change' );
	assert.equal( mic.getAttribute( 'aria-pressed' ), 'false' );
	mic.click();
	await flush();
	assert.equal( recorder.sessions.length, 1 );
	assert.equal( mic.getAttribute( 'aria-pressed' ), 'true' );
	assert.equal( mic.classList.contains( 'is-listening' ), true );
	assert.equal( mic.getAttribute( 'aria-label' ), 'Stop and send' );
	assert.equal( mic.getAttribute( 'data-onb-label' ), 'postsStopSpeaking' );
	assert.equal( formStatus( 'x' ).textContent, 'Listening… Tap the microphone again when you are done.' );
	assert.equal( part( 'x', 'ihq-onb-posts__send' ).disabled, true );
	mic.click();
	await flush();
	assert.equal( recorder.sessions[ 0 ].stopped, 1 );
	assert.equal( mic.getAttribute( 'aria-pressed' ), 'false' );
	assert.equal( mic.getAttribute( 'aria-label' ), 'Tell Sami what to change' );
	assert.deepEqual( fetch.calls[ 0 ].body, { platform: 'x', lang: 'ja', audio: { data: 'QUJD', mime: 'audio/webm;codecs=opus' } } );
	fetch.respond( 0, 200, { drafts: { x: 'Spoken X' }, heard: 'もっと短く' } );
	await flush();
	assert.equal( draftText( 'x' ), 'Spoken X' );
	const heard = part( 'x', 'ihq-onb-posts__heard' );
	assert.equal( heard.hidden, false );
	assert.equal( heard.textContent, 'You said: “もっと短く”' );
	assert.equal( said[ said.length - 1 ].script, "Here's the new version." );
} );

test( 'taps while the browser is still asking for the microphone are ignored', async () => {
	let grant = null;
	const starts = [];
	const session = { stopped: 0, cancelled: 0, stop() {
		session.stopped += 1;
		return Promise.resolve( { data: 'QUJD', mime: 'audio/webm' } );
	}, cancel() {
		session.cancelled += 1;
	} };
	const recorder = { supported: true, start() {
		starts.push( 1 );
		return new Promise( ( resolve ) => {
			grant = () => resolve( session );
		} );
	} };
	const { part, fetch } = setup( { state: { drafts: STORED }, recorder } );
	part( 'x', 'ihq-onb-posts__mic' ).click();
	part( 'x', 'ihq-onb-posts__mic' ).click();
	part( 'instagram', 'ihq-onb-posts__mic' ).click();
	assert.equal( starts.length, 1 );
	grant();
	await flush();
	assert.equal( part( 'x', 'ihq-onb-posts__mic' ).getAttribute( 'aria-pressed' ), 'true' );
	assert.equal( session.cancelled, 0 );
	part( 'x', 'ihq-onb-posts__mic' ).click();
	await flush();
	assert.equal( session.stopped, 1 );
	assert.equal( fetch.calls.length, 1 );
} );

test( 'leaving while the browser asks for the microphone releases it once granted', async () => {
	let grant = null;
	const session = { cancelled: 0, stop: () => Promise.resolve( { data: '', mime: '' } ), cancel() {
		session.cancelled += 1;
	} };
	const recorder = { supported: true, start: () => new Promise( ( resolve ) => {
		grant = () => resolve( session );
	} ) };
	const { part, pressContinue, clock } = setup( { state: { drafts: STORED }, recorder } );
	part( 'x', 'ihq-onb-posts__mic' ).click();
	pressContinue();
	grant();
	await flush();
	assert.equal( session.cancelled, 1 );
	assert.equal( clock.pendingCount(), 0 );
} );

test( 'a recording stops and is sent by itself after MAX_RECORDING_MS', async () => {
	const { recorder, fetch, part, clock } = setup( { state: { drafts: STORED } } );
	part( 'x', 'ihq-onb-posts__mic' ).click();
	await flush();
	clock.tick( MAX_RECORDING_MS - 1 );
	assert.equal( recorder.sessions[ 0 ].stopped, 0 );
	clock.tick( 1 );
	await flush();
	assert.equal( recorder.sessions[ 0 ].stopped, 1 );
	assert.equal( fetch.calls.length, 1 );
} );

test( 'a recording with nothing in it says so and sends nothing', async () => {
	const { recorder, fetch, part, formError } = setup( { state: { drafts: STORED } } );
	recorder.recording = { data: '', mime: 'audio/webm' };
	part( 'x', 'ihq-onb-posts__mic' ).click();
	await flush();
	part( 'x', 'ihq-onb-posts__mic' ).click();
	await flush();
	assert.deepEqual( fetch.calls, [] );
	assert.equal( formError( 'x' ).textContent, "Sami didn't hear anything. Please try again." );
} );

test( 'a refused microphone says to type; any other microphone failure too', async () => {
	const blocked = setup( { state: { drafts: STORED } } );
	blocked.recorder.failure = Object.assign( new Error( 'no' ), { name: 'NotAllowedError' } );
	blocked.part( 'x', 'ihq-onb-posts__mic' ).click();
	await flush();
	assert.equal( blocked.formError( 'x' ).textContent, "Sami can't hear you without the microphone. You can type instead." );
	assert.equal( blocked.part( 'x', 'ihq-onb-posts__mic' ).getAttribute( 'aria-pressed' ), 'false' );
	const broken = setup( { state: { drafts: STORED } } );
	broken.recorder.failure = Object.assign( new Error( 'no' ), { name: 'NotReadableError' } );
	broken.part( 'x', 'ihq-onb-posts__mic' ).click();
	await flush();
	assert.equal( broken.formError( 'x' ).textContent, "The microphone didn't start. You can type instead." );
	assert.deepEqual( broken.fetch.calls, [] );
} );

test( 'without a recorder the mic is hidden and typing still works', async () => {
	const { part, type, fetch } = setup( { state: { drafts: STORED }, recorder: createFakeRecorder( false ) } );
	assert.equal( part( 'x', 'ihq-onb-posts__mic' ).hidden, true );
	type( 'x', 'Shorter' );
	part( 'x', 'ihq-onb-posts__send' ).click();
	await flush();
	assert.equal( fetch.calls.length, 1 );
} );

test( 'one microphone: recording in another panel drops the first recording', async () => {
	const { recorder, part, fetch } = setup( { state: { drafts: STORED } } );
	part( 'x', 'ihq-onb-posts__mic' ).click();
	await flush();
	part( 'instagram', 'ihq-onb-posts__mic' ).click();
	await flush();
	assert.equal( recorder.sessions[ 0 ].cancelled, 1 );
	assert.equal( recorder.sessions[ 0 ].stopped, 0 );
	assert.equal( part( 'x', 'ihq-onb-posts__mic' ).getAttribute( 'aria-pressed' ), 'false' );
	assert.equal( part( 'instagram', 'ihq-onb-posts__mic' ).getAttribute( 'aria-pressed' ), 'true' );
	assert.deepEqual( fetch.calls, [] );
} );

test( 'closing the request, or leaving the screen, drops a recording and releases the microphone', async () => {
	const closing = setup( { state: { drafts: STORED } } );
	closing.part( 'x', 'ihq-onb-posts__change-toggle' ).click();
	closing.part( 'x', 'ihq-onb-posts__mic' ).click();
	await flush();
	closing.part( 'x', 'ihq-onb-posts__change-toggle' ).click();
	assert.equal( closing.recorder.sessions[ 0 ].cancelled, 1 );
	assert.equal( closing.clock.pendingCount(), 0 );
	const leaving = setup( { state: { drafts: STORED } } );
	leaving.part( 'x', 'ihq-onb-posts__mic' ).click();
	await flush();
	leaving.pressContinue();
	assert.equal( leaving.recorder.sessions[ 0 ].cancelled, 1 );
	assert.equal( leaving.clock.pendingCount(), 0 );
	await flush();
	assert.deepEqual( leaving.fetch.calls, [] );
} );

test( 'leaving the screen clears a copy confirmation timer', async () => {
	const { part, clock, pressContinue } = setup( { state: { drafts: STORED } } );
	part( 'x', 'ihq-onb-posts__copy' ).click();
	await flush();
	assert.equal( clock.pendingCount(), 1 );
	pressContinue();
	assert.equal( clock.pendingCount(), 0 );
} );

// --- Language ---

test( 'requests use the language at the time; a language change re-labels without redrafting', async () => {
	const { api, fetch, input, part, strings, events, panel } = setup( { state: { platforms: [ 'x' ] } } );
	strings.add( 'ko', { postsChangePlaceholder: '예: 더 짧게' } );
	api.setLocale( 'ko' );
	await flush();
	assert.equal( fetch.calls.length, 1 );
	assert.equal( fetch.calls[ 0 ].body.lang, 'ko', 'the request was queued after the switch' );
	fetch.respond( 0, 200, { drafts: { x: 'Korean X' } } );
	await flush();
	events.emit( 'locale', 'ko' );
	assert.equal( input( 'x' ).getAttribute( 'placeholder' ), '예: 더 짧게' );
	assert.equal( part( 'x', 'ihq-onb-posts__draft' ).textContent, 'Korean X' );
	assert.equal( fetch.calls.length, 1 );
	assert.equal( panel( 'x' ).querySelector( 'h3' ).getAttribute( 'data-onb-string' ), 'platform_x' );
} );

// --- Pure helpers ---

test( 'selectedPlatforms keeps the store\'s order and only strings', () => {
	assert.deepEqual( selectedPlatforms( { platforms: [ 'x', 'instagram', 3 ] } ), [ 'x', 'instagram' ] );
	assert.deepEqual( selectedPlatforms( { platforms: 'x' } ), [] );
	assert.deepEqual( selectedPlatforms( null ), [] );
} );

test( 'cleanDrafts keeps non-empty text only', () => {
	assert.deepEqual( cleanDrafts( { x: 'X', line: '', instagram: ' ', tiktok: 3, qq: null } ), { x: 'X' } );
	assert.deepEqual( cleanDrafts( [ 'X' ] ), {} );
	assert.deepEqual( cleanDrafts( 'X' ), {} );
	assert.deepEqual( cleanDrafts( null ), {} );
} );

test( 'batches cuts a list into pieces of at most size', () => {
	assert.deepEqual( batches( [ 1, 2, 3, 4, 5 ], 2 ), [ [ 1, 2 ], [ 3, 4 ], [ 5 ] ] );
	assert.deepEqual( batches( [ 1, 2 ], 2 ), [ [ 1, 2 ] ] );
	assert.deepEqual( batches( [], 8 ), [] );
} );

test( 'errorOf maps results to the message codes', () => {
	assert.equal( errorOf( { ok: true, status: 200, data: {} } ), '' );
	assert.equal( errorOf( { ok: false, status: 0, data: null } ), 'timeout' );
	assert.equal( errorOf( { ok: false, status: 422, data: { error: 'refused' } } ), 'refused' );
	assert.equal( errorOf( { ok: false, status: 429, data: { error: 'limit' } } ), 'limit' );
	assert.equal( errorOf( { ok: false, status: 400, data: { error: 'invalid' } } ), 'invalid' );
	assert.equal( errorOf( { ok: false, status: 502, data: { error: 'failed' } } ), 'failed' );
	assert.equal( errorOf( { ok: false, status: 503, data: { error: 'unavailable' } } ), 'unavailable' );
	assert.equal( errorOf( { ok: false, status: 500, data: { error: 'toString' } } ), 'unavailable', 'not an inherited property' );
	assert.equal( errorOf( { ok: false, status: 401, data: 'Unauthorized' } ), 'unavailable' );
	assert.equal( errorOf( { ok: false, status: 401, data: null } ), 'unavailable' );
} );

test( 'withTimeout passes the answer through and clears its timer, or rejects after ms', async () => {
	const clock = createFakeClock();
	const calls = [];
	const answered = withTimeout( async ( url, options ) => {
		calls.push( [ url, options ] );
		return 'response';
	}, 100, clock );
	assert.equal( await answered( '/u', { a: 1 } ), 'response' );
	assert.deepEqual( calls, [ [ '/u', { a: 1 } ] ] );
	assert.equal( clock.pendingCount(), 0 );
	const failing = withTimeout( async () => {
		throw new Error( 'network' );
	}, 100, clock );
	await assert.rejects( failing( '/u', {} ), /network/ );
	assert.equal( clock.pendingCount(), 0 );
	const silent = withTimeout( () => new Promise( () => {} ), 100, clock );
	const pending = silent( '/u', {} );
	clock.tick( 100 );
	await assert.rejects( pending, /timed out/ );
} );

test( 'bytesToBase64 matches Node\'s base64, across chunk boundaries', () => {
	const bytes = new Uint8Array( 0x8000 * 2 + 5 );
	for ( let index = 0; index < bytes.length; index++ ) {
		bytes[ index ] = ( index * 7 ) % 256;
	}
	assert.equal( bytesToBase64( bytes ), Buffer.from( bytes ).toString( 'base64' ) );
	assert.equal( bytesToBase64( new Uint8Array( [ 65, 66, 67 ] ) ), 'QUJD' );
	assert.equal( bytesToBase64( new Uint8Array( [ 1 ] ), ( binary ) => 'custom:' + binary.length ), 'custom:1' );
} );

test( 'createDraftClient: a request that throws does not stop the ones after it', async () => {
	let calls = 0;
	const api = {
		config: CONFIG,
		state: { drafts: {} },
		getLocale: () => 'en',
		postJson: async () => {
			calls += 1;
			if ( 1 === calls ) {
				throw new Error( 'boom' );
			}
			return { ok: true, status: 200, data: { drafts: { x: 'X' } } };
		},
	};
	const client = createDraftClient( { api, fetchFn: () => {}, timers: createFakeClock() } );
	await assert.rejects( client.draft( [ 'x' ] ), /boom/ );
	assert.deepEqual( await client.draft( [ 'x' ] ), { drafts: { x: 'X' }, heard: '', error: '' } );
	assert.deepEqual( client.latest(), { x: 'X' } );
} );

// --- The voice recorder over MediaRecorder ---

function createMediaFakes( { supportedTypes = [ 'audio/webm' ], hasIsTypeSupported = true, constructorThrows = false } = {} ) {
	const tracks = [ { stopped: 0, stop() {
		this.stopped += 1;
	} } ];
	const stream = { getTracks: () => tracks };
	const created = [];
	class FakeMediaRecorder {
		constructor( givenStream, options ) {
			if ( constructorThrows ) {
				throw new Error( 'unsupported' );
			}
			this.stream = givenStream;
			this.options = options;
			this.mimeType = options.mimeType ? options.mimeType + ';codecs=opus' : '';
			this.state = 'inactive';
			this.stops = 0;
			created.push( this );
		}
		start() {
			this.state = 'recording';
		}
		stop() {
			this.stops += 1;
			this.state = 'inactive';
			if ( this.onstop ) {
				this.onstop();
			}
		}
	}
	if ( hasIsTypeSupported ) {
		FakeMediaRecorder.isTypeSupported = ( type ) => supportedTypes.includes( type );
	}
	const requests = [];
	const mediaDevices = { getUserMedia: async ( constraints ) => {
		requests.push( constraints );
		return stream;
	} };
	return { tracks, mediaDevices, FakeMediaRecorder, created, requests };
}

test( 'createVoiceRecorder is supported only with getUserMedia, MediaRecorder and Blob', () => {
	const { mediaDevices, FakeMediaRecorder } = createMediaFakes();
	assert.equal( createVoiceRecorder( { mediaDevices, MediaRecorderCtor: FakeMediaRecorder, BlobCtor: Blob } ).supported, true );
	assert.equal( createVoiceRecorder( { mediaDevices: undefined, MediaRecorderCtor: FakeMediaRecorder, BlobCtor: Blob } ).supported, false );
	assert.equal( createVoiceRecorder( { mediaDevices: {}, MediaRecorderCtor: FakeMediaRecorder, BlobCtor: Blob } ).supported, false );
	assert.equal( createVoiceRecorder( { mediaDevices, MediaRecorderCtor: undefined, BlobCtor: Blob } ).supported, false );
	assert.equal( createVoiceRecorder( { mediaDevices, MediaRecorderCtor: FakeMediaRecorder, BlobCtor: undefined } ).supported, false );
} );

test( 'createVoiceRecorder records in the first supported type and resolves to base64 and the type', async () => {
	const fakes = createMediaFakes( { supportedTypes: [ 'audio/ogg', 'audio/mp4' ] } );
	const recorder = createVoiceRecorder( { mediaDevices: fakes.mediaDevices, MediaRecorderCtor: fakes.FakeMediaRecorder, BlobCtor: Blob } );
	const session = await recorder.start();
	assert.deepEqual( fakes.requests, [ { audio: true } ] );
	const media = fakes.created[ 0 ];
	assert.deepEqual( media.options, { mimeType: 'audio/ogg' } );
	assert.equal( media.state, 'recording' );
	media.ondataavailable( { data: new Blob( [ 'AB' ] ) } );
	media.ondataavailable( { data: new Blob( [] ) } );
	media.ondataavailable( { data: null } );
	media.ondataavailable( { data: new Blob( [ 'C' ] ) } );
	const audio = await session.stop();
	assert.deepEqual( audio, { data: 'QUJD', mime: 'audio/ogg;codecs=opus' } );
	assert.equal( fakes.tracks[ 0 ].stopped, 1, 'the microphone is released' );
} );

test( 'createVoiceRecorder with nothing recorded resolves to empty data', async () => {
	const fakes = createMediaFakes();
	const session = await createVoiceRecorder( { mediaDevices: fakes.mediaDevices, MediaRecorderCtor: fakes.FakeMediaRecorder, BlobCtor: Blob } ).start();
	assert.deepEqual( await session.stop(), { data: '', mime: 'audio/webm;codecs=opus' } );
} );

test( 'createVoiceRecorder lets the browser choose when it cannot be asked, or supports none of the types', async () => {
	const unaskable = createMediaFakes( { hasIsTypeSupported: false } );
	await createVoiceRecorder( { mediaDevices: unaskable.mediaDevices, MediaRecorderCtor: unaskable.FakeMediaRecorder, BlobCtor: Blob } ).start();
	assert.deepEqual( unaskable.created[ 0 ].options, {} );
	const none = createMediaFakes( { supportedTypes: [] } );
	await createVoiceRecorder( { mediaDevices: none.mediaDevices, MediaRecorderCtor: none.FakeMediaRecorder, BlobCtor: Blob } ).start();
	assert.deepEqual( none.created[ 0 ].options, {} );
} );

test( 'createVoiceRecorder cancel stops recording and releases the microphone without a result', async () => {
	const fakes = createMediaFakes();
	const session = await createVoiceRecorder( { mediaDevices: fakes.mediaDevices, MediaRecorderCtor: fakes.FakeMediaRecorder, BlobCtor: Blob } ).start();
	session.cancel();
	assert.equal( fakes.created[ 0 ].stops, 1 );
	assert.equal( fakes.created[ 0 ].onstop, null );
	assert.equal( fakes.tracks[ 0 ].stopped, 1 );
	session.cancel();
	assert.equal( fakes.created[ 0 ].stops, 1, 'an inactive recorder is not stopped again' );
} );

test( 'createVoiceRecorder releases the microphone when the recorder cannot be made', async () => {
	const fakes = createMediaFakes( { constructorThrows: true } );
	await assert.rejects( createVoiceRecorder( { mediaDevices: fakes.mediaDevices, MediaRecorderCtor: fakes.FakeMediaRecorder, BlobCtor: Blob } ).start(), /unsupported/ );
	assert.equal( fakes.tracks[ 0 ].stopped, 1 );
} );

test( 'install registers the English copy and screen 14 as a MANUAL screen', () => {
	const registered = [];
	const added = [];
	install( {
		config: CONFIG,
		strings: { add: ( locale, table ) => added.push( [ locale, table ] ) },
		screens: { register: ( definition ) => registered.push( definition ), ADVANCE_MODES },
	}, { fetchFn: () => {}, timers: createFakeClock(), clipboard: null, share: null, recorder: createFakeRecorder() } );
	assert.deepEqual( added, [ [ 'en', POSTS_STRINGS_EN ] ] );
	assert.equal( registered.length, 1 );
	assert.equal( registered[ 0 ].id, 'draft_posts' );
	assert.equal( registered[ 0 ].advanceMode, ADVANCE_MODES.MANUAL );
	assert.equal( typeof registered[ 0 ].render, 'function' );
} );
