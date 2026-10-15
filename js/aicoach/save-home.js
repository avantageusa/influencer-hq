/**
 * SAVE IHQ TO HOME SCREEN (ENGR-7575, PO-3063 T13, PO-3222).
 *
 * Every element with data-ihq-save-home (the onboarding frame's button, the closing screen's,
 * any other) gets the same behaviour:
 * - Android Chrome (the browser fired beforeinstallprompt): the button opens the native install
 *   prompt.
 * - iOS Safari and every other browser: the button opens a short sheet: Share, then Add to Home
 *   Screen (or the browser menu, outside iOS).
 * - The buttons hide while the portal runs as an installed app (standalone display mode), and
 *   after the Influencer dismisses the prompt or the sheet on this device (remembered in
 *   localStorage). Buttons added later (a screen rendered afterwards) are covered too: clicks are
 *   handled on the document, and hiding is a class on <html>.
 *
 * Everything the browser provides is passed in; js/aicoach/save-home-page.js wires it to the page.
 */

/** localStorage key remembering a dismissal on this device. */
export const DISMISSED_KEY = 'ihq_save_home_dismissed';

/** Class on <html> that hides every Save button. */
export const HIDDEN_CLASS = 'ihq-save-home-hidden';

/** Selector of the buttons. */
export const BUTTON_SELECTOR = '[data-ihq-save-home]';

/** The sheet's text. The portal outside onboarding is English only. */
export const SHEET_TEXT = Object.freeze( {
	title: 'Save IHQ to your Home Screen',
	ios: 'Tap the Share button, then Add to Home Screen.',
	other: "Open your browser's menu, then choose Add to Home Screen (or Install app).",
	close: 'Close',
} );

/**
 * Whether the portal is running as an installed app.
 *
 * @param {Object}   env
 * @param {Function} env.matchMedia window.matchMedia.
 * @param {Object}   env.navigator  window.navigator (iOS sets navigator.standalone).
 * @return {boolean} True in standalone display mode.
 */
export function isStandalone( { matchMedia, navigator } ) {
	if ( navigator && true === navigator.standalone ) {
		return true;
	}
	return Boolean( matchMedia && matchMedia( '(display-mode: standalone)' ).matches );
}

/**
 * Whether the browser is on iOS or iPadOS, where Add to Home Screen is in the Share sheet. iPadOS
 * reports itself as a Mac, so a touch-capable "Mac" counts as iOS.
 *
 * @param {Object} navigator window.navigator.
 * @return {boolean} True on iPhone, iPad or iPod.
 */
export function isIos( navigator ) {
	const userAgent = String( ( navigator && navigator.userAgent ) || '' );
	if ( /iPhone|iPad|iPod/.test( userAgent ) ) {
		return true;
	}
	return /Macintosh/.test( userAgent ) && Number( navigator.maxTouchPoints ) > 1;
}

/**
 * What the button does.
 *
 * @param {Object}  state
 * @param {boolean} state.hasPrompt The browser offered its install prompt.
 * @param {boolean} state.ios
 * @return {string} 'prompt', 'sheet-ios' or 'sheet-other'.
 */
export function chooseAction( { hasPrompt, ios } ) {
	if ( hasPrompt ) {
		return 'prompt';
	}
	return ios ? 'sheet-ios' : 'sheet-other';
}

function readDismissed( storage ) {
	try {
		return '1' === storage.getItem( DISMISSED_KEY );
	} catch ( error ) {
		return false;
	}
}

function writeDismissed( storage ) {
	try {
		storage.setItem( DISMISSED_KEY, '1' );
	} catch ( error ) {
		// Private mode or storage blocked: the buttons hide for this page view only.
	}
}

/**
 * @param {Object}   deps
 * @param {Document} deps.document
 * @param {Window}   deps.window   addEventListener, matchMedia, navigator.
 * @param {Storage}  deps.storage  localStorage (may throw).
 * @return {Object} { handleClick, hide, isHidden, sheet }.
 */
export function createSaveHome( { document, window, storage } ) {
	let deferredPrompt = null;
	let sheet = null;
	const root = document.documentElement;

	function hide() {
		root.classList.add( HIDDEN_CLASS );
	}

	function dismiss() {
		writeDismissed( storage );
		hide();
	}

	function closeSheet() {
		if ( sheet ) {
			sheet.remove();
			sheet = null;
		}
		dismiss();
	}

	function openSheet( ios ) {
		if ( sheet ) {
			return;
		}
		sheet = document.createElement( 'div' );
		sheet.className = 'ihq-save-home-sheet';
		sheet.setAttribute( 'role', 'dialog' );
		sheet.setAttribute( 'aria-modal', 'true' );
		sheet.setAttribute( 'aria-labelledby', 'ihq-save-home-title' );
		const title = document.createElement( 'h2' );
		title.setAttribute( 'id', 'ihq-save-home-title' );
		title.textContent = SHEET_TEXT.title;
		const how = document.createElement( 'p' );
		how.textContent = ios ? SHEET_TEXT.ios : SHEET_TEXT.other;
		const close = document.createElement( 'button' );
		close.setAttribute( 'type', 'button' );
		close.textContent = SHEET_TEXT.close;
		close.addEventListener( 'click', closeSheet );
		sheet.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' === event.key ) {
				closeSheet();
			}
		} );
		sheet.append( title, how, close );
		document.body.append( sheet );
		close.focus();
	}

	async function showPrompt() {
		const prompt = deferredPrompt;
		deferredPrompt = null;
		prompt.prompt();
		const choice = await prompt.userChoice;
		if ( choice && 'accepted' === choice.outcome ) {
			hide();
			return;
		}
		dismiss();
	}

	/**
	 * The click on a Save button.
	 *
	 * @return {Promise<string>} The action taken.
	 */
	async function handleClick() {
		const action = chooseAction( { hasPrompt: Boolean( deferredPrompt ), ios: isIos( window.navigator ) } );
		if ( 'prompt' === action ) {
			await showPrompt();
			return action;
		}
		openSheet( 'sheet-ios' === action );
		return action;
	}

	window.addEventListener( 'beforeinstallprompt', ( event ) => {
		event.preventDefault();
		deferredPrompt = event;
	} );
	window.addEventListener( 'appinstalled', hide );
	document.addEventListener( 'click', ( event ) => {
		const button = event.target && event.target.closest ? event.target.closest( BUTTON_SELECTOR ) : null;
		if ( button ) {
			event.preventDefault();
			handleClick();
		}
	} );

	if ( isStandalone( { matchMedia: window.matchMedia, navigator: window.navigator } ) || readDismissed( storage ) ) {
		hide();
	}

	return {
		handleClick,
		hide,
		isHidden: () => root.classList.contains( HIDDEN_CLASS ),
		sheet: () => sheet,
	};
}
