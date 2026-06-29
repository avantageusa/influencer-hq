/**
 * Conversation modal — portal embed (same step-1 UI as page-lander).
 */
(function (window) {
  'use strict';

  var modalCfg = window.IHQ_CONVERSATION_MODAL || {};

  function isGateMode() {
    return Boolean(modalCfg.gateMode || window.ihqConversationModalOpenedFromGate);
  }

  function clearGateMode() {
    window.ihqConversationModalOpenedFromGate = false;
    modalCfg.gateMode = false;
  }

  function ihqGetModalCommCheckboxes() {
    return document.querySelectorAll('#modal-comm-pick .modal-comm-option input[type=checkbox]');
  }

  function syncModalCommCardVisual() {
    ihqGetModalCommCheckboxes().forEach(function (box) {
      var row = box.closest('.modal-comm-option');
      if (row) {
        row.classList.toggle('is-on', box.checked);
      }
    });
  }

  function ihqClearModalCommErr() {
    var pick = document.getElementById('modal-comm-pick');
    var err = document.getElementById('modal-comm-err');
    if (pick) {
      pick.classList.remove('field-error');
    }
    if (err) {
      err.textContent = '';
      err.classList.remove('is-visible');
    }
  }

  function ihqShowModalCommErr(msg) {
    var pick = document.getElementById('modal-comm-pick');
    var err = document.getElementById('modal-comm-err');
    if (pick) {
      pick.classList.add('field-error');
    }
    if (err) {
      err.textContent = msg || '';
      err.classList.toggle('is-visible', Boolean(msg));
    }
  }

  function ihqResetModalCommMethods() {
    ihqGetModalCommCheckboxes().forEach(function (box) {
      box.checked = false;
      var key = box.getAttribute('data-comm-key');
      var entry = key ? document.getElementById('modal-comm-entry-' + key) : null;
      var input = key ? document.getElementById('modal-comm-input-' + key) : null;
      if (entry) {
        entry.hidden = true;
      }
      if (input) {
        input.value = '';
      }
    });
    syncModalCommCardVisual();
    ihqClearModalCommErr();
  }

  function ihqHideModalCommThanks() {
    var thanks = document.getElementById('modal-comm-thanks');
    var body = document.getElementById('modal-comm-form-body');
    if (thanks) {
      thanks.hidden = true;
    }
    if (body) {
      body.hidden = false;
    }
  }

  function ihqResetModalSocialPlatforms() {
    document.querySelectorAll('.social-grid-item.is-selected').forEach(function (btn) {
      btn.classList.remove('is-selected');
      btn.setAttribute('aria-pressed', 'false');
    });
    document.querySelectorAll('.social-input-row').forEach(function (row) {
      row.hidden = true;
      var inp = row.querySelector('input.social-handle-input');
      if (inp) {
        inp.value = '';
      }
    });
  }

  function ihqResetMainConversationModal() {
    ihqResetModalCommMethods();
    ihqHideModalCommThanks();
    ihqResetModalSocialPlatforms();
    document.querySelectorAll('.comp-card.sel').forEach(function (card) {
      card.classList.remove('sel');
    });
  }

  function clearFieldErrors() {
    document.querySelectorAll('.field-error').forEach(function (el) {
      el.classList.remove('field-error');
    });
  }

  function showFieldError(el) {
    if (!el) {
      return;
    }
    el.classList.add('field-error');
    window.setTimeout(function () {
      el.classList.remove('field-error');
    }, 1200);
  }

  function validateModalStep1Communication() {
    var checked = [];
    ihqGetModalCommCheckboxes().forEach(function (box) {
      if (box.checked) {
        checked.push(box);
      }
    });
    if (!checked.length) {
      ihqShowModalCommErr('Please pick a method of communication');
      return false;
    }
    var missingInput = false;
    checked.forEach(function (box) {
      var key = box.getAttribute('data-comm-key');
      var input = key ? document.getElementById('modal-comm-input-' + key) : null;
      var entry = key ? document.getElementById('modal-comm-entry-' + key) : null;
      if (!input || !input.value.trim()) {
        missingInput = true;
        if (entry) {
          showFieldError(entry);
        }
      }
    });
    if (missingInput) {
      return false;
    }
    ihqClearModalCommErr();
    return true;
  }

  function saveVisitorIntentFromModal() {
    if (typeof window.ihqVisitorIntentMerge !== 'function' || typeof window.ihqVisitorIntentCollectFromModal !== 'function') {
      return;
    }
    window.ihqVisitorIntentMerge(window.ihqVisitorIntentCollectFromModal());
  }

  function openModal() {
    var modal = document.getElementById('mainModal');
    if (!modal) {
      return;
    }
    ihqResetMainConversationModal();
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    var modal = document.getElementById('mainModal');
    clearGateMode();
    if (!modal) {
      return;
    }
    ihqResetMainConversationModal();
    modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  function onModalSubmit() {
    clearFieldErrors();
    ihqClearModalCommErr();
    if (!validateModalStep1Communication()) {
      var modalEl = document.querySelector('#mainModal .modal');
      if (modalEl) {
        modalEl.scrollTo({ top: 0, behavior: 'smooth' });
      }
      return;
    }

    if (isGateMode()) {
      saveVisitorIntentFromModal();
      if (typeof window.ihqVisitorIntentIssueVerification === 'function') {
        window.ihqVisitorIntentIssueVerification({ buttonPressUrl: window.location.href.split('#')[0] });
      }
      closeModal();
      return;
    }

    saveVisitorIntentFromModal();
    if (typeof window.ihqVisitorIntentIssueVerification === 'function') {
      window.ihqVisitorIntentIssueVerification({ buttonPressUrl: window.location.href.split('#')[0] });
    }
    closeModal();
  }

  function pickComp(id) {
    var el = document.getElementById(id);
    if (!el) {
      return;
    }
    el.classList.toggle('sel');
  }

  function ihqToggleSocialPlatform(key) {
    var btn = document.getElementById('social-grid-' + key);
    var row = document.getElementById('social-entry-' + key);
    if (!btn || !row) {
      return;
    }
    var isOn = !btn.classList.contains('is-selected');
    btn.classList.toggle('is-selected', isOn);
    btn.setAttribute('aria-pressed', isOn ? 'true' : 'false');
    row.hidden = !isOn;
    if (!isOn) {
      var cleared = row.querySelector('input.social-handle-input');
      if (cleared) {
        cleared.value = '';
      }
      return;
    }
    var focusInput = row.querySelector('input.social-handle-input');
    if (focusInput) {
      window.setTimeout(function () {
        focusInput.focus();
      }, 50);
    }
  }

  window.openModal = openModal;
  window.closeModal = closeModal;
  window.onModalSubmit = onModalSubmit;
  window.pickComp = pickComp;
  window.ihqToggleSocialPlatform = ihqToggleSocialPlatform;

  document.addEventListener('DOMContentLoaded', function () {
    var mainModal = document.getElementById('mainModal');
    if (!mainModal) {
      return;
    }

    ihqGetModalCommCheckboxes().forEach(function (box) {
      box.addEventListener('change', function () {
        var key = box.getAttribute('data-comm-key');
        var entry = key ? document.getElementById('modal-comm-entry-' + key) : null;
        var input = key ? document.getElementById('modal-comm-input-' + key) : null;
        if (entry) {
          entry.hidden = !box.checked;
        }
        if (!box.checked && input) {
          input.value = '';
        }
        if (box.checked && input) {
          window.setTimeout(function () {
            input.focus();
          }, 50);
        }
        syncModalCommCardVisual();
        ihqClearModalCommErr();
      });
    });
    syncModalCommCardVisual();

    mainModal.addEventListener('click', function (event) {
      if (event.target === mainModal) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && mainModal.classList.contains('open')) {
        closeModal();
      }
    });
  });
})(window);
