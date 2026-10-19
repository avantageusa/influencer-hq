/**
 * Onboarding screen 14, the draft posts (ENGR-7570, PO-3063 T8, PO-3212).
 *
 * "I'll help you draft messages to your followers": one panel per platform saved on screen 6,
 * each with the platform's name, Sami's draft, Copy (with a confirmation) and Share (the
 * device's share sheet, where the browser has one). The draft is text, not a field: to change
 * it the Influencer asks Sami, typed or spoken, from that panel, and Gary's new draft replaces
 * that panel's only. Sami's reply on this screen is caption-only (no clip).
 *
 * Drafts come from the server (inc/portal-onboarding-drafts.php), which asks Gary's posting
 * routes and stores them on the account. Stored drafts are shown as they are, so a returning
 * Influencer, or one sent back here by screen 15's "Let's edit this together now", gets them
 * without a new request; missing ones are asked for when the screen opens, at most
 * DRAFTS_PER_REQUEST platforms a request (Gary's limit). Every request costs Gary budget, so
 * they go one at a time and a language change does not redraft. A failure or a timeout shows a
 * message in the panel concerned, with Try again for a missing draft, and leaves every other
 * draft usable.
 *
 * Spoken requests are recorded with MediaRecorder and sent to Gary as audio, which he
 * transcribes in any of the seven languages (the landing flow's SpeechRecognition is
 * English-only); the microphone is asked for only when the mic is tapped.
 *
 * The screen is MANUAL: it never moves on by itself, and its own CONTINUE (always available,
 * so an outage cannot hold the Influencer here) moves on to screen 15.
 *
 * A plug-in module: the entry installs it, and it registers its screen with the engine.
 * Platform names are the social screen's string-table keys (platform_<key>).
 */

/** English copy (PO-3212; the design's "to you followers" read as "to your followers"). */
export const POSTS_STRINGS_EN = {
	postsHeading: "I'll help you draft messages to your followers",
	postsNoPlatforms: "You didn't choose any social media sites, so there are no posts to draft. You can add them on your Profile page later.",
	postsWriting: 'Sami is writing your post…',
	postsRewriting: 'Sami is rewriting your post…',
	postsCopy: 'Copy',
	postsCopied: 'Copied',
	postsCopyFailed: "Couldn't copy. Select the text and copy it instead.",
	postsShare: 'Share',
	postsShareFailed: "Couldn't open sharing on this device. Use Copy instead.",
	postsChange: 'Ask Sami to change this',
	postsChangeLabel: 'What should Sami change?',
	postsChangePlaceholder: 'For example: make it shorter and warmer',
	postsSend: 'Send',
	postsSpeak: 'Tell Sami what to change',
	postsStopSpeaking: 'Stop and send',
	postsListening: 'Listening… Tap the microphone again when you are done.',
	postsInstructionRequired: 'Type or say what Sami should change.',
	postsNothingHeard: "Sami didn't hear anything. Please try again.",
	postsMicBlocked: "Sami can't hear you without the microphone. You can type instead.",
	postsMicFailed: "The microphone didn't start. You can type instead.",
	postsHeard: 'You said:',
	postsRetry: 'Try again',
	postsRevised: "Here's the new version.",
	postsContinue: 'CONTINUE',
	postsErrorRefused: "Sami couldn't write that. Please try again, or ask for something different.",
	postsErrorLimit: 'Sami has written all the posts she can for today. Please try again tomorrow.',
	postsErrorUnavailable: "Sami can't write posts right now. Please try again in a few minutes.",
	postsErrorFailed: 'Something went wrong writing this post. Please try again.',
	postsErrorTimeout: "Sami didn't answer in time. Please check your connection and try again.",
	postsErrorInvalid: "Sami couldn't use that request. Please try again.",
};

/** Gary drafts 1 to 8 platforms per request (IHQ_PORTAL_ONBOARDING_DRAFTS_PER_REQUEST). */
export const DRAFTS_PER_REQUEST = 8;

