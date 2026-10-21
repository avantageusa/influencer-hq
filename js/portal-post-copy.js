/**
 * Post page Copy (PO-3239 / FR-12).
 *
 * Each .post-panel-copy copies only that panel's .post-panel-draft text — not
 * the date, the platform aria-label, or markup. A short "Copied" confirmation
 * sits on the button. Default panels (no data-has-draft, FR-07) stay disabled
 * so placeholder copy is not offered.
 *
 * Clipboard is injected so tests can stand in for navigator.clipboard, and so
 * the write stays inside the click (Safari).
 */

/** Marker that this panel has a real draft, not FR-07 placeholder copy. */
export const HAS_DRAFT_ATTR = 'data-has-draft';

/** How long "Copied" stays on the button. */
export const COPY_CONFIRMATION_MS = 2000;

export const COPY_COPIED_LABEL = 'Copied';
export const COPY_FAILED_LABEL = "Couldn't copy. Select the text and copy it instead.";

const BROWSER_TIMERS = Object.freeze( {
	setTimeout: ( callback, ms ) => window.setTimeout( callback, ms ),
	clearTimeout: ( id ) => window.clearTimeout( id ),
} );

/**
 * The draft string Copy should put on the clipboard. Pure.
 *
 * @param {Element|null} panel A .post-panel, or null.
 * @return {string} textContent of .post-panel-draft, or ''.
 */
export function draftTextFromPanel( panel ) {
	if ( ! panel || ! panel.querySelector ) {
		return '';
	}
	const draft = panel.querySelector( '.post-panel-draft' );
	return draft ? String( draft.textContent ?? '' ) : '';
}

/**
 * Whether Copy should be usable on this panel (FR-07).
 *
 * @param {Element|null} panel A .post-panel, or null.
 * @return {boolean}
 */
export function panelHasCopyableDraft( panel ) {
	if ( ! panel || ! panel.hasAttribute( HAS_DRAFT_ATTR ) ) {
		return false;
	}
	return '' !== draftTextFromPanel( panel ).trim();
}

/**
 * Bind Copy on every .post-panel-copy under root.
 *
 * @param {Object}          deps
 * @param {Element|Document} deps.root      Document or a subtree that contains the panels.
 * @param {Function|null}   [deps.clipboard] ( text ) => Promise. Null when there is none.
 * @param {Object}          [deps.timers]   { setTimeout, clearTimeout }.
 * @param {string}          [deps.copiedLabel]
 * @param {string}          [deps.failedLabel]
 * @return {Object} { destroy() }
 */
export function bindPostPanelCopy( {
	root,
	clipboard = null,
	timers = BROWSER_TIMERS,
	copiedLabel = COPY_COPIED_LABEL,
	failedLabel = COPY_FAILED_LABEL,
} ) {
	const confirmTimers = new Map();
	const copyLabelByButton = new WeakMap();
	const buttons = root && root.querySelectorAll
		? Array.from( root.querySelectorAll( '.post-panel-copy' ) )
		: [];

	function restore( button ) {
		timers.clearTimeout( confirmTimers.get( button ) );
		confirmTimers.delete( button );
		const original = copyLabelByButton.get( button );
		if ( undefined !== original ) {
			button.textContent = original;
		}
	}

	function confirm( button, message ) {
		timers.clearTimeout( confirmTimers.get( button ) );
		button.textContent = message;
		confirmTimers.set(
			button,
			timers.setTimeout( () => restore( button ), COPY_CONFIRMATION_MS )
		);
	}

	function syncEnabled( button ) {
		const panel = button.closest( '.post-panel' );
		const enabled = panelHasCopyableDraft( panel );
		button.disabled = ! enabled;
		if ( enabled ) {
			button.removeAttribute( 'tabindex' );
		} else {
			button.setAttribute( 'tabindex', '-1' );
		}
	}

	async function onCopyClick( event ) {
		const button = event.currentTarget;
		if ( ! button || button.disabled ) {
			return;
		}
		const panel = button.closest( '.post-panel' );
		if ( ! panelHasCopyableDraft( panel ) ) {
			return;
		}
		const text = draftTextFromPanel( panel );
		if ( ! clipboard ) {
			confirm( button, failedLabel );
			return;
		}
		try {
			await clipboard( text );
			confirm( button, copiedLabel );
		} catch ( error ) {
			confirm( button, failedLabel );
		}
	}

	buttons.forEach( ( button ) => {
		copyLabelByButton.set( button, button.textContent );
		button.setAttribute( 'aria-live', 'polite' );
		syncEnabled( button );
		button.addEventListener( 'click', onCopyClick );
	} );

	return {
		destroy() {
			buttons.forEach( ( button ) => {
				button.removeEventListener( 'click', onCopyClick );
				restore( button );
			} );
		},
	};
}

/**
 * Labels from the Post page root, then English defaults.
 *
 * @param {Element|Document} root
 * @return {{ copiedLabel: string, failedLabel: string }}
 */
export function copyLabelsFromRoot( root ) {
	const content = root && root.querySelector
		? root.querySelector( '.post-page-content' )
		: null;
	const copied = content && content.getAttribute( 'data-copy-copied' );
	const failed = content && content.getAttribute( 'data-copy-failed' );
	return {
		copiedLabel: copied || COPY_COPIED_LABEL,
		failedLabel: failed || COPY_FAILED_LABEL,
	};
}

function browserClipboard() {
	if ( typeof navigator === 'undefined' || ! navigator.clipboard || ! navigator.clipboard.writeText ) {
		return null;
	}
	return ( text ) => navigator.clipboard.writeText( text );
}

function autoBind() {
	if ( typeof document === 'undefined' || ! document.querySelector ) {
		return;
	}
	if ( ! document.querySelector( '.post-panel-copy' ) ) {
		return;
	}
	const labels = copyLabelsFromRoot( document );
	bindPostPanelCopy( {
		root: document,
		clipboard: browserClipboard(),
		timers: BROWSER_TIMERS,
		copiedLabel: labels.copiedLabel,
		failedLabel: labels.failedLabel,
	} );
}

if ( typeof document !== 'undefined' && document.querySelector ) {
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', autoBind );
	} else {
		autoBind();
	}
}
