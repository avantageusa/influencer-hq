---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-7565 portal onboarding shell (PR #123)
date: 2026-10-15
---

# Code Review: ENGR-7565 portal onboarding shell (PR #123)

Independent review of `feat/ENGR-7565-onboarding_shell` (head `0f62d59`) against ENGR-7565 and `.claude/rules/coding-standards.md`.

## Done-when check

| Done-when | Status |
|---|---|
| Shell matches frame 46497:79246 at 398 px | **Not verified.** Neither the build session nor this review could open the Figma frame. The values match the landing flow's version of the frame (`page-home-aicoach.php`: banner `398 / 182`, commentary `min(316px, 79.4%)` on `#2d2b2b`, green `#148942`). |
| Mute silences the video, keeps subtitles; unmute restores audio | Met. `onboarding-narrator.js` `setMuted` touches only `video.muted`; tests at `onboarding-narrator.test.js:109`, `:135` |
| Caption-only fallback with no clip | Met. `playCaptions` at reading pace via `estimateDurationMs`; tests `:71`, `:143`, `:156`, `:180`, `:200` |
| No new pixel value in a media query; lint passes | Queries are `(min-width: 768px)` / `(min-width: 1025px)`, both already used across the estate (15× and 13×). Lint is not verified independently (see Checks). |
| Tests cover mute, caption-only fallback, subtitle clearing | Met |

## Blockers

None.

## Warnings

- `css/portal-onboarding.css:167`: Done-when 1 (the frame matches 46497:79246 at 398 px) is still unverified. Before merge, someone with Figma access should compare the header, banner, commentary box and CTA positions at 398 px and record the result on the ticket or PR. Product can also accept the landing-flow parity explicitly.
- `template-parts/portal-onboarding.php:26`: the layer declares `role="dialog" aria-modal="true"`, but nothing moves focus into it or keeps focus inside it. Keyboard focus stays on the Coach page underneath, which `aria-modal` has hidden from assistive tech. Tab walks through invisible links behind the overlay. Fix: in `createShell`, focus the first control (or the dialog root with `tabindex="-1"`) on open, and keep Tab inside `root`, or make the background `inert` while `OPEN_CLASS` is set and remove it in `close()`.

## Nits

- `js/aicoach/onboarding-narrator.js:140`: if the clip reports `video.duration` as `Infinity` (a streamed or unknown-length source), `buildSentenceCues` returns cues with `startMs: NaN`, and no subtitle ever shows for that clip. Guard with `Number.isFinite( video.duration )` and fall back to `estimateDurationMs( script )`.
- `js/aicoach/onboarding-narrator.js:136`: a stall or error mid-clip restarts the captions from the first sentence while the paused clip stays on its current frame. Consider starting the captions from `video.currentTime`, or noting the restart as intended.
- `js/aicoach/onboarding-shell.js:59`: the hamburger menu closes only on the button or Escape. Add a click outside it or focus leaving it, as the portal's other menus do.
- `inc/portal-onboarding-shell.php:128`: `'/portal/portal-home/'` is a magic string. Name it as a constant, or reuse the portal's existing home-path constant or helper if there is one.
- `js/aicoach/onboarding-shell.test.js:174`: these are partial-match assertions (`assert.ok( template.includes( ... ) )`), which the standards discourage. Asserting the extracted set of `data-onb` values with `assert.deepEqual` would kill more mutants.
- `js/aicoach/onboarding-boot.js:80`: `setLocale` and the `locale` event are the start of language switching, which the ticket marks out of scope (T10/ENGR-7572). They are harmless, but they are scaffolding beyond the Done-when. Keep them minimal, or move them to ENGR-7572.

## Checks run

- `npm run test:js`: 257 pass, 0 fail.
- Every `tests/*.test.php` run with PHP CLI: all pass, including `tests/portal-onboarding-shell.test.php`, which the build session could not run.
- `php -l` on `inc/portal-onboarding-shell.php` and `template-parts/portal-onboarding.php`: clean.
- Not run: ESLint and stylelint (no `node_modules` in this environment). There is no TypeScript in this repo, so no `tsc`. There was no manual or browser check, and no Figma comparison (no Figma access).

## Summary

The diff is well structured and does what the ticket asks. The pure modules are injected and tested against fakes. Landing-flow files are untouched. The breakpoints reuse existing queries. Mute, the caption-only fallback and subtitle clearing all have targeted tests. The PHP test the author could not run passes. There are no blockers. The biggest open risk is the unverified design match at 398 px, which is Done-when 1. The dialog focus handling is the main code issue. Recommendation: **merge** once the 398 px frame check is done and recorded. Fix the focus warning in this PR or file it before release. The nits are optional.
