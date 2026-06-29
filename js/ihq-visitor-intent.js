/**
 * Anonymous visitor intent — 30-day cookie + modal/lander capture + 6-digit registration code.
 */
(function (window) {
  'use strict';

  var cfg = window.IHQ_VISITOR_INTENT || {};
  var COOKIE_NAME = cfg.cookieName || 'ihq_visitor_intent';
  var CODE_ISSUED_COOKIE = 'ihq_visitor_code_issued';
  var COOKIE_DAYS = cfg.cookieDays || 30;
  var MAX_COOKIE_BYTES = 3800;
  var CODE_EXPIRES_MINUTES = cfg.codeExpiresMinutes || 15;

  function readCookie(name) {
    var prefix = name + '=';
    var parts = document.cookie.split(';');
    for (var i = 0; i < parts.length; i++) {
      var part = parts[i].trim();
      if (part.indexOf(prefix) === 0) {
        return decodeURIComponent(part.substring(prefix.length));
      }
    }
    return '';
  }

  function writeCookie(name, value, days) {
    var maxAge = Math.max(1, parseInt(days, 10) || 30) * 86400;
    var secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie =
      name + '=' + encodeURIComponent(value) +
      '; Path=/' +
      '; Max-Age=' + maxAge +
      '; SameSite=Lax' +
      secure;
  }

  function readIntent() {
    var raw = readCookie(COOKIE_NAME);
    if (!raw) {
      return {};
    }
    try {
      var parsed = JSON.parse(raw);
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch (e) {
      return {};
    }
  }

  function writeIntent(data) {
    var json = JSON.stringify(data);
    if (json.length > MAX_COOKIE_BYTES) {
      return false;
    }
    writeCookie(COOKIE_NAME, json, COOKIE_DAYS);
    return true;
  }

  function hasCommMethods(intent) {
    var methods = (intent || readIntent()).comm_methods;
    if (!methods || typeof methods !== 'object') {
      return false;
    }
    return Object.keys(methods).some(function (key) {
      var val = methods[key];
      return typeof val === 'string' && val.trim() !== '';
    });
  }

  function readVerificationCodeIssued() {
    return readCookie(CODE_ISSUED_COOKIE) === '1';
  }

  function markVerificationCodeIssued() {
    writeCookie(CODE_ISSUED_COOKIE, '1', COOKIE_DAYS);
  }

  function isPortalPage() {
    var path = window.location.pathname || '';
    return path.indexOf('/portal') !== -1;
  }

  function openCommunicationModal() {
    if (typeof window.openModal !== 'function' || !document.getElementById('mainModal')) {
      return;
    }
    window.ihqConversationModalOpenedFromGate = true;
    window.openModal();
  }

  function mergeIntent(patch) {
    var current = readIntent();
    var next = Object.assign({}, current, patch || {});
    if (patch && patch.competition_ratings) {
      next.competition_ratings = Object.assign(
        {},
        current.competition_ratings || {},
        patch.competition_ratings
      );
    }
    if (patch && patch.comm_methods) {
      next.comm_methods = Object.assign({}, current.comm_methods || {}, patch.comm_methods);
    }
    if (patch && patch.social_handles) {
      next.social_handles = Object.assign({}, current.social_handles || {}, patch.social_handles);
    }
    next.updated_at = new Date().toISOString();
    writeIntent(next);
    return next;
  }

  function collectModalCommMethods() {
    var methods = {};
    document.querySelectorAll('#modal-comm-pick .modal-comm-option input[type=checkbox]').forEach(function (box) {
      if (!box.checked) {
        return;
      }
      var key = box.getAttribute('data-comm-key');
      var input = key ? document.getElementById('modal-comm-input-' + key) : null;
      if (key && input && input.value.trim()) {
        methods[key] = input.value.trim();
      }
    });
    return methods;
  }

  function collectModalSocialHandles() {
    var handles = {};
    document.querySelectorAll('.social-grid-item.is-selected').forEach(function (btn) {
      var key = btn.getAttribute('data-social-key');
      var row = key ? document.getElementById('social-entry-' + key) : null;
      var inp = row ? row.querySelector('input.social-handle-input') : null;
      var label = btn.textContent.trim();
      if (key && inp && inp.value.trim()) {
        handles[key] = label ? label + ': ' + inp.value.trim() : inp.value.trim();
      }
    });
    return handles;
  }

  function collectModalChallengeType() {
    var cw = document.getElementById('cw');
    var cp = document.getElementById('cp');
    if (cw && cw.classList.contains('sel')) {
      return 'weekend_world';
    }
    if (cp && cp.classList.contains('sel')) {
      return 'community_challenge';
    }
    return 'maybe_later';
  }

  function collectPlatformHandleFromSocial() {
    var parts = [];
    var handles = collectModalSocialHandles();
    Object.keys(handles).forEach(function (key) {
      parts.push(handles[key]);
    });
    return parts.join(' | ');
  }

  function collectCompetitionRatings() {
    var ratings = {};
    ['world', 'community', 'private'].forEach(function (group) {
      var el = document.getElementById('ln-comp-rating-' + group);
      if (el && el.value) {
        ratings[group] = el.value;
      }
    });
    return ratings;
  }

  function collectFromModal() {
    var ratings = collectCompetitionRatings();
    var payload = {
      comm_methods: collectModalCommMethods(),
      social_handles: collectModalSocialHandles(),
      platform_handle: collectPlatformHandleFromSocial(),
      challenge_type: collectModalChallengeType(),
      source_url: window.location.href,
      captured_from: 'modal',
    };
    if (Object.keys(ratings).length) {
      payload.competition_ratings = ratings;
    }
    return payload;
  }

  function saveRating(group, val) {
    var ratings = {};
    ratings[group] = String(val);
    mergeIntent({
      competition_ratings: ratings,
      source_url: window.location.href,
      captured_from: 'lander_rating',
    });
  }

  function resolveCountryIso() {
    return typeof window.ihqResolveClientCountryIsoAlpha2 === 'function'
      ? window.ihqResolveClientCountryIsoAlpha2()
      : '';
  }

  function buildIssueFormData(intent, options) {
    options = options || {};
    var buttonUrl = options.buttonPressUrl || window.location.href.split('#')[0];
    if (options.gateId) {
      buttonUrl += '#gate=' + encodeURIComponent(options.gateId);
    }

    var fd = new FormData();
    fd.append('action', 'ihq_issue_visitor_verification');
    fd.append('nonce', cfg.nonce || '');
    fd.append('intent_json', JSON.stringify(intent));
    fd.append('button_press_url', buttonUrl);
    fd.append('country_iso', resolveCountryIso());
    if (options.gateId) {
      fd.append('gate_id', options.gateId);
    }
    return { fd: fd, buttonUrl: buttonUrl };
  }

  function buildBrazePreview(intent, extras) {
    var out = {
      intent: intent || {},
      button_press_url: extras && extras.button_press_url ? extras.button_press_url : '',
      registration_code: extras && extras.registration_code ? extras.registration_code : '',
      expires_minutes: extras && extras.expires_minutes ? extras.expires_minutes : CODE_EXPIRES_MINUTES,
    };
    if (extras && extras.braze_track_payload) {
      out.braze_track_payload = extras.braze_track_payload;
    }
    if (extras && extras.braze_response) {
      out.braze_response = extras.braze_response;
    }
    return out;
  }

  function renderPreview(el, data) {
    if (!el) {
      return;
    }
    el.textContent = JSON.stringify(data, null, 2);
  }

  function refreshTestRegistryPreview() {
    var el = document.getElementById('ihq-visitor-intent-braze-preview');
    if (!el) {
      return;
    }
    renderPreview(el, buildBrazePreview(readIntent(), {}));
  }

  function logGateRegistryConsole(gateId, label, payload) {
    if (!gateId || !window.console) {
      return;
    }
    var prefix = '[IHQ Registry Gate] ' + gateId;
    if (label === 'request') {
      console.group(prefix + ' — AJAX request');
      console.log(payload);
      console.groupEnd();
      return;
    }
    if (label === 'braze') {
      console.group(prefix + ' — sent to Braze (/users/track)');
      console.log(payload.braze_track_payload || payload);
      console.groupEnd();
      console.group(prefix + ' — Braze HTTP response');
      console.log(payload.braze_response || payload);
      console.groupEnd();
      if (payload.registration_code) {
        console.log(prefix + ' — registration_code:', payload.registration_code);
      }
      return;
    }
    if (label === 'error') {
      console.warn(prefix + ' — error:', payload);
    }
  }

  function issueVisitorVerification(options) {
    options = options || {};
    var btn = document.getElementById('ihq-test-registry-btn');
    var preview = document.getElementById('ihq-visitor-intent-braze-preview');
    var intent = readIntent();
    var gateId = options.gateId || '';

    if (!hasCommMethods(intent)) {
      return Promise.resolve({ success: false });
    }

    if (readVerificationCodeIssued() && !options.force) {
      return Promise.resolve({ success: true, skipped: true });
    }

    var built = buildIssueFormData(intent, {
      gateId: gateId,
      buttonPressUrl: options.buttonPressUrl,
    });

    if (gateId) {
      logGateRegistryConsole(gateId, 'request', {
        action: 'ihq_issue_visitor_verification',
        gate_id: gateId,
        button_press_url: built.buttonUrl,
        country_iso: resolveCountryIso(),
        intent: intent,
      });
    }

    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Sending…';
    }

    return fetch(cfg.ajaxUrl || '/wp-admin/admin-ajax.php', { method: 'POST', body: built.fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'TEST REGISTRY';
        }
        if (!data.success) {
          var msg = (data.data && data.data.message) ? data.data.message : 'Request failed.';
          if (gateId) {
            logGateRegistryConsole(gateId, 'error', msg);
          }
          renderPreview(preview, buildBrazePreview(intent, { error: msg }));
          return data;
        }

        markVerificationCodeIssued();

        var registrationCode = '';
        if (data.data.registration_code_debug) {
          registrationCode = data.data.registration_code_debug;
        } else if (data.data.registration_code) {
          registrationCode = data.data.registration_code;
        } else if (data.data.braze_track_payload && data.data.braze_track_payload.attributes && data.data.braze_track_payload.attributes[0]) {
          registrationCode = data.data.braze_track_payload.attributes[0].registration_code || '';
        }

        if (gateId) {
          logGateRegistryConsole(gateId, 'braze', {
            braze_track_payload: data.data.braze_track_payload || null,
            braze_response: data.data.braze_response || null,
            registration_code: registrationCode,
          });
        }

        renderPreview(preview, buildBrazePreview(intent, {
          button_press_url: built.buttonUrl,
          registration_code: registrationCode,
          expires_minutes: data.data.expires_minutes || CODE_EXPIRES_MINUTES,
          braze_track_payload: data.data.braze_track_payload || null,
          braze_response: data.data.braze_response || null,
        }));
        return data;
      })
      .catch(function (err) {
        if (gateId) {
          logGateRegistryConsole(gateId, 'error', err && err.message ? err.message : 'Network error.');
        }
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'TEST REGISTRY';
        }
        renderPreview(preview, buildBrazePreview(intent, { error: 'Network error.' }));
      });
  }

  function saveFromModalAndRedirect() {
    var payload = collectFromModal();
    mergeIntent(payload);
    var target = cfg.accountUrl || '/portal/account/';

    issueVisitorVerification({ buttonPressUrl: window.location.href.split('#')[0] })
      .finally(function () {
        window.location.href = target;
      });
  }

  function verifyVisitorCode(code) {
    var intent = readIntent();
    var fd = new FormData();
    fd.append('action', 'ihq_verify_visitor_code');
    fd.append('nonce', cfg.nonce || '');
    fd.append('intent_json', JSON.stringify(intent));
    fd.append('code', String(code || '').replace(/\D/g, ''));
    fd.append('country_iso', resolveCountryIso());

    return fetch(cfg.ajaxUrl || '/wp-admin/admin-ajax.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); });
  }

  function sendTestRegistry(options) {
    return issueVisitorVerification(options);
  }

  function maybeIssueVerificationOnPortalArrival() {
    if (!isPortalPage() || !hasCommMethods() || readVerificationCodeIssued()) {
      return;
    }
    issueVisitorVerification({ buttonPressUrl: window.location.href.split('#')[0] });
  }

  window.ihqVisitorIntentRead = readIntent;
  window.ihqVisitorIntentMerge = mergeIntent;
  window.ihqVisitorIntentCollectFromModal = collectFromModal;
  window.ihqVisitorIntentSaveFromModalAndRedirect = saveFromModalAndRedirect;
  window.ihqVisitorIntentSaveRating = saveRating;
  window.ihqVisitorIntentRefreshPreview = refreshTestRegistryPreview;
  window.ihqVisitorIntentSendTestRegistry = sendTestRegistry;
  window.ihqVisitorIntentIssueVerification = issueVisitorVerification;
  window.ihqVisitorIntentVerifyCode = verifyVisitorCode;
  window.ihqVisitorIntentHasCommMethods = hasCommMethods;
  window.ihqVisitorIntentVerificationCodeIssued = readVerificationCodeIssued;
  window.ihqVisitorIntentMarkVerificationCodeIssued = markVerificationCodeIssued;
  window.ihqOpenVisitorCommunicationModal = openCommunicationModal;

  // Legacy aliases
  window.ihqVisitorIntentRegistryBrazeSent = readVerificationCodeIssued;
  window.ihqVisitorIntentMarkRegistryBrazeSent = markVerificationCodeIssued;

  document.addEventListener('DOMContentLoaded', function () {
    refreshTestRegistryPreview();
    maybeIssueVerificationOnPortalArrival();

    var testBtn = document.getElementById('ihq-test-registry-btn');
    if (testBtn) {
      testBtn.addEventListener('click', function (e) {
        e.preventDefault();
        issueVisitorVerification({ force: true });
      });
    }
  });
})(window);
