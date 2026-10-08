---
name: Times up screen and appointment scheduling for the AI Coach (FR-18)
overview: >
  When the visitor's selected time runs out, the AI Coach asks "Time is up?"
  with two choices, Keep Talking Now and Set an Appointment. Keep Talking Now
  carries on; Set an Appointment opens "Set your appointment" (In 30 minutes,
  In an hour, Other with date, time zone and time), and "Copy Your Appointment
  Link" copies a link, confirms it and moves the visitor to the final screen
  after 4 seconds. This plan splits the work into reviewable steps, starting
  with the Times up screen, and records the questions that block later steps.
  Out of scope until answered: the appointment link pages (waiting, join,
  missed, expired, ended, invalid) and the reminder.
todos:
  - id: answer-questions
    content: Get answers to the open questions below (Ivan / Steve / Marcus) before the steps that depend on them
    status: pending
  - id: times-up-panel
    content: Replace the FR-17 Yes/No overlay with the Time is up? screen from the new design (Keep Talking Now / Set an Appointment)
    status: pending
  - id: appointment-logic
    content: Pure module js/aicoach/appointment.js (choice to start time, required fields, past-time check, time zone handling) with tests
    status: pending
  - id: scheduling-panel
    content: Set your appointment panel, validation messages, copy to clipboard with confirmation and the 4 second move to the final screen
    status: pending
  - id: appointment-endpoint
    content: WordPress REST endpoints that create a signed appointment link and store it with the visitor's progress
    status: pending
  - id: link-pages
    content: Appointment link pages and their states (separate PR, depends on the answers)
    status: pending
  - id: reminder
    content: Reminder 5 minutes before through the visitor's channels via Braze (depends on Steve)
    status: pending
  - id: verify
    content: Run tests, lint, and CI gate before merge
    status: pending
---

# [PO-3109] Times up screen and appointment scheduling (FR-18)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3109
**Drafted by:** Claude (claude-sonnet-5-5)

## Problem
FR-17 (PO-3108) shows a Yes/No modal when the selected time is almost used up,
and "No" currently only dismisses it (`timeCheckNoBtn` in
`js/aicoach-coach-flow.js`: "FR-18 isn't built yet"). FR-18 adds the
appointment flow behind it, and the design (Figma, nodes 46293:66471 and
46293:66086) replaces the Yes/No modal with a "Time is up?" screen.

## What the design shows
- **Time is up?** (nodes 46293:66471, 46293:66086): the same stage as every
  other screen (header, coach, dotted commentary box) with the heading
  "Time is up?" and two checkbox-style options, **Keep Talking Now** and **Set an
  Appointment**. The two nodes are the two selected states (first one, then the
  other, green box). There is no confirm button, so choosing acts at once, like
  the time selection.
- **Set your appointment** (earlier design, ticket link): In 30 minutes / In an
  hour / Other, a green "Copy Your Appointment Link" button and the line "Open
  this link at your appointment time to join your video with Sami"; Other shows
  Date, Timezone and Appointment Time.
- Not found in the design yet: the message after copying, the correction
  messages (missing field, past time, nothing chosen), the final screen after the
  4 seconds, and the link pages.

## Approach
Steps, each its own PR where it makes sense:

