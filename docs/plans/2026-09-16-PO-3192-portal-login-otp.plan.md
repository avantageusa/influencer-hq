---
name: Portal Log in button delivers its 6-digit code
overview: >
 Clicking Log in on the portal header produced no code email. The button is
 intercepted by the header_login registry gate, whose 6-digit code is delivered
 through Braze — and no IHQ_BRAZE_* constants are defined on the live instance,
 so the send failed server-side and the visitor saw nothing. The code is now
 emailed with wp_mail(), the same way the sign-in code on page-portal-login.php
 already works, with the Braze call kept commented and ready. Also aligns the
 auth modal's copy with the ticket. Braze configuration is out of scope.
todos:
 - id: deliver
 content: Email visitor verification codes with wp_mail, mirroring the sign-in code mail
 status: completed
 - id: swap
 content: Comment out the Braze POST in ihq_issue_visitor_verification_code, ready to restore
 status: completed
 - id: errors
 content: Return a real error when there is no email on file or the send fails
 status: completed
 - id: modal
 content: Match the auth modal copy to the acceptance criteria and show the preferred method
 status: completed
 - id: verify
 content: Click Log in on a portal page as a logged-out user and confirm the code arrives
 status: pending
---

# [PO-3192] Log in flow

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3192
**Drafted by:** Claude (Opus 4.5), Cursor

## Problem
A logged-out user clicking Log in on the portal header never receives a 6-digit
code, so there is no way back into the portal. The same code requested from
`page-portal-login.php` arrives normally.

## Reproduction
Log out, open any portal page, click Log in in the header, complete the gate's
communication step. The code entry box appears; no email is ever delivered.

## Approach
Two separate findings, one root cause.

The Log in button never opens the auth modal. `js/ihq-registry-gates.js` binds
`header_login` through `bindGate()`, which listens on `document` in the capture
phase; `triggerGate()` then calls `preventDefault()`, `stopPropagation()` and
`stopImmediatePropagation()`, killing the modal's own bubble-phase handler. The
gate's own notice takes over — and it is already the PO-3192 UI, since
`inc/registry-gates.php` localizes the acceptance criteria's exact message and
its "Continue" label.

That gate delivers its code via Braze: `ihq_issue_visitor_verification_code()`
built a payload and POSTed it to `ihq_braze_rest_endpoint() . '/users/track'`.
Braze needs `IHQ_BRAZE_REST_ENDPOINT` and `IHQ_BRAZE_TRACK_API_KEY`, neither of
which is defined on the live instance, so the POST failed, the failure was only
`error_log`ged, and the function still returned `ok`. The gate then showed a
code box for a code that was never sent.

So delivery moves to `wp_mail()`:

- `ihq_send_visitor_verification_code_email()` sends the code with the same
  markup and From address as the sign-in code mail in
  `inc/email-verification-handler.php`, so both look identical to the user.
- `ihq_deliver_visitor_verification_code()` wraps recipient lookup and send, and
  returns a failure that now propagates — no email on file, or a rejected send,
  surfaces a message in the gate instead of a code box that will never fill.
- The Braze POST is commented in place with a note on how to restore it, and
  `ihq_post_braze_track_payload()` plus the payload builder are untouched, so
  re-enabling is uncommenting two lines once the constants exist.
- The reuse branch now resends too. A code issued while delivery was broken
  would otherwise lock the visitor out for its full 15-minute lifetime.

Separately, `template-parts/portal-auth-modal.php` gained the preferred-method
row and the acceptance criteria's copy, so the modal matches the ticket wherever
it is reached from.

## Alternatives considered
- Point the gate at `ihq_send_login_code` — rejected: that handler runs
  `ihq_verify_turnstile_or_error_for_ajax()`, and with Turnstile configured on
  live the gate's notice has no widget to produce a token, so every request
  would fail human verification.
- Remove the `header_login` gate so the button opens the auth modal — rejected:
  the gate is deliberate, captures visitor intent, and already carries the
  ticket's copy.
- Define the `IHQ_BRAZE_*` constants on live and change nothing — rejected for
  now; email should work regardless of whether Braze is configured.

## Blast radius
`ihq_issue_visitor_verification_code()` is shared by every registry gate, not
just `header_login`, so all gate codes now arrive by email. Both callers already
guard `braze_response` with `! empty()`, so dropping that key while Braze is off
breaks neither the AJAX response nor the debug panel. Visitors who chose only
LINE / WhatsApp / WeChat now get an explicit "we need an email address" message
where they previously got silence — different, but not a regression, since no
code reached them before either.

## Notes
- Braze remains the intended channel for non-email methods; this is a stopgap
  until the constants are configured per instance, and the call site is ready.
- Not verified against a running site: the local environment is down, and live
  runs unmerged branch code. Needs a logged-out click-through before merge.
- Telegram shows as "Coming soon" in the modal's method list while the Login
  with Telegram button works — different mechanisms, but worth a design check.
