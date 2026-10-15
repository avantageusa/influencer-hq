import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { createFakeDocument, createEvent } from './fake-dom.js';
import { createEmitter } from './onboarding-events.js';
import { createStringTable } from './onboarding-strings.js';
import { createApi } from './onboarding-boot.js';
import { SEQUENCE, startStage, nextStage } from './onboarding-sequence.js';
import { createEngine, ENGINE_STRINGS_EN } from './onboarding-engine.js';
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
	strings.add( 'en', ENGINE_STRINGS_EN );
	const api = createApi( { config: { state: { ...STATE, ...state }, progressUrl: '/p', completeUrl: '/c', nonce: 'n' }, events, strings } );
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
	// A screen after this one, so advancing has somewhere to go.
	engine.register( { id: 'world_championship', render: () => ( {} ) } );
	engine.start();
	const screen = () => content.querySelector( 'section' );
	const row = ( key ) => screen().querySelector( `div[data-platform="${ key }"]` );
	const tick = ( key ) => row( key ).querySelector( 'button' );
	const field = ( key ) => row( key ).querySelector( 'input' );
	const error = ( key ) => row( key ).querySelector( 'p' );
	const type = ( key, value ) => {
		field( key ).value = value;
		field( key ).dispatchEvent( createEvent( 'input' ) );
		field( key ).dispatchEvent( createEvent( 'change' ) );
	};
	return { document, api, engine, posts, strings, screen, row, tick, field, error, type };
}

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
	const { engine, tick, type, field, error, document } = setup();
	tick( 'tiktok' ).click();
	tick( 'weibo' ).click();
	type( 'weibo', 'weibo.com/me' );
	assert.equal( engine.advance(), false );
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
	assert.equal( engine.advance(), true );
} );

test( 'a handle of only spaces does not count', () => {
	const { engine, tick, type } = setup();
	tick( 'qq' ).click();
	type( 'qq', '   ' );
	assert.equal( engine.advance(), false );
} );

test( 'unticking a platform with an error removes the error', () => {
	const { engine, tick, error } = setup();
	tick( 'line' ).click();
	engine.advance();
	tick( 'line' ).click();
	assert.equal( error( 'line' ).hidden, true );
	assert.equal( engine.advance(), true );
} );

test( 'no platform ticked is allowed to continue', () => {
	const { engine } = setup();
	assert.equal( engine.advance(), true );
	assert.equal( engine.current(), 'world_championship' );
} );

test( 'a returning Influencer sees their ticked platforms and handles', () => {
	const { tick, field, engine } = setup( { platforms: [ 'x', 'line', 'myspace' ], handles: { x: '@me', line: 'me-line', youtube: 'stale' } } );
	assert.equal( tick( 'x' ).getAttribute( 'aria-checked' ), 'true' );
	assert.equal( field( 'x' ).value, '@me' );
	assert.equal( field( 'line' ).value, 'me-line' );
	assert.equal( tick( 'youtube' ).getAttribute( 'aria-checked' ), 'false' );
	assert.equal( engine.advance(), true );
} );

test( 'placeholders and field names follow the language; leaving the screen stops listening', () => {
	const { field, tick, api, strings, engine, type } = setup();
	strings.add( 'ja', { placeholder_x: '@ユーザー名', platform_x: 'エックス' } );
	tick( 'x' ).click();
	api.setLocale( 'ja' );
	assert.equal( field( 'x' ).getAttribute( 'placeholder' ), '@ユーザー名' );
	assert.equal( field( 'x' ).getAttribute( 'aria-label' ), 'エックス' );
	const input = field( 'x' );
	type( 'x', '@me' );
	assert.equal( engine.advance(), true );
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

test( 'registers screen 6 only', () => {
	const registered = [];
	const strings = createStringTable();
	install( { strings, screens: { register: ( definition ) => registered.push( definition.id ) } } );
	assert.deepEqual( registered, [ 'social_platforms' ] );
	PLATFORMS.forEach( ( key ) => {
		assert.ok( strings.t( 'en', 'platform_' + key ), key );
		assert.ok( strings.t( 'en', 'placeholder_' + key ), key );
	} );
} );
