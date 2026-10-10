---
name: Times up screen and appointment scheduling for the AI Coach (FR-18)
overview: >
  When the visitor's selected time runs out, the AI Coach asks "Time is up?"
  with two choices, Keep Talking Now and Set an Appointment. Keep Talking Now
  carries on; Set an Appointment opens "Set your appointment" (In 30 minutes,
  In an hour, Other with date, time zone and time), and "Copy Your Appointment
  Link" copies a link, confirms it and moves the visitor to the final screen
  ("You are all set up") after 4 seconds. This plan splits the work into reviewable steps, starting
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
    status: completed
  - id: scheduling-panel
    content: Set your appointment panel, validation messages, copy to clipboard with confirmation, the 4 second move to the final screen and the final screen itself (step 3)
    status: completed
  - id: appointment-endpoint
    content: WordPress REST endpoint that validates the time on the server, creates a signed appointment link and stores the appointment (step 4); the screen asks it for the link
    status: in-progress
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
- **Final screen** (design node 45327:43893, screen 17): the same stage with the
  heading "You are all set up" and a green button "CONTINUE TO IHQ PORTAL". A
  note beside it gives Sami's closing message: "I'll always be here for you 24
  hours a day, 7 days a week. If you have not saved IHQ portal address already, make
  sure to do that now." The coach is Sami everywhere.
- Not found in the design (Ivan, 2026-10-09: there is none): the message after
  copying and the correction messages (missing field, past time, nothing chosen).
  Plain English text is used for them. The link pages are a later step.

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
   **Done in step 2** (`js/aicoach/appointment.js`, not yet imported by the page, so
   not yet registered in `inc/aicoach-modules.php`; step 3 wires and registers it):
   `validateAppointment( selection, now )` returns `{ ok, errors, startsAt,
   startsAtIso }`; `errors` are codes (`choice-missing`, `date-missing`,
   `time-zone-missing`, `time-missing`, `date-invalid`, `time-invalid`,
   `time-zone-invalid`, `time-does-not-exist`, `time-in-past`) that the screen maps
   to text. `zonedDateTimeToInstant()` turns a wall-clock date, time and IANA zone into
   one instant: the skipped hour when clocks go forward is refused
   (`time-does-not-exist`), and the repeated hour when they go back resolves to the
   first occurrence. Also `buildTimeSlots()` (every 30 minutes, 00:00 to 23:30; removed in step 3,
   see there), `listTimeZones()` (browser list, sorted, UTC always present), `getDefaultTimeZone()`
   and `isValidTimeZone()`. Assumptions the ticket leaves open, each a one-line
   change: "In 30 minutes" and "In an hour" count from the moment `now` is taken
   (the screen takes it when the visitor copies); a time equal to `now` is in the
   past; a missing field is reported before anything else is checked; no rule for
   an appointment under 5 minutes away, since the ticket does not define one.
