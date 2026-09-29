---
name: PO-3346 — Ask Sami asks by voice, not typing
overview: >
  A build with the original PO-3330 MVP's text input box was reviewed
  (relayed via Teams, 2026-09-29) and rejected: "ne valja ovo, ne treba
  input polje da bude, samo glasom" (this isn't right, there shouldn't be an
  input field, voice only). That also matches PO-3330's own AC, which never
  actually asked for typing — "ask by voice", with a decline/blocked-mic
  fallback that returns the visitor to the flow, not a text box. Replaces the
  text form with a mic button using the browser's own SpeechRecognition.
  Deliberately scoped to ONLY the input side, on the existing button-opened
  panel — tap-to-interrupt (replacing tap-to-skip) is a separate, later
  piece; see Notes.
todos:
  - id: swap-input
    content: "buildAskSami() (js/aicoach-coach-flow.js): removed the <form>/
      <input type=text>/submit button entirely. Added a mic button
      (.aicoach-ask-mic) and a status line (.aicoach-ask-status) instead.
      page-home-aicoach.php: removed the now-dead .aicoach-ask-form/-input/
      -submit CSS, added .aicoach-ask-voice/-mic/-status (including a
      pulsing is-listening state)."
    status: completed
  - id: speech-recognition
    content: "startListening() creates a fresh SpeechRecognition (or
      webkitSpeechRecognition) instance per attempt — lang 'en-US' (Ask Sami
      is English-only for now, same existing 'en' gate), continuous=false,
      interimResults=false. onresult's transcript goes straight into
      askQuestion(), the same /message call the old form's submit handler
      used (extracted, otherwise unchanged) — no backend change needed at
      all, since inc/gary-proxy.php's ihq_coach_handle_message() already
      only ever took a text field. openPanel() calls startListening()
      immediately (AC: permission requested 'at that moment and not
      before'); closePanel() calls the new stopListening(), which
      aborts any in-flight recognition and nulls its onresult so a
      transcript racing in after an explicit close can't still fire a
      question."
    status: completed
  - id: error-states
    content: "onerror branches per AC: 'not-allowed'/'service-not-allowed'
      (permission declined) and no SpeechRecognition constructor at all
      (browser/device blocks it) both show \"Questions need the microphone
      for now\" and leave the visitor to close the panel themselves — no
      text fallback is offered, matching the review feedback and the AC's
      own wording ('returned to the flow', not 'type instead'). 'no-speech'
      (said nothing) shows a plain retry prompt and records nothing.
      'no-match' (spoke but not recognised) asks to repeat rather than
      guessing or submitting garbage to Gary. The mic button re-arms
      startListening() on every click, covering all the retry paths with
      one handler."
    status: completed
  - id: verify
    content: "node --check and php -l both clean. Live-verified on wp-env:
      confirmed the old text input/submit button no longer exist in the DOM
      at all, mic button renders correctly. This sandboxed browser itself
      blocks microphone access, which exercises the real 'browser/device
      blocks it' AC path end-to-end: opening the panel correctly triggered
      the onerror('not-allowed') branch and showed the exact 'Questions
      need the microphone for now' message; closing and reopening the panel
      re-armed listening cleanly with no stuck state; clicking the mic
      button to retry produced the same correct message with no console
      errors. The actual 'speak and get a transcribed question' path isn't
      testable in this environment (no real microphone/audio input
      available here, same class of limitation as the live Anam/Gary
      connection noted in earlier PO-3346 plan docs) — worth a real-device
      pass before considering this fully closed."
    status: completed
---

# PO-3346 — Ask Sami asks by voice, not typing

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Ticket:** https://avantageusa.atlassian.net/browse/PO-3346
**Drafted by:** Claude Code (Sonnet 5)

## Problem

The PO-3330 MVP shipped Ask Sami with a typed text box, a deliberate
simplification at the time (see that plan doc) while the real `/message`
endpoint was still blocked server-side. Once a real build went out for
review, the text box was rejected outright — voice only, no input field. This
is also what PO-3330's own AC actually specifies (never mentions typing;
its fallback for a declined/blocked microphone is "return to the flow", not
"let them type instead").

## Approach

Smallest change that satisfies the actual complaint: keep the existing
button-opened panel and its `/message` wiring exactly as they are, and
replace only the input mechanism — text form → mic button +
`SpeechRecognition`. The transcribed text goes through the identical
`askQuestion()` call the old form's submit handler made; Gary's `/message`
endpoint was always text-only server-side, so none of `inc/gary-proxy.php`
needed to change.

## Alternatives considered

- **Also implement tap-to-interrupt (tapping the video itself opens this
  panel) in the same change**, since the user's own gap-analysis list
  grouped these two AC bullets together. Rejected for this piece — the
  actual complaint was specifically about the input field, not the trigger
  gesture, and tap-to-interrupt has a real, separate trade-off (it would
  replace today's tap-to-skip convenience on caption-only screens with no
  real speech to interrupt). Decoupling lets this ship the thing that was
  actually flagged without also deciding that unrelated question. Tracked
  as the next piece.
- **Route the visitor's spoken audio to Gary/a server-side STT service**
  instead of transcribing in the browser. Rejected — confirmed in
  `inc/gary-proxy.php` that `/message` only ever accepted a `text` field;
  there's no audio-in route on Gary's side to build against, and the
  browser's own `SpeechRecognition` already produces text locally with no
  new infrastructure at all. Building a server-side STT pipeline would be
  strictly more work for a result the AC doesn't ask for (it asks for a
  voice **experience**, not audio specifically reaching Gary).
- **Keep the text input as a hidden fallback for browsers without
  `SpeechRecognition` support (e.g. Firefox).** Considered, but the AC is
  explicit that a blocked/unsupported microphone should tell the visitor
  plainly and return them to the flow — not offer a different input method.
  Matches the review feedback too. A future ticket could revisit this if it
  turns out to meaningfully cut off Firefox visitors, but that's a product
  call, not a default to make silently here.

## Blast radius

- `js/aicoach-coach-flow.js`: `buildAskSami()` only — the `<form>`/`<input>`/
  submit button are gone, replaced by a mic button and status line; the
  `/message` fetch call itself (now `askQuestion()`) is unchanged. Every
  other function in this file (sequence loop, timers, locale selection) is
  untouched.
- `page-home-aicoach.php`: CSS only, same `.aicoach-ask-*` block — the
  dead form/input/submit rules were removed, not left dangling.

## Notes

**Tap-to-interrupt is a deliberately separate follow-up**, not done here —
see Alternatives above. It needs its own decision about what happens to the
existing tap-to-skip convenience on screens with no real speech to interrupt
(most of the sequence today is a static caption, not live/recorded audio —
see the "known, flagged gap" comment at the top of this file).

**Voicing the ANSWER (lip-synced avatar speech) is still not done** — same
scope boundary as PO-3330's own MVP. Only the question side changed here.

**Not tested with a real microphone/spoken question** — this session's
browser tooling has no real audio input device, so the actual "speak a
question, get it transcribed and answered" path is unverified beyond code
review and the (very real) permission-blocked path this environment does
exercise. Flagging for a real-device pass rather than claiming full
end-to-end coverage.
