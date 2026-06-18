/**
 * Turnstile for guest portal verify page (explicit render, form POST to same URL).
 * Turnstile api.js must load synchronously immediately before this file (no async/defer).
 */
(function () {
	'use strict';

	var cfg = window.IHQ_PORTAL_TURNSTILE_GATE;
	if (!cfg || !cfg.siteKey || !cfg.postUrl) {
		return;
	}

	var errEl = document.getElementById('ihq-portal-ts-err');
	var retryBtn = document.getElementById('ihq-portal-ts-retry');
	var submitInFlight = false;
	var widgetStarted = false;
	var widgetId = null;

	function showError(message) {
		if (!errEl) {
			return;
		}
		errEl.textContent = message || cfg.errorMessage || 'Verification failed.';
		errEl.style.display = 'block';
	}

	function appendHidden(form, name, value) {
		var input = document.createElement('input');
		input.type = 'hidden';
		input.name = name;
		input.value = value;
		form.appendChild(input);
	}

	function submitToken(token) {
		if (submitInFlight || !token) {
			return;
		}
		submitInFlight = true;

		if (errEl) {
			errEl.style.display = 'none';
		}

		var form = document.createElement('form');
		form.method = 'POST';
		form.action = cfg.postUrl;
		appendHidden(form, 'ihq_portal_ts_unlock', '1');
		appendHidden(form, 'nonce', cfg.nonce);
		appendHidden(form, 'cf-turnstile-response', token);
		appendHidden(form, 'redirect_to', cfg.redirectTo);
		document.body.appendChild(form);
		form.submit();
	}

	function renderWidget() {
		if (widgetStarted) {
			return;
		}

		var host = document.getElementById('ihq-portal-ts-widget');
		if (!host || host.getAttribute('data-ihq-ts-init') === '1') {
			return;
		}

		if (typeof window.turnstile === 'undefined') {
			showError(cfg.errorMessage);
			if (retryBtn) {
				retryBtn.style.display = 'inline-block';
			}
			return;
		}

		host.setAttribute('data-ihq-ts-init', '1');
		widgetStarted = true;

		if (retryBtn) {
			retryBtn.style.display = 'none';
		}

		widgetId = window.turnstile.render(host, {
			sitekey: cfg.siteKey,
			size: 'normal',
			theme: 'dark',
			callback: submitToken,
			'error-callback': function () {
				submitInFlight = false;
				widgetStarted = false;
				host.removeAttribute('data-ihq-ts-init');
				showError(cfg.errorMessage);
				if (retryBtn) {
					retryBtn.style.display = 'inline-block';
				}
			},
			'expired-callback': function () {
				submitInFlight = false;
				if (widgetId === null) {
					return;
				}
				try {
					window.turnstile.reset(widgetId);
				} catch (e) {
					/* ignore */
				}
			},
		});
	}

	function startVerification() {
		if (cfg.requireManualStart && retryBtn) {
			retryBtn.style.display = 'inline-block';
			retryBtn.addEventListener('click', function () {
				renderWidget();
			});
			if (cfg.showRetryError) {
				showError(cfg.retryMessage || cfg.errorMessage);
			}
			return;
		}

		renderWidget();
	}

	if (!document.getElementById('ihq-portal-ts-widget')) {
		return;
	}

	if (typeof window.turnstile !== 'undefined' && typeof window.turnstile.ready === 'function') {
		window.turnstile.ready(startVerification);
	} else {
		startVerification();
	}
})();
