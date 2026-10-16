---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-7561 onboarding auto-launch kill switch (influencer-hq PR #120)
date: 2026-10-15
---

# Code Review: ENGR-7561 onboarding auto-launch kill switch

PR: https://github.com/avantageusa/influencer-hq/pull/120 (head `2390149` at review time)
Ticket: ENGR-7561 (epic PO-3063)

## Blockers

None.

## Warnings

- `functions.php:423` — the new `glob( inc/portal-onboarding-*.php )` loader makes the load order of the epic's modules depend on file names (alphabetical), and departs from this file's convention of one explicit `require_once` per module. Any future module with a file-scope dependency on another (for example `const X = IHQ_PORTAL_ONBOARDING_ENABLED_OPTION;` in a `portal-onboarding-gate.php`, which sorts before `-switch.php`) fails at load with an undefined-constant error, and any stray file matching the pattern (a copy such as `portal-onboarding-switch-old.php` left on a server) is executed on every request. The stated benefit (avoiding merge conflicts on a list of `require_once` lines) is small: those conflicts are trivial to resolve. Suggested fix: replace the loop with `require_once get_template_directory() . '/inc/portal-onboarding-switch.php';` and have each later ticket add its own line; if the glob stays, document in the docblock that modules must not depend on each other at file scope, and update the test accordingly.

## Nits

- `inc/portal-onboarding-switch.php:50` — the accepted "on" values `array( '1', 'true', 'yes', 'on' )` are inline magic strings (coding standards: no magic strings), while the module already names its option constant and the repo names such sets elsewhere (`inc/aicoach-appointment-page.php`). Suggested fix: `const IHQ_PORTAL_ONBOARDING_ON_VALUES = array( '1', 'true', 'yes', 'on' );` and use it in the `in_array()` call.
- `tests/portal-onboarding-switch.test.php:45` — the loader check is a source-text `strpos` on `functions.php`: a whitespace or quoting change in the loop fails it while the module is still loaded, and it would still pass if the loop were moved inside a function that is never called. Suggested fix: if the explicit `require_once` is adopted, assert on that line; otherwise drop the check or extract the loop into a small function that the test can call with a stubbed directory and assert which files it required.

## Checks run

- `php -l` on `inc/portal-onboarding-switch.php`, `functions.php`, `tests/portal-onboarding-switch.test.php` (PHP 8.3.6): no syntax errors.
- `php tests/portal-onboarding-switch.test.php`: all checks pass (option name, absent option off, read with `false` default, 8 on values, 13 off values, loader present). The PR's test plan lists this as not run; it now has been.
- `npm run test:php` (whole suite): all pass.
- `npm run test:js`: 188 pass, 0 fail.
- CI on the head commit: "PHP tests" and "JS tests" both success.
- Not run: `phpcs` (`vendor/` not installed, no Composer install in this session); `npx tsc --noEmit` not applicable (no TypeScript); Stryker not applicable to PHP; no manual run on a WordPress site.

## Against the ticket

- "With the option off, no user is shown onboarding": met. The option is absent after deploy, `get_option(..., false)` returns `false`, and every unrecognised value reads as off. Nothing on the Coach page changes in this PR.
- "With it on, eligible users are": not verifiable in this PR by design. The switch has no caller until the entry gate (ENGR-7564) lands; the function itself returns `true` for the on values. This criterion is satisfied end to end only once ENGR-7564 merges and the Verify story runs.
- "The default after deploy is off": met (fail-closed reader, tested).
- "Tests cover both states": met for the switch function.
- Scope: the only thing beyond the switch itself is the glob loader (see the warning). Per-user targeting is correctly absent.

## Summary

A small, well-contained change: a fail-closed WordPress option reader with thorough value coverage, tests that pass locally and in CI, and a DEPLOYMENT NOTE that matches the ticket. The one real concern is the glob-based loader in `functions.php`, which trades a trivial merge convenience for implicit, name-ordered loading of every matching file; an explicit `require_once` is safer and matches the rest of the file. The two nits are polish. Recommended action: revise (address the loader warning), then merge.
