/**
 * The onboarding question screens (ENGR-7568, PO-3063 T6): 3 baccarat experience,
 * 4 streaming experience, 5 equity knowledge and 12 active followers. Each is single choice.
 *
 * Screens 4 and 5 have conditional sub-options: picking I'M NEW TO STREAMING (or I CURRENTLY
 * OWN EQUITY) reveals two more, one of which is then required; picking another answer hides
 * them and clears the sub-answer.
 *
 * Each answer is saved to the account store as soon as it is given, as its enum value (the
 * values ihq_portal_onboarding_answer_values() accepts, inc/portal-onboarding-progress.php),
 * so a drop-out's answers are kept and reach Braze (ENGR-7573). The engine's api.save() sends
 * every progress save one at a time in call order (answers and stages alike), so answers are
 * saved in click order.
 *
 * There is no CONTINUE (design decision 1): the screens are PICK screens and move on by
 * themselves PICK_ADVANCE_DELAY_MS after a pick that completes the answer, so the choice is
 * seen to register first. A pick that reveals the follow-up answers waits for the follow-up
 * pick. An arrow-key move is a pick like a click, and each further pick during the pause
 * starts it again, so a keyboard user can browse the answers before settling on one.
 *
 * A plug-in module: the entry installs it, and it registers its screens with the engine.
 */

/** English copy (PO-3204, PO-3205, PO-3206, PO-3210; OQ16 for "Under 10,000"). */
export const QUESTION_STRINGS_EN = {
	baccaratQuestion: 'What is your experience with Baccarat?',
	baccaratPlayedALot: "I'VE PLAYED A LOT",
	baccaratPlayedSome: "I'VE PLAYED SOME",
	baccaratNew: "I'M NEW",

	streamingQuestion: 'Do you have streaming experience?',
	streamingALot: "I'VE STREAMED A LOT",
	streamingSome: "I'VE STREAMED SOME",
	streamingNew: "I'M NEW TO STREAMING",
	streamingReadyToLearn: "I'M READY TO LEARN",
	streamingMaybeLater: 'MAYBE LATER',

	equityQuestion: 'How much do you know about equity?',
	equityOwn: 'I CURRENTLY OWN EQUITY',
	equityUnderstand: 'I UNDERSTAND HOW EQUITY WORKS',
	equityInterested: 'INTERESTED IN EARNING EQUITY',
	equitySelfManaged: 'I MANAGE IT MYSELF',
	equityManagedByOther: 'SOMEONE ELSE DOES IT FOR ME',

	followersQuestion: 'How many Active Followers do you have?',
	followers100k: '100,000+',
	followers50k: '50,000+',
	followers25k: '25,000+',
	followers10k: '10,000+',
	followersUnder10k: 'Under 10,000',

	answerRequired: 'Choose an answer to continue.',
	subAnswerRequired: 'Choose one of the options below your answer to continue.',
};

/** The radio keyboard pattern: these keys move the choice to the next or previous option. */
const NEXT_OPTION_KEYS = Object.freeze( [ 'ArrowDown', 'ArrowRight' ] );
const PREVIOUS_OPTION_KEYS = Object.freeze( [ 'ArrowUp', 'ArrowLeft' ] );

const GROUP = Object.freeze( { main: 'main', sub: 'sub' } );

/**
 * How long a completing pick stays on screen before the screen moves on: long enough to see
 * the answer fill in, short enough not to feel like a wait.
 */
export const PICK_ADVANCE_DELAY_MS = 500;

/** The browser's timers. installModules() passes a plug-in only the api, so install() defaults to these. */
const BROWSER_TIMERS = Object.freeze( {
	setTimeout: ( callback, ms ) => window.setTimeout( callback, ms ),
	clearTimeout: ( id ) => window.clearTimeout( id ),
} );

/**
 * The four questions as data: the stored field, the text, and each option's stored value.
 * An option with `reveals` opens a required sub-question stored in its own field.
 */
