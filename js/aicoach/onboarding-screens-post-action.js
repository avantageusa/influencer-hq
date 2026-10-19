/**
 * Onboarding screen 15, the post action (ENGR-7570, PO-3063 T8, PO-3213).
 *
 * After the drafts of screen 14, the Influencer says what they will do with them: post now,
 * edit them together with Sami now, or edit later today and then post. Single choice, saved
 * to the account store as answers.post_action_choice (one of POST_ACTIONS' values, which
 * ihq_portal_onboarding_answer_values() accepts) as soon as it is picked.
 *
 * Like the question screens (onboarding-screens-questions.js) it is a PICK screen with no
 * CONTINUE: it moves on by itself PICK_ADVANCE_DELAY_MS after a pick, and re-picking the
 * current answer is also the way on. The choice is the engine's branch: "Let's edit this
 * together now" goes back to screen 14 with the drafts kept (onboarding-sequence.js
 * BRANCHES); the other two go on to the closing screen. The answers look like the question
 * screens' and use their classes, in the design's sentence case.
 *
 * A plug-in module: the entry installs it, and it registers its screen with the engine.
 */

/** English copy (PO-3213; Figma instance 46893:117629, frame "15 Draft Posts to copy/paste"). */
export const POST_ACTION_STRINGS_EN = {
	postActionQuestion: 'What would you like to do with your posts?',
	postActionPostNow: "Great job, I'll post these now.",
	postActionEditTogether: "Let's edit this together now.",
	postActionEditLater: "I'm going to edit later today and then post.",
	postActionRequired: 'Choose one to continue.',
};

/** The stored answer field. */
export const POST_ACTION_FIELD = 'post_action_choice';

/** The choices in screen order, with their stored values. */
export const POST_ACTIONS = Object.freeze( [
	Object.freeze( { value: 'post_now', labelKey: 'postActionPostNow' } ),
	Object.freeze( { value: 'edit_together', labelKey: 'postActionEditTogether' } ),
	Object.freeze( { value: 'edit_later', labelKey: 'postActionEditLater' } ),
] );

/** The question screens' pause after a pick (PICK_ADVANCE_DELAY_MS there), so the flow feels the same. */
export const PICK_ADVANCE_DELAY_MS = 500;

/** The radio keyboard pattern: these keys move the choice to the next or previous option. */
const NEXT_OPTION_KEYS = Object.freeze( [ 'ArrowDown', 'ArrowRight' ] );
const PREVIOUS_OPTION_KEYS = Object.freeze( [ 'ArrowUp', 'ArrowLeft' ] );

/** The browser's timers. installModules() passes a plug-in only the api, so install() defaults to these. */
const BROWSER_TIMERS = Object.freeze( {
	setTimeout: ( callback, ms ) => window.setTimeout( callback, ms ),
	clearTimeout: ( id ) => window.clearTimeout( id ),
} );

/**
 * The stored choice, or '' when there is none or it is not one of the three. Pure.
 *
 * @param {Object} answers The store's answers.
 * @return {string} A POST_ACTIONS value, or ''.
 */
export function storedChoice( answers ) {
	const value = answers && 'object' === typeof answers ? answers[ POST_ACTION_FIELD ] : undefined;
	return POST_ACTIONS.some( ( action ) => action.value === value ) ? value : '';
}

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

function optionButton( { document, text, action } ) {
	const button = document.createElement( 'button' );
	button.setAttribute( 'type', 'button' );
	button.setAttribute( 'role', 'radio' );
	button.setAttribute( 'aria-checked', 'false' );
	button.setAttribute( 'data-value', action.value );
	button.className = 'ihq-onb-q__option ihq-onb-post-action__option';
	button.append( text( 'span', action.labelKey, 'ihq-onb-q__label' ) );
	return button;
}

/**
 * Marks the chosen option, and makes it the group's one Tab stop (the first option when none
 * is chosen), as the radio pattern expects.
 *
 * @param {Element[]} buttons
 * @param {string}    chosen
 */
function paint( buttons, chosen ) {
	const hasChosen = buttons.some( ( button ) => button.getAttribute( 'data-value' ) === chosen );
	buttons.forEach( ( button, index ) => {
		const isChecked = button.getAttribute( 'data-value' ) === chosen;
		const isTabStop = isChecked || ( ! hasChosen && 0 === index );
		button.setAttribute( 'aria-checked', isChecked ? 'true' : 'false' );
		button.setAttribute( 'tabindex', isTabStop ? '0' : '-1' );
	} );
}

function renderPostAction( timers ) {
	return function( { container, api, text, advance } ) {
		const document = container.ownerDocument;
		const group = document.createElement( 'div' );
		group.setAttribute( 'role', 'radiogroup' );
		// The design has no heading on this screen, so the question names the group for a screen reader.
		group.setAttribute( 'data-onb-label', 'postActionQuestion' );
		group.setAttribute( 'aria-label', api.strings.t( api.getLocale(), 'postActionQuestion' ) );
		group.className = 'ihq-onb-q__options ihq-onb-post-action__options';
		const buttons = POST_ACTIONS.map( ( action ) => optionButton( { document, text, action } ) );
		group.append( ...buttons );
		container.append( group );

		const error = text( 'p', 'postActionRequired', 'ihq-onb__error' );
		error.setAttribute( 'role', 'alert' );
		error.hidden = true;
		container.append( error );

		let chosen = storedChoice( api.state.answers );
		let advanceTimer = null;

		function cancelAdvance() {
			timers.clearTimeout( advanceTimer );
			advanceTimer = null;
		}

		function scheduleAdvance() {
			cancelAdvance();
			advanceTimer = timers.setTimeout( () => {
				advanceTimer = null;
				advance();
			}, PICK_ADVANCE_DELAY_MS );
		}

		function pick( value ) {
			if ( value === chosen ) {
				// Re-picking the current answer keeps a pause already running; with none running
				// (a returning Influencer's saved choice) it is the way on.
				if ( null === advanceTimer ) {
					scheduleAdvance();
				}
				return;
			}
			chosen = value;
			error.hidden = true;
			paint( buttons, chosen );
			api.save( { answers: { [ POST_ACTION_FIELD ]: value } } );
			scheduleAdvance();
		}

		group.addEventListener( 'click', ( event ) => {
			const button = event.target.closest( 'button' );
			if ( button ) {
				pick( button.getAttribute( 'data-value' ) );
			}
		} );
		group.addEventListener( 'keydown', ( event ) => {
			const step = arrowStep( event.key );
			const current = event.target.closest( 'button' );
			if ( 0 === step || ! current ) {
				return;
			}
			event.preventDefault();
			const index = ( buttons.indexOf( current ) + step + buttons.length ) % buttons.length;
			pick( buttons[ index ].getAttribute( 'data-value' ) );
			buttons[ index ].focus();
		} );
		paint( buttons, chosen );

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
 * Adds the English copy and registers screen 15.
 *
 * @param {Object} api      The onboarding API.
 * @param {Object} [timers] { setTimeout, clearTimeout }; the browser's when left out.
 */
export function install( api, timers = BROWSER_TIMERS ) {
	api.strings.add( 'en', POST_ACTION_STRINGS_EN );
	api.screens.register( {
		id: 'post_action',
		advanceMode: api.screens.ADVANCE_MODES.PICK,
		render: renderPostAction( timers ),
	} );
}
