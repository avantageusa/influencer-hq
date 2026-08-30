---
name: PO-3098 — Identity capture (FR-07)
overview: >
  Adds the identity-capture form (First Name, Last Name, Username) reached once the coach's spoken
  sequence naturally ends, for every tier. No coach narration exists for this screen (unlike every
  prior one — no approved script was given), so it's plain form logic: CONTINUE is gated on all
  three fields being non-empty, and a username-uniqueness check runs on blur or submit. The
  uniqueness-check endpoint is owned by BE (confirmed with Dejan — BE builds username/account
  creation, FE only calls it), which doesn't exist yet; the FE fails open against a 404 so the
  form stays usable end-to-end in the meantime.
todos:
  - id: identity-panel-markup
    content: Add the identity panel — headline, subheading, First/Last Name + Username fields, inline error slot, CONTINUE button (disabled by default)
    status: completed
  - id: finish-sequence-destination
    content: finishSequence() now navigates to the identity panel (previously a no-op) — the universal next step once the spoken sequence ends, regardless of which combination of equity/competition screens preceded it
    status: completed
  - id: required-field-gating
    content: CONTINUE disabled until First Name, Last Name, and Username are all non-empty (Scenario 10) — independent of username-availability state
    status: completed
  - id: username-uniqueness-check
    content: checkUsernameAvailability() calls an assumed BE endpoint (GET {identityRestBase}/username-available?username=, expects {available:bool}) on blur or submit (Scenario 11); shows inline error and blocks submission if taken
    status: completed
  - id: fail-open-until-be-ships
    content: Endpoint doesn't exist yet — network/non-2xx errors are treated as "available" with a console warning, so the form isn't blocked on a 404 before BE builds the real thing
    status: completed
  - id: verify
    content: php -l, composer lint:php (only the known unrelated failure), phpcs (new TextDomainMismatch findings traced to a pre-existing repo-wide phpcs.xml misconfiguration, flagged separately, not fixed here), live wp-env testing of all three states (fields-empty-disabled, submit-with-no-BE-endpoint fails open, submit-with-mocked-taken-response blocks correctly)
    status: completed
---

# PO-3098 Identity capture — Scenario 10/11/12 (FR-07)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3098 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Problem

Every tier needs an identity-capture step (First Name, Last Name, Username, all required) once
the coach's scripted content ends. Username must be checked for uniqueness inline, with the
result eventually feeding the portal Profile page (Scenario 12) — but account creation itself is
FR-09, a separate story.

## Scope clarification (before writing any code)

Checked with Dejan first: this ticket touches a real backend concern (username uniqueness must be
checked against existing accounts — impossible to do purely client-side). Per the team's agreed
split (BE builds username-check + account creation; FE only calls the endpoint), this story is
scoped to: the form UI, client-side required-field gating, and calling BE's (not-yet-built)
uniqueness endpoint. Account creation, Luna, and Braze writes are explicitly FR-09's job.

## Approach

1. **No coach narration for this screen.** Every FR before this one (FR-01 through FR-06) came
   with an "Approved script" block in its ticket; FR-07 doesn't have one. Rather than invent
   narration, this screen isn't part of `SCREENS`/`speakScreen` at all — it's the plain
   `showPanel('identity')` destination that `finishSequence()` now navigates to once the spoken
   sequence naturally exhausts, for every tier (2-minute arrives right after BTS; 5/10-minute
   after the FR-06 competition screens) — reusing the same "one universal end-of-speech hook"
   `finishSequence()` was designed as, back in PO-3096.
2. **Two independent gates**, matching Scenario 10 vs. 11 precisely: CONTINUE's disabled state
   depends only on all three fields being non-empty; the *username-taken* inline error is a
   separate check that runs on blur or submit and blocks the submit handler from proceeding
   (`capturedIdentity` never gets set) without needing to re-disable the button itself.
3. **Assumed BE contract, clearly flagged.** `checkUsernameAvailability()` calls
   `GET {identityRestBase}/username-available?username=X`, expecting `{ available: bool }`. This
   endpoint doesn't exist yet (BE hasn't built it as of this story) — confirmed the namespace/
   route/shape here is this FE's assumption, not something to treat as final. On any
   network/non-2xx response, the check fails open (treats it as available, with a console
   warning) purely so the form is usable end-to-end today; this needs to be revisited once BE
   ships the real endpoint (remove the fail-open, and confirm the assumed contract matches).
4. **Captured data held in memory only** (`capturedIdentity`), same pattern as PO-3094's
   `elapsedStartedAt` — FR-08 (communication channels) and FR-09 (account creation) will read
   this when they're built; no persistence exists yet, matching FR-11/Luna being a separate story.

## Alternatives considered

- **Guessing at BE's endpoint contract and building it here too** (since it's technically WP
  theme-side PHP): rejected per the scope clarification above — BE owns this specific piece by
  agreement, and duplicating it risks conflicting with whatever they actually build.
- **Blocking the whole form until a real endpoint exists**: rejected — the fail-open approach lets
  the rest of the flow (and future FR-08/09 work built on top of it) stay testable now, with the
  gap clearly flagged rather than silently accepted as permanent.

## Blast radius

- `page-home-aicoach.php`: one new panel + its CSS block (`.aicoach-identity*`).
- `inc/anam-proxy.php`: `AICOACH_SAMI` config gains `identityRestBase` and an `i18n` sub-object
  (`usernameTaken`, `identitySaved`) — no behavior change to the existing Anam session wiring.
- `js/aicoach-coach-flow.js`: `finishSequence()` now does something (previously a no-op comment);
  new, self-contained identity-form block — doesn't touch `SCREENS`/`speakScreen`/tap-to-advance.

## Notes

- **BE endpoint doesn't exist yet** — this is the load-bearing caveat of this PR. Confirm the
  real contract with BE once they build it, and remove the fail-open fallback then.
- **phpcs found 2 new `TextDomainMismatch` findings** in `inc/anam-proxy.php` (the two new
  `__()` calls) — traced to `phpcs.xml`'s `text_domain` property still being set to `_s` (the
  original starter-theme scaffold) instead of `influencer-hq` (which `style.css` correctly
  declares). This is a pre-existing, repo-wide misconfiguration that would flag *any* correctly-
  domained string anywhere in the theme — flagged as a separate background task, not fixed here.
- Same known-interim-architecture note as PO-3092–97 (direct Anam integration, approved temporary
  by Filip Milinković pending a separate engineer's ("Gary") agent API) — unaffected by this story
  either way, since this screen has no coach narration at all.
