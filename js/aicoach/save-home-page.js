/**
 * Wires SAVE IHQ TO HOME SCREEN on portal pages (ENGR-7575); the behaviour and its styles are in
 * js/aicoach/save-home.js, enqueued by inc/portal-install.php.
 */
import { mountSaveHome } from '@ihq/aicoach/save-home';

mountSaveHome( { document, window } );
