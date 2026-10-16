/**
 * The portal onboarding screen engine (ENGR-7566, PO-3063 T4).
 *
 * Screens are data: each screen module registers a definition with api.screens.register(),
 * and the engine shows them in the order of onboarding-sequence.js. A definition is
 *
 *     {
 *       id: 'baccarat_experience',                       // a stage id from SEQUENCE
 *       narration: 'baccaratNarration',                  // optional string-table key for Sami's line
 *       advanceMode: api.screens.ADVANCE_MODES.PICK,     // optional, NARRATION when left out
 *       render( ctx ) { ... return controller; },
 *     }
 *
 * There is no CONTINUE button (design decision 1). How a screen moves on is its advance mode:
 * NARRATION screens move on when Sami's line ends (the clip's end, or the caption-only
 * fallback's reading-time end); one with no script and no clip yet moves on after the reading
 * time of its own text instead. PICK and MANUAL screens never move on by themselves and call
 * ctx.advance() (after a pick, or from their own control).
 *
 * render() gets { container, api, t, text, advance } and draws the screen into container.
 * advance() moves on from this screen only while it is still the one showing, so a late timer
 * or a second call cannot skip the screen after it. It may return a controller: isValid() (the
 * screen does not advance until it is true), showValidation() (called when an advance is asked
 * for while it is not), choice() (for a branch, e.g. the post action screen) and destroy().
 *
 * Advancing saves the new stage to the account store, so a returning Influencer resumes
 * there. Progress saves are sent one at a time, in order, without holding up the flow.
 * Browser back is held by the history guard. Everything the engine uses is passed in, so
 * it is tested with the fake DOM.
 */

/** How a screen moves on to the next one. */
export const ADVANCE_MODES = Object.freeze( {
	// When Sami's line for the screen has been said (teaching screens).
	NARRATION: 'narration',
	// The screen calls advance() after the Influencer picks an answer (question screens).
	PICK: 'pick',
	// The screen calls advance() from its own control (social platforms, closing).
	MANUAL: 'manual',
} );

const KNOWN_ADVANCE_MODES = Object.freeze( Object.values( ADVANCE_MODES ) );

/**
 * The least time a NARRATION screen with no script or clip stays up when it has any text, so a
 * screen of a few words does not flash past. One with no text at all moves on at once.
 */
export const MIN_READING_TIME_MS = 2000;

/**
 * The words a screen shows, for its reading time: the text of its own section (not the
 * shell's commentary or footer), with runs of white space collapsed.
 *
 * @param {Element} container The screen's section.
 * @return {string} The text, trimmed.
 */
export function screenText( container ) {
	return container.textContent.replace( /\s+/g, ' ' ).trim();
}

/**
 * @param {Object}   deps
 * @param {Object}   deps.api          The onboarding API (onboarding-boot.js createApi()), with shell set.
 * @param {Object}   deps.sequence     { SEQUENCE, startStage, nextStage } from onboarding-sequence.js.
 * @param {Document} deps.document
 * @param {Function} deps.postJson     From onboarding-request.js.
 * @param {Function} deps.fetchFn      Same contract as window.fetch.
 * @param {Object}   deps.historyGuard From onboarding-history.js.
 * @param {Function} deps.estimateDurationMs Reading-pace length of a text (commentary.js), as the narrator uses.
 * @param {Function} deps.setTimeout
 * @param {Function} deps.clearTimeout
 * @param {Function} [deps.onError]    Told about a failed save. Defaults to console.error.
 * @return {Object} { register, start, advance, current, save, complete }.
 */
