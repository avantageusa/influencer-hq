---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-7568 onboarding question screens 3, 4, 5 and 12 (PR #126)
date: 2026-10-15
---

# Code Review: ENGR-7568 onboarding question screens 3, 4, 5 and 12 (PR #126)

Independent review of https://github.com/avantageusa/influencer-hq/pull/126 (head `3c32257`,
base `feat/ENGR-7566-onboarding_screen_engine`) against ENGR-7568 and
`.claude/rules/coding-standards.md`. The PR's own work is commit `3c32257`; the store files
(`inc/portal-onboarding-progress.php`, its test and plan) come from ENGR-7563's merged-in branch
(#121) and were read only as the contract this PR writes to.

## Done-when check

| Done when | Status |
|---|---|
| Each screen matches its frame (46497:79353, :79698, :79730, :79465), last follower option "Under 10,000" | Copy and options match the ticket; "Under 10,000" present. Visual match **not verified** by the author or by this review (see W2). |
| None advances without a selection; revealed sub-option required; switching away hides and clears it | Met: `missingAnswer()` gates `isValid()`, `pickMain()` clears the sub-answer on screen and sends `null` to the store. Tested. |
| Each answer stored as its enum value | Met; a test cross-checks every value against `ihq_portal_onboarding_answer_values()`. |
| Tests cover every option, the reveal and clear, and the stored values | Met (24 new tests). |

Nothing beyond the ticket was added. The appointment branch (T16) is correctly left out.

## Blockers

None.

## Warnings

- **W1 `js/aicoach/onboarding-screens-questions.js:284` — per-click saves are fire-and-forget and run concurrently, so an answer can be lost or a cleared sub-answer revived.**
  `apply()` calls `api.save()` on every pick without waiting for the previous one, and CONTINUE
  sends `save( { stage } )` straight after. The store merges by read-modify-write of one user meta
  (`ihq_portal_onboarding_merge()`, `inc/portal-onboarding-progress.php:376-379`) with no lock.
  Scenario: on screen 4 the Influencer taps I'M NEW TO STREAMING, then I'M READY TO LEARN and
  CONTINUE within one round trip (easy on a slow mobile connection). Three POSTs are in flight;
  each PHP request reads the same old state, and whichever writes last wins, so
  `streaming_learning_intent` (or the main answer) is dropped while the UI showed it as picked.
  If two requests arrive out of order, `{ streaming_learning_intent: null }` can land before the
  earlier `{ streaming_learning_intent: 'new_ready_to_learn' }`, leaving a sub-answer stored for a
  main answer that no longer reveals it. The ticket's "each answer is stored" and the Braze hand-off
  (ENGR-7573) then see wrong data.
  Suggested fix: serialize this module's saves (chain each `api.save()` on the previous promise,
  e.g. a module-level `let pending = Promise.resolve(); pending = pending.then( () => api.save( … ) )`),
  and add a test with a `postJson` mock that resolves out of order to show the writes are sent one
  at a time. The engine-wide race (stage save vs. answer save) is listed under "Outside this PR".

- **W2 `css/portal-onboarding-questions.css:1` — the first Done-when item (each screen matches its frame) is unverified.**
  The PR body and the plan's `design-check` todo (`blocked`) say the frames could not be opened.
  The CSS (option borders, green selected fill, 24px sub-option indent, grey off-diamonds `#555`,
  diamond glyph `◆`) is therefore a guess at the design. This review could not open Figma either.
  Suggested fix: compare screens 3, 4, 5 and 12 with frames 46497:79353, :79698, :79730, :79465 at
  398 px (someone with Figma access, or a screenshot pasted into the ticket) and adjust before the
  ticket is called done; until then keep the PR's "Design fidelity unverified" note and the
  unticked test-plan box.

## Nits

- **N1 `js/aicoach/onboarding-screens-questions.js:201` — `role="radio"` buttons without the radio keyboard pattern.**
  Each option is a separate Tab stop and arrow keys do nothing, while screen readers announce
  "radio button, 1 of 3" and users expect arrows to move the choice. Suggested fix: roving
  `tabindex` (0 on the checked or first option, -1 on the rest) with an ArrowUp/ArrowDown handler on
  the group, or native `<input type="radio">` + `<label>` styled as the buttons.

- **N2 `js/aicoach/onboarding-screens-questions.js:250` — the sub-option radiogroup is labelled by the main question.**
  Both groups use `aria-labelledby` = the `h2`, so the revealed group is announced as "Do you have
  streaming experience?" again. Suggested fix: give the revealing option button an id and label the
  sub group with it (or with a dedicated hidden label).

- **N3 `js/aicoach/onboarding-screens-questions.js:134` — re-picking the already-selected answer sends another write.**
  `pickMain()` returns a `save` even when `value === answer.main` and nothing changes, so each repeat
  tap is a POST and a `ihq_portal_onboarding_saved` action (which feeds the Braze sync). Suggested
  fix: return `{ answer, save: null }` when the value and sub-answer are unchanged, and update the
  `pickMain` test at line 251 accordingly (that test currently encodes the redundant save, so flag it
  as an intended behavior change).

- **N4 `css/portal-onboarding-questions.css:61` — new raw colour `#555` (and the `◆` glyph at `onboarding-screens-questions.js:214`) are unnamed literals.**
  The frame CSS keeps its palette as `--ihq-onb-*` tokens. Suggested fix: add a token such as
  `--ihq-onb-diamond-off` beside the others, and name the glyph as a constant
  (`DIAMOND_GLYPH`) next to `MAX_DIAMONDS`.

## Checks run

- `npm run test:js`: 332 pass, 0 fail.
- `npm run test:php`: all files pass, including `tests/portal-onboarding-progress.test.php` (the
  PR's unticked box for these can now be ticked by the author if they confirm on CI).
- Not run: ESLint / stylelint on the touched files (`npx wp-scripts` could not be installed: the
  package registry rejected the request with E401 in this environment); `npx tsc --noEmit` (no
  TypeScript and no `tsconfig.json` in the repo); mutation testing (no Stryker set-up in the repo);
  manual happy / failure path (no local site); design comparison (no Figma access).

## Outside this PR

- `js/aicoach/onboarding-engine.js:91` (`save()`, ENGR-7566) sends every partial concurrently and
  `inc/portal-onboarding-progress.php:376-379` (`ihq_portal_onboarding_merge()`, ENGR-7563) is an
  unlocked read-modify-write of one user meta, so any two overlapping saves (e.g. an answer and the
  `stage` sent by CONTINUE) can lose one. A save queue in the engine (or an atomic per-field merge
  on the server) fixes it for every screen, not only these four.
- The PR body reports that `npm run lint:js` cannot parse ES2020+ and fails on `main`; not verified
  here (lint tooling could not be installed).

## Summary

The module is clean, data-driven and well tested: the questions are declared once as data, the
pick/validation logic is pure and covered, the conditional reveal/require/clear behaviour matches
the ticket, and the enum values are pinned against the PHP store by test. No blockers. Two
warnings: answers saved per click can race in the unlocked store and be lost or left stale (W1),
and the visual match with the four frames, the ticket's first Done-when item, has not been checked
by anyone (W2). Recommended action: **revise** — serialize the saves and get the design check done,
then merge; the nits are optional.