/** The server waits 40 s for Gary (IHQ_PORTAL_ONBOARDING_DRAFTS_TIMEOUT); the browser a little longer. */
export const REQUEST_TIMEOUT_MS = 45000;

/** How long "Copied" stays up. */
export const COPY_CONFIRMATION_MS = 2000;

/** The longest typed request the server takes, in characters (IHQ_PORTAL_ONBOARDING_INSTRUCTION_MAX_LENGTH). */
export const INSTRUCTION_MAX_LENGTH = 500;

/** A recording stops and is sent after this long; well under the server's 1 MB at speech bitrates. */
export const MAX_RECORDING_MS = 60000;

/** Recording types in order of preference; the server accepts these (IHQ_PORTAL_ONBOARDING_AUDIO_TYPES). */
export const RECORDING_TYPES = Object.freeze( [ 'audio/webm', 'audio/ogg', 'audio/mp4' ] );

/** The message for each error the server sends, plus 'timeout' for no answer at all. */
export const ERROR_KEYS = Object.freeze( {
	refused: 'postsErrorRefused',
	limit: 'postsErrorLimit',
	unavailable: 'postsErrorUnavailable',
	failed: 'postsErrorFailed',
	invalid: 'postsErrorInvalid',
	timeout: 'postsErrorTimeout',
} );

const ENTER_KEY = 'Enter';

/** What getUserMedia rejects with when the microphone is refused. */
const PERMISSION_DENIED_ERROR = 'NotAllowedError';

/** What navigator.share rejects with when the Influencer closes the share sheet. */
const SHARE_CANCELLED_ERROR = 'AbortError';

/** btoa() takes a string; building it this many bytes at a time stays within argument limits. */
const BASE64_CHUNK_BYTES = 0x8000;

/**
 * The selected platforms, in the store's order (screen 6 saves them in screen order). Pure.
 *
 * @param {Object} state The store's state.
 * @return {string[]} Platform keys.
 */
export function selectedPlatforms( state ) {
	const platforms = state && Array.isArray( state.platforms ) ? state.platforms : [];
	return platforms.filter( ( key ) => 'string' === typeof key );
}

/**
 * Drafts as platform => non-empty text; anything else is left out. Pure.
 *
 * @param {*} value
 * @return {Object} The drafts.
 */
export function cleanDrafts( value ) {
	const drafts = {};
	if ( ! value || 'object' !== typeof value || Array.isArray( value ) ) {
		return drafts;
	}
	Object.keys( value ).forEach( ( key ) => {
		if ( 'string' === typeof value[ key ] && '' !== value[ key ].trim() ) {
			drafts[ key ] = value[ key ];
		}
	} );
	return drafts;
}

/**
 * A list cut into pieces of at most size items. Pure.
 *
 * @param {Array}  list
 * @param {number} size
 * @return {Array[]} The pieces, in order.
 */
export function batches( list, size ) {
	const pieces = [];
	for ( let index = 0; index < list.length; index += size ) {
		pieces.push( list.slice( index, index + size ) );
	}
	return pieces;
}

/**
 * The error of a postJson() result: '' when it worked, 'timeout' when no answer came, the
 * server's code when it is one of ERROR_KEYS, else 'unavailable'. Pure.
 *
 * @param {Object} result { ok, status, data } from postJson().
 * @return {string} An ERROR_KEYS key, or ''.
 */
export function errorOf( result ) {
	if ( result.ok ) {
		return '';
	}
	if ( 0 === result.status ) {
		return 'timeout';
	}
	const code = result.data && 'object' === typeof result.data ? result.data.error : undefined;
	return Object.prototype.hasOwnProperty.call( ERROR_KEYS, code ) ? code : 'unavailable';
}

/**
 * A fetch that rejects when no response has come within ms.
 *
 * @param {Function} fetchFn Same contract as window.fetch.
 * @param {number}   ms
 * @param {Object}   timers  { setTimeout, clearTimeout }.
 * @return {Function} Same contract as window.fetch.
 */
