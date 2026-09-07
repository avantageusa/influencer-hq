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
      return cfg.agent_id_guest || '';
    }
    if (cfg.is_logged_in) {
      return cfg.agent_id_default || '';
    }
    return cfg.agent_id_guest || '';
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

  var FAB_MOVED_CLASS = 'is-moved';
  var FAB_DRAGGING_CLASS = 'is-dragging';
  var FAB_POSITION_STORAGE_KEY = 'ihqConciergeFabPosition';
  var FAB_DRAG_THRESHOLD_PX = 8;
  var FAB_VIEWPORT_PADDING_PX = 8;
  var FAB_DEFAULT_SIZE_PX = 64;
  var FAB_DEFAULT_SIZE_MOBILE_PX = 56;
  var FAB_MOBILE_MAX_WIDTH_PX = 575;

  function getFabFallbackSize() {
    if (window.matchMedia && window.matchMedia('(max-width: ' + FAB_MOBILE_MAX_WIDTH_PX + 'px)').matches) {
      return FAB_DEFAULT_SIZE_MOBILE_PX;
    }
    return FAB_DEFAULT_SIZE_PX;
  }

  function readStoredFabPosition() {
    try {
      var raw = window.localStorage.getItem(FAB_POSITION_STORAGE_KEY);
      if (!raw) {
        return null;
      }
      var parsed = JSON.parse(raw);
      if (!parsed || typeof parsed.left !== 'number' || typeof parsed.top !== 'number') {
        return null;
      }
      if (!isFinite(parsed.left) || !isFinite(parsed.top)) {
        return null;
      }
      return { left: parsed.left, top: parsed.top };
    } catch (err) {
      return null;
    }
  }

  function writeStoredFabPosition(position) {
    try {
      window.localStorage.setItem(FAB_POSITION_STORAGE_KEY, JSON.stringify({
        left: position.left,
        top: position.top,
      }));
    } catch (err) {
      // Private mode / quota — drag still works for this page load.
    }
  }

  function clampFabPosition(left, top, fab) {
    var fallback = getFabFallbackSize();
    var width = fab.offsetWidth || fallback;
    var height = fab.offsetHeight || fallback;
    var maxLeft = Math.max(FAB_VIEWPORT_PADDING_PX, window.innerWidth - width - FAB_VIEWPORT_PADDING_PX);
    var maxTop = Math.max(FAB_VIEWPORT_PADDING_PX, window.innerHeight - height - FAB_VIEWPORT_PADDING_PX);
    var nextLeft = Math.min(maxLeft, Math.max(FAB_VIEWPORT_PADDING_PX, left));
    var nextTop = Math.min(maxTop, Math.max(FAB_VIEWPORT_PADDING_PX, top));
    return { left: nextLeft, top: nextTop };
  }

  function applyFabPosition(fab, left, top) {
    var clamped = clampFabPosition(left, top, fab);
    fab.classList.add(FAB_MOVED_CLASS);
    fab.style.setProperty('--ihq-fab-left', clamped.left + 'px');
    fab.style.setProperty('--ihq-fab-top', clamped.top + 'px');
    if (fab.parentNode !== document.body) {
      document.body.appendChild(fab);
    }
    return clamped;
  }

  function enableFabDrag(fab) {
    if (!fab || fab.getAttribute('data-ihq-fab-drag') === '1') {
      return;
    }
    fab.setAttribute('data-ihq-fab-drag', '1');

    var dragState = {
      pointerId: null,
      startX: 0,
      startY: 0,
      originLeft: 0,
      originTop: 0,
      hasMoved: false,
      suppressClick: false,
    };

    function resetDragState() {
      dragState.pointerId = null;
      dragState.hasMoved = false;
      fab.classList.remove(FAB_DRAGGING_CLASS);
    }

    fab.addEventListener('click', function (event) {
      if (!dragState.suppressClick) {
        return;
      }
      event.preventDefault();
      event.stopImmediatePropagation();
      dragState.suppressClick = false;
    }, true);

    fab.addEventListener('pointerdown', function (event) {
      if (event.button !== 0) {
        return;
      }
      var rect = fab.getBoundingClientRect();
      dragState.pointerId = event.pointerId;
      dragState.startX = event.clientX;
      dragState.startY = event.clientY;
      dragState.originLeft = rect.left;
      dragState.originTop = rect.top;
      dragState.hasMoved = false;
      dragState.suppressClick = false;
      fab.setPointerCapture(event.pointerId);
    });

    fab.addEventListener('pointermove', function (event) {
      if (dragState.pointerId !== event.pointerId) {
        return;
      }
      var deltaX = event.clientX - dragState.startX;
      var deltaY = event.clientY - dragState.startY;
      var distance = Math.sqrt((deltaX * deltaX) + (deltaY * deltaY));
      if (!dragState.hasMoved && distance < FAB_DRAG_THRESHOLD_PX) {
        return;
      }
      if (!dragState.hasMoved) {
        dragState.hasMoved = true;
        fab.classList.add(FAB_DRAGGING_CLASS);
      }
      if (event.cancelable) {
        event.preventDefault();
      }
      applyFabPosition(fab, dragState.originLeft + deltaX, dragState.originTop + deltaY);
    });

    function finishPointer(event) {
      if (dragState.pointerId !== event.pointerId) {
        return;
      }
      if (dragState.hasMoved) {
        var rect = fab.getBoundingClientRect();
        var saved = applyFabPosition(fab, rect.left, rect.top);
        writeStoredFabPosition(saved);
        dragState.suppressClick = true;
      }
      if (fab.hasPointerCapture && fab.hasPointerCapture(event.pointerId)) {
        fab.releasePointerCapture(event.pointerId);
      }
      resetDragState();
    }

    fab.addEventListener('pointerup', finishPointer);
    fab.addEventListener('pointercancel', finishPointer);

    window.addEventListener('resize', function () {
      if (!fab.classList.contains(FAB_MOVED_CLASS)) {
        return;
      }
      var rect = fab.getBoundingClientRect();
      var saved = applyFabPosition(fab, rect.left, rect.top);
      writeStoredFabPosition(saved);
    });

    var stored = readStoredFabPosition();
    if (stored) {
      applyFabPosition(fab, stored.left, stored.top);
    }
  }

  function initTriggers() {
    document.querySelectorAll('[data-ihq-concierge-trigger]').forEach(function (el) {
      bindTrigger(el, {});
    });
    var fab = document.getElementById('ihq-concierge-fab');
    if (fab) {
      enableFabDrag(fab);
    }
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
