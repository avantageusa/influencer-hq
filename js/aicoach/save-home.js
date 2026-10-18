/**
 * SAVE IHQ TO HOME SCREEN (ENGR-7575, PO-3063 T13, PO-3222).
 *
 * Every element with data-ihq-save-home (the onboarding frame's button, the closing screen's,
 * any other) gets the same behaviour:
 * - Android Chrome (the browser fired beforeinstallprompt): the button opens the native install
 *   prompt.
 * - iOS Safari and every other browser: the button opens a short sheet: Share, then Add to Home
 *   Screen (or the browser menu, outside iOS).
 * - The buttons hide while the portal runs as an installed app (standalone display mode), after
 *   the Influencer installs it from the prompt, and after they dismiss the prompt or the sheet on
 *   this device (installs and dismissals are remembered in localStorage). Buttons added later (a
 *   screen rendered afterwards) are covered too: clicks are handled on the document, and hiding is
 *   a class on <html>.
 *
 * This module renders no button. The buttons carrying data-ihq-save-home come from the onboarding
 * stories: the frame's button from ENGR-7565 (template-parts/portal-onboarding.php) and the closing
 * screen's from ENGR-7571 (js/aicoach/onboarding-screens-closing.js). Until those merge, nothing on
 * the page uses this behaviour.
 *
 * Everything the browser provides is passed in; js/aicoach/save-home-page.js calls mountSaveHome()
 * with the page's document and window.
 */

/** localStorage key remembering a dismissal on this device. */
export const DISMISSED_KEY = 'ihq_save_home_dismissed';

/**
 * localStorage key remembering an install from the prompt on this device. Chrome stops firing
 * beforeinstallprompt once the app is installed, so without it a later visit in a browser tab would
 * offer the browser-menu instructions to someone who already installed the app.
 */
export const INSTALLED_KEY = 'ihq_save_home_installed';

/** The value stored under DISMISSED_KEY and INSTALLED_KEY. */
const STORED_FLAG = '1';

/** Class on <html> that hides every Save button. */
export const HIDDEN_CLASS = 'ihq-save-home-hidden';

/** Selector of the buttons. */
export const BUTTON_SELECTOR = '[data-ihq-save-home]';

/** What a click on a Save button does. */
export const SAVE_HOME_ACTION = Object.freeze( {
	prompt: 'prompt',
	sheetIos: 'sheet-ios',
	sheetOther: 'sheet-other',
} );

/** The prompt's userChoice outcome when the Influencer installs. */
const OUTCOME_ACCEPTED = 'accepted';

/** The key that closes the sheet. */
const CLOSE_KEY = 'Escape';

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
 * @return {string} One of SAVE_HOME_ACTION.
 */
export function chooseAction( { hasPrompt, ios } ) {
	if ( hasPrompt ) {
		return SAVE_HOME_ACTION.prompt;
	}
	return ios ? SAVE_HOME_ACTION.sheetIos : SAVE_HOME_ACTION.sheetOther;
}

function readFlag( storage, key ) {
	try {
		return STORED_FLAG === storage.getItem( key );
	} catch ( error ) {
		return false;
	}
}

