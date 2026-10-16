---
name: Onboarding closing screen with Continue to IHQ Portal
overview: >
  The last onboarding screen shows Sami's enlarged avatar, her closing message, and CONTINUE TO IHQ
  PORTAL, which marks onboarding complete and lands the Influencer on the Coach page. SAVE IHQ TO HOME
  SCREEN stays in the shell's sticky footer. This adds it as a plug-in for the screen engine. The install
  behaviour of the Save button (ENGR-7575) and the appointment screens (T16) are out of scope.
todos:
  - id: module
    content: js/aicoach/onboarding-screens-closing.js and css/portal-onboarding-closing.css
    status: completed
  - id: tests
    content: content, completion success and failure, single submit, Save stays, frame Save untouched, no auto-advance
    status: completed
  - id: verify
    content: test:js, lint on touched files
    status: completed
---

# [ENGR-7571] Closing screen with Continue to IHQ Portal

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7571
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
The engine (ENGR-7566) has nothing to show at stage `closing`, and nothing calls the completion path.

## Approach
- Registered with `advanceMode: api.screens.ADVANCE_MODES.MANUAL`: the engine has no CONTINUE (design
  decision 1) and never moves on by itself here; the screen's own button ends the flow.
- Content from PO-3217 / the old frame 400:6277 (OQ5): the banner's portrait enlarged and centred
  (same image as the banner, so it follows the avatar version), the two message points verbatim, then
  CONTINUE TO IHQ PORTAL (class `ihq-onb-closing__proceed`, 8 px above it as
  `--ihq-onb-closing-proceed-gap`).
- CONTINUE calls `api.complete()` (engine → `POST /ihq/v1/onboarding/complete` from ENGR-7564 → lift
  the history guard → `shell.close()`, which removes the layer and scrolls the Coach page to the top).
  The button is disabled while the request runs; on failure the screen stays with an inline alert and
  the button works again.
- SAVE is the frame's own `data-ihq-save-home` button and stays in the shell's sticky footer, as on
  every screen (design decision 1, 2026-10-16). This screen does not touch it, so ENGR-7575's install
  behaviour and hide rules apply unchanged.
- No avatar is drawn when the banner has no portrait source, rather than an empty ring.

## Alternatives considered
- A second Save button built by this screen, with the frame's hidden meanwhile (the first version):
  it duplicated the label and depended on ENGR-7575 also covering buttons added after page load.
- Moving the frame's Save button under CONTINUE while this screen shows (the PR #128 review version):
  dropped once the shell kept Save in a sticky footer on every screen and the dev decided closing does
  the same.
- Closing the layer even when completion fails: the account would still be "not complete", so the
  onboarding would open again on the next visit without telling the Influencer why.

## Blast radius
A new plug-in and stylesheet only.

## Notes
- "Something went wrong. Please try again." is new copy, in the string table.
- Design has no closing frame yet (OQ5, pending Product); the old frame could not be opened through
  the Figma MCP in this session either.