export function withTimeout( fetchFn, ms, timers ) {
	return ( url, options ) => new Promise( ( resolve, reject ) => {
		const timer = timers.setTimeout( () => reject( new Error( 'Onboarding: drafting timed out' ) ), ms );
		fetchFn( url, options ).then(
			( response ) => {
				timers.clearTimeout( timer );
				resolve( response );
			},
			( error ) => {
				timers.clearTimeout( timer );
				reject( error );
			}
		);
	} );
}

/**
 * @param {Uint8Array} bytes
 * @param {Function}   [btoaFn] Defaults to the global btoa.
 * @return {string} The bytes as base64.
 */
export function bytesToBase64( bytes, btoaFn = ( binary ) => btoa( binary ) ) {
	let binary = '';
	for ( let index = 0; index < bytes.length; index += BASE64_CHUNK_BYTES ) {
		binary += String.fromCharCode.apply( null, bytes.subarray( index, index + BASE64_CHUNK_BYTES ) );
	}
	return btoaFn( binary );
}

/**
 * Talks to the draft routes. Requests go one at a time, in call order. Drafts that come back
 * are kept here and in api.state, so the screen shows them when it is opened again even if a
 * progress save answered with an older state meanwhile.
 *
 * @param {Object}   deps
 * @param {Object}   deps.api       The onboarding API (config.draftsUrl, draftsReviseUrl, nonce; postJson).
 * @param {Function} deps.fetchFn   Same contract as window.fetch.
 * @param {Object}   deps.timers    { setTimeout, clearTimeout }.
 * @param {number}   [deps.timeoutMs]
 * @return {Object} { draft( platforms ), revise( platform, fields ), latest() }; both calls
 *                  resolve to { drafts, heard, error } and never reject.
 */
export function createDraftClient( { api, fetchFn, timers, timeoutMs = REQUEST_TIMEOUT_MS } ) {
	const timedFetch = withTimeout( fetchFn, timeoutMs, timers );
	let written = {};
	let queue = Promise.resolve();

	async function call( url, body ) {
		const result = await api.postJson( { url, nonce: api.config.nonce, body, fetchFn: timedFetch } );
		const error = errorOf( result );
		if ( '' !== error ) {
			return { drafts: {}, heard: '', error };
		}
		const drafts = cleanDrafts( result.data && result.data.drafts );
		written = { ...written, ...drafts };
		api.state = { ...api.state, drafts: { ...cleanDrafts( api.state.drafts ), ...drafts } };
		const heard = result.data && 'string' === typeof result.data.heard ? result.data.heard : '';
		return { drafts, heard, error: '' };
	}

	// The body is built when the request is sent, so a queued request goes in the language of
	// that moment.
	function send( url, bodyOf ) {
		const run = queue.then( () => call( url, bodyOf() ) );
		// A request that throws must not stop the ones queued after it.
		queue = run.catch( () => undefined );
		return run;
	}

	return {
		draft: ( platforms ) => send( api.config.draftsUrl, () => ( { platforms, lang: api.getLocale() } ) ),
		revise: ( platform, fields ) => send( api.config.draftsReviseUrl, () => ( { platform, lang: api.getLocale(), ...fields } ) ),
		latest: () => ( { ...written } ),
	};
}

/**
 * Records the Influencer's voice. start() asks for the microphone (the browser's prompt, the
 * first time) and resolves to a session: stop() resolves to the recording as { data, mime }
 * ({ data: '' } when nothing was recorded); cancel() drops it. The microphone is released
 * either way.
 *
 * @param {Object}   deps
 * @param {Object}   [deps.mediaDevices]      navigator.mediaDevices.
 * @param {Function} [deps.MediaRecorderCtor] window.MediaRecorder.
 * @param {Function} [deps.BlobCtor]          window.Blob.
 * @param {string[]} [deps.types]
 * @return {Object} { supported, start }.
 */
