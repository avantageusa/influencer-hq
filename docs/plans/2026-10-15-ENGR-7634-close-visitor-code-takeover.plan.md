---
ticket: ENGR-7634
title: Close the logged-out visitor-code account takeover
status: in-progress
todos:
  - { id: no-code-in-responses, content: "No AJAX response carries the visitor code unless IHQ_VISITOR_CODE_DEBUG is on in a dev/qa environment", status: completed }
  - { id: existing-user-gate, content: "Existing-user sign-in only when the code was emailed to that user's account email and the user is an influencer", status: completed }
  - { id: attempt-limits, content: "Verify: portal Turnstile pass when configured, per-IP lockout shared with login-code verify, per-fingerprint and per-email attempt caps", status: completed }
  - { id: hashed-storage, content: "One record per fingerprint holding only an HMAC of the code; constant-time compare", status: completed }
  - { id: review-fixes, content: "Independent review: register from the stored intent and bind new accounts to delivered_email; 10-misses-per-day email cap keyed by delivered address; burn the code after 3 misses; 45s per-email issue throttle; IHQ_ENVIRONMENT docs", status: completed }
  - { id: tests, content: "tests/visitor-code-verify.test.php covers takeover, new-visitor flow, limits, storage", status: completed }
---

# Close the logged-out visitor-code account takeover (ENGR-7634)

Ticket: https://avantageusa.atlassian.net/browse/ENGR-7634

## Problem

`inc/visitor-intent-handler.php` lets a logged-out caller obtain a visitor code
for any email and then sign in as the existing user who owns that email:

- `ihq_test_registry_braze` (nopriv) returned `registration_code`. Nothing in
  the theme calls it; the debug panel's TEST REGISTRY button posts
  `ihq_issue_visitor_verification`.
- `ihq_issue_visitor_verification` (nopriv) returned `braze_track_payload`,
  which carries the code in `attributes[0].registration_code` and the event
  properties, plus `registration_code_debug` under `WP_DEBUG`.
- `ihq_verify_visitor_code` signs in an existing account with no role check,
  no attempt limit and no Turnstile.
- The code sits in plaintext in `ihq_visitor_verify_fp_<fp>` and in the option
  name `ihq_visitor_verify_<code>`.

## Approach

1. **No code in any response by default.** The code echo is how developers
   sign in as test accounts, so it stays, behind
   `ihq_visitor_code_debug_enabled()`: wp-config `IHQ_VISITOR_CODE_DEBUG = '1'`
   AND `IHQ_ENVIRONMENT` of `dev` or `qa`. Without it the test action answers
   "Not available." and the issue handler omits `braze_track_payload` (it
   carries the code) and `registration_code_debug` (previously on `WP_DEBUG`).
2. **Existing accounts.** The record stores the address the code was emailed
   to. `ihq_complete_visitor_intent_registration()` takes that address and
   signs in an existing user only when it equals the account email
   (case-insensitive) and the user has the influencer role, the same role rule
   as `ihq_handle_verify_login_code_ajax()`. Without a delivered address (the
   legacy magic-link path, which nothing writes any more) an existing account
   is refused.
3. **Limits.** Verify requires the portal Turnstile pass when Turnstile is
   configured (code entry only happens on gated portal pages), reuses the
   per-IP mutex and 3-strikes/15-minute lockout of the login-code verify, and
   reserves an attempt atomically against per-fingerprint and per-email
   counters (same `ihq_login_verify_fail_*` option family, so the existing
   cleanup sweep covers them) before comparing.
4. **Storage.** One option per fingerprint holding an HMAC of the code with a
   per-record token. A re-request rotates the code (the old code cannot be
   recovered to resend). Pre-deploy records are cleaned up when touched.

5. **Review follow-up.** Verify registers from the intent stored at issue
   time (the posted intent only selects the record by fingerprint), and a new
   account must also be for `delivered_email`. A per-email cap of 10 misses a
   day, keyed by the delivered address, sits on top of the 15-minute caps;
   after 3 misses on a fingerprint the code is deleted. Issue is throttled to
   one code per email per 45 seconds, because each issue now cancels the
   previous code.

## Alternatives considered

- Delete the test action: removes the developers' way to test as any
  account; the flag keeps it with prod excluded twice (flag unset, env check).
- Render a Turnstile widget in the code prompt: front-end work in two JS files
  and a fresh token per verify; the portal pass already proves the visitor
  passed Turnstile on the page that shows the prompt.
- Refuse existing accounts on this path outright: breaks header Login, which
  is how influencers sign back in (ENGR-7260) and which PO-3255 OQ5 keeps.

## Risks

- Codes issued before the deploy stop working (15-minute TTL; visitors
  request a new one).
- Repeated "send code" now rotates the code, so only the latest email works.
