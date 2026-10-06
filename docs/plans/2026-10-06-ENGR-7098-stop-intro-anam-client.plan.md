---
name: Stop the live intro's Anam client once the intro has handed off
overview: >
  start() opens an Anam client for the live intro, stores it in `activeClient`
  and never touches it again, so its WebRTC connection stays open for the rest
  of the visit and appears to keep one of the account's concurrent avatar seats
  while the visitor watches pre-rendered clips. The Ask Sami video answer needs
  a seat of its own and falls back to audio/text when none is free. The fix
  releases the intro client right after the hand-off to the first clip and on
  pagehide, with the intro's CONNECTION_CLOSED handler detached first so the
  release is not mistaken for a dropped connection. Out of scope: the case of
  a visitor asking a question while the intro is still speaking (both clients
  exist for a moment), and any change to the Q&A client's lifecycle.
todos:
  - id: reproduce
    content: Confirm that the intro stream's tracks are still live after the first clip is playing
    status: completed
  - id: release-helper
    content: Add an idempotent releaseIntroClient() that detaches the intro CONNECTION_CLOSED handler, then calls stopStreaming(), and replace the unused activeClient
    status: completed
  - id: wire
    content: Call it right after the intro hand-off (before runFallback( true )) and on pagehide
    status: completed
  - id: verify
    content: Run tests, lint, and CI gate before merge
    status: pending
---

# [ENGR-7098] Stop the live intro Anam client after hand-off

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7098
**Drafted by:** Claude (claude-sonnet-5-5)

## Problem
The live intro's Anam client is created in `start()` (`js/aicoach-coach-flow.js`)
and assigned to `activeClient`, which is never read again. Nothing calls
`stopStreaming()` on it and there is no `pagehide` handling; only the Ask Sami
client (`qaClient`) is torn down. After the hand-off to the pre-rendered clips
the intro connection stays open. During ENGR-7051 testing the account hit
"Concurrency limit reached" and the first Ask Sami video answer fell back to
audio/text.

## Reproduction
1. Load the AI Coach page as a fresh visitor, keep a reference to
   `video.srcObject` while the intro plays (the Browser pane has to be
   visible, otherwise the intro never reaches `live`).
2. Let the intro hand off to `believe-1`.
3. Before the fix (wp-env, change stashed): the captured stream's tracks are
   `live` at the clip start and still `live` 12 s later (`active: true`).
   After the fix they are `ended` at the clip start.

How long Anam keeps a session counted after the page stops using it is not
verified; stale sessions did expire by themselves during earlier testing.

## Approach
Read from the Anam SDK 4.27.1 bundle the page loads (jsdelivr `+esm`):
`stopStreaming()` first **emits `CONNECTION_CLOSED`** and only then stops the
connection; it never touches the `<video>` element's `srcObject`.

- **Detach before stopping.** The intro's `CONNECTION_CLOSED` handler treats a
  close as a dropped connection (`avatarIsLive = false`, `runFallback()`).
  A release we trigger ourselves must not run that, so the handler is removed
  before `stopStreaming()` is called — the same order `teardownQaClient()`
  already uses for the Q&A client.
- **`releaseIntroClient()`**, module-level next to `activeClient`, assigned
  inside `start()` where the client and its handler exist. Idempotent (clears
  itself first) so the hand-off and `pagehide` can both call it. It replaces the
  dead `activeClient` variable. `stopStreaming()` is wrapped in try/catch with
  a warning like `teardownQaClient()`.
- **When (revised after review, Dejan Arsic).** In `playPrerenderedClip()`'s
  `load()`, right after `video.srcObject = null`, i.e. when a clip actually
  replaces the intro stream. The first version released at the hand-off, before
  `runFallback( true )`. That is wrong when no clip is rendered for the first
  screen (`getPrerenderedUrl()` returns `null`; a supported degrade path):
  `runFallback()` then never touches the `<video>`, `data-status` stays `live`,
  and a stopped stream renders empty in Chrome and Safari, so Sami would be a
  dark circle for the whole static flow instead of the live idle avatar she is
  today. Releasing at replacement time keeps the happy path identical (tracks
  `ended` when the clip starts) and leaves the no-clip case exactly as before
  (the intro client stays open, no regression). With Ask Sami open at the
  hand-off the stream stays until the panel closes and the first clip starts.
- **`pagehide`.** Best-effort: a closing tab drops the peer connection
  regardless, but an explicit stop lets Anam end the session without waiting
  for the connection to time out.
