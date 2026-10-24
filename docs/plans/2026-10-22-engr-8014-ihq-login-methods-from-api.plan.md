---
name: IHQ login shows and accepts only the methods influencerhq-api lists as on
overview: >
  IHQ's sign-in methods are hard-coded in the theme. Read influencerhq-api
  GET /v1/channels/methods server to server (cached 30 seconds, last good answer
  kept on a failed call), hide a switched-off method from the auth modal and the
  Portal Login page, and refuse it in the email-code and Telegram-widget AJAX
  handlers with 403 method_disabled and "<method> isn't available right now".
  An absent feature ({"methods":null}) or a failed call with no earlier answer
  keeps today's lists. The conversation-modal and AI Coach channel lists are out
  of scope.
todos:
  - id: reader
    content: inc/ihq-channel-methods.php — fetch, parse, 30 s cache, last good answer, allowed(), list filter, refusal payload
    status: completed
  - id: handlers
    content: Refuse a switched-off method in the email-code (login and registration, send and verify) and Telegram-widget AJAX handlers
    status: completed
  - id: views
    content: Auth modal (method list, email form, Telegram button) and Portal Login email forms follow the route
    status: completed
  - id: client
    content: js/ihq-channel-methods.js — on a method_disabled answer, hide that method's controls until the next page load
    status: completed
  - id: tests
    content: PHP tests (reader + handlers) and node --test (client) for method on, method off, absent feature, failed call; focused mutation script
    status: completed
  - id: refusal-once
    content: >
      Incremental re-review nit: a refusal on a first step showed twice (error box and the notice below it).
      js/ihq-channel-methods.js showFailure — a refusal with a notice on the page empties and hides the error
      box beside it; anything else shows the message there as before. The modal's email and Telegram steps and
      Portal Login's login and register steps use it; code steps keep their own error box
    status: completed
  - id: verify
    content: Run tests, lint, and CI gate before merge
    status: in-progress
---

# [ENGR-8014] IHQ login shows and accepts only the methods influencerhq-api lists as on

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-8014
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem

IHQ's sign-in methods are fixed in the theme: the auth modal always offers the
email code and, where `IHQ_TELEGRAM_LOGIN_CLIENT_ID` is set, the Telegram Login
widget. ENGR-7866 adds one switch per method in GrowthBook
(`communication-methods.ihq.*`). The theme reads no GrowthBook, so it gets the
switches from influencerhq-api `GET /v1/channels/methods` (ENGR-8013; contract
EQ12 to EQ14 on the ENGR-7866 evaluation).

## Approach

- **Reader** (`inc/ihq-channel-methods.php`). `GET <IHQ_API_BASE_URL>/v1/channels/methods`
  server to server, 3 s timeout, no redirects, TLS verified.
  - 2xx `{"methods":{…}}` → feature present. A method is on only when its value is
    `true`; a missing key or a non-boolean value is off (EQ2).
  - 2xx `{"methods":null}` → no feature: every method behaves as today (Q7).
  - Anything else (WP_Error, non-2xx, bad JSON, wrong shape) is a failed call: the
    last good answer is used, and with none, today's lists.
  - The answer, good or fallen back, is cached in a transient for 30 seconds, so a
    switch takes effect within 30 seconds and a down API is retried every 30 seconds
    rather than on every request. The last good answer lives in a non-autoloaded
    option.
- **Handlers.** After the nonce check, `ihq_channel_method_refuse_if_off()` answers
  403 `{"error":"method_disabled","method":"<m>","message":"<Method> isn't available right now"}`:
  - email: `ihq_send_login_code`, `ihq_verify_login_code`;
  - registration code: `ihq_send_registration_code` and `ihq_verify_registration_code`
    by delivery method (Telegram when the code goes by Telegram DM, else email);
  - Telegram widget: `ihq_telegram_login_nonce`, `ihq_verify_telegram_id_token`,
    `ihq_register_telegram_user`, `ihq_login_telegram_user`.
- **Views.**
  - Auth modal (`template-parts/portal-auth-modal.php`): the code-delivery list drops
    switched-off methods; the email form renders only when email is on; the Telegram
    button only when telegram is on and the client id is configured; the "OR" divider
    only when both show; "Email isn't available right now" when no sign-in method
    is left.
  - Portal Login (`page-portal-login.php`): email is its only method, so with email
    off the tabs, forms and their script are replaced by "Email isn't available right now".
- **Client** (`js/ihq-channel-methods.js`, ES module, sets `window.ihqChannelMethods`).
  When a handler answers `method_disabled`, the existing error box shows the server's
  message and every element marked `data-ihq-channel-method="<m>"` is hidden until
  the next page load (EQ14). Error boxes are never marked, so the message stays visible.

## Alternatives considered

- Render the lists in the browser from a JSON map: a flash of today's buttons and a
  second source of truth. The server renders them instead.
- Cache only the fresh answer: every request would call a down API with its timeout.
- Fail closed while the route is unreachable: Q7 says an absent feature changes
  nothing, and a failed call with no earlier answer keeps today's lists.

## Blast radius

- Every logged-out portal page (auth modal) and Portal Login now make one
  server-to-server GET per 30 seconds per WordPress instance (transient cache).
- The email-code and Telegram handlers answer 403 for a switched-off method. The
  lander and home-claude pages render their own forms (not in EQ13); they are not
  hidden there, but the handlers refuse them and their error boxes show the message.
- The deploy order is compulsory (DEPLOYMENT NOTE): influencerhq-api with the route
  first. Before then the route 404s, which is a failed call, so today's lists stay.

## Notes

- Out of scope: the conversation-modal and AI Coach channel lists (contact channel,
  not sign-in), and `send_verification_email` (verification link, not a code).
- No Stryker in this repo; the focused mutation script
  `scripts/test-channel-methods-mutations.py` is the mutation gate, as for ENGR-7017.