function writeFlag( storage, key ) {
	try {
		storage.setItem( key, STORED_FLAG );
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

	function remember( key ) {
		writeFlag( storage, key );
		hide();
	}

	function onDocumentKeydown( event ) {
		if ( CLOSE_KEY === event.key ) {
			closeSheet();
		}
	}

	// Focus is not moved back to the Save button: closing hides every Save button, so focus
	// returns to the document.
	function closeSheet() {
		document.removeEventListener( 'keydown', onDocumentKeydown );
		if ( sheet ) {
			sheet.remove();
			sheet = null;
		}
		remember( DISMISSED_KEY );
	}

	// Not aria-modal: the page behind stays usable, there is no backdrop and no focus trap. Escape
	// is heard on the whole document while the sheet is open, wherever focus is.
	function openSheet( ios ) {
		if ( sheet ) {
			return;
		}
		sheet = document.createElement( 'div' );
		sheet.className = 'ihq-save-home-sheet';
		sheet.setAttribute( 'role', 'dialog' );
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
		document.addEventListener( 'keydown', onDocumentKeydown );
		sheet.append( title, how, close );
		document.body.append( sheet );
		close.focus();
	}

	/**
	 * Shows the captured install prompt, once.
	 *
	 * @return {Promise<boolean>} False when the browser refused it (a stale event throws
	 *                            InvalidStateError), so the caller can fall back to the sheet.
	 */
	async function showPrompt() {
		const prompt = deferredPrompt;
		deferredPrompt = null;
		let choice = null;
		try {
			await prompt.prompt();
			choice = await prompt.userChoice;
		} catch ( error ) {
			return false;
		}
		if ( choice && OUTCOME_ACCEPTED === choice.outcome ) {
			remember( INSTALLED_KEY );
			return true;
		}
		remember( DISMISSED_KEY );
		return true;
	}

	/**
	 * The click on a Save button.
	 *
	 * @return {Promise<string>} The action taken, one of SAVE_HOME_ACTION.
	 */
	async function handleClick() {
		const ios = isIos( window.navigator );
		const action = chooseAction( { hasPrompt: Boolean( deferredPrompt ), ios } );
		if ( SAVE_HOME_ACTION.prompt !== action ) {
			openSheet( ios );
			return action;
		}
		const prompted = await showPrompt();
		if ( prompted ) {
			return action;
		}
		openSheet( ios );
		return chooseAction( { hasPrompt: false, ios } );
	}

	window.addEventListener( 'beforeinstallprompt', ( event ) => {
		event.preventDefault();
		deferredPrompt = event;
	} );
	window.addEventListener( 'appinstalled', () => remember( INSTALLED_KEY ) );
	document.addEventListener( 'click', ( event ) => {
		const button = event.target && event.target.closest ? event.target.closest( BUTTON_SELECTOR ) : null;
		if ( button ) {
			event.preventDefault();
			handleClick();
		}
	} );

	const standalone = isStandalone( { matchMedia: window.matchMedia, navigator: window.navigator } );
	if ( standalone || readFlag( storage, DISMISSED_KEY ) || readFlag( storage, INSTALLED_KEY ) ) {
		hide();
	}

	return {
		handleClick,
		hide,
		isHidden: () => root.classList.contains( HIDDEN_CLASS ),
		sheet: () => sheet,
	};
}

/** The sheet's colours: the portal's gold accent bar, near-black panel, white text, green action. */
const SHEET_COLOUR = Object.freeze( {
	border: '#b7962f',
	background: '#111',
	text: '#fff',
	button: '#148942',
	shadow: 'rgba(0, 0, 0, 0.6)',
} );

/** Gap between the sheet and the viewport edges. */
const SHEET_EDGE_GAP = '16px';

/** The sheet's widest size, so it stays a card on tablet and desktop. */
const SHEET_MAX_WIDTH = '420px';

/** Above the portal header and the onboarding layer. */
const SHEET_Z_INDEX = 100001;

/** The styles the sheet needs, and the rule that hides every Save button. */
export const SHEET_STYLE = `
.${ HIDDEN_CLASS } ${ BUTTON_SELECTOR } { display: none !important; }
.ihq-save-home-sheet { position: fixed; right: ${ SHEET_EDGE_GAP }; bottom: ${ SHEET_EDGE_GAP }; left: ${ SHEET_EDGE_GAP }; z-index: ${ SHEET_Z_INDEX }; max-width: ${ SHEET_MAX_WIDTH }; margin: 0 auto; padding: 20px; border: 1px solid ${ SHEET_COLOUR.border }; background: ${ SHEET_COLOUR.background }; color: ${ SHEET_COLOUR.text }; font-family: inherit; box-shadow: 0 8px 32px ${ SHEET_COLOUR.shadow }; }
.ihq-save-home-sheet h2 { margin: 0 0 8px; font-size: 1.125rem; }
.ihq-save-home-sheet p { margin: 0 0 16px; line-height: 1.5; }
.ihq-save-home-sheet button { width: 100%; padding: 12px; border: 0; background: ${ SHEET_COLOUR.button }; color: ${ SHEET_COLOUR.text }; font: inherit; font-weight: 600; cursor: pointer; }
`;

/** Used when localStorage cannot be reached: nothing is remembered. */
const NO_STORAGE = Object.freeze( {
	getItem: () => null,
	setItem: () => {},
} );

function pageStorage( window ) {
	try {
		return window.localStorage;
	} catch ( error ) {
		// Reading localStorage throws when site data is blocked.
		return NO_STORAGE;
	}
}

/**
 * Adds the styles to the page and starts the behaviour with the page's localStorage.
 *
 * @param {Object}   page
 * @param {Document} page.document
 * @param {Window}   page.window
 * @return {Object} What createSaveHome() returns.
 */
export function mountSaveHome( { document, window } ) {
	const style = document.createElement( 'style' );
	style.textContent = SHEET_STYLE;
	document.head.append( style );
	return createSaveHome( { document, window, storage: pageStorage( window ) } );
}
