import { test } from 'node:test';
import assert from 'node:assert/strict';
import { installModules } from './onboarding-boot.js';
import { SCREEN_MODULE_PATTERN, failedScreenModules, launch } from './onboarding-launch.js';

const TEACHING = '@ihq/aicoach/onboarding-screens-teaching';
const QUESTIONS = '@ihq/aicoach/onboarding-screens-questions';
const LANGUAGE = '@ihq/aicoach/onboarding-addon-language';

// An engine whose start() answers the given stage, and a log of every call made.
function setup( { startsAt = 'game_of_kings' } = {} ) {
	const log = [];
	const errors = [];
	const engine = {
		start: () => {
			log.push( 'start' );
			return startsAt;
		},
	};
	const shell = { close: () => log.push( 'close' ) };
	const ready = () => log.push( 'ready' );
	const onError = ( error ) => errors.push( error.message );
	return { engine, shell, ready, onError, log, errors };
}

test( 'screen modules are the onboarding-screens-* ids; add-ons are not', () => {
	assert.equal( SCREEN_MODULE_PATTERN.test( TEACHING ), true );
	assert.equal( SCREEN_MODULE_PATTERN.test( 'onboarding-screens-closing' ), true );
	assert.equal( SCREEN_MODULE_PATTERN.test( LANGUAGE ), false );
	assert.equal( SCREEN_MODULE_PATTERN.test( '@ihq/aicoach/onboarding-screens-' ), false );
	assert.equal( SCREEN_MODULE_PATTERN.test( '@ihq/aicoach/not-onboarding-screens-teaching' ), false );
} );

test( 'failedScreenModules lists the screen modules that did not install, in listed order', () => {
	assert.deepEqual( failedScreenModules( { listed: [ QUESTIONS, LANGUAGE, TEACHING ], installed: [] } ), [ QUESTIONS, TEACHING ] );
	assert.deepEqual( failedScreenModules( { listed: [ TEACHING, QUESTIONS ], installed: [ QUESTIONS ] } ), [ TEACHING ] );
	assert.deepEqual( failedScreenModules( { listed: [ TEACHING, LANGUAGE ], installed: [ TEACHING ] } ), [] );
} );

test( 'failedScreenModules treats a missing module list as empty', () => {
	assert.deepEqual( failedScreenModules( { listed: undefined, installed: [] } ), [] );
	assert.deepEqual( failedScreenModules( { listed: 'not a list', installed: [] } ), [] );
} );

test( 'when every screen module installs, the modules are told it is ready and the engine opens', () => {
	const { engine, shell, ready, onError, log, errors } = setup();
	const stage = launch( { engine, shell, listed: [ TEACHING, LANGUAGE ], installed: [ TEACHING, LANGUAGE ], ready, onError } );
	assert.equal( stage, 'game_of_kings' );
	assert.deepEqual( log, [ 'ready', 'start' ] );
	assert.deepEqual( errors, [] );
} );

test( 'a failed add-on does not stop the onboarding', () => {
	const { engine, shell, ready, onError, log, errors } = setup();
	const stage = launch( { engine, shell, listed: [ TEACHING, LANGUAGE ], installed: [ TEACHING ], ready, onError } );
	assert.equal( stage, 'game_of_kings' );
	assert.deepEqual( log, [ 'ready', 'start' ] );
	assert.deepEqual( errors, [] );
} );

test( 'a listed screen module that failed closes the layer without starting, so no stage is saved past it', () => {
	const { engine, shell, ready, onError, log, errors } = setup();
	const stage = launch( { engine, shell, listed: [ TEACHING, QUESTIONS ], installed: [ TEACHING ], ready, onError } );
	assert.equal( stage, null );
	assert.deepEqual( log, [ 'close' ] );
	assert.deepEqual( errors, [ 'Onboarding: screen modules failed to load (' + QUESTIONS + '); closed until the next visit' ] );
} );

test( 'a listed screen module that throws on import keeps the onboarding closed', async () => {
	const { engine, shell, ready, onError, log, errors } = setup();
	const listed = [ TEACHING, QUESTIONS ];
	const importFn = async ( id ) => {
		if ( QUESTIONS === id ) {
			throw new Error( 'network error importing ' + id );
		}
		return { install: () => log.push( 'install ' + id ) };
	};
	const installed = await installModules( { ids: listed, importFn, api: {}, onError } );
	assert.equal( launch( { engine, shell, listed, installed, ready, onError } ), null );
	assert.deepEqual( log, [ 'install ' + TEACHING, 'close' ] );
	assert.deepEqual( errors, [
		'network error importing ' + QUESTIONS,
		'Onboarding: screen modules failed to load (' + QUESTIONS + '); closed until the next visit',
	] );
} );

test( 'when no screen is installed the layer is closed instead of left empty', () => {
	const { engine, shell, ready, onError, log, errors } = setup( { startsAt: null } );
	const stage = launch( { engine, shell, listed: [ LANGUAGE ], installed: [ LANGUAGE ], ready, onError } );
	assert.equal( stage, null );
	assert.deepEqual( log, [ 'ready', 'start', 'close' ] );
	assert.deepEqual( errors, [ 'Onboarding: no screen is installed; closed until the next visit' ] );
} );

test( 'without onError the reason goes to console.error', ( context ) => {
	const logged = [];
	context.mock.method( console, 'error', ( error ) => logged.push( error.message ) );
	const { engine, shell, ready, log } = setup( { startsAt: null } );
	assert.equal( launch( { engine, shell, listed: [], installed: [], ready } ), null );
	assert.deepEqual( logged, [ 'Onboarding: no screen is installed; closed until the next visit' ] );
	assert.deepEqual( log, [ 'ready', 'start', 'close' ] );
} );
