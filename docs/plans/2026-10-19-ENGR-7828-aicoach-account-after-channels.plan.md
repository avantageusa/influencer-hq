---
name: Create the account after the channels step, before the closing screen (ENGR-7828)
overview: >
  Gary releases the spoken closing passage (complete) only once the visitor's account exists, and
  Product decided Sami should speak it (PO-3100, Ivan Vladic, 2026-10-19). The account used to be
  created by the last "Let's Continue" click, after that screen. This moves the creation to the end
  of the channels step: the channels CONTINUE creates it, a failure shows the server's reason there,
  and the final click only closes the Gary session and follows the address the creation returned.
  The path that books an appointment is unchanged. The spoken passage, the /attest call and the
  session handling are a separate task that waits for Gary's answers.
todos:
  - id: module
    content: js/aicoach/account-step.js creates the account once (shared in-flight call, no repeat after success, failure is returned and not remembered, saves flushed before, afterCreated once) with tests
    status: completed
  - id: flow
    content: The channels submit creates the account and queues the closing screen only on success; the final click follows the returned address and falls back to creating it for a visitor saved there before this change
    status: completed
  - id: guards
    content: No progress is saved after the account exists, and the Time is up prompt no longer fires
    status: completed
  - id: ui
    content: An error line on the channels panel (empty it takes no room) shows the server's reason
    status: completed
  - id: verify
    content: JS and PHP tests, seven mutations of the module, the whole path in wp-env with a normal run and with the first creation refused
    status: completed
  - id: next
    content: The spoken closing passage, the attest and the session handling - separate task, after Gary answers
    status: pending
---

# [ENGR-7828] Create the account after the channels step

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7828

## Problem
The closing passage can be spoken only for an account that exists. Today the account is created
by the click that comes after that screen (`js/aicoach-coach-flow.js`, the final-continue handler).

## Approach
- `js/aicoach/account-step.js` holds the rules, so they are tested without a browser: one account,
  a second call during the first shares it, a call after success returns the same address without
  sending, a failure is returned (never thrown) and never remembered, progress saves are flushed
  before the request and `afterCreated` is called once.
- The channels submit (not the appointment path) calls it. On success the closing screen is queued
  as before; on failure the panel stays, shows the server's reason (`serverErrorMessage()`) in
  `#aicoach-channels-error` and CONTINUE works again.
- The final click calls the same function: when the account exists it returns the address at once;
  when it does not (a visitor saved at that screen before this change) it creates it there, as
  that click always did. Then it closes the Gary session and goes to the address.
- After creation `saveProgress()` does nothing: the server cleared the progress at creation, and a
  later save (the closing screen's own stage) would leave a row for a visitor who is already a user.
- After creation the Time is up prompt does not fire: with the account made, registration is
  complete and an appointment to finish it makes no sense.
- `inc/aicoach-modules.php` registers the module; `tests/aicoach-modules.test.php` lists it.

## Alternatives considered
- Speaking the passage after the click, with a wait before the portal: the visitor waits and the
  click stops being a button; Ivan chose the earlier account.
- Changing the passage so it does not lead into the button: needs new wording approval.
- Creating the account server-side when channels are saved (progress route): hides the account
  creation inside a save and its errors from the screen that has to show them.

## Blast radius
- A visitor who leaves on the closing screen already has an account (Ivan, 2026-10-19: accepted).
  The shared creation function (`ihq_create_influencer_user_from_registration_data()`) syncs the
  profile to Braze, so that now happens at the channels step, before the closing screen. Whether
  Braze then sends the visitor anything (a welcome message, for example) is not visible from this
  repo and was not checked: it should be looked at on dev, with whoever owns the Braze campaigns.
- A visitor who reloads the closing screen after creation starts the landing flow again (the
  progress is gone). Known; can be handled with the spoken passage.
- The existing-account path is the same: sign-in code and login address; for it the address is
  followed at the final click as before.
- The appointment path never creates an account; the booking branch returns before the creation.

## Test plan
- [x] `npm run test:js` and `npm run test:php`
- [x] Seven manual mutations of the module (no shared call, failure remembered, no afterCreated, no
      flush, no repeat guard, redirect true, channels forwarded as is) each fail a test
- [x] wp-env, whole path: `create-account` goes out when the channels CONTINUE is clicked, no
      progress save follows, the closing screen shows, the click lands on `/portal/portal-home/`
      with the onboarding layer
- [x] wp-env, first creation answered 400 "That username is already taken.": the channels panel
      stays, shows the reason, CONTINUE is usable, the second try creates the account
- [ ] A visitor who books an appointment and the "Time is up?" prompt after creation (not run)
- [ ] Braze on dev (not run)
