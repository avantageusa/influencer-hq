/**
 * Portal registry gates — guest-only privileged-action interceptors (ENGR-5495).
 */
(function (window) {
  'use strict';

  var cfg = window.IHQ_REGISTRY_GATES || {};
  if (cfg.isLoggedIn) {
    return;
  }

  var MESSAGE = cfg.message || 'Please check your preferred method of communication and enter the 6-digit code we sent you.';
  var SUBMIT_LABEL = cfg.submitLabel || 'Continue';
  var verifyInFlight = false;

  function hideNotice() {
    var backdrop = document.getElementById('ihq-registry-gate-backdrop');
    var el = document.getElementById('ihq-registry-gate-notice');
    if (backdrop) {
      backdrop.classList.remove('is-visible');
    }
    if (el) {
      el.classList.remove('is-visible');
      el.classList.remove('is-error');
      var err = el.querySelector('.ihq-registry-gate-notice-error');
      if (err) {
        err.textContent = '';
        err.hidden = true;
      }
      var input = el.querySelector('.ihq-registry-gate-code-input');
      if (input) {
        input.value = '';
      }
    }
    document.body.classList.remove('ihq-registry-gate-open');
  }

  function showCodeError(message) {
    var el = document.getElementById('ihq-registry-gate-notice');
    if (!el) {
      return;
    }
    var err = el.querySelector('.ihq-registry-gate-notice-error');
    if (err) {
      err.textContent = message || '';
      err.hidden = !message;
    }
    el.classList.toggle('is-error', Boolean(message));
  }

  function submitVerificationCode() {
    if (verifyInFlight) {
      return;
    }

    var el = document.getElementById('ihq-registry-gate-notice');
    var input = el ? el.querySelector('.ihq-registry-gate-code-input') : null;
    var submitBtn = el ? el.querySelector('.ihq-registry-gate-code-submit') : null;
    var raw = input ? String(input.value || '').replace(/\D/g, '') : '';

    showCodeError('');

    if (raw.length !== 6) {
      showCodeError('Enter the 6-digit code we sent you.');
      if (input) {
        input.focus();
      }
      return;
    }

    if (typeof window.ihqVisitorIntentVerifyCode !== 'function') {
      showCodeError('Verification is unavailable. Please refresh and try again.');
      return;
    }

    verifyInFlight = true;
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Verifying…';
    }

    window.ihqVisitorIntentVerifyCode(raw)
      .then(function (data) {
        if (!data || !data.success) {
          var msg = (data && data.data && data.data.message) ? data.data.message : 'That code did not work. Try again.';
          showCodeError(msg);
          return;
        }
        var redirectUrl = (data.data && data.data.redirect_url) ? data.data.redirect_url : window.location.href;
        window.location.href = redirectUrl;
      })
      .catch(function () {
        showCodeError('Network error. Please try again.');
      })
      .finally(function () {
        verifyInFlight = false;
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = SUBMIT_LABEL;
        }
      });
  }

  function showCodeEntry() {
    var backdrop = document.getElementById('ihq-registry-gate-backdrop');
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.id = 'ihq-registry-gate-backdrop';
      backdrop.className = 'ihq-registry-gate-backdrop';
      backdrop.setAttribute('aria-hidden', 'true');
      backdrop.addEventListener('click', hideNotice);
      document.body.appendChild(backdrop);
    }

    var el = document.getElementById('ihq-registry-gate-notice');
    if (!el) {
      el = document.createElement('div');
      el.id = 'ihq-registry-gate-notice';
      el.className = 'ihq-registry-gate-notice';
      el.setAttribute('role', 'alertdialog');
      el.setAttribute('aria-modal', 'true');
      el.setAttribute('aria-live', 'polite');

      var text = document.createElement('p');
      text.className = 'ihq-registry-gate-notice-text';
      el.appendChild(text);

      var form = document.createElement('div');
      form.className = 'ihq-registry-gate-code-form';

      var input = document.createElement('input');
      input.type = 'text';
      input.inputMode = 'numeric';
      input.pattern = '[0-9]*';
      input.maxLength = 6;
      input.autocomplete = 'one-time-code';
      input.className = 'ihq-registry-gate-code-input';
      input.setAttribute('aria-label', '6-digit verification code');
      input.placeholder = '000000';
      form.appendChild(input);

      var submitBtn = document.createElement('button');
      submitBtn.type = 'button';
      submitBtn.className = 'ihq-registry-gate-code-submit';
      submitBtn.textContent = SUBMIT_LABEL;
      submitBtn.addEventListener('click', submitVerificationCode);
      form.appendChild(submitBtn);

      var err = document.createElement('p');
      err.className = 'ihq-registry-gate-notice-error';
      err.hidden = true;
      form.appendChild(err);

      el.appendChild(form);

      input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
          event.preventDefault();
          submitVerificationCode();
        }
      });

      input.addEventListener('input', function () {
        input.value = input.value.replace(/\D/g, '').slice(0, 6);
        showCodeError('');
      });

      var closeBtn = document.createElement('button');
      closeBtn.type = 'button';
      closeBtn.className = 'ihq-registry-gate-notice-close';
      closeBtn.setAttribute('aria-label', 'Close');
      closeBtn.textContent = '\u2715';
      closeBtn.addEventListener('click', hideNotice);
      el.appendChild(closeBtn);

      document.body.appendChild(el);
    }

    var textEl = el.querySelector('.ihq-registry-gate-notice-text');
    if (textEl) {
      textEl.textContent = MESSAGE;
    }

    var inputEl = el.querySelector('.ihq-registry-gate-code-input');
    if (inputEl) {
      inputEl.value = '';
    }
    showCodeError('');

    backdrop.classList.add('is-visible');
    backdrop.setAttribute('aria-hidden', 'false');
    el.classList.add('is-visible');
    document.body.classList.add('ihq-registry-gate-open');

    if (inputEl) {
      window.setTimeout(function () {
        inputEl.focus();
      }, 80);
    }
  }

  function hasCommMethods() {
    return typeof window.ihqVisitorIntentHasCommMethods === 'function'
      && window.ihqVisitorIntentHasCommMethods();
  }

  function brazeAlreadySent() {
    return typeof window.ihqVisitorIntentVerificationCodeIssued === 'function'
      && window.ihqVisitorIntentVerificationCodeIssued();
  }

  function openCommunicationModal() {
    if (typeof window.ihqOpenVisitorCommunicationModal === 'function') {
      window.ihqOpenVisitorCommunicationModal();
    }
  }

  function collapseCompetitionDropdown(selector) {
    var dropdown = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!dropdown) {
      return;
    }

    dropdown.classList.add('ihq-gate-collapsed');
    var body = dropdown.querySelector('.competition-dropdown-body');
    if (body) {
      body.style.display = 'none';
    }
  }

  function collapseEquityAttributionCard() {
    document.querySelectorAll('.equity-card').forEach(function (card) {
      card.classList.add('ihq-gate-collapsed');

      var body = card.querySelector('.equity-card-body');
      if (body) {
        body.hidden = true;
        body.style.display = 'none';
      }

      var header = card.querySelector('.equity-card-header');
      if (header) {
        header.setAttribute('aria-expanded', 'false');
        var toggle = header.querySelector('.equity-card-toggle');
        if (toggle) {
          toggle.textContent = '\u25BE';
        }
      }
    });
  }

  function maintainCollapsedForGate(gateId) {
    if (gateId === 'equity_attribution') {
      collapseEquityAttributionCard();
      return;
    }

    if (gateId === 'private_see_current_challenge') {
      collapsePrivateAccordion('cpcCollapse1');
      return;
    }

    if (gateId === 'private_create_new_challenge') {
      collapsePrivateAccordion('cpcCollapse2');
      return;
    }

    if (gateId === 'world_see_my_results') {
      collapseCompetitionDropdown('#world-leaderboards');
      return;
    }

    if (gateId === 'world_see_my_medals') {
      var worldTab = document.getElementById('world-tab');
      if (!worldTab) {
        return;
      }

      worldTab.querySelectorAll('.competition-dropdown:not(#world-leaderboards)').forEach(function (dropdown) {
        collapseCompetitionDropdown(dropdown);
      });
      return;
    }

    if (gateId === 'profile_social_media') {
      collapseProfileSection('socialMediaBody', 'socialMediaArrow');
      return;
    }

    if (gateId === 'profile_celebrity_followers_leagues') {
      collapseProfileSection('celebLeaguesBody', 'celebLeaguesArrow');
      return;
    }

    if (gateId === 'profile_international_league_team') {
      collapseProfileSection('intlLeagueBody', 'intlLeagueArrow');
      return;
    }

    if (gateId === 'profile_username_or_contact') {
      collapseProfileSection('contactBody', 'contactArrow');
    }
  }

  function scheduleCollapsedReassert(gateId) {
    maintainCollapsedForGate(gateId);
    window.requestAnimationFrame(function () {
      maintainCollapsedForGate(gateId);
    });
    window.setTimeout(function () {
      maintainCollapsedForGate(gateId);
    }, 0);
  }

  function disableGuestBootstrapCollapseTriggers() {
    document.querySelectorAll('#cpcAccordion1 > .cpc-accordion-header, #cpcAccordion2 > .cpc-accordion-header').forEach(function (header) {
      if (header.getAttribute('data-bs-toggle')) {
        header.setAttribute('data-ihq-bs-toggle', header.getAttribute('data-bs-toggle'));
        header.removeAttribute('data-bs-toggle');
      }
      if (header.getAttribute('data-bs-target')) {
        header.setAttribute('data-ihq-bs-target', header.getAttribute('data-bs-target'));
        header.removeAttribute('data-bs-target');
      }
    });
  }

  function blockGuestBootstrapCollapsePanels() {
    ['cpcCollapse1', 'cpcCollapse2'].forEach(function (panelId) {
      var panel = document.getElementById(panelId);
      if (!panel) {
        return;
      }

      panel.addEventListener('show.bs.collapse', function (event) {
        event.preventDefault();
        collapsePrivateAccordion(panelId);
      });
    });
  }

  /**
   * @param {string} gateId
   * @param {Event} [event]
   */
  function triggerGate(gateId, event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
      if (typeof event.stopImmediatePropagation === 'function') {
        event.stopImmediatePropagation();
      }
    }

    scheduleCollapsedReassert(gateId);

    if (window.console) {
      console.log('[IHQ Registry Gate] triggered:', gateId);
    }

    if (typeof window.ihqVisitorIntentMerge === 'function') {
      window.ihqVisitorIntentMerge({
        gate_id: gateId,
        captured_from: 'portal_gate',
        source_url: window.location.href,
      });
    }

    if (!hasCommMethods()) {
      if (window.console) {
        console.log('[IHQ Registry Gate] no comm method — opening modal:', gateId);
      }
      openCommunicationModal();
      return;
    }

    showCodeEntry();

    if (!brazeAlreadySent() && typeof window.ihqVisitorIntentIssueVerification === 'function') {
      window.ihqVisitorIntentIssueVerification({ gateId: gateId });
    }
  }

  /**
   * @param {string} gateId
   * @param {string} selector
   * @param {string[]} [events]
   */
  function bindGate(gateId, selector, events) {
    var types = events || ['click'];
    types.forEach(function (eventName) {
      document.addEventListener(
        eventName,
        function (event) {
          if (!event.target || typeof event.target.closest !== 'function') {
            return;
          }
          if (!event.target.closest(selector)) {
            return;
          }
          triggerGate(gateId, event);
        },
        true
      );
    });
  }

  function collapseProfileSection(bodyId, arrowId) {
    var body = document.getElementById(bodyId);
    if (body) {
      body.style.display = 'none';
    }
    if (arrowId) {
      var arrow = document.getElementById(arrowId);
      if (arrow) {
        arrow.textContent = '\u25BC';
      }
    }
  }

  function collapsePrivateAccordion(collapseId) {
    var panel = document.getElementById(collapseId);
    if (!panel) {
      return;
    }

    panel.classList.remove('show', 'collapsing');
    panel.classList.add('ihq-gate-force-hidden');
    panel.style.display = 'none';

    var accordion = panel.closest('.cpc-accordion');
    if (!accordion) {
      return;
    }

    var header = accordion.querySelector('.cpc-accordion-header');
    if (header) {
      header.classList.add('collapsed');
      header.setAttribute('aria-expanded', 'false');
    }
  }

  function initCollapsedSections() {
    disableGuestBootstrapCollapseTriggers();
    blockGuestBootstrapCollapsePanels();

    collapseEquityAttributionCard();

    var worldTab = document.getElementById('world-tab');
    if (worldTab) {
      worldTab.querySelectorAll('.competition-dropdown').forEach(function (dropdown) {
        collapseCompetitionDropdown(dropdown);
      });
    }

    collapsePrivateAccordion('cpcCollapse1');
    collapsePrivateAccordion('cpcCollapse2');

    collapseProfileSection('socialMediaBody', 'socialMediaArrow');
    collapseProfileSection('celebLeaguesBody', 'celebLeaguesArrow');
    collapseProfileSection('intlLeagueBody', 'intlLeagueArrow');
    collapseProfileSection('contactBody', 'contactArrow');
  }

  function registerGates() {
    bindGate('equity_attribution', '.equity-card .equity-card-header');

    bindGate('private_see_current_challenge', '#cpcAccordion1 > .cpc-accordion-header');
    bindGate('private_create_new_challenge', '#cpcAccordion2 > .cpc-accordion-header');

    bindGate('world_see_my_results', '#world-leaderboards .competition-dropdown-header');
    bindGate(
      'world_see_my_medals',
      '#world-tab .competition-dropdown:not(#world-leaderboards) .competition-dropdown-header'
    );

    bindGate('leagues_celebrity_followers', '#leagues-tab select.celeb-select[data-category]', ['mousedown', 'focusin']);
    bindGate('leagues_international_team', '#leagues-tab #intlLeagueSelect', ['mousedown', 'focusin']);

    bindGate(
      'live_request_appearance',
      '#live-request-form input, #live-request-form select, #live-request-form textarea, #live-request-form button, #live-calendar-open-btn',
      ['mousedown', 'focusin', 'click']
    );
    bindGate(
      'kick_broadcasting_schedule',
      '#kick-schedule-form input, #kick-schedule-form select, #kick-schedule-form button',
      ['mousedown', 'focusin', 'click']
    );

    bindGate(
      'profile_youtube_video',
      '#ihq_gameplay_video_url, button[name="ihq_gameplay_video_submit"]',
      ['mousedown', 'focusin', 'click']
    );
    bindGate(
      'profile_account_information',
      '.sett-editable[data-group="account"], #portal-username-input, #portal-username-save-btn, .sett-timezone-select[data-group="account"], #sett-avatar-btn',
      ['mousedown', 'focusin', 'click']
    );

    bindGate('profile_social_media', '#socialMediaHead');
    bindGate('profile_celebrity_followers_leagues', '#celebLeaguesHead');
    bindGate('profile_international_league_team', '#intlLeagueHead');
    bindGate('profile_username_or_contact', '#contactHead');

    bindGate('header_hamburger_menu', '#hamburgerMenuBtn');
    bindGate('header_login', '#portalHeaderOpenLogin, .portal-header-auth-trigger[data-auth-tab="login"]');
  }

  function bootRegistryGates() {
    initCollapsedSections();
    registerGates();
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      hideNotice();
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootRegistryGates);
  } else {
    bootRegistryGates();
  }
})(window);
