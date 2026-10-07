import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeClock } from './fake-clock.js';
import { createStallWatchdog, once } from './stall-watchdog.js';

const STALL_MS = 15000;

function setup() {
	const clock = createFakeClock();
	const stalls = [];
	const watchdog = createStallWatchdog( {
		stallMs: STALL_MS,
		onStall() {
			stalls.push( clock.now() );
		},
		setTimeout: clock.setTimeout,
		clearTimeout: clock.clearTimeout,
	} );
	return { clock, stalls, watchdog };
}

test( 'fires onStall once, exactly after stallMs without progress', () => {
	const { clock, stalls, watchdog } = setup();
	watchdog.arm();

	clock.tick( STALL_MS - 1 );
	assert.deepEqual( stalls, [] );
	clock.tick( 1 );
	assert.deepEqual( stalls, [ STALL_MS ] );
	clock.tick( 100000 );
	assert.deepEqual( stalls, [ STALL_MS ] );
} );

test( 'every arm() restarts the clock, so steady progress never stalls', () => {
	const { clock, stalls, watchdog } = setup();
	watchdog.arm();
	for ( let i = 0; i < 10; i++ ) {
		clock.tick( STALL_MS - 1 );
		watchdog.arm();
	}
	assert.deepEqual( stalls, [] );
	assert.equal( clock.pendingCount(), 1 );

	clock.tick( STALL_MS );
	assert.deepEqual( stalls, [ clock.now() ] );
} );

test( 'it measures time since the last progress, not total time', () => {
	const { clock, stalls, watchdog } = setup();
	watchdog.arm();
	clock.tick( 12900 );
	watchdog.arm();

	clock.tick( STALL_MS - 1 );
	assert.deepEqual( stalls, [] );
	clock.tick( 1 );
	assert.deepEqual( stalls, [ 12900 + STALL_MS ] );
} );

test( 'disarm cancels a pending stall', () => {
	const { clock, stalls, watchdog } = setup();
	watchdog.arm();
	clock.tick( 5000 );
	watchdog.disarm();

	assert.equal( clock.pendingCount(), 0 );
	clock.tick( 100000 );
	assert.deepEqual( stalls, [] );
} );

test( 'disarm without arm is harmless, and arm works again after disarm', () => {
	const { clock, stalls, watchdog } = setup();
	watchdog.disarm();
	watchdog.arm();
	watchdog.disarm();
	watchdog.arm();

	assert.equal( clock.pendingCount(), 1 );
	clock.tick( STALL_MS );
	assert.deepEqual( stalls, [ STALL_MS ] );
} );

test( 'once runs the function a single time and returns its first result', () => {
	const calls = [];
	const guarded = once( ( value ) => {
		calls.push( value );
		return 'result-' + value;
	} );

	assert.equal( guarded( 1 ), 'result-1' );
	assert.equal( guarded( 2 ), undefined );
	assert.equal( guarded( 3 ), undefined );
	assert.deepEqual( calls, [ 1 ] );
} );

test( 'once passes the arguments and this through', () => {
	const seen = [];
	const owner = {
		name: 'owner',
		run: once( function ( a, b ) {
			seen.push( [ this.name, a, b ] );
		} ),
	};

	owner.run( 'x', 'y' );
	assert.deepEqual( seen, [ [ 'owner', 'x', 'y' ] ] );
} );

test( 'a stall and the AbortError that pause() raises finish the audio once', () => {
	// Mirrors playFallbackAudio(): finish() is the watchdog callback, and the
	// pause() inside it makes the pending play() promise reject, whose
	// .catch( finish ) calls it a second time.
	const clock = createFakeClock();
	const steps = [];
	let watchdog = null;
	const finish = once( () => {
		steps.push( 'finish' );
		watchdog.disarm();
		steps.push( 'pause' );
		finish(); // the rejected play() calling back in
	} );
	watchdog = createStallWatchdog( {
		stallMs: STALL_MS,
		onStall: finish,
		setTimeout: clock.setTimeout,
		clearTimeout: clock.clearTimeout,
	} );

	watchdog.arm();
	clock.tick( STALL_MS );
	finish();

	assert.deepEqual( steps, [ 'finish', 'pause' ] );
	assert.equal( clock.pendingCount(), 0 );
} );
