/**
 * A small event emitter the parts of the portal onboarding talk through (ENGR-7565).
 *
 * The onboarding is built by several tasks (the screen engine, the screens, the language
 * selector, clip preloading). Each one is a module the entry installs; they announce and
 * hear things like "a screen started" or "the language changed" here instead of calling
 * each other, so a module can be added without editing the others.
 */

function reportError( error ) {
	// eslint-disable-next-line no-console -- a broken listener must be visible, not swallowed.
	console.error( error );
}

/**
 * @return {Object} { on, off, emit }.
 */
export function createEmitter() {
	const listeners = new Map();

	/**
	 * @param {string}   type
	 * @param {Function} listener
	 * @return {Function} Removes the listener.
	 */
	function on( type, listener ) {
		if ( ! listeners.has( type ) ) {
			listeners.set( type, new Set() );
		}
		listeners.get( type ).add( listener );
		return () => off( type, listener );
	}

	function off( type, listener ) {
		const set = listeners.get( type );
		if ( set ) {
			set.delete( listener );
		}
	}

	/**
	 * Calls every listener of the type, in the order they were added. A listener that throws
	 * is reported and does not stop the others.
	 *
	 * @param {string}   type
	 * @param {*}        [detail]
	 * @param {Object}   [options]
	 * @param {Function} [options.onError] Defaults to console.error.
	 */
	function emit( type, detail, { onError = reportError } = {} ) {
		const set = listeners.get( type );
		if ( ! set ) {
			return;
		}
		Array.from( set ).forEach( ( listener ) => {
			try {
				listener( detail );
			} catch ( error ) {
				onError( error );
			}
		} );
	}

	return { on, off, emit };
}
