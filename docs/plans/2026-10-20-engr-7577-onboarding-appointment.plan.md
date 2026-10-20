---
name: Onboarding appointment screens (Talk more?, Set your appointment)
overview: >
  PO-3429 (FR-18, Scenario 37) lets the Influencer book a later session with Sami. Per Ivan
  (2026-10-20), two screens come before the closing screen for everyone: "Talk more?"
  (Continue to IHQ portal / Set an Appointment) and the landing flow's scheduling (PO-3109
  rules, helpers and signed links); both choices end on the closing screen. The appointment is
  keyed to the logged-in Influencer and saved on their account; Join on the link leads to the
  portal Coach page. Showing "Talk more?" when time runs out mid-flow, the 5-minute reminder and
  the Braze confirmation (Scenario 38) are out of scope.
todos:
  - id: plan
    content: Write this plan (rewritten 2026-10-20 after Ivan's answers on PO-3429)
    status: completed
  - id: sequence
    content: Add time_up and appointment before closing in SEQUENCE and the store's stage list; branch Continue on time_up to closing
    status: completed
  - id: screens
    content: js/aicoach/onboarding-appointment.js (the two screens, tested) + onboarding-screens-appointment.js (browser wiring) + css/portal-onboarding-appointment.css; strings through the string table
    status: completed
  - id: route
    content: inc/portal-onboarding-appointment.php — POST /ihq/v1/onboarding/appointment keyed to the user (option record + user meta), Join redirect to the portal Coach page; appointmentUrl in the layer context; shared modules in the shell
    status: completed
  - id: landing-hook
    content: inc/aicoach-appointment.php / -page.php — share the request handling and add a Join redirect filter; landing behaviour unchanged
    status: completed
  - id: tests
    content: JS (sequence, engine, screens with fake DOM/clock) and PHP (route, store, join filter, gate, shell) tests; spot-check key guards by breaking them
    status: completed
  - id: verify
    content: npm run test:js (688) and test:php pass; 22 guards broken one at a time, all caught; lint not run (@wordpress/scripts not installed); real clipboard and a signed-out Join not exercised
    status: in-progress
---

# [ENGR-7577] Onboarding appointment screens

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7577
**Story:** https://avantageusa.atlassian.net/browse/PO-3429 (Scenario 37; source of truth over the ticket text)
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem

PO-3429 asks for the landing flow's appointment booking (PO-3109) inside the onboarding,
behind an options screen (Figma 47487:22255). Ivan's answers on PO-3429 (2026-10-20): the
screens come before the closing screen for everyone, titled "Talk more?" (to be confirmed with
Marcus); single choice; Continue to IHQ portal goes to the end screen, Set an Appointment
schedules first and then goes to the end screen. The dev confirmed the end screen is the
closing screen for both, so frame 17 ("You are all set up") is not used.

## Approach

**Order (js/aicoach/onboarding-sequence.js, inc/portal-onboarding-progress.php).**
`… post_action, time_up, appointment, closing`. A new branch `{ from: 'time_up', choice:
'continue', to: 'closing' }` skips scheduling. If the module is not installed, post action goes
straight to closing as before (the engine passes over uninstalled screens).

**Screens** (logic in `onboarding-appointment.js`, a core module with everything injected so it
is tested with the fake DOM; `onboarding-screens-appointment.js` is the thin plug-in that imports
the landing helpers and the browser's clipboard, fetch, clock and timers, as
`onboarding-addon-language.js` does):

- `time_up` (PICK): "Talk more?", two single-choice options in the question screens' look,
  moving on 500 ms after a pick. Nothing is stored.
- `appointment` (MANUAL): "Yes, I'm Ready..." / "Set your appointment" (the story's wording,
  as the landing flow), In 30 minutes / In an hour / Other; Other swaps the options for Date,
  Timezone, Appointment Time. Copy Your Appointment Link validates with `validateAppointment()`
  (appointment.js), asks the server, copies with `copyToClipboard()` (started inside the tap
  for Safari), confirms, and moves on to closing 4 s later. No option, a missing field or a
  past time gives no request and a message per problem; a server refusal shows its codes; a
  failed copy shows the link to copy by hand and keeps it for a retry (no second appointment).

**Server (inc/portal-onboarding-appointment.php, new).** `POST /ihq/v1/onboarding/appointment`
with the onboarding permission check (logged in + wp_rest nonce), the same body, responses and
per-IP limit as the landing route, the same validation, token and link. The record is keyed to
the user: an option per appointment (`userId` instead of `ref`, so the link page finds it) and
user meta `_ihq_portal_onboarding_appointment` (decision 2026-10-17: the account, not Luna). A
new booking replaces the previous one. Completing the onboarding (closing screen) does not end
the link: Join leads to `/portal/portal-home/`, where the Influencer talks with Sami. Signed
out, that page shows the portal's own sign-in gate (code sent to their contact method), which is
what the adjusted last scenario asks for. The landing files get two behaviour-neutral changes:
the route handling moves into a shared function, and Join asks a filter for the redirect (null
keeps the landing flow's ref + AI Coach page); no credential is handed out for an onboarding
record.

## Alternatives considered

- A detour off the main order taken on screen 4's I'M READY TO LEARN answer (the first build):
  replaced by Ivan's answer, which puts the screens before closing for everyone.
- Keep "You are all set up" after scheduling: the dev chose the closing screen as the end
  screen for both choices.
- End the link when the onboarding completes: booking happens just before completion, so the
  link would read "ended" at once.
- Key the onboarding appointment through the landing route (cookie ref): the ref is the landing
  flow's credential, and the Influencer already has an account.

## Blast radius

- Every Influencer now sees "Talk more?" between post action and closing.
- `inc/aicoach-appointment*.php`: refactor + filter only; the landing tests pass unchanged.
- The shell registers `appointment` and `appointment-screen` as shared modules (static deps of
  the entry, ~10 KB more on the onboarding page).
- Existing tests that encoded "closing follows post action" now expect `time_up`; tests that
  list the layer context keys and shared modules gain the new entries.
- Braze sync (#130, open) maps answers, not stages; the two new stage ids may need a mapping.

## Notes — open, not built

- Showing "Talk more?" when time runs out mid-flow (Ivan): the onboarding has no time limit
  yet; needs one from Product before it can be built.
- "Talk more?" wording: Ivan to confirm with Marcus.
- The 5-minute reminder: to be built for the landing flow in its own ticket, then reused here.
- Braze confirmation message (Scenario 38).
- "Book" buttons on the link page still go to the landing AI Coach page for every state.
- No Sami line / clip for the two screens yet (no script given).
- All new copy is English only, as the landing flow's (no approved translations).
