/**
 * The onboarding frame's behaviour (ENGR-7565): the hamburger menu, the sound button, what
 * Sami says in the commentary box, the content area screens render into, and closing the
 * layer. The markup is template-parts/portal-onboarding.php; elements are found by their
 * data-onb attribute.
 *
 * The narrator and the string table are passed in.
 */

/** Class on <html> while the layer is open, so the Coach page behind it does not scroll. */
export const OPEN_CLASS = 'ihq-onb-open';

/**
 * Finds a part of the frame.
 *
 * @param {Element} root
 * @param {string}  name The data-onb value.
 * @return {Element|null} The element, or null when the markup has no such part.
 */
export function part( root, name ) {
	return root.querySelector( '[data-onb="' + name + '"]' );
}

/**
 * @param {Object}   deps
 * @param {Element}  deps.root      The #ihq-onboarding element.
 * @param {Document} deps.document
 * @param {Window}   deps.window
 * @param {Object}   deps.narrator  From createNarrator().
 * @param {Object}   deps.strings   From createStringTable().
 * @param {Function} deps.getLocale The language the text is shown in.
 * @return {Object} The shell.
 */
export function createShell( { root, document, window, narrator, strings, getLocale } ) {
	const menuButton = part( root, 'menu-button' );
	const menu = part( root, 'menu' );
	const soundButton = part( root, 'sound' );
	const subtitle = part( root, 'subtitle' );
	const transcript = part( root, 'transcript' );
	const content = part( root, 'content' );

	document.documentElement.classList.add( OPEN_CLASS );

	function label( key ) {
		return strings.t( getLocale(), key );
	}

	function setMenuOpen( open ) {
		menu.hidden = ! open;
		menuButton.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		menuButton.setAttribute( 'aria-label', label( open ? 'menuClose' : 'menuOpen' ) );
	}

	function showMuted( muted ) {
		soundButton.setAttribute( 'aria-pressed', muted ? 'true' : 'false' );
		soundButton.setAttribute( 'aria-label', label( muted ? 'soundUnmute' : 'soundMute' ) );
	}

	menuButton.addEventListener( 'click', () => setMenuOpen( menu.hidden ) );
	root.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' === event.key && ! menu.hidden ) {
			setMenuOpen( false );
			menuButton.focus();
		}
	} );
	soundButton.addEventListener( 'click', () => {
		narrator.setMuted( ! narrator.isMuted() );
		showMuted( narrator.isMuted() );
	} );

	/**
	 * Re-labels every element marked with data-onb-string (text) or data-onb-label
	 * (aria-label) in the current language.
	 */
	function applyStrings() {
		root.querySelectorAll( '[data-onb-string]' ).forEach( ( element ) => {
			element.textContent = label( element.getAttribute( 'data-onb-string' ) );
		} );
		root.querySelectorAll( '[data-onb-label]' ).forEach( ( element ) => {
			element.setAttribute( 'aria-label', label( element.getAttribute( 'data-onb-label' ) ) );
		} );
		setMenuOpen( ! menu.hidden );
		showMuted( narrator.isMuted() );
	}

	/**
	 * Sami says one line: the clip if there is one, captions at reading pace otherwise. A
	 * screen reader gets the whole line once.
	 *
	 * @param {Object} line
	 * @param {string} line.script    What Sami says.
	 * @param {string} [line.clipUrl] Her clip of it, if any.
	 * @return {Promise<Object>} Settles when she has finished (or was stopped).
	 */
	function say( line ) {
		transcript.textContent = line.script;
		return narrator.play( line );
	}

	/**
	 * Removes the layer, leaving the Coach page at its top.
	 */
	function close() {
		narrator.stop();
		root.remove();
		document.documentElement.classList.remove( OPEN_CLASS );
		window.scrollTo( 0, 0 );
	}

	return {
		root,
		content,
		say,
		stopSpeaking: () => narrator.stop(),
		showSubtitle: ( text ) => {
			subtitle.textContent = text;
		},
		showMuted,
		applyStrings,
		close,
	};
}
