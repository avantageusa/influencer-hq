---
name: Show Sami's idle portrait on the screens that have no clip (ENGR-7099)
overview: >
  After the last pre-rendered clip ends, the video is paused and Sami stays on its last frame on the
  identity, channels and final screens, while a visitor who resumes there sees the neutral portrait.
  This takes option (a) of the ticket: a screen without a clip shows the idle portrait with the existing
  breathing loop (ENGR-7051). No new assets, no change to clips or timing.
todos:
  - id: decision
    content: Product picks the behaviour - Dejan chose option (a), reuse the idle portrait, on 2026-10-15; the final screen is included because it has no clip
    status: completed
  - id: idle-state
    content: isAvatarIdle() takes screenWithoutClip, new pure isScreenWithoutClip(), tests
    status: completed
  - id: flow
    content: showPanel() sets the state, syncAvatarIdle() reads it, closeTimeUp() recomputes it from the screen it returns to
    status: completed
  - id: verify
    content: Real flow in wp-env to the identity screen, idle flips logged, resume after reload
    status: completed
---

# [ENGR-7099] Idle portrait on the screens without a clip

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7099

## Problem
`playPrerenderedClip()` only pauses the `<video>` and `data-status` stays `live`, so the last frame of
the previous clip stays on the identity, comm-channels and final-continue screens. A visitor who
resumes at identity gets the neutral portrait (`status: idle`), so the two paths differ.

## Approach
- `js/aicoach/idle-state.js`: `isAvatarIdle()` also returns true while the screen on show has no clip
  (and no answer video is playing); `isScreenWithoutClip( panelKey, clipPanelKeys )` is true for every
  screen except the intro and the screens in `PRERENDERED_PANEL_MAP`.
- `js/aicoach-coach-flow.js`: `showPanel()` records whether the screen has a clip and recomputes
  `.is-idle` through the existing `syncAvatarIdle()` (reached through a hook, since it lives in the Ask
  Sami closure). `closeTimeUp()` recomputes it from the screen it gives back instead of always removing it.
- CSS is unchanged: `.is-idle` already swaps the frame for the portrait, drops the animation under
  `prefers-reduced-motion`, and is skipped while connecting.

## Alternatives considered
- A pre-rendered silent loop from Gary: best fidelity, but the Video API renders a script, not silence,
  and it adds assets. Left as a later upgrade.
- Leaving the last frame: the inconsistency with the resume path stays.

## Blast radius
Every screen change recomputes the idle state; clip screens compute false, so the behaviour between two
clips is unchanged (checked: one idle flip in a full run, at identity). On the desktop the portrait is the
wide banner picture, a different person from the current clips; that is part of the open banner question
for Filip.

## Test plan
- [x] `npm run test:js` (191 passing), `node --check`
- [x] Real flow in wp-env to identity: `is-idle` turns on once, at identity; after a reload at identity it is on
- [ ] Stryker mutation run and a real phone (not run)
