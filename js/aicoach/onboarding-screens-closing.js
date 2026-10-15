/**
 * Onboarding screen 16, the closing screen (ENGR-7571, PO-3063 T9, PO-3217).
 *
 * Sami's avatar enlarged and centred, her closing message (available around the clock, and a
 * reminder to save the portal address), then CONTINUE TO IHQ PORTAL and SAVE IHQ TO HOME
 * SCREEN stacked beneath. The current design has no closing frame yet (OQ5): the content is
 * the old frame's (IHQ file node 400:6277) in the current header, banner and buttons.
 *
 * CONTINUE marks onboarding complete through the engine (api.complete(), the completion route
 * of ENGR-7564) and removes the layer, leaving the Influencer at the top of the Coach page.
 * SAVE carries data-ihq-save-home, so the install behaviour (ENGR-7575) handles it and the
 * Influencer stays on this screen. The frame's own Save button is hidden here, so the screen
 * does not show two.
 *
 * A plug-in module: the entry installs it, and it registers its screen with the engine.
 */

/** English copy (PO-3217). */
export const CLOSING_STRINGS_EN = {
	closingAlwaysHere: "I'll always be here for you 24 hours a day, 7 days a week.",
	closingSaveReminder: 'If you have not saved the IHQ portal address already, make sure to do that now.',
	closingContinue: 'CONTINUE TO IHQ PORTAL',
	closingSave: 'SAVE IHQ TO HOME SCREEN',
	closingFailed: 'Something went wrong. Please try again.',
};

function renderClosing( { container, api, text } ) {
	const document = container.ownerDocument;
	const frameCta = api.shell.root ? api.shell.root.querySelector( '.ihq-onb__cta' ) : null;
	if ( frameCta ) {
		frameCta.hidden = true;
	}

	const avatar = document.createElement( 'img' );
	avatar.className = 'ihq-onb-closing__avatar';
	const portrait = api.shell.root ? api.shell.root.querySelector( '.ihq-onb__portrait' ) : null;
	avatar.setAttribute( 'src', portrait ? portrait.getAttribute( 'src' ) : '' );
	avatar.setAttribute( 'alt', '' );
	container.append( avatar );

	const message = document.createElement( 'div' );
	message.className = 'ihq-onb-closing__message';
	message.append( text( 'p', 'closingAlwaysHere' ), text( 'p', 'closingSaveReminder' ) );
	container.append( message );

	const actions = document.createElement( 'div' );
	actions.className = 'ihq-onb-closing__actions';
	const proceed = text( 'button', 'closingContinue', 'ihq-onb__button ihq-onb__button--continue' );
	proceed.setAttribute( 'type', 'button' );
	proceed.setAttribute( 'data-onb', 'complete' );
	const save = text( 'button', 'closingSave', 'ihq-onb__button ihq-onb__button--save' );
	save.setAttribute( 'type', 'button' );
	save.setAttribute( 'data-ihq-save-home', '' );
	const failed = text( 'p', 'closingFailed', 'ihq-onb__error' );
	failed.setAttribute( 'role', 'alert' );
	failed.hidden = true;
	actions.append( proceed, save, failed );
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

	return {
		destroy() {
			if ( frameCta ) {
				frameCta.hidden = false;
			}
		},
	};
}

/**
 * Adds the English copy and registers screen 16.
 *
 * @param {Object} api The onboarding API.
 */
export function install( api ) {
	api.strings.add( 'en', CLOSING_STRINGS_EN );
	api.screens.register( { id: 'closing', hideContinue: true, render: renderClosing } );
}
