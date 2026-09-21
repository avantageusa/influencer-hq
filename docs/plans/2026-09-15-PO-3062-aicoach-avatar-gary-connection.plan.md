---
name: PO-3062 — Connect AI Coach avatar to a real Gary session
overview: >
  Replaces the interim direct-Anam-SDK session minting (inc/anam-proxy.php's
  session-token route) with a real Gary Coach API session (inc/gary-proxy.php,
  PR #22) as the avatar's connection source. The video/avatar is now genuinely
  driven by Gary — real network calls, a real Anam session_token brokered by
  Gary's response, real live-generated content for the opening line. Explicitly
  known and flagged: Gary has no way to recite this file's fixed SCREENS script
  verbatim, so every screen after the opening line still displays that existing
  copy as a static caption rather than spoken/lip-synced content, and Sami's
  current Gary-side config produces registration-irrelevant (betting-context)
  responses until that's separately reconfigured. Proceeding anyway per Filip
  Milinkovic's explicit direction (2026-09-15): "this isn't a technical
  blocker, continue connecting the API."
todos:
  - id: gary-session-open
    content: "start() now opens a Gary session via inc/gary-proxy.php's /coach/session (with the visitor's currentLocale) instead of minting an Anam token directly; found and fixed a bug in the same pass — gary-proxy.php's session payload was missing \"video\" in its want array, so Gary's response never included say.video.session_token at all"
    status: completed
  - id: connect-anam-via-gary-token
    content: The same Anam SDK (createClient/streamToVideoElement) now connects using the session_token Gary's response provides, instead of one minted by our own anam-proxy.php — verified live that this produces a genuine playing video stream (videoSrcObject, currentTime advancing), not just a successful HTTP response
    status: completed
  - id: show-garys-real-opening-line
    content: The intro screen's caption is now Gary/Sami's own live-generated say.text (confirmed distinct from — and topically different than — this file's hardcoded intro script), not our fixed copy
    status: completed
  - id: static-caption-fallback-for-rest-of-flow
    content: Every screen after the intro still displays this file's existing SCREENS/EQUITY_SCREENS/COMPETITION_SCREENS copy, but as a static caption (same pacing/tap-to-advance as the no-connection fallback) rather than spoken — removed runSequence()/speakScreen() (the client.talk()-based live-speech path) as dead code, since calling client.talk() directly on a Gary-brokered Anam session isn't part of Gary's documented contract and risks desyncing from whatever Gary's own backend thinks that session is doing
    status: completed
  - id: session-close-on-completion
    content: Fires a best-effort (non-blocking) POST to /coach/{id}/close right before the portal redirect on successful account creation, matching Gary's documented session lifecycle
    status: completed
  - id: locale-passthrough
    content: "create-account's language field now sends the visitor's actual currentLocale (from PO-3104's selector) instead of a hardcoded 'en' with a stale \"FR-12/13 aren't built yet\" comment — those are built now"
    status: completed
  - id: fix-double-advance-bug
    content: "Found via live testing: Anam's VIDEO_PLAY_STARTED fired more than once for a single session (WebRTC renegotiation, not a real second connection), causing two concurrent runFallback() loops to race the same shared sequenceIndex/skipCurrent state and advance two screens per tap instead of one. Fixed with a fallbackRunning guard."
    status: completed
  - id: verify
    content: node --check, php -l, phpcs (0 new errors on gary-proxy.php), extensive live wp-env testing across multiple fresh browser tabs (repeatedly navigating within one tab produced misleading stale-WebRTC-state results — always use a fresh tab for this kind of test) — confirmed real video streaming, Gary's real opening line, correct panel progression, working tap-to-advance after the fix, avatar staying visibly live through the whole static-caption flow
    status: completed
---

# PO-3062 Connect AI Coach avatar to a real Gary session

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude Code (Sonnet 5)

## Problem

The avatar has been driven by an interim direct-Anam-SDK integration (PO-3092/3093) since the
epic's first stories — approved as temporary by Filip back on 2026-08-28, pending Gary's real
Coach API. That API (inc/gary-proxy.php, PR #22) has existed since 2026-09-14 but wasn't wired
into the actual avatar/video connection yet — PO-3104 (language selector) explicitly deferred
that piece. This is that piece.

## Approach

1. **Session source swap.** `start()` now calls a new `openGarySession(locale)` helper that opens
   a session via `POST /wp-json/ihq/v1/coach/session` (inc/gary-proxy.php) instead of minting an
   Anam token via `inc/anam-proxy.php`. Found and fixed a real bug while wiring this up: the
   session-open payload's `want` array only listed `['text', 'audio']`, so Gary's response never
   included `say.video` (the Anam session_token the FE needs) at all — per Gary's docs, `"video"`
   is optional and must be requested explicitly. Added it.
2. **Same Anam SDK, different token source.** `createClient()`/`streamToVideoElement()` are
   unchanged — they now just receive `gary.say.video.session_token` instead of a token from our
   own proxy. Live-verified this produces a real, playing video stream (not just a 200 response) —
   confirmed via `video.srcObject` and `video.currentTime` actually advancing.
3. **Gary's real opening line replaces the hardcoded intro script — for that one line only.**
   `say.text` from the session-open response is shown as the intro caption. Confirmed live it's
   genuinely different content than this file's hardcoded intro ("Hey, good to see you at the
   table. What can I help with?" vs. "Hello. I'm Sami. Your Executive Coach...") — and confirms the
   already-known finding (from testing `gary-proxy.php` itself) that Sami's current Gary-side
   config is tuned for betting, not registration ("at the table" is a gambling reference). Flagged,
   not fixed here — separate content-config ask already sent to Filip/Marcus.
4. **Removed `runSequence()`/`speakScreen()` as dead code.** These drove the *old* mechanism:
   calling `client.talk(ourScriptText)` on the Anam client and building captions from
   `MESSAGE_STREAM_EVENT_RECEIVED`. Calling `client.talk()` directly on a Gary-brokered Anam
   session isn't part of Gary's documented API contract — Gary's backend likely orchestrates that
   same session in lockstep with its own LLM-generated speech, so forcing arbitrary text onto it
   client-side risks undefined behavior or desyncing from whatever Gary's side thinks is happening.
   Given that path is gone, every screen after the intro now uses the same static-caption display
   the no-connection fallback already used (`runFallback()`, extended with a `keepAvatarLive` flag
   so the real video stays visible instead of reverting to the idle portrait).
5. **Simplified the three "resume a stalled sequence" call sites** (late tier click, identity
   submit, comm-channels submit) — they used to branch on `usingFallback`/`activeClient` to decide
   between `runSequence()` and `runFallback()`; now there's only one runner, so they just call
   `runFallback(avatarIsLive)`. Removed `usingFallback` entirely (became write-only/dead once the
   branch disappeared) and the now-unused `sleep()`/`SCREEN_ADVANCE_DELAY_MS`.
6. **Session close + real locale on account creation.** Fires a non-blocking `POST
   /coach/{id}/close` right before the portal redirect (matches Gary's documented lifecycle -
   doesn't delay the redirect the visitor is waiting on). Also noticed and fixed `create-account`'s
   `language` field, hardcoded to `'en'` with a comment saying FR-12/13 weren't built — they are
   now (PO-3104) — so it sends the visitor's actual `currentLocale`.

## A real bug found via live testing (not caught by static review)

Testing the "tap to advance" behavior turned up a genuine race condition: Anam's
`VIDEO_PLAY_STARTED` event fired more than once for a single session (a WebRTC renegotiation, not
a second real connection) — since the handler called `runFallback(true)` unconditionally, this
started **two concurrent loops** racing the same shared `sequenceIndex`/`skipCurrent` module state.
Effect: a single tap advanced two screens instead of one (believe-1 → home, skipping believe-2).
Fixed with a `fallbackRunning` guard that makes a second concurrent call a no-op. Verified live,
in a **freshly opened browser tab** — repeatedly `navigate()`-ing within the same tab across test
runs produced misleading, inconsistent results (stale WebRTC connection state from the previous
page load bleeding into the new one), which cost real debugging time before realizing the tab
itself was the confound, not the code. Worth remembering for any future live-testing of this file.

## Alternatives considered

- **Sending a Gary `event`/`message` call per screen to try to get Gary to narrate each one**:
  rejected for this pass — Gary's box doesn't know this epic's content taxonomy (confirmed:
  defaults to betting topics for unrelated questions), so what it would actually say for an
  unrecognized "screen_view: believe-1"-style event is unknown and untested, and Gary's
  deterministic `want_reply` rule may not even trigger a response for an event type it doesn't
  recognize. Not worth guessing at against a real, budget-metered production API without a content
  contract from Gary's side first.
- **Keeping `runSequence()`/`client.talk()` as a code path for a hypothetical future where Gary
  allows verbatim recitation**: rejected — dead code with no confirmed way to ever become live
  again given Gary's documented API shape (no such capability exists in their spec), and this
  epic's own coding standards call for removing code that isn't reachable rather than keeping it
  "just in case."

## Blast radius

- `js/aicoach-coach-flow.js`: session/video connection source swapped; `runSequence`/`speakScreen`
  removed; `runFallback()` gained a parameter and absorbed both the true-fallback and
  now-Gary-connected-static-caption cases; the 3 "resume" call sites simplified; `usingFallback`/
  `sleep`/`SCREEN_ADVANCE_DELAY_MS` removed as dead; `create-account`'s `language` field now real.
- `inc/gary-proxy.php`: one-line fix (`want` array now includes `"video"`).
- `inc/anam-proxy.php`: **untouched** — its session-token route stays, still used by
  `page-portal-poc.php`.

## Notes

- **Every screen after the intro is still a known content gap, not a bug**: the ticket text for
  FR-13 (Scenario 25) and FR-14 (PO-3105, Scenario 26) both expect the avatar to actually speak
  each screen's content in the visitor's language. That's not achievable against Gary's current API
  shape (no verbatim recitation) or Sami's current Gary-side config (no IHQ registration content
  to draw from) — both flagged separately to Filip/Marcus. This PR makes the avatar *connection*
  real; it does not and cannot make Sami *say the right things* yet.
- FR-14's "video restarts in the new language" (PO-3105) and full avatar/voice language-switching
  (rest of FR-13's Scenario 25) still aren't wired — `selectLocale()` only swaps static
  `data-i18n` text, same as before this PR. Reconnecting to a fresh Gary session on language change
  (closing the old one, tearing down the Anam client, opening a new one) is the natural next
  increment on top of this, not folded in here.
- Same "TBD, don't invent" handling as everything else in this epic — nothing here pretends Gary's
  generic response is approved registration copy.