3. **Scheduling panel and final screen (step 3).** Answers from Ivan Vladic
   (Teams, 2026-10-08 and 2026-10-09) decide the details:
   - The three options are **square checkbox-looking boxes with a single choice**
     (a radio group underneath, like the Time is up options), not round buttons
     (the first version of this plan said round; that was a misreading, corrected
     here). The button reads **Copy Your Appointment Link**.
   - Choosing **Other** **replaces** the three options with Date, Timezone and
     Appointment Time (design 16c). There is no way back to the three options: the
     flow only moves forward (the dev, 2026-10-09), so no back control is built.
   - "In 30 minutes" counts from when the link is created, i.e. when the visitor
     copies it. A time that is not strictly after that moment is in the past (15:15
     cannot be booked at 15:16): `validateAppointment()` already does this.
   - **Timezone** lists every zone the browser knows, the visitor's own preselected.
   - **Appointment Time** is a choice of hour and minute, not a list of 30-minute
     slots. It is a native `<input type="time">` (the phone's own hour and minute
     picker), so `buildTimeSlots()` from step 2 is no longer used and is removed
     with its tests (the requirement it encoded was replaced by Ivan's answer, not
     bent to make a test pass). Minutes are one minute apart; Ivan did not say
     otherwise.
   - Messages (plain English, no design): the correction for each error code of
     `validateAppointment()`, and "Your appointment link is copied." after copying.
     If the browser refuses to copy, the link is shown in a field to copy by hand
     and the screen does not move on (the visitor would lose the only copy).
   - After copying, the final screen opens 4 seconds later. It is a new transient
     panel (`appointment-done`, never saved as the visitor's stage): "You are all
     set up", the closing message as its caption, and a button "CONTINUE TO IHQ
     PORTAL". The screen stays held (no clip, no timers, Ask Sami disabled), since
     the visit is over.
   - **CONTINUE TO IHQ PORTAL** goes to the IHQ Coach page (the dev,
     2026-10-09): `home_url( '/portal-home' )`, the address of the "Coach" item in the
     portal header.
   - **The link is a placeholder until step 4:** the page address with the start
     time in `appointment` (ISO, UTC). It is not signed and no page handles it yet.
     It is isolated in one function so step 4 replaces it with the server's signed
     link. This change must not go to production before steps 4 and 5.
   - **Collecting a contact method first (Ivan) is not in this step.** Which screen
     and whether the identity is needed is still open (question 1), so Set an
     Appointment goes straight to scheduling for now.
4. **Endpoint in the theme (step 4)** (`inc/aicoach-appointment.php`, same style as
   `aicoach-progress.php` and `gary-proxy.php`). The dev's decisions, 2026-10-09,
   taken as defaults where the ticket is silent:
   - `POST ihq/v1/aicoach/appointment` with the same nonce and the same per-IP write
     limiter as the progress route. Body: `choice`, `date`, `time`, `timeZone`.
     Returns 201 `{ link, startsAt }`, 400 `{ errors }` with the same codes as
     `appointment.js` (the screen already has text for them), 429 when limited.
   - **The server validates again** with a PHP port of `validateAppointment()`
     (same rules: relative choices from the server clock, the field order, a time
     not strictly after now is past, the skipped hour refused, the repeated hour
     resolves to the first occurrence). The browser's clock and zone are never
     trusted. The PHP tests repeat the JS table, so the two cannot drift.
   - **Signed link:** `home_url( '/appointment/' )` with `?t=<token>`; the token is
     the appointment id and start time, signed with HMAC-SHA256 (the pattern of
     `gary-proxy.php`), so an edited link is detectable. The secret is the constant
     `IHQ_AICOACH_APPOINTMENT_SECRET` from configuration, with `wp_salt()` as the
     fallback. Verification (`ihq_aicoach_appointment_verify_token()`) is written
     and tested here because it belongs with the signing; the page that uses it is
     step 5. The visitor's cookie ref is never put in the link.
   - **Storage:** one appointment per visitor, a new one replaces the old one. The
     record lives in the visitor's progress (`appointment`: id, start, zone, created)
     and in its own option `ihq_aicoach_appointment_<id>` (ref, start, zone, created),
     so the link page can find the visitor from the id alone on another device,
     where there is no cookie. Both are written by the server only: the browser could
     write the `appointment` key through the generic progress route (a shape reserved
     for this story), which would let it forge what the server later trusts, so that
     key is removed from the progress sanitizer.
   - **The screen** asks the endpoint for the link instead of building one
     (`buildAppointmentLink()` is removed). The local check still runs first for
     instant messages. The request starts inside the click and the clipboard write is
     given the pending link (`ClipboardItem` with a promise, falling back to waiting
     and `writeText`), because Safari on a phone only allows the write inside the
     gesture and the design is a phone Safari frame. A server error shows a message
     and nothing is copied.
   - Zone names are checked against the server's own list (`DateTimeZone::listIdentifiers()`
     with the legacy names), so offsets and abbreviations are refused. A browser that
     reports a name this server's time zone data does not have (for example an old
     alias such as `Europe/Kiev`, which this server has dropped) gets "time zone is not
     valid" and can choose another name from the list.
   - Not decided here and not needed: how long records are kept (options pile up
     one per appointment, no cleanup yet; step 5's "expired" state is the natural
     place to decide), and which channels the reminder uses (the progress record is
     cleared at registration, so the reminder step has to read the channels from the
     account then).
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
2. ~~**"The final screen" after copying**~~ Answered (design, screen 17): a new
   screen, "You are all set up" with a button "CONTINUE TO IHQ PORTAL". Still
   open: what happens to a visitor who has no account yet when they reach the Coach
   page (question 1).
