/**
 * The order of the portal onboarding screens and how the flow moves through them
 * (ENGR-7566, PO-3063 T4). Pure: no DOM.
 *
 * The flow only moves forward, one screen at a time: there is no back, no skip and no
 * progress indicator (PO-3218). The one way back is the edit-return branch on the post
 * action screen: "Let's edit this together now" returns to the draft posts, with the drafts
 * kept (PO-3213 Scenario 23).
 *
 * The stage ids are the ones the account store keeps (inc/portal-onboarding-progress.php,
 * ihq_portal_onboarding_stages()); keep both lists the same.
 *
 * A screen whose module is not installed (or failed to load) is passed over, so the flow
 * still reaches the closing screen.
 */

/** The sixteen screens, in order. */
export const SEQUENCE = Object.freeze( [
	'game_of_kings',
	'modern_era',
	'baccarat_experience',
	'streaming_experience',
	'equity_knowledge',
	'social_platforms',
	'world_championship',
	'world_tour',
	'classic',
	'equity',
	'how_this_works',
	'active_followers',
	'equity_example',
	'draft_posts',
	'post_action',
	'closing',
] );

/**
 * The permitted backward steps: from a screen, with a choice, to an earlier screen.
 * Kept as data so the appointment branch (T16) can be added the same way.
 */
export const BRANCHES = Object.freeze( [
	Object.freeze( { from: 'post_action', choice: 'edit_together', to: 'draft_posts' } ),
] );

/**
 * The first screen that can be shown, from a given one on.
 *
 * @param {string[]} order
 * @param {number}   fromIndex
 * @param {Function} isAvailable ( stage ) => boolean.
 * @return {string|null} The stage, or null when none from there on is available.
 */
function firstAvailableFrom( order, fromIndex, isAvailable ) {
	for ( let index = Math.max( 0, fromIndex ); index < order.length; index++ ) {
		if ( isAvailable( order[ index ] ) ) {
			return order[ index ];
		}
	}
	return null;
}

/**
 * Where onboarding opens: the saved stage when there is one, else screen 1. A saved stage
 * that is unknown, or whose screen is not installed, opens the next screen that is.
 *
 * @param {Object}   options
 * @param {string}   options.saved       The stored stage ('' for a new user).
 * @param {Function} options.isAvailable ( stage ) => boolean.
 * @param {string[]} [options.order]
 * @return {string|null} The stage to open, or null when there is nothing to show.
 */
export function startStage( { saved, isAvailable, order = SEQUENCE } ) {
	const index = order.indexOf( saved );
	return firstAvailableFrom( order, -1 === index ? 0 : index, isAvailable );
}

/**
 * The screen after the current one.
 *
 * @param {Object}   options
 * @param {string}   options.current     The stage being left.
 * @param {string}   [options.choice]    What the screen chose, for a branch.
 * @param {Function} options.isAvailable ( stage ) => boolean.
 * @param {string[]} [options.order]
 * @param {Array}    [options.branches]
 * @return {string|null} The next stage, or null after the last screen.
 */
export function nextStage( { current, choice, isAvailable, order = SEQUENCE, branches = BRANCHES } ) {
	const branch = branches.find( ( entry ) => entry.from === current && entry.choice === choice );
	if ( branch && isAvailable( branch.to ) ) {
		return branch.to;
	}
	const index = order.indexOf( current );
	if ( -1 === index ) {
		return null;
	}
	return firstAvailableFrom( order, index + 1, isAvailable );
}
