/**
 * Progress-based stall watchdog and a call-once guard (ENGR-7066), extracted
 * from playFallbackAudio() in js/aicoach-coach-flow.js (ENGR-7051 follow-up).
 *
 * A recording that stalls without erroring fires neither 'ended' nor 'error',
 * so playback never finished and the sequence stayed held. Every sign of
 * progress re-arms the watchdog; if it fires, playback is abandoned. It
 * measures the time since the last progress, not the total length, so it
 * cannot cut off a long answer that is playing.
 */

/**
 * @param {Object}   deps
 * @param {number}   deps.stallMs      Time without progress before onStall runs.
 * @param {Function} deps.onStall      Called once the watchdog fires.
 * @param {Function} deps.setTimeout   Same contract as window.setTimeout.
 * @param {Function} deps.clearTimeout Same contract as window.clearTimeout.
 * @return {{ arm: Function, disarm: Function }}
 */
export function createStallWatchdog( { stallMs, onStall, setTimeout, clearTimeout } ) {
	let timerId = null;

	// Starts the countdown, or restarts it from zero if one is running.
	function arm() {
		clearTimeout( timerId );
		timerId = setTimeout( onStall, stallMs );
	}

	function disarm() {
		clearTimeout( timerId );
		timerId = null;
	}

	return { arm, disarm };
}

/**
 * Wraps a function so it runs at most once, however many times it is called.
 * playFallbackAudio()'s finish() is reached by the watchdog, by 'ended' and
 * 'error', and again by the AbortError that audio.pause() raises on the
 * pending play() promise; only the first call may do anything.
 *
 * @param {Function} fn
 * @return {Function}
 */
export function once( fn ) {
	let called = false;
	return function ( ...args ) {
		if ( called ) {
			return undefined;
		}
		called = true;
		return fn.apply( this, args );
	};
}
