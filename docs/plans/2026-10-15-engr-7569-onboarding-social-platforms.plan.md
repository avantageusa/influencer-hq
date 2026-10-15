---
name: Onboarding social platforms screen with typed handles; Profile aligned to the 19 platforms
overview: >
  Onboarding screen 6 lists 19 social platforms; ticking one asks for its handle, and CONTINUE waits
  until every ticked platform has one. The handles must also show on the Profile page, whose platform
  list becomes the same 19 (keeping anything already saved outside them). This adds the screen as a
  plug-in, a server hook that copies onboarding handles into the Profile meta, and the Profile and
  save-allowlist changes. Platform sign-in (OAuth) and the Post page are out of scope.
todos:
  - id: screen
    content: js/aicoach/onboarding-screens-social.js and its stylesheet
    status: completed
  - id: mirror
    content: inc/portal-onboarding-social.php — copy handles to _ihq_social_handles on the store's saved action
    status: completed
  - id: profile
    content: Profile list from ihq_profile_social_platform_rows(); ihq_allowed_contact_keys() keeps the 19
    status: completed
  - id: tests
    content: JS screen tests and tests/portal-onboarding-social.test.php
    status: completed
  - id: placeholders
    content: Replace the interim placeholders with PO-3207's table once it exists
    status: blocked
  - id: verify
    content: test:js, lint on touched files, CI PHP job
    status: completed
---

# [ENGR-7569] Social platforms screen with typed handles; align Profile to the 19 platforms

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7569
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
Screen 6 does not exist, and the Profile page's platform list (11 keys) and its save allowlist (12
keys) do not match the 19 platforms the onboarding uses.

## Approach
- Screen (plug-in): 19 rows, a checkbox-role button and a hidden handle input each, under PLATFORM /
  ACCOUNT ID column headings. Ticking shows and focuses the input; unticking hides and clears it. No
  "Connect with …" buttons. `isValid()` is false while a ticked platform's handle is blank; then each
  such field gets an inline error (`aria-invalid`, `aria-describedby`) and focus moves to the first.
  Zero platforms is allowed (the story says "any number"). Every tick, untick and handle change saves
  `{ platforms, handles }` (blank handles left out, since the store refuses them).
- Server: `ihq_portal_onboarding_mirror_handles()` on `ihq_portal_onboarding_saved` copies the
  onboarding's handles into `_ihq_social_handles`. It records which keys it wrote
  (`_ihq_portal_onboarding_mirrored_handles`), so unticking later removes only those, never a handle
  that came from registration or Profile.
- Profile: the rendered list is `ihq_profile_social_platform_rows( $social_handles )`: the 19, then
  any other platform with a saved handle (Email, KICK, Twitch and anything unknown). The ticket points
  at `page-portal-profile.php:768` (`$ihq_profile_social_platforms`), but that array is never used;
  the list on screen is `$contact_platforms` (line 809), which is what changed.
- `ihq_allowed_contact_keys()` (Profile's save allowlist, which strips other keys on every save) now
  holds the 19 plus email, kick and twitch, so a Profile save never deletes an onboarding handle.

## Alternatives considered
- Writing `_ihq_social_handles` from the browser through Profile's AJAX action: two writes per change,
  and a second nonce.
- Replacing `_ihq_social_handles` wholesale with the onboarding's handles: would delete handles given
  at registration (e.g. WhatsApp) that the onboarding never showed (PO-3207: no pre-population).

## Blast radius
- `_ihq_social_handles` is also read by the Luna export (`inc/luna-users-rest.php`) and by Braze's
  OAuth registration payload at sign-up; onboarding handles now reach the Luna export too.
- Profile's "Communicate with Me" toggles (`_ihq_comm_prefs`) use the same allowlist, so the 19 can
  now be toggled there as well.
- Profile shows 19 rows plus saved extras instead of 11; Email/KICK/Twitch rows disappear for users
  with nothing saved there.

## Notes
- Placeholders are '@username' or a URL prefix per platform, as the ticket says, until PO-3207 adds
  its table of placeholders and accepted formats. They are in the string table.
- The design frame (46497:79558) could not be opened through the Figma MCP in this session.
