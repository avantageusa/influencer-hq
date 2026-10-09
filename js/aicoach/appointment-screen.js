/**
 * The parts of the "Set your appointment" screen that are not DOM (PO-3109,
 * FR-18): which text belongs to each error from appointment.js, the link that is
 * copied, the clipboard call and the time zone list the visitor picks from.
 * Kept apart from js/aicoach-coach-flow.js so they can be tested without a browser.
 */

const ERROR_KEY_PREFIX = 'appointmentError-';
const APPOINTMENT_PARAM = 'appointment';

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
 * The link the visitor copies.
 *
 * PLACEHOLDER until the server issues links (PO-3109 step 4): the page's own
 * address with the start time in it. It is not signed and no page handles it yet,
 * so it must not reach production as it is. Step 4 replaces this function's body
 * with the signed link the server returns.
 *
 * @param {Object} options
 * @param {string} options.pageUrl      The page the visitor is on.
 * @param {string} options.startsAtIso  The appointment start, ISO 8601 in UTC.
 * @return {string}
 */
export function buildAppointmentLink( { pageUrl, startsAtIso } ) {
	const link = new URL( pageUrl );
	link.hash = '';
	link.searchParams.set( APPOINTMENT_PARAM, startsAtIso );
	return link.toString();
}

/**
 * Copies text to the clipboard. A browser that has no clipboard API, or refuses
 * (permission, an insecure page), is reported rather than thrown.
 *
 * @param {string} text
 * @param {{ writeText: Function }|undefined} clipboard Usually navigator.clipboard.
 * @return {Promise<{ ok: boolean }>}
 */
export async function copyToClipboard( text, clipboard ) {
	if ( ! clipboard || 'function' !== typeof clipboard.writeText ) {
		return { ok: false };
	}
	try {
		await clipboard.writeText( text );
		return { ok: true };
	} catch ( error ) {
		return { ok: false };
	}
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
