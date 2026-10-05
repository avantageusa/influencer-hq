---
name: Give Sami an animated idle state while the Ask Sami panel is open
overview: >
  Opening Ask Sami pauses the shared <video> element so the sequence can be
  held, which leaves Sami frozen on whatever frame she was on (often
  mid-word) for as long as the panel stays open — while listening, while a
  question is in flight, and after an answer finishes. This swaps the frozen
  frame for her neutral portrait with a subtle looping "breathing" animation
  for exactly that window, and brings the video back (crossfaded) when the
  panel closes or a lip-synced answer starts. CSS and one small derived-state
  helper only; no backend, no new dependency. Out of scope: changing what she
  looks like at any other moment (see Notes).
todos:
  - id: reproduce
    content: Reproduce the frozen frame locally (wp-env) before changing anything
    status: completed
  - id: idle-state
    content: Add a derived is-idle class on the avatar wrapper (panel open and no lip-synced answer stream showing) and keep it in sync from the five places that change either input
    status: completed
  - id: idle-css
    content: Crossfade from the paused video to the neutral portrait and animate the portrait with a breathing loop; honour prefers-reduced-motion
    status: completed
  - id: verify
    content: Exercise every state live (clip, live intro stream, caption-only screen, answer playback, audio-only fallback, close/reopen, language switch) and run lint
    status: completed
---

# [ENGR-7051] Animated idle state for Sami while Ask Sami is open

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7051
**Drafted by:** Claude (claude-sonnet-5-5)

## Problem
Reported as a suggestion (Filip Milinkovic on Teams, Sharaj Kornaya in Jira):
when Ask Sami is clicked while Sami is speaking, her video stops and she
looks frozen. Confirmed locally on wp-env: at the moment of the click the
`<video>` was paused at `t=12.57` of `we_believe_1.mp4`, still at
`opacity: 1` over the portrait, and `currentTime` did not advance 1.5 s later —
a still frame of her mid-word, held for the whole time the panel is open.

## Reproduction
1. Open the AI Coach page and let the intro stream / first clip start.
2. While she is speaking, click "Ask Sami".
3. Observe: `video.paused === true`, `data-status="live"` so the video (not
   the portrait) stays visible; the frame never changes until the panel is
   closed.

Same result for every pre-rendered clip and for the live intro stream. It
also recurs after a spoken answer ends with the panel still open: the
`finally` block in `speakAnswerOrFallback()` restores the interrupted clip's
`src` and re-pauses it.

## Approach
`openPanel()` pauses the video and the sequence timers on purpose (the screen
must not advance underneath a question), so the fix is to change what is
*shown* while that pause is in effect, not to un-pause anything.

