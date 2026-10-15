---
name: Clear the pending login state on logout
overview: >
  After logout, header Login opened the 6-digit code prompt straight away. The
  guest login state lives in two 30-day browser cookies written by
  js/ihq-visitor-intent.js — `ihq_visitor_intent` (holds comm_methods) and
  `ihq_visitor_code_issued` — and neither is touched by a WordPress logout, so
  js/ihq-registry-gates.js sees comm methods on file and skips the account and
  channel step. Expire both cookies on `wp_logout`.
todos:
  - id: server
    content: inc/visitor-intent-logout.php — wp_logout hook expires ihq_visitor_intent + ihq_visitor_code_issued (path /, no domain, same as the JS writer) and unsets them from $_COOKIE; required from functions.php
    status: completed
  - id: tests
    content: tests/visitor-intent-logout.test.php — cleared names (return value), Set-Cookie writer calls, $_COOKIE side effects, no-cookie no-op, other cookies untouched, cookie options, hook registration
    status: completed
  - id: deploy
    content: Manual WP Engine deploy of the theme to IHQ dev after merge, then QA retest
    status: pending
---

# [ENGR-7260] Clear the pending login state on logout

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7260
**Diagnosis:** Steve Wolfe on the ticket, 2026-10-15 — "cookie needs to be cleared on logout".

## Cause

influencer-hq#119 forced a code send on header Login but did not change which
screen opens. `triggerGate('header_login')` in js/ihq-registry-gates.js calls
`ihqVisitorIntentHasCommMethods()`; when the `ihq_visitor_intent` cookie still
holds comm methods from the session before logout it goes straight to
`showCodeEntry()` instead of `openCommunicationModal()`.

## Fix

One server-side hook covers every logout link, since they all go through
`wp_logout_url()` → wp-login.php → `wp_logout()`. The hook expires both
visitor-intent cookies with the same path and host-only scope the JS uses.

Mid-login is unchanged: a guest who has chosen a channel and has a code
outstanding keeps both cookies until a logout happens.

## Alternatives considered

- **Clear the cookies in the client when Login is clicked.** Rejected: Login
  is also how a guest who picked a channel gets back to their code prompt, so
  clearing there would break the mid-login flow. A logout is the only event
  that should reset it.
- **Clear only `comm_methods` inside the intent cookie.** Rejected: the rest of
  the intent is pre-registration capture (ratings, gate ids) for a visitor who
  has since registered, and the cookie carries the previous user's contact
  details on a shared browser.
- **Delete the issued-code option on logout.** Not needed: the option is keyed
  by the comm fingerprint and expires on its own; a re-login with the same
  details reuses and resends it (existing behaviour).

## Tests

`tests/visitor-intent-logout.test.php`, standalone like the other PHP tests.
