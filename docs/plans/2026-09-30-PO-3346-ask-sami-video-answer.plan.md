---
name: PO-3346 — Ask Sami answers with a lip-synced video reply
overview: >
  Gary confirmed (2026-09-30, in response to a direct question) that
  video/lip-sync has actually been available on the registration surface
  since 2026-09-21 — GET /coach/v1/health's video_excluded_surfaces:
  ["registration"] was a hardcoded bug in the health check itself, not a
  real restriction; it now correctly reads []. Live-verified directly
  against a real /message call before writing any code: want:
  ["text","audio","video"] returns a usable say.video envelope on this
  surface today. This wires that up — Ask Sami's spoken answer now plays
  through the same avatar/video visitors already see, with graceful
  fallback to audio, then text, exactly as Gary's contract describes.
todos:
  - id: want-passthrough
    content: "inc/gary-proxy.php's ihq_coach_handle_message() accepts an
      optional want array in the request body (sanitized against the 3
      known values, duplicates collapsed, falls back to the original
      hardcoded ['text','audio','video'] if empty/absent) — lets the
      frontend ask for text only on a follow-up question once an avatar is
      already connected, instead of tying up another avatar seat per
      Gary's own guidance. Fully backward compatible: any caller that
      doesn't send want gets identical behavior to before this change."
    status: completed
  - id: audio-url-rewrite
    content: "Same function rewrites say.audio.url from a Gary-relative path
      (e.g. /coach/v1/audio/{id}) to an absolute URL on Gary's own host
      before returning it — it's meant to be fetched directly by the
      browser (confirmed unauthenticated with Gary), but relative to OUR
      origin it would 404. Verified live against a real /message call: the
      URL now comes back as https://influencerhq.agent.gary.club/coach/v1/audio/{id}."
    status: completed
  - id: speak-with-video
    content: "New speakAnswerOrFallback() (js/aicoach-coach-flow.js,
      buildAskSami()) — when say.video is present, connects (or reuses) an
      Anam client the same way start() already does for the live intro
      (createClient(session_token, {disableInputAudio:true}),
      streamToVideoElement(AVATAR_VIDEO_ID), wait for VIDEO_PLAY_STARTED,
      then client.talk(say.text)), streamed into the SAME main video
      element visitors already see — matches the AC's 'same avatar and
      voice... no perceptible change of speaker'. The client is kept alive
      across questions (cleared via its own CONNECTION_CLOSED listener
      whenever Anam ends it) rather than reconnected per question."
    status: completed
  - id: fallback-chain
    content: "Falls back to playing say.audio.url directly (new Audio(url))
      when say.video is absent (Gary's own degrade path — busy avatar
      seats, etc., meta.reason explains why) or when the video connection
      itself fails client-side after Gary did offer one; falls back to the
      text already shown if neither audio nor video is usable. Never plays
      say.audio.url alongside a working video stream (Gary's guidance —
      it's a separate, non-synced recording)."
    status: completed
  - id: shared-video-coordination
    content: "qaAnswering + qaVideoSnapshot let closePanel() and
      speakAnswerOrFallback() coordinate ownership of the shared video
      element instead of racing each other if the visitor closes the panel
      while Sami is still speaking: closePanel() skips its own
      wasPlaying-resume when qaAnswering is true, and
      speakAnswerOrFallback()'s own finally block performs that same
      deferred resume itself once it finishes, if the panel has since
      closed. Verified with an isolated Node.js simulation (mocked
      timers, no real DOM/Anam) rather than only reasoning about it, since
      this is the trickiest part of the change."
    status: completed
  - id: verify
    content: "php -l and node --check clean. New/updated PHP tests in
      tests/gary-proxy.test.php: want passthrough (explicit want forwarded,
      unknown values dropped, empty-after-filtering falls back to the
      default set) and the audio URL rewrite, all passing alongside the
      full existing suite. Isolated Node.js simulation of the
      closePanel()/speakAnswerOrFallback() race (want-selection toggling
      after the first successful connection; panel-closes-mid-speech
      resolves without a double resume or a dropped one) — both scenarios
      passed. Live-verified directly against the real Gary API (not a
      mock): want:['text'] correctly suppresses audio/video in the
      response; want:['text','audio','video'] returns a real, usable
      say.video envelope (provider/session_token/avatar_id/avatar_model/
      max_seconds/aspect) and an absolute, correctly-rewritten
      say.audio.url. Normal Ask Sami open/close still works with no new
      console errors. Not tested: an actual Anam WebRTC connection
      completing end to end — this session's browser tooling has no real
      microphone and the same class of network limitation noted on
      earlier PO-3346 PRs (the live Anam/intro connection was
      intermittently unresponsive here too) applies to a second Anam
      connection the same way; worth a real-device pass."
    status: completed
---

# PO-3346 — Ask Sami answers with a lip-synced video reply

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Ticket:** https://avantageusa.atlassian.net/browse/PO-3346
**Drafted by:** Claude Code (Sonnet 5)

## Problem

PO-3330's AC requires answers "delivered as generated lip-synced speech and
video in the same avatar and voice as the recorded content... with no
perceptible change of speaker." This was blocked — or believed to be —
since `GET /coach/v1/health` reported `video_excluded_surfaces:
["registration"]`. Emailed Gary directly to ask about enabling it rather
than assuming; the answer (2026-09-30) was that this was a bug in the
health check itself (a hardcoded line), not a real restriction — video has
actually worked on this surface since 2026-09-21. Confirmed by calling
`/message` directly before writing any implementation code: a real,
complete `say.video` envelope comes back today.

## Approach

Reuse the exact connection pattern `start()` already uses for the live
intro avatar (same SDK, same `disableInputAudio` option, same
`streamToVideoElement`/`VIDEO_PLAY_STARTED`/`talk()` sequence), but as an
independent connection scoped to Ask Sami's Q&A rather than the intro's own
`activeClient` — the intro's connection is capped at `max_seconds: 300` and
becomes irrelevant once the sequence moves on to pre-rendered clips, so it
can't be assumed still alive by the time a visitor asks a question later in
the flow. Kept alive and reused across multiple questions in the same
visit (per Gary's explicit guidance not to open a new video session per
reply), torn down and reconnected automatically whenever Anam itself closes
it.

## Alternatives considered

- **A separate, smaller video element inside the Ask Sami panel**, instead
  of reusing the main avatar video. Rejected per product decision (asked
  rather than guessed) — the AC's "no perceptible change of speaker"
  reads most naturally as the same on-screen avatar answering, not a
  second small video appearing alongside it.
- **Open a fresh Anam connection for every question.** This is what Gary's
  own guidance explicitly asked not to do (each session ties up an avatar
  seat) — reusing one connection across a visit's questions is both what
  was asked for and less wasteful.
- **Always play say.audio regardless of video.** Rejected — Gary was
  explicit that the audio recording and the video's own lip-synced audio
  are not the same take and won't line up; playing both would mean
  doubled, out-of-sync audio.
- **Have closePanel() always restore the video, and have
  speakAnswerOrFallback() skip its own restore if the panel already
  closed.** Equivalent in effect to what's implemented, just with the two
  functions' roles swapped — picked the direction where the function that
  actually knows the snapshot (speakAnswerOrFallback) owns restoring it,
  rather than threading that snapshot out to closePanel() too.

## Blast radius

- `inc/gary-proxy.php`: `ihq_coach_handle_message()` only — one new
  optional request field (backward compatible), one response rewrite
  (`say.audio.url`). No other route touched.
- `js/aicoach-coach-flow.js`: `buildAskSami()` gains `speakAnswerOrFallback()`
  and 4 new state variables; `askQuestion()` sends `want` and awaits the new
  function; `closePanel()` gains one guard condition. A new
  `waitForQaSpeechOrSkip()` at module scope, a deliberate near-duplicate of
  the existing `waitForSpeechOrSkip()` rather than a shared abstraction —
  see that function's own comment for why (it's intentionally not wired
  into the shared `skipCurrent` the main sequence loop depends on). Nothing
  about the intro's own `start()`/`activeClient`, the main sequence loop, or
  pre-rendered clip playback is touched.

## Notes

**Not live-tested with a real Anam connection completing end to end.**
Same environment limitation noted on earlier PO-3346 plan docs — this
session's browser tooling has no real microphone, and the live Anam
connection has been intermittently unresponsive here on unrelated attempts
earlier in this epic's work. What WAS verified live and directly against
the real Gary API (not a mock): the `want` passthrough actually changes
what comes back, `say.video`'s shape matches what's implemented against,
and the `say.audio.url` rewrite produces a real, correctly-formed absolute
URL. The riskiest logic — the closePanel()/speakAnswerOrFallback() race —
was verified with an isolated Node.js simulation instead of only reasoning
about it. Worth a real-device pass (real mic, real Anam connection) before
considering this fully closed.

**Voicing in other languages is still out of scope** — Ask Sami's entry
point stays hidden outside English (Gary's `registration_languages` is
still `["en"]` only), unrelated to this change.