export function createVoiceRecorder( { mediaDevices, MediaRecorderCtor, BlobCtor, types = RECORDING_TYPES } ) {
	const supported = Boolean( mediaDevices && 'function' === typeof mediaDevices.getUserMedia && 'function' === typeof MediaRecorderCtor && 'function' === typeof BlobCtor );

	function preferredType() {
		if ( 'function' !== typeof MediaRecorderCtor.isTypeSupported ) {
			return '';
		}
		return types.find( ( type ) => MediaRecorderCtor.isTypeSupported( type ) ) || '';
	}

	async function start() {
		const stream = await mediaDevices.getUserMedia( { audio: true } );
		const release = () => stream.getTracks().forEach( ( track ) => track.stop() );
		const mimeType = preferredType();
		let recorder;
		try {
			recorder = new MediaRecorderCtor( stream, mimeType ? { mimeType } : {} );
		} catch ( error ) {
			release();
			throw error;
		}
		const chunks = [];
		recorder.ondataavailable = ( event ) => {
			if ( event.data && event.data.size > 0 ) {
				chunks.push( event.data );
			}
		};
		recorder.start();

		return {
			stop: () => new Promise( ( resolve ) => {
				recorder.onstop = async () => {
					release();
					const blob = new BlobCtor( chunks, { type: recorder.mimeType || mimeType } );
					const bytes = new Uint8Array( await blob.arrayBuffer() );
					resolve( { data: bytes.length > 0 ? bytesToBase64( bytes ) : '', mime: blob.type } );
				};
				recorder.stop();
			} ),
			cancel() {
				recorder.onstop = null;
				if ( 'inactive' !== recorder.state ) {
					recorder.stop();
				}
				release();
			},
		};
	}

	return { supported, start };
}

/**
 * An element whose text is a string-table key, re-labelled on a language change; or nothing.
 *
 * @param {Element}  element
 * @param {Function} t       ( key ) => text.
 * @param {string}   key     '' clears and hides it.
 */
function setMessage( element, t, key ) {
	if ( '' === key ) {
		element.removeAttribute( 'data-onb-string' );
		element.textContent = '';
		element.hidden = true;
		return;
	}
	element.setAttribute( 'data-onb-string', key );
	element.textContent = t( key );
	element.hidden = false;
}

function button( text, key, className ) {
	const element = text( 'button', key, className );
	element.setAttribute( 'type', 'button' );
	return element;
}

/**
 * The change request part of a panel: a field, the mic and Send, hidden until asked for.
 *
 * @param {Object} parts { document, text, t, key, canRecord }.
 * @return {Object} The elements.
 */
function changeForm( { document, text, t, key, canRecord } ) {
	const form = document.createElement( 'div' );
	form.className = 'ihq-onb-posts__change';
	form.setAttribute( 'id', 'ihq-onb-posts-change-' + key );
	form.hidden = true;

	const label = text( 'label', 'postsChangeLabel', 'ihq-onb-posts__change-label' );
	label.setAttribute( 'for', 'ihq-onb-posts-input-' + key );

	const input = document.createElement( 'input' );
	input.setAttribute( 'type', 'text' );
	input.setAttribute( 'id', 'ihq-onb-posts-input-' + key );
	input.className = 'ihq-onb-posts__input';
	input.setAttribute( 'maxlength', String( INSTRUCTION_MAX_LENGTH ) );
	input.setAttribute( 'autocomplete', 'off' );
	input.setAttribute( 'placeholder', t( 'postsChangePlaceholder' ) );

	const mic = document.createElement( 'button' );
	mic.setAttribute( 'type', 'button' );
	mic.className = 'ihq-onb-posts__mic';
	mic.setAttribute( 'aria-pressed', 'false' );
	mic.setAttribute( 'data-onb-label', 'postsSpeak' );
	mic.setAttribute( 'aria-label', t( 'postsSpeak' ) );
	mic.hidden = ! canRecord;

	const send = button( text, 'postsSend', 'ihq-onb-posts__send' );

	const row = document.createElement( 'div' );
	row.className = 'ihq-onb-posts__change-row';
	row.append( input, mic, send );

	const status = document.createElement( 'p' );
	status.className = 'ihq-onb-posts__status';
	status.setAttribute( 'role', 'status' );
	status.hidden = true;

	const error = document.createElement( 'p' );
	error.className = 'ihq-onb__error';
	error.setAttribute( 'role', 'alert' );
	error.hidden = true;

	const heard = document.createElement( 'p' );
	heard.className = 'ihq-onb-posts__heard';
	heard.hidden = true;
	const heardText = document.createElement( 'span' );
	heardText.className = 'ihq-onb-posts__heard-text';
	heard.append( text( 'span', 'postsHeard' ), ' ', heardText );

	form.append( label, row, status, error, heard );
	return { form, input, mic, send, status, error, heard, heardText };
}

