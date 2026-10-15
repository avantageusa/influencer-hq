/**
 * Calls to the onboarding REST routes (ENGR-7566): saving progress to the account store
 * (ENGR-7563, POST /ihq/v1/onboarding/progress) and marking onboarding complete
 * (ENGR-7564, POST /ihq/v1/onboarding/complete). Both need the wp_rest nonce.
 */

/**
 * POSTs JSON and reads the JSON answer. Never throws: a network failure or a non-JSON
 * answer comes back as ok: false, so a failed save never stops the onboarding.
 *
 * @param {Object}   options
 * @param {string}   options.url
 * @param {string}   options.nonce
 * @param {Object}   [options.body]
 * @param {Function} options.fetchFn Same contract as window.fetch.
 * @return {Promise<Object>} { ok, status, data }; status 0 when the request did not complete.
 */
export async function postJson( { url, nonce, body = {}, fetchFn } ) {
	try {
		const response = await fetchFn( url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
			body: JSON.stringify( body ),
		} );
		let data = null;
		try {
			data = await response.json();
		} catch ( error ) {
			data = null;
		}
		return { ok: Boolean( response.ok ), status: response.status, data };
	} catch ( error ) {
		return { ok: false, status: 0, data: null };
	}
}
