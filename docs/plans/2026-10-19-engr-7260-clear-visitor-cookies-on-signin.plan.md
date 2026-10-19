---
ticket: ENGR-7260
title: Clear the guest login cookies on visitor-code sign-in
status: in-review
todos:
  - id: reopen-check
    content: Re-check the QA reopen against the logout fix (influencer-hq#133)
    status: completed
  - id: clear-on-signin
    content: Expire ihq_visitor_intent and ihq_visitor_code_issued after a successful visitor-code verify
    status: completed
  - id: tests
    content: Extend tests/visitor-code-verify.test.php for success and failure paths
    status: completed
---

# Clear the guest login cookies on visitor-code sign-in

## Context

influencer-hq#133 expires the 30-day `ihq_visitor_intent` and `ihq_visitor_code_issued`
cookies on `wp_logout`. QA reopened the ticket on 2026-10-19. Both QA recordings start
already logged out, so the logout click is not on them. A session can end without
`wp_logout`: the visitor-code sign-in sets a browser-session auth cookie, capped at
24 hours by `inc/auth-session-lifetime.php`. The guest cookies then outlive the session,
and the header Login gate (`js/ihq-registry-gates.js`) opens the 6-digit code prompt.

## Change

After `ihq_handle_verify_visitor_code_ajax` has signed the visitor in and deleted the
verification record, call `ihq_visitor_logout_clear_cookies()` (from
`inc/visitor-intent-logout.php`). Failed or refused verifies leave the cookies alone, so
a retry still works. The logout clearing is unchanged.

Nothing reads the cookies while signed in. The registry gates only load for guests
(`inc/registry-gates.php`). The one signed-in reader, `maybeIssueVerificationOnPortalArrival`
in `js/ihq-visitor-intent.js`, would send a stray code when only `ihq_visitor_code_issued`
is gone. Both cookies are cleared together, so `hasCommMethods()` is false and it does nothing.

## Alternatives considered

- Hook `wp_login` / `set_logged_in_cookie` so every sign-in path clears them. That is
  wider than the brief (the portal login-code page and SSO paths too). Left as a follow-up.

## Verification

- `php -l` on the touched files.
- `tests/visitor-intent-logout.test.php` and `tests/visitor-code-verify.test.php`.
