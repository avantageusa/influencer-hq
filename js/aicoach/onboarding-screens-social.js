/**
 * Onboarding screen 6, the social platforms (ENGR-7569, PO-3063 T7, PO-3207).
 *
 * "Which Social Media sites do you post on?" lists the 19 platforms under PLATFORM, none
 * ticked, with ACCOUNT ID alongside. Ticking a platform shows its handle field, with a
 * placeholder for that platform; unticking hides and clears it. There are no "Connect with …"
 * sign-in buttons: handles are typed (OQ3). Any number of platforms may be ticked, none
 * included, but CONTINUE is blocked while a ticked platform has no handle, with an error on
 * that field.
 *
 * The screen never moves on by itself (MANUAL advance mode): only its own CONTINUE control,
 * the design's small button under SAVE IHQ TO HOME SCREEN, moves the flow on (design
 * decision 1). It is drawn at the end of the screen's own content, right-aligned, so the
 * shell's footer stays the shell's.
 *
 * Platforms and handles are saved to the account store as they change; the server copies the
 * handles to the Profile page (inc/portal-onboarding-social.php). Nothing is pre-filled from
 * the communication channels given at registration (PO-3207).
 *
 * A plug-in module: the entry installs it, and it registers its screen with the engine.
 */

/** The longest handle the store accepts (IHQ_PORTAL_ONBOARDING_HANDLE_MAX_LENGTH). */
export const HANDLE_MAX_LENGTH = 190;

/**
 * The 19 platforms in screen order: stored key and string-table keys. The keys are the store's
 * (ihq_portal_onboarding_platforms()) and the Profile page's.
 */
export const PLATFORMS = Object.freeze( [
	'instagram',
	'facebook',
	'tiktok',
	'youtube',
	'bilibili',
	'douyin',
	'kakaotalk',
	'line',
	'linkedin',
	'pinterest',
	'qq',
	'reddit',
	'rednote',
	'snapchat',
	'telegram',
	'wechat',
	'weibo',
	'whatsapp',
	'x',
] );

/**
 * English copy. The placeholders are '@username' or the platform's URL prefix, as in the earlier
 * design, until PO-3207 gives a table of placeholders and accepted formats.
 */
export const SOCIAL_STRINGS_EN = {
	socialQuestion: 'Which Social Media sites do you post on?',
	socialPlatformColumn: 'PLATFORM',
	socialAccountColumn: 'ACCOUNT ID',
	socialHandleRequired: 'Enter your account ID for this platform.',
	socialContinue: 'CONTINUE',

	platform_instagram: 'Instagram',
	platform_facebook: 'Facebook',
	platform_tiktok: 'TikTok',
	platform_youtube: 'YouTube',
	platform_bilibili: 'Bilibili',
	platform_douyin: 'Douyin',
	platform_kakaotalk: 'KakaoTalk',
	platform_line: 'LINE',
	platform_linkedin: 'LinkedIn',
	platform_pinterest: 'Pinterest',
	platform_qq: 'QQ',
	platform_reddit: 'Reddit',
	platform_rednote: 'RedNote',
	platform_snapchat: 'Snapchat',
	platform_telegram: 'Telegram',
	platform_wechat: 'WeChat',
	platform_weibo: 'Weibo',
	platform_whatsapp: 'WhatsApp',
	platform_x: 'X',

	placeholder_instagram: '@username',
	placeholder_facebook: 'facebook.com/',
	placeholder_tiktok: '@username',
	placeholder_youtube: 'youtube.com/@',
	placeholder_bilibili: 'space.bilibili.com/',
	placeholder_douyin: '@username',
	placeholder_kakaotalk: '@username',
	placeholder_line: '@username',
	placeholder_linkedin: 'linkedin.com/in/',
	placeholder_pinterest: 'pinterest.com/',
	placeholder_qq: '@username',
	placeholder_reddit: 'reddit.com/u/',
	placeholder_rednote: '@username',
	placeholder_snapchat: '@username',
	placeholder_telegram: '@username',
	placeholder_wechat: '@username',
	placeholder_weibo: 'weibo.com/',
	placeholder_whatsapp: '@username',
	placeholder_x: '@username',
};

/**
 * The selection a returning Influencer had, limited to the 19 and to handles of ticked
 * platforms. Pure.
 *
 * @param {Object} state The store's state.
 * @return {Object} { ticked: string[], handles: Object }.
 */
export function storedSelection( state ) {
	const platforms = state && Array.isArray( state.platforms ) ? state.platforms : [];
	const ticked = PLATFORMS.filter( ( key ) => platforms.includes( key ) );
	const handles = {};
	const stored = state && state.handles && 'object' === typeof state.handles ? state.handles : {};
	ticked.forEach( ( key ) => {
		if ( 'string' === typeof stored[ key ] ) {
			handles[ key ] = stored[ key ];
		}
	} );
	return { ticked, handles };
}

/**
 * The ticked platforms whose handle is empty. Pure.
 *
 * @param {Object} selection { ticked, handles }.
 * @return {string[]} Platform keys, in screen order.
 */
export function missingHandles( selection ) {
	return selection.ticked.filter( ( key ) => '' === String( selection.handles[ key ] || '' ).trim() );
}

/**
 * What to send to the store for a selection: the ticked platforms, and the handles that are
 * filled in (the store refuses empty ones). Pure.
 *
 * @param {Object} selection { ticked, handles }.
 * @return {Object} { platforms, handles }.
 */
