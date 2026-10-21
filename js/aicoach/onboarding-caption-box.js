/**
 * The onboarding's commentary box (ENGR-7891): the phrase Sami is saying, and the phrase before
 * it above in a softer style so a viewer can catch up (Marcus's commentary delivery
 * instructions, PO-3201).
 *
 * A change fades the box out, swaps the words while it is invisible and fades it back in
 * (css/portal-onboarding.css times the fades). The narrator reports the caption on every tick
 * of the clock; a report of what is already shown, or already on its way in, does nothing, so
 * the fade never restarts on the same words. With prefers-reduced-motion the words swap at
 * once and nothing fades.
 *
 * The box is aria-hidden: a screen reader gets the whole line once from the shell's transcript.
 */

/** How long the box takes to fade out before the words change; the CSS fade-out matches it. */
export const FADE_OUT_MS = 180;

/** On the box while it fades out. */
export const CHANGING_CLASS = 'is-changing';

/** The viewer has asked for less movement: no fading. */
export const REDUCED_MOTION_QUERY = '(prefers-reduced-motion: reduce)';

const PHRASE_CLASS = 'ihq-onb__phrase';
const PREVIOUS_CLASS = 'ihq-onb__phrase--previous';
const CURRENT_CLASS = 'ihq-onb__phrase--current';

function sameCaption( a, b ) {
	return a.current === b.current && a.previous === b.previous;
}

function captionOf( value ) {
	const caption = value || {};
	return { current: String( caption.current ?? '' ), previous: String( caption.previous ?? '' ) };
}

/**
 * @param {Object}  deps
 * @param {Element} deps.element  The box: [data-onb="subtitle"]. Its two lines are put in it.
 * @param {Object}  deps.document
 * @param {Object}  deps.window   For setTimeout, clearTimeout and matchMedia.
 * @return {Object} { show( { current, previous } ) }.
 */
export function createCaptionBox( { element, document, window } ) {
	const previousLine = document.createElement( 'span' );
	previousLine.className = PHRASE_CLASS + ' ' + PREVIOUS_CLASS;
	const currentLine = document.createElement( 'span' );
	currentLine.className = PHRASE_CLASS + ' ' + CURRENT_CLASS;
	element.replaceChildren( previousLine, currentLine );

	let shown = captionOf( null );
	let incoming = null;
	let timerId = null;

	function prefersReducedMotion() {
		if ( 'function' !== typeof window.matchMedia ) {
			return false;
		}
		return window.matchMedia( REDUCED_MOTION_QUERY ).matches;
	}

	function write( caption ) {
		previousLine.textContent = caption.previous;
		currentLine.textContent = caption.current;
		shown = caption;
		element.classList.remove( CHANGING_CLASS );
	}

	function swapIn() {
		timerId = null;
		const caption = incoming;
		incoming = null;
		write( caption );
	}

	/**
	 * @param {{ current: string, previous: string }} value From the narrator.
	 */
	function show( value ) {
		const caption = captionOf( value );
		if ( sameCaption( incoming || shown, caption ) ) {
			return;
		}
		if ( prefersReducedMotion() ) {
			window.clearTimeout( timerId );
			timerId = null;
			incoming = null;
			write( caption );
			return;
		}
		incoming = caption;
		// A change while the box is already fading out just swaps in the newer words.
		if ( null !== timerId ) {
			return;
		}
		element.classList.add( CHANGING_CLASS );
		timerId = window.setTimeout( swapIn, FADE_OUT_MS );
	}

	return { show };
}
