---
name: PO-3094 — Time selection screen (FR-03)
overview: >
  Adds the FR-03 approved script as a 4th entry in the coach's screen sequence, spoken over the
  existing tier-selection UI (the "home" panel) rather than a separate screen — the AC has Sami's
  pitch play while the three time options are already visible, not before them. Confirming a tier
  (existing click handler) now also acts as the FR-02-style early-advance so her voice doesn't
  fight the panel the visitor already chose, marks the sequence done, and captures an elapsed-
  time-start timestamp for FR-17 (a separate story) to build on. No new screen, no new panel.
todos:
  - id: home-caption
    content: Add a .aicoach-caption element inside the existing "home" panel so the coach's time-selection line has somewhere to render, matching intro/We-Believe
    status: completed
  - id: screens-entry
    content: Add {panel:'home', script:<approved FR-03 script>} as SCREENS' 4th entry
    status: completed
  - id: confirm-coordination
    content: Tier click handler now also calls skipCurrent() (ends the coach's line early if still speaking) and sets sequenceFinished=true, so runSequence's own advance doesn't undo the visitor's navigation to the equity-story panel
    status: completed
  - id: elapsed-tracking
    content: Capture elapsedStartedAt/selectedTierMinutes (in-memory only) the moment a tier is confirmed, as a hook for FR-17 — not building the countdown/prompt itself
    status: completed
  - id: verify
    content: php -l, composer lint:php (only the known unrelated failure), phpcs (497 vs 496 baseline — the one new finding is the same pre-existing tabs/spaces convention, not a new category), node --check on the JS, static-mockup screenshot of the home panel with caption
    status: completed
---

# PO-3094 Time selection screen — Scenario 3 (FR-03)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3094 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Problem

FR-03 requires the coach to deliver an approved time-selection script when the tier-selection
screen appears, with the 2-minute tier pre-selected (already true) and elapsed-time tracking
starting when the visitor confirms. The existing "home" panel (built ahead of schedule during
PO-3092/93 as a static demo) already has the correct tier UI, but nothing spoke over it and
nothing captured a confirm timestamp.

## Approach

1. **No new panel.** The AC's wording ("the time selection screen appears... the coach delivers
   the approved... script... three selectable options are displayed") describes one screen, not
   a spoken intro screen followed by a silent picker. Added the script as the sequence's 4th
   entry targeting the existing `home` panel, and added a `.aicoach-caption` element inside it
   (previously the panel had no caption slot at all).
2. **Reconciled tap-to-advance with tier-click-to-confirm.** The existing FR-02 mechanism lets a
   tap end the current line early via `skipCurrent()`; a tier click already had its own
   independent handler that jumps straight to the chosen tier's story panel. Without
   coordination, confirming a tier while Sami is still mid-sentence would leave `runSequence`
   still awaiting her line, and once it naturally finished, `finishSequence()` would call
   `showPanel('home')` again — undoing the visitor's choice. Fixed by having the tier click
   handler also call `skipCurrent()` and set `sequenceFinished = true` before navigating, so the
   loop's own continuation sees `sequenceFinished` and exits without touching the panel again.
3. **Elapsed-time capture is deliberately minimal.** `elapsedStartedAt` / `selectedTierMinutes`
   are plain in-memory variables set on confirm — FR-17 (time-remaining check) is a separate
   story that owns the actual countdown/prompt UI; this just gives it a starting mark to read.
   Not persisted (that's FR-11/Luna's job, also separate), not exposed on `window` since FR-17 is
   expected to extend this same file rather than a different one.

## Alternatives considered

- **A distinct "time" screen before the tier picker, silent picker after:** more literally mirrors
  "screen sequence" phrasing but contradicts the AC's own "the coach delivers... And three
  selectable options are displayed" — read together as simultaneous, not sequential.
- **Persist elapsed-time-start to sessionStorage now:** rejected — FR-11 (Luna) is explicitly the
  story that owns cross-session persistence; adding a parallel ad hoc persistence mechanism here
  would likely conflict with or duplicate that story's eventual design.

## Blast radius

- `page-home-aicoach.php`: one new `<p class="aicoach-caption">` line in the `home` panel, one
  CSS tweak (`.aicoach-caption` gets a bottom margin — cosmetic, affects all caption instances
  uniformly, verified via static mockup screenshot).
- `js/aicoach-coach-flow.js`: additive — new `SCREENS` entry, new state vars, tier click handler
  extended (still does everything it did before, plus the new coordination/capture).
- No change to the equity-example panels (`data-panel="2"/"5"/"10"`) — FR-05 territory, untouched.

## Notes

- Same **known-interim-architecture** note as PO-3092/PO-3093 — this still drives Sami directly
  via Anam, pending Gary's API (approved as temporary by Filip Milinković, 2026-08-28).
- **phpcs:** 497 findings vs. 496 before this change — the +1 is the same pre-existing
  tabs-vs-spaces-in-markup convention already present throughout this file (and every
  `page-home-*` sibling), not a new violation category. See PO-3092/3093's fast-follow PR (#5)
  notes for the fuller investigation of this.
