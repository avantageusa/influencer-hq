/**
 * Pausable timers and the sequence "hold" for the AI Coach flow (ENGR-7066).
 *
 * Extracted unchanged in behaviour from js/aicoach-coach-flow.js so it can be
 * tested without a browser: the timer functions and the clock are injected.
 *
 * Why it exists: Ask Sami's panel pauses everything that would otherwise move
 * the screen on underneath it (reading dwells, clip safety caps). Every timer
 * the sequence uses is created through createPausableTimeout() so
 * pause()/resume() can freeze and restart them with the remaining time
 * preserved. waitWhileHeld() lets the screen loop wait out a hold before it
 * starts anything new (ENGR-7051).
 */

/**
 * @param {Object}   deps
 * @param {Function} deps.setTimeout   Same contract as window.setTimeout.
 * @param {Function} deps.clearTimeout Same contract as window.clearTimeout.
 * @param {Function} deps.now          Returns the current time in ms.
 * @return {{
 *   createPausableTimeout: Function,
 *   pause: Function,
 *   resume: Function,
 *   waitWhileHeld: Function,
 *   isPaused: Function
 * }}
 */
export function createSequenceHold( { setTimeout, clearTimeout, now } ) {
	const timers = new Set();
	// A timer created while the hold is on must not start counting down on
	// creation (PR #63): without this flag a timer made mid-panel, for example
	// by a language switch restarting a clip, would run live under the panel.
	let paused = false;
	// Resolvers for everything currently waiting out a hold (ENGR-7051);
	// released together by resume().
	let waiters = [];

	function createPausableTimeout( callback, ms ) {
		let remaining = ms;
		let timerId = null;
		let armedAt = null;
		const handle = {
			pause() {
				if ( null === timerId ) {
					return;
				}
				clearTimeout( timerId );
				timerId = null;
				remaining = Math.max( 0, remaining - ( now() - armedAt ) );
			},
			resume() {
				if ( null !== timerId ) {
					return; // already running; pause()/resume() calls are not expected to nest
				}
				armedAt = now();
				timerId = setTimeout( function () {
					timerId = null;
					timers.delete( handle );
					callback();
				}, remaining );
			},
			cancel() {
				if ( null !== timerId ) {
					clearTimeout( timerId );
					timerId = null;
				}
				timers.delete( handle );
			},
		};
		timers.add( handle );
		if ( ! paused ) {
			handle.resume();
		}
		return handle;
	}

	function pause() {
		paused = true;
		timers.forEach( function ( timer ) {
			timer.pause();
		} );
	}

	function resume() {
		paused = false;
		timers.forEach( function ( timer ) {
			timer.resume();
		} );
		const released = waiters;
		waiters = [];
		released.forEach( function ( release ) {
			release();
		} );
	}

	// Resolves immediately unless the sequence is on hold.
	function waitWhileHeld() {
		if ( ! paused ) {
			return Promise.resolve();
		}
		return new Promise( function ( resolve ) {
			waiters.push( resolve );
		} );
	}

	function isPaused() {
		return paused;
	}

	return { createPausableTimeout, pause, resume, waitWhileHeld, isPaused };
}
