---
name: Extract AI Coach flow script out of page-home-aicoach.php
overview: >
  Steve flagged (PR #4 review nit) that page-home-aicoach.php was getting big. Moves the
  ~270-line coach-flow <script type="module"> block into js/aicoach-coach-flow.js, enqueued
  conditionally from inc/anam-proxy.php with wp_localize_script for its AICOACH_SAMI config —
  matching the js/ + functions.php pattern already used for ihq-elevenlabs-concierge.js. No
  behavior change.
todos:
  - id: extract-js
    content: Move the module script body verbatim into js/aicoach-coach-flow.js
    status: completed
  - id: enqueue
    content: Register/enqueue conditionally on page-home-aicoach.php only, localize AICOACH_SAMI, add a script_loader_tag filter for type="module"
    status: completed
  - id: cleanup
    content: Remove the now-dead $aicoach_sami_cfg variable and both inline <script> tags from the template
    status: completed
  - id: verify
    content: php -l both files, composer lint:php (repo-wide, only the known pre-existing page-influencer-hq-new.php failure), phpcs on touched files (0 new violations — all findings pre-existing/repo-wide)
    status: completed
---

# Extract AI Coach flow script

**Ticket:** review nit on https://github.com/avantageusa/influencer-hq/pull/4 (PO-3093), no separate Jira ticket
**Drafted by:** Claude Code (Sonnet 5)

## Problem

`page-home-aicoach.php` grew to 883 lines across PO-3092 + PO-3093, mostly from an inline
`<script type="module">` block. Steve approved PR #4 with a non-blocking nit that the file was
getting big and suggested breaking it down.

## Approach

1. Moved the entire module script body verbatim into `js/aicoach-coach-flow.js`, matching this
   theme's existing convention for standalone page scripts (e.g. `ihq-elevenlabs-concierge.js`).
2. Added `ihq_aicoach_enqueue_coach_flow()` in `inc/anam-proxy.php` (already the home for
   Anam/Sami-related wiring), hooked to `wp_enqueue_scripts`, gated on
   `is_page_template('page-home-aicoach.php')` — registers the script, `wp_localize_script`s the
   `AICOACH_SAMI` config (restBase + nonce, same values as before), then enqueues it.
3. Since the script uses `import` (ES module) and this theme has no first-class module-enqueue
   API, added a `script_loader_tag` filter scoped to just this one handle to add `type="module"`.
4. Removed the now-dead `$aicoach_sami_cfg` PHP variable and both inline `<script>` tags from the
   template — the config is built independently inside the new enqueue function instead.

## Alternatives considered

- **`wp_enqueue_script_module()`** (WP 6.5+ core API): cleaner, but risks assuming a WP core
  version this theme hasn't otherwise committed to; the `script_loader_tag` filter approach works
  on any WP version this theme already supports and mirrors zero new API surface.
- **Leave it inline:** rejected — that's the exact thing under review feedback.

## Blast radius

- `page-home-aicoach.php`: 883 → 611 lines. No functional change — same DOM ids/classes, same JS
  logic, same config values.
- `inc/anam-proxy.php`: additive only (two new functions, two new hooks). Existing Anam
  routes/proxy/suppression logic untouched.
- New file `js/aicoach-coach-flow.js`.

## Notes

- **phpcs findings are 100% pre-existing/repo-wide, not introduced here** — verified by running
  `./vendor/bin/phpcs` against an untouched sibling (`page-home-2.php`: 639 errors) and confirming
  `inc/anam-proxy.php`'s 5 errors all sit on lines untouched by this change (pre-existing `??`
  operator PHP-5.6-compat flags + two inline-comment-punctuation nits). My new code in both files:
  0 new violations.
- `composer lint:php` repo-wide still only fails on the known unrelated
  `page-influencer-hq-new.php:2319` syntax error (separate tracked issue, not this PR's).
