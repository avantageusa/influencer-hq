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
 * so a drop-out's answers are kept and reach Braze (ENGR-7573). A screen does not advance
 * until it has a complete answer.
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

/** The diamonds shown next to a baccarat answer, as its XP level. */
const MAX_DIAMONDS = 3;

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
			Object.freeze( { value: 'played_a_lot', labelKey: 'baccaratPlayedALot', diamonds: 3 } ),
			Object.freeze( { value: 'played_some', labelKey: 'baccaratPlayedSome', diamonds: 2 } ),
			Object.freeze( { value: 'new_player', labelKey: 'baccaratNew', diamonds: 1 } ),
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
	const save = { [ question.field ]: value };
	const subField = revealedField( question );
	const keepsSub = Boolean( option.reveals ) && answer.main === value;
	if ( subField && ! keepsSub && '' !== answer.sub ) {
		save[ subField ] = null;
	}
	return { answer: { main: value, sub: keepsSub ? answer.sub : '' }, save };
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

function optionButton( { document, text, option, group } ) {
	const button = document.createElement( 'button' );
	button.setAttribute( 'type', 'button' );
	button.setAttribute( 'role', 'radio' );
	button.setAttribute( 'aria-checked', 'false' );
	button.setAttribute( 'data-group', group );
	button.setAttribute( 'data-value', option.value );
	button.className = 'ihq-onb-q__option';
	button.append( text( 'span', option.labelKey, 'ihq-onb-q__label' ) );
	if ( option.diamonds ) {
		const level = document.createElement( 'span' );
		level.className = 'ihq-onb-q__diamonds';
		level.setAttribute( 'aria-hidden', 'true' );
		for ( let index = 0; index < MAX_DIAMONDS; index++ ) {
			const diamond = document.createElement( 'span' );
			diamond.className = index < option.diamonds ? 'ihq-onb-q__diamond is-on' : 'ihq-onb-q__diamond';
			diamond.textContent = '◆';
			level.append( diamond );
		}
		button.append( level );
	}
	return button;
}

function optionGroup( { document, text, options, group, labelledBy } ) {
	const element = document.createElement( 'div' );
	element.setAttribute( 'role', 'radiogroup' );
	element.setAttribute( 'aria-labelledby', labelledBy );
	element.className = 'ihq-onb-q__options';
	element.setAttribute( 'data-group', group );
	options.forEach( ( option ) => element.append( optionButton( { document, text, option, group } ) ) );
	return element;
}

/**
 * The renderer of one question screen.
 *
 * @param {Object} question One of QUESTIONS.
 * @return {Function} An engine render().
 */
function renderQuestion( question ) {
	return function( { container, api, text } ) {
		const document = container.ownerDocument;
		const headingId = 'ihq-onb-q-' + question.id;
		const heading = text( 'h2', question.questionKey, 'ihq-onb-q__question' );
		heading.setAttribute( 'id', headingId );
		container.append( heading );

		const main = optionGroup( { document, text, options: question.options, group: 'main', labelledBy: headingId } );
		container.append( main );

		const revealing = question.options.find( ( option ) => option.reveals );
		const sub = revealing ? optionGroup( { document, text, options: revealing.reveals.options, group: 'sub', labelledBy: headingId } ) : null;
		if ( sub ) {
			sub.classList.add( 'ihq-onb-q__options--sub' );
			container.append( sub );
		}

		const error = document.createElement( 'p' );
		error.className = 'ihq-onb__error';
		error.setAttribute( 'role', 'alert' );
		error.hidden = true;
		container.append( error );

		let answer = storedAnswer( question, api.state.answers );

		function paint() {
			main.querySelectorAll( 'button' ).forEach( ( button ) => {
				button.setAttribute( 'aria-checked', button.getAttribute( 'data-value' ) === answer.main ? 'true' : 'false' );
			} );
			if ( ! sub ) {
				return;
			}
			sub.hidden = answer.main !== revealing.value;
			sub.querySelectorAll( 'button' ).forEach( ( button ) => {
				button.setAttribute( 'aria-checked', button.getAttribute( 'data-value' ) === answer.sub ? 'true' : 'false' );
			} );
		}

		function apply( result ) {
			if ( ! result.save ) {
				return;
			}
			answer = result.answer;
			error.hidden = true;
			paint();
			api.save( { answers: result.save } );
		}

		main.addEventListener( 'click', ( event ) => {
			const button = event.target.closest( 'button' );
			if ( button ) {
				apply( pickMain( question, answer, button.getAttribute( 'data-value' ) ) );
			}
		} );
		if ( sub ) {
			sub.addEventListener( 'click', ( event ) => {
				const button = event.target.closest( 'button' );
				if ( button ) {
					apply( pickSub( question, answer, button.getAttribute( 'data-value' ) ) );
				}
			} );
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
		};
	};
}

/**
 * The screen definitions this module registers.
 *
 * @return {Object[]} Engine screen definitions.
 */
export function questionScreens() {
	return QUESTIONS.map( ( question ) => ( { id: question.id, render: renderQuestion( question ) } ) );
}

/**
 * Adds the English copy and registers the screens.
 *
 * @param {Object} api The onboarding API.
 */
export function install( api ) {
	api.strings.add( 'en', QUESTION_STRINGS_EN );
	questionScreens().forEach( ( definition ) => api.screens.register( definition ) );
}
