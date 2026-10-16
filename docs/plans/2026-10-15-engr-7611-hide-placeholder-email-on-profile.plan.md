---
name: Never show the stand-in email on the IHQ Profile page
overview: >
  Influencers who registered without an email (Telegram or another chat
  channel) have the ENGR-7511 stand-in address <uuid>@chat.invalid, and the
  Profile page's Email row showed it. The stand-in is never shown: the Email row
  is empty and offers to add an email, and the channel the influencer
  registered with gets its own row (same rule as the game's settings, PO-3155 /
  PO-3308).
todos:
  - id: helper
    content: inc/profile-email-display.php — ihq_profile_email_row_value() (empty for a stand-in, via ihq_is_placeholder_email) and ihq_profile_registered_channel() (preferred_communication / communication_methods, chat channels only, LINE consent marker hidden)
    status: completed
  - id: page
    content: page-portal-profile.php — Email row shows "Add email" when empty; "Registered with" row after it; inline edit starts empty and saves nothing when left empty
    status: completed
  - id: tests
    content: tests/profile-email-display.test.php
    status: completed
  - id: deploy
    content: Manual WP Engine deploy of the theme to IHQ dev after merge
    status: pending
---

# [ENGR-7611] Never show the stand-in email on the IHQ Profile page

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7611

## Approach

Rendering only. Detection keys on `ihq_is_placeholder_email()` (inc/placeholder-email.php), the one place that knows the stand-in domain, so ENGR-7549's work on placeholder domains carries through without changes here. Account creation and placeholder generation are untouched.

- The Email row shows an underlined "Add email" prompt when the account has no real email. Clicking it opens the existing inline editor with an empty input; leaving it empty keeps the prompt and saves nothing (so the stored stand-in is never overwritten with an empty string). Typing an address saves it through the existing `save_settings_field` path, as for any other Email edit.
- A read-only "Registered with" row (e.g. `Telegram @handle`) follows the Email row, only when there is no real email. The channel comes from `preferred_communication` (written at registration), falling back to the first chat channel in `communication_methods`.

## Alternatives considered

- Show the channel row for every account with a chat channel: not asked for; the ticket ties it to the no-email case.
- Verify the added email by code before saving: that is the registration/login rework (ENGR-7638), not a rendering fix.
