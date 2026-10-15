import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createEmitter } from './onboarding-events.js';
import { createStringTable } from './onboarding-strings.js';
import { CONFIG_ELEMENT_ID, DEFAULT_LOCALE, parseConfig, clipFor, createApi, installModules } from './onboarding-boot.js';

const STATE = { stage: '', answers: {}, platforms: [], handles: {}, language: '', completed: false, completed_at: '' };

test( 'constants', () => {
	assert.equal( CONFIG_ELEMENT_ID, 'ihq-onboarding-config' );
	assert.equal( DEFAULT_LOCALE, 'en' );
} );

test( 'parseConfig reads a config with a state', () => {
	assert.deepEqual( parseConfig( JSON.stringify( { state: STATE, modules: [] } ) ), { state: STATE, modules: [] } );
} );

test( 'parseConfig gives null for anything that cannot start the onboarding', () => {
	for ( const json of [ null, undefined, '', 'not json', '[]', 'null', '5', '{}', '{"state":null}', '{"state":"x"}' ] ) {
		assert.equal( parseConfig( json ), null, String( json ) );
	}
} );

test( 'clipFor finds a clip by stage and locale, else empty', () => {
	const clips = { equity: { en: 'en.mp4', ja: 'ja.mp4', ko: 5 }, broken: 'x' };
	assert.equal( clipFor( clips, 'equity', 'ja' ), 'ja.mp4' );
	assert.equal( clipFor( clips, 'equity', 'vi' ), '' );
	assert.equal( clipFor( clips, 'equity', 'ko' ), '' );
	assert.equal( clipFor( clips, 'classic', 'en' ), '' );
	assert.equal( clipFor( clips, 'broken', 'en' ), '' );
	assert.equal( clipFor( undefined, 'equity', 'en' ), '' );
	assert.equal( clipFor( [], 'equity', 'en' ), '' );
} );

test( 'createApi starts in the saved language, else English', () => {
	const events = createEmitter();
	const strings = createStringTable();
	assert.equal( createApi( { config: { state: { ...STATE, language: 'th' } }, events, strings } ).getLocale(), 'th' );
	assert.equal( createApi( { config: { state: STATE }, events, strings } ).getLocale(), 'en' );
} );

test( 'createApi exposes the config, a copy of the state, events and strings', () => {
	const events = createEmitter();
	const strings = createStringTable();
	const config = { state: STATE };
	const api = createApi( { config, events, strings } );
	assert.equal( api.config, config );
	assert.deepEqual( api.state, STATE );
	assert.notEqual( api.state, STATE );
	assert.equal( api.events, events );
	assert.equal( api.strings, strings );
	assert.equal( api.shell, null );
} );

test( 'setLocale changes the language and emits locale once per change', () => {
	const events = createEmitter();
	const seen = [];
	events.on( 'locale', ( locale ) => seen.push( locale ) );
	const api = createApi( { config: { state: STATE }, events, strings: createStringTable() } );
	api.setLocale( 'ko' );
	api.setLocale( 'ko' );
	api.setLocale( 'en' );
	assert.deepEqual( seen, [ 'ko', 'en' ] );
	assert.equal( api.getLocale(), 'en' );
} );

test( 'clipUrl and imageUrl look up the current screen assets', () => {
	const config = { state: STATE, clips: { classic: { en: 'c-en.mp4', zh: 'c-zh.mp4' } }, images: { classic: 'classic.webp', bad: 3 } };
	const api = createApi( { config, events: createEmitter(), strings: createStringTable() } );
	assert.equal( api.clipUrl( 'classic' ), 'c-en.mp4' );
	api.setLocale( 'zh' );
	assert.equal( api.clipUrl( 'classic' ), 'c-zh.mp4' );
	assert.equal( api.imageUrl( 'classic' ), 'classic.webp' );
	assert.equal( api.imageUrl( 'bad' ), '' );
	assert.equal( api.imageUrl( 'equity' ), '' );
	const bare = createApi( { config: { state: STATE }, events: createEmitter(), strings: createStringTable() } );
	assert.equal( bare.imageUrl( 'classic' ), '' );
	assert.equal( bare.clipUrl( 'classic' ), '' );
} );

test( 'installModules imports and installs in order, skipping ones without install()', async () => {
	const api = { name: 'api' };
	const calls = [];
	const modules = {
		'@ihq/aicoach/onboarding-screens-a': { install: ( received ) => calls.push( [ 'a', received ] ) },
		'@ihq/aicoach/onboarding-addon-b': { other: true },
		'@ihq/aicoach/onboarding-screens-c': { install: ( received ) => calls.push( [ 'c', received ] ) },
	};
	const imported = [];
	const installed = await installModules( {
		ids: Object.keys( modules ),
		importFn: async ( id ) => {
			imported.push( id );
			return modules[ id ];
		},
		api,
	} );
	assert.deepEqual( imported, Object.keys( modules ) );
	assert.deepEqual( calls, [ [ 'a', api ], [ 'c', api ] ] );
	assert.deepEqual( installed, [ '@ihq/aicoach/onboarding-screens-a', '@ihq/aicoach/onboarding-screens-c' ] );
} );

test( 'a module that fails to load or install is reported and the rest still install', async () => {
	const errors = [];
	const loadError = new Error( 'load' );
	const installError = new Error( 'install' );
	const installed = await installModules( {
		ids: [ 'bad-load', 'bad-install', 'good' ],
		importFn: async ( id ) => {
			if ( 'bad-load' === id ) {
				throw loadError;
			}
			if ( 'bad-install' === id ) {
				return {
					install: () => {
						throw installError;
					},
				};
			}
			return { install: () => {} };
		},
		api: {},
		onError: ( error ) => errors.push( error ),
	} );
	assert.deepEqual( errors, [ loadError, installError ] );
	assert.deepEqual( installed, [ 'good' ] );
} );

test( 'installModules with no list installs nothing', async () => {
	assert.deepEqual( await installModules( { ids: undefined, importFn: async () => ( {} ), api: {} } ), [] );
	assert.deepEqual( await installModules( { ids: 'x', importFn: async () => ( {} ), api: {} } ), [] );
} );
