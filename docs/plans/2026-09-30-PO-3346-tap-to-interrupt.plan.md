---
name: PO-3346 — tap-to-interrupt replaces tap-to-skip
overview: >
  FR-19's Definition of Done ("Tapping the Coach video pauses her
  mid-delivery and opens the voice question state on every screen") directly
  conflicted with PO-3093/FR-02's own requirement text ("Screens advance
  based on narration timing or visitor tap/click"), which is where the
  existing tap-to-skip behavior on .aicoach-stage came from. Flagged as a
  comment on the PO-3062 epic (2026-09-29); Ivan Vladić resolved it
  (2026-09-30): there should be no tap-to-skip at all — Marcus never wanted
  the flow skippable — and FR-02 is being edited to drop that clause. This
  implements that decision: the stage's tap/click now opens Ask Sami instead
  of advancing early.
todos:
  - id: remove-skip
    content: ".aicoach-stage's click listener no longer calls skipCurrent().
      Screens still advance on their own via each screen's dwell timer or
      its clip's natural 'ended' event — that was never driven by this
      listener, only early-advance was, so nothing else in the sequence
      changes. The tier-selection click handler's own early-advance
      (a visitor confirming a time tier) is unrelated and untouched."
    status: completed
  - id: tap-opens-panel
    content: "The same listener now calls a new toggleAskSamiPanel()
      (js/aicoach-coach-flow.js), exposed from buildAskSami() the same way
      closeAskSamiPanel already is. It's the exact toggle logic the Ask
      Sami button's own click handler used inline before — extracted into
      a named togglePanel() so both triggers share one implementation
      rather than two copies that could drift. Guards against a
      disabled/hidden button (no language support yet, or no live session)
      since the stage listener bypasses the native disabled-button
      protection a real <button> click would have."
    status: completed
  - id: event-bubbling-fix
    content: "Found while wiring this up, not before: Ask Sami's own
      document-level 'click outside closes the panel' listener has no
      target check, so opening the panel from a stage tap — which doesn't
      stop propagation — would have the same click immediately bubble to
      that listener and close it again. Fixed with event.stopPropagation()
      in the stage listener, at the point it actually opens the panel.
      That stopPropagation() also means this click no longer reaches
      document's unmuteOnFirstInteraction() listener, so the stage listener
      now calls it explicitly too — same pattern this file already uses for
      the language selector and Ask Sami's own button, both of which
      stopPropagation() for the same underlying reason."
    status: completed
  - id: intro-scope
    content: "Intro is no longer excluded from the tap listener (the old
      code specifically skipped tap-to-skip on 'intro' to protect the live
      conversation from being skipped mid-speech) — FR-19's DoD says 'every
      screen', and Ask Sami's own button is already clickable during intro
      today, with the same known gap (see Notes). Removing the intro
      exclusion just means tap and the button behave identically there too,
      rather than the tap gesture specifically carving out an exception the
      button never had."
    status: completed
  - id: verify
    content: "node --check clean. Live-verified on wp-env: tapping the stage
      on a caption/clip screen (believe-1, no live Anam connection involved)
      opens Ask Sami, pauses the video, and the sequence correctly stays on
      that screen for 10+ seconds while the panel is open (confirms this
      composes correctly with PO-3346's existing pauseSequenceTimers/
      resumeSequenceTimers work from PR #62/#63) — closing via the close
      button resumes normally, and tapping the stage again correctly
      toggles the panel open and closed repeatedly. Also tapped the stage
      during the live 'intro' screen: the panel opened correctly, but the
      sequence advanced to believe-1 a few seconds later regardless — this
      is the pre-existing, already-documented gap (waitForSpeechOrSkip(),
      the live intro's own wait, was never covered by the pause mechanism;
      see that code's own comment), not something this change introduces or
      was expected to fix. The Ask Sami button has always had the identical
      behavior during intro. No new console errors in either test."
    status: completed
---

# PO-3346 — tap-to-interrupt replaces tap-to-skip

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Ticket:** https://avantageusa.atlassian.net/browse/PO-3346
**Drafted by:** Claude Code (Sonnet 5)

## Problem

Two real, written requirements governed the same tap gesture on the same
video element and contradicted each other:

- PO-3093 (FR-02): "Screens advance based on narration timing or visitor
  tap/click" — the origin of the existing tap-to-skip behavior (confirmed
  via `git log -S skipCurrent`, this is where it was introduced).
- FR-19's Definition of Done (added to the PO-3062 epic): "Tapping the Coach
  video pauses her mid-delivery and opens the voice question state on every
  screen, in every tier and language."

Flagged as a comment on the epic rather than guessed at; Ivan Vladić
resolved it directly: no tap-to-skip at all, full stop, and FR-02 itself is
being edited to remove that clause.

## Approach

Replace, not add alongside: `.aicoach-stage`'s click listener stops calling
`skipCurrent()` and calls the new `toggleAskSamiPanel()` instead — the same
open/close toggle the button already exposes, so a tap and a button click
produce byte-for-byte the same result, matching the AC's own wording ("...
producing the same state as the tap").

## Alternatives considered

- **Keep tap-to-skip on caption-only screens, tap-to-interrupt only on
  screens with real audio (prerendered clips, the live intro).** This was
  the hybrid design considered before Ivan's answer came back — rejected
  once the answer was "no skipping at all," which is simpler to implement
  and reason about than a per-screen-type branch would have been.
- **A separate tap handler for interrupt, left alongside the old one with a
  flag to pick which fires.** Rejected — there's no scenario where both
  behaviors are wanted at once anymore, so a flag would just be dead
  branching with no live "off" state.

## Blast radius

- `js/aicoach-coach-flow.js` only. Changed: `.aicoach-stage`'s click
  listener (body replaced, not restructured elsewhere), `buildAskSami()`
  (button's inline toggle logic extracted into `togglePanel()`, exposed as
  `toggleAskSamiPanel`). New module-level variable:
  `toggleAskSamiPanel`, following the existing `closeAskSamiPanel` pattern.
  `skipCurrent` itself, the tier-selection click handler, and every dwell/
  clip-end resolution path are untouched — they never depended on the stage
  listener to begin with.

## Notes

**The live intro screen's own gap is unchanged, not newly introduced.**
`waitForSpeechOrSkip()` (intro's wait for Gary's real, live line) was never
covered by `pauseSequenceTimers()`/`resumeSequenceTimers()` — documented
already in `2026-09-29-PO-3346-sequence-pause-timers.plan.md` — because
pausing local video rendering doesn't pause the actual conversation
happening server-side. Ask Sami's button has always been clickable during
intro with this same gap; making tap behave identically there is consistent
with the button, not a new, separate problem to solve here.

**A minor, deliberately-accepted edge case**: the stage tap's
`event.stopPropagation()` (needed to stop Ask Sami's own "click outside
closes" listener from immediately closing what the tap just opened) also
means a stage tap no longer incidentally closes the language selector
dropdown if a visitor left it open and then tapped the stage. Before this
change, that closing was a side effect of the same event bubbling to
document, not an intentional feature. Not fixed here — two unrelated open
panels is a minor cosmetic overlap, not a functional bug, and wiring a third
exposed "close" function for it felt like solving a problem nobody has
reported.
