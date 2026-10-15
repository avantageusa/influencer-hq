---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-7567 onboarding teaching screens 1, 2, 7-11 and 13 (PR #125)
date: 2026-10-15
---

# Code Review: ENGR-7567 onboarding teaching screens (PR #125)

Independent review of `feat/ENGR-7567-onboarding_teaching_screens` against its base
`feat/ENGR-7566-onboarding_screen_engine` (reviewed head 4b4ccc7), the ticket ENGR-7567 (spec,
Done-when and comments), the copy stories PO-3203, PO-3208, PO-3209 and PO-3211, and
`.claude/rules/coding-standards.md`.

Copy check: every string in `TEACHING_STRINGS_EN` was compared with the four stories and matches
character for character (the stories' in-quote line breaks joined with one space). Screen ids match
`SEQUENCE`; screen 13 reads no state; screen 11 is the title and two bullets only; long screens
scroll in the layer (`.ihq-onb` has `overflow-y: auto`); the stylesheet is picked up by the shell's
`portal-onboarding-*.css` glob.

## Blockers

- `js/aicoach/onboarding-screens-teaching.js:92` (and `:100`) — the Equity charts plot invented
  values (`[40, 90, …, 390]` shares; `$0.20 → $4` share value over 24 months). The ticket asks for
  "both charts with the sample values drawn in the frame"; these are not from the frame, and they are
  a financial projection shown to Influencers on a screen whose copy is under legal review (OQ18).
  Fix: read the values off frame 46497:79385 (or get them from Product/Design) and replace `values`
  before merge; if the frame stays unreadable, keep this screen out of the merge rather than ship
  made-up figures.

## Warnings

- `js/aicoach/onboarding-screens-teaching.js:180` — no hero images ship: nothing in the repo hooks
  `ihq_portal_onboarding_images`, so every screen renders without its hero image, and the Done-when
  "each screen matches its frame … at 398 px" is unverified (plan todo `design-check` is `blocked`).
  Fix: add the hero assets and the filter callback that maps each stage to its image, then do the
  398 px comparison per frame; or have Product move hero images to an explicit follow-up ticket so
  this one's Done-when is honest.
- `js/aicoach/onboarding-screens-teaching.test.js:138` (also 118, 139–157, 166) — partial matchers
  (`assert.ok( svg.includes(…) )`, `startsWith`, `every(…startsWith)`), which the standards forbid
  ("Use `toEqual`… never partial-match helpers"). A mutation in any un-asserted attribute (gridline
  coordinates, font size, stroke) survives. Fix: assert the whole SVG string with `assert.equal`
  against an expected literal for the small fixture, and each chart `src` with
  `assert.equal( src, chartImageSrc( chart, t ) )`.
- `js/aicoach/onboarding-screens-teaching.test.js:227` — the "every piece of copy is a string-table
  key" test checks only `h2, p` on one screen and then asserts `strings.t( 'ko', 'tourTitle' )`, which
  tests the string table, not this module. Fix: loop over all eight stages and every `h2, p, li`, and
  after `api.setLocale( 'ko' )` assert the rendered `h2` text equals `'월드 투어'`.

## Nits

- `js/aicoach/onboarding-screens-teaching.js:147` (also 150, 155, 158 and the `* 10` in 142) —
  magic numbers in the SVG: label offsets 6/4/8, title baseline 16, font sizes 11/13, stroke width 3,
  rounding factor 10. Fix: name them in the `CHART` constant (e.g. `labelGap`, `titleBaseline`,
  `axisFontSize`).
- `js/aicoach/onboarding-screens-teaching.js:118` — `CHART_COLOURS.line` duplicates
  `--ihq-onb-yellow` (`#fdd65b`). Fix: add a one-line comment that a `data:` SVG cannot read CSS
  custom properties and must be kept in step with `css/portal-onboarding.css`.
- `js/aicoach/onboarding-screens-teaching.js:99` — y-axis labels (`'$.20'`, `'$1'` …) are copy
  outside the string table, while the x labels are in it; the ticket says all copy goes through the
  table. Fix: add `yKeys` with entries in `TEACHING_STRINGS_EN`, mirroring `xKeys`.
- `css/portal-onboarding-teaching.css:62` — the `"↓"` pseudo-content is announced by screen readers
  ("downwards arrow") between every step. Fix: `content: "↓" / "";`.

## Summary

A clean, well-scoped plug-in: copy is verbatim, the string-table wiring and language redraw work,
screen 13 is fixed, and the 327 JS tests pass. The one blocker is the chart data: the PR ships invented
equity figures where the ticket asks for the frame's, on a screen under legal review. Hero images and
the 398 px frame check are also still open, and several tests use partial matchers the standards
forbid. Recommended action: **revise**.

Checks run: `npm run test:js` (327 pass, 0 fail); `php tests/portal-onboarding-shell.test.php` (all
pass, including the teaching stylesheet's media-query check); manual copy diff against PO-3203/3208/
3209/3211. Not run: ESLint/stylelint (`npx wp-scripts` failed with npm E401 in this environment, no
`node_modules`); `npx tsc --noEmit` (not a TypeScript repo); mutation testing (no Stryker script in
`package.json`); the 398 px visual check (design frames not available here).
