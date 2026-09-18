/**
 * AI Coach events for Luna / the page (PO-3257).
 *
 * First event: register. Luna (or the Let's Continue button) calls
 * window.ihqCoachEvents.register(detail) once the coach flow is done.
 * Missing fields are filled from the identity + communication forms on
 * page-home-aicoach.php. New emails create + sign in with no code screen;
 * existing emails get a passwordless login code and a login redirect.
 *
 *   window.ihqCoachEvents.register()
 *   window.ihqCoachEvents.register({ firstName: 'Ada', redirect: false })
 *   window.dispatchEvent(new CustomEvent('ihq:coach-register'))
 *   document.dispatchEvent(new CustomEvent('ihq:coach-register'))
 */
(function (window, document) {
	'use strict';

	var EVENT_REGISTER = 'ihq:coach-register';
	var cfg = window.IHQ_AICOACH_EVENTS || {};
	var registerInFlight = false;

	function trimString(value) {
		if (typeof value !== 'string') {
			return '';
		}
		return value.trim();
	}

	function collectIdentityFromPage() {
		return {
			firstName: trimString((document.getElementById('aicoach-first-name') || {}).value),
			lastName: trimString((document.getElementById('aicoach-last-name') || {}).value),
			username: trimString((document.getElementById('aicoach-username') || {}).value),
		};
	}

	function collectChannelsFromPage() {
		var form = document.getElementById('aicoach-channels-form');
		var channels = [];
		if (!form) {
			return channels;
		}

		var rows = form.querySelectorAll('.aicoach-channel');
		Array.prototype.forEach.call(rows, function (row) {
			var checkbox = row.querySelector('.aicoach-channel-check');
			var input = row.querySelector('.aicoach-channel-input');
			if (!checkbox || !checkbox.checked || !input) {
				return;
			}
			var key = trimString(row.getAttribute('data-channel') || '');
			var value = trimString(input.value);
			if (!key || !value) {
				return;
			}
			channels.push({ channel: key, value: value });
		});

		return channels;
	}

	function collectDurationFromPage() {
		var selected = document.querySelector('input[name="aicoach_duration"]:checked');
		if (!selected) {
			return '';
		}
		return trimString(selected.value);
	}

	function collectLanguageFromPage() {
		var htmlLang = document.documentElement ? document.documentElement.getAttribute('lang') : '';
		return trimString(htmlLang || 'en');
	}

	function collectFromPage() {
		var identity = collectIdentityFromPage();
		return {
			firstName: identity.firstName,
			lastName: identity.lastName,
			username: identity.username,
			channels: collectChannelsFromPage(),
			language: collectLanguageFromPage(),
			duration: collectDurationFromPage(),
		};
	}

	function channelsFromCommMethods(commMethods) {
		if (!commMethods || typeof commMethods !== 'object') {
			return [];
		}
		var channels = [];
		Object.keys(commMethods).forEach(function (key) {
			var value = trimString(String(commMethods[key] == null ? '' : commMethods[key]));
			if (!key || !value) {
				return;
			}
			channels.push({ channel: key, value: value });
		});
		return channels;
	}

	function mergePayload(collected, detail) {
		if (!detail || typeof detail !== 'object') {
			return collected;
		}

		var next = {
			firstName: collected.firstName,
			lastName: collected.lastName,
			username: collected.username,
			channels: collected.channels,
			language: collected.language,
			duration: collected.duration,
		};

		if (trimString(detail.firstName) !== '') {
			next.firstName = trimString(detail.firstName);
		} else if (trimString(detail.first_name) !== '') {
			next.firstName = trimString(detail.first_name);
		}

		if (trimString(detail.lastName) !== '') {
			next.lastName = trimString(detail.lastName);
		} else if (trimString(detail.last_name) !== '') {
			next.lastName = trimString(detail.last_name);
		}

		if (trimString(detail.username) !== '') {
			next.username = trimString(detail.username);
		}

		if (Array.isArray(detail.channels) && detail.channels.length > 0) {
			next.channels = detail.channels;
		} else if (detail.comm_methods) {
			var mapped = channelsFromCommMethods(detail.comm_methods);
			if (mapped.length > 0) {
				next.channels = mapped;
			}
		}

		if (trimString(detail.language) !== '') {
			next.language = trimString(detail.language);
		}
		if (trimString(detail.duration) !== '') {
			next.duration = trimString(detail.duration);
		}

		return next;
	}

	function hasEmailChannel(channels) {
		if (!Array.isArray(channels)) {
			return false;
		}
		return channels.some(function (entry) {
			if (!entry || typeof entry !== 'object') {
				return false;
			}
			var value = trimString(String(entry.value == null ? '' : entry.value));
			return value.indexOf('@') !== -1;
		});
	}

	function postRegister(payload) {
		if (!cfg.restUrl) {
			return Promise.resolve({
				success: false,
				error: 'Registration is not configured on this page.',
			});
		}

		return fetch(cfg.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce || '',
			},
			body: JSON.stringify(payload),
		}).then(function (response) {
			return response.json().then(function (data) {
				if (!data || typeof data !== 'object') {
					return {
						success: false,
						error: cfg.i18n && cfg.i18n.network ? cfg.i18n.network : 'Network error. Please try again.',
					};
				}
				return data;
			});
		}).catch(function () {
			return {
				success: false,
				error: cfg.i18n && cfg.i18n.network ? cfg.i18n.network : 'Network error. Please try again.',
			};
		});
	}

	function register(detail) {
		if (registerInFlight) {
			return Promise.resolve({
				success: false,
				error: 'Registration is already in progress.',
			});
		}

		var options = detail && typeof detail === 'object' ? detail : {};
		var shouldRedirect = options.redirect !== false;
		var payload = mergePayload(collectFromPage(), options);

		if (!hasEmailChannel(payload.channels)) {
			return Promise.resolve({
				success: false,
				error: cfg.i18n && cfg.i18n.missingEmail
					? cfg.i18n.missingEmail
					: 'Please add an email address so we can create your account.',
			});
		}

		registerInFlight = true;
		return postRegister(payload).then(function (data) {
			registerInFlight = false;
			if (data.success && data.signupToken) {
				try {
					window.sessionStorage.setItem('ihq_pending_login_token', String(data.signupToken));
					var emailForResume = '';
					if (Array.isArray(payload.channels)) {
						payload.channels.some(function (entry) {
							if (!entry || typeof entry !== 'object') {
								return false;
							}
							var value = trimString(String(entry.value == null ? '' : entry.value));
							if (value.indexOf('@') !== -1) {
								emailForResume = value;
								return true;
							}
							return false;
						});
					}
					if (emailForResume) {
						window.sessionStorage.setItem('ihq_pending_login_email', emailForResume);
					}
					if (data.message) {
						window.sessionStorage.setItem('ihq_pending_login_message', String(data.message));
					}
				} catch (storageError) {
					// sessionStorage may be unavailable; login page still works without resume.
				}
			}
			if (data.success && shouldRedirect && data.redirectUrl) {
				window.location.href = data.redirectUrl;
			}
			return data;
		}).catch(function () {
			registerInFlight = false;
			return {
				success: false,
				error: cfg.i18n && cfg.i18n.network ? cfg.i18n.network : 'Network error. Please try again.',
			};
		});
	}

	function onRegisterEvent(event) {
		register(event && event.detail ? event.detail : {});
	}

	// Luna may dispatch on window or document; listen on both.
	window.addEventListener(EVENT_REGISTER, onRegisterEvent);
	document.addEventListener(EVENT_REGISTER, onRegisterEvent);

	window.ihqCoachEvents = {
		REGISTER: EVENT_REGISTER,
		collect: collectFromPage,
		register: register,
	};
})(window, document);