3. Does Sami speak on the Times up screen (the commentary box is shown)? If so,
   what is the script?
4. Does Keep Talking Now resume exactly where she was, as FR-17's scenario 30
   says ("no loss")?
5. The behaviours the ticket marks as undefined: does "in 30 minutes" count from
   selecting or from copying; what happens when the clipboard is blocked or the
   appointment is under 5 minutes away; the join/missed boundary at exactly 15
   minutes; what the reminder says and whether it carries the link.
6. ~~Single or multiple choice?~~ Answered (Ivan, 2026-10-08): single choice, but
   drawn as the square checkbox look, not round radio buttons.
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
- A language pick that cannot restart anything (review, CodeRabbit): when the new
  language resolves to the clip already loaded, or the screen is the live stream,
  `restartCurrentClipForLocale()` returns without playing, so `closeTimeUp()` used to
  leave the clip it had paused paused. The function now returns whether it restarted
  the clip and `closeTimeUp()` plays the paused clip otherwise. Live, on the intro
  (live stream, `video.srcObject` set): Time is up paused the stream, Mandarin was picked
  while it showed, and Keep Talking Now resumed the stream (`paused: false`, time
  advancing).
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

## Verification, step 2
- `npm run test:js`: 127 tests, 46 of them new in `appointment.test.js`. They cover the
  three choices, every missing-field combination and its order, the past rule at the
  exact boundary, invalid dates, times and zones, zones with no daylight saving,
  half-hour and negative offsets, summer and winter, the skipped hour when clocks go
  forward (New York, Belgrade), the repeated hour when they go back, the
  slots, and the zone list with a replaced `Intl`.
- Manual mutation checks (Stryker is deferred): 26 mutations (comparison and
  boundary flips, the two durations, offset sign and side, earliest versus latest
  occurrence, the skipped-hour check, the 23/59 limits, blank handling, inherited
  object keys, slot size and step, sorting, the UTC guarantee, field order), 23
  killed. The three that survive are equivalent: removing either the month or the day
  check of the rollover test changes nothing because each catches what the other
  would (the year check covers the rest), and dropping the milliseconds in the offset
  calculation only matters for a fractional instant, which no caller produces.
- Not verified: the module in a real browser (it is not imported by the page yet),
  and behaviour on a browser without `Intl.supportedValuesOf` (covered by a replaced
  `Intl` only).

## Verification, step 3
- `npm run test:js`: 136 tests (127 before; 11 new in `appointment-screen.test.js`,
  2 removed with `buildTimeSlots()`), `npm run test:php` all pass, `node --check` on
  the entry as a module and `php -l` are clean. `tests/aicoach-modules.test.php` now
  expects the two new modules (`appointment`, `appointment-screen`).
- wp-env, mobile viewport (414 px), threshold temporarily lowered to 0.06 (reverted,
  not in the diff), resumed at the time selection with the 2-minute tier:
  - Time is up, Set an Appointment: the scheduling screen replaced it (heading,
    "Set your appointment", three square boxes, green button, hint), matching
    design 16.
  - Copy with nothing chosen: "Please choose when you would like your appointment."
  - Other: the three options disappeared and Date, Timezone (419 zones, Europe/Belgrade
    preselected) and Appointment Time appeared (design 16c).
  - Other with the zone preselected and the rest empty: "Please choose a date." and
    "Please choose a time." on two lines. Today 00:01 in the zone: "That time has already
    passed. Please choose a later time."
  - Other, today in two minutes (17:27 in Belgrade, +2): copied
    `...home-ai-coach/?appointment=2026-10-09T15%3A27%3A00.000Z`; "Your appointment link
    is copied."; the final screen opened 4 seconds later.
  - In an hour: the copied start was 60 minutes after the click; In 30 minutes was
    chosen in the refusal run below.
  - Clipboard refused (`writeText` replaced to throw), In 30 minutes: "We could not copy
    the link automatically. Please copy it from the box below.", the link shown and
    selected in a field, the button usable again, no move to the final screen.
  - Final screen: closing message, "You are all set up", one-line green button
    "CONTINUE TO IHQ PORTAL" (href `/portal-home`, the header's Coach address). The saved stage stayed
    `home` through all of it (never `appointment` or `appointment-done`).
- Not verified: the real flow with a live Gary session (all runs resumed at the time
  selection), a real phone's date and time pickers (the browser pane's pickers were
  filled by script), the translations (English only), other time zones' daylight-saving
  edges on the screen (covered by the unit tests of step 2), a narrow-phone layout
  below 414 px, and the screen's look at desktop width.