/**
 * One platform's panel. Its show*() methods put it in each state.
 *
 * @param {Object} parts { document, text, t, key, canShare, canRecord }.
 * @return {Object} The elements and the state methods.
 */
function platformPanel( { document, text, t, key, canShare, canRecord } ) {
	const panel = document.createElement( 'article' );
	panel.className = 'ihq-onb-posts__panel';
	panel.setAttribute( 'data-platform', key );
	panel.setAttribute( 'aria-labelledby', 'ihq-onb-posts-name-' + key );

	const name = text( 'h3', 'platform_' + key, 'ihq-onb-posts__platform' );
	name.setAttribute( 'id', 'ihq-onb-posts-name-' + key );

	// Plain text, never a field: the draft is changed by asking Sami.
	const draft = document.createElement( 'p' );
	draft.className = 'ihq-onb-posts__draft';
	draft.hidden = true;

	const status = document.createElement( 'p' );
	status.className = 'ihq-onb-posts__status';
	status.setAttribute( 'role', 'status' );
	status.hidden = true;

	const error = document.createElement( 'p' );
	error.className = 'ihq-onb__error';
	error.setAttribute( 'role', 'alert' );
	error.hidden = true;

	const retry = button( text, 'postsRetry', 'ihq-onb-posts__retry' );
	retry.hidden = true;

	const actions = document.createElement( 'div' );
	actions.className = 'ihq-onb-posts__actions';
	actions.hidden = true;
	const copy = button( text, 'postsCopy', 'ihq-onb-posts__copy' );
	const share = button( text, 'postsShare', 'ihq-onb-posts__share' );
	share.hidden = ! canShare;
	const change = button( text, 'postsChange', 'ihq-onb-posts__change-toggle' );
	change.setAttribute( 'aria-expanded', 'false' );
	change.setAttribute( 'aria-controls', 'ihq-onb-posts-change-' + key );
	actions.append( copy, share, change );

	const form = changeForm( { document, text, t, key, canRecord } );
	panel.append( name, draft, status, error, retry, actions, form.form );

	function showDraft( value ) {
		draft.textContent = value;
		draft.hidden = false;
		panel.removeAttribute( 'aria-busy' );
		setMessage( status, t, '' );
		setMessage( error, t, '' );
		retry.hidden = true;
		actions.hidden = false;
	}

	function showWriting() {
		panel.setAttribute( 'aria-busy', 'true' );
		setMessage( status, t, 'postsWriting' );
		setMessage( error, t, '' );
		retry.hidden = true;
	}

	function showFailed( errorCode ) {
		panel.removeAttribute( 'aria-busy' );
		setMessage( status, t, '' );
		setMessage( error, t, ERROR_KEYS[ errorCode ] );
		retry.hidden = false;
	}

	function setFormOpen( open ) {
		form.form.hidden = ! open;
		change.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		if ( open ) {
			form.input.focus();
		}
	}

	function setBusy( busy ) {
		form.input.disabled = busy;
		form.send.disabled = busy;
		form.mic.disabled = busy;
	}

	function setRecording( recording ) {
		form.mic.setAttribute( 'aria-pressed', recording ? 'true' : 'false' );
		form.mic.classList.toggle( 'is-listening', recording );
		const label = recording ? 'postsStopSpeaking' : 'postsSpeak';
		form.mic.setAttribute( 'data-onb-label', label );
		form.mic.setAttribute( 'aria-label', t( label ) );
		form.input.disabled = recording;
		form.send.disabled = recording;
	}

	function showHeard( value ) {
		form.heardText.textContent = value ? '“' + value + '”' : '';
		form.heard.hidden = '' === value;
	}

	return {
		panel,
		draft,
		status,
		retry,
		copy,
		share,
		change,
		form,
		showDraft,
		showWriting,
		showFailed,
		setFormOpen,
		setBusy,
		setRecording,
		showHeard,
		text: () => draft.textContent,
	};
}

