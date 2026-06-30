/**
 * Executive Concierge — shared ElevenLabs ConvAI session (FAB + page triggers).
 */
(function (window) {
  'use strict';

  var cfg = window.ihqElevenLabs || {};
  var activeSession = null;
  var activeContext = null;
  var DEFAULT_LANG = 'en';

  function pagePrimaryLang() {
    var raw = document.documentElement ? document.documentElement.getAttribute('lang') : '';
    if (typeof raw !== 'string') {
      raw = '';
    }
    raw = raw.trim();
    if (!raw) {
      return DEFAULT_LANG;
    }
    var primary = raw.split(/[-_\s]/)[0].toLowerCase();
    if (!/^[a-z]{2,10}$/.test(primary)) {
      return DEFAULT_LANG;
    }
    return primary;
  }

  /**
   * @param {'auto'|'default'|'guest'} agentMode
   * @return {string}
   */
  function resolveAgentId(agentMode) {
    if (agentMode === 'default') {
      return cfg.agent_id_default || '';
    }
    if (agentMode === 'guest') {
      return cfg.agent_id_guest || cfg.agent_id_portal_home_claude || '';
    }
    if (cfg.is_logged_in) {
      return cfg.agent_id_default || '';
    }
    return cfg.agent_id_guest || cfg.agent_id_portal_home_claude || '';
  }

  function buildSignedUrlBody(agentId) {
    var parts = [
      'action=ihq_elevenlabs_signed_url',
      'nonce=' + encodeURIComponent(cfg.nonce || ''),
    ];
    if (agentId) {
      parts.push('agent_id=' + encodeURIComponent(agentId));
    }
    return parts.join('&');
  }

  function getSyncElements(context) {
    if (!context || !context.syncSelector) {
      return context && context.triggerEl ? [context.triggerEl] : [];
    }
    return Array.prototype.slice.call(document.querySelectorAll(context.syncSelector));
  }

  function isFabElement(el) {
    return Boolean(el && el.classList && el.classList.contains('ihq-concierge-fab'));
  }

  function setConnectingState(context) {
    var elements = getSyncElements(context);
    elements.forEach(function (el) {
      if (isFabElement(el)) {
        el.classList.add('is-connecting');
        el.classList.remove('is-active', 'is-error');
        return;
      }
      el.textContent = cfg.label_connecting || 'Connecting…';
      el.style.pointerEvents = 'none';
    });
  }

  function setConnectedState(context) {
    var elements = getSyncElements(context);
    elements.forEach(function (el) {
      if (isFabElement(el)) {
        el.classList.remove('is-connecting', 'is-error');
        el.classList.add('is-active');
        el.setAttribute('aria-pressed', 'true');
        return;
      }
      el.textContent = cfg.label_end_talk || 'End Talk';
      el.style.pointerEvents = '';
    });
  }

  function resetTriggerState(context) {
    if (!context) {
      return;
    }
    var elements = getSyncElements(context);
    elements.forEach(function (el, index) {
      if (isFabElement(el)) {
        el.classList.remove('is-connecting', 'is-active', 'is-error');
        el.setAttribute('aria-pressed', 'false');
        return;
      }
      var original = context.originalTexts && context.originalTexts[index]
        ? context.originalTexts[index]
        : (context.originalTexts && context.originalTexts[0]) || '';
      el.textContent = original;
      el.style.pointerEvents = '';
    });
    hideInlineError(context);
  }

  function showInlineError(context, message) {
    if (!context || !context.triggerEl || isFabElement(context.triggerEl)) {
      if (context && context.triggerEl && isFabElement(context.triggerEl)) {
        context.triggerEl.classList.add('is-error');
      }
      return;
    }
    if (!context.errorEl) {
      context.errorEl = document.createElement('p');
      context.errorEl.className = 'ihq-concierge-inline-error';
      context.errorEl.style.cssText = 'color:#f85149;font-size:.85rem;text-align:center;margin-top:8px;';
    }
    context.errorEl.textContent = message;
    var anchor = context.triggerEl;
    if (context.errorEl.parentNode !== anchor.parentNode) {
      anchor.parentNode.insertBefore(context.errorEl, anchor.nextSibling);
    }
    context.errorEl.style.display = 'block';
  }

  function hideInlineError(context) {
    if (context && context.errorEl) {
      context.errorEl.style.display = 'none';
    }
    if (context && context.triggerEl && isFabElement(context.triggerEl)) {
      context.triggerEl.classList.remove('is-error');
    }
  }

  function endSession() {
    if (activeSession) {
      activeSession.endSession();
    }
  }

  function startSession(context) {
    if (typeof window.ElevenLabsClient === 'undefined' || !window.ElevenLabsClient.Conversation) {
      showInlineError(context, cfg.error_unavailable || 'Concierge is unavailable.');
      resetTriggerState(context);
      return Promise.resolve();
    }

    var agentId = resolveAgentId(context.agentMode);
    setConnectingState(context);
    hideInlineError(context);

    return fetch(cfg.ajax_url || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: buildSignedUrlBody(agentId),
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!data.success || !data.data || !data.data.signed_url) {
          showInlineError(context, cfg.error_connect || 'Could not connect. Please try again.');
          resetTriggerState(context);
          return;
        }

        return window.ElevenLabsClient.Conversation.startSession({
          signedUrl: data.data.signed_url,
          overrides: {
            agent: {
              language: pagePrimaryLang(),
            },
          },
          onConnect: function () {
            setConnectedState(context);
          },
          onDisconnect: function () {
            activeSession = null;
            activeContext = null;
            resetTriggerState(context);
          },
          onError: function () {
            showInlineError(context, cfg.error_connect || 'Connection error. Please try again.');
            activeSession = null;
            activeContext = null;
            resetTriggerState(context);
          },
          onMessage: function () {},
        }).then(function (session) {
          activeSession = session;
          activeContext = context;
        }).catch(function () {
          showInlineError(context, cfg.error_connect || 'Could not start conversation. Please try again.');
          activeSession = null;
          activeContext = null;
          resetTriggerState(context);
        });
      })
      .catch(function () {
        showInlineError(context, cfg.error_connect || 'Connection error. Please try again.');
        activeSession = null;
        activeContext = null;
        resetTriggerState(context);
      });
  }

  function toggleFromContext(context) {
    if (activeSession) {
      endSession();
      return;
    }
    startSession(context);
  }

  function bindTrigger(el, options) {
    if (!el) {
      return;
    }

    options = options || {};
    var agentMode = options.agentMode || el.getAttribute('data-ihq-concierge-agent') || 'auto';
    var syncSelector = options.syncSelector || el.getAttribute('data-ihq-concierge-sync') || '';
    var syncElements = syncSelector ? getSyncElements({ syncSelector: syncSelector }) : [el];
    var originalTexts = syncElements.map(function (node) { return node.textContent; });

    var context = {
      triggerEl: el,
      agentMode: agentMode,
      syncSelector: syncSelector,
      originalTexts: originalTexts,
      errorEl: null,
    };

    el.addEventListener('click', function (event) {
      event.preventDefault();
      if (activeSession && activeContext) {
        endSession();
        return;
      }
      toggleFromContext(context);
    });
  }

  function bindAll(selector, options) {
    document.querySelectorAll(selector).forEach(function (el) {
      bindTrigger(el, options);
    });
  }

  function initTriggers() {
    document.querySelectorAll('[data-ihq-concierge-trigger]').forEach(function (el) {
      bindTrigger(el, {});
    });
  }

  window.ihqConcierge = {
    bindTrigger: bindTrigger,
    bindAll: bindAll,
    toggle: toggleFromContext,
    endSession: endSession,
    resolveAgentId: resolveAgentId,
    getActiveSession: function () { return activeSession; },
  };

  document.addEventListener('DOMContentLoaded', initTriggers);
})(window);
