---
name: PO-3346 — don't show the time-remaining prompt over an open Ask Sami panel
overview: >
  PO-3330's AC splits this into two separate requirements that pull in
  different directions: elapsed-time tracking against the selected tier must
  keep counting THROUGH a Q&A exchange (time spent asking Sami a question
  still counts against the tier), but the time-remaining PROMPT itself must
  wait until an open Q&A exchange finishes before it's shown, rather than
  popping up layered on top of it. scheduleTimeRemainingCheck() previously
  used a plain window.setTimeout() with no awareness of Ask Sami at all, so
  the prompt could appear over an open panel — only its z-index (PR #61) kept
  it visually on top rather than actually deferring it.
todos:
  - id: defer-show-not-count
    content: "scheduleTimeRemainingCheck()'s underlying window.setTimeout()
      is deliberately left untouched (still a plain, unpaused timer) — the
      real-time countdown against the tier must not pause during Q&A. Split
      out a new maybeShowTimeRemainingCheck(): when the countdown fires, it
      only shows the overlay if Ask Sami's panel isn't currently open;
      otherwise it sets a timeRemainingCheckPending flag and returns without
      showing anything."
    status: completed
  - id: wire-close
    content: "closePanel() (Ask Sami) checks timeRemainingCheckPending after
      resuming the sequence timers and restoring video playback; if set, it
      clears the flag and calls maybeShowTimeRemainingCheck() again, which
      now shows the overlay immediately since the panel is closed."
    status: completed
  - id: verify
    content: "node --check clean. Live-verified on wp-env end to end: fresh
      visitor, confirmed the 2-minute tier (scheduleTimeRemainingCheck fires
      at 80% = 96s), opened Ask Sami immediately after, kept the panel open
      past the 96s mark — confirmed via direct DOM/class inspection that the
      overlay did NOT appear while the panel stayed open. Closed the panel
      and confirmed the overlay appeared immediately (aria-hidden flips to
      false, is-visible class added) instead of on the original 96s
      schedule. Screenshot taken of the overlay appearing correctly on
      close."
    status: completed
---

# PO-3346 — don't show the time-remaining prompt over an open Ask Sami panel

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Ticket:** https://avantageusa.atlassian.net/browse/PO-3346
**Drafted by:** Claude Code (Sonnet 5)

## Problem

PO-3330's AC: *"Given my selected tier is running out while I am mid-answer,
when the time-remaining prompt is due, then it waits until the answer has
finished before firing."* `scheduleTimeRemainingCheck()` had no idea Ask Sami
existed — its `window.setTimeout()` fires the overlay on a fixed real-time
schedule regardless of what else is on screen. PR #61 gave the overlay a
higher z-index than the Ask Sami panel so it would at least render on top
correctly, but that's a stacking fix, not a timing one — the prompt could
still interrupt a visitor mid-question.

## Approach

Two different timers, doing two different jobs, both required by the AC:

1. The **countdown** (when does the tier's time run out) must keep running
   in real time through a Q&A exchange — a separate AC bullet requires
   elapsed time to count against the tier while Ask Sami is in use. This
   stays a plain `window.setTimeout()`, untouched, and deliberately does
   **not** go through `createPausableTimeout()`/`pausableSequenceTimers`
   (unlike PO-3346's other timers) — pausing it would be wrong here, not
   just unnecessary.
2. The **prompt** (whether to actually show the overlay right now) is a
   separate decision, made in the new `maybeShowTimeRemainingCheck()`: show
   it immediately if Ask Sami's panel is closed, otherwise remember that it's
   due (`timeRemainingCheckPending`) and let `closePanel()` show it the
   moment the panel actually closes.

## Alternatives considered

- **Route the countdown itself through `createPausableTimeout()`**, matching
  the pattern used for the sequence's other timers in this same ticket.
  Rejected — that would pause the real-time countdown while Ask Sami is
  open, directly contradicting the AC that elapsed time must keep counting
  against the tier through a Q&A exchange. The other timers in PO-3346 exist
  to advance a *screen*, which genuinely should wait; this one tracks *real
  session time*, which shouldn't.
- **Just rely on PR #61's z-index fix and leave it.** That satisfies "the
  visitor can still see and dismiss the prompt" but not the actual AC
  wording ("waits until the answer has finished before firing") — a visitor
  mid-question would still have the overlay appear on top of them
  unannounced, interrupting the exchange rather than waiting for it.

## Blast radius

- `js/aicoach-coach-flow.js` only. New: `maybeShowTimeRemainingCheck()`,
  `timeRemainingCheckPending`. Changed: `scheduleTimeRemainingCheck()` now
  schedules `maybeShowTimeRemainingCheck` instead of an inline callback;
  Ask Sami's `closePanel()` gains one conditional check. Nothing about the
  countdown's own duration, the tier-duration math, or the Yes/No button
  handlers changed.

## Notes

This is the "#7" item flagged while reviewing the rest of PO-3330's AC after
PR #62/#63 merged — noticed while re-reading `scheduleTimeRemainingCheck()`
to confirm it wasn't already covered by the pausable-timer work in
[2026-09-29-PO-3346-sequence-pause-timers.plan.md](2026-09-29-PO-3346-sequence-pause-timers.plan.md).
Same umbrella ticket (PO-3346), same "one ticket, not many sub-tickets"
scope decision — this is a separate plan doc rather than folding into that
one because it touches a genuinely different timer with an opposite pausing
requirement, not an extension of the same mechanism.