export function selectionToSave( selection ) {
	const handles = {};
	selection.ticked.forEach( ( key ) => {
		const handle = String( selection.handles[ key ] || '' ).trim();
		if ( '' !== handle ) {
			handles[ key ] = handle;
		}
	} );
	return { platforms: selection.ticked.slice(), handles };
}

function platformRow( { document, text, t, key } ) {
	const row = document.createElement( 'div' );
	row.className = 'ihq-onb-social__row';
	row.setAttribute( 'data-platform', key );

	const tick = document.createElement( 'button' );
	tick.setAttribute( 'type', 'button' );
	tick.setAttribute( 'role', 'checkbox' );
	tick.setAttribute( 'aria-checked', 'false' );
	tick.className = 'ihq-onb-social__tick';
	tick.append( text( 'span', 'platform_' + key, 'ihq-onb-social__name' ) );

	const field = document.createElement( 'input' );
	field.setAttribute( 'type', 'text' );
	field.className = 'ihq-onb-social__handle';
	field.setAttribute( 'maxlength', String( HANDLE_MAX_LENGTH ) );
	field.setAttribute( 'autocomplete', 'off' );
	field.setAttribute( 'placeholder', t( 'placeholder_' + key ) );
	field.setAttribute( 'data-onb-placeholder', 'placeholder_' + key );
	field.setAttribute( 'aria-label', t( 'platform_' + key ) );
	field.setAttribute( 'aria-describedby', 'ihq-onb-social-error-' + key );
	field.hidden = true;

	const error = text( 'p', 'socialHandleRequired', 'ihq-onb__error' );
	error.setAttribute( 'id', 'ihq-onb-social-error-' + key );
	error.hidden = true;

	row.append( tick, field, error );
	return { row, tick, field, error };
}

function continueControl( { text, advance } ) {
	const control = text( 'button', 'socialContinue', 'ihq-onb__button ihq-onb-social__continue' );
	control.setAttribute( 'type', 'button' );
	// advance() checks isValid() and calls showValidation() itself; its result is not needed here.
	control.addEventListener( 'click', () => advance() );
	return control;
}

function renderSocial( { container, api, t, text, advance } ) {
	const document = container.ownerDocument;
	container.append( text( 'h2', 'socialQuestion', 'ihq-onb-social__question' ) );

	const header = document.createElement( 'div' );
	header.className = 'ihq-onb-social__columns';
	header.setAttribute( 'aria-hidden', 'true' );
	header.append( text( 'span', 'socialPlatformColumn' ), text( 'span', 'socialAccountColumn' ) );
	container.append( header );

	const list = document.createElement( 'div' );
	list.className = 'ihq-onb-social__list';
	container.append( list );

	let selection = storedSelection( api.state );
	const rows = new Map();
	PLATFORMS.forEach( ( key ) => {
		const parts = platformRow( { document, text, t, key } );
		rows.set( key, parts );
		list.append( parts.row );
	} );

	function paint() {
		rows.forEach( ( parts, key ) => {
			const ticked = selection.ticked.includes( key );
			parts.tick.setAttribute( 'aria-checked', ticked ? 'true' : 'false' );
			parts.field.hidden = ! ticked;
			parts.field.value = ticked ? selection.handles[ key ] || '' : '';
			if ( ! ticked ) {
				showError( key, false );
			}
		} );
	}

	function showError( key, on ) {
		const parts = rows.get( key );
		parts.error.hidden = ! on;
		if ( on ) {
			parts.field.setAttribute( 'aria-invalid', 'true' );
			return;
		}
		parts.field.removeAttribute( 'aria-invalid' );
	}

	function save() {
		api.save( selectionToSave( selection ) );
	}

	function toggle( key ) {
		const ticked = selection.ticked.includes( key );
		const handles = { ...selection.handles };
		delete handles[ key ];
		const keys = ticked ? selection.ticked.filter( ( entry ) => entry !== key ) : selection.ticked.concat( key );
		selection = { ticked: PLATFORMS.filter( ( entry ) => keys.includes( entry ) ), handles };
		paint();
		save();
		if ( ! ticked ) {
			rows.get( key ).field.focus();
		}
	}

	rows.forEach( ( parts, key ) => {
		parts.tick.addEventListener( 'click', () => toggle( key ) );
		parts.field.addEventListener( 'input', () => {
			selection = { ticked: selection.ticked, handles: { ...selection.handles, [ key ]: parts.field.value } };
			if ( '' !== parts.field.value.trim() ) {
				showError( key, false );
			}
		} );
		parts.field.addEventListener( 'change', save );
	} );
	paint();
	container.append( continueControl( { text, advance } ) );

	const stopLocale = api.events.on( 'locale', () => {
		rows.forEach( ( parts, key ) => {
			parts.field.setAttribute( 'placeholder', t( 'placeholder_' + key ) );
			parts.field.setAttribute( 'aria-label', t( 'platform_' + key ) );
		} );
	} );

	return {
		isValid: () => 0 === missingHandles( selection ).length,
		showValidation() {
			const missing = missingHandles( selection );
			missing.forEach( ( key ) => showError( key, true ) );
			if ( missing.length > 0 ) {
				rows.get( missing[ 0 ] ).field.focus();
			}
		},
		destroy: stopLocale,
	};
}

/**
 * Adds the English copy and registers screen 6.
 *
 * @param {Object} api The onboarding API.
 */
export function install( api ) {
	api.strings.add( 'en', SOCIAL_STRINGS_EN );
	api.screens.register( {
		id: 'social_platforms',
		advanceMode: api.screens.ADVANCE_MODES.MANUAL,
		render: renderSocial,
	} );
}
