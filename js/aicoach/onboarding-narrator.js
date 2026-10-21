/**
 * Plays what Sami says on one onboarding screen and drives the subtitles (ENGR-7565): her line
 * a phrase at a time, with the phrase before it (ENGR-7891).
 *
 * With a clip, the subtitles follow the clip's own clock and clear when it ends. Without
 * one (no clip rendered yet for that screen and language, or a clip that fails, or stalls
 * before it plays) the subtitles still play, at reading pace: the caption-only fallback. At
 * reading pace every phrase stays at least MIN_PHRASE_MS, so a short one can still be read;
 * with a clip the clip's clock wins, so the words stay in step with Sami.
 *
 * Mute only silences the video. The subtitles carry on either way, and unmuting brings the
 * sound back on the clip that is playing.
 *
 * Everything it uses is passed in (the <video>, timers, the phrase timing from
 * onboarding-subtitles.js, the reading-pace estimate and the stall watchdog from the landing
 * flow's modules), so the whole thing is tested with fakes. Modules here never import each
 * other: the page's import map versions each file, and a relative import would bypass it.
 */

/** How often the caption-only fallback moves its subtitles on. */
export const CAPTION_TICK_MS = 100;

/** A clip that shows no progress for this long is abandoned for captions. */
export const CLIP_STALL_MS = 8000;

/**
 * The least time a phrase stays up at reading pace. Subtitle guidelines put the floor for a
 * short subtitle at about a second; below it a phrase flashes past.
 */
export const MIN_PHRASE_MS = 1000;

/** The caption once Sami has finished or been stopped: the shape captionAt() returns. */
const NO_CAPTION = Object.freeze( { current: '', previous: '' } );

/**
 * Which phrase had been reached at a moment of a clip: the last one that had started,
 * or 0 before the first (or when the clip had no cues yet).
 *
 * @param {Array<Object>} cues   From buildPhraseCues().
 * @param {number}        timeMs Time into the clip.
 * @return {number} The phrase index.
 */
export function phraseIndexAt( cues, timeMs ) {
	const started = cues.filter( ( cue ) => timeMs >= cue.startMs ).length;
	return Math.max( 0, started - 1 );
}

/**
 * How far into the caption timeline a phrase starts.
 *
 * @param {Array<Object>} cues   From buildPhraseCues().
 * @param {number}        phrase Index of the phrase.
 * @return {number} Milliseconds, 0 when there is no such phrase.
 */
export function captionOffsetMs( cues, phrase ) {
	const cue = cues[ phrase ];
	if ( ! cue ) {
		return 0;
	}
	return cue.startMs;
}

/**
 * @param {Object}   deps
 * @param {Object}   deps.video               An HTMLVideoElement (or a fake with the same members).
 * @param {Function} deps.onSubtitle          Receives the caption to show, { current, previous }
 *                                            (both '' to clear), on every tick of the clock.
 * @param {Function} deps.buildPhraseCues     From onboarding-subtitles.js.
 * @param {Function} deps.captionAt           From onboarding-subtitles.js.
 * @param {Function} deps.estimateDurationMs  Reading-pace length of a script (commentary.js).
 * @param {Function} deps.createStallWatchdog The landing flow's watchdog (stall-watchdog.js).
 * @param {Function} deps.setTimeout
 * @param {Function} deps.clearTimeout
 * @param {Function} deps.now                 Milliseconds, monotonic.
 * @param {Function} [deps.onMutedChange]     Told when autoplay rules force the clip muted.
 * @return {Object} { play, stop, setMuted, isMuted }.
 */
