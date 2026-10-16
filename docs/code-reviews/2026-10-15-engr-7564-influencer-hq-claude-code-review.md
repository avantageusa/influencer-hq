---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-7564 start or resume onboarding on the Coach page (PR #122)
date: 2026-10-15
---

# Code Review: ENGR-7564 start or resume onboarding on the Coach page (PR #122)

Independent review of PR #122 (`feat/ENGR-7564-start_resume_onboarding` into
`feat/ENGR-7563-onboarding_progress_store`, reviewed at 9c2878d) against ticket ENGR-7564
and `.claude/rules/coding-standards.md`.

## Blockers

None.

## Warnings

- `inc/portal-onboarding-gate.php:104` — `ihq_portal_onboarding_should_launch()` never checks
  the user's role. The ticket scopes onboarding to "a logged-in Influencer", but any logged-in
  account created after the release date (an administrator, editor or QA staff account made
  after release) that opens the Coach page is onboarded too, and the module docblock says
  "whatever path it registered through", which reads as including them. Suggested fix: add an
  early `return false` unless `in_array( 'influencer', (array) get_userdata( $user_id )->roles, true )`
  (the role `inc/influencer-role.php` uses), and add a not-launched test for a post-release
  non-influencer account.
- `inc/portal-onboarding-gate.php:57` — `ctype_digit()` reads any all-digit value as a Unix
  timestamp, so a compact date such as `20261101` becomes 1970-08-23 and every account created
  since 1970 is onboarded as soon as the kill switch is on. That fails open, against the PR's
  own "every check fails closed". Relatedly, `strtotime()` (line 62) accepts relative values
  (`next monday`, `yesterday`), which make the release instant move on every request. Suggested
  fix: accept only the documented format (an ISO-8601 date/datetime, e.g. via
  `DateTimeImmutable::createFromFormat` / a strict regex before `strtotime`) and, if a timestamp
  form is kept, require it to be at least a plausible lower bound (for example after 2026-01-01);
  add tests for `20261101` and a relative string reading as unset.

## Nits

- `tests/portal-onboarding-gate.test.php:210` — the PR body says the completion route returns
  403 on a bad nonce, but only the 401 logged-out path is tested here (the stub request always
  sends the good nonce). Add a bad-nonce case asserting `array( 'status' => 403 )`, or note that
  the store's tests (#121) cover it.
- `tests/portal-onboarding-gate.test.php:215` — the Coach page check matches the exact source
  line, so a reformat of `page-portal-home.php` fails the test without any behavior change.
  Matching `ihq_portal_onboarding_maybe_render()` alone (as line 216 already does) is enough.

## Checks run

- `php -l` on `inc/portal-onboarding-gate.php`, `page-portal-home.php`,
  `tests/portal-onboarding-gate.test.php`: no syntax errors (PHP 8.3.6).
- `php tests/portal-onboarding-gate.test.php`: all checks passed.
- `npm run test:php` (full PHP suite): ALL PASS.
- Confirmed `ihq_portal_onboarding_enabled()` is the function name defined on
  `feat/ENGR-7561-onboarding_kill_switch`, and that `functions.php` loads every
  `inc/portal-onboarding-*.php` by glob, so the gate is loaded without a `functions.php` change.
- Not run: `npm run test:js` (no JS changed), `npx tsc --noEmit` (no TypeScript), manual
  exercise on a live site (no WordPress instance in this environment).

## Done-when coverage

- Eligible, not-complete user sees onboarding at screen 1 or the saved stage: met (render
  action carries the default state or the saved state; tested).
- Completed user, pre-release account, kill switch off: page unchanged (tested). See the role
  warning for accounts that are not Influencers.
- Marking complete stores the flag and reopening does not start onboarding: met (tested).
- Tests cover each eligibility case and the kill-switch-off case: met.

## Summary

The gate is small, readable and well tested, the render action keeps it independent of the
shell (ENGR-7565), and with no release-date option or the switch off nothing changes on the
Coach page. Two warnings should be fixed before merge: the missing Influencer role check
(onboards staff accounts created after release, against the ticket) and the release-date
parser accepting all-digit and relative values, one of which fails open for every account.
Recommended action: revise.