- **Failure path (CodeRabbit).** `releaseIntroClient` is assigned before
  `await client.streamToVideoElement()`, so if that call rejects the client
  exists and `start()`'s `catch` used to start the static fallback without
  stopping it. The `catch` now calls `releaseIntroClient()` first; it is a
  no-op when the failure came earlier (`openGarySession()` rejects before any
  client exists). An earlier version of this plan wrongly said no client
  exists in this `catch`.
- **Untouched:** the Q&A client, `waitForSpeechOrSkip()`, resume-from-progress
  (no intro client is created there), the hand-off itself.

## Alternatives considered
- **Stop the intro client when it finishes speaking** (`MESSAGE_HISTORY_UPDATED`)
  instead of at the hand-off: earlier release, but the hand-off point is where
  the sequence already waits for the same event or its 25 s cap, so the gain is
  nil and the extra timing path is one more thing to get wrong.
- **Reuse the intro client for Ask Sami answers** (one seat for the whole
  visit): attractive, but the intro session is a Gary-owned conversation with
  its own persona/session token; reusing it would change what Ask Sami says
  and is a design question for Gary/Anam, not a cleanup.
- **Leave it and raise the Anam limit:** costs money and hides the leak.

## Blast radius
Only the intro path of `js/aicoach-coach-flow.js`. Risks: (1) stopping emits
`CONNECTION_CLOSED` — mitigated by detaching the handler first; (2) a stopped
stream left on the `<video>` while Ask Sami is open during the hand-off —
hidden by the idle portrait, replaced by the first clip on close; (3) `pagehide`
fires on bfcache navigations too, but the flow cannot resume a live intro
anyway. No backend, REST or progress change.

## Verification (wp-env, visible Browser pane, real Gary/Anam session)
- Intro stream tracks `live,live` during the intro and `ended,ended` when
  `believe-1` starts (before the fix: `live,live` still 12 s into the clip).
  The clip starts normally and plays on (t advancing), panel `believe-1`.
- No `[aicoach] Sami CONNECTION_CLOSED before sequence finished` warning and
  no second `runFallback()` in any run.
- Ask Sami after the hand-off: question asked on `believe-1`, answer stream
  attached about 9 s later (visible, idle off), ended about 20 s later, idle
  back on with the panel open.
- Ask Sami opened during the intro (ENGR-7051 hold): with the release in
  `playPrerenderedClip()` the intro tracks stay `live` while the panel is open
  (30 s on `intro`, no clip, idle on), and are `ended` once the panel is closed
  and `we_believe_1.mp4` starts (checked 1-3 s after close).
- **No rendered clips (review, Dejan Arsic):** with `manifest.json` moved away
  in the wp-env container (`prerenderedVideos` empty, 0 segments), the first
  version of this change (release at the hand-off) left the intro tracks
  `ended` while `data-status` stayed `live`: the circle was black (average
  frame brightness 0, screenshot confirmed) through believe-1, believe-2 and the
  rest of the static flow. With the release moved into `load()` the same
  scenario keeps the intro stream `live` and rendering (`videoWidth` 1152, the
  avatar visible next to the static captions) through `intro`, `believe-1`,
  `believe-2` and `home`; the intro client is simply not released there, as
  before. The manifest was restored and verified identical to a backup.
- Returning visitor resuming at `identity`: no intro client, `status: idle`,
  a manual `pagehide` event does nothing and throws nothing.
- `node --check` on a module copy passes; `php tests/aicoach-progress.test.php`
  passes. No ESLint run (dependencies are not installed in this checkout).

**Not verified, called out rather than assumed:**
- The concurrency limit itself (would need an exhausted account) — and so
  whether Anam stops counting the session the moment the stream ends.
- The `catch` fix (CodeRabbit): a `streamToVideoElement()` rejection could not
  be forced live (the stream attached before a renamed video element could
  make the call fail), so it was verified by tracing the code and a syntax
  check only. `releaseIntroClient()` is idempotent and `stopStreaming()` is a
  no-op without a streaming client, so the call is safe on every path into
  the `catch`.
- `pagehide` on a real tab close: only a dispatched event was tried.

## Notes
- Open question on the ticket: whether Anam counts an idle, connected session
  against the limit, and whether the limit is per environment. Worth asking
  Anam; the change is correct hygiene either way.
- Related: `2026-10-05-ENGR-7051-ask-sami-idle-animation.plan.md` (where this
  surfaced).
