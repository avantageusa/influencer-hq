/**
 * Wires SAVE IHQ TO HOME SCREEN on portal pages (ENGR-7575); the behaviour is in
 * js/aicoach/save-home.js, enqueued by inc/portal-install.php.
 */
import { createSaveHome, HIDDEN_CLASS } from '@ihq/aicoach/save-home';

const SHEET_STYLE = `
.${ HIDDEN_CLASS } [data-ihq-save-home] { display: none !important; }
.ihq-save-home-sheet { position: fixed; right: 16px; bottom: 16px; left: 16px; z-index: 100001; max-width: 420px; margin: 0 auto; padding: 20px; border: 1px solid #b7962f; background: #111; color: #fff; font-family: inherit; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6); }
.ihq-save-home-sheet h2 { margin: 0 0 8px; font-size: 1.125rem; }
.ihq-save-home-sheet p { margin: 0 0 16px; line-height: 1.5; }
.ihq-save-home-sheet button { width: 100%; padding: 12px; border: 0; background: #148942; color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
`;

function storage() {
	try {
		return window.localStorage;
	} catch ( error ) {
		return { getItem: () => null, setItem: () => {} };
	}
}

const style = document.createElement( 'style' );
style.textContent = SHEET_STYLE;
document.head.append( style );

createSaveHome( { document, window, storage: storage() } );
