---
name: Portal header login uses the 6-digit code flow
overview: >
 The portal header's Login button already opens the auth modal, and that modal
 already runs the passwordless 6-digit flow from page-portal-login.php. What it
 did not do is match PO-3192's wording or show which channel the code is sent
 on. This adds a preferred-method-of-communication choice to the login step
 (email enabled, other channels listed but disabled) and brings the code-step
 copy and button label in line with the acceptance criteria. Backend handlers,
 registration, and the standalone login template are out of scope.
todos:
 - id: methods
 content: Add the preferred-method group to the login step, email selected, other channels disabled
 status: completed
 - id: copy
 content: Use the AC message on the code step and rename the confirm button to Continue
 status: completed
 - id: reset
 content: Reset the method selection whenever the modal panels reset
 status: completed
 - id: verify
 content: Run lint and exercise send-code plus verify-code paths before merge
 status: pending
---

# [PO-3192] Log in flow

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3192
**Drafted by:** Claude (Opus 4.5), Cursor

## Problem
A user who is logged out — manually or by session expiry — needs to get back
into the portal with a 6-digit code. The ticket notes the flow "is already
implemented, we just need to make sure it works". It does work mechanically,
but the login step never tells the user which channel the code goes to, and the
code step's copy and button label do not match the acceptance criteria.

## Approach
Front-end only, confined to `template-parts/portal-auth-modal.php`, which the
header's `portalHeaderOpenLogin` button already opens.

- A `Preferred method of communication` radio group sits under the email field.
  The channel list mirrors the register pane (Email, LINE, Telegram, WhatsApp,
  WeChat). Only Email is selectable; the rest render disabled with a "Coming
  soon" note, so the choice is visible without implying it works.
- The code step now reads "Please check your preferred method of communication
  and enter the 6-digit code we sent you.", and its confirm button reads
  "Continue", both taken verbatim from the acceptance criteria.
- `ihqAuthLoginResetPanels()` restores the default channel, so reopening the
  modal never leaves a stale selection.
- Styles are scoped to this template part. They deliberately avoid `.auth-field
  label`, whose uppercase treatment is meant for field labels, not option text.

The request to `ihq_send_login_code` is unchanged: email is the only selectable
channel, so there is no new value to send and no backend change to make.

## Alternatives considered
- Dropping the email field and identifying the user from the expired session —
  rejected; a logged-out visitor has no reliable identity to read.
- Sending the chosen channel to the backend now — rejected as speculative while
  email is the only option; the handler would only validate it back to email.
- Hiding the unavailable channels entirely — rejected; the AC asks the user to
  check their preferred method, so the list needs to be visible.

## Blast radius
`template-parts/portal-auth-modal.php` renders on every portal page via
`template-parts/portal-header.php`, but only for logged-out visitors — the part
returns early when `is_user_logged_in()`. Register pane, Telegram login,
Turnstile, and both AJAX handlers are untouched. New CSS class names are unique
to this part, so no existing selector changes meaning.

## Notes
- Telegram appears as "Coming soon" in the channel list while the "Login with
  Telegram" button below it does work. They are different mechanisms — the list
  is about where a 6-digit code is delivered — but the wording may want a second
  look with design.
- Not exercised against a running site: the local environment was not up. The
  send-code and verify-code paths should be walked manually before merge.
- Backend delivery to LINE / WhatsApp / WeChat is the follow-up that makes the
  disabled options selectable.