function continueControl( { text, advance } ) {
	const control = button( text, 'postsContinue', 'ihq-onb__button ihq-onb-posts__continue' );
	// Always available: a failed or slow draft never keeps the Influencer on this screen.
	control.addEventListener( 'click', () => advance() );
	return control;
}

/**
 * The screen's one microphone: at most one recording, in one panel. A tap while the browser is
 * still asking for the microphone is ignored, so a second tap on its prompt cannot start a
 * second recording.
 *
 * @param {Object}   deps
 * @param {Object}   deps.recorder   From createVoiceRecorder().
 * @param {Object}   deps.timers     { setTimeout, clearTimeout }.
 * @param {Function} deps.t          ( key ) => text.
 * @param {Map}      deps.panels     Platform key => platformPanel().
 * @param {Function} deps.isAlive    Whether the screen is still showing.
 * @param {Function} deps.onRecorded ( key, audio ) for a recording with something in it.
 * @return {Object} { toggle( key ), cancelIn( key ), cancel() }.
 */
function createMicControl( { recorder, timers, t, panels, isAlive, onRecorded } ) {
	let recording = null;
	let starting = false;

	function release() {
		timers.clearTimeout( recording.timer );
		const panel = panels.get( recording.key );
		panel.setRecording( false );
		setMessage( panel.form.status, t, '' );
		const { key, session } = recording;
		recording = null;
		return { key, session, panel };
	}

	async function finish() {
		const { key, session, panel } = release();
		const audio = await session.stop();
		if ( ! isAlive() ) {
			return;
		}
		if ( '' === audio.data ) {
			setMessage( panel.form.error, t, 'postsNothingHeard' );
			return;
		}
		onRecorded( key, audio );
	}

	async function start( key ) {
		const panel = panels.get( key );
		setMessage( panel.form.error, t, '' );
		starting = true;
		let session;
		try {
			session = await recorder.start();
		} catch ( error ) {
			const isBlocked = error && PERMISSION_DENIED_ERROR === error.name;
			setMessage( panel.form.error, t, isBlocked ? 'postsMicBlocked' : 'postsMicFailed' );
			return;
		} finally {
			starting = false;
		}
		if ( ! isAlive() ) {
			session.cancel();
			return;
		}
		recording = { key, session, timer: timers.setTimeout( finish, MAX_RECORDING_MS ) };
		panel.setRecording( true );
		setMessage( panel.form.status, t, 'postsListening' );
	}

	function cancel() {
		if ( recording ) {
			release().session.cancel();
		}
	}

	function toggle( key ) {
		if ( starting ) {
			return;
		}
		if ( recording && recording.key === key ) {
			finish();
			return;
		}
		// One microphone: a recording in another panel is dropped first.
		cancel();
		start( key );
	}

	function cancelIn( key ) {
		if ( recording && recording.key === key ) {
			cancel();
		}
	}

	return { toggle, cancelIn, cancel };
}

