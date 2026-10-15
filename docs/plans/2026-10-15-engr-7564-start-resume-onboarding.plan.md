---
name: Start or resume onboarding on the Coach page and mark it complete
overview: >
  The Coach page has to start Sami's onboarding at screen 1, or resume it at the saved stage, for an
  Influencer created after release who has not finished it, and leave everyone else's page as it is.
  This adds the eligibility gate (release-date option, kill switch, completion), a render action the
  onboarding shell hooks into, and a completion route. The screens themselves are other tasks.
todos:
  - id: gate
    content: inc/portal-onboarding-gate.php — release date, should_launch(), layer context, render action
    status: completed
  - id: complete-route
    content: POST /ihq/v1/onboarding/complete using the store's mark_complete()
    status: completed
  - id: coach-page
    content: page-portal-home.php calls ihq_portal_onboarding_maybe_render() after the portal header
    status: completed
  - id: tests
    content: tests/portal-onboarding-gate.test.php — every eligibility case, kill switch off/absent, resume, completion
    status: completed
  - id: verify
    content: test:js and lint; PHP tests run only in CI (no PHP on the build host)
    status: completed
---

# [ENGR-7564] Start or resume onboarding on the Coach page and mark it complete

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7564
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
Nothing on the Coach page knows whether an Influencer has been onboarded. New accounts must be walked
through onboarding; existing accounts and finished users must not see it.

## Approach
- `ihq_portal_onboarding_should_launch( $user_id )`: logged in, kill switch on, account created strictly
  after the release date, and the store (ENGR-7563) not complete. Each check fails closed.
- Release date: option `ihq_portal_onboarding_release_date` (ISO date, a date without a zone is UTC,
  or a Unix timestamp). Missing or unreadable means nobody is onboarded. Accounts created before it are
  never launched, which is how "treated as complete" is met without writing to every old account.
- Kill switch: checked as `function_exists( 'ihq_portal_onboarding_enabled' ) && ihq_portal_onboarding_enabled()`
  because ENGR-7561 is a separate PR off `main`.
- `ihq_portal_onboarding_maybe_render()` is called from `page-portal-home.php` right after the portal
  header. When the user should be onboarded it fires `ihq_portal_onboarding_render_layer` with
  `{ state, progressUrl, completeUrl, nonce }`; the shell (ENGR-7565) renders the layer on it. An empty
  `state.stage` means screen 1; otherwise the engine (ENGR-7566) resumes at that stage.
- `POST /wp-json/ihq/v1/onboarding/complete`: same permission check as the progress routes; marks
  complete. The browser then removes the layer, leaving the user at the top of the Coach page
  (closing screen, ENGR-7571).

## Alternatives considered
- Marking every pre-release account complete with a migration: a write to every existing user for a
  rule one date comparison expresses; also the date would still be needed for accounts created during
  the deploy window.
- Rendering the shell from here: the shell is its own ticket off `main`; an action keeps the two
  independent and lets either merge first.
- A new URL for onboarding: EQ2 says it renders inside the Coach page.

## Blast radius
`page-portal-home.php` gains one guarded call that prints nothing unless every condition holds and a
shell is hooked on the action. With the kill switch off (the default) the page is unchanged.

## Notes
DEPLOYMENT NOTE (from the ticket): deploy, then set the release date
(`wp option update ihq_portal_onboarding_release_date "<release instant, UTC>"`), then switch on the
kill switch after QA.
