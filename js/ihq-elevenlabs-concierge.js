/**
 * Executive Concierge — shared ElevenLabs ConvAI session (FAB + page triggers).
 */
(function (window) {
  'use strict';

  var cfg = window.ihqElevenLabs || {};
  var activeSession = null;
  var activeContext = null;
  var startToken = 0;
  var DEFAULT_LANG = 'en';
  var FAB_SIZE_PX = 160;
  var DRAG_THRESHOLD_PX = 6;
  var SESSION_PHASE = {
    IDLE: 'idle',
    CONNECTING: 'connecting',
    OPEN: 'open',
    CLOSING: 'closing',
  };
  var sessionPhase = SESSION_PHASE.IDLE;

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

  function isStartCurrent(token) {
    return token === startToken;
  }

  function clearSessionState(context) {
    activeSession = null;
    activeContext = null;
    sessionPhase = SESSION_PHASE.IDLE;
    resetTriggerState(context);
  }

  function endTrackedSession(session) {
    if (!session || !session.endSession) {
      return;
    }
    Promise.resolve()
      .then(function () {
        return session.endSession();
      })
      .catch(function (error) {
        console.error('Failed to end voice session', error);
      });
  }

  function endSession() {
    if (sessionPhase === SESSION_PHASE.CLOSING) {
      return;
    }
    var context = activeContext;
    var session = activeSession;
    if (!session || !session.endSession) {
      startToken += 1;
      activeSession = null;
      activeContext = null;
      sessionPhase = SESSION_PHASE.IDLE;
      resetTriggerState(context);
      return;
    }
    sessionPhase = SESSION_PHASE.CLOSING;
    Promise.resolve()
      .then(function () {
        return session.endSession();
      })
      .then(function () {
        if (activeSession !== session) {
          return;
        }
        startToken += 1;
        activeSession = null;
        activeContext = null;
        sessionPhase = SESSION_PHASE.IDLE;
        resetTriggerState(context);
      })
      .catch(function (error) {
        console.error('Failed to end voice session', error);
        if (activeSession !== session) {
          return;
        }
        sessionPhase = SESSION_PHASE.OPEN;
      });
  }

  function startSession(context) {
    if (sessionPhase !== SESSION_PHASE.IDLE) {
      return Promise.resolve();
    }
    if (typeof window.ElevenLabsClient === 'undefined' || !window.ElevenLabsClient.Conversation) {
      showInlineError(context, cfg.error_unavailable || 'Concierge is unavailable.');
      resetTriggerState(context);
      return Promise.resolve();
    }

    var token = startToken;
    var agentId = resolveAgentId(context.agentMode);
    sessionPhase = SESSION_PHASE.CONNECTING;
    activeContext = context;
    setConnectingState(context);
    hideInlineError(context);

    return fetch(cfg.ajax_url || '/wp-admin/admin-ajax.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: buildSignedUrlBody(agentId),
    })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (!isStartCurrent(token)) {
          return;
        }
        if (!data.success || !data.data || !data.data.signed_url) {
          showInlineError(context, cfg.error_connect || 'Could not connect. Please try again.');
          clearSessionState(context);
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
            if (!isStartCurrent(token)) {
              return;
            }
            sessionPhase = SESSION_PHASE.OPEN;
            setConnectedState(context);
          },
          onDisconnect: function () {
            if (!isStartCurrent(token)) {
              return;
            }
            clearSessionState(context);
          },
          onError: function () {
            if (!isStartCurrent(token)) {
              return;
            }
            showInlineError(context, cfg.error_connect || 'Connection error. Please try again.');
            if (activeSession) {
              endSession();
              return;
            }
            startToken += 1;
            clearSessionState(context);
          },
          onMessage: function () {},
        }).then(function (session) {
          if (!isStartCurrent(token)) {
            endTrackedSession(session);
            return;
          }
          activeSession = session;
          activeContext = context;
          sessionPhase = SESSION_PHASE.OPEN;
        }).catch(function () {
          if (!isStartCurrent(token)) {
            return;
          }
          showInlineError(context, cfg.error_connect || 'Could not start conversation. Please try again.');
          clearSessionState(context);
        });
      })
      .catch(function () {
        if (!isStartCurrent(token)) {
          return;
        }
        showInlineError(context, cfg.error_connect || 'Connection error. Please try again.');
        clearSessionState(context);
      });
  }

  function toggleFromContext(context) {
    if (sessionPhase !== SESSION_PHASE.IDLE) {
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
      toggleFromContext(context);
    });
  }

  function bindAll(selector, options) {
    document.querySelectorAll(selector).forEach(function (el) {
      bindTrigger(el, options);
    });
  }

  function fabDefaultPosition() {
    return {
      left: window.innerWidth - FAB_SIZE_PX - 20,
      top: window.innerHeight - FAB_SIZE_PX - 20,
    };
  }

  function clampFabPosition(left, top, fab) {
    var maxLeft = Math.max(0, window.innerWidth - fab.offsetWidth);
    var maxTop = Math.max(0, window.innerHeight - fab.offsetHeight);
    return {
      left: Math.min(Math.max(0, left), maxLeft),
      top: Math.min(Math.max(0, top), maxTop),
    };
  }

  function placeFab(fab, left, top) {
    fab.classList.add('is-moved');
    if (fab.parentNode !== document.body) {
      document.body.appendChild(fab);
    }
    var next = clampFabPosition(left, top, fab);
    fab.style.position = 'fixed';
    fab.style.margin = '0';
    fab.style.width = FAB_SIZE_PX + 'px';
    fab.style.height = FAB_SIZE_PX + 'px';
    fab.style.right = 'auto';
    fab.style.bottom = 'auto';
    fab.style.left = next.left + 'px';
    fab.style.top = next.top + 'px';
  }

  function enableFabDrag(fab) {
    var pointerId = null;
    var startX = 0;
    var startY = 0;
    var originLeft = 0;
    var originTop = 0;
    var dragged = false;

    fab.addEventListener('pointerdown', function (event) {
      if (event.button !== 0) {
        return;
      }
      event.preventDefault();
      pointerId = event.pointerId;
      dragged = false;
      var rect = fab.getBoundingClientRect();
      startX = event.clientX;
      startY = event.clientY;
      originLeft = rect.left;
      originTop = rect.top;
      if (fab.setPointerCapture) {
        fab.setPointerCapture(pointerId);
      }
    });

    fab.addEventListener('pointermove', function (event) {
      if (pointerId !== event.pointerId) {
        return;
      }
      var deltaX = event.clientX - startX;
      var deltaY = event.clientY - startY;
      if (!dragged && Math.abs(deltaX) < DRAG_THRESHOLD_PX && Math.abs(deltaY) < DRAG_THRESHOLD_PX) {
        return;
      }
      if (!dragged) {
        dragged = true;
        placeFab(fab, originLeft, originTop);
      }
      var next = clampFabPosition(originLeft + deltaX, originTop + deltaY, fab);
      fab.style.left = next.left + 'px';
      fab.style.top = next.top + 'px';
    });

    function endPointer(event, canceled) {
      if (pointerId !== event.pointerId) {
        return;
      }
      pointerId = null;
      if (canceled) {
        dragged = false;
        fab.removeAttribute('data-ihq-dragged');
        return;
      }
      if (dragged) {
        fab.setAttribute('data-ihq-dragged', '1');
      }
    }

    fab.addEventListener('pointerup', function (event) {
      endPointer(event, false);
    });
    fab.addEventListener('pointercancel', function (event) {
      endPointer(event, true);
    });

    fab.addEventListener('click', function (event) {
      if (fab.getAttribute('data-ihq-dragged') !== '1') {
        return;
      }
      event.preventDefault();
      event.stopImmediatePropagation();
      fab.removeAttribute('data-ihq-dragged');
    }, true);
  }

  function clampFabToViewport() {
    var fab = document.getElementById('ihq-concierge-fab');
    if (!fab || !fab.classList.contains('is-moved')) {
      return;
    }
    var left = parseFloat(fab.style.left);
    var top = parseFloat(fab.style.top);
    if (isNaN(left) || isNaN(top)) {
      return;
    }
    placeFab(fab, left, top);
  }

  function initTriggers() {
    var fab = document.getElementById('ihq-concierge-fab');
    if (fab) {
      var start = fabDefaultPosition();
      placeFab(fab, start.left, start.top);
      enableFabDrag(fab);
    }
    window.addEventListener('resize', clampFabToViewport);
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
