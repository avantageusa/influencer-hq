import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createFakeClock } from './fake-clock.js';
import { createSequenceHold } from './sequence-hold.js';

function setup() {
	const clock = createFakeClock();
	const hold = createSequenceHold( {
		setTimeout: clock.setTimeout,
		clearTimeout: clock.clearTimeout,
		now: clock.now,
	} );
	return { clock, hold };
}

function recorder() {
	const calls = [];
	return {
		calls,
		fn() {
			calls.push( 'fired' );
		},
	};
}

test( 'a timer fires once, exactly when its time is up', () => {
	const { clock, hold } = setup();
	const fired = recorder();
	hold.createPausableTimeout( fired.fn, 1000 );

	clock.tick( 999 );
	assert.deepEqual( fired.calls, [] );
	clock.tick( 1 );
	assert.deepEqual( fired.calls, [ 'fired' ] );
	clock.tick( 5000 );
	assert.deepEqual( fired.calls, [ 'fired' ] );
	assert.equal( clock.pendingCount(), 0 );
} );

test( 'pause keeps the remaining time and resume restarts it', () => {
	const { clock, hold } = setup();
	const fired = recorder();
	hold.createPausableTimeout( fired.fn, 1000 );

	clock.tick( 400 );
	hold.pause();
	assert.equal( clock.pendingCount(), 0 );
	clock.tick( 60000 );
	assert.deepEqual( fired.calls, [] );

	hold.resume();
	clock.tick( 599 );
	assert.deepEqual( fired.calls, [] );
	clock.tick( 1 );
	assert.deepEqual( fired.calls, [ 'fired' ] );
} );

test( 'several pause/resume rounds add up the time actually waited', () => {
	const { clock, hold } = setup();
	const fired = recorder();
	hold.createPausableTimeout( fired.fn, 1000 );

	clock.tick( 300 );
	hold.pause();
	clock.tick( 9000 );
	hold.resume();
	clock.tick( 300 );
	hold.pause();
	clock.tick( 9000 );
	hold.resume();
	clock.tick( 399 );
	assert.deepEqual( fired.calls, [] );
	clock.tick( 1 );
	assert.deepEqual( fired.calls, [ 'fired' ] );
} );

test( 'a timer created while the hold is on does not start until resume (PR #63)', () => {
	const { clock, hold } = setup();
	const fired = recorder();
	hold.pause();
	hold.createPausableTimeout( fired.fn, 500 );

	assert.equal( clock.pendingCount(), 0 );
	clock.tick( 10000 );
	assert.deepEqual( fired.calls, [] );

	hold.resume();
	clock.tick( 499 );
	assert.deepEqual( fired.calls, [] );
	clock.tick( 1 );
	assert.deepEqual( fired.calls, [ 'fired' ] );
} );

test( 'cancel stops a running timer and later pause/resume do not bring it back', () => {
	const { clock, hold } = setup();
	const fired = recorder();
	const handle = hold.createPausableTimeout( fired.fn, 1000 );

	clock.tick( 100 );
	handle.cancel();
	assert.equal( clock.pendingCount(), 0 );
	hold.pause();
	hold.resume();
	assert.equal( clock.pendingCount(), 0 );
	clock.tick( 5000 );
	assert.deepEqual( fired.calls, [] );
} );

test( 'cancel on a paused timer also removes it', () => {
	const { clock, hold } = setup();
	const fired = recorder();
	const handle = hold.createPausableTimeout( fired.fn, 1000 );

	hold.pause();
	handle.cancel();
	hold.resume();
	assert.equal( clock.pendingCount(), 0 );
	clock.tick( 5000 );
	assert.deepEqual( fired.calls, [] );
} );

test( 'a timer that already fired is not re-armed by a later pause/resume', () => {
	const { clock, hold } = setup();
	const fired = recorder();
	hold.createPausableTimeout( fired.fn, 100 );

	clock.tick( 100 );
	hold.pause();
	hold.resume();
	clock.tick( 5000 );
	assert.deepEqual( fired.calls, [ 'fired' ] );
	assert.equal( clock.pendingCount(), 0 );
} );

test( 'resume twice does not arm a timer twice, and pause twice keeps the remaining time', () => {
	const { clock, hold } = setup();
	const fired = recorder();
	hold.createPausableTimeout( fired.fn, 1000 );

	clock.tick( 250 );
	hold.pause();
	clock.tick( 5000 );
	hold.pause();
	hold.resume();
	hold.resume();
	assert.equal( clock.pendingCount(), 1 );
	clock.tick( 750 );
	assert.deepEqual( fired.calls, [ 'fired' ] );
} );

