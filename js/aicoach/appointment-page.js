/**
 * The appointment link page (PO-3109, FR-18): the countdown on the waiting page and a
 * reload at the next boundary, so the page shows the next state without the visitor
 * doing anything. Everything it needs comes from the server in data attributes on
 * #aicoach-link: how many seconds to the start, and how many to the next state.
 */
import { formatCountdown, secondsLeft, reloadDelayMs } from '@ihq/aicoach/countdown';

const COUNTDOWN_TICK_MS = 1000;
// Reload a little after the boundary, so the server has certainly crossed it.
const RELOAD_MARGIN_MS = 500;

const root = document.getElementById( 'aicoach-link' );
if ( root ) {
	const openedAt = performance.now();

	const countdown = document.getElementById( 'aicoach-link-countdown' );
	const secondsToStart = Number( root.dataset.secondsToStart );
	if ( countdown && Number.isFinite( secondsToStart ) ) {
		const tick = function () {
			countdown.textContent = formatCountdown( secondsLeft( secondsToStart, performance.now() - openedAt ) );
		};
		tick();
		window.setInterval( tick, COUNTDOWN_TICK_MS );
	}

	const reloadDelay = reloadDelayMs( root.dataset.reloadIn );
	if ( null !== reloadDelay ) {
		window.setTimeout( function () {
			window.location.reload();
		}, reloadDelay + RELOAD_MARGIN_MS );
	}
}
