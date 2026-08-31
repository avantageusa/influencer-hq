---
name: PO-3100 — Account creation and portal transfer (FR-09)
overview: >
  Adds the final confirmation screen (approved script, "Everything is ready... Let's Continue")
  queued once FR-08's comm-channels succeeds. Its button posts the data captured across FR-07
  (identity) and FR-08 (channels) to BE's account-creation endpoint and either redirects to the
  portal on success or shows an inline error and lets the visitor retry — never fakes success,
  since (unlike FR-07's username check) there's no safe placeholder for a real account/session.
todos:
  - id: final-panel-markup
    content: Add the final-continue panel — caption (spoken), error slot, "Let's Continue" button
    status: completed
  - id: queue-from-channels-success
    content: Comm-channels' submit handler now pushes FINAL_SCREEN and resumes the sequence runner (same pattern as FR-07→FR-08), instead of just disabling its own button
    status: completed
  - id: create-account-call
    content: "Button posts { firstName, lastName, username, channels, language } to BE's assumed POST {identityRestBase}/create-account, expects { success, redirectUrl } or { success:false, error }"
    status: completed
  - id: no-fail-open
    content: Unlike FR-07's uniqueness check, a missing/failing endpoint surfaces as a real inline error (Scenario 16) rather than a fail-open success — faking a portal redirect with no real account/session would just break, not help
    status: completed
  - id: verify
    content: php -l, composer lint:php (only the known unrelated failure), phpcs (+9 findings, same pre-existing text_domain/tabs conventions), live wp-env testing of both the real-404 error path and a mocked-success redirect path
    status: completed
---

# PO-3100 Account creation and portal transfer — Scenario 16/17/18 (FR-09)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3100 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Scope (confirmed before writing code)

This is the account-creation, Braze-write, and portal-login story — squarely BE territory per
the same agreement already established for FR-07's username check ("BE builds username/account
creation, FE only calls the endpoint"). This story's FE scope is: the final screen, its script,
and calling BE's (not-yet-built) endpoint with the data FR-07/FR-08 already collected — not
building any of the account/session/Braze mechanics itself.

## Approach

1. **Has narration, so it's a SCREENS entry**, queued from comm-channels' own submit success
   handler — identical pattern to FR-07 queuing FR-08. `sequenceEndDestination` stays `null`;
   there's no further panel after this one's own speech finishes — the real "next step" is the
   portal redirect the button triggers, not another screen.
2. **No fail-open, unlike FR-07.** FR-07's username check could safely assume "available" on a
   missing endpoint because the real BE check would still catch a genuine duplicate at actual
   creation time — low stakes either way. There's no equivalent safe assumption here: faking a
   successful redirect with no real account or logged-in session would just send the visitor to a
   portal page that doesn't work for them. So a missing/failing endpoint surfaces as a real
   inline error (Scenario 16's formatting-error-surfaced-inline requirement, generalized to any
   failure) with the button re-enabled to retry — verified this is exactly what happens against
   the real (currently 404) endpoint.
3. **Assumed contract**, flagged for BE to confirm: `POST {identityRestBase}/create-account` with
   `{ firstName, lastName, username, channels: [{channel, value}], language }`, expecting
   `{ success: true, redirectUrl }` on success or `{ success: false, error }` on failure. Verified
   both paths live: the real endpoint (404 today) correctly shows the inline error and lets the
   visitor retry with no data loss; a mocked successful response correctly triggers
   `window.location.href` (confirmed the browser actually navigated to the given URL).
4. **Field-level error correction is explicitly out of scope for now.** Scenario 16 describes
   highlighting "the relevant field" on a formatting error and letting the visitor "correct and
   resubmit without restarting the flow" — but the relevant fields live on the identity/channels
   panels, two screens back, and FR-10 says the flow is otherwise strictly forward-moving. Without
   BE's real error-response shape (field-specific errors don't exist yet since the endpoint
   doesn't exist), designing the "go back to a specific field" mechanism now would be guessing at
   both the error contract and how backward-correction should reconcile with forward-only
   navigation. Shipped the generic error case only; flag this gap explicitly rather than building
   a guessed mechanism.

## Alternatives considered

- **Fail open on a missing endpoint** (matching FR-07's pattern): rejected — see "no fail-open"
  above. The risk profile is fundamentally different from a uniqueness check.
- **Building field-specific error correction now**: rejected — no real contract to design against
  yet (see point 4). Revisit once BE's endpoint exists and its error shape is known.

## Blast radius

- `page-home-aicoach.php`: one new panel + its CSS block.
- `inc/anam-proxy.php`: one more `i18n` string (`accountCreateErr`) + a doc comment on the assumed
  `create-account` contract, same location as FR-07's `username-available` note.
- `js/aicoach-coach-flow.js`: additive — `FINAL_SCREEN` + the button's click handler; comm-channels'
  submit handler gains the same push+resume block FR-07's already has.

## Notes

- **Side-finding, not fixed here**: tracing this story's flow surfaced that a visitor who never
  clicks a time tier at all still reaches identity capture — `finishSequence()`'s destination
  fires whenever the *original* fixed 4-screen sequence (intro/We-Believe×2/home) runs out,
  regardless of whether a tier was ever confirmed. This has been true since PO-3096 (not
  introduced by this story), meaning a visitor could in principle skip FR-05/06 entirely and land
  on identity/comm-channels/account-creation having seen none of the equity or competition
  content. Worth a real look — flagging for a follow-up, not fixing here since it's a pre-existing
  behavior spanning multiple already-shipped stories, not something introduced by FR-09.
- Same known-interim-architecture note as PO-3092–99 (direct Anam integration, approved temporary
  by Filip Milinković pending a separate engineer's ("Gary") agent API).
- `language: 'en'` is hardcoded in the request payload — FR-12/13 (language detection/selector)
  aren't built yet; update this once they are.
