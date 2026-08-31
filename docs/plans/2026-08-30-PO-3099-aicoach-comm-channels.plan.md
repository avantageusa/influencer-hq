---
name: PO-3099 — Communication channels, minimum 1 required (FR-08)
overview: >
  Adds the communication-channel selection screen (8 channels: Email, KakaoTalk, Line, SMS,
  Telegram, WeChat, WhatsApp, Zalo) queued once FR-07's identity form succeeds. Unlike FR-07, this
  screen has an approved coach script, so it's spoken like every FR-01–FR-06 screen while the
  interactive checkbox/field UI is shown simultaneously (same "narrate over an interactive panel"
  pattern FR-03 established for the tier picker). CONTINUE is gated on at least one channel checked
  and every checked channel's field passing its format check.
todos:
  - id: comm-channels-panel
    content: Add the comm-channels panel — caption (spoken), hint message, 8 channels each with a checkbox that reveals its own labelled input field, CONTINUE button
    status: completed
  - id: queue-from-identity-success
    content: Identity's submit handler now pushes COMM_CHANNELS_SCREEN and resumes the sequence runner (same pattern as FR-03's tier-confirm queuing), instead of just disabling its own button
    status: completed
  - id: per-channel-validation
    content: Email/SMS/WhatsApp/Line get real format validation (regex, matching the ticket's given formats); KakaoTalk/Telegram/WeChat/Zalo are marked "TBD" in the ticket itself, so those four just require non-empty rather than an invented strict pattern
    status: completed
  - id: reveal-on-check
    content: Checking a channel reveals its field and required it for CONTINUE; unchecking clears and hides it
    status: completed
  - id: min-one-required
    content: CONTINUE disabled + hint message shown whenever zero channels are checked; inline per-field error when a checked channel's value is invalid
    status: completed
  - id: fix-showpanel-pending-transition
    content: "Found via live testing: showPanel() silently dropped a call that arrived while a prior transition's 800ms fade was still settling (e.g. FR-08's screen queued right after FR-07's own transition into 'identity'). Fixed by queuing the latest requested panel and applying it once the in-flight transition finishes, instead of dropping it."
    status: completed
  - id: fix-finishsequence-destination
    content: "Found via live testing: finishSequence() was hardcoded to always navigate to 'identity', so calling it a second time (after comm-channels' own queued screen finished) incorrectly bounced back to the identity screen. Replaced the hardcoded destination with sequenceEndDestination state, cleared to null by identity's success handler since nothing is built after comm-channels yet."
    status: completed
  - id: verify
    content: php -l, composer lint:php (only the known unrelated failure), phpcs (+1 finding, same pre-existing text_domain misconfiguration), extensive live wp-env testing that caught and fixed two real sequencing bugs before they shipped
    status: completed
---

# PO-3099 Communication channels — Scenario 13/14/15 (FR-08)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3099 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Problem

After identity capture, every tier needs a communication-channel screen: 8 channels, at least one
required, each with its own contact-detail field revealed on selection, CONTINUE gated on valid
data for every checked channel. Unlike FR-07, this screen **has** an approved coach script.

## Approach

1. **Has narration, so it's a SCREENS entry** (unlike FR-07). Queued from identity's own submit
   success handler — `SCREENS.push(COMM_CHANNELS_SCREEN)` then resume the sequence runner, the
   exact same pattern PO-3096 established for tier-confirm queuing equity/competition screens.
   The interactive checkbox/field UI lives inside the same panel as the spoken caption, mirroring
   how FR-03's tier picker is narrated over while still being clickable.
2. **Two independent validity concerns**: whether zero channels are checked (shows a persistent
   hint, not a per-field error) vs. whether a checked channel's value passes its format check
   (per-field inline error). CONTINUE requires both: `checked.length > 0 && every checked channel
   valid`.
3. **TBD channels get lenient validation, on purpose.** The ticket itself marks KakaoTalk,
   Telegram, WeChat, and Zalo's exact contact-detail format/Braze attribute as "TBD" — rather than
   invent a plausible-looking format that might not match what gets confirmed later, those four
   just require a non-empty value. Email/SMS/WhatsApp/Line have concrete formats given in the
   ticket, so those get real regex validation.

## Two real bugs found via live testing (not just static mockups)

Testing this against a running wp-env instance (not just reading the code) surfaced two genuine
sequencing bugs that static review wouldn't have caught:

1. **`showPanel()` silently dropped a call arriving mid-transition.** The panel-swap fade takes
   800ms (`isAnimating` stays true the whole time); FR-08's queued screen can get requested via
   `showPanel()` well within that window on a fast/automated submit (identity's own transition
   *into* itself hadn't settled yet). The old code just `return`ed and dropped the request
   entirely — the caption text still got set on the (now-hidden-forever) target panel, but the
   panel itself never became visible. Fixed by remembering the latest requested `panelKey` and
   re-applying it once the current transition's own timers finish, instead of dropping it.
2. **`finishSequence()` was hardcoded to always navigate to `'identity'`.** That was correct the
   first time (nothing else existed to navigate to), but calling `finishSequence()` a *second*
   time — once comm-channels' own single queued screen finishes playing — incorrectly sent the
   visitor back to the identity screen they'd already completed. Replaced the hardcoded call with
   a `sequenceEndDestination` variable that identity's success handler clears to `null` (nothing
   built after comm-channels yet, same "just stop" pattern PO-3096 used before FR-07 existed).
   Future stories that queue more content after their own predecessor succeeds should update this
   the same way, rather than re-introducing a hardcoded destination.

Both were caught by literally driving the flow through wp-env with real timing (not forced DOM
state) and watching where it actually landed — worth calling out in review since neither would
show up from reading the diff alone.

## Alternatives considered

- **Inventing strict formats for the 4 TBD channels**: rejected — the ticket explicitly flags
  these as unconfirmed; guessing risks shipping validation that contradicts whatever gets decided.
- **A single global "select at least one" error only, no per-field errors**: rejected — Scenario
  15 explicitly distinguishes "no channels selected" from "a selected channel's field is empty/
  invalid," so the UI keeps both as separate signals.

## Blast radius

- `page-home-aicoach.php`: one new panel + `$aicoach_channels` config array + its CSS block.
- `inc/anam-proxy.php`: one more `i18n` string (`channelInvalid`).
- `js/aicoach-coach-flow.js`: the two bug fixes above touch shared code (`showPanel`,
  `finishSequence`) used by every prior FR — verified none of PO-3092–98's own flows regressed
  (still exercised the same tier confirms, tap-to-advance, and fallback paths during testing).

## Notes

- Captured channels (`capturedChannels`) are in-memory only, same pattern as FR-07's
  `capturedIdentity` — FR-09 (account creation) reads both once it exists.
- Same known-interim-architecture note as PO-3092–98 (direct Anam integration, approved temporary
  by Filip Milinković pending a separate engineer's ("Gary") agent API).
- Deferred to FR-13 per team agreement: script text (including this story's) stays hardcoded in
  `js/aicoach-coach-flow.js` for now — see PO-3096's plan notes / memory for why.
