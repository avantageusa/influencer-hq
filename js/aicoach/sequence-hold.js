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
 *   isPaused: Function,
 *   setExternalHold: Function,
 *   waitWhileExternallyHeld: Function
 * }}
 */
export function createSequenceHold( { setTimeout, clearTimeout, now } ) {
	const timers = new Set();
	// A timer created while the hold is on must not start counting down on
	// creation (PR #63): without this flag a timer made mid-panel, for example
	// by a language switch restarting a clip, would run live under the panel.
	let paused = false;
	// A second reason to hold new screens back that does not touch the timers
	// (PO-3109: the Time is up screen). Whoever owns it clears it; resume() must
	// not release a screen that is still waiting on it.
	let externalHold = false;
	// Resolvers for everything currently waiting out a hold (ENGR-7051);
	// released together once no hold is left.
	let waiters = [];
	// Resolvers waiting for the external hold alone (see waitWhileExternallyHeld).
	let externalWaiters = [];

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
		releaseWaiters();
	}

	function releaseWaiters() {
		if ( paused || externalHold ) {
			return;
		}
		const released = waiters;
		waiters = [];
		released.forEach( function ( release ) {
			release();
		} );
	}

	// Turns the external hold on or off. It holds back waitWhileHeld() only:
	// the timers are paused and resumed by pause()/resume().
	function setExternalHold( on ) {
		externalHold = Boolean( on );
		releaseWaiters();
		if ( ! externalHold ) {
			const released = externalWaiters;
			externalWaiters = [];
			released.forEach( function ( release ) {
				release();
			} );
		}
	}

	// Like waitWhileHeld(), but for the external hold only: a caller that must not
	// run underneath the Time is up screen, yet is allowed to run while the Ask
	// Sami panel has the timers paused.
	function waitWhileExternallyHeld() {
		if ( ! externalHold ) {
			return Promise.resolve();
		}
		return new Promise( function ( resolve ) {
			externalWaiters.push( resolve );
		} );
	}

	// Resolves immediately unless the sequence is on hold.
	function waitWhileHeld() {
		if ( ! paused && ! externalHold ) {
			return Promise.resolve();
		}
		return new Promise( function ( resolve ) {
			waiters.push( resolve );
		} );
	}

	function isPaused() {
		return paused;
	}

	return { createPausableTimeout, pause, resume, waitWhileHeld, isPaused, setExternalHold, waitWhileExternallyHeld };
}
