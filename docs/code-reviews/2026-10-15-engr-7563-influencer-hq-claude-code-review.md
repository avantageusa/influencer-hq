---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-7563 portal onboarding progress store (influencer-hq PR #121)
date: 2026-10-15
---

# Code Review: ENGR-7563 portal onboarding progress store (PR #121)

Independent review of `feat/ENGR-7563-onboarding_progress_store` at `cb69778` against `main`, against
ticket ENGR-7563 (Done-when) and `.claude/rules/coding-standards.md`.

Checks run in this review:

- `php -l inc/portal-onboarding-progress.php functions.php`: no syntax errors (PHP 8.3.6)
- `php tests/portal-onboarding-progress.test.php`: 224 checks, all pass (the PR's test plan left this unticked; it now has a local run)
- `npm run test:php` (every `tests/*.test.php`): pass
- `npm run test:js`: 188 pass, 0 fail
- CI on `cb69778`: "JS tests" and "PHP tests" both green
- Not run: `npx tsc --noEmit` (no TypeScript in this diff), Stryker (no PHP mutation tooling in this repo; no JS changed), a manual run against a WordPress site (none available)

Done-when against the diff:

| Done-when | Status |
| --- | --- |
| GET returns stored state, empty default for a new user | Met (`handle_get`, tested) |
| POST merges only allowlisted fields/values; anything else 400, nothing stored | Met for the REST route (32 rejected inputs tested). The PHP `merge()` API does not apply the allowlist (W2) |
| Both routes 401 when logged out | Met (`permission_check`, tested) |
| State survives logout/login, same from any browser | Met (user meta, no cookie) |
| Tests cover every field, rejected input, logged-out access, merge | Met; PHP and JS suites pass |

## Blockers

None.

## Warnings

- **W1** `inc/portal-onboarding-progress.php:379` — `ihq_portal_onboarding_merge()` does a read-modify-write of the whole record, including `completed` and `completed_at`, which it never changes. If `mark_complete()` runs while a POST is between its read (line 378) and its write (line 379), the POST writes back `completed => false` over the completion. `ihq_portal_onboarding_completed` has already fired, so `is_complete()` then says false, the entry gate (ENGR-7564) sends the Influencer back into onboarding, and the next `mark_complete()` fires the completed action a second time with a new timestamp. The closing screen saving its stage and marking completion in close succession is the expected flow, so this window is reachable. Suggested fix: keep completion in its own user meta key (e.g. `_ihq_portal_onboarding_completed_at`) that only `mark_complete()` writes and `get_state()` reads, so a merge can never overwrite it; add a test that interleaves a stale merge with a completion.
- **W2** `inc/portal-onboarding-progress.php:376` — `ihq_portal_onboarding_merge()` is the public API the epic's other tasks call (T2, T6, T7, T11), but it trusts its `$partial` and does no validation: `merge( $uid, array( 'answers' => array( 'baccarat_experience' => 'Played a lot' ) ) )` stores a non-enum value that Braze and the screens will then receive, and a non-array `answers` makes the `foreach` in `apply()` warn. The allowlist is the contract the ticket asks for, and today only the REST route enforces it. Suggested fix: validate inside the public merge (call `ihq_portal_onboarding_validate_partial()` and return `{ state, error }` or a `WP_Error`), or rename the unvalidated function as internal and expose a validated one; add tests for an invalid partial through the PHP API.
- **W3** `inc/portal-onboarding-progress.php:380` — a posted language is also written to `_ihq_aicoach_language`. That key drives the portal locale filter (`inc/aicoach-register.php:495`) and Braze `language_preference` (`inc/braze-integration.php:157`), so this POST now changes existing behaviour (the whole portal switches locale) beyond what ENGR-7563 asks for: its Done-when only covers storing the language in the onboarding record, and the comment cites ENGR-7572 as the reason. Suggested fix: move the side-write to ENGR-7572 (e.g. a listener on `ihq_portal_onboarding_saved`), or have ENGR-7563's ticket updated to include it, so the ticket and the change agree.

## Nits

- **N1** `inc/portal-onboarding-progress.php:365` — a handle for a platform that is not selected is dropped silently with a 200 (`POST {"handles":{"x":"@z"}}` when `x` is not selected returns success and stores nothing for it). A client that saves handles before platforms loses data without a signal. Suggested fix: return 400 when a posted handle's platform is not in the resulting `platforms`, or document the ordering requirement in the route's docblock for the screen tasks.
- **N2** `inc/portal-onboarding-progress.php:391` — `ihq_portal_onboarding_saved` and `ihq_portal_onboarding_completed` are a cross-task contract (ENGR-7573 binds them) but are bare string literals. Suggested fix: name them as constants next to the meta key constants, as the standards ask for magic strings.
- **N3** `docs/plans/2026-10-15-engr-7563-onboarding-progress-store.plan.md:20` — the `verify` todo ("PHP tests run only in CI", "lint") is `completed`, while the PR's test plan says `php -l` was not run. Suggested fix: make the todo's text match what ran (PHP tests have now run in CI and in this review).

## Summary

A clean, well-scoped store: the allowlist is strict and whole-request, rejected inputs store and fire nothing, responses are `no-store`, the user-meta keying meets the any-browser requirement, and the tests are thorough and pass locally and in CI. No blockers. The main risks are for the tasks that build on it: a merge can silently undo a completion (W1), and the PHP API the other tasks will call bypasses the allowlist the REST route enforces (W2). W3 is a scope question about writing the existing language preference. Recommended action: revise for W1 and W2 (both small), settle W3 with the ticket, then merge.