export function createEngine( deps ) {
	const { api, sequence, document, postJson, fetchFn, historyGuard, estimateDurationMs, setTimeout, clearTimeout } = deps;
	const onError = deps.onError || reportError;
	const definitions = new Map();
	let stage = null;
	let controller = {};
	// Counts screen showings, so a screen's advance() only works while it is still showing.
	let visit = 0;
	// What say() returned for the line playing now. A line that is no longer this one was
	// stopped by another screen or a new language and must not move the flow. (One stopped by
	// complete() closing the layer finds no screen to move on from.)
	let currentLine = null;
	// The section the current screen draws into, and the timer standing in for Sami's line
	// on a screen that has no script or clip yet.
	let screenElement = null;
	let readingTimer = null;
	// Every progress save (stage and answers) runs after the one before has settled. The
	// server's merge reads, changes and writes the whole record without a lock, so two
	// overlapping saves could lose one of the writes, and an older answer arriving last
	// would leave the earlier state in api.state.
	let saveQueue = Promise.resolve();

	function t( key ) {
		return api.strings.t( api.getLocale(), key );
	}

	/**
	 * An element with translatable text: the text now, and data-onb-string so a language
	 * change re-labels it (shell.applyStrings()).
	 *
	 * @param {string} tag         Element name.
	 * @param {string} key         String-table key.
	 * @param {string} [className] Classes to set.
	 * @return {Element} The element.
	 */
	function text( tag, key, className ) {
		const element = document.createElement( tag );
		element.setAttribute( 'data-onb-string', key );
		element.textContent = t( key );
		if ( className ) {
			element.className = className;
		}
		return element;
	}

	function isAvailable( id ) {
		return definitions.has( id );
	}

	function advanceModeOf( definition ) {
		if ( undefined === definition.advanceMode ) {
			return ADVANCE_MODES.NARRATION;
		}
		return definition.advanceMode;
	}

	/**
	 * @param {Object} definition See the file comment.
	 */
	function register( definition ) {
		if ( ! definition || ! sequence.SEQUENCE.includes( definition.id ) || 'function' !== typeof definition.render ) {
			throw new Error( 'Onboarding: invalid screen definition ' + ( definition && definition.id ) );
		}
		if ( ! KNOWN_ADVANCE_MODES.includes( advanceModeOf( definition ) ) ) {
			throw new Error( 'Onboarding: unknown advance mode ' + definition.advanceMode + ' for ' + definition.id );
		}
		definitions.set( definition.id, definition );
	}

	/**
	 * Saves a partial update to the account store, after any save still in flight. Never
	 * blocks the flow: a failure is reported and the Influencer carries on.
	 *
	 * @param {Object} partial For example { stage } or { answers: { … } }.
	 * @return {Promise<boolean>} Whether the store accepted this save.
	 */
	function save( partial ) {
		const run = saveQueue.then( () => sendProgress( partial ) );
		// A failed save must not stop the ones queued after it; the caller still gets its own result.
		saveQueue = run.catch( () => undefined );
		return run;
	}

	/**
	 * @param {Object} partial
	 * @return {Promise<boolean>} Whether the store accepted it.
	 */
	async function sendProgress( partial ) {
		const result = await postJson( { url: api.config.progressUrl, nonce: api.config.nonce, body: partial, fetchFn } );
		if ( ! result.ok ) {
			onError( new Error( 'Onboarding: saving progress failed (' + result.status + ')' ) );
			return false;
		}
		if ( result.data && 'object' === typeof result.data ) {
			api.state = result.data;
		}
		return true;
	}

	/**
	 * Says the current screen's line. A NARRATION screen moves on when the line has been said,
	 * unless it was stopped first. The narrator settles say() either way, so a stopped line is
	 * told apart by no longer being the latest one.
	 *
	 * A screen with no script and no clip would get a 0 ms line, so it waits for the reading
	 * time of its own text instead; a real script or clip takes over once there is one.
	 */
	function narrate() {
		stopReadingTimer();
		const definition = definitions.get( stage );
		const script = definition.narration ? t( definition.narration ) : '';
		const clipUrl = api.clipUrl( stage );
		const line = api.shell.say( { script, clipUrl } );
		currentLine = line;
		if ( ADVANCE_MODES.NARRATION !== advanceModeOf( definition ) ) {
			return;
		}
		const shownVisit = visit;
		if ( '' === script && ! clipUrl ) {
			readingTimer = setTimeout( () => advanceFrom( shownVisit ), readingTimeMs( screenText( screenElement ) ) );
			return;
		}
		Promise.resolve( line ).then( () => {
			if ( currentLine !== line ) {
				return;
			}
			advanceFrom( shownVisit );
		} );
	}

	/**
	 * @param {string} words What the screen shows.
	 * @return {number} Milliseconds: 0 for no text, else the reading pace, at least the floor.
	 */
	function readingTimeMs( words ) {
		if ( '' === words ) {
			return 0;
		}
		return Math.max( MIN_READING_TIME_MS, estimateDurationMs( words ) );
	}

	function stopReadingTimer() {
		clearTimeout( readingTimer );
		readingTimer = null;
	}

	/**
	 * @param {number} fromVisit The showing the request belongs to.
	 * @return {boolean} Whether the flow moved.
	 */
	function advanceFrom( fromVisit ) {
		if ( fromVisit !== visit ) {
			return false;
		}
		return advance();
	}

	function show( id ) {
		const definition = definitions.get( id );
		if ( controller.destroy ) {
			controller.destroy();
		}
		stage = id;
		visit += 1;
		const shownVisit = visit;
		const container = document.createElement( 'section' );
		container.className = 'ihq-onb-screen';
		container.setAttribute( 'data-stage', id );
		api.shell.content.replaceChildren( container );
		screenElement = container;
		const screenAdvance = () => advanceFrom( shownVisit );
		controller = definition.render( { container, api, t, text, advance: screenAdvance } ) || {};
		narrate();
		api.events.emit( 'screen', { stage: id, next: nextFrom( id ) } );
	}

	function nextFrom( id, choice ) {
		return sequence.nextStage( { current: id, choice, isAvailable } );
	}

	/**
	 * Opens the saved stage, or screen 1, and starts guarding browser back.
	 *
	 * @return {string|null} The stage shown, or null when no screen is installed.
	 */
	function start() {
		const first = sequence.startStage( { saved: api.state.stage, isAvailable } );
		if ( null === first ) {
			return null;
		}
		historyGuard.install();
		show( first );
		return first;
	}

	/**
	 * Moves exactly one screen forward (or along a branch) when the screen allows it. Every
	 * way of moving on comes through here. It acts on whichever screen is showing; a screen
	 * calls the advance() in its render context instead, which is tied to that screen.
	 *
	 * @return {boolean} Whether the flow moved.
	 */
	function advance() {
		if ( null === stage ) {
			return false;
		}
		if ( controller.isValid && ! controller.isValid() ) {
			if ( controller.showValidation ) {
				controller.showValidation();
			}
			return false;
		}
		const choice = controller.choice ? controller.choice() : undefined;
		const next = nextFrom( stage, choice );
		if ( null === next ) {
			return false;
		}
		save( { stage: next } );
		show( next );
		return true;
	}

	/**
	 * Marks onboarding complete and removes the layer, leaving the Coach page at its top
	 * (CONTINUE TO IHQ PORTAL on the closing screen).
	 *
	 * @return {Promise<boolean>} False when the server did not record it; the layer stays.
	 */
	async function complete() {
		const result = await postJson( { url: api.config.completeUrl, nonce: api.config.nonce, fetchFn } );
		if ( ! result.ok ) {
			onError( new Error( 'Onboarding: marking complete failed (' + result.status + ')' ) );
			return false;
		}
		if ( controller.destroy ) {
			controller.destroy();
		}
		controller = {};
		stage = null;
		stopReadingTimer();
		historyGuard.uninstall();
		api.shell.close();
		return true;
	}

	// A language change restarts the current screen's line in the new language.
	api.events.on( 'locale', () => {
		if ( null !== stage ) {
			narrate();
		}
	} );

	return { register, start, advance, current: () => stage, save, complete };
}

function reportError( error ) {
	// eslint-disable-next-line no-console -- a failed save must be visible, not swallowed.
	console.error( error );
}