export const QUESTIONS = Object.freeze( [
	Object.freeze( {
		id: 'baccarat_experience',
		field: 'baccarat_experience',
		questionKey: 'baccaratQuestion',
		options: Object.freeze( [
			Object.freeze( { value: 'played_a_lot', labelKey: 'baccaratPlayedALot' } ),
			Object.freeze( { value: 'played_some', labelKey: 'baccaratPlayedSome' } ),
			Object.freeze( { value: 'new_player', labelKey: 'baccaratNew' } ),
		] ),
	} ),
	Object.freeze( {
		id: 'streaming_experience',
		field: 'streaming_experience',
		questionKey: 'streamingQuestion',
		options: Object.freeze( [
			Object.freeze( { value: 'streamed_a_lot', labelKey: 'streamingALot' } ),
			Object.freeze( { value: 'streamed_some', labelKey: 'streamingSome' } ),
			Object.freeze( {
				value: 'new_to_streaming',
				labelKey: 'streamingNew',
				reveals: Object.freeze( {
					field: 'streaming_learning_intent',
					options: Object.freeze( [
						Object.freeze( { value: 'new_ready_to_learn', labelKey: 'streamingReadyToLearn' } ),
						Object.freeze( { value: 'new_maybe_later', labelKey: 'streamingMaybeLater' } ),
					] ),
				} ),
			} ),
		] ),
	} ),
	Object.freeze( {
		id: 'equity_knowledge',
		field: 'equity_knowledge',
		questionKey: 'equityQuestion',
		options: Object.freeze( [
			Object.freeze( {
				value: 'owns_equity',
				labelKey: 'equityOwn',
				reveals: Object.freeze( {
					field: 'equity_management',
					options: Object.freeze( [
						Object.freeze( { value: 'owns_self_managed', labelKey: 'equitySelfManaged' } ),
						Object.freeze( { value: 'owns_managed_by_other', labelKey: 'equityManagedByOther' } ),
					] ),
				} ),
			} ),
			Object.freeze( { value: 'understands_equity', labelKey: 'equityUnderstand' } ),
			Object.freeze( { value: 'interested_in_equity', labelKey: 'equityInterested' } ),
		] ),
	} ),
	Object.freeze( {
		id: 'active_followers',
		field: 'active_follower_count',
		questionKey: 'followersQuestion',
		options: Object.freeze( [
			Object.freeze( { value: '100k_plus', labelKey: 'followers100k' } ),
			Object.freeze( { value: '50k_plus', labelKey: 'followers50k' } ),
			Object.freeze( { value: '25k_plus', labelKey: 'followers25k' } ),
			Object.freeze( { value: '10k_plus', labelKey: 'followers10k' } ),
			Object.freeze( { value: 'under_10k', labelKey: 'followersUnder10k' } ),
		] ),
	} ),
] );

/**
 * The answer state of one question after a pick. Pure.
 *
 * @param {Object} question One of QUESTIONS.
 * @param {Object} answer   { main, sub } — the current answer ('' for none).
 * @param {string} value    The option picked on the main question.
 * @return {Object} { answer, save }: the new answer, and the partial to store.
 */
export function pickMain( question, answer, value ) {
	const option = question.options.find( ( candidate ) => candidate.value === value );
	if ( ! option ) {
		return { answer, save: null };
	}
	const keepsSub = Boolean( option.reveals ) && answer.main === value;
	const next = { main: value, sub: keepsSub ? answer.sub : '' };
	// Re-picking the current answer changes nothing, so it is not stored again.
	if ( next.main === answer.main && next.sub === answer.sub ) {
		return { answer, save: null };
	}
	const save = { [ question.field ]: value };
	const subField = revealedField( question );
	if ( subField && '' !== answer.sub ) {
		save[ subField ] = null;
	}
	return { answer: next, save };
}

/**
 * The answer state after a pick on the revealed sub-question. Pure.
 *
 * @param {Object} question One of QUESTIONS.
 * @param {Object} answer   { main, sub }.
 * @param {string} value    The sub-option picked.
 * @return {Object} { answer, save }.
 */
export function pickSub( question, answer, value ) {
	const option = question.options.find( ( candidate ) => candidate.value === answer.main );
	const valid = option && option.reveals && option.reveals.options.some( ( candidate ) => candidate.value === value );
	if ( ! valid ) {
		return { answer, save: null };
	}
	if ( value === answer.sub ) {
		return { answer, save: null };
	}
	return { answer: { main: answer.main, sub: value }, save: { [ option.reveals.field ]: value } };
}

