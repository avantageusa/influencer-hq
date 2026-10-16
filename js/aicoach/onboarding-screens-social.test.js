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
	PLATFORMS,
	SOCIAL_STRINGS_EN,
	HANDLE_MAX_LENGTH,
	storedSelection,
	missingHandles,
	selectionToSave,
	install,
} from './onboarding-screens-social.js';

const STATE = { stage: 'social_platforms', answers: {}, platforms: [], handles: {}, language: '', completed: false, completed_at: '' };

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
	install( api );
	// A screen after this one, so advancing has somewhere to go.
	engine.register( { id: 'world_championship', render: () => ( {} ) } );
	engine.start();
	const screen = () => content.querySelector( 'section' );
	const row = ( key ) => screen().querySelector( `div[data-platform="${ key }"]` );
	const tick = ( key ) => row( key ).querySelector( 'button' );
	const field = ( key ) => row( key ).querySelector( 'input' );
	const error = ( key ) => row( key ).querySelector( 'p' );
	// Clicks the screen's CONTINUE; returns whether the flow left the screen.
	const pressContinue = () => {
		screen().querySelector( '.ihq-onb-social__continue' ).click();
		return 'social_platforms' !== engine.current();
	};
	const type = ( key, value ) => {
		field( key ).value = value;
		field( key ).dispatchEvent( createEvent( 'input' ) );
		field( key ).dispatchEvent( createEvent( 'change' ) );
	};
	return { document, api, engine, clock, posts, strings, screen, row, tick, field, error, type, pressContinue };
}

// Far longer than any narration line or reading time.
const LONG_WAIT_MS = 10 * 60 * 1000;

// The engine sends each progress save on its serial chain, a microtask or more after api.save().
const flush = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

test( 'the 19 platforms, in the story order, are the store keys', () => {
	assert.deepEqual( PLATFORMS, [ 'instagram', 'facebook', 'tiktok', 'youtube', 'bilibili', 'douyin', 'kakaotalk', 'line', 'linkedin', 'pinterest', 'qq', 'reddit', 'rednote', 'snapchat', 'telegram', 'wechat', 'weibo', 'whatsapp', 'x' ] );
	const php = readFileSync( new URL( '../../inc/portal-onboarding-progress.php', import.meta.url ), 'utf8' );
	const body = php.slice( php.indexOf( 'function ihq_portal_onboarding_platforms()' ) );
	const list = body.slice( body.indexOf( 'array(' ), body.indexOf( ');' ) );
	assert.deepEqual( Array.from( list.matchAll( /'([a-z]+)'/g ), ( match ) => match[ 1 ] ), PLATFORMS );
	assert.equal( HANDLE_MAX_LENGTH, 190 );
} );

test( 'the screen lists the 19 platforms by name, with the PLATFORM and ACCOUNT ID columns, none ticked', () => {
	const { screen, tick, field } = setup();
	assert.equal( screen().querySelector( 'h2' ).textContent, 'Which Social Media sites do you post on?' );
	assert.equal( screen().querySelector( '.ihq-onb-social__columns' ).textContent, 'PLATFORMACCOUNT ID' );
	const names = screen().querySelectorAll( '.ihq-onb-social__name' ).map( ( name ) => name.textContent );
	assert.deepEqual( names, [ 'Instagram', 'Facebook', 'TikTok', 'YouTube', 'Bilibili', 'Douyin', 'KakaoTalk', 'LINE', 'LinkedIn', 'Pinterest', 'QQ', 'Reddit', 'RedNote', 'Snapchat', 'Telegram', 'WeChat', 'Weibo', 'WhatsApp', 'X' ] );
	PLATFORMS.forEach( ( key ) => {
		assert.equal( tick( key ).getAttribute( 'aria-checked' ), 'false', key );
		assert.equal( tick( key ).getAttribute( 'role' ), 'checkbox', key );
		assert.equal( field( key ).hidden, true, key );
	} );
} );

test( 'there are no Connect with … sign-in buttons', () => {
	const { screen } = setup();
	assert.ok( ! /connect/i.test( screen().textContent ) );
	assert.ok( ! Object.values( SOCIAL_STRINGS_EN ).some( ( text ) => /connect with/i.test( text ) ) );
} );

test( 'nothing is pre-ticked from registration channels (only the onboarding record counts)', () => {
	const { tick } = setup( { handles: { whatsapp: '+3816000000' } } );
	assert.equal( tick( 'whatsapp' ).getAttribute( 'aria-checked' ), 'false' );
} );

