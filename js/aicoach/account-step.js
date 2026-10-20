/**
 * Creating the visitor's account once, at the end of the channels step (ENGR-7828).
 *
 * The account used to be created by the last "Let's Continue" click. Gary releases the spoken
 * closing passage only once the account exists, so it is created earlier, when the channels step
 * is confirmed, and the last click only follows the address the creation returned. This keeps
 * the rules of that step out of the page script so they can be tested without a browser:
 *
 * - the account is created once: a second call while the first is still running shares it, and a
 *   call after it succeeded returns the same address without sending anything;
 * - isStarted() is true from the moment the request goes out until it fails, so a screen that must
 *   not appear for a visitor who is registering (Time is up) can tell a request in flight from none;
 * - a failure never throws and is never remembered, so the visitor can try again;
 * - what is saved to the visitor's progress is flushed before the request (a save that reached the
 *   server after the account was created would leave a row for a visitor who is already a user),
 *   and nothing is saved once the account exists (see afterCreated).
 *
 * @param {Object}   deps
 * @param {Function} deps.register       Sends the registration; resolves with the server's answer.
 *                                       Normally window.ihqCoachEvents.register.
 * @param {Function} deps.beforeRegister Resolves when the progress saves in flight have reached the server.
 * @param {Function} deps.afterCreated   Called once, right after the account was created, with the server's answer.
 * @param {Function} deps.messageFor     The server's own reason in an answer that was not a success, or ''.
 * @param {string}   deps.genericMessage What the visitor sees when there is no reason to show.
 * @return {{
 *   run: Function,
 *   isCreated: Function,
 *   isStarted: Function,
 *   redirectUrl: Function
 * }}
 */
export function createAccountStep( { register, beforeRegister, afterCreated, messageFor, genericMessage } ) {
	let createdRedirectUrl = null;
	let running = null;

	function failure( message ) {
		return { ok: false, message: message || genericMessage };
	}

	async function create( { identity, channels, language } ) {
		if ( typeof register !== 'function' ) {
			return failure( '' );
		}
		let data;
		try {
			await beforeRegister();
			const person = identity || {};
			data = await register( {
				firstName: person.firstName,
				lastName: person.lastName,
				username: person.username,
				channels: channels || undefined,
				language,
				redirect: false,
			} );
		} catch ( error ) {
			return failure( '' );
		}
		if ( ! data || ! data.success || ! data.redirectUrl ) {
			return failure( messageFor( data ) );
		}
		createdRedirectUrl = data.redirectUrl;
		afterCreated( data );
		return { ok: true, redirectUrl: createdRedirectUrl };
	}

	return {
		/**
		 * @param {Object} visitor
		 * @param {Object} [visitor.identity]  { firstName, lastName, username }
		 * @param {Array}  [visitor.channels]  [{ channel, value }]
		 * @param {string} [visitor.language]
		 * @return {Promise<{ ok: true, redirectUrl: string } | { ok: false, message: string }>}
		 */
		run( visitor ) {
			if ( createdRedirectUrl ) {
				return Promise.resolve( { ok: true, redirectUrl: createdRedirectUrl } );
			}
			if ( running ) {
				return running;
			}
			running = create( visitor || {} ).finally( function () {
				running = null;
			} );
			return running;
		},
		isCreated() {
			return null !== createdRedirectUrl;
		},
		isStarted() {
			return null !== running || null !== createdRedirectUrl;
		},
		redirectUrl() {
			return createdRedirectUrl;
		},
	};
}