/**
 * What is missing for the question to be complete. Pure.
 *
 * @param {Object} question
 * @param {Object} answer   { main, sub }.
 * @return {string} '' when complete, else the string-table key of the message to show.
 */
export function missingAnswer( question, answer ) {
	const option = question.options.find( ( candidate ) => candidate.value === answer.main );
	if ( ! option ) {
		return 'answerRequired';
	}
	if ( option.reveals && ! option.reveals.options.some( ( candidate ) => candidate.value === answer.sub ) ) {
		return 'subAnswerRequired';
	}
	return '';
}

function revealedField( question ) {
	const revealing = question.options.find( ( option ) => option.reveals );
	return revealing ? revealing.reveals.field : '';
}

/**
 * The answer stored for a question, for a returning Influencer.
 *
 * @param {Object} question
 * @param {Object} answers  The store's answers.
 * @return {Object} { main, sub }.
 */
export function storedAnswer( question, answers ) {
	const stored = answers || {};
	const main = 'string' === typeof stored[ question.field ] ? stored[ question.field ] : '';
	const subField = revealedField( question );
	const sub = subField && 'string' === typeof stored[ subField ] ? stored[ subField ] : '';
	return { main, sub };
}

/**
 * The id of the label of an option that reveals sub-options; the sub group is named by it.
 *
 * @param {string} headingId The question heading's id.
 * @param {Object} option    The revealing option.
 * @return {string} The id.
 */
function revealingLabelId( headingId, option ) {
	return headingId + '-' + option.value;
}

function optionButton( { document, text, option, group, headingId } ) {
	const button = document.createElement( 'button' );
	button.setAttribute( 'type', 'button' );
	button.setAttribute( 'role', 'radio' );
	button.setAttribute( 'aria-checked', 'false' );
	button.setAttribute( 'data-group', group );
	button.setAttribute( 'data-value', option.value );
	button.className = 'ihq-onb-q__option';
	const label = text( 'span', option.labelKey, 'ihq-onb-q__label' );
	if ( option.reveals ) {
		label.setAttribute( 'id', revealingLabelId( headingId, option ) );
	}
	button.append( label );
	return button;
}

function optionGroup( { document, text, options, group, labelledBy, headingId } ) {
	const element = document.createElement( 'div' );
	element.setAttribute( 'role', 'radiogroup' );
	element.setAttribute( 'aria-labelledby', labelledBy );
	element.className = 'ihq-onb-q__options';
	element.setAttribute( 'data-group', group );
	options.forEach( ( option ) => element.append( optionButton( { document, text, option, group, headingId } ) ) );
	return element;
}

/**
 * Marks the selected option of a group, and makes it the group's one Tab stop (the first
 * option when none is selected), as the radio pattern expects.
 *
 * @param {Element} group    A radiogroup from optionGroup().
 * @param {string}  selected The selected value ('' for none).
 */
function paintGroup( group, selected ) {
	const buttons = Array.from( group.querySelectorAll( 'button' ) );
	const hasSelected = buttons.some( ( button ) => button.getAttribute( 'data-value' ) === selected );
	buttons.forEach( ( button, index ) => {
		const isChecked = button.getAttribute( 'data-value' ) === selected;
		const isTabStop = isChecked || ( ! hasSelected && 0 === index );
		button.setAttribute( 'aria-checked', isChecked ? 'true' : 'false' );
		button.setAttribute( 'tabindex', isTabStop ? '0' : '-1' );
	} );
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

/**
 * Wires a group's clicks and arrow keys to pick(value). Arrows wrap around and move focus
 * with the choice.
 *
 * @param {Element}  group A radiogroup from optionGroup().
 * @param {Function} pick  Called with the picked value.
 */
function listenToGroup( group, pick ) {
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
		const buttons = Array.from( group.querySelectorAll( 'button' ) );
		const index = ( buttons.indexOf( current ) + step + buttons.length ) % buttons.length;
		const target = buttons[ index ];
		pick( target.getAttribute( 'data-value' ) );
		target.focus();
	} );
}

