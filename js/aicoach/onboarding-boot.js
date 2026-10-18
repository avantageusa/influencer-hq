/**
 * Start-up helpers for the portal onboarding entry (ENGR-7565): reading the server's config,
 * the API object every plug-in module receives, and installing those modules.
 *
 * A plug-in is a script module named onboarding-screens-*.js or onboarding-addon-*.js. The
 * server lists their ids in config.modules (inc/portal-onboarding-shell.php); each exports
 * install( api ). One that fails to load or install is reported and skipped, so a broken
 * plug-in cannot take the whole onboarding down.
 */

/** Id of the <script type="application/json"> holding the config. */
export const CONFIG_ELEMENT_ID = 'ihq-onboarding-config';

/** The language used when none is saved (ENGR-7572 adds browser detection). */
export const DEFAULT_LOCALE = 'en';

function reportError( error ) {
	// eslint-disable-next-line no-console -- a plug-in that fails must be visible, not swallowed.
	console.error( error );
}

/**
 * Parses the config JSON. Anything unreadable gives null, and the onboarding does not start.
 *
 * @param {string|null|undefined} json
 * @return {Object|null} The config, or null.
 */
export function parseConfig( json ) {
	try {
		const config = JSON.parse( String( json ?? '' ) );
		if ( ! config || 'object' !== typeof config || ! config.state || 'object' !== typeof config.state ) {
			return null;
		}
		return config;
	} catch ( error ) {
		return null;
	}
}

/**
 * The clip for a screen in a language, or '' when none has been rendered.
 *
 * @param {Object} clips  { [stage]: { [locale]: url } }
 * @param {string} stage
 * @param {string} locale
 * @return {string} The clip URL, or ''.
 */
export function clipFor( clips, stage, locale ) {
	const byLocale = clips && clips[ stage ];
	if ( ! byLocale || 'object' !== typeof byLocale ) {
		return '';
	}
	const url = byLocale[ locale ];
	return 'string' === typeof url ? url : '';
}

/**
 * Sami's lines from the config, as string tables per language, so the captions and the
 * clips say the same words (inc/portal-onboarding-narration.php renders the clips from the
 * same table). Anything that is not a non-empty string is left out.
 *
 * @param {Object}   narration { [stage]: { [locale]: text } }
 * @param {Function} keyFor    ( stage ) => string-table key; the entry passes narrationKey.
 * @return {Object} { [locale]: { [key]: text } }
 */
export function narrationStrings( narration, keyFor ) {
	const tables = {};
	if ( ! narration || 'object' !== typeof narration ) {
		return tables;
	}
	for ( const [ stage, lines ] of Object.entries( narration ) ) {
		if ( ! lines || 'object' !== typeof lines ) {
			continue;
		}
		for ( const [ locale, text ] of Object.entries( lines ) ) {
			if ( 'string' !== typeof text || '' === text ) {
				continue;
			}
			tables[ locale ] = { ...tables[ locale ], [ keyFor( stage ) ]: text };
		}
	}
	return tables;
}

/**
 * The object every plug-in gets in install( api ).
 *
 * @param {Object} deps
 * @param {Object} deps.config  From parseConfig().
 * @param {Object} deps.events  From createEmitter().
 * @param {Object} deps.strings From createStringTable().
 * @return {Object} The API.
 */
export function createApi( { config, events, strings } ) {
	let locale = config.state.language || DEFAULT_LOCALE;
	const api = {
		config,
		state: { ...config.state },
		events,
		strings,
		shell: null,
		getLocale: () => locale,
		/**
		 * Changes the language of the onboarding and tells every module ('locale' event).
		 *
		 * @param {string} next
		 */
		setLocale( next ) {
			if ( next === locale ) {
				return;
			}
			locale = next;
			events.emit( 'locale', next );
		},
		clipUrl: ( stage ) => clipFor( config.clips, stage, locale ),
		imageUrl: ( stage ) => ( config.images && 'string' === typeof config.images[ stage ] ? config.images[ stage ] : '' ),
	};
	return api;
}

/**
 * Imports and installs the plug-in modules in the order the server listed them.
 *
 * @param {Object}   deps
 * @param {string[]} deps.ids       Module ids from config.modules.
 * @param {Function} deps.importFn  ( id ) => Promise<module>; the entry passes ( id ) => import( id ).
 * @param {Object}   deps.api
 * @param {Function} [deps.onError] Defaults to console.error.
 * @return {Promise<string[]>} The ids that installed.
 */
export async function installModules( { ids, importFn, api, onError = reportError } ) {
	const installed = [];
	for ( const id of Array.isArray( ids ) ? ids : [] ) {
		try {
			const module = await importFn( id );
			if ( module && 'function' === typeof module.install ) {
				module.install( api );
				installed.push( id );
			}
		} catch ( error ) {
			onError( error );
		}
	}
	return installed;
}
