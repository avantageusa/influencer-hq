/**
 * Plays what Sami says on one onboarding screen and drives the subtitles (ENGR-7565).
 *
 * With a clip, the subtitles follow the clip's own clock and clear when it ends. Without
 * one (no clip rendered yet for that screen and language, or a clip that fails, or stalls
 * before it plays) the subtitles still play, at reading pace: the caption-only fallback.
 *
 * Mute only silences the video. The subtitles carry on either way, and unmuting brings the
 * sound back on the clip that is playing.
 *
 * Everything it uses is passed in (the <video>, timers, the sentence timing from
 * onboarding-subtitles.js, the reading-pace estimate and the stall watchdog from the landing
 * flow's modules), so the whole thing is tested with fakes. Modules here never import each
 * other: the page's import map versions each file, and a relative import would bypass it.
 */

/** How often the caption-only fallback moves its subtitles on. */
export const CAPTION_TICK_MS = 100;

/** A clip that shows no progress for this long is abandoned for captions. */
export const CLIP_STALL_MS = 8000;

/**
 * Which sentence had been reached at a moment of a clip: the last one that had started,
 * or 0 before the first (or when the clip had no cues yet).
 *
 * @param {Array<Object>} cues   From buildSentenceCues().
 * @param {number}        timeMs Time into the clip.
 * @return {number} The sentence index.
 */
export function sentenceIndexAt( cues, timeMs ) {
	const started = cues.filter( ( cue ) => timeMs >= cue.startMs ).length;
	return Math.max( 0, started - 1 );
}

/**
 * How far into the caption timeline a sentence starts.
 *
 * @param {Array<Object>} cues     From buildSentenceCues().
 * @param {number}        sentence Index of the sentence.
 * @return {number} Milliseconds, 0 when there is no such sentence.
 */
export function captionOffsetMs( cues, sentence ) {
	const cue = cues[ sentence ];
	if ( ! cue ) {
		return 0;
	}
	return cue.startMs;
}

/**
 * @param {Object}   deps
 * @param {Object}   deps.video               An HTMLVideoElement (or a fake with the same members).
 * @param {Function} deps.onSubtitle          Receives the sentence to show ('' to clear).
 * @param {Function} deps.buildSentenceCues   From onboarding-subtitles.js.
 * @param {Function} deps.subtitleAt          From onboarding-subtitles.js.
 * @param {Function} deps.estimateDurationMs  Reading-pace length of a script (commentary.js).
 * @param {Function} deps.createStallWatchdog The landing flow's watchdog (stall-watchdog.js).
 * @param {Function} deps.setTimeout
 * @param {Function} deps.clearTimeout
 * @param {Function} deps.now                 Milliseconds, monotonic.
 * @param {Function} [deps.onMutedChange]     Told when autoplay rules force the clip muted.
 * @return {Object} { play, stop, setMuted, isMuted }.
 */
export function createNarrator( deps ) {
	const { video, onSubtitle, buildSentenceCues, subtitleAt, setTimeout, clearTimeout } = deps;
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
				onSubtitle( '' );
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
	 * @param {number} [fromSentence] Index of the sentence to start at (a clip that broke
	 *                                off mid-line carries on from where Sami got to).
	 */
	function playCaptions( run, script, fromSentence = 0 ) {
		run.mode = 'captions';
		const durationMs = deps.estimateDurationMs( script );
		const cues = buildSentenceCues( script, durationMs );
		const startedAt = deps.now() - captionOffsetMs( cues, fromSentence );
		let timerId = null;
		run.onCleanup( () => clearTimeout( timerId ) );

		function tick() {
			const elapsed = deps.now() - startedAt;
			if ( elapsed >= durationMs ) {
				run.finish();
				return;
			}
			onSubtitle( subtitleAt( cues, elapsed ) );
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
			const fromSentence = sentenceIndexAt( cues, video.currentTime * 1000 );
			detach();
			video.pause();
			playCaptions( run, script, fromSentence );
		}

		function onMetadata() {
			cues = buildSentenceCues( script, clipDurationMs( script ) );
		}
		function onTime() {
			watchdog.arm();
			onSubtitle( subtitleAt( cues, video.currentTime * 1000 ) );
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
	// metadata is in. Then the sentences are spread over the reading-pace length instead, so
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
