/**
 * Plug-in for the onboarding appointment screens (ENGR-7577). The screens are in
 * onboarding-appointment.js; this file only connects them to the page: the landing flow's
 * appointment rules and helpers (PO-3109, appointment.js and appointment-screen.js), the
 * clipboard, fetch, the clock and the timers.
 */
import { CHOICE, validateAppointment, listTimeZones, getDefaultTimeZone } from '@ihq/aicoach/appointment';
import { appointmentErrorKey, requestAppointmentLink, copyToClipboard, timeZoneChoices } from '@ihq/aicoach/appointment-screen';
import { installAppointment } from '@ihq/aicoach/onboarding-appointment';

/**
 * @param {Object} api The onboarding API.
 */
export function install( api ) {
	installAppointment( api, {
		rules: { CHOICE, validateAppointment, listTimeZones, getDefaultTimeZone },
		helpers: { appointmentErrorKey, requestAppointmentLink, copyToClipboard, timeZoneChoices },
		env: {
			clipboard: window.navigator.clipboard,
			ClipboardItemClass: window.ClipboardItem,
			fetchFn: ( ...args ) => window.fetch( ...args ),
			now: () => Date.now(),
			setTimeout: ( callback, ms ) => window.setTimeout( callback, ms ),
			clearTimeout: ( id ) => window.clearTimeout( id ),
		},
	} );
}
