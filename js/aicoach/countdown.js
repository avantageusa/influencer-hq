/**
 * Counting down on the appointment link page (PO-3109, FR-18). Pure functions: the page
 * script (js/aicoach/appointment-page.js) does the DOM and the timers.
 *
 * The seconds left come from the server when the page is rendered, so a phone with a
 * wrong clock still counts to the right moment: the page only measures how long it has
 * been open.
 */

const SECONDS_PER_MINUTE = 60;
const SECONDS_PER_HOUR = 60 * SECONDS_PER_MINUTE;
const SECONDS_PER_DAY = 24 * SECONDS_PER_HOUR;
const MS_PER_SECOND = 1000;

// setTimeout stores its delay in 32 bits; a longer wait fires immediately.
const MAX_TIMER_MS = 2147483647;

function pad( value ) {
	return String( value ).padStart( 2, '0' );
}

/**
 * The time left as text: "HH:MM:SS", with the days in front when there is a day or more
 * ("2d 03:04:05"). Anything that is not a positive number of seconds is zero.
 *
 * @param {number} totalSeconds
 * @return {string}
 */
export function formatCountdown( totalSeconds ) {
	const whole = Number.isFinite( totalSeconds ) && totalSeconds > 0 ? Math.floor( totalSeconds ) : 0;
	const days = Math.floor( whole / SECONDS_PER_DAY );
	const hours = Math.floor( ( whole % SECONDS_PER_DAY ) / SECONDS_PER_HOUR );
	const minutes = Math.floor( ( whole % SECONDS_PER_HOUR ) / SECONDS_PER_MINUTE );
	const seconds = whole % SECONDS_PER_MINUTE;
	const clock = pad( hours ) + ':' + pad( minutes ) + ':' + pad( seconds );
	return days > 0 ? days + 'd ' + clock : clock;
}

/**
 * Seconds left now, given the seconds left when the page was rendered and how long ago
 * that was. A partial second still counts as a second left, so zero means it is time.
 *
 * @param {number} secondsAtRender
 * @param {number} elapsedMs
 * @return {number}
 */
export function secondsLeft( secondsAtRender, elapsedMs ) {
	if ( ! Number.isFinite( secondsAtRender ) || ! Number.isFinite( elapsedMs ) ) {
		return 0;
	}
	return Math.max( 0, Math.ceil( secondsAtRender - elapsedMs / MS_PER_SECOND ) );
}

/**
 * How long to wait before reloading to show the next state, in milliseconds, or null
 * when there is nothing to wait for (no next state, not a number, not positive, or too
 * far away for a timer).
 *
 * @param {number|string|undefined|null} seconds
 * @return {number|null}
 */
export function reloadDelayMs( seconds ) {
	if ( null === seconds || undefined === seconds || '' === seconds ) {
		return null;
	}
	const wanted = Number( seconds );
	if ( ! Number.isFinite( wanted ) || wanted <= 0 ) {
		return null;
	}
	const delay = wanted * MS_PER_SECOND;
	return delay > MAX_TIMER_MS ? null : delay;
}
