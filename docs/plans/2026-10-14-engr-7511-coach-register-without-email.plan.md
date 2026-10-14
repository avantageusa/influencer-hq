---
name: AI Coach registration with any one channel (no email required)
overview: >
  AI Coach create-account required an email among the chosen channels, so a
  visitor who picked only Telegram (or any other non-email channel) got the
  generic "Something went wrong creating your account" and no account. Accept
  any one channel (PO-3099) and give an email-less account an undeliverable
  stand-in address; show the server's own error on the final screen.
todos:
  - id: server
    content: ihq_aicoach_parse_register_payload accepts any one channel; no email → <uuid>@chat.invalid; no channel → "Please select at least one communication method."
    status: completed
  - id: client
    content: ihq-aicoach-events.js gates on "any channel with a value" instead of "a value containing @"
    status: completed
  - id: final-screen
    content: aicoach-coach-flow.js shows the server's error text instead of the generic message when there is one
    status: completed
  - id: tests
    content: tests/aicoach-register-channels.test.php (parse + handler, return values and side effects)
    status: completed
  - id: deploy
    content: Manual WP Engine deploy of the theme to IHQ dev after merge (DEPLOYMENT NOTE on the ticket)
    status: pending
---

# [ENGR-7511] AI Coach registration with any one channel

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7511
**Decision:** Steve Wolfe, 2026-10-14 — option B (accounts without an email are
allowed), with minimal changes until PO-3155's sign-in is applied to IHQ through
PO-3255 (Registration/Login on IHQ).

## Problem

`POST /wp-json/ihq/v1/create-account` returned 400 "Please add an email address
so we can create your account." whenever no chosen channel was an email.
QA picked only Telegram (`@Avantage123`). The browser check let it through
because it counted any value containing "@" as an email, and the final screen
replaced the server's message with the generic one. The request never reached
influencerhq-api or account-api.

## Change

- **Server** (`inc/aicoach-register.php`): an error only when no allowed channel
  has a value. With no email, the account email is
  `wp_generate_uuid4() . '@chat.invalid'`.
  - `.invalid` cannot deliver (RFC 2606), and it is the placeholder domain PO-3155
    uses for chat-app-only accounts.
  - It passes the referral service's class-validator `IsEmail`, which the
    players stream feeds. The older `@email_telegram.com` form fails it (underscore
    in the domain).
  - Random, not derived from the handle: the handle is unverified.
- **Client** (`js/ihq-aicoach-events.js`): require any channel with a value.
- **Final screen** (`js/aicoach-coach-flow.js`): show `data.error` from the
  server when present; network/JS failures keep the generic message.

## Known gap (accepted)

An account created without an email cannot sign back in after its session ends:
IHQ sign-in is the emailed code only. That is PO-3255's job. The account,
start-session (B2B user, players row, referral) and Braze sync are created as for
any other registration, with the stand-in email.

## Alternatives considered

- Reuse `ihq_telegram_build_fake_email()` (`<handle>@email_telegram.com`): Telegram
  only, built from an unverified handle, and rejected by referral's `IsEmail`.
- Wire the unreachable Telegram widget login into `/portal/login`: adds a sign-in
  surface PO-3155 is retiring; out of the minimal scope Steve set.

## Review follow-up (independent review, 2026-10-14)

- **W1:** `inc/placeholder-email.php` owns the domain and `ihq_is_placeholder_email()`, so PO-3255 can find stand-in accounts to merge. No dedupe on the unverified handle.
- **W2:** a stand-in is not marked `email_verified`, and `ihq_send_influencer_to_braze()` neither looks it up in Braze nor sends it as `email`. start-session still gets it (account-api and referral need an email).
- **W3:** the browser email check now applies WordPress `is_email()`'s rules (`isWordPressEmail` in `js/aicoach/screens.js`), so the invalid-email 400 can't be reached from the form. Reopening the identity / channels panel from the final screen is **not** done: it changes the coach flow's screen sequencing, beyond the minimal change Steve asked for (PO-3255 / PO-3100 Scenario 16 follow-up).
- **W4:** JS tests for the channel gate (`js/aicoach/ihq-aicoach-events.test.js`, VM) and `serverErrorMessage()`.