/**
 * Copy and Share for the panels, with the panel's short-lived confirmation.
 *
 * @param {Object}        deps
 * @param {Function|null} deps.clipboard ( text ) => Promise, or null where there is none.
 * @param {Function|null} deps.share     ( data ) => Promise (navigator.share), or null.
 * @param {Object}        deps.timers    { setTimeout, clearTimeout }.
 * @param {Function}      deps.t         ( key ) => text.
 * @param {Map}           deps.panels    Platform key => platformPanel().
 * @return {Object} { copy( key ), share( key ), destroy() }.
 */
function createCopyShare( { clipboard, share, timers, t, panels } ) {
	const confirmTimers = new Map();

	function confirm( key, messageKey ) {
		const panel = panels.get( key );
		timers.clearTimeout( confirmTimers.get( key ) );
		setMessage( panel.status, t, messageKey );
		confirmTimers.set( key, timers.setTimeout( () => setMessage( panel.status, t, '' ), COPY_CONFIRMATION_MS ) );
	}

	async function copy( key ) {
		if ( ! clipboard ) {
			confirm( key, 'postsCopyFailed' );
			return;
		}
		try {
			await clipboard( panels.get( key ).text() );
			confirm( key, 'postsCopied' );
		} catch ( error ) {
			confirm( key, 'postsCopyFailed' );
		}
	}

	async function shareDraft( key ) {
		try {
			await share( { text: panels.get( key ).text() } );
		} catch ( error ) {
			if ( ! error || SHARE_CANCELLED_ERROR !== error.name ) {
				confirm( key, 'postsShareFailed' );
			}
		}
	}

	return {
		copy,
		share: shareDraft,
		destroy: () => confirmTimers.forEach( ( timer ) => timers.clearTimeout( timer ) ),
	};
}

/**
 * The renderer of screen 14.
 *
 * @param {Object} deps { client, timers, clipboard, share, recorder } (see install()).
 * @return {Function} An engine render().
 */
