---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-7571 onboarding closing screen (PR #128)
date: 2026-10-15
---

# Code Review: ENGR-7571 onboarding closing screen (PR #128)

Independent review of `feat/ENGR-7571-onboarding_closing_screen` against `feat/ENGR-7566-onboarding_screen_engine`
(head `323734f`), against the ticket ENGR-7571 (body and comments) and `.claude/rules/coding-standards.md`.

## Blockers

None.

## Warnings

- `js/aicoach/onboarding-screens-closing.js:53`: the screen builds a second `[data-ihq-save-home]` button at
  render time and hides the frame's own one (lines 29–32, restored in `destroy()`). ENGR-7575's spec says only that
  the button gets its behaviour, and that it hides in standalone mode and after a dismissal. Nothing there says it
  also handles buttons added to the DOM after page load. If ENGR-7575 binds the buttons it finds at load (the
  template comment says "binds every [data-ihq-save-home] button"), this button does nothing when tapped. Its hide
  rules also never apply to it, so an installed app would still show SAVE on the closing screen. That breaks the
  Done-when "SAVE IHQ TO HOME SCREEN runs the install behaviour from T13". It also duplicates the copy as
  `closingSave` next to the existing `saveToHomeScreen` key.
  **Fix:** in `renderClosing`, move the frame's existing Save button into `.ihq-onb-closing__actions`, and move it
  back in `destroy()`. Then drop `closingSave` and the hide/restore of `.ihq-onb__cta`. If two buttons must stay,
  write the contract on ENGR-7575 (delegated click handling on `document`, hide state applied to each matching
  element when it is created) and cover this button in that ticket's tests.

## Nits

- `js/aicoach/onboarding-screens-closing.js:37`: when the frame has no `.ihq-onb__portrait`, the screen still
  renders `<img src="">`, which shows as an empty gold ring. Skip the avatar when there is no portrait source.
- `css/portal-onboarding-closing.css:38`: `.ihq-onb__cta` sets no `display`, so the browser's own `[hidden]` rule
  already hides it and this rule is redundant. It is also a frame rule kept in a screen stylesheet. Remove it, or
  move it to `css/portal-onboarding.css` next to `.ihq-onb__button[hidden]`. The rule goes away anyway if the
  warning above is fixed by moving the button.
- `js/aicoach/onboarding-screens-closing.test.js:83` (and `:147`): `assert.ok` gives a truthy check where the
  standards ask for exact equality. Use `assert.deepEqual( buttons.map( ( button ) => button.getAttribute( 'type' ) ), [ 'button', 'button' ] )`.
  At `:147`, use `assert.notEqual( …, null )` or assert the button's text.

## Ticket coverage

- Enlarged, centred avatar, both message points, both buttons stacked: met (test "the screen shows…").
- CONTINUE marks onboarding complete and lands at the top of the Coach page: met through `api.complete()` →
  `POST completeUrl` → history guard lifted → `shell.close()` (`window.scrollTo( 0, 0 )`). It has a failure path
  with an inline alert and a retry, and a guard against a double submit.
- SAVE runs T13's install behaviour and leaves the user on the screen: "stays on the screen" is met. "Runs the
  install behaviour" depends on how ENGR-7575 binds the button (see the warning).
- Nothing beyond the ticket: no appointment screens (T16); new copy "Something went wrong. Please try again." is
  declared in the PR.

## Checks run

- `npm run test:js`: 318 pass, 0 fail.
- `npm run test:php`: all pass (includes the onboarding stylesheet media-query check; the new CSS has no media
  queries).
- Not run: `wp-scripts lint-js` / `lint-style`. `npm ci` failed on registry authentication in this environment, so
  `node_modules` is absent. `npx tsc --noEmit` does not apply (plain JS repo, no tsconfig). No manual browser run.

## Summary

A small, well-scoped plug-in that does what the ticket asks for CONTINUE, with a sound failure path and good tests.
The one real risk is the second, dynamically created Save button. Whether it works and hides correctly depends on
an ENGR-7575 contract that nobody has written down. Reusing the frame's button removes that risk and the
hide/restore code. Recommendation: revise (the warning), then merge.
