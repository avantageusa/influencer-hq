/**
 * The onboarding appointment screens (ENGR-7577, PO-3429 Scenario 37): "Talk more?" and "Set
 * your appointment", shown to everyone before the closing screen (onboarding-sequence.js).
 *
 * - time_up (PICK): Continue to IHQ portal goes to the closing screen (a branch in BRANCHES);
 *   Set an Appointment goes to scheduling. Single choice, moving on PICK_ADVANCE_DELAY_MS after
 *   a pick, like the question screens. Nothing is stored.
 * - appointment (MANUAL): the landing flow's scheduling (PO-3109) with its rules
 *   (appointment.js) and helpers (appointment-screen.js): In 30 minutes, In an hour, or Other
 *   with a date, a time zone and a time. Copy Your Appointment Link checks the choice first
 *   (nothing is asked of the server for a missing option, a missing field or a past time),
 *   asks the server for the signed link (POST /ihq/v1/onboarding/appointment), copies it and
 *   says so, then moves on to the closing screen COPIED_ADVANCE_DELAY_MS later.
 *
 * Everything from the page (the landing modules, the clipboard, fetch, the clock and timers)
 * is passed in, so this is tested with the fake DOM; onboarding-screens-appointment.js is the
 * plug-in that connects it to the browser.
 */

/** English copy: the landing flow's wording (locales.js, PO-3109) and Figma frame 47487:22255. */
export const APPOINTMENT_STRINGS_EN = {
	// Ivan's proposal on PO-3429 (2026-10-20), still to be confirmed with Marcus.
	talkMoreTitle: 'Talk more?',
	timeUpContinue: 'Continue to IHQ portal',
	timeUpSetAppointment: 'Set an Appointment',
	timeUpRequired: 'Choose one to continue.',

	appointmentReady: "Yes, I'm Ready...",
	appointmentTitle: 'Set your appointment',
	appointmentIn30Minutes: 'In 30 minutes',
	appointmentInAnHour: 'In an hour',
	appointmentOther: 'Other',
	appointmentDate: 'Date',
	appointmentTimeZone: 'Timezone',
	appointmentTime: 'Appointment Time',
	appointmentCopy: 'Copy Your Appointment Link',
	appointmentHint: 'Open this link at your appointment time to join your video with Sami.',
	appointmentCopied: 'Your appointment link is copied.',
	appointmentRequestFailed: 'We could not create your appointment link. Please try again.',
	appointmentCopyFailed: 'We could not copy the link automatically. Please copy it from the box below.',
	appointmentLinkLabel: 'Your appointment link',
	// Keyed by appointmentErrorKey( code ) (appointment-screen.js), one per ERROR in appointment.js.
	'appointmentError-choice-missing': 'Please choose when you would like your appointment.',
	'appointmentError-date-missing': 'Please choose a date.',
	'appointmentError-time-zone-missing': 'Please choose a time zone.',
	'appointmentError-time-missing': 'Please choose a time.',
	'appointmentError-date-invalid': 'That date is not valid. Please choose another date.',
	'appointmentError-time-invalid': 'That time is not valid. Please choose another time.',
	'appointmentError-time-zone-invalid': 'That time zone is not valid. Please choose another one.',
	'appointmentError-time-does-not-exist': 'That time does not exist in this time zone because the clocks change then. Please choose another time.',
	'appointmentError-time-in-past': 'That time has already passed. Please choose a later time.',
};

/** The two stages (SEQUENCE in onboarding-sequence.js). */
export const STAGE = Object.freeze( {
	TIME_UP: 'time_up',
	APPOINTMENT: 'appointment',
} );

/** "Talk more?" choices. CONTINUE is the choice of the branch to the closing screen (BRANCHES). */
export const TIME_UP_CHOICE = Object.freeze( {
	CONTINUE: 'continue',
	APPOINTMENT: 'appointment',
} );

const TIME_UP_OPTIONS = Object.freeze( [
	Object.freeze( { value: TIME_UP_CHOICE.CONTINUE, labelKey: 'timeUpContinue' } ),
	Object.freeze( { value: TIME_UP_CHOICE.APPOINTMENT, labelKey: 'timeUpSetAppointment' } ),
] );