function renderPosts( deps ) {
	const { client, timers, clipboard, share, recorder } = deps;

	return function( { container, api, t, text, advance } ) {
		const document = container.ownerDocument;
		container.append( text( 'h2', 'postsHeading', 'ihq-onb-posts__heading' ) );

		const platforms = selectedPlatforms( api.state );
		if ( 0 === platforms.length ) {
			container.append( text( 'p', 'postsNoPlatforms', 'ihq-onb-posts__empty' ) );
			container.append( continueControl( { text, advance } ) );
			return {};
		}

		const list = document.createElement( 'div' );
		list.className = 'ihq-onb-posts__list';
		container.append( list );
		const panels = new Map();
		platforms.forEach( ( key ) => {
			const panel = platformPanel( { document, text, t, key, canShare: Boolean( share ), canRecord: recorder.supported } );
			panels.set( key, panel );
			list.append( panel.panel );
		} );
		container.append( continueControl( { text, advance } ) );

		let alive = true;
		const isAlive = () => alive;

		async function requestDrafts( keys ) {
			keys.forEach( ( key ) => panels.get( key ).showWriting() );
			for ( const piece of batches( keys, DRAFTS_PER_REQUEST ) ) {
				const result = await client.draft( piece );
				if ( ! alive ) {
					return;
				}
				piece.forEach( ( key ) => {
					if ( result.drafts[ key ] ) {
						panels.get( key ).showDraft( result.drafts[ key ] );
						return;
					}
					// A reply that left this platform out is a failure for it.
					panels.get( key ).showFailed( result.error || 'failed' );
				} );
			}
		}

		async function revise( key, fields ) {
			const panel = panels.get( key );
			panel.setBusy( true );
			setMessage( panel.form.error, t, '' );
			setMessage( panel.form.status, t, 'postsRewriting' );
			const result = await client.revise( key, fields );
			if ( ! alive ) {
				return;
			}
			panel.setBusy( false );
			setMessage( panel.form.status, t, '' );
			if ( '' !== result.error || ! result.drafts[ key ] ) {
				// The draft on screen stays as it was, still copyable.
				setMessage( panel.form.error, t, ERROR_KEYS[ result.error || 'failed' ] );
				return;
			}
			panel.showDraft( result.drafts[ key ] );
			panel.showHeard( result.heard );
			panel.form.input.value = '';
			api.shell.say( { script: t( 'postsRevised' ) } );
		}

		function sendTyped( key ) {
			const panel = panels.get( key );
			const instruction = panel.form.input.value.trim();
			if ( '' === instruction ) {
				setMessage( panel.form.error, t, 'postsInstructionRequired' );
				return;
			}
			revise( key, { instruction } );
		}

		const mic = createMicControl( { recorder, timers, t, panels, isAlive, onRecorded: ( key, audio ) => revise( key, { audio } ) } );
		const copyShare = createCopyShare( { clipboard, share, timers, t, panels } );

		function toggleForm( key ) {
			const panel = panels.get( key );
			const open = panel.form.form.hidden;
			if ( ! open ) {
				mic.cancelIn( key );
			}
			panel.setFormOpen( open );
		}

		panels.forEach( ( panel, key ) => {
			panel.retry.addEventListener( 'click', () => requestDrafts( [ key ] ) );
			panel.copy.addEventListener( 'click', () => copyShare.copy( key ) );
			panel.share.addEventListener( 'click', () => copyShare.share( key ) );
			panel.change.addEventListener( 'click', () => toggleForm( key ) );
			panel.form.send.addEventListener( 'click', () => sendTyped( key ) );
			panel.form.mic.addEventListener( 'click', () => mic.toggle( key ) );
			panel.form.input.addEventListener( 'keydown', ( event ) => {
				if ( ENTER_KEY === event.key ) {
					event.preventDefault();
					sendTyped( key );
				}
			} );
		} );

		// Drafts this visit already got stay, even if a progress save answered with an older state.
		const known = { ...cleanDrafts( api.state.drafts ), ...client.latest() };
		platforms.forEach( ( key ) => {
			if ( known[ key ] ) {
				panels.get( key ).showDraft( known[ key ] );
			}
		} );
		const missing = platforms.filter( ( key ) => ! known[ key ] );
		if ( missing.length > 0 ) {
			requestDrafts( missing );
		}

		const stopLocale = api.events.on( 'locale', () => {
			panels.forEach( ( panel ) => panel.form.input.setAttribute( 'placeholder', t( 'postsChangePlaceholder' ) ) );
		} );

		return {
			destroy() {
				alive = false;
				mic.cancel();
				copyShare.destroy();
				stopLocale();
			},
		};
	};
}

/** The browser's side of the screen. installModules() passes a plug-in only the api. */
function browserDeps() {
	const navigator = window.navigator;
	const hasClipboard = navigator.clipboard && 'function' === typeof navigator.clipboard.writeText;
	return {
		fetchFn: ( ...args ) => window.fetch( ...args ),
		timers: {
			setTimeout: ( callback, ms ) => window.setTimeout( callback, ms ),
			clearTimeout: ( id ) => window.clearTimeout( id ),
		},
		clipboard: hasClipboard ? ( value ) => navigator.clipboard.writeText( value ) : null,
		share: 'function' === typeof navigator.share ? ( data ) => navigator.share( data ) : null,
		recorder: createVoiceRecorder( { mediaDevices: navigator.mediaDevices, MediaRecorderCtor: window.MediaRecorder, BlobCtor: window.Blob } ),
	};
}

/**
 * Adds the English copy and registers screen 14.
 *
 * @param {Object} api    The onboarding API.
 * @param {Object} [deps] { fetchFn, timers, clipboard( text ), share( data ), recorder }; the
 *                        browser's when left out. clipboard and share are null where missing.
 */
export function install( api, deps = browserDeps() ) {
	api.strings.add( 'en', POSTS_STRINGS_EN );
	const client = createDraftClient( { api, fetchFn: deps.fetchFn, timers: deps.timers } );
	api.screens.register( {
		id: 'draft_posts',
		advanceMode: api.screens.ADVANCE_MODES.MANUAL,
		render: renderPosts( { ...deps, client } ),
	} );
}