test( 'ticking a platform shows its handle field with the platform placeholder, and saves', async () => {
	const { tick, field, posts, document } = setup();
	tick( 'youtube' ).click();
	assert.equal( tick( 'youtube' ).getAttribute( 'aria-checked' ), 'true' );
	assert.equal( field( 'youtube' ).hidden, false );
	assert.equal( field( 'youtube' ).getAttribute( 'placeholder' ), 'youtube.com/@' );
	assert.equal( field( 'youtube' ).getAttribute( 'maxlength' ), '190' );
	assert.equal( field( 'youtube' ).getAttribute( 'aria-label' ), 'YouTube' );
	assert.equal( document.activeElement, field( 'youtube' ) );
	await flush();
	assert.deepEqual( posts, [ { platforms: [ 'youtube' ], handles: {} } ] );
} );

test( 'every platform has a placeholder', () => {
	const { tick, field } = setup();
	PLATFORMS.forEach( ( key ) => {
		tick( key ).click();
		assert.ok( field( key ).getAttribute( 'placeholder' ).length > 0, key );
	} );
	assert.equal( field( 'instagram' ).getAttribute( 'placeholder' ), '@username' );
	assert.equal( field( 'linkedin' ).getAttribute( 'placeholder' ), 'linkedin.com/in/' );
} );

test( 'typing a handle saves the ticked platforms with their handles, trimmed, in screen order', async () => {
	const { tick, type, posts } = setup();
	tick( 'x' ).click();
	tick( 'instagram' ).click();
	type( 'x', '  @me  ' );
	await flush();
	assert.deepEqual( posts.at( -1 ), { platforms: [ 'instagram', 'x' ], handles: { x: '@me' } } );
	type( 'instagram', '@insta' );
	await flush();
	assert.deepEqual( posts.at( -1 ), { platforms: [ 'instagram', 'x' ], handles: { x: '@me', instagram: '@insta' } } );
} );

test( 'unticking hides and clears the handle, and saves without it', async () => {
	const { tick, field, type, posts } = setup();
	tick( 'reddit' ).click();
	type( 'reddit', 'reddit.com/u/me' );
	tick( 'reddit' ).click();
	assert.equal( field( 'reddit' ).hidden, true );
	assert.equal( field( 'reddit' ).value, '' );
	await flush();
	assert.deepEqual( posts.at( -1 ), { platforms: [], handles: {} } );
	tick( 'reddit' ).click();
	assert.equal( field( 'reddit' ).value, '', 'the cleared handle does not come back' );
} );

test( 'CONTINUE is blocked while a ticked platform has no handle, with an error on that field', () => {
	const { engine, tick, type, field, error, document, pressContinue } = setup();
	tick( 'tiktok' ).click();
	tick( 'weibo' ).click();
	type( 'weibo', 'weibo.com/me' );
	assert.equal( pressContinue(), false );
	assert.equal( engine.current(), 'social_platforms' );
	assert.equal( error( 'tiktok' ).hidden, false );
	assert.equal( error( 'tiktok' ).textContent, 'Enter your account ID for this platform.' );
	assert.equal( field( 'tiktok' ).getAttribute( 'aria-invalid' ), 'true' );
	assert.equal( field( 'tiktok' ).getAttribute( 'aria-describedby' ), 'ihq-onb-social-error-tiktok' );
	assert.equal( error( 'tiktok' ).getAttribute( 'id' ), 'ihq-onb-social-error-tiktok' );
	assert.equal( document.activeElement, field( 'tiktok' ) );
	assert.equal( error( 'weibo' ).hidden, true );
	type( 'tiktok', '@tt' );
	assert.equal( error( 'tiktok' ).hidden, true );
	assert.equal( field( 'tiktok' ).getAttribute( 'aria-invalid' ), null );
	assert.equal( pressContinue(), true );
	assert.equal( engine.current(), 'world_championship' );
} );

test( 'a handle of only spaces does not count', () => {
	const { tick, type, error, pressContinue } = setup();
	tick( 'qq' ).click();
	type( 'qq', '   ' );
	assert.equal( pressContinue(), false );
	assert.equal( error( 'qq' ).hidden, false );
} );

test( 'unticking a platform with an error removes the error', () => {
	const { tick, error, pressContinue } = setup();
	tick( 'line' ).click();
	assert.equal( pressContinue(), false );
	tick( 'line' ).click();
	assert.equal( error( 'line' ).hidden, true );
	assert.equal( pressContinue(), true );
} );

test( 'no platform ticked is allowed to continue', async () => {
	const { engine, pressContinue, posts } = setup();
	assert.equal( pressContinue(), true );
	assert.equal( engine.current(), 'world_championship' );
	await flush();
	assert.deepEqual( posts, [ { stage: 'world_championship' } ] );
} );

