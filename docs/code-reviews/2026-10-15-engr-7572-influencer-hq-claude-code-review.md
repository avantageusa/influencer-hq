---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-7572 seven-language onboarding strings and in-flow language selector (PR 129)
date: 2026-10-15
---

# Code Review: ENGR-7572 seven-language onboarding strings and in-flow language selector (PR 129)

Independent review of PR #129 (`feat/ENGR-7572-onboarding_languages` into `feat/ENGR-7565-onboarding_shell`, head `37a66ab`) against ticket ENGR-7572 and `.claude/rules/coding-standards.md`.

## Blockers

None.

## Warnings

- `js/aicoach/onboarding-addon-language.js:20` — a rejected save is silent. `fetch()` resolves on HTTP errors, so a 403 (expired `wp_rest` nonce) or a 400 from the store is never reported: only network failures reach `.catch()`. The ticket's "saved for the next visit" then fails without a trace. Fix: check `response.ok` and log the status when it is false (or reuse the `postJson()` helper from ENGR-7566 once both are in the same base, and log when `ok` is false).
- `js/aicoach/onboarding-addon-language.js:14` — the plug-in has no test. The request it sends (URL, method, nonce header, `{ language }` body), the `navigator.languages || [ navigator.language ]` fallback, and the failure path are all untested. The standards ask for every dependency to be mocked and every state covered. Fix: let `install()` take `fetchFn` / `navigator` (default `window.fetch` / `window.navigator`) and add `onboarding-addon-language.test.js` asserting the request, the browser-tag fallback, and a rejected save (non-ok and network error) both being logged.
- `js/aicoach/onboarding-language.js:119` — Escape only closes the list when focus is inside it. The keydown listener sits on `list`, but after the globe is clicked focus stays on the globe, so Escape does nothing there. The shell's menu (`onboarding-shell.js:60`) listens on `root` instead. The PR body says "Escape closes the list and returns focus to the globe". Fix: listen on `api.shell.root` (or on both `globe` and `list`) and close when the list is open, then add a test that presses Escape with focus on the globe.

## Nits

- `js/aicoach/onboarding-language.test.js:150` — the "every key present in all seven locales (or explicitly falling back)" test (a Done-when item) covers only the shell keys, plus `continue`, which setup adds by hand. Nothing will check the screen tables from ENGR-7567/7568/7569/7571 once those PRs land. Fix: have the test go through every English key in the table, after every screen table is registered (or have each screen PR add its table to this test).
- `js/aicoach/onboarding-language.test.js:138` — `locales.filter( ( code ) => 'ko' !== code )` would still pass if choosing the active language emitted `locale` again. Fix: `assert.deepEqual( locales, [ 'ko' ] )`, which pins the single opening emit.
- `css/portal-onboarding.css:181` — screen readers announce the `" ✓"` generated content ("check mark") on top of `aria-current`. Fix: `content: " ✓" / "";`.
- `inc/portal-onboarding-shell.php:130` — the meta key is a literal string. ENGR-7563 defines `IHQ_PORTAL_ONBOARDING_LANGUAGE_META_KEY` for the same key. Fix: use that constant once ENGR-7563 is in the base. Also, the PR body's Blast radius should mention that `_ihq_aicoach_language` drives the portal's WordPress locale (`ihq_aicoach_filter_portal_locale`, `inc/aicoach-register.php:495`), so a choice in onboarding also changes the portal locale on the next page load.

## Checks run

- `npm run test:js`: 272 pass, 0 fail.
- `npm run test:php` equivalent (every `tests/*.test.php` under php): all pass, including `tests/portal-onboarding-shell.test.php`.
- `npx tsc --noEmit`: not applicable (no TypeScript, no `tsconfig.json`).
- `npm run lint:js` / `lint-style`: not run. `npm ci` failed on registry authentication in this environment.
- Stryker: not run (not configured in this repo).

## Summary

The PR does what the ticket asks and stays in scope. The opening language is chosen saved → store → browser (`detectLocale`, zh-HK → yue) → English. The globe lists the seven languages, each in its own language, and marks the active one with `aria-current`, `lang` and a tick. Switching language goes through the `locale` event without re-rendering, so position and answers stay. Only approved translations ship (the landing flow's `continue`), and the keys that fall back to English are listed in the PR as the ticket requires. The save target (ENGR-7563's `language` field, which also writes `_ihq_aicoach_language`) and the clip restart on `locale` (ENGR-7566) are in sibling PRs, not this PR's base, so they only work end to end once those merge. The main risks are a save that fails silently on HTTP errors, the untested plug-in wiring, and Escape not working when focus is on the globe. Recommendation: revise (the three warnings), then merge.
