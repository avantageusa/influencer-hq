/**
 * Referral capture (ENGR-6966): ?ref=<code> on any IHQ page -> first-party
 * ihq_ref cookie. Last touch wins. Runs in the browser because PROD pages are
 * served from the Cloudflare / WP Engine edge cache, where PHP never runs.
 * Server side (inc/ihq-referral-attribution.php) sanitises the value again
 * before it is stored or sent, and clears the cookie after a start-session
 * succeeds.
 */
(function (window, document) {
  'use strict';

  var cfg = window.IHQ_REF_CAPTURE || {};
  var COOKIE_NAME = cfg.cookieName || 'ihq_ref';
  var QUERY_PARAM = cfg.queryParam || 'ref';
  var DEFAULT_COOKIE_DAYS = 90;
  var DEFAULT_MAX_LENGTH = 64;
  var SECONDS_PER_DAY = 86400;

  function positiveIntOr(value, fallback) {
    var parsed = parseInt(value, 10);
    if (parsed > 0) {
      return parsed;
    }
    return fallback;
  }

  var COOKIE_DAYS = positiveIntOr(cfg.cookieDays, DEFAULT_COOKIE_DAYS);
  var MAX_LENGTH = positiveIntOr(cfg.maxLength, DEFAULT_MAX_LENGTH);

  function readRefParam(search) {
    if (typeof window.URLSearchParams !== 'function') {
      return '';
    }
    var value = new window.URLSearchParams(search).get(QUERY_PARAM);
    if (typeof value !== 'string') {
      return '';
    }
    return value.trim().substring(0, MAX_LENGTH);
  }

  function writeCookie(name, value, days) {
    var secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie =
      name + '=' + encodeURIComponent(value) +
      '; Path=/' +
      '; Max-Age=' + days * SECONDS_PER_DAY +
      '; SameSite=Lax' +
      secure;
  }

  var code = readRefParam(window.location.search);
  if (code === '') {
    return;
  }
  writeCookie(COOKIE_NAME, code, COOKIE_DAYS);
})(window, document);