/** The question screens' pause after a pick, so the flow feels the same. */
export const PICK_ADVANCE_DELAY_MS = 500;

/** From the copy confirmation to the closing screen (PO-3429: 4 seconds). */
export const COPIED_ADVANCE_DELAY_MS = 4000;

/** The radio keyboard pattern: these keys move the choice to the next or previous option. */
const NEXT_OPTION_KEYS = Object.freeze( [ 'ArrowDown', 'ArrowRight' ] );
const PREVIOUS_OPTION_KEYS = Object.freeze( [ 'ArrowUp', 'ArrowLeft' ] );

/**
 * How far an arrow key moves the choice.
 *
 * @param {string} key KeyboardEvent.key.
 * @return {number} 1, -1, or 0 for a key that does not move it.
 */
function arrowStep( key ) {
	if ( NEXT_OPTION_KEYS.includes( key ) ) {
		return 1;
	}
	if ( PREVIOUS_OPTION_KEYS.includes( key ) ) {
		return -1;
	}
	return 0;
}

/**
 * A single-choice group in the question screens' look (role radio buttons with the green
 * square), in sentence case.
 *
 * @param {Object}   options
 * @param {Document} options.document
 * @param {Function} options.text       The engine's text().
 * @param {Object[]} options.choices    { value, labelKey }.
 * @param {string}   options.labelledBy Id of the element naming the group.
 * @param {Function} options.onPick     Called with the picked value (click or arrow key).
 * @return {Object} { element, paint( chosen ) }.
 */
function radioGroup( { document, text, choices, labelledBy, onPick } ) {
	const element = document.createElement( 'div' );
	element.setAttribute( 'role', 'radiogroup' );
	element.setAttribute( 'aria-labelledby', labelledBy );
	element.className = 'ihq-onb-q__options ihq-onb-appt__options';
	const buttons = choices.map( ( choice ) => {
		const button = document.createElement( 'button' );
		button.setAttribute( 'type', 'button' );
		button.setAttribute( 'role', 'radio' );
		button.setAttribute( 'aria-checked', 'false' );
		button.setAttribute( 'data-value', choice.value );
		button.className = 'ihq-onb-q__option ihq-onb-appt__option';
		button.append( text( 'span', choice.labelKey, 'ihq-onb-q__label' ) );
		return button;
	} );
	element.append( ...buttons );

	element.addEventListener( 'click', ( event ) => {
		const button = event.target.closest( 'button' );
		if ( button ) {
			onPick( button.getAttribute( 'data-value' ) );
		}
	} );
	element.addEventListener( 'keydown', ( event ) => {
		const step = arrowStep( event.key );
		const current = event.target.closest( 'button' );
		if ( 0 === step || ! current ) {
			return;
		}
		event.preventDefault();
		const index = ( buttons.indexOf( current ) + step + buttons.length ) % buttons.length;
		onPick( buttons[ index ].getAttribute( 'data-value' ) );
		buttons[ index ].focus();
	} );

	/**
	 * Marks the chosen option, and makes it the one Tab stop (the first when none is chosen).
	 *
	 * @param {string} chosen
	 */
	function paint( chosen ) {
		const hasChosen = buttons.some( ( button ) => button.getAttribute( 'data-value' ) === chosen );
		buttons.forEach( ( button, index ) => {
			const isChecked = button.getAttribute( 'data-value' ) === chosen;
			const isTabStop = isChecked || ( ! hasChosen && 0 === index );
			button.setAttribute( 'aria-checked', isChecked ? 'true' : 'false' );
			button.setAttribute( 'tabindex', isTabStop ? '0' : '-1' );
		} );
	}
	paint( '' );
	return { element, paint };
}

/**
 * A heading with an id, for a group to be named by.
 *
 * @param {Object}   options
 * @param {Function} options.text      The engine's text().
 * @param {string}   options.tag
 * @param {string}   options.key       String-table key.
 * @param {string}   options.id
 * @param {string}   options.className
 * @return {Element} The heading.
 */
function namedHeading( { text, tag, key, id, className } ) {
	const heading = text( tag, key, className );
	heading.setAttribute( 'id', id );
	return heading;
}

