/**
 * The parts of the "Set your appointment" screen that are not DOM (PO-3109,
 * FR-18): which text belongs to each error from appointment.js, the request that
 * asks the server for the signed link (inc/aicoach-appointment.php), the clipboard
 * call and the time zone list the visitor picks from. Kept apart from
 * js/aicoach-coach-flow.js so they can be tested without a browser.
 */

const ERROR_KEY_PREFIX = 'appointmentError-';
const CREATED_STATUS = 201;
const REFUSED_STATUS = 400;

/**
 * The locales.js key of the correction message for an error code of
 * validateAppointment().
 *
 * @param {string} errorCode One of ERROR in appointment.js.
 * @return {string}
 */
export function appointmentErrorKey( errorCode ) {
	return ERROR_KEY_PREFIX + errorCode;
}

/**
 * Asks the server for the appointment link. The server validates the time again
 * with the same rules and signs the link, so what comes back can be trusted and
 * what the browser computed cannot.
 *
 * Never rejects: a refusal carries the server's error codes (the same codes as
 * appointment.js, for the screen to turn into text), and anything else (no
 * network, a limit, a server error, an answer that is not what the route returns)
 * is reported as `failed`.
 *
 * @param {Object} options
 * @param {{ choice: string, date: string, time: string, timeZone: string }} options.selection
 * @param {string}   options.url     The appointment route.
 * @param {string}   options.nonce   The REST nonce.
 * @param {Function} options.fetchFn fetch, or a replacement in tests.
 * @return {Promise<{ ok: boolean, link: (string|null), errors: string[], failed: boolean }>}
 */
export async function requestAppointmentLink( { selection, url, nonce, fetchFn } ) {
	const failure = { ok: false, link: null, errors: [], failed: true };
	let response;
	try {
		response = await fetchFn( url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
			credentials: 'same-origin',
			body: JSON.stringify( selection ),
		} );
	} catch ( error ) {
		return failure;
	}

	let body = null;
	try {
		body = await response.json();
	} catch ( error ) {
		return failure;
	}

	if ( CREATED_STATUS === response.status && body && 'string' === typeof body.link && '' !== body.link ) {
		return { ok: true, link: body.link, errors: [], failed: false };
	}
	if ( REFUSED_STATUS === response.status && body && Array.isArray( body.errors ) ) {
		const errors = body.errors.filter( ( code ) => 'string' === typeof code );
		if ( errors.length > 0 ) {
			return { ok: false, link: null, errors, failed: false };
		}
	}
	return failure;
}

/**
 * Copies text to the clipboard. The text may still be on its way (a promise): the
 * write is started at once, inside the visitor's tap, and finished when the text
 * arrives, because Safari on a phone only allows a clipboard write made inside the
 * gesture, and a request to the server comes before the link exists. A browser
 * that has no clipboard API, or refuses (permission, an insecure page, a text
 * that never arrived), is reported rather than thrown.
 *
 * @param {string|Promise<string>} text
 * @param {Object} environment
 * @param {{ write: Function, writeText: Function }|undefined} environment.clipboard Usually navigator.clipboard.
 * @param {Function|undefined} environment.ClipboardItemClass Usually window.ClipboardItem.
 * @return {Promise<{ ok: boolean }>}
 */
export async function copyToClipboard( text, { clipboard, ClipboardItemClass } = {} ) {
	const pendingText = Promise.resolve( text );
	// A text that never arrives must not surface as an unhandled rejection when
	// there is no clipboard to hand it to.
	pendingText.catch( () => {} );

	if ( ! clipboard ) {
		return { ok: false };
	}
	if ( 'function' === typeof clipboard.write && 'function' === typeof ClipboardItemClass ) {
		try {
			const blob = pendingText.then( ( value ) => new Blob( [ value ], { type: 'text/plain' } ) );
			await clipboard.write( [ new ClipboardItemClass( { 'text/plain': blob } ) ] );
			return { ok: true };
		} catch ( error ) {
			// Fall through to the plain call below.
		}
	}
	if ( 'function' === typeof clipboard.writeText ) {
		try {
			await clipboard.writeText( await pendingText );
			return { ok: true };
		} catch ( error ) {
			return { ok: false };
		}
	}
	return { ok: false };
}

/**
 * The Timezone list: the browser's zones, with the visitor's own zone chosen. A
 * zone the browser reports as the visitor's own but leaves out of its list (an
 * old alias) is added at the top so it can still be chosen.
 *
 * @param {Object} options
 * @param {string[]} options.zones        From listTimeZones().
 * @param {string}   options.defaultZone  From getDefaultTimeZone(); may be empty.
 * @return {{ zones: string[], selected: string }}
 */
export function timeZoneChoices( { zones, defaultZone } ) {
	if ( ! defaultZone ) {
		return { zones: zones.slice(), selected: '' };
	}
	if ( zones.includes( defaultZone ) ) {
		return { zones: zones.slice(), selected: defaultZone };
	}
	return { zones: [ defaultZone, ...zones ], selected: defaultZone };
}
