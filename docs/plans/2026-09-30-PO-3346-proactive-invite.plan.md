---
name: PO-3346 — Sami proactively invites questions at 3 defined beats
overview: >
  PO-3330's AC: "Given I have just finished the equity example, or am about
  to reach identity capture or the communication channels screen / When
  that beat is reached / Then the Coach pauses and invites me to ask
  anything, and continues on her own if I do not." No approved script line
  exists for this prompt (unlike every spoken screen in this file), so this
  implements it as a UI-level nudge on the existing Ask Sami entry point —
  a brief highlighted pulse and a fixed, pausable dwell — rather than
  inventing new avatar dialogue.
todos:
  - id: invite-mechanism
    content: "New inviteQuestionOrSkip() — adds .is-inviting to the Ask Sami
      button (CSS pulse, page-home-aicoach.php) and waits via
      createPausableTimeout() (the same shared primitive PO-3346's earlier
      timer-pause work already established and proved), 6 seconds
      (INVITE_DWELL_MS). No-ops immediately if Ask Sami isn't actually
      available right now (button missing/disabled, or hidden outside
      English) — nothing to invite toward in that case. Because it's a
      registered pausable timer, opening Ask Sami during the invite window
      pauses it exactly like every other sequence timer already does
      (pauseSequenceTimers(), called by openPanel()) — no bespoke
      coordination needed, this composes with existing, already-tested
      infrastructure for free."
    status: completed
  - id: three-call-sites
    content: "Wired at exactly the 3 points the AC names: (1) inside
      runFallback()'s loop, right after the equity-bts screen's own
      dwell/clip finishes — only when something else in SCREENS actually
      follows it (competition types, 5/10-minute tiers); (2) inside
      finishSequence(), before showPanel('identity') — this is also where
      the 2-minute tier's 'after equity' beat effectively lands, since
      equity-bts is that tier's last queued screen with nothing else
      between it and identity, so the two AC beats collapse into one
      invite rather than firing twice back to back; (3) in the identity
      form's submit handler, after saveProgress() and before
      SCREENS.push(COMM_CHANNELS_SCREEN) — not in applyResumedIdentity()
      (PO-3102's resume path just restores already-completed UI state, it
      isn't a live 'just finished this step' moment)."
    status: completed
  - id: visual-cleanup
    content: "openPanel() strips .is-inviting the moment the visitor actually
      opens the panel — the pulse has done its job; the underlying dwell
      still ends normally later via the existing pause/resume machinery,
      it just isn't visually highlighted anymore once they're already
      looking at the panel it was pointing at."
    status: completed
  - id: verify
    content: "node --check and php -l clean. Live-verified on wp-env with a
      continuous state log (500ms polling) across two of the three beats,
      not just a single before/after snapshot — confirms the actual
      timeline, not just the end state: (1) 2-minute tier, equity-bts ->
      identity: .is-inviting appeared right as the clip ended, held for
      the full ~6s dwell, then cleared and identity became active
      immediately after, nothing early. (2) identity form submit ->
      comm-channels: same pattern, invite starts at submit, ~6s later
      comm-channels becomes active. The third beat (after equity WITH
      competition screens following, 5/10-minute tiers) uses the identical
      mechanism gated by a simple, directly-traced boolean condition
      (sequenceIndex + 1 < SCREENS.length) rather than being separately
      live-run — the two beats already verified cover the actual
      mechanism end to end. No console errors from either run (one
      pre-existing, unrelated 404 on a username-availability endpoint, not
      touched by this change)."
    status: completed
---

# PO-3346 — Sami proactively invites questions at 3 defined beats

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Ticket:** https://avantageusa.atlassian.net/browse/PO-3346
**Drafted by:** Claude Code (Sonnet 5)

## Problem

PO-3330's AC requires the coach to proactively pause and invite questions at
three specific points in the flow, continuing on her own if the visitor
doesn't engage. Unlike every other screen in this file, there's no approved
script line for what she'd actually say here — checked the same Jira/Figma
sources that gave every other screen its verbatim copy, found nothing for
this specific prompt.

## Approach

Treat "invites questions" as a UI-level affordance rather than invented
dialogue: highlight the existing Ask Sami button (a pulse animation,
matching `.aicoach-ask-mic.is-listening`'s existing visual language) for a
fixed dwell, then continue automatically. Built directly on
`createPausableTimeout()` — the exact primitive already proven across
PO-3346's earlier work — rather than a new timer mechanism, so "continues on
her own if I do not" and "pauses correctly if I open Ask Sami during the
invite" both fall out of infrastructure that already exists and is already
tested, instead of needing new coordination code.

## Alternatives considered

- **Invent a plausible spoken line** ("Do you have any questions so far?")
  and have the avatar speak it. Rejected — this codebase has consistently
  never invented unapproved avatar copy for any other screen (translations,
  scripts, even UI microcopy that needed sign-off), and this prompt is
  spoken dialogue exactly like every other screen's script, not incidental
  UI chrome text like a button label or error message.
- **A separate, dedicated timer/mechanism for the invite dwell** instead of
  reusing `createPausableTimeout()`. Rejected — would need its own
  pause/resume wiring to correctly handle "visitor opens Ask Sami during the
  invite," duplicating work `createPausableTimeout()` and
  `pauseSequenceTimers()` already do for every other screen's dwell.
- **Fire the "after equity" and "before identity" invites separately even
  when they're back-to-back** (2-minute tier, no competition screens
  between them). Rejected as poor UX — two consecutive 6-second pulses with
  nothing happening between them reads as a glitch, not two distinct
  moments. The loop's own beat checks whether anything actually separates
  them before firing.

## Blast radius

- `js/aicoach-coach-flow.js`: `inviteQuestionOrSkip()` and
  `INVITE_DWELL_MS` (new), `finishSequence()` (now `async`, one new
  conditional await — every existing caller already ignored its return
  value, so this is a no-op change for them), `runFallback()`'s loop (one
  new conditional block after the existing `sequenceFinished` check),
  identity form's submit handler (one new `await` line), `openPanel()`
  (one new `classList.remove()` line). Nothing about the sequence loop's
  core iteration, dwell timing for any other screen, or the identity/
  comm-channels handoff logic itself changed.
- `page-home-aicoach.php`: CSS only — one new rule + keyframe block, styled
  after the existing mic-listening pulse.

## Notes

**Only wired for the resume-unaffected, "just happened live" moments** —
`applyResumedIdentity()` (PO-3102's restore-on-return path) deliberately
does not get an invite call; a returning visitor isn't "just now" finishing
identity capture, their data is simply being redisplayed.

**English-only**, consistent with Ask Sami itself (already hidden outside
`'en'` — `inviteQuestionOrSkip()`'s own guard naturally no-ops when the
button isn't there/visible, no separate locale check needed here).