export function createNarrator( deps ) {
	const { video, onSubtitle, buildPhraseCues, captionAt, setTimeout, clearTimeout } = deps;
	const onMutedChange = deps.onMutedChange || function() {};
	let muted = false;
	let current = null;

	function setMuted( value ) {
		muted = Boolean( value );
		video.muted = muted;
	}

	function stop() {
		if ( current ) {
			current.finish();
		}
	}

	/**
	 * @param {Object} line
	 * @param {string} line.script    What Sami says.
	 * @param {string} [line.clipUrl] Her clip of it, when one has been rendered.
	 * @return {Promise<Object>} Settles with { mode: 'clip' | 'captions' } when the line has been said or stopped.
	 */
	function play( { script, clipUrl } ) {
		stop();
		return new Promise( ( resolve ) => {
			const run = createRun( resolve );
			current = run;
			if ( clipUrl ) {
				playClip( run, script, clipUrl );
				return;
			}
			playCaptions( run, script );
		} );
	}

	function createRun( resolve ) {
		const cleanups = [];
		const run = {
			mode: 'captions',
			done: false,
			onCleanup( fn ) {
				cleanups.push( fn );
			},
			finish() {
				if ( run.done ) {
					return;
				}
				run.done = true;
				cleanups.splice( 0 ).forEach( ( fn ) => fn() );
				onSubtitle( NO_CAPTION );
				if ( current === run ) {
					current = null;
				}
				resolve( { mode: run.mode } );
			},
		};
		return run;
	}

	/**
	 * @param {Object} run
	 * @param {string} script
	 * @param {number} [fromPhrase] Index of the phrase to start at (a clip that broke off
	 *                              mid-line carries on from where Sami got to).
	 */
	function playCaptions( run, script, fromPhrase = 0 ) {
		run.mode = 'captions';
		const cues = buildPhraseCues( script, deps.estimateDurationMs( script ), { minPhraseMs: MIN_PHRASE_MS } );
		// The minimum can make the line longer than the estimate: it ends with its last phrase.
		const lineMs = cues.length > 0 ? cues[ cues.length - 1 ].endMs : 0;
		const startedAt = deps.now() - captionOffsetMs( cues, fromPhrase );
		let timerId = null;
		run.onCleanup( () => clearTimeout( timerId ) );

		function tick() {
			const elapsed = deps.now() - startedAt;
			if ( elapsed >= lineMs ) {
				run.finish();
				return;
			}
			onSubtitle( captionAt( cues, elapsed ) );
			timerId = setTimeout( tick, CAPTION_TICK_MS );
		}
		tick();
	}

	function playClip( run, script, clipUrl ) {
		run.mode = 'clip';
		let cues = [];
		let fellBack = false;

		const watchdog = deps.createStallWatchdog( {
			stallMs: CLIP_STALL_MS,
			onStall: fallBack,
			setTimeout,
			clearTimeout,
		} );

		function fallBack() {
			if ( fellBack || run.done ) {
				return;
			}
			fellBack = true;
			const fromPhrase = phraseIndexAt( cues, video.currentTime * 1000 );
			detach();
			video.pause();
			playCaptions( run, script, fromPhrase );
		}

		function onMetadata() {
			cues = buildPhraseCues( script, clipDurationMs( script ) );
		}
		function onTime() {
			watchdog.arm();
			onSubtitle( captionAt( cues, video.currentTime * 1000 ) );
		}
		function onEnded() {
			run.finish();
		}

		const listeners = { loadedmetadata: onMetadata, timeupdate: onTime, ended: onEnded, error: fallBack };
		Object.keys( listeners ).forEach( ( type ) => video.addEventListener( type, listeners[ type ] ) );
		function detach() {
			watchdog.disarm();
			Object.keys( listeners ).forEach( ( type ) => video.removeEventListener( type, listeners[ type ] ) );
		}
		run.onCleanup( () => {
			detach();
			if ( ! fellBack ) {
				video.pause();
			}
		} );

		video.muted = muted;
		video.src = clipUrl;
		watchdog.arm();
		startPlayback( run, fallBack );
	}

	// A streamed clip can report its duration as Infinity, and any clip as NaN before its
	// metadata is in. Then the phrases are spread over the reading-pace length instead, so
	// the subtitles still show.
	function clipDurationMs( script ) {
		if ( Number.isFinite( video.duration ) ) {
			return video.duration * 1000;
		}
		return deps.estimateDurationMs( script );
	}

	// Browsers refuse to start a clip with sound before the user has interacted with the
	// page. Then the clip plays muted (subtitles still show) and the sound button says so.
	function startPlayback( run, fallBack ) {
		Promise.resolve( video.play() ).catch( ( error ) => {
			if ( run.done ) {
				return;
			}
			if ( error && 'NotAllowedError' === error.name && ! muted ) {
				setMuted( true );
				onMutedChange( true );
				Promise.resolve( video.play() ).catch( fallBack );
				return;
			}
			fallBack();
		} );
	}

	return {
		play,
		stop,
		setMuted,
		isMuted: () => muted,
	};
}