/**
 * The renderer of one question screen.
 *
 * @param {Object} question One of QUESTIONS.
 * @param {Object} timers   { setTimeout, clearTimeout }, for the pause before advancing.
 * @return {Function} An engine render().
 */
function renderQuestion( question, timers ) {
	return function( { container, api, text, advance } ) {
		const document = container.ownerDocument;
		const headingId = 'ihq-onb-q-' + question.id;
		const heading = text( 'h2', question.questionKey, 'ihq-onb-q__question' );
		heading.setAttribute( 'id', headingId );
		container.append( heading );

		const main = optionGroup( { document, text, options: question.options, group: GROUP.main, labelledBy: headingId, headingId } );
		container.append( main );

		const revealing = question.options.find( ( option ) => option.reveals );
		let sub = null;
		if ( revealing ) {
			// Named by the option that reveals it, not the question, so it is announced as its follow-up.
			sub = optionGroup( { document, text, options: revealing.reveals.options, group: GROUP.sub, labelledBy: revealingLabelId( headingId, revealing ), headingId } );
			sub.classList.add( 'ihq-onb-q__options--sub' );
			container.append( sub );
		}

		const error = document.createElement( 'p' );
		error.className = 'ihq-onb__error';
		error.setAttribute( 'role', 'alert' );
		error.hidden = true;
		container.append( error );

		let answer = storedAnswer( question, api.state.answers );
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

		function paint() {
			paintGroup( main, answer.main );
			if ( ! sub ) {
				return;
			}
			sub.hidden = answer.main !== revealing.value;
			paintGroup( sub, answer.sub );
		}

		/**
		 * Takes a pick: stores a changed answer, and (re)starts the pause before advancing
		 * when the answer is complete, or cancels it when the pick revealed the follow-up.
		 *
		 * @param {Object} result From pickMain() or pickSub().
		 */
		function apply( result ) {
			const isComplete = '' === missingAnswer( question, result.answer );
			if ( ! result.save ) {
				// Re-picking the current answer keeps a pause already running. With none running
				// (a returning Influencer's saved answer) it is the way on, as a pick.
				if ( isComplete && null === advanceTimer ) {
					scheduleAdvance();
				}
				return;
			}
			answer = result.answer;
			error.hidden = true;
			paint();
			api.save( { answers: result.save } );
			if ( isComplete ) {
				scheduleAdvance();
				return;
			}
			cancelAdvance();
		}

		listenToGroup( main, ( value ) => apply( pickMain( question, answer, value ) ) );
		if ( sub ) {
			listenToGroup( sub, ( value ) => apply( pickSub( question, answer, value ) ) );
		}
		paint();

		return {
			isValid: () => '' === missingAnswer( question, answer ),
			showValidation() {
				const key = missingAnswer( question, answer );
				error.setAttribute( 'data-onb-string', key );
				error.textContent = api.strings.t( api.getLocale(), key );
				error.hidden = false;
			},
			destroy: cancelAdvance,
		};
	};
}

/**
 * The screen definitions this module registers.
 *
 * @param {Object} deps
 * @param {Object} deps.advanceModes The engine's ADVANCE_MODES (api.screens.ADVANCE_MODES).
 * @param {Object} deps.timers       { setTimeout, clearTimeout }.
 * @return {Object[]} Engine screen definitions.
 */
export function questionScreens( { advanceModes, timers } ) {
	return QUESTIONS.map( ( question ) => ( {
		id: question.id,
		advanceMode: advanceModes.PICK,
		render: renderQuestion( question, timers ),
	} ) );
}

/**
 * Adds the English copy and registers the screens.
 *
 * @param {Object} api      The onboarding API.
 * @param {Object} [timers] { setTimeout, clearTimeout }; the browser's when left out.
 */
export function install( api, timers = BROWSER_TIMERS ) {
	api.strings.add( 'en', QUESTION_STRINGS_EN );
	const definitions = questionScreens( { advanceModes: api.screens.ADVANCE_MODES, timers } );
	definitions.forEach( ( definition ) => api.screens.register( definition ) );
}
