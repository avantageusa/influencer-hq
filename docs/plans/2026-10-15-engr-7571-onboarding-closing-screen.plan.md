---
name: Onboarding closing screen with Continue to IHQ Portal
overview: >
  The last onboarding screen shows Sami's enlarged avatar, her closing message, and two stacked
  buttons: CONTINUE TO IHQ PORTAL, which marks onboarding complete and lands the Influencer on the Coach
  page, and SAVE IHQ TO HOME SCREEN. This adds it as a plug-in for the screen engine. The install
  behaviour of the Save button (ENGR-7575) and the appointment screens (T16) are out of scope.
todos:
  - id: module
    content: js/aicoach/onboarding-screens-closing.js and css/portal-onboarding-closing.css
    status: completed
  - id: tests
    content: content, completion success and failure, single submit, Save stays, frame Save hidden
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
- Registered with `hideContinue: true`: the screen has its own buttons.
- Content from PO-3217 / the old frame 400:6277 (OQ5): the banner's portrait enlarged and centred
  (same image as the banner, so it follows the avatar version), the two message points verbatim, then
  CONTINUE TO IHQ PORTAL and SAVE IHQ TO HOME SCREEN stacked.
- CONTINUE calls `api.complete()` (engine → `POST /ihq/v1/onboarding/complete` from ENGR-7564 → lift
  the history guard → `shell.close()`, which removes the layer and scrolls the Coach page to the top).
  The button is disabled while the request runs; on failure the screen stays with an inline alert and
  the button works again.
- SAVE carries `data-ihq-save-home`; ENGR-7575 handles every such element. Nothing here navigates, so
  the Influencer stays on the screen. The frame's own Save button is hidden while this screen shows
  and restored when it goes, so only one Save button is on screen.

## Alternatives considered
- Closing the layer even when completion fails: the account would still be "not complete", so the
  onboarding would open again on the next visit without telling the Influencer why.

## Blast radius
A new plug-in and stylesheet only.

## Notes
- "Something went wrong. Please try again." is new copy, in the string table.
- Design has no closing frame yet (OQ5, pending Product); the old frame could not be opened through
  the Figma MCP in this session either.
