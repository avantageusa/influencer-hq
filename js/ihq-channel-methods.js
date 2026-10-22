/**
 * Sign-in methods switched off while the page is open (ENGR-8014, EQ14).
 *
 * The server renders only the methods influencerhq-api lists as on, but a page
 * opened before a switch still shows the old buttons. When an AJAX handler
 * answers 403 {"error":"method_disabled","method":"<m>","message":"…"}, the
 * page's own error box shows the message, and every element marked
 * data-ihq-channel-method="<m>" is hidden until the next page load. Error
 * boxes are never marked, so the message stays visible.
 *
 * The inline scripts in template-parts/portal-auth-modal.php and
 * page-portal-login.php are not modules, so this one also publishes its
 * functions on window.ihqChannelMethods.
 */

/** Error code of a refused method; the same name account-api and influencerhq-api use. */
export const METHOD_DISABLED_ERROR = 'method_disabled';

/** Attribute naming the method an element belongs to. */
export const METHOD_ATTRIBUTE = 'data-ihq-channel-method';

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
 * Hide the controls of a method the server refused.
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
	const selector = '[' + METHOD_ATTRIBUTE + '="' + refusal.method + '"]';
	root.querySelectorAll( selector ).forEach( ( element ) => {
		element.style.display = 'none';
	} );
	return refusal;
}

if ( 'undefined' !== typeof window ) {
	window.ihqChannelMethods = Object.freeze( { methodRefusal, hideRefusedMethod } );
}
