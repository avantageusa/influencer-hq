/**
 * Browser back does not move the onboarding (ENGR-7566, PO-3218).
 *
 * While onboarding is open, one extra history entry sits on top of the Coach page. Back
 * pops it; the guard puts it straight back, so the page, and the screen, stay where they
 * are. Closing the onboarding removes the guard and its entry.
 */

/** Marks the guard's own history entry. */
export const GUARD_STATE_KEY = 'ihqOnboarding';

/**
 * @param {Object} deps
 * @param {Object} deps.history A History (pushState, back, state).
 * @param {Object} deps.window  Something with addEventListener/removeEventListener for 'popstate'.
 * @return {Object} { install, uninstall, isInstalled }.
 */
export function createHistoryGuard( { history, window } ) {
	let installed = false;

	function pushGuard() {
		history.pushState( { [ GUARD_STATE_KEY ]: true }, '' );
	}

	function onPopState() {
		pushGuard();
	}

	function install() {
		if ( installed ) {
			return;
		}
		installed = true;
		pushGuard();
		window.addEventListener( 'popstate', onPopState );
	}

	/**
	 * Stops guarding and drops the guard's entry when it is the current one, so the next
	 * Back leaves the Coach page as it normally would.
	 */
	function uninstall() {
		if ( ! installed ) {
			return;
		}
		installed = false;
		window.removeEventListener( 'popstate', onPopState );
		if ( history.state && history.state[ GUARD_STATE_KEY ] ) {
			history.back();
		}
	}

	return { install, uninstall, isInstalled: () => installed };
}
