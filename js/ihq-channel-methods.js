/**
 * Sign-in methods switched off while the page is open (ENGR-8014, EQ14).
 *
 * The server renders only the methods influencerhq-api lists as on, but a page
 * opened before a switch still shows the old buttons. When an AJAX handler
 * answers 403 {"error":"method_disabled","method":"<m>","message":"…"}, every
 * element marked data-ihq-channel-method="<m>" is hidden until the next page
 * load, and the message goes into every notice marked
 * data-ihq-channel-method-unavailable="<m>", which stays visible: the page's
 * reset and Back paths clear its error boxes, never these notices. The error
 * box beside such a notice stays empty then (showFailure), so the message
 * shows once; a code step's own error box still shows it. The "OR"
 * divider between two methods (data-ihq-channel-method-divider) hides on any
 * refusal, as one side of it is then gone.
 *
 * The inline scripts in template-parts/portal-auth-modal.php and
 * page-portal-login.php are not modules, so this one also publishes its
 * functions on window.ihqChannelMethods.
 */

/** Error code of a refused method; the same name account-api and influencerhq-api use. */
export const METHOD_DISABLED_ERROR = 'method_disabled';

/** Attribute naming the method an element belongs to. */
export const METHOD_ATTRIBUTE = 'data-ihq-channel-method';

/** Attribute of the notice that says a method is unavailable, once it is refused. */
export const UNAVAILABLE_NOTICE_ATTRIBUTE = 'data-ihq-channel-method-unavailable';

/** Attribute of the divider shown between two methods. */
export const DIVIDER_ATTRIBUTE = 'data-ihq-channel-method-divider';

/** The display a revealed notice gets; its stylesheet may hide it by default. */
const NOTICE_DISPLAY = 'block';

/** The display a shown error box gets, as the inline scripts gave it. */
const ERROR_DISPLAY = 'block';

/** Display names, for a refusal that arrives without a message. */
export const METHOD_LABELS = Object.freeze( {
	email: 'Email',
	sms: 'SMS',
	telegram: 'Telegram',
	line: 'LINE',
	whatsapp: 'WhatsApp',
	zalo: 'Zalo',
	kakaotalk: 'KakaoTalk',
	wechat: 'WeChat',
} );

/**
 * The message for a refused method when the server sent none.
 *
 * @param {string} method One of METHOD_LABELS' keys.
 * @return {string} "<Method> isn't available right now".
 */
export function unavailableMessage( method ) {
	return METHOD_LABELS[ method ] + " isn't available right now";
}

/**
 * The refusal in a WordPress AJAX answer, or null when the answer is anything
 * else: a success, another error, or no parsed body at all. Pure.
 *
 * @param {*} response Parsed JSON of a wp_send_json_* answer.
 * @return {{method: string, message: string}|null} The refused method and the message to show.
 */
export function methodRefusal( response ) {
	if ( ! response || true === response.success || ! response.data ) {
		return null;
	}
	const data = response.data;
	if ( METHOD_DISABLED_ERROR !== data.error ) {
		return null;
	}
	if ( ! Object.prototype.hasOwnProperty.call( METHOD_LABELS, data.method ) ) {
		return null;
	}
	const message = 'string' === typeof data.message && '' !== data.message
		? data.message
		: unavailableMessage( data.method );
	return { method: data.method, message };
}

/**
 * Hide the controls of a method the server refused, hide the divider, and
 * show the method's unavailable notices with the refusal's message.
 *
 * @param {Object}           deps
 * @param {Document|Element} deps.root     Where the method's controls live.
 * @param {*}                deps.response Parsed JSON of a wp_send_json_* answer.
 * @return {{method: string, message: string}|null} The refusal, or null when nothing was hidden.
 */
export function hideRefusedMethod( { root, response } ) {
	const refusal = methodRefusal( response );
	if ( ! refusal ) {
		return null;
	}
	const controls = '[' + METHOD_ATTRIBUTE + '="' + refusal.method + '"], [' + DIVIDER_ATTRIBUTE + ']';
	root.querySelectorAll( controls ).forEach( ( element ) => {
		element.style.display = 'none';
	} );
	root.querySelectorAll( noticeSelector( refusal.method ) ).forEach( ( notice ) => {
		notice.textContent = refusal.message;
		notice.style.display = NOTICE_DISPLAY;
	} );
	return refusal;
}

/**
 * Show a failed answer's message once, next to the form that sent it. A
 * refused method's message goes into its unavailable notice, right below the
 * error box, so the box is emptied and hidden instead of repeating it (or
 * keeping an earlier error above it). Anything else, a refusal with no notice
 * on the page included, shows `message` in the error box as before.
 *
 * @param {Object}           deps
 * @param {Document|Element} deps.root     Where the method's controls and notices live.
 * @param {*}                deps.response Parsed JSON of a wp_send_json_* answer.
 * @param {?Element}         deps.errorBox The error box beside the method's notice, if the page has one.
 * @param {string}           deps.message  What the error box shows for any other failure.
 * @return {{method: string, message: string}|null} The refusal, or null when nothing was hidden.
 */
export function showFailure( { root, response, errorBox, message } ) {
	const refusal = hideRefusedMethod( { root, response } );
	if ( ! errorBox ) {
		return refusal;
	}
	if ( refusal && root.querySelector( noticeSelector( refusal.method ) ) ) {
		errorBox.textContent = '';
		errorBox.style.display = 'none';
		return refusal;
	}
	errorBox.textContent = message;
	errorBox.style.display = ERROR_DISPLAY;
	return refusal;
}

function noticeSelector( method ) {
	return '[' + UNAVAILABLE_NOTICE_ATTRIBUTE + '="' + method + '"]';
}

if ( 'undefined' !== typeof window ) {
	window.ihqChannelMethods = Object.freeze( { methodRefusal, hideRefusedMethod, showFailure } );
}
