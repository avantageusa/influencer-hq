---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-7573 sync onboarding answers to Braze (PR #130)
date: 2026-10-15
---

# Code Review: ENGR-7573 sync onboarding answers to Braze (PR #130)

Independent review of `feat/ENGR-7573-onboarding_braze_sync` at `30466ad` against base
`feat/ENGR-7563-onboarding_progress_store`, the ENGR-7573 ticket (Done-when) and
`.claude/rules/coding-standards.md`.

## Blockers

None.

## Warnings

- `tests/portal-onboarding-braze.test.php:47`: the Done-when says "A Braze failure is **logged**",
  but no test checks that anything is logged. The comment says `error_log()` "cannot be stubbed".
  That is wrong: `ini_set( 'error_log', $tmp_file )` sends its output to a file the test can read
  (checked on PHP 8.3). If any of the three `error_log()` calls in `ihq_portal_onboarding_braze_send()`
  were deleted, every test would still pass. **Fix:** in `reset_world()`, point `error_log` at a
  temp file. Then assert the exact log line for the `WP_Error` case, for the 500 and 400 cases
  (code and body), and for a missing external_id. Assert that a 2xx logs nothing.
- `inc/portal-onboarding-braze.php:88`: the `/users/track` POST runs synchronously inside the
  onboarding save request at priority 20, so each answer save's REST response can wait up to
  5 s when Braze is slow. The ticket asks that Braze "never blocks the onboarding flow". A save
  that stalls for 5 s on every answer is a soft block of the flow, and it hits every screen (one
  request per answer). The PR names the 5 s cap as the trade-off. However, an option that keeps the
  logging was not considered: move the send off the response path and keep it in the same PHP
  process. **Fix:** queue the attributes in the `saved`/`completed` listeners. On `shutdown`,
  call `fastcgi_finish_request()` when it exists, then send. Failures still reach `error_log`,
  and the existing tests can call the flush function directly. If the author keeps the synchronous
  send, say in the PR why a stall of up to 5 s per answer is acceptable and get Product's sign-off.

## Nits

- `inc/portal-onboarding-braze.php:58`, `:65`, `:89`, `:135`: `'social_platforms'`,
  `'language_preference'`, `'/users/track'` and `'onboarding_completed'` are inline literals.
  The same file already names `IHQ_PORTAL_ONBOARDING_BRAZE_HANDLE_PREFIX`. Name these the same way
  (the standards' "no magic strings").
- `tests/portal-onboarding-braze.test.php:115`: one `check()` joins three assertions with `&&`,
  so a failure does not say which one broke. Split it into three checks, or compare the whole
  `sent()` payload with `===` as the other platform test does.

## Checks run

- `php tests/portal-onboarding-braze.test.php` (PHP 8.3.6): 50 PASS, exit 0.
- `npm run test:php` (every `tests/*.test.php`): exit 0.
- `npm run test:js`: 188 tests, 188 pass.
- `php -l` on both new PHP files: no syntax errors.
- Not run: `npx tsc --noEmit` (no TypeScript in this diff or this repo's PHP path), Stryker (no PHP
  mutation tooling in the repo), and a live Braze workspace (none available).

## Spec check (ENGR-7573 Done-when)

- Every answer sends its attribute with its enum value for the user's `external_id`: yes. Every
  value of every answer is asserted with an exact payload.
- `onboarding_completed` is sent when onboarding is marked complete, and only once: yes.
- A Braze failure never blocks the save: yes for errors. Logging is unasserted, and there is a
  latency concern (see the warnings).
- Tests assert the exact payload per answer and the failure path, with Braze mocked: yes, apart
  from the logging.
- Scope: no segments or campaigns, and nothing beyond the ticket.

## Outside this PR

- `ihq_portal_onboarding_mark_complete()` has no production caller on this stack yet. A grep of
  `inc/` finds only its definition. Until the task that wires completion lands, `onboarding_completed`
  never fires outside tests.
- No client calls `ihq/v1/onboarding/progress` yet. The only `progress` URL in `js/` is
  `/aicoach/progress` (`js/aicoach-coach-flow.js:243`), so this PR's latency impact depends on the
  client task.
- The `/users/track` request (endpoint, headers, key) is now built in both
  `inc/braze-integration.php` and `inc/portal-onboarding-braze.php`. One shared
  `ihq_braze_track()` helper would be a separate refactor.

## Summary

The module does what the ticket asks. The mapping is a pure function, it hooks the store's
actions without editing the store, and the tests are thorough on payloads (every enum value, the
null for a cleared sub-answer, all 19 handle attributes, unticking a platform, completing only
once, and network, 500, 400 and missing-id errors that still store the answer). There are no
blockers. Two warnings: the "is logged" half of the Done-when is untested, and the synchronous
send can add up to 5 s to every answer save. Recommendation: **revise** for the two warnings.
Separately, the PR's own privacy gate stands: do not merge until OQ17, Jenna's confirmation that
handles may go to Braze, is resolved.
