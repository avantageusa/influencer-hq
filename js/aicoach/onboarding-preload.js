/**
 * Preloads the next onboarding screen's clip and hero image while the current one plays
 * (ENGR-7576, PO-3063 T14, PO-3223), so the next screen starts without waiting on the network.
 *
 * The screen engine (ENGR-7566) announces each screen with a 'screen' event carrying the next
 * stage. On it, the next clip in the active language and the next hero image are requested. On a
 * language change ('locale' event) the next screen's clip is requested again in the new language.
 *
 * A clip is preloaded with a detached, muted <video preload="auto">: the browser fetches it the
 * way the real player will (range requests), so the cache serves the player. Only the latest
 * clip is kept loading; the previous one is released, so preloads never pile up on a phone.
 * Each URL is requested once.
 *
 * Everything is passed in, so it is tested with the fake DOM; onboarding-addon-preload.js wires it.
 */

/**
 * @param {Object}   deps
 * @param {Object}   deps.api      The onboarding API: events, clipUrl( stage ), imageUrl( stage ).
 * @param {Document} deps.document
 * @return {Object} { preload, requested } — preload( stage ) for the current language.
 */
export function createPreloader( { api, document } ) {
	const requested = new Set();
	let clip = null;
	let nextStage = null;

	function preloadClip( url ) {
		if ( ! url || requested.has( url ) ) {
			return;
		}
		requested.add( url );
		if ( clip ) {
			// Dropping src and calling load() is how a media element is told to stop fetching.
			clip.removeAttribute( 'src' );
			if ( 'function' === typeof clip.load ) {
				clip.load();
			}
		}
		clip = document.createElement( 'video' );
		clip.setAttribute( 'preload', 'auto' );
		clip.setAttribute( 'muted', '' );
		clip.muted = true;
		clip.setAttribute( 'playsinline', '' );
		clip.setAttribute( 'src', url );
	}

	function preloadImage( url ) {
		if ( ! url || requested.has( url ) ) {
			return;
		}
		requested.add( url );
		const image = document.createElement( 'img' );
		image.setAttribute( 'src', url );
	}

	/**
	 * Requests a screen's clip (active language) and hero image.
	 *
	 * @param {string|null} stage
	 */
	function preload( stage ) {
		if ( ! stage ) {
			return;
		}
		preloadClip( api.clipUrl( stage ) );
		preloadImage( api.imageUrl( stage ) );
	}

	api.events.on( 'screen', ( detail ) => {
		nextStage = detail && detail.next ? detail.next : null;
		preload( nextStage );
	} );
	api.events.on( 'locale', () => preload( nextStage ) );

	return {
		preload,
		requested: () => Array.from( requested ),
		currentClip: () => clip,
	};
}
