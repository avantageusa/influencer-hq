---
name: PO-3108 — Time-remaining check (FR-17)
overview: >
  Adds a coach prompt that interrupts whichever screen is currently active once the visitor has
  used up a configurable proportion of their selected time tier, asking whether they have
  additional time. Yes dismisses and resumes with no loss of progress; No dismisses (FR-18's
  appointment-scheduling flow, PO-3109, isn't built yet — wiring deferred). Both the trigger
  threshold and the prompt copy are explicitly unconfirmed/unapproved in the ticket, so both are
  implemented as clearly-flagged placeholders.
todos:
  - id: time-check-overlay-markup
    content: Add the .aicoach-time-check overlay markup (caption, Yes/No buttons) in page-home-aicoach.php, positioned to sit above whichever .aicoach-panel is active rather than being one of the panels itself
    status: completed
  - id: time-check-overlay-css
    content: Add matching CSS — fixed backdrop overlay, centered box, flex button row, hidden by default via display:none / .is-visible{display:flex}
    status: completed
  - id: schedule-on-tier-click
    content: Call scheduleTimeRemainingCheck(tierMinutes) from the existing tier-click handler (js/aicoach-coach-flow.js), right where elapsedStartedAt/selectedTierMinutes are already set, per the ticket's "elapsed time from the moment the visitor confirms their time tier (FR-03)" requirement
    status: completed
  - id: timer-and-handlers
    content: "setTimeout-based timer keyed off TIER_DURATION_MS[tier] * TIME_REMAINING_THRESHOLD_RATIO; a timeRemainingPromptShown flag makes it fire at most once per session (Scenario 30); Yes just hides the overlay (no state to restore since it overlays in place); No hides the overlay with a comment marking where FR-18 wiring goes once PO-3109 exists"
    status: completed
  - id: placeholder-threshold-and-copy
    content: "Threshold hardcoded as TIME_REMAINING_THRESHOLD_RATIO = 0.8 (80%) and prompt copy hardcoded as plain placeholder English text — both explicitly called out in code comments, this plan, and the PR description as pending confirmation/approval per the ticket's Open Questions"
    status: completed
  - id: verify
    content: php -l, node --check, composer lint:php (only the known unrelated pre-existing failure), phpcs (delta is proportional pre-existing-style debt, not a new issue — see Notes), live wp-env testing of the timer scheduling, overlay visuals, and both Yes/No handlers
    status: completed
---

# PO-3108 Time-remaining check — Scenario 29/30 (FR-17)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3108 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Problem

Once a visitor confirms a time tier (FR-03), the coach needs to track elapsed time and, if the
visitor is approaching the end of their selected tier without having reached the final CONTINUE,
interrupt whichever screen is showing with a Yes/No prompt. Yes resumes with no loss; No is meant
to trigger FR-18's appointment scheduling (PO-3109), a separate story not yet built. Both the exact
trigger threshold and the prompt copy are marked in the ticket as still needing confirmation/
approval.

## Approach

1. **Overlay, not a SCREENS/panel entry.** Every prior FR screen is one `.aicoach-panel` in the
   `SCREENS` queue, shown one at a time. This prompt is different: it needs to interrupt *whichever*
   panel currently happens to be active (equity, competition, identity, comm-channels — any of
   them), not replace it. So it's a separate fixed-position overlay (`#aicoach-time-check`) that
   layers on top of the active panel rather than becoming one, toggled via an `.is-visible` class
   independent of the `showPanel()` panel-switching logic.
2. **Timer keyed to tier confirmation, not page load.** `scheduleTimeRemainingCheck(tierMinutes)` is
   called from the existing tier-click handler, at the same point `elapsedStartedAt` is already
   recorded — matching the ticket's "elapsed time from the moment the visitor confirms their time
   tier (FR-03)" wording exactly. `TIER_DURATION_MS` maps each tier (`'2'`/`'5'`/`'10'`) to its
   total duration in ms; the timer fires at `TIER_DURATION_MS[tier] * TIME_REMAINING_THRESHOLD_RATIO`.
3. **Fires at most once per session.** A `timeRemainingPromptShown` flag, set the first time the
   timer callback runs, guards against a second timer somehow firing again — satisfies Scenario 30's
   "does not fire again" explicitly. (There's currently only one path that schedules the timer, so
   in practice this is a single-fire timer either way; the flag is defensive.)
4. **No guard needed for "already registered" case.** If the visitor completes registration before
   the timer fires, PO-3100's account-creation success handler already navigates away to the portal
   — the page (and the pending `setTimeout`) is gone by then, so there's nothing extra to wire up.
5. **Placeholders, explicitly flagged, for both open questions.** The ticket's own "Open Questions"
   section leaves the trigger-threshold proportion and the prompt copy unresolved. Rather than guess
   at values that might contradict what gets decided, both are implemented as clearly-marked
   placeholders — `TIME_REMAINING_THRESHOLD_RATIO = 0.8` and plain English placeholder copy — with
   comments at each definition site pointing back to the open questions, so a future update is a
   one-line change in each place rather than a hunt through the code.
6. **No's FR-18 wiring is a stub.** FR-18 (appointment scheduling, PO-3109) isn't built yet. No's
   click handler hides the overlay and leaves a comment marking exactly where the real scheduling
   trigger goes once that story exists, rather than guessing at an API/flow that doesn't exist.

## Alternatives considered

- **Making this a `SCREENS` entry like every other FR:** rejected — it needs to interrupt an
  *arbitrary* in-progress screen rather than take its own turn in sequence, which the
  `SCREENS`/`showPanel()` model doesn't support without much more invasive changes.
- **Guessing a specific threshold (e.g. exactly matching some other product's convention) or writing
  more elaborate placeholder copy:** rejected — same reasoning as FR-07/FR-08's TBD fields elsewhere
  in this epic: an invented-but-plausible value risks quietly surviving un-updated once the real one
  is confirmed, whereas an obviously-generic placeholder is more likely to get caught and swapped.

## Blast radius

- `page-home-aicoach.php`: one new overlay markup block + its CSS block. No changes to existing
  panels, PHP config arrays, or other CSS.
- `js/aicoach-coach-flow.js`: two new constants, one line added to the existing tier-click handler,
  one new self-contained block (timer scheduling + both button handlers) with no changes to
  `SCREENS`, `showPanel()`, `finishSequence()`, or any other existing function.

## Notes

- **phpcs delta**: this file already carries ~777 pre-existing `DisallowSpaceIndent` violations
  (space indentation throughout, conflicting with `phpcs.xml`'s tab rule) predating this story —
  same pre-existing-debt situation as the `text_domain` mismatch flagged in PO-3099's plan doc. The
  new markup/CSS added here matches the file's actual (space-indented) surrounding style exactly, so
  the ~76-violation increase is proportional to the lines added, not a new/different problem
  introduced by this change. Not fixed here, consistent with the established pattern of not doing
  drive-by reformatting of pre-existing debt.
- **Live-tested in wp-env**: confirmed the timer schedules with the correct delay for the clicked
  tier (verified 5-minute tier → 240000ms = `5 * 60 * 1000 * 0.8`, matching
  `TIME_REMAINING_THRESHOLD_RATIO`), the overlay renders visibly with correct copy/buttons when
  forced open, and both Yes and No correctly hide it with no console errors introduced (the only
  console errors present are pre-existing and unrelated — a 501 from the unconfigured
  `ANAM_API_KEY` on localhost, and Cloudflare Turnstile failing on a non-production domain).
- Same known-interim-architecture note as every prior story in this epic (direct Anam integration,
  approved temporary by Filip Milinković pending Gary's agent API) — this story doesn't touch that
  code at all, so it's unaffected either way.
- FR-18 (PO-3109, appointment scheduling) is a natural next story — it's the other half of this
  ticket's "No" branch.
