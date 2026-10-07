---
name: Make the AI Coach flow testable — extract pure logic from aicoach-coach-flow.js
overview: >
  js/aicoach-coach-flow.js is 3,185 lines and almost everything past line 858
  lives in one `if ( stage && avatarWrap )` block, so none of its logic can be
  tested without a browser, a live Gary session and a Chrome tab. Recent bugs
  (ENGR-7051 and its follow-ups) were all in small pieces of timing and state
  logic that were only verified by hand. The plan adds a dependency-free JS test
  runner, extracts three small pieces of pure logic into ES modules with
  injected timers (sequence hold, stall watchdog, idle derivation) and covers
  them with characterization tests, then moves the static data (translations,
  screen definitions) into its own modules. Out of scope: splitting
  buildAskSami(), changing any runtime behaviour, adding Stryker or any other
  new dependency (both are called out as separate decisions).
todos:
  - id: loader-decision
    content: Production runs WordPress 7.1.2 (and wp-env the same), so WP script modules are used; proven on wp-env that the import map needs a carrier module with the three modules as dependencies (enqueueing them alone prints no map)
    status: completed
  - id: test-runner
    content: Add `npm run test:js` using the built-in `node --test` (no new dependency) and a js/aicoach/package.json with "type":"module" so Node treats the extracted files as ESM
    status: completed
  - id: lint-glob
    content: Widen the `lint:js` glob in package.json (currently 'js/*.js') so it also covers js/aicoach/*.js
    status: completed
  - id: ci-decision
    content: Add a GitHub Action that runs `npm run test:js` and the PHP suites on PRs (approved by Steve on the ticket, 2026-10-06)
    status: completed
  - id: characterization-tests
    content: Write tests for the pausable timers / hold, the fallback-audio stall watchdog and the idle derivation against the behaviour of the code being extracted
    status: completed
  - id: extract-sequence-hold
    content: Extract createPausableTimeout / pause / resume / waitWhileSequenceHeld into js/aicoach/sequence-hold.js as createSequenceHold({ setTimeout, clearTimeout, now })
    status: completed
  - id: extract-stall-watchdog
    content: Extract the fallback-audio stall watchdog and idempotent finish into js/aicoach/stall-watchdog.js
    status: completed
  - id: extract-idle-state
    content: Extract the is-idle derivation into js/aicoach/idle-state.js as a pure isAvatarIdle({ panelOpen, answerVideoActive })
    status: completed
  - id: wire-and-regress
    content: Switch aicoach-coach-flow.js to import the three modules with thin local aliases, so call sites do not change, then repeat the ENGR-7051 manual scenarios against wp-env
    status: completed
  - id: split-data
    content: Second PR - move SUPPORTED_LOCALES / I18N_TRANSLATIONS / t() and the SCREENS / EQUITY / COMPETITION / CHANNELS definitions into their own modules; no behaviour change
    status: completed
  - id: mutation-gate
    content: Stryker is deferred (Steve, 2026-10-06); until then manual mutation checks on the extracted modules, recorded in the PR
    status: blocked
  - id: verify
    content: Run tests, lint, and CI gate before merge
    status: pending
---

# [ENGR-7066] Make the AI Coach flow testable

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7066
**Drafted by:** Claude (claude-sonnet-5-5)

## Problem
`js/aicoach-coach-flow.js` holds the whole AI Coach experience in one file and,
past the constants and i18n tables, in one closure: sequence control, the live
intro, Anam, Ask Sami (`buildAskSami()`), progress. It has no tests. The coding
standards ask for isolation plus mutation testing, but there is no JS test
runner and no `npm run stryker`, so every change is verified by hand in Chrome
against a real Gary/Anam session. That is slow, depends on Anam seat
availability, and leaves no protection for the next person who touches the
timing logic.

## Where the 3,185 lines go
| Region (approx. lines) | What it is | Testable as-is? |
|---|---|---|
| 81–160 | pausable timers, sequence hold | Almost: pure logic, but wired to `window` timers and module globals |
| 170–510 | `SUPPORTED_LOCALES`, `I18N_*`, `t()` | Pure data and a pure lookup |
| 564–680 | `SCREENS`, `EQUITY_SCREENS`, `COMPETITION_SCREENS`, `CHANNELS` | Pure data and small getters |
| 693–850 | pre-rendered URL / caption lookup, progress | Mixed (`cfg`, `fetch`) |
| 858–end | one `if ( stage && avatarWrap ) { … }` block incl. `buildAskSami()` | No |

## Approach
Order matters: tests first, then extraction, so the extraction is provably a
no-op.

1. **Loader (blocks everything).** Today one `<script type="module">` is
   versioned with `filemtime` (`inc/anam-proxy.php`, `ihq_aicoach_enqueue_coach_flow`).
   Files it `import`s would be fetched by bare relative URL with no `?ver=`, so
   after a deploy a visitor (or a CDN) could pair a new entry file with a
   stale cached module. Production runs WordPress 7.1.2 (checked by the dev;
   wp-env runs the same), so the WordPress script modules API is used. The
   entry script stays a classic script handle: it depends on the classic
   `ihq-aicoach-events` script and on `AICOACH_SAMI` from
   `wp_localize_script`, and the `script_loader_tag` filter already makes it a
   module. **Finding from the proof on wp-env:** enqueueing the three modules is
   not enough: WordPress prints their `<script type="module">` tags but no
   import map, because a module only enters the map as a *static dependency of
   another enqueued module*. So `inc/aicoach-modules.php` registers the three
   modules and enqueues one empty carrier module (`js/aicoach/index.js`) that
   lists them as dependencies. The map WordPress then prints
   (`#wp-importmap`, before the first module script) maps
   `@ihq/aicoach/<name>` to `...?ver=<filemtime>`, plus `modulepreload` links.
   The carrier must not import the modules itself (a relative import would
   load unversioned duplicates). No PHP-printed import map was needed. The PHP
   side has `tests/aicoach-modules.test.php`.
2. **Test runner, no new dependency.** `"test:js": "node --test js/aicoach/*.test.js"`
   using `node:test`; the tests inject timers and the clock into the modules,
   so they use their own deterministic `js/aicoach/fake-clock.js` (time moves
   only on `tick()`) instead of `mock.timers`. The
   extracted files stay `.js` so the browser serves them with the right MIME
   type; a `js/aicoach/package.json` containing `{ "type": "module" }` makes
   Node load them as ESM on every Node version without touching the root
   `package.json` (the root one is the underscores starter's and also serves
   the SCSS build). `lint:js` is currently `wp-scripts lint-js 'js/*.js'`, which
   does not reach a subdirectory, so the glob is widened in the same change or
   the new modules would ship unlinted.
3. **Extract three pieces, each a factory with injected collaborators**
   (dependency inversion, per the standards):
   - `sequence-hold.js` — `createSequenceHold( { setTimeout, clearTimeout, now } )`
     returns `{ createPausableTimeout, pause, resume, waitWhileHeld, isPaused }`.
     Owns `pausableSequenceTimers`, `sequenceTimersPaused` and
     `sequenceHoldWaiters`, which stop being module globals. Pins the
     behaviour the comments call out: remaining time survives pause/resume; a
     timer created while paused is not armed (PR #63); `cancel()` removes it;
     `waitWhileHeld()` resolves at once when not paused and every waiter is
     released together by `resume()` (ENGR-7051).
   - `stall-watchdog.js` — `createStallWatchdog( { stallMs, onStall, setTimeout, clearTimeout } )`
     returns `{ arm, disarm }`, plus a small `once( fn )` guard for the
     idempotent `finish()`. Pins: re-arming on progress resets the clock;
     it fires only after `stallMs` without progress; `disarm()` cancels; the
     guarded finish runs once however it is reached (watchdog and the
     `AbortError` from `pause()`).
   - `idle-state.js` — `isAvatarIdle( { panelOpen, answerVideoActive } )`, all
     four combinations.
4. **Wire back with thin aliases.** `aicoach-coach-flow.js` builds one
   `createSequenceHold( … )` with the real `window` timers and `Date.now`, and
   keeps the local names (`createPausableTimeout`, `pauseSequenceTimers`,
   `resumeSequenceTimers`, `waitWhileSequenceHeld`) bound to it, so none of the
   ~20 call sites change in this PR. `playFallbackAudio()` keeps its DOM
   wiring and delegates only the timing/idempotency to the watchdog.
5. **Characterization before refactor.** Tests are first written against the
   existing logic as it is (copied into the module unchanged) and must pass
   before any call site is switched. If a test reveals that current behaviour
   is wrong, that is flagged to the dev as a separate finding, not corrected
   inside this refactor.
6. **Second PR — static data.** Translations and screen definitions move to
   `js/aicoach/locales.js` and `js/aicoach/screens.js`. `t()` and the
   `get*ScreensForTier()` getters get table-driven tests (every locale has
   every key the English table has, fallback to English, unknown tier). The
   locale-completeness test is the useful part: it would have caught missing
   translations before QA does. `applyLocale()` stays where it is (DOM).
7. **Not in this plan: `buildAskSami()`.** It shares mutable state
   (`qaAnswering`, `qaClient`, `qaVideoActive`, `wasPlaying`, the avatar
   `status`) across many handlers, and splitting it means first deciding who
   owns that state. That needs its own plan, written after the tests above
   exist.

## Alternatives considered
- **Playwright / browser end-to-end tests.** Real coverage of the hand-off
  scenarios, but needs a live Gary key and Anam seats (the very thing that
  blocked my last live verification), a new dependency and CI that this repo
  does not have in-tree. Worth a separate look; it complements rather than
  replaces unit tests of the timing logic.
- **Bundler (esbuild/Vite) producing one file.** Solves caching with no PHP
  change but adds a build step and a committed or CI-built artefact to a
  theme that currently ships source as-is.
- **Jest/Vitest.** Heavier than needed; `node:test` covers fake timers and
  assertions with zero install.
- **Extract everything at once, including buildAskSami().** Largest payoff,
  largest risk, and no safety net yet. Rejected for the first pass.
- **Do nothing and keep verifying by hand.** Every ENGR-7051 round trip cost
  a live session; the three pieces above are exactly where the regressions
  came from.

## Blast radius
Every visitor of the AI Coach page: it is one module graph, so a failed or
mis-cached import stops the whole script (the page falls back to what the PHP
template renders, with no sequence). That is the main risk and the reason the
loader decision comes first and PR 1 is limited to three small modules with
unchanged call sites. Also touched: `inc/anam-proxy.php` (enqueue / import
map), `package.json` (one script, no dependency), a new `js/aicoach/`
directory. Not touched: Gary/Anam integration, REST routes, progress records,
CSS. Runtime behaviour must be identical; the `.eslintrc` globs and any
deploy step that copies only `js/*.js` (not subdirectories) need checking.

## Verification (PR 1, done)
- `npm run test:js`: 24 tests in 3 files (`sequence-hold`, `stall-watchdog`,
  `idle-state`) pass; `npm run test:php`: all 11 suites pass, including the new
  `tests/aicoach-modules.test.php` (10 checks). Assertions use deep equality on
  return values and recorded side effects (timers armed and cleared, promises
  released).
- Manual mutation checks (Stryker is deferred): 17 mutations across the three
  modules (flipped sign of the remaining time, timer armed while paused,
  `cancel` leaving the handle registered, double-armed `resume`, waiters never
  released, `waitWhileHeld` inverted, fired timer left registered, watchdog not
  restarting / off by one / not cancelled by `disarm`, `once` never marking
  called or losing `this`, `&&` to `||`, un-negated answer flag, no boolean
  coercion): 16 killed. The survivor, "waiters not cleared", is an equivalent
  mutant: resolving an already-resolved promise again has no observable effect.
- Loader (wp-env, WordPress 7.1.2): the page prints `#wp-importmap` before the
  first module script with the three `@ihq/aicoach/*` specifiers mapped to
  `...?ver=<filemtime>`, plus `modulepreload` links; the entry script runs
  (Gary session opens, Ask Sami enabled), each module is requested exactly once
  with its versioned URL, no console errors.
- Live regression on wp-env (visible Browser pane, real Gary/Anam session),
  same scenarios as ENGR-7051: intro hands off to `believe-1`, clip plays; Ask
  Sami opened mid-clip holds the clip at the same position for 4 s with idle on
  and resumes from there (+1 s) with idle off; Ask Sami opened during the intro
  and a fallback audio that stalls (no `ended`/`error`): the sequence stays
  held on `intro` with the mic disabled for 13 s after the last progress, then
  at 15 s `audio.pause()` is called once, the mic comes back and
  `we_believe_1.mp4` starts at 0.1 s.
- Not verified: ESLint on `js/aicoach/*.js` (dependencies are not installed in
  this checkout, and `node-sass` does not build on Node 24); the GitHub Action
  itself (YAML parsed and `npm run test:js` / `test:php` run locally, but not
  executed on GitHub); a cold-cache load and a reload after touching one module
  (only the first load was checked); real Safari/Firefox with the import map.

## PR 2 (data modules) - done on branch feat/ENGR-7066-coach_flow_data_modules
Stacked on PR 1 (#98), which provides the runner, the loader and the carrier
module. `js/aicoach-coach-flow.js` goes from 3,185 to 2,689 lines.
- **`js/aicoach/locales.js`**: `SUPPORTED_LOCALES`, `I18N_EN`,
  `I18N_TRANSLATIONS` and `t()`, moved verbatim (a pure move: the 4-space
  indentation of the original is kept so the diff stays a move), plus
  `detectLocale( browserTags )`: the matching half of `detectInitialLocale()`,
  which used to read `navigator` itself. The flow keeps a six-line
  `detectInitialLocale()` that reads `navigator.languages` and calls it.
  `applyLocale()` stays in the flow (DOM).
- **`js/aicoach/screens.js`**: `SCREENS` (exported as the same array the flow
  appends to with `SCREENS.push`), `EQUITY_SCREENS`, `COMPETITION_SCREENS`,
  `COMM_CHANNELS_SCREEN`, `FINAL_SCREEN`, `CHANNELS`, `isValidPhoneNumber` and the
  two `get*ScreensForTier()` getters, moved verbatim.
- **Registration**: `locales` and `screens` added to
  `ihq_aicoach_script_module_names()`; `tests/aicoach-modules.test.php` updated
  (it also still checks that the entry imports exactly the registered modules).
- **Tests (49 new, 73 in all)**: language list and labels; every table belongs
  to a supported language and no table has a key English lacks (catches a typo
  in a key name); `t()` fallbacks; `detectLocale()` over 23 browser-tag cases
  including `zh-HK`/`zh-MO`/`zh-Hant-HK` to Cantonese; tier-to-screens selection
  and ordering, fresh arrays per call; E.164 phone boundaries, e-mail, Line ID
  and the four TBD channels.
- **Finding: the translations are far from complete.** The planned check, "every
  language has every key English has", cannot pass today: each of the six other
  languages lacks 30 to 32 of English's 65 keys (Japanese also `belief` and
  `weBelieve`; Korean also `firstName` and `lastName`, deliberately held back
  per the comment in the file), and `t()` shows English for them. Inventing
  copy is not an option, so the test pins the exact known gaps per language: a
  new gap, or a gap that gets filled, changes the test and cannot go unnoticed.
  The gaps themselves are a product/translation question.
- **Mutation checks** (Stryker deferred): 24 mutations across the two modules
  (fallbacks in `t()`, each branch of `detectLocale()`, tier comparisons and
  ordering, phone/e-mail/Line patterns and bounds, TBD channels, a removed
  locale), 23 killed. The survivor, "yue primary", is an equivalent mutant: the
  early `yue` return is redundant with the supported-language check that
  follows it.
- **Live (wp-env, resume at `home`)**: import map lists the five modules, each
  requested once with its versioned URL, no console errors; the selector shows
  the seven languages; switching to Mandarin translates static text
  (`2 分钟`) and falls back to English for a missing key (`Yes`), and back to
  English restores it; the 5-minute tier queues `equity-bts` then
  `competition-world`, `competition-community`, `competition-private` in order.
- **Not verified**: ESLint (not installed), automatic language detection in a
  real browser with a non-English language setting (covered by the unit tests
  only), the identity/channels/final screens after the queue (not walked
  through), and a cold-cache reload.

## Notes
- **Open questions for the dev:** (1) ~~production WordPress version~~ answered: 7.1.2;
  (2) ~~GitHub Action for the JS and PHP tests~~ approved by Steve on the ticket
  (2026-10-06); (3) ~~Stryker~~ deferred by Steve (manual mutation checks until
  then).
- **Minimum WordPress version (review, CodeRabbit).** Script modules need
  WordPress 6.5+; the theme declared `Requires at least: 4.5` (a leftover from
  the underscores starter) and production runs 7.1.2. `readme.txt` and
  `style.css` now declare `Requires at least: 6.5`. A `function_exists()` guard
  was not added: without the import map the entry script cannot load its
  imports, so the page would not work either way. This supersedes the
  "`wp_enqueue_script_module()` risks assuming a WP core version" alternative
  in `2026-08-28-extract-aicoach-coach-flow-js.plan.md`. `Tested up to` was
  5.4 (also a starter leftover), which left the minimum above the tested
  version; it is now 7.1, the version production and dev run the whole theme
  on (7.1.2), as decided by the dev on 2026-10-07.
- Suggested split: PR 1 = runner + loader + three modules; PR 2 = data
  modules; a later plan = `buildAskSami()`.
- **CI findings (checked 2026-10-06):** the only GitHub Actions workflow in the
  repo is Dependabot, the only check on a PR is CodeRabbit, and there is no
  CircleCI/GitLab/Jenkins/husky configuration. Branch-protection rules for
  `main` could not be read (404, so either none or no permission). Nothing in
  this repo runs the existing PHP suites either, so `npm run test:js` would be
  a manual gate until a workflow exists. Deploy tooling may live in another
  repo and is not visible from here.
- Related plans: `2026-10-05-ENGR-7051-ask-sami-idle-animation.plan.md` (the
  logic being extracted), `2026-10-02-time-selection-translations.plan.md`
  (i18n tables).
- Other findings from the same review that are separate tickets, not part of
  this plan: the never-stopped intro Anam client (holds a concurrency seat),
  the frozen last frame on identity / channels / final screens, and ignoring
  `.wp-env.json`, `composer.lock`, `vendor/`.
