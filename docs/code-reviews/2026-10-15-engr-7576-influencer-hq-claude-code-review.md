---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-7576 onboarding clip preload (PR #132)
date: 2026-10-15
---

# Code Review: ENGR-7576 onboarding clip preload (PR #132)

Reviewed head: `b3795a8acf87417679f3f52bb7195688ddc1eb6d` against `main`, ticket ENGR-7576 (Jira body and comments read).

## Blockers

- `js/aicoach/onboarding-preload.js:29` — switching the language back does not preload again (Done-when 2: "A language switch preloads in the new language"). `requested` is a permanent Set, but `preloadClip` cancels the previous clip by removing its `src` and calling `load()`. On the same screen, en → ja → en: the `en` clip is cancelled when `ja` starts, then skipped on the switch back because its URL is already in `requested`. The `ja` clip keeps loading for a language the user has left. Reproduced with the PR's own `fake-dom.js`: after en→ja→en, `currentClip().getAttribute('src')` is `b-ja.mp4`. **Fix:** only skip a clip URL when it is the one currently loading (`clip && clip.getAttribute('src') === url`), or remove a URL from `requested` when its element is released. Add a test for en → ja → en that checks `currentClip()` is the `en` URL.

## Warnings

- `js/aicoach/onboarding-preload.js:70` — the preload starts as soon as the `screen` event fires. The engine emits that event right after `narrate()` starts the current clip (`onboarding-engine.js:128` on #124). On 4G, the next clip then competes with the current clip while it is still buffering, which can slow the very start-up that PO-3223 is trying to speed up. **Fix:** start the next-clip preload once the current clip can play through or has started playing (for example on the shell player's `canplaythrough`/`playing`). Or measure both orders in the planned lab run before choosing.
- `js/aicoach/onboarding-preload.js:41` — whether the preload works is unproven on the target devices. iOS Safari ignores `preload="auto"` on video, and Android Chrome may reduce it with Data Saver. Whether the player reuses the cache from a different element's range requests is assumed, not shown. The PR leaves the network-log check (Done-when 1) unticked. **Fix:** before closing the ticket, record a network log on iOS Safari and Android Chrome. It should show the next clip's request starting during the current screen, and the player then served from cache (or a 206 range response with no second full download).
- `js/aicoach/onboarding-preload.test.js:105` — "a malformed screen event does nothing" checks the wrong object. It builds a second preloader with `listenersApi`, throws away its return value, and then checks the untouched `preloader` from `setup()`. The test passes whatever the code does. **Fix:** `const malformed = createPreloader( … ); assert.deepEqual( malformed.requested(), [] );` and also check that no element was created.
- `js/aicoach/onboarding-preload.test.js:120` — the plug-in test greps the source with `assert.ok( addon.includes( … ) )`. That is a partial match, which the standards ban, and `install()` never runs. Reformatting would break the test even if the behaviour is unchanged, and a broken `install` could still pass. **Fix:** test the behaviour. For example, let `install( api, { document } )` accept its document, or map the `@ihq/aicoach/` specifier in the test run, then call `install` with a fake API and check that the listeners for `screen` and `locale` were registered.
- `docs/plans/2026-10-15-engr-7576-onboarding-clip-preload.plan.md:17` — Done-when 3 (the LCP/FCP baseline, its tool and settings recorded) is not delivered, and Done-when 1 is not verified. The PR says so plainly. Merging is fine, but ENGR-7576 cannot be closed on this PR alone. **Fix:** record the baseline on the ticket and PR before moving the ticket on, or split it into its own ticket.

## Nits

- `js/aicoach/onboarding-preload.js:21` — the `@return` JSDoc lists `{ preload, requested }`, but the function also returns `currentClip`, and `requested` is a function. Update the doc.
- `js/aicoach/onboarding-preload.js:70` — the event names `'screen'` and `'locale'` are magic strings shared with the engine and boot modules. A shared constant (for example `ONBOARDING_EVENTS.SCREEN`) would follow the standards' no-magic-strings rule.
- `js/aicoach/fake-dom.js:1` — this is a 314-line copy of #123's helper. It is identical today on #123 and #124 (checked), but any change to it there before this PR merges turns the copy into a conflict. Basing this PR on #124 once that merges would avoid the copy.

## Checks run

- `npm run test:js`: 197 pass, 0 fail.
- Reproduction of the blocker with a scratch Node script against the PR's own modules.
- API contract checked against #123/#124 heads (`clipUrl`, `imageUrl`, `'locale'` emit in `onboarding-boot.js`, `'screen'` `{ stage, next }` emit in `onboarding-engine.js`, `onboarding-*.js` discovery in `inc/portal-onboarding-shell.php`): it matches.
- Not run: ESLint (`npm ci` fails on registry auth in this environment, so `@wordpress/scripts` is unavailable). `npx tsc --noEmit` does not apply: the repo has no TypeScript (no `tsconfig.json`). Not run: Stryker, and any browser or network-log check.

## Summary

The change is small, isolated and inert until #123/#124 land, and its contract with the shell and engine matches. One real bug blocks it. The permanent `requested` set combined with cancelling the previous clip means that switching back to a language never preloads it again, and the wrong-language clip keeps loading. That fails Done-when 2 in an ordinary language toggle. Two tests are weak: one passes whatever the code does, and the other greps the source text. The preload also starts while the current clip is still buffering, which may cost more on 4G than it saves, and the mechanism is unproven on iOS Safari. The LCP/FCP baseline is still owed to the ticket. Recommendation: **revise**. Fix the switch-back dedupe and the two tests, then merge. Record the baseline and the network-log verification before closing ENGR-7576.
