/**
 * The banner composed by Gary's adapter (ENGR-7848).
 *
 * js/vendor/sami-banner/sami-banner-compositor.js draws a poster on a canvas and composes the
 * avatar's video over it. This module is the glue: it loads the adapter and its two assets,
 * hands it the page's shared <video> (the one the flow plays clips and the live stream on), and
 * keeps the canvas and the video layer in step with what the flow does. It never plays, pauses
 * or changes the video and never touches the flow's own state.
 *
 * Until the adapter is running the page shows exactly what it shows without it: the canvas is
 * hidden by CSS and the video layer works as before. Anything that goes wrong while loading, and
 * any frame of a size the calibration does not fit, leaves or puts the page back on the video.
 *
 * The CSS classes it sets on the avatar wrapper (see the style block in page-home-aicoach.php):
 *   is-composited       the adapter is running: the canvas is shown and the video layer is hidden.
 *   is-composite-bypass the adapter reports a frame of the wrong size: the video layer is shown
 *                       again, so a live stream that does not fit never leaves a frozen poster.
 *
 * The flow's idle state (the Ask Sami panel open, or a screen without a clip) is the wrapper's
 * `is-idle` class; while it is on, the canvas shows the adapter's poster instead of a frozen frame.
 */

/** Set on the wrapper while the adapter is running. */
export const COMPOSITED_CLASS = 'is-composited';

/** Set on the wrapper while the video layer is shown instead of the canvas. */
export const BYPASS_CLASS = 'is-composite-bypass';

/** The flow's idle class on the avatar wrapper (js/aicoach-coach-flow.js, syncAvatarIdle()). */
export const IDLE_CLASS = 'is-idle';

/** The adapter's own value of canvas.dataset.status for a frame its calibration does not fit. */
export const WRONG_DIMENSIONS_STATUS = 'wrong-video-dimensions';

/**
 * @param {string} url
 * @return {Promise<{createSamiBanner: Function}>}
 */
function importModule( url ) {
	return import( url );
}

/**
 * @param {string} url
 * @return {Promise<HTMLImageElement>} A loaded image.
 */
async function loadImage( url ) {
	const image = new Image();
	image.src = url;
	await image.decode();
	return image;
}

/**
 * @param {string} url
 * @return {Promise<Object>} The parsed JSON.
 */
async function loadJson( url ) {
	const response = await fetch( url );
	if ( ! response.ok ) {
		throw new Error( 'HTTP ' + response.status + ' for ' + url );
	}
	return response.json();
}

/**
 * @param {Object}   options
 * @param {Object}   options.config               window.AICOACH_SAMI.bannerCompositor.
 * @param {Element}  options.wrap                 The avatar wrapper (#aicoach-avatar-wrap).
 * @param {Element}  options.video                The shared <video> element.
 * @param {Element}  options.canvas               The banner canvas.
 * @param {Function} [options.loadModule]         url => Promise of the adapter's module.
 * @param {Function} [options.loadPoster]         url => Promise of a loaded image.
 * @param {Function} [options.loadCalibration]    url => Promise of the calibration object.
 * @param {Function} [options.createObserver]     callback => an object with observe() and disconnect(), like MutationObserver.
 * @param {Object}   [options.logger]             Where a failure is reported, console by default.
 * @return {{start: Function, stop: Function}}
 */
export function createBannerCompositor( {
	config,
	wrap,
	video,
	canvas,
	loadModule = importModule,
	loadPoster = loadImage,
	loadCalibration = loadJson,
	createObserver = ( callback ) => new MutationObserver( callback ),
	logger = console,
} ) {
	let banner = null;
	let wrapObserver = null;
	let canvasObserver = null;
	let wasIdle = false;
	let starting = null;

	function syncIdle() {
		const idle = wrap.classList.contains( IDLE_CLASS );
		if ( idle === wasIdle ) {
			return;
		}
		wasIdle = idle;
		if ( idle ) {
			// A paused frame is not what the visitor should see while nobody speaks: the poster is.
			banner.reset();
			return;
		}
		// Back from idle: draw whatever the video shows now (the clip that is about to play, or the
		// frame the video was paused on), as the video layer itself would have shown it.
		banner.draw();
	}

	function syncBypass() {
		wrap.classList.toggle( BYPASS_CLASS, WRONG_DIMENSIONS_STATUS === canvas.dataset.status );
	}

	function observeChanges() {
		wasIdle = wrap.classList.contains( IDLE_CLASS );
		wrapObserver = createObserver( syncIdle );
		wrapObserver.observe( wrap, { attributes: true, attributeFilter: [ 'class' ] } );
		canvasObserver = createObserver( syncBypass );
		canvasObserver.observe( canvas, { attributes: true, attributeFilter: [ 'data-status' ] } );
	}

	async function run() {
		try {
			const [ adapter, poster, calibration ] = await Promise.all( [
				loadModule( config.moduleUrl ),
				loadPoster( config.posterUrl ),
				loadCalibration( config.calibrationUrl ),
			] );
			banner = adapter.createSamiBanner( {
				canvas,
				video,
				poster,
				calibration,
				avatarId: config.avatarId,
				showDisclosure: config.showDisclosure,
				preserveVideoCorner: config.preserveVideoCorner,
			} );
		} catch ( error ) {
			logger.warn( '[aicoach] the composed banner could not start, the page keeps its video:', error );
			return false;
		}
		observeChanges();
		wrap.classList.add( COMPOSITED_CLASS );
		syncBypass();
		return true;
	}

	/**
	 * Loads the adapter and starts composing. Calling it again returns the first call's answer.
	 *
	 * @return {Promise<boolean>} True when the banner is composed, false when the page stays on the video.
	 */
	function start() {
		if ( ! config || ! canvas || ! wrap || ! video ) {
			return Promise.resolve( false );
		}
		if ( null === starting ) {
			starting = run();
		}
		return starting;
	}

	/**
	 * Stops composing and puts the page back on the video layer.
	 */
	function stop() {
		starting = null;
		if ( ! banner ) {
			return;
		}
		wrapObserver.disconnect();
		canvasObserver.disconnect();
		banner.destroy();
		banner = null;
		wrap.classList.remove( COMPOSITED_CLASS, BYPASS_CLASS );
	}

	return { start, stop };
}
