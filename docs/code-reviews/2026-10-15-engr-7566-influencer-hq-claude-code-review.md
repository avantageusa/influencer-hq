---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-7566 forward-only onboarding screen engine (PR #124)
date: 2026-10-15
---

# Code Review: ENGR-7566 forward-only onboarding screen engine (PR #124)

Independent review of `feat/ENGR-7566-onboarding_screen_engine` against `feat/ENGR-7565-onboarding_shell`
(head reviewed: `819bde1`), against the ticket's Done-when and `.claude/rules/coding-standards.md`.

## Blockers

None.

## Warnings

- `js/aicoach/onboarding-engine.js:70`: "not installed" and "failed to load" are treated the same.
  `isAvailable()` is `definitions.has( id )`, and `installModules()` (onboarding-boot.js) swallows a
  failed `import()` or `install()`. So a screen module that fails to load on a transient network error
  is passed over, `advance()` saves the stage beyond it, and because the flow is forward-only the
  Influencer never sees that question screen and its answer is never stored. The pass-over exists so
  screens 14–15 can wait on ENGR-7570, which the ticket comment accepts, but it should not apply to
  a screen whose module the server listed in `config.modules` and that then failed.
  **Suggested fix:** make the held screens explicit (e.g. a `HELD` list in onboarding-sequence.js,
  emptied by ENGR-7570), or have `installModules()` report the failed ids and keep the flow from
  advancing or saving past a screen whose module failed (show an error/retry state instead). Add a
  test where a listed module throws on import and the stage is not saved past it.

- `js/aicoach/onboarding-main.js:72`: the return value of `engine.start()` is ignored. When no screen
  module installs (all imports fail, or the shell ships before ENGR-7567+), `start()` returns `null`
  and the modal layer stays open over the Coach page with an empty content area and no CONTINUE.
  The Influencer is stuck with only the logout link. **Suggested fix:** when `start()` returns
  `null`, log it and close the layer (`shell.close()`), or show a "try again" state. The decision
  belongs in main, and main needs a test.

## Nits

- `js/aicoach/onboarding-engine.js:170` / `:98`: saves are fire-and-forget, so two quick advances put
  two `{ stage }` POSTs in flight. If they finish out of order, the store keeps the older stage
  (resume lands one screen back) and `api.state` is overwritten with the older answer. Consider
  chaining saves on one promise (serialize), or ignoring a response whose `stage` is not the latest
  one sent.
- `js/aicoach/onboarding-history.js:29`: on a page reload during onboarding, the current entry is
  already a guard entry (`history.state.ihqOnboarding`) and `install()` pushes another one, so guard
  entries pile up across reloads, and after `complete()` Back lands on a stale same-URL entry.
  Consider skipping the push when `history.state?.[ GUARD_STATE_KEY ]` is already set (and a test for it).
- `css/portal-onboarding.css:270`: raw `#ff6b6b` for the validation colour, in a file that defines
  `--ihq-onb-*` colour tokens. Add `--ihq-onb-error` alongside the others and use it.

## Ticket conformance (Done-when)

- One-step advance in the 16-screen order, with the stage saved: met (`onboarding-sequence.js`,
  tested through the whole order). Screens without a module are passed over, as the Jira comment
  says (see Warning 1 for the failure case).
- Invalid question screen does not advance and shows validation: met (`isValid` / `showValidation`
  gate, tested, no save sent).
- Browser back leaves the user on the current screen: met (history guard, tested with a fake History).
- Screen-15 edit returns to 14, the other two choices go to 16: met (`BRANCHES` data, tested).
  "Drafts kept" depends on the draft screen reading the stored drafts (ENGR-7570). The engine
  re-renders screen 14 and keeps nothing itself.
- Unit tests for order, validation, history guard, edit branch: present and passing.
- No scope creep beyond the per-group stylesheet list, which the PR states and covers with a PHP test.

## Checks run

- `npm run test:js`: 308 pass, 0 fail.
- `npm run test:php` (incl. `tests/portal-onboarding-shell.test.php`, not run by the author): ALL PASS.
- `php -l` on `inc/portal-onboarding-shell.php` and `template-parts/portal-onboarding.php`: clean.
- Not run: ESLint/stylelint (no `node_modules` in this environment). `npx tsc --noEmit` does not apply
  (JS repo, no tsconfig). No manual run (no local WordPress site).

## Summary

A clean, well-isolated change. The sequence and branches are data, the engine takes all its
dependencies as arguments, and the tests use exact assertions on returns and side effects.
All Done-when items are met and the tests pass. The main risk is the silent pass-over: a screen
whose module fails to load is skipped for good, and an empty modal appears when nothing installs.
Both are cheap to address. Recommended action: **revise** (address the two warnings), then merge.
