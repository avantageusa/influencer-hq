---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-7575 installable portal (Save IHQ to Home Screen), PR #131
date: 2026-10-15
---

# Code Review: ENGR-7575 installable portal (PR #131)

Reviewed head: `5f825ca` (feat/ENGR-7575-installable_portal into main), against the ENGR-7575 ticket body
and comments and `.claude/rules/coding-standards.md`.

## Blockers

None.

## Warnings

- `js/aicoach/save-home.js:158` — an accepted install is not remembered. `showPrompt()` only calls `hide()`
  on `accepted` (and `appinstalled` at line 184 does the same), so nothing is written to storage. On the
  next visit in an ordinary browser tab (not standalone), Chrome no longer fires `beforeinstallprompt`
  because the app is installed, so the button comes back and opens the "Open your browser's menu…" sheet,
  telling an Influencer who already installed the app to install it. Fix: persist on `accepted` and on
  `appinstalled` too (call `writeDismissed()`, or a separate `ihq_save_home_installed` key read alongside
  it at start-up), and add tests for both paths asserting the stored value.
- `js/aicoach/save-home.js:25` — nothing in the repo renders an element with `data-ihq-save-home`
  (`grep -rn data-ihq-save-home` finds only this PR's module and tests), so on merge the behaviour is
  attached to no button and the Done-when items "on Android Chrome the button shows the install prompt; on
  iOS Safari it shows the instruction sheet" and "in standalone mode the button is not shown" cannot be
  observed from this PR. Fix: either add the attribute to the SAVE IHQ TO HOME SCREEN button in this PR,
  or state in the PR body (and on the ticket) which story renders the button and that it must carry
  `data-ihq-save-home`, so the dependency is tracked before Verify on dev.

## Nits

- `js/aicoach/save-home.js:75` — `'prompt'`, `'sheet-ios'`, `'sheet-other'` are magic strings compared again
  in `handleClick()` (lines 172, 176) and the tests. Export a frozen `SAVE_HOME_ACTION` object and use it
  everywhere.
- `js/aicoach/save-home.js:189` — `handleClick()` is a floating promise. If `prompt.prompt()` throws (Chrome
  raises `InvalidStateError` when the event is stale) the rejection is unhandled and the click does
  nothing. Catch it in `showPrompt()` and fall back to `openSheet()`; add a test with a throwing `prompt`.
- `js/aicoach/save-home.js:132` — the sheet declares `aria-modal="true"` but has no backdrop and no focus
  trap, and Escape only works while focus is inside the sheet. Either drop `aria-modal` or keep focus inside
  and listen for Escape on the document while the sheet is open.
- `js/aicoach/save-home-page.js:9` — colours (`#b7962f`, `#111`, `#148942`) and sizes are literals in a JS
  string. Name them as constants (or move the rules to the theme's SCSS) so they carry intent.
- `js/aicoach/save-home.test.js:227` — `assert.ok( page.includes( … ) )` is a partial, source-text match
  (the standards forbid `toContain`-style assertions) and breaks on any reformat. Export the style string
  (or a `buildSheetStyle()` helper) and assert it with `assert.equal`.

## Summary

The change is small, well-isolated and matches the ticket's shape: manifest via REST with per-environment
URLs, head tags and the module only on portal templates, one delegated click handler, the three platform
branches, and the hide conditions with storage failures contained. No service worker was added (out of
scope respected). `npm run test:js` (207 pass) and `npm run test:php`, including the new
`tests/portal-install.test.php` (all checks pass), ran clean here; `php -l` on `inc/portal-install.php` is
clean. ESLint could not be run (dependency install was refused by the registry), and Chrome's
installability check and real devices still need the deployed dev site, as the PR says. The two warnings
are a real UX regression after a successful install and an untracked dependency on the button that the
behaviour attaches to. Recommended action: **revise** (fix the warnings; nits optional).

## Outside this PR

- Two different portal home URLs are in use: `home_url( '/portal-home/' )` in `page-portal-login.php:42`
  and `:382` and `page-home-aicoach.php:17`, versus `home_url( '/portal/portal-home/' )` in
  `template-parts/portal-footer.php:56` and `template-parts/portal-turnstile-interstitial.php:23` (and this
  PR's manifest `start_url`). Not caused by this PR; worth confirming which one is canonical.