1. **Times up screen (done in step 1).** A new panel `time-up` in
   `page-home-aicoach.php` replaces the modal `#aicoach-time-check`, styled from the
   design (white heading, white-outlined boxes, green `#148942` when chosen; two
   radio inputs, so one choice, acting at once). `maybeShowTimeRemainingCheck()`
   calls `openTimeUp()`: it records the screen the visitor was on, pauses the
   clip, pauses the timers and sets an *external hold* on the screen loop
   (`setExternalHold()` in `js/aicoach/sequence-hold.js`, so a stray
   `resumeSequenceTimers()` cannot let a new screen start underneath), shows the
   idle portrait, and disables the Ask Sami button. Keep Talking Now (and, until
   step 3, Set an Appointment) calls `closeTimeUp()`: it shows the recorded screen
   again, resumes the timers and the clip and gives the Ask Sami button its previous
   state. The panel is transient (`TRANSIENT_PANEL_KEYS`): it is never saved as the
   visitor's stage, so a reload does not resume into it. If Ask Sami is open or an
   answer is still playing, showing it waits. New copy ("Time is up?", "Keep
   Talking Now", "Set an Appointment") is English only: other languages fall back
   to English until approved translations exist; the old FR-17 strings
   (`timeCheckText`, `yes`, `no`) are removed. Until step 3, Set an Appointment
   must not reach production as a dead end, so it behaves like Keep Talking Now.
2. **Appointment logic, pure and tested.** `js/aicoach/appointment.js` with the
   timers/clock injected where needed: turns the chosen option into a start time
   (in 30 minutes, in an hour, or date + time + IANA time zone into one UTC
   instant), lists missing fields, and rejects a time in the past. Time zones come
   from the browser (`Intl.supportedValuesOf( 'timeZone' )`) with the visitor's
   own zone preselected. Table-driven tests including DST edges.
3. **Scheduling panel.** Three radio options (one choice, drawn round), the Other
   fields, the three correction messages, copy to clipboard with a visible
   confirmation, the 4 second move. Before it opens, if no contact method has been
   given yet, Set an Appointment first collects one (Ivan, 2026-10-08).
4. **Endpoints in the theme** (`inc/`, same style as `aicoach-progress.php` and
   `gary-proxy.php`): create an appointment (the time is validated again on the
   server, never trusted from the browser), return a link that is signed with an
   HMAC so an edited link is detectable, and store the appointment next to the
   visitor's progress record. The link state is computed from the server clock.
5. **Link pages and reminder**: after the questions below are answered.

## Alternatives considered
- Keeping the modal and only renaming the buttons: the design is a screen in
  the stage, not a modal, and a modal would sit over the coach.
- Generating the link in the browser: an edited link could not be detected and
  the past-time rule would be bypassable; the link needs a server secret.
- Using WP-Cron for the reminder: it runs on page traffic, so "5 minutes before"
  would not be reliable; the reminder should be scheduled on the messaging side.

## Blast radius
Step 1 changes FR-17's behaviour, which PO-3108 (Ready for QA) tests as a
Yes/No modal ("Do you have a few more minutes?"): its scenarios 29 and 30 need
updating with it. The panel list, `showPanel()`, the hold logic and the
language data all feed it. Later steps add REST routes and a stored record per
appointment; nothing is deployed until those are agreed.

## Open questions (they block steps 3 to 5)
1. ~~Reminder channels.~~ Answered (the dev, then Ivan Vladic on Teams,
   2026-10-08): the channels come from the channel step of the flow (design nodes
   44987:30249 and 45086:22830: "Let's Start The Conversation", the channel grid,
   "Connect with ..." and "Forgot One?"). Time is up can appear before that step
   (Ivan: possible "in theory"), so **when no contact method has been given yet,
   Set an Appointment must first collect one, and only then go to scheduling.**
   Still to settle for that path: whether it needs the identity (name) step as
   well or only a channel, and which screen it uses (the channel screens in those
   two nodes look different from the current `comm-channels` panel, a checkbox
   list with an input per channel; that restyle is a separate change).
2. **"The final screen" after copying**: which one in this flow, `final-continue`
   (Let's Continue, account creation) or a new one? Account creation needs the
   identity and channels from question 1.
3. Does Sami speak on the Times up screen (the commentary box is shown)? If so,
   what is the script?
4. Does Keep Talking Now resume exactly where she was, as FR-17's scenario 30
   says ("no loss")?
5. The behaviours the ticket marks as undefined: does "in 30 minutes" count from
   selecting or from copying; what happens when the clipboard is blocked or the
   appointment is under 5 minutes away; the join/missed boundary at exactly 15
   minutes; what the reminder says and whether it carries the link.
6. ~~Single or multiple choice?~~ Answered: single choice, shown as round radio
   buttons instead of checkboxes (the three scheduling options).
7. Who sets up the reminder on the messaging side, and which channels can send
   an unprompted reminder (Steve).

## Verification, step 1 (wp-env, visible Browser pane)
- `npm run test:js` (78 tests, 5 new for the external hold) and `npm run test:php`
  (all 11 suites) pass; `php -l` and `node --check` are clean.
- The time check fires at the real threshold: 2-minute tier, 96 s after the tier
  click, the "Time is up?" screen took the stage over the identity form.
- With the threshold temporarily lowered (not committed) to see the rest quickly:
  over the equity clip the video paused at 6.7 s and stayed there for 38 s with
  the screen still `time-up` (a clip that would have ended by then did not
  advance the sequence); the idle portrait showed, the Ask Sami button was
  disabled, and the saved stage stayed `equity-bts`, never `time-up`.
- Choosing straight after the screen appears (review, CodeRabbit): the first version
  resumed the clip while the previous screen was still fading back in, so for
  about 0.3 s the clip played (with sound) under the Time is up screen
  (reproduced, sampled every 100 ms: `paused: false`, time advancing, panel still
  `time-up`). `closeTimeUp()` now waits for `showPanel()` before it removes the
  idle portrait, re-enables Ask Sami, resumes the timers, clears the hold and
  plays the clip; with the same quick double choice the clip stayed paused at
  6.67 s with idle on until the previous screen was back (about 800 ms) and then
  resumed, and the second choice did nothing.
- Language pick while the screen shows (review, Dejan Arsic): the restart used to
  play the clip underneath, and at the last screen its end let `finishSequence()`
  replace the screen with identity while the hold was still on, so the form's
  Continue waited forever. Now `restartCurrentClipForLocale()` only remembers the
  pick while Time is up shows, `closeTimeUp()` restarts the clip in the new
  language once the previous screen is back, and `finishSequence()` waits for the
  external hold (`waitWhileExternallyHeld()`, new in `sequence-hold.js`, 3 tests).
  Live: with Mandarin picked on the last screen the clip stayed paused at 6.66 s for
  8 s and the screen stayed `time-up`; Keep Talking Now restarted `bts-zh.mp4` from
  0 with the Chinese caption, and identity appeared only after the clip ended.
- Question still in flight (review, Dejan Arsic): `qaQuestionsInFlight` counts
  questions sent but not yet answered (closing the panel does not cancel one);
  `isAnswerPending()` counts them with an answer being spoken, and
  `speakAnswerOrFallback()` also returns while Time is up shows. Live, with the reply
  held back 12 s: question sent at 1 s, panel closed at 2 s, time check due at about
  8 s; the screen did not open then, the reply was delivered at 19 s and spoken at
  19 s, and Time is up opened at 21 s.
- Keep Talking Now: the previous screen came back, the clip resumed from where it
  stopped (6.66 s to 7.47 s), idle went off, the Ask Sami button returned to its
  previous state (it was disabled before: a resumed visit has no live session),
  and the saved stage was the previous screen's.
- Set an Appointment (interim): same as Keep Talking Now, back to the previous
  screen, saved stage correct.
- Computed styles: heading white 38.4 px (capped; 34 px at the design's 398 px
  width), boxes 28 px with a 2 px white border, label white; the chosen box uses
  `#148942`.
- Not verified: the chosen-box green state in a screenshot (the choice acts at once,
  so it is only visible for an instant), the real flow with a live Gary session
  (all runs resumed at the time selection, with Ask Sami disabled), Ask Sami open
  or an answer playing at the moment the time is up (written to wait, not driven),
  a language change while the screen is showing, translations (English only),
  and a narrow-phone layout.

## Notes
- The three scheduling options are a radio group (one choice) drawn round, per
  the answer to question 6. The Time is up options follow the design (squares,
  like the time selection, which is also a radio group underneath).
- The appointment record is stored by the theme (WordPress); nothing here
  requires a separate service.
- The new strings are English-only on purpose (see Approach 1).
- Related: `2026-10-05-aicoach-coach-flow-testable-modules.plan.md` (the test
  runner and module layout this reuses).