/**
 * "Talk more?".
 *
 * @param {Object} env { setTimeout, clearTimeout }.
 * @return {Function} An engine render().
 */
function renderTimeUp( env ) {
	return function( { container, text, advance } ) {
		const document = container.ownerDocument;
		const headingId = 'ihq-onb-appt-time-up';
		container.append( namedHeading( { text, tag: 'h2', key: 'talkMoreTitle', id: headingId, className: 'ihq-onb-appt__title' } ) );

		const error = text( 'p', 'timeUpRequired', 'ihq-onb__error' );
		error.setAttribute( 'role', 'alert' );
		error.hidden = true;

		let chosen = '';
		let advanceTimer = null;

		function cancelAdvance() {
			env.clearTimeout( advanceTimer );
			advanceTimer = null;
		}

		function scheduleAdvance() {
			cancelAdvance();
			advanceTimer = env.setTimeout( () => {
				advanceTimer = null;
				advance();
			}, PICK_ADVANCE_DELAY_MS );
		}

		const group = radioGroup( {
			document,
			text,
			choices: TIME_UP_OPTIONS,
			labelledBy: headingId,
			onPick( value ) {
				// Re-picking the current choice keeps a pause already running.
				if ( value === chosen && null !== advanceTimer ) {
					return;
				}
				chosen = value;
				error.hidden = true;
				group.paint( chosen );
				scheduleAdvance();
			},
		} );
		container.append( group.element, error );

		return {
			isValid: () => '' !== chosen,
			showValidation() {
				error.hidden = false;
			},
			choice: () => chosen,
			destroy: cancelAdvance,
		};
	};
}

/**
 * A labelled field of the Other choice.
 *
 * @param {Object}   options
 * @param {Document} options.document
 * @param {Function} options.text
 * @param {string}   options.tag      'input' or 'select'.
 * @param {string}   options.type     Input type ('' for a select).
 * @param {string}   options.labelKey
 * @param {string}   options.name     data-field, for the tests and the styles.
 * @return {Object} { label, control }.
 */
function field( { document, text, tag, type, labelKey, name } ) {
	const label = document.createElement( 'label' );
	label.className = 'ihq-onb-appt__field';
	const control = document.createElement( tag );
	control.className = 'ihq-onb-appt__input';
	control.setAttribute( 'data-field', name );
	if ( type ) {
		control.setAttribute( 'type', type );
	}
	label.append( text( 'span', labelKey, 'ihq-onb-appt__field-label' ), control );
	return { label, control };
}

/**
 * The Timezone list, with the Influencer's own zone chosen.
 *
 * @param {Element} select
 * @param {Object}  rules   appointment.js.
 * @param {Object}  helpers appointment-screen.js.
 */
function fillTimeZones( select, rules, helpers ) {
	const document = select.ownerDocument;
	const choices = helpers.timeZoneChoices( { zones: rules.listTimeZones(), defaultZone: rules.getDefaultTimeZone() } );
	choices.zones.forEach( ( zone ) => {
		const option = document.createElement( 'option' );
		option.setAttribute( 'value', zone );
		option.textContent = zone;
		select.append( option );
	} );
	select.value = choices.selected;
}

/**
 * The link to hand the clipboard.
 *
 * @param {Object} created From requestAppointmentLink(): { ok, link, errors, failed }.
 * @return {string|Promise} The link, or a rejection when there is none (nothing is copied).
 */
function linkOrReject( created ) {
	if ( created.ok ) {
		return created.link;
	}
	return Promise.reject( new Error( 'no appointment link' ) );
}

/**
 * "Set your appointment".
 *
 * @param {Object} deps { rules, helpers, env }.
 * @return {Function} An engine render().
 */
