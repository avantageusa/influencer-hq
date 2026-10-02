---
name: Capture ?ref on any IHQ page and send it as referrerCode on start-session
overview: >
  Influencer-to-influencer referrals (epic PO-3367) need IHQ to remember which
  influencer's link a visitor arrived on and hand that code to account-api when
  the visitor's player account is created. A small script on every page stores
  ?ref in a 90-day first-party cookie; ihq_register_oauth_user() adds it to the
  start-session payload as referrerCode until a start-session succeeds, then
  clears it. The email-verification flow carries the code in the pending
  registration so a link opened in another browser still attributes, and the
  emailed link is minified through influencerhq-api POST /minify. account-api
  (ENGR-6965) and the /minify route (ENGR-6971) are out of scope.
todos:
  - id: capture-script
    content: js/ihq-ref-capture.js + inc/ihq-referral-attribution.php enqueue on every front-end page; TTL from IHQ_REF_COOKIE_TTL_DAYS in inc/ihq-env.php
    status: completed
  - id: send-and-clear
    content: ihq_register_oauth_user() adds referrerCode (explicit code wins over cookie), clears cookie + $_COOKIE on success only
    status: completed
  - id: email-link
    content: handle_verification_email() stores referrer_code in pending_registration_<token>; verify_email passes it through; emailed link minified with raw-link fallback
    status: completed
  - id: tests
    content: Standalone PHP tests for attribution helpers, start-session payload/clear, minify fallback, TTL accessor
    status: completed
  - id: verify
    content: Run tests and lint, open PR, check CI
    status: in-progress
---

# [ENGR-6966] Capture ?ref on any IHQ page, keep it 90 days, and send it on start-session until one succeeds

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-6966
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem

An influencer who registers through another influencer's link must be linked
to them (PO-3369). IHQ does not record where a visitor came from, so account-api
never learns the referrer.

## Approach

- **Capture (JS, every page).** `js/ihq-ref-capture.js` reads `?ref` and writes
  `ihq_ref` (Path=/, SameSite=Lax, Secure on https, Max-Age = TTL days), using
  the same cookie writer shape as `js/ihq-visitor-intent.js`. Last touch wins.
  It runs in the browser, so pages served from the Cloudflare / WP Engine edge
  cache still capture. Enqueued in the head on `wp_enqueue_scripts` by
  `inc/ihq-referral-attribution.php`, with config from `wp_localize_script`.
- **TTL.** `ihq_env_ref_cookie_ttl_days()` in `inc/ihq-env.php` reads
  `IHQ_REF_COOKIE_TTL_DAYS` (default 90, falls back on anything that is not a
  positive whole number).
- **Send.** `ihq_register_oauth_user()` gains a trailing `array $options`
  (`referrer_code`) rather than an eighth positional argument. It resolves the
  code (explicit wins, else sanitised cookie) and adds `referrerCode` only when
  non-empty, so a cookieless payload is byte-identical to today.
- **Clear.** On a successful start-session only: expire the cookie
  (Set-Cookie, same path, no domain) and `unset($_COOKIE['ihq_ref'])`, so the
  AI Coach second call in the same request does not resend it. Failure keeps it.
- **Email link.** `handle_verification_email()` stores the sanitised cookie as
  `referrer_code` in `pending_registration_<token>`; the `verify_email` handler
  passes it to `ihq_create_influencer_user_from_registration_data()`, which
  hands it to `ihq_register_oauth_user()`. The emailed link is shortened with
  `POST ${IHQ_API_BASE_URL}/minify` (`{originalUrl}` → `{shortUrl, shortCode}`,
  `inc/ihq-url-minify.php`); any error, non-2xx, missing `shortCode` or
  non-https `shortUrl` sends the raw link.
- **Sanitising.** `sanitize_text_field()` then a 64-character cap, before the
  code is stored or sent.

## Alternatives considered

- PHP `setcookie()` on page load: never runs on edge-cached pages. Rejected by the ticket.
- Eighth positional parameter on `ihq_register_oauth_user()`: the function already
  takes seven; an options array keeps the call sites readable.
- Putting `ref` in the email URL: leaks the code into a forwardable link; the
  stored pending record already survives a device switch.

## Blast radius

- Every start-session (registration and every login) passes through the changed
  function. With no cookie, the payload is unchanged.
- A new script loads in the head of every front-end page (tiny, no dependencies).
- The verification email now depends on `/minify` being reachable; failure falls
  back to the raw link with a 5 s timeout, so a slow minifier delays the AJAX
  response by up to that long.
- Deploy order: influencerhq-api with ENGR-6971 and its `IHQ_ALLOWED_ORIGINS`
  SSM key before this; account-api ENGR-6965 to act on `referrerCode`.

## Notes

- The QC-only harness bridge (`inc/harness-auth-bridge.php`) does not go through
  `ihq_register_oauth_user()` and is deliberately untouched.
- The dead magic-register handler is untouched (no generator since PO-2821).
- Not verified here: the edge-cache behaviour on PROD, WP Engine passing the
  `ihq_ref` cookie to admin-ajax / REST requests, and the end-to-end players-row
  check, which needs ENGR-6965 and ENGR-6971 deployed to qc.
