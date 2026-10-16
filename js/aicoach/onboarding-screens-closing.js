/**
 * Onboarding screen 16, the closing screen (ENGR-7571, PO-3063 T9, PO-3217).
 *
 * Sami's avatar enlarged and centred, her closing message (available around the clock, and a
 * reminder to save the portal address), then CONTINUE TO IHQ PORTAL. The current design has no
 * closing frame yet (OQ5): the content is the old frame's (IHQ file node 400:6277) in the current
 * header, banner and buttons.
 *
 * The screen is MANUAL: the engine never moves on by itself, and its own button ends the flow.
 * CONTINUE TO IHQ PORTAL marks onboarding complete through the engine (api.complete(), the
 * completion route of ENGR-7564) and removes the layer, leaving the Influencer at the top of the
 * Coach page.
 *
 * SAVE IHQ TO HOME SCREEN is the frame's own button (data-ihq-save-home,
 * template-parts/portal-onboarding.php) and stays where the shell puts it, in the sticky footer,
 * as on every other screen (design decision 1). This screen does not touch it.
 *
 * A plug-in module: the entry installs it, and it registers its screen with the engine.
 */

/** English copy (PO-3217). */
export const CLOSING_STRINGS_EN = {
	closingAlwaysHere: "I'll always be here for you 24 hours a day, 7 days a week.",
	closingSaveReminder: 'If you have not saved the IHQ portal address already, make sure to do that now.',
	closingContinue: 'CONTINUE TO IHQ PORTAL',
	closingFailed: 'Something went wrong. Please try again.',
};

/** The banner portrait, enlarged here. */
const PORTRAIT_SELECTOR = '.ihq-onb__portrait';

function frameElement( root, selector ) {
	if ( ! root ) {
		return null;
	}
	return root.querySelector( selector );
}

function renderAvatar( { container, root } ) {
	const portrait = frameElement( root, PORTRAIT_SELECTOR );
	const source = portrait ? portrait.getAttribute( 'src' ) : '';
	// Without a portrait an <img src=""> would show as an empty gold ring.
	if ( ! source ) {
		return;
	}
	const avatar = container.ownerDocument.createElement( 'img' );
	avatar.className = 'ihq-onb-closing__avatar';
	avatar.setAttribute( 'src', source );
	avatar.setAttribute( 'alt', '' );
	container.append( avatar );
}

function renderClosing( { container, api, text } ) {
	const document = container.ownerDocument;

	renderAvatar( { container, root: api.shell.root } );

	const message = document.createElement( 'div' );
	message.className = 'ihq-onb-closing__message';
	message.append( text( 'p', 'closingAlwaysHere' ), text( 'p', 'closingSaveReminder' ) );
	container.append( message );

	const actions = document.createElement( 'div' );
	actions.className = 'ihq-onb-closing__actions';
	const proceed = text( 'button', 'closingContinue', 'ihq-onb__button ihq-onb-closing__proceed' );
	proceed.setAttribute( 'type', 'button' );
	proceed.setAttribute( 'data-onb', 'complete' );
	const failed = text( 'p', 'closingFailed', 'ihq-onb__error' );
	failed.setAttribute( 'role', 'alert' );
	failed.hidden = true;
	actions.append( proceed, failed );
	container.append( actions );

	proceed.addEventListener( 'click', async () => {
		proceed.disabled = true;
		failed.hidden = true;
		const done = await api.complete();
		if ( done ) {
			return;
		}
		proceed.disabled = false;
		failed.hidden = false;
	} );
}

/**
 * Adds the English copy and registers screen 16.
 *
 * @param {Object} api The onboarding API.
 */
export function install( api ) {
	api.strings.add( 'en', CLOSING_STRINGS_EN );
	api.screens.register( { id: 'closing', advanceMode: api.screens.ADVANCE_MODES.MANUAL, render: renderClosing } );
}