function renderAppointment( { rules, helpers, env } ) {
	return function( { container, api, text, advance } ) {
		const document = container.ownerDocument;
		const titleId = 'ihq-onb-appt-title';
		container.append(
			text( 'h2', 'appointmentReady', 'ihq-onb-appt__title' ),
			namedHeading( { text, tag: 'p', key: 'appointmentTitle', id: titleId, className: 'ihq-onb-appt__subtitle' } )
		);

		let choice = '';
		// The link the server issued, kept when only the copy failed: pressing the button again
		// copies the same link instead of booking again (one appointment per Influencer, so a
		// new one would invalidate the link they may have copied by hand).
		let createdLink = null;
		let copied = false;
		let doneTimer = null;
		let destroyed = false;

		const choices = radioGroup( {
			document,
			text,
			choices: [
				{ value: rules.CHOICE.IN_30_MINUTES, labelKey: 'appointmentIn30Minutes' },
				{ value: rules.CHOICE.IN_AN_HOUR, labelKey: 'appointmentInAnHour' },
				{ value: rules.CHOICE.OTHER, labelKey: 'appointmentOther' },
			],
			labelledBy: titleId,
			onPick: pick,
		} );
		container.append( choices.element );

		const other = document.createElement( 'div' );
		other.className = 'ihq-onb-appt__other';
		other.hidden = true;
		const date = field( { document, text, tag: 'input', type: 'date', labelKey: 'appointmentDate', name: 'date' } );
		const zone = field( { document, text, tag: 'select', type: '', labelKey: 'appointmentTimeZone', name: 'time-zone' } );
		const time = field( { document, text, tag: 'input', type: 'time', labelKey: 'appointmentTime', name: 'time' } );
		other.append( date.label, zone.label, time.label );
		container.append( other );
		fillTimeZones( zone.control, rules, helpers );

		const copy = text( 'button', 'appointmentCopy', 'ihq-onb__button ihq-onb-appt__copy' );
		copy.setAttribute( 'type', 'button' );
		const hint = text( 'p', 'appointmentHint', 'ihq-onb-appt__hint' );
		const errors = document.createElement( 'div' );
		errors.className = 'ihq-onb__error ihq-onb-appt__errors';
		errors.setAttribute( 'role', 'alert' );
		const status = document.createElement( 'p' );
		status.className = 'ihq-onb-appt__status';
		status.setAttribute( 'role', 'status' );
		const linkBox = document.createElement( 'input' );
		linkBox.className = 'ihq-onb-appt__input ihq-onb-appt__link';
		linkBox.setAttribute( 'type', 'text' );
		linkBox.setAttribute( 'readonly', '' );
		linkBox.setAttribute( 'data-onb-label', 'appointmentLinkLabel' );
		linkBox.setAttribute( 'aria-label', api.strings.t( api.getLocale(), 'appointmentLinkLabel' ) );
		linkBox.hidden = true;
		container.append( copy, errors, status, linkBox, hint );

		function pick( value ) {
			// Locked while a link is being made and after it is copied, so the choice on screen
			// is always the one that was booked.
			if ( copied || copy.disabled ) {
				return;
			}
			choice = value;
			choices.paint( choice );
			clearMessages();
			forgetLink();
			// Other swaps the three options for its three fields; the flow only moves forward,
			// so nothing brings the options back (as in the landing flow).
			if ( rules.CHOICE.OTHER === value ) {
				choices.element.hidden = true;
				other.hidden = false;
				date.control.focus();
			}
		}

		function clearMessages() {
			errors.replaceChildren();
			status.textContent = '';
			status.removeAttribute( 'data-onb-string' );
		}

		function showStatus( key ) {
			status.setAttribute( 'data-onb-string', key );
			status.textContent = api.strings.t( api.getLocale(), key );
		}

		/**
		 * One line per problem, each re-labelled on a language change.
		 *
		 * @param {string[]} keys String-table keys.
		 */
		function showErrors( keys ) {
			errors.replaceChildren( ...keys.map( ( key ) => text( 'p', key ) ) );
		}

		function forgetLink() {
			createdLink = null;
			linkBox.hidden = true;
			linkBox.value = '';
		}

		/**
		 * Locks the choice and the Other fields while a link is being made (the request uses
		 * what they held when Copy was pressed), and unlocks them when it fails.
		 *
		 * @param {boolean} locked
		 */
		function setLocked( locked ) {
			copy.disabled = locked;
			[ date.control, zone.control, time.control ].forEach( ( control ) => {
				control.disabled = locked;
			} );
		}

		// Changing a field makes an issued link the wrong one.
		other.addEventListener( 'input', forgetLink );
		other.addEventListener( 'change', forgetLink );

		function selection() {
			return { choice, date: date.control.value, time: time.control.value, timeZone: zone.control.value };
		}

		async function copyLink() {
			// The button stays disabled once copied; this also holds if something enables it.
			if ( copied ) {
				return;
			}
			clearMessages();
			// The clock is read once, here: "in 30 minutes" counts from the moment the link is
			// made. This check only gives instant messages; the server checks again.
			const chosen = selection();
			const checked = rules.validateAppointment( chosen, env.now() );
			if ( ! checked.ok ) {
				showErrors( checked.errors.map( helpers.appointmentErrorKey ) );
				return;
			}

			setLocked( true );
			// The request starts inside the tap and the clipboard is handed the pending link:
			// Safari on a phone only accepts a clipboard write made inside the gesture.
			const pending = createdLink
				? Promise.resolve( { ok: true, link: createdLink, errors: [], failed: false } )
				: helpers.requestAppointmentLink( { selection: chosen, url: api.config.appointmentUrl, nonce: api.config.nonce, fetchFn: env.fetchFn } );
			const copiedResult = await helpers.copyToClipboard( pending.then( linkOrReject ), {
				clipboard: env.clipboard,
				ClipboardItemClass: env.ClipboardItemClass,
			} );
			const created = await pending;
			if ( destroyed ) {
				return;
			}
			if ( ! created.ok ) {
				setLocked( false );
				showRefusal( created );
				return;
			}
			createdLink = created.link;
			if ( ! copiedResult.ok ) {
				// Moving on now would lose the only copy of the link: show it to copy by hand.
				linkBox.value = created.link;
				linkBox.hidden = false;
				linkBox.focus();
				linkBox.select();
				showStatus( 'appointmentCopyFailed' );
				setLocked( false );
				return;
			}
			copied = true;
			showStatus( 'appointmentCopied' );
			doneTimer = env.setTimeout( () => {
				doneTimer = null;
				advance();
			}, COPIED_ADVANCE_DELAY_MS );
		}

		/**
		 * @param {Object} created A refused requestAppointmentLink() result.
		 */
		function showRefusal( created ) {
			if ( created.errors.length > 0 ) {
				showErrors( created.errors.map( helpers.appointmentErrorKey ) );
				return;
			}
			showErrors( [ 'appointmentRequestFailed' ] );
		}

		copy.addEventListener( 'click', copyLink );

		return {
			destroy() {
				destroyed = true;
				env.clearTimeout( doneTimer );
				doneTimer = null;
			},
		};
	};
}

