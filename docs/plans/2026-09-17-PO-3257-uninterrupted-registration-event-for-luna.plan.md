---
name: Uninterrupted AI Coach registration event for Luna
overview: >
  When the AI Coach flow finishes, create the influencer and sign them in with
  no 6-digit code or login screen. Luna (and the Let's Continue button) fire
  the same JS event; the server reuses the existing user-create path. Wiring
  more coach events later is in scope of follow-ups, not this ticket.
todos:
 - id: php
   content: Add POST /ihq/v1/create-account that creates or signs in without OTP
   status: completed
 - id: js
   content: Expose window.ihqCoachEvents.register and the ihq:coach-register CustomEvent
   status: completed
 - id: glue
   content: Point Let's Continue at the event and enqueue it before the coach-flow module
   status: completed
 - id: verify
   content: Complete identity + email on the coach page, finish, land in the portal signed in
   status: pending
---

# [PO-3257] Scenario 1 — Registration is uninterrupted

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3257
**Drafted by:** Cursor Grok 4.6

## Problem
Finishing the AI Coach flow must create an account and drop the visitor into
the portal already signed in. No code, verification, or login screen. The
coach page already collects identity and communication channels, and the
Let's Continue button already POSTed to `/ihq/v1/create-account`, but that
route did not exist.

## Approach
Two new files, one shared event:

- `js/ihq-aicoach-events.js` — `window.ihqCoachEvents.register(detail)`.
  Missing fields are read from `#aicoach-identity-form` and
  `#aicoach-channels-form`. Luna can also `dispatchEvent(new CustomEvent(
  'ihq:coach-register'))`. `redirect: false` lets the page close the Gary
  session before navigating.
- `inc/aicoach-register.php` — `POST /wp-json/ihq/v1/create-account`. Parses
  the coach-flow payload (and the older `comm_methods` map), calls
  `ihq_create_influencer_user_from_registration_data()`, sets the auth cookie
  the same way passwordless login does, and returns `{ success, redirectUrl }`.
  An existing email signs in instead of erroring, so a returning visitor is
  not bounced to a login screen.

Turnstile is skipped on this path on purpose: the ticket forbids a
verification screen. CSRF is the REST nonce the coach page already sends.

## Alternatives considered
- Point Luna at the 6-digit `ihq_send_registration_code` flow — rejected; the
  AC forbids a code screen.
- Invent a second user-create function — rejected; the existing helper already
  sets the influencer role, comm methods, OAuth tokens, and Braze sync.
- Leave Let's Continue posting directly and only add the event for Luna —
  rejected; two clients would drift, and agent-filled checkboxes would not
  populate the in-memory `capturedIdentity` the button currently requires.

## Blast radius
Only `page-home-aicoach.php`. Other login/register modals are unchanged.
`ihq_create_influencer_user_from_registration_data()` is shared; this call
site does not change its contract. Username uniqueness is checked before
create so a taken handle cannot leave an orphan WP user.

## Notes
- Email is required because WordPress accounts need one. If Luna only filled
  LINE / WhatsApp, the event returns a clear error rather than creating a
  user with a fake address.
- More coach events (checkbox toggles, language, duration) can hang off
  `window.ihqCoachEvents` the same way; only register ships in this change.
- Not exercised against a running site in this session.