- **State, derived not stored.** `avatarWrap.is-idle` is a pure function of
  two facts the code already tracks: the panel is open
  (`wrap.classList.contains('is-open')`) and no lip-synced answer stream is
  being shown (new boolean `qaVideoActive`, true only between "answer stream
  attached" and the `finally` of `speakAnswerOrFallback()`). A single
  `syncAvatarIdle()` recomputes it; it is called from `openPanel()`,
  `closePanel()`, and the points in `speakAnswerOrFallback()` where the
  answer stream starts, fails and ends. Audio-only fallback answers
  (`playFallbackAudio()`) deliberately keep her idle — there is no lip-synced
  video to show. This includes the failure case: if `talk()` throws or the
  connection drops mid-speech (`waitForQaSpeechOrSkip()` rejects on purpose),
  the `catch` clears `qaVideoActive` and re-syncs *before* awaiting the
  fallback audio, because the `finally` that normally clears it only runs
  after that audio ends (found by CodeRabbit on review).
- **Visuals (`page-home-aicoach.php`).** While `is-idle`, the video fades out
  and the existing neutral portrait (`coach-portrait.webp`, already
  extracted from her real rendered video so the face matches) shows instead,
  with a slow scale/translate "breathing" loop on the portrait only. The
  portrait is made opaque instantly (no transition) underneath while the
  video fades, so the crossfade does not dip through the dark background. The
  animation is transform-only (compositor-friendly) and is inside the
  avatar's existing circular `overflow: hidden` mask, so it never changes
  layout or the circle's size.
- **Accessibility.** `prefers-reduced-motion: reduce` keeps the swap to the
  neutral portrait but drops the animation.
- **Untouched on purpose:** the `connecting` state, the sequence/timer pause
  logic, `wasPlaying` resume, and every other place `data-status` is set.

## Alternatives considered
- **Keep a live Anam avatar connected from the moment the panel opens** (a
  live avatar idles naturally). Rejected: each connection holds one of a
  limited number of avatar seats, adds a multi-second WebRTC connect before
  anything moves, costs money for visitors who never ask anything, and fails
  outright when seats are busy.
- **A pre-rendered silent idle loop from Gary's Video API.** Best fidelity,
  but the API renders a script, not "no speech", and it would multiply the
  clip set. Worth asking Gary; not blockable on.
- **Drive idle from the video's `pause`/`ended` events instead of the panel.**
  Would also cover other still moments, but it changes behaviour at every
  clip boundary (a flash of the portrait between screens). Wider than the
  ticket; see Notes.

## Blast radius
Only while the Ask Sami panel is open. Touches the avatar wrapper's classes
(CSS + `js/aicoach-coach-flow.js` inside `buildAskSami()`); no backend, REST,
or progress-record change. Visitors who never open Ask Sami see no
difference. Risk is in the open/close/answer ordering (a stuck `is-idle`
would leave the video hidden after the panel closes), which is why it is
derived from state and recomputed at every transition rather than toggled
ad hoc.

## Verification (wp-env, real Gary session)
Exercised live, in a visible browser so video playback and CSS transitions
were real:
- A pre-rendered clip interrupted by Ask Sami: before, the clip was playing
  (video opacity 1, portrait 0); after the click the video is paused and
  hidden and the portrait is opaque with the breathing animation running
  (scale oscillating 1.001–1.029).
- The live intro stream interrupted by Ask Sami: same result.
- Closing via the × button, the button's second click and an outside click:
  idle clears, the video is back and playing.
- A full question and answer with the panel open: idle during the ~13 s of
  thinking and avatar connecting, off exactly when the answer stream starts
  (clean ~0.4 s crossfade), back on when it ends while the panel is still open.
- **Closing the panel while the lip-synced answer is still speaking:** idle
  stays off and the video stays visible until the answer finishes; once the
  stream ends the clip resumes and nothing is left stuck.
- The audio-only fallback (video stripped from the `/message` response): idle
  stays on for the whole answer and clears on close.

`php -l`, a module syntax check and the existing PHP suites (`gary-proxy`,
`aicoach-progress`, `aicoach-language-braze`) pass.

**Not exercised, called out rather than assumed:**
- The `catch` fix for a video answer that fails *after* the stream started
  (`talk()` throws, or the connection drops mid-speech) was verified by
  tracing the code, not driven live. A live attempt (capturing Anam's
  `RTCPeerConnection` and closing it mid-answer) could not be completed: the
  Anam account's concurrent-session limit kept refusing new streams.
- `prefers-reduced-motion: reduce` — the rule uses the same selector as the
  animation rule so it cannot lose on specificity, but the media query itself
  was not emulated.
- No ESLint run: dependencies are not installed in this checkout.

## Notes
- Two pre-existing issues surfaced while testing; neither is caused by this
  change and neither is fixed here. (1) The live intro's Anam client
  (`activeClient`) is never stopped, so it keeps holding a concurrent avatar
  seat for its lifetime; combined with the account's concurrency limit
  ("Concurrency limit reached"), the first Ask Sami video answer falls back to
  audio/text whenever seats are busy. (2) Opening Ask Sami during the live
  intro lets the intro finish server-side and the sequence start the next clip
  under the open panel, overwriting the answer stream on the shared `<video>`
  (the gap already documented next to `pausableSequenceTimers`).
- The same "frozen" look exists briefly elsewhere: after a clip ends the last
  frame stays on screen for the identity / channels / final screens. Not part
  of this ticket — flagged for a follow-up decision rather than widened here.
- Speech recognition is not driven for real during verification (the
  microphone is blocked in the embedded browser); the answer path is
  exercised by feeding a transcript to the page's own recognition handler.
