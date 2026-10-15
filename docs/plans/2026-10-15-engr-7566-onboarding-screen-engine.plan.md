---
name: Forward-only onboarding screen engine with the edit-return branch
overview: >
  The onboarding's sixteen screens have to play in a fixed order, forward only, with question screens
  gated on a valid answer, the stage saved on every advance, browser back held, and one backward branch
  (screen 15 "Let's edit this together now" back to screen 14). This adds the sequence as data, the
  engine that renders registered screen definitions into the shell, a history guard and the REST calls
  for saving and completing. Screen content is out of scope (ENGR-7567 onwards).
todos:
  - id: sequence
    content: onboarding-sequence.js — SEQUENCE, BRANCHES, startStage(), nextStage() (pure)
    status: completed
  - id: engine
    content: onboarding-engine.js — register/start/advance/save/complete, CONTINUE button, validation gate, screen events
    status: completed
  - id: history
    content: onboarding-history.js — browser back keeps the current screen
    status: completed
  - id: request
    content: onboarding-request.js — POST JSON with the wp_rest nonce, never throws
    status: completed
  - id: wiring
    content: onboarding-main.js creates the engine, exposes api.screens/save/advance/complete, starts after plug-ins install; per-group stylesheets
    status: completed
  - id: verify
    content: test:js, lint on touched files, CI PHP job
    status: completed
  - id: review-fixes
    content: PR #124 review — onboarding-launch.js (close on failed screen module / no screen), serial stage saves, no guard pile-up on reload, --ihq-onb-error token
    status: completed
---

# [ENGR-7566] Forward-only onboarding screen engine with edit-return branch

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7566
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
The shell (ENGR-7565) can show one screen's content and Sami's line, but nothing decides which screen
comes next, saves the position, or stops the Influencer skipping or going back.

## Approach
- `onboarding-sequence.js` (pure): `SEQUENCE` (the 16 stage ids, the same list as
  `ihq_portal_onboarding_stages()` in ENGR-7563), `BRANCHES` (`post_action` + `edit_together` →
  `draft_posts`), `startStage()` (saved stage, else screen 1) and `nextStage()` (one forward, or a
  branch). Both are data so T16 can add its appointment screens and branch.
- Screens register `{ id, narration?, hideContinue?, render( ctx ) }` through `api.screens.register()`.
  `render()` gets `{ container, api, t, text }` and may return a controller with `isValid()`,
  `showValidation()`, `choice()` and `destroy()`. The engine adds a CONTINUE button (string key
  `continue`) unless `hideContinue`.
- Advancing: blocked while `isValid()` is false (and `showValidation()` is called); otherwise moves
  to `nextStage()`, saves `{ stage }` to the store (not awaited; a failure is logged and never
  blocks; stage saves are chained so they reach the store one at a time, in order), destroys the old screen and renders the new one. Each screen emits `screen`
  `{ stage, next }` for preloading (ENGR-7576).
- A screen whose module is not installed is passed over. Screens 14 and 15 wait on ENGR-7570, so until
  then the flow goes from 13 to 16; the edit branch is only taken when screen 14 exists.
- That pass-over must not apply to a module the server listed that then failed to load: the screen
  would be skipped for good. `onboarding-launch.js` closes the layer for the visit when a listed
  `onboarding-screens-*` module did not install, or when no screen is installed at all (nothing is
  saved; the next visit retries). A failed add-on does not stop the flow.
- `onboarding-history.js`: one guard entry is pushed when the engine starts; `popstate` pushes it back,
  so Back leaves the user on the current screen. A reload already on the guard entry pushes no second
  one. `complete()` lifts the guard.
- `complete()` POSTs to `completeUrl` (ENGR-7564) and closes the layer only when the server recorded
  it, so the user is never dropped on the Coach page with onboarding still open on the account.
- A language change re-says the current screen's line in the new language (clip restarts).
- Shell: stylesheets are now `css/portal-onboarding.css` plus each `css/portal-onboarding-*.css`, so
  each screen group ships its own file, as with the plug-in modules.

## Alternatives considered
- A step counter instead of stage ids: the store keeps stage ids, and an inserted screen (T16) would
  shift every number.
- Saving the stage on screen exit only: a refresh mid-screen would lose it; saving on advance is
  what the ticket asks.
- Blocking the advance until the save returns: a slow or failed save would freeze the flow.

## Blast radius
Only the onboarding modules, the shell PHP (stylesheet list) and its test. Nothing renders until
ENGR-7564's action fires.

## Notes
- The shell's PHP test changed with the stylesheet list (one URL became a list); the behaviour it
  checks for the frame stylesheet is the same.
- The cross-check that `SEQUENCE` equals the PHP stage list lives in ENGR-7568, the first branch that
  has both files.
