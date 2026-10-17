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
 * A clip is skipped only while it is the one loading: a released clip stopped downloading, so
 * switching back to its language must request it again. An image is requested once.
 *
 * Everything is passed in, so it is tested with the fake DOM; onboarding-addon-preload.js wires it.
 */

/**
 * The onboarding event names this module listens to. They must match what the screen engine
 * (onboarding-engine.js, ENGR-7566) and the boot module (onboarding-boot.js, ENGR-7565) emit.
 */
export const ONBOARDING_EVENTS = Object.freeze( {
	SCREEN: 'screen',
	LOCALE: 'locale',
} );

/**
 * @param {Object}   deps
 * @param {Object}   deps.api      The onboarding API: events, clipUrl( stage ), imageUrl( stage ).
 * @param {Document} deps.document
 * @return {{ preload: Function, requested: Function, currentClip: Function }}
 *   preload( stage ) requests a stage's clip (active language) and hero image; requested()
 *   returns every URL requested so far, in order; currentClip() returns the <video> still
 *   loading, or null.
 */
export function createPreloader( { api, document } ) {
	const requested = [];
	const requestedImages = new Set();
	let clip = null;
	let nextStage = null;

	function releaseClip() {
		if ( ! clip ) {
			return;
		}
		// Dropping src and calling load() is how a media element is told to stop fetching.
		clip.removeAttribute( 'src' );
		if ( 'function' === typeof clip.load ) {
			clip.load();
		}
	}

	function preloadClip( url ) {
		if ( ! url ) {
			return;
		}
		const isAlreadyLoading = null !== clip && clip.getAttribute( 'src' ) === url;
		if ( isAlreadyLoading ) {
			return;
		}
		requested.push( url );
		releaseClip();
		clip = document.createElement( 'video' );
		clip.setAttribute( 'preload', 'auto' );
		clip.setAttribute( 'muted', '' );
		clip.muted = true;
		clip.setAttribute( 'playsinline', '' );
		clip.setAttribute( 'src', url );
	}

	function preloadImage( url ) {
		if ( ! url || requestedImages.has( url ) ) {
			return;
		}
		requestedImages.add( url );
		requested.push( url );
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

	api.events.on( ONBOARDING_EVENTS.SCREEN, ( detail ) => {
		nextStage = detail && detail.next ? detail.next : null;
		preload( nextStage );
	} );
	api.events.on( ONBOARDING_EVENTS.LOCALE, () => preload( nextStage ) );

	return {
		preload,
		requested: () => Array.from( requested ),
		currentClip: () => clip,
	};
}