test( 'each timer keeps its own remaining time', () => {
	const { clock, hold } = setup();
	const order = [];
	hold.createPausableTimeout( () => order.push( 'short' ), 300 );
	hold.createPausableTimeout( () => order.push( 'long' ), 1000 );

	clock.tick( 200 );
	hold.pause();
	clock.tick( 10000 );
	hold.resume();
	clock.tick( 100 );
	assert.deepEqual( order, [ 'short' ] );
	clock.tick( 699 );
	assert.deepEqual( order, [ 'short' ] );
	clock.tick( 1 );
	assert.deepEqual( order, [ 'short', 'long' ] );
} );

test( 'isPaused follows pause and resume', () => {
	const { hold } = setup();
	assert.equal( hold.isPaused(), false );
	hold.pause();
	assert.equal( hold.isPaused(), true );
	hold.resume();
	assert.equal( hold.isPaused(), false );
} );

test( 'waitWhileHeld resolves at once when nothing is on hold', async () => {
	const { hold } = setup();
	assert.equal( await hold.waitWhileHeld(), undefined );
} );

test( 'waitWhileHeld stays pending during the hold and every waiter is released by resume (ENGR-7051)', async () => {
	const { hold } = setup();
	const log = [];
	hold.pause();
	const first = hold.waitWhileHeld().then( () => log.push( 'first' ) );
	const second = hold.waitWhileHeld().then( () => log.push( 'second' ) );

	await Promise.resolve();
	await Promise.resolve();
	assert.deepEqual( log, [] );

	hold.resume();
	await Promise.all( [ first, second ] );
	assert.deepEqual( log, [ 'first', 'second' ] );
} );

test( 'a second pause after the release holds new waiters again', async () => {
	const { hold } = setup();
	hold.pause();
	hold.resume();
	hold.pause();
	let released = false;
	const waiting = hold.waitWhileHeld().then( () => {
		released = true;
	} );

	await Promise.resolve();
	await Promise.resolve();
	assert.equal( released, false );
	hold.resume();
	await waiting;
	assert.equal( released, true );
} );

test( 'resume restarts the paused timers', () => {
	const { clock, hold } = setup();
	hold.pause();
	hold.createPausableTimeout( () => {}, 100 );
	assert.equal( clock.pendingCount(), 0 );

	hold.resume();
	assert.equal( clock.pendingCount(), 1 );
} );

test( 'the external hold keeps waiters waiting until it is cleared, even after resume (PO-3109)', async () => {
	const { hold } = setup();
	const log = [];
	hold.pause();
	hold.setExternalHold( true );
	const waiting = hold.waitWhileHeld().then( () => log.push( 'released' ) );

	hold.resume();
	await Promise.resolve();
	await Promise.resolve();
	assert.deepEqual( log, [] );

	hold.setExternalHold( false );
	await waiting;
	assert.deepEqual( log, [ 'released' ] );
} );

test( 'the external hold alone holds waiters, and clearing it releases them', async () => {
	const { hold } = setup();
	let released = false;
	hold.setExternalHold( true );
	const waiting = hold.waitWhileHeld().then( () => {
		released = true;
	} );

	await Promise.resolve();
	await Promise.resolve();
	assert.equal( released, false );
	assert.equal( hold.isPaused(), false );

	hold.setExternalHold( false );
	await waiting;
	assert.equal( released, true );
} );

test( 'the external hold does not touch the timers', () => {
	const { clock, hold } = setup();
	const fired = recorder();
	hold.createPausableTimeout( fired.fn, 100 );

	hold.setExternalHold( true );
	clock.tick( 100 );
	assert.deepEqual( fired.calls, [ 'fired' ] );
} );

test( 'pause still holds waiters after the external hold is cleared', async () => {
	const { hold } = setup();
	let released = false;
	hold.pause();
	hold.setExternalHold( true );
	const waiting = hold.waitWhileHeld().then( () => {
		released = true;
	} );

	hold.setExternalHold( false );
	await Promise.resolve();
	await Promise.resolve();
	assert.equal( released, false );

	hold.resume();
	await waiting;
	assert.equal( released, true );
} );

test( 'waitWhileHeld resolves at once after the external hold was turned off', async () => {
	const { hold } = setup();
	hold.setExternalHold( true );
	hold.setExternalHold( false );
	assert.equal( await hold.waitWhileHeld(), undefined );
} );