test( 'CONTINUE is a button at the end of the screen, right under the platforms', () => {
	const { screen } = setup();
	const children = screen().children;
	const control = children.at( -1 );
	assert.equal( control.tagName, 'BUTTON' );
	assert.equal( control.getAttribute( 'type' ), 'button' );
	assert.equal( control.className, 'ihq-onb__button ihq-onb-social__continue' );
	assert.equal( control.textContent, 'CONTINUE' );
	assert.equal( control.getAttribute( 'data-onb-string' ), 'socialContinue' );
	assert.equal( screen().querySelectorAll( '.ihq-onb-social__continue' ).length, 1 );
	assert.equal( children.at( -2 ), screen().querySelector( '.ihq-onb-social__list' ) );
} );

test( 'clicking CONTINUE with every ticked platform filled in moves on once', () => {
	const { engine, tick, type, screen } = setup();
	tick( 'x' ).click();
	type( 'x', '@me' );
	const control = screen().querySelector( '.ihq-onb-social__continue' );
	control.click();
	assert.equal( engine.current(), 'world_championship' );
	// The old screen's control is tied to its showing: a late second click does nothing.
	control.click();
	assert.equal( engine.current(), 'world_championship' );
} );

test( 'the screen never moves on by itself, after its line or any reading time', async () => {
	const { engine, clock } = setup();
	await flush();
	clock.tick( LONG_WAIT_MS );
	await flush();
	assert.equal( engine.current(), 'social_platforms' );
	assert.equal( clock.pendingCount(), 0 );
} );

test( 'a returning Influencer sees their ticked platforms and handles', () => {
	const { tick, field, pressContinue } = setup( { platforms: [ 'x', 'line', 'myspace' ], handles: { x: '@me', line: 'me-line', youtube: 'stale' } } );
	assert.equal( tick( 'x' ).getAttribute( 'aria-checked' ), 'true' );
	assert.equal( field( 'x' ).value, '@me' );
	assert.equal( field( 'line' ).value, 'me-line' );
	assert.equal( tick( 'youtube' ).getAttribute( 'aria-checked' ), 'false' );
	assert.equal( pressContinue(), true );
} );

test( 'placeholders and field names follow the language; leaving the screen stops listening', () => {
	const { field, tick, api, strings, type, pressContinue } = setup();
	strings.add( 'ja', { placeholder_x: '@ユーザー名', platform_x: 'エックス' } );
	tick( 'x' ).click();
	api.setLocale( 'ja' );
	assert.equal( field( 'x' ).getAttribute( 'placeholder' ), '@ユーザー名' );
	assert.equal( field( 'x' ).getAttribute( 'aria-label' ), 'エックス' );
	const input = field( 'x' );
	type( 'x', '@me' );
	assert.equal( pressContinue(), true );
	api.setLocale( 'en' );
	assert.equal( input.getAttribute( 'placeholder' ), '@ユーザー名' );
} );

test( 'storedSelection keeps only the 19, in screen order, and handles of ticked platforms', () => {
	assert.deepEqual( storedSelection( { platforms: [ 'x', 'kick', 'instagram' ], handles: { x: '@a', twitch: 'b', instagram: 7 } } ), { ticked: [ 'instagram', 'x' ], handles: { x: '@a' } } );
	assert.deepEqual( storedSelection( {} ), { ticked: [], handles: {} } );
	assert.deepEqual( storedSelection( null ), { ticked: [], handles: {} } );
	assert.deepEqual( storedSelection( { platforms: 'x', handles: 'y' } ), { ticked: [], handles: {} } );
} );

test( 'missingHandles and selectionToSave', () => {
	const selection = { ticked: [ 'facebook', 'x', 'qq' ], handles: { facebook: ' fb ', x: '', qq: '  ' } };
	assert.deepEqual( missingHandles( selection ), [ 'x', 'qq' ] );
	assert.deepEqual( selectionToSave( selection ), { platforms: [ 'facebook', 'x', 'qq' ], handles: { facebook: 'fb' } } );
	assert.notEqual( selectionToSave( selection ).platforms, selection.ticked );
} );

test( 'registers screen 6 only, in MANUAL advance mode', () => {
	const registered = [];
	const strings = createStringTable();
	const register = ( definition ) => registered.push( { id: definition.id, advanceMode: definition.advanceMode } );
	install( { strings, screens: { register, ADVANCE_MODES } } );
	assert.deepEqual( registered, [ { id: 'social_platforms', advanceMode: ADVANCE_MODES.MANUAL } ] );
	assert.equal( strings.t( 'en', 'socialContinue' ), 'CONTINUE' );
	PLATFORMS.forEach( ( key ) => {
		assert.ok( strings.t( 'en', 'platform_' + key ), key );
		assert.ok( strings.t( 'en', 'placeholder_' + key ), key );
	} );
} );