/**
 * The screen definitions.
 *
 * @param {Object} deps
 * @param {Object} deps.advanceModes The engine's ADVANCE_MODES.
 * @param {Object} deps.rules        appointment.js: { CHOICE, validateAppointment, listTimeZones, getDefaultTimeZone }.
 * @param {Object} deps.helpers      appointment-screen.js: { appointmentErrorKey, requestAppointmentLink, copyToClipboard, timeZoneChoices }.
 * @param {Object} deps.env          { clipboard, ClipboardItemClass, fetchFn, now, setTimeout, clearTimeout }.
 * @return {Object[]} Engine screen definitions.
 */
export function appointmentScreens( { advanceModes, rules, helpers, env } ) {
	return [
		{ id: STAGE.TIME_UP, advanceMode: advanceModes.PICK, render: renderTimeUp( env ) },
		{ id: STAGE.APPOINTMENT, advanceMode: advanceModes.MANUAL, render: renderAppointment( { rules, helpers, env } ) },
	];
}

/**
 * Adds the English copy and registers the two screens.
 *
 * @param {Object} api  The onboarding API.
 * @param {Object} deps { rules, helpers, env }, as appointmentScreens().
 */
export function installAppointment( api, { rules, helpers, env } ) {
	api.strings.add( 'en', APPOINTMENT_STRINGS_EN );
	appointmentScreens( { advanceModes: api.screens.ADVANCE_MODES, rules, helpers, env } )
		.forEach( ( definition ) => api.screens.register( definition ) );
}
