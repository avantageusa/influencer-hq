---
name: PO-3105 (FR-14) — language change mid-flow restarts the current clip
overview: >
  Scenario 26 requires that changing language mid-flow keeps the visitor on
  the same screen with all captured data intact, and restarts the current
  screen's avatar video from the beginning in the new language. Progress
  preservation (tier/identity/channels) already existed from PO-3102, and
  PO-3107 already made every pre-rendered clip language-aware — the one
  missing piece was actually swapping the currently-playing clip when the
  visitor changes language without waiting for the next screen.
todos:
  - id: restart-current-clip
    content: "selectLocale() (js/aicoach-coach-flow.js) now calls
      restartCurrentClipForLocale(), which looks up the current screen
      (SCREENS[sequenceIndex]) and, if it has a pre-rendered clip and isn't
      the live Gary/Anam session (video.srcObject unset), swaps the video to
      that segment's clip in the new language and restarts it from 0 — all
      without touching sequenceIndex or the runFallback() loop."
    status: completed
  - id: fix-stale-rejection-race
    content: "Found live while verifying: playPrerenderedClip()'s pending
      video.play() promise for the clip being replaced could still reject a
      moment after the restart swapped video.src, and its .catch(onError)
      would then wrongly resolve the CURRENT screen as 'failed', skipping to
      the next screen instead of showing the just-restarted clip. Fixed by
      giving playPrerenderedClip() a generation counter — each load() (initial
      or restart) captures its own generation, and a play() rejection is only
      treated as real if no newer load() has since superseded it. Exposed via
      a new activeClipRestart closure variable (set for whichever clip is
      currently in flight, mirroring the existing skipCurrent pattern) so
      restartCurrentClipForLocale() reuses the exact same pending promise
      rather than manipulating the <video> element directly."
    status: completed
  - id: verify
    content: "node --check clean. Verified live on wp-env: this specific test
      browser's autoplay policy rejects every prerendered clip's play() call
      almost immediately regardless of this change (a pre-existing,
      environment-specific quirk — the <video autoplay> attribute itself
      still plays the media natively either way), which made the restart
      race hard to observe directly. Confirmed the real bug and fix with
      console instrumentation (temporary, not committed) showing the stale
      rejection firing before a click could land, then confirmed the actual
      fix by patching HTMLMediaElement.prototype.play in the browser console
      to behave like a normal successful play() (never rejecting) and
      re-running the same scenario: language switch mid we_believe_2/
      magic_johnson correctly swapped video.src to the new locale's clip and
      resumed playback (video.currentTime advancing, not paused) on the same
      screen, with no premature advance to the next one."
    status: completed
---

# PO-3105 (FR-14) — language change mid-flow restarts the current clip

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude Code (Sonnet 5)

## Problem

Scenario 26: a visitor who has progressed into the flow (tier selected,
identity typed) changes language — they must stay on the same screen with
everything they entered intact, and the current screen's avatar video must
restart from the beginning in the new language. Progress preservation
already existed (PO-3102) and every pre-rendered clip already had per-language
URLs (PO-3107); `selectLocale()` itself explicitly documented that it did not
yet restart a currently-playing clip.

## Approach

`playPrerenderedClip()` already owns the one `<video>` element's 'ended'/
'error' listeners for whichever clip is in flight. Rather than having
`selectLocale()` reach into the DOM directly, it now calls into that same
pending clip's own restart hook (`activeClipRestart`), so the exact promise
`runFallback()` is awaiting keeps resolving correctly — no new sequencing
state, no re-entrant `runFallback()` call.

## Bug found during verification

Restarting mid-flight exposed a real race: the clip being replaced can have
its `video.play()` promise still pending. If it rejects (media source
changed/interrupted) after the restart already swapped in the new clip,
`onError()` would fire, resolve the CURRENT screen as failed, and the
sequence would advance past the new clip before it had a chance to play.
Fixed with a per-`load()` generation counter, so only the *current* attempt's
rejection is treated as a real failure — one already superseded by a restart
is silently ignored.

## Blast radius

- `js/aicoach-coach-flow.js` only. `runFallback()`'s loop, `sequenceIndex`,
  and PO-3102/3107's own logic are all untouched.
- Does not reconnect the live Gary/Anam session for the "intro" screen — that
  stays a documented, separate gap (closing/reopening a WebRTC session is a
  bigger piece of work than swapping an `<video src>`).
