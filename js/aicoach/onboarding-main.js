/**
 * Entry of the portal onboarding (ENGR-7565, PO-3063).
 *
 * Runs only when inc/portal-onboarding-shell.php has rendered the layer over the Coach page.
 * Builds the frame (narrator, string table, shell), then installs the plug-in modules the
 * server listed (screens, language selector, preloading) and tells them the onboarding is
 * ready. The screen engine (ENGR-7566) starts the first screen on 'ready'.
 *
 * Bare specifiers resolve through the import map WordPress prints from the modules
 * registered in inc/portal-onboarding-shell.php.
 */
import { estimateDurationMs } from '@ihq/aicoach/commentary';
import { createStallWatchdog } from '@ihq/aicoach/stall-watchdog';
import { buildSentenceCues, subtitleAt } from '@ihq/aicoach/onboarding-subtitles';
import { createNarrator } from '@ihq/aicoach/onboarding-narrator';
import { createStringTable, SHELL_STRINGS_EN, FALLBACK_LOCALE } from '@ihq/aicoach/onboarding-strings';
import { createEmitter } from '@ihq/aicoach/onboarding-events';
import { createShell } from '@ihq/aicoach/onboarding-shell';
import { CONFIG_ELEMENT_ID, parseConfig, createApi, installModules } from '@ihq/aicoach/onboarding-boot';

async function start() {
	const root = document.getElementById( 'ihq-onboarding' );
	const configElement = document.getElementById( CONFIG_ELEMENT_ID );
	const config = parseConfig( configElement ? configElement.textContent : null );
	if ( ! root || ! config ) {
		return;
	}

	const strings = createStringTable();
	strings.add( FALLBACK_LOCALE, SHELL_STRINGS_EN );
	const events = createEmitter();
	const api = createApi( { config, events, strings } );

	let shell = null;
	const narrator = createNarrator( {
		video: root.querySelector( '[data-onb="video"]' ),
		onSubtitle: ( text ) => shell.showSubtitle( text ),
		onMutedChange: ( muted ) => shell.showMuted( muted ),
		buildSentenceCues,
		subtitleAt,
		estimateDurationMs,
		createStallWatchdog,
		setTimeout: window.setTimeout.bind( window ),
		clearTimeout: window.clearTimeout.bind( window ),
		now: () => window.performance.now(),
	} );
	shell = createShell( { root, document, window, narrator, strings, getLocale: api.getLocale } );
	api.shell = shell;
	events.on( 'locale', () => shell.applyStrings() );

	await installModules( { ids: config.modules, importFn: ( id ) => import( id ), api } );
	shell.applyStrings();
	events.emit( 'ready', api );
}

start();
