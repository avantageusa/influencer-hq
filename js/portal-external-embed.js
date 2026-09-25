/**
 * Availability watchdog for game-portal iframes (PO-2901, AC#5).
 *
 * A cross-origin frame exposes no readable state, so "did it load?" has to be
 * approximated. The watchdog only starts once the wrap is actually on screen,
 * because the frames are lazy and competition tabs are hidden until selected —
 * a timer started at parse time would fire against a frame that was never asked
 * to load. A frame refused by X-Frame-Options still fires `load`, so that case
 * is not detectable here; it is covered by the allowlist on the game-portal side.
 *
 * Frames ship with data-src (not src). This script binds load/error first, then
 * assigns src, so a footer-enqueued listener cannot miss an early load event and
 * falsely trip the timeout fallback.
 */
(function () {
	'use strict';

	var LOAD_TIMEOUT_MS = 15000;
	var EMBED_SELECTOR = '[data-ihq-external-embed]';
	var FRAME_SELECTOR = '[data-ihq-embed-frame]';
	var FALLBACK_SELECTOR = '[data-ihq-embed-fallback]';

	function showFallback(wrap) {
		var frame = wrap.querySelector(FRAME_SELECTOR);
		var fallback = wrap.querySelector(FALLBACK_SELECTOR);

		if (!fallback) {
			return;
		}

		if (frame) {
			frame.hidden = true;
		}

		fallback.hidden = false;
	}

	function watchEmbed(wrap) {
		var frame = wrap.querySelector(FRAME_SELECTOR);

		if (!frame) {
			return;
		}

		var timer = null;
		var loaded = false;
		var pendingSrc = frame.getAttribute('data-src') || '';

		frame.addEventListener('load', function () {
			loaded = true;
			window.clearTimeout(timer);
		});

		frame.addEventListener('error', function () {
			window.clearTimeout(timer);
			showFallback(wrap);
		});

		if (pendingSrc !== '' && !frame.getAttribute('src')) {
			frame.setAttribute('src', pendingSrc);
			frame.removeAttribute('data-src');
		}

		function startWatchdog() {
			if (loaded || timer !== null) {
				return;
			}

			timer = window.setTimeout(function () {
				if (!loaded) {
					showFallback(wrap);
				}
			}, LOAD_TIMEOUT_MS);
		}

		if (typeof window.IntersectionObserver !== 'function') {
			startWatchdog();
			return;
		}

		var observer = new window.IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (!entry.isIntersecting) {
					return;
				}

				startWatchdog();
				observer.disconnect();
			});
		});

		observer.observe(wrap);
	}

	function init() {
		var wraps = document.querySelectorAll(EMBED_SELECTOR);
		Array.prototype.forEach.call(wraps, watchEmbed);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
