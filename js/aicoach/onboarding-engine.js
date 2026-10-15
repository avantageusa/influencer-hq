/**
 * The portal onboarding screen engine (ENGR-7566, PO-3063 T4).
 *
 * Screens are data: each screen module registers a definition with api.screens.register(),
 * and the engine shows them in the order of onboarding-sequence.js. A definition is
 *
 *     {
 *       id: 'baccarat_experience',      // a stage id from SEQUENCE
 *       narration: 'baccaratNarration', // optional string-table key for Sami's line
 *       hideContinue: false,            // true when the screen has its own buttons
 *       render( ctx ) { ... return controller; },
 *     }
 *
 * render() gets { container, api, t, text } and draws the screen into container. It may
 * return a controller: isValid() (a question screen does not advance until it is true),
 * showValidation() (called when CONTINUE is pressed while it is not), choice() (for a
 * branch, e.g. the post action screen) and destroy().
 *
 * Advancing saves the new stage to the account store, so a returning Influencer resumes
 * there. Progress saves are sent one at a time, in order, without holding up the flow.
 * Browser back is held by the history guard. Everything the engine uses is passed in, so
 * it is tested with the fake DOM.
 */

/** The engine's own text. */
export const ENGINE_STRINGS_EN = {
	continue: 'CONTINUE',
};

/**
 * @param {Object}   deps
 * @param {Object}   deps.api          The onboarding API (onboarding-boot.js createApi()), with shell set.
 * @param {Object}   deps.sequence     { SEQUENCE, startStage, nextStage } from onboarding-sequence.js.
 * @param {Document} deps.document
 * @param {Function} deps.postJson     From onboarding-request.js.
 * @param {Function} deps.fetchFn      Same contract as window.fetch.
 * @param {Object}   deps.historyGuard From onboarding-history.js.
 * @param {Function} [deps.onError]    Told about a failed save. Defaults to console.error.
 * @return {Object} { register, start, advance, current, save, complete }.
 */
export function createEngine( deps ) {
	const { api, sequence, document, postJson, fetchFn, historyGuard } = deps;
	const onError = deps.onError || reportError;
	const definitions = new Map();
	let stage = null;
	let controller = {};
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

	/**
	 * @param {Object} definition See the file comment.
	 */
	function register( definition ) {
		if ( ! definition || ! sequence.SEQUENCE.includes( definition.id ) || 'function' !== typeof definition.render ) {
			throw new Error( 'Onboarding: invalid screen definition ' + ( definition && definition.id ) );
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

	function narrate() {
		const definition = definitions.get( stage );
		const script = definition && definition.narration ? t( definition.narration ) : '';
		return api.shell.say( { script, clipUrl: api.clipUrl( stage ) } );
	}

	function show( id ) {
		const definition = definitions.get( id );
		if ( controller.destroy ) {
			controller.destroy();
		}
		stage = id;
		const container = document.createElement( 'section' );
		container.className = 'ihq-onb-screen';
		container.setAttribute( 'data-stage', id );
		api.shell.content.replaceChildren( container );
		controller = definition.render( { container, api, t, text } ) || {};
		if ( ! definition.hideContinue ) {
			const button = text( 'button', 'continue', 'ihq-onb__button ihq-onb__button--continue' );
			button.setAttribute( 'type', 'button' );
			button.setAttribute( 'data-onb', 'continue' );
			button.addEventListener( 'click', () => advance() );
			container.append( button );
		}
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
	 * Moves exactly one screen forward (or along a branch) when the screen allows it.
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
