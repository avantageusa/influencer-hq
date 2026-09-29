---
name: PO-3346 — pause the sequence's own timers while Ask Sami is open
overview: >
  CodeRabbit flagged this on PR #59/#61 and it was deliberately left
  unfixed at the time (a "heavy lift" not worth forcing into that PR):
  openPanel() (Ask Sami) only pauses the <video> element, so
  waitForReadOrSkip()'s dwell timer and playPrerenderedClip()'s 60s safety
  cap keep counting regardless, and the sequence can advance to a later
  screen (or start a new clip) while the question panel is still open on
  top of it.
todos:
  - id: pausable-timer
    content: "Added createPausableTimeout() (js/aicoach-coach-flow.js) — a
      setTimeout wrapper that can pause() (stop counting, remember what's
      left) and resume() (continue from there) instead of firing on its
      original schedule regardless of what else is happening. Registers
      itself in a shared pausableSequenceTimers Set on creation and removes
      itself once it actually fires or is cancel()led."
    status: completed
  - id: wire-timers
    content: "waitForReadOrSkip()'s dwell and playPrerenderedClip()'s 60s
      safety cap both now go through createPausableTimeout() instead of a
      bare window.setTimeout(). No change to their own logic otherwise —
      still resolve on 'ended'/a tap/the cap exactly as before, just
      pausable now."
    status: completed
  - id: wire-ask-sami
    content: "openPanel()/closePanel() (Ask Sami) call
      pauseSequenceTimers()/resumeSequenceTimers() alongside the existing
      video.pause()/play()."
    status: completed
  - id: verify
    content: "node --check clean. Extracted createPausableTimeout() /
      pauseSequenceTimers() / resumeSequenceTimers() and ran them standalone
      under Node (global.window = global as the only shim) to test the
      actual pause/resume delta math in isolation: a 500ms timer paused
      immediately and resumed 1000ms later fired at 1502ms total (expected
      ~1500 = 500 remaining + 1000 paused) — confirms 'remaining' is
      genuinely honored across a pause, not just reset or ignored. A second
      timer cancelled while paused never fired. pausableSequenceTimers.size
      was back to 0 once both resolved, confirming no leaked entries.
      End-to-end live-checking the actual Ask Sami scenario (open the panel
      mid-dwell on a real screen, confirm it hasn't advanced) wasn't
      practical this session — the live Anam/Gary connection needed to
      reach a screen with Ask Sami enabled was intermittently very slow/
      stuck in this environment during testing, unrelated to this change."
    status: completed
---

# PO-3346 — pause the sequence's own timers while Ask Sami is open

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Ticket:** https://avantageusa.atlassian.net/browse/PO-3346
**Drafted by:** Claude Code (Sonnet 5)

## Problem

Ask Sami's `openPanel()` pauses the avatar `<video>` element so the visitor
isn't watching the sequence race ahead while they're reading/typing. But the
sequence's own *timers* — `waitForReadOrSkip()`'s fixed caption dwell,
`playPrerenderedClip()`'s 60s safety cap — have no idea the panel opened and
keep counting down in real time. A visitor who takes longer than the
current dwell to ask a question can close the panel and find the sequence
has already moved to a later screen, or a new clip already started, behind
what they were looking at.

## Approach

Wrapped the two timers in a small pausable-timeout helper
(`createPausableTimeout()`) instead of rewriting either function's own
resolve logic. Each call site still just gets something that calls its
callback after N ms; the only change is that N ms of *real elapsed time*
now excludes however long the Ask Sami panel was open, via `pause()`/
`resume()` on that same handle. `openPanel()`/`closePanel()` call
`pauseSequenceTimers()`/`resumeSequenceTimers()`, which pause/resume every
currently-registered pausable timer (in practice, at most one is ever
running at a time, since the sequence loop is a single `for` awaiting one
screen at a time — a `Set` handles that without needing to track "the
current one" explicitly).

## Alternatives considered

- **A single module-level "is the sequence paused" flag, checked inside
  each timer's own callback before actually resolving.** Simpler on paper,
  but means a paused dwell still *fires* on its original schedule and just
  no-ops — the timer would need to reschedule itself anyway to actually
  wait out the remaining time once unpaused, which is most of what
  `createPausableTimeout()` already does. Rejected for not actually being
  simpler once the reschedule is accounted for.
- **Tear down and recreate the timer from scratch on resume, using
  `Date.now()` deltas tracked by the caller instead of a reusable helper.**
  Would work but duplicates the same pause/resume math at both call sites
  instead of once — rejected in favor of one small shared utility, matching
  how this file already prefers (`getCaptionScript()`/`getPrerenderedUrl()`
  share one shape rather than two near-identical functions).
- **Also cover `waitForSpeechOrSkip()`** (the live intro's wait). Rejected —
  see Notes. Pausing local video rendering doesn't pause the actual live
  conversation on Gary's side, so there's nothing to "resume from where we
  left off" for; a visitor opening Ask Sami during the live intro is a
  separate, harder problem than this ticket solves.

## Blast radius

- `js/aicoach-coach-flow.js` only. New: `createPausableTimeout()`,
  `pauseSequenceTimers()`, `pausableSequenceTimers` (module-level constants/
  functions). Changed: `waitForReadOrSkip()` and `playPrerenderedClip()`
  swap their timer type; Ask Sami's `openPanel()`/`closePanel()` gain two
  function calls each. `waitForSpeechOrSkip()`, `runFallback()`'s loop
  structure, and every other sequencing function are untouched.

## Notes

**Live intro screen (real Anam/WebRTC stream) is explicitly out of scope**,
per the Alternatives note above. Ask Sami is technically clickable during
that narrow window (enabled as soon as `garySessionId` is set, which
happens before `VIDEO_PLAY_STARTED`) — if that turns out to matter in
practice, it needs its own design (there's no local "pause" for a live
conversation happening on Gary's servers), not an extension of this fix.

**Not live-verified this session** — the actual race (open panel mid-dwell,
confirm the screen doesn't advance while it's open) needs reaching a
prerendered-clip screen with a live Gary session, which required an Anam/
Gary connection that was intermittently very slow to connect in this
environment during testing. Verified by code review (the pause/resume
delta math, and that both call sites needed no logic changes beyond the
timer type) instead. Worth an explicit live pass before considering this
fully closed.