- Known gaps, by design of this step: the copied link is the unsigned placeholder (step
  4), no page handles it, and a visitor with no contact method is not asked for one first (question 1).

## Verification, step 4
- `npm run test:js`: 147 tests (136 before; the placeholder link's tests are replaced by
  tests for the request and the clipboard). `npm run test:php`: 12 suites, all pass,
  the new `tests/aicoach-appointment.test.php` has 107 checks. Its time-rule cases
  are the ones of `appointment.test.js` (the zone table, the refusals, the field order,
  the boundary at exactly now, Tokyo against UTC), so the browser and the server are
  held to the same rules. Also covered: the token (made, verified, a changed start or
  signature, a missing or extra part, non-text input, a correctly signed payload of the
  wrong shape, the configured secret against the salts), the stored records (own
  option plus progress, a new one replacing the old one, another visitor untouched,
  the rest of the progress kept, ids that are not UUIDs), the route (201, 400 with
  codes, nothing stored on a refusal, non-text fields ignored, an oversized zone,
  429 past the limit, `Cache-Control: no-store` on every answer) and that the progress
  sanitizer drops an `appointment` sent by the browser.
- Manual mutation checks on `inc/aicoach-appointment.php` (Stryker is deferred): 18
  mutations (the past boundary, earliest against latest occurrence, the offset day,
  the 23 and 59 limits, the 30 minutes, the field order, the signature comparison, the
  part count, deleting the old appointment, dropping the progress save, dropping
  `no-store`, ignoring the limiter, removing the date check, loosening the zone list,
  ignoring the configured secret, the id format and the start type): 17 killed, and
  the survivor (the id format check, because a name with no option returns null
  anyway) got a test with an option of that name, which kills it.
- wp-env (the route called from the page): missing fields 400 with the three codes, a
  past time 400 `time-in-past`, a bad nonce 403, In an hour 201 with a link
  `.../appointment/?t=<payload>.<signature>` and the start about an hour ahead; the
  visitor's progress then holds the appointment (id, start, zone, created).
- wp-env, the screen (414 px, threshold temporarily lowered and reverted): In 30 minutes
  sent one request with the selection, the clipboard was written through a
  `ClipboardItem` with the link the server returned, "Your appointment link is
  copied.", the final screen 4 seconds later; the saved stage stayed `home`. With the
  server answering 500 (fetch replaced): "We could not create your appointment link.
  Please try again.", the button usable again, one request. With the clipboard refusing
  both writes: the link shown in the field and the copy message, a second press made no
  new request, copied the same link and moved on.
- Failed writes (review, CodeRabbit): `ihq_aicoach_appointment_create()` used to delete
  the visitor's previous appointment before writing the new one and ignored the write
  results, so a failed write could leave the visitor with no appointment while the route
  still answered 201 with a link for a record that was not saved. It now keeps the
  previous appointment until both writes have succeeded, checks the option write, reads
  the progress record back (`ihq_aicoach_progress_save()` does not report its result,
  and `update_option()` leaves the cache alone when the write fails), removes the new
  option if the progress write did not take, and the route answers 500 with no link.
  10 new PHP checks (a failed appointment write, a failed progress write, the old
  appointment kept, a retry that then works, the 500) and 7 mutations of this handling,
  all killed. Live on wp-env: two requests in a row left one appointment option, the
  visitor's current one.
- Not verified: Safari on a phone (the reason for the `ClipboardItem` write; Chrome
  here), a real server with Cloudflare in front, the signature against a configured
  secret on a live instance (the unit test covers it), another visitor's appointment
  in the same database, and the link page (step 5; the link goes to `/appointment/`,
  which does not exist yet).

## Notes
- The three scheduling options are a radio group (one choice) drawn as squares,
  per the answer to question 6, like the Time is up options and the time selection.
- The appointment record is stored by the theme (WordPress); nothing here
  requires a separate service.
- The new strings are English-only on purpose (see Approach 1).
- Related: `2026-10-05-aicoach-coach-flow-testable-modules.plan.md` (the test
  runner and module layout this reuses).
