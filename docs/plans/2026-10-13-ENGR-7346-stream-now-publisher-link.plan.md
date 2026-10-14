---
name: ENGR-7346 — STREAM NOW opens the publisher in a new tab, already signed in
overview: >
  STREAM NOW on the Live Appearance page is a dead button. Turn it into a link to this instance's
  publisher app, carrying the influencer's stored SSO code the same way PLAY carries it to the game.
  The publisher URL comes from a new optional wp-config constant, IHQ_PUBLISHER_URL. When the
  constant is unset or not https, or when nobody is logged in, the element is not rendered at all.
  Phones (max-width: 767px) do not get it. Out of scope: minting a fresh code per click (that waits
  on ENGR-7033), the publisher itself, joint streams (ENGR-6558), a contest-type picker, analytics,
  and setting the constant on the WP Engine instances (DEPLOYMENT NOTE, ops).
todos:
  - id: branch
    content: Create feat/ENGR-7346-stream_now_link from an up-to-date origin/main, and copy this plan to docs/plans/2026-10-13-ENGR-7346-stream-now-publisher-link.plan.md
    status: completed
  - id: test-first
    content: Write tests/ihq-stream-now.test.php (cases below) and confirm it fails before the module exists
    status: completed
  - id: module
    content: Add inc/ihq-stream-now.php with ihq_get_stream_now_url( $user_id ), and require it in functions.php next to the other ihq-* modules
    status: completed
  - id: template
    content: page-portal-live.php:368 — replace the dead <button> with a conditional <a class="live-stream-now" target="_blank" rel="noopener noreferrer">
    status: completed
  - id: styles
    content: "template-parts/portal-styles.php:4399 — cursor: pointer, text-decoration: none, text-align: center, a :focus-visible outline, then a max-width: 767px display:none block placed after the base rule"
    status: completed
  - id: docs
    content: Document IHQ_PUBLISHER_URL in the inc/ihq-env.php header, in the README wp-config block, sentence and instance table, and in .wp-env.example.json
    status: completed
  - id: verify
    content: "npm run test:php (15 files, exit 0) and npm run test:js (183/183) green; php -l clean on touched PHP; manual mutation pass 8/8 killed. wp-env 2026-10-13: logged-out has no element (AC6), logged-in href + new tab correct, PLAY and STREAM NOW carry the same dummy hqSsoCode (AC3), 767px none / 768px block (AC7), box identical to the old button. Dev browser pass 2026-10-14: AC3, AC4, AC5, AC6, AC7 confirmed. AC1, AC2, AC8 need a real SSO code and run on QC"
    status: completed
---

# [ENGR-7346] STREAM NOW opens the publisher in a new tab, already signed in

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7346
**Epic:** https://avantageusa.atlassian.net/browse/ENGR-6505 · [evaluation page](https://avantageusa.atlassian.net/wiki/spaces/AE/pages/5039587331), which the tickets now supersede
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem

On Live Appearance, `<button type="button" class="live-stream-now">` (`page-portal-live.php:368`) has
no handler, and its style sets `cursor: default`. Influencers need STREAM NOW to open the publisher
app in a new tab, signed in. The publisher already redeems `hqSsoCode`, signs the influencer in and
gates streaming (eligibility, bans, one live session at a time), so IHQ only has to provide the link.

## Approach

**Reuse PLAY's mechanism.** PLAY (`template-parts/portal-header.php:79-115`) builds its link from
`ihq_get_hq_sso_code_for_user( $uid )` (`inc/email-verification-handler.php:1613`) and adds
`hqSsoCode` only when the code is non-empty, using `add_query_arg`. It renders only when the user is
logged in and opens with `target="_blank" rel="noopener noreferrer"`. STREAM NOW does the same thing,
so AC3 (same code as PLAY on the same page load) holds by construction.

**New module `inc/ihq-stream-now.php`.** It holds one function, `ihq_get_stream_now_url( $user_id )`,
which returns either the href or `''`:
- If `$user_id <= 0`, it returns `''`. A logged-out visitor has user id 0, so this is the same test as
  `is_user_logged_in()`, but it can be unit-tested (AC6).
- If `ihq_env_get( 'IHQ_PUBLISHER_URL' )` returns null, it returns `''` (AC5, unset).
- If the value is set but `ihq_env_is_https_url()` rejects it, it writes
  `error_log( '[ihq-stream-now] … STREAM NOW is hidden.' )` and returns `''` (AC5, invalid). Without
  the log line, an operator who sets an `http://` value would get a missing button and no clue why.
- If the SSO code is `''`, it returns the publisher URL unchanged, with no `hqSsoCode` (AC4).
- Otherwise it returns `add_query_arg( array( 'hqSsoCode' => $code ), $publisher_url )`.
- It keeps the configured URL as is. It does not call `untrailingslashit`, because nothing is
  appended to the path.

The module reads the constant through the existing `ihq_env_get()` / `ihq_env_is_https_url()`. That
is the same way the other optional keys are read (`inc/turnstile-verify.php:17`,
`functions.php:1082`). It adds no new config mechanism.

The function lives in its own `inc/` file instead of `functions.php` or the template so that a test
can load it without WordPress. This follows the pattern of `inc/ihq-url-minify.php` and
`tests/ihq-url-minify.test.php`. The doc block names ENGR-7033: if SSO codes become single-use, PLAY
and STREAM NOW must change together.

**`IHQ_PUBLISHER_URL` stays out of `IHQ_ENV_REQUIRED_KEYS` and `IHQ_ENV_URL_KEYS`.** On any key in
either list, `ihq_env_problems()` stops the whole site (`wp_die`) when the key is missing or invalid.
An optional key has to fail soft. The new test checks this.

**Template (`page-portal-live.php:368`).**
```php
<?php $la_stream_now_url = ihq_get_stream_now_url( get_current_user_id() ); ?>
<?php if ( $la_stream_now_url !== '' ) : ?>
    <a class="live-stream-now" href="<?php echo esc_url( $la_stream_now_url ); ?>" target="_blank" rel="noopener noreferrer">STREAM NOW</a>
<?php endif; ?>
```
The click makes no request, so a pop-up blocker has nothing to block. No page JS references
`.live-stream-now` or selects form buttons generically, so changing `<button>` to `<a>` affects no
script.

**Styles (`template-parts/portal-styles.php:4399`).** Bootstrap 5.3's reboot underlines every `a`,
and an `<a>` does not center its text the way a `<button>` does. To keep the button looking the same,
the plan:
- adds `text-decoration: none` and `text-align: center`
- changes `cursor` from `default` to `pointer`
- adds a `:focus-visible` outline: 2px solid #fff with an offset, which shows against the dark page.

To hide the link on phones, it adds
`@media (max-width: 767px) { body.page-template-page-portal-live-php .live-stream-now { display: none; } }`.
This uses the Live page's existing breakpoint; no new one is added. **The block must come after the
base rule.** The nearest existing Live 767 block (line 4262) comes before the base rule, and because
both rules have the same specificity, the later `display: block` would win.

**Docs.**
- `inc/ihq-env.php` header: list the constant under "Optional constants (feature is off when absent)".
- README: add the `define()` line in the optional block, and add a sentence saying it must be https
  but that an invalid value only hides the button. Add an `IHQ_PUBLISHER_URL` column to the instance
  table:
  - dev: `https://stream-dev.influencerhq.co`
  - qa: `https://stream-qa.influencerhq.co`
  - prod: unset

  Below the table, add a note: set a stream host only after it serves a valid certificate and that
  stage's game allows it to frame (ENGR-7349/7350/7351). Until then, dev uses
  `https://qc-influencer-publisher-tf.dev.ae.games`, qa uses its current publisher host, and prod stays
  unset, which acts as the kill switch.
- `.wp-env.example.json`: add the dev publisher host, so a local copy shows the button.

**Tests (`tests/ihq-stream-now.test.php`).** The test follows `tests/ihq-url-minify.test.php`: no
WordPress, stubbed WP functions, and a strict `check( $label, $actual, $expected )`. The stubs for
`add_query_arg` and `ihq_get_hq_sso_code_for_user` record their calls, so every case asserts both the
return value and the side effects:
- user 0 or a negative id → `''`, and neither the SSO helper nor `add_query_arg` is called
- constant unset, or set to an empty string → `''`
- `http://`, no host, or garbage → `''`, and the log names `IHQ_PUBLISHER_URL`
- valid URL with a code → URL plus `hqSsoCode`, the helper is called with the user id, and
  `add_query_arg` receives the exact arguments
- valid URL with an empty code → the URL unchanged, and `add_query_arg` is not called
- a configured path or trailing slash is passed through unchanged
- the constant is read ahead of the environment variable
- an `http://` `IHQ_PUBLISHER_URL` leaves `ihq_env_problems()` empty, so the site still renders

## Alternatives considered

- **Build the link inline in the template, as PLAY does.** Rejected: the AC5/AC6/AC4 logic could not be
  unit-tested.
- **Put the builder in `functions.php` next to `ihq_build_hq_game_portal_external_url`.** Rejected:
  `functions.php` cannot be loaded in an isolated test.
- **Add `ihq_env_publisher_url()` to `inc/ihq-env.php`.** Rejected: other optional keys are read in the
  module that uses them, and one call site does not justify a second accessor.
- **Make `IHQ_PUBLISHER_URL` required or a URL key.** Rejected: the ticket forbids it, because a
  missing value would stop the whole site on any instance without a publisher.
- **Render the button disabled when it is not configured.** Rejected: AC5 says the element is absent.
- **A theme endpoint that mints a fresh code per click.** Deferred: only needed if ENGR-7033 makes codes
  single-use.

## Blast radius

- **Live Appearance only.** The CSS selector is scoped to `body.page-template-page-portal-live-php`,
  and only `page-portal-live.php` calls the new function.
- **`functions.php` gets one `require_once`.** A fatal error in the new file would take down every
  page. This is covered by `php -l` and by loading the site locally.
- **Safe default on deploy.** Until ops sets `IHQ_PUBLISHER_URL` in wp-config, the button simply
  disappears on that instance: today's dead button goes away and nothing else changes. Prod stays
  unset by design.
- **AC8 (unchanged).** PLAY, the scheduled-appearance form and the publisher are not edited. The form's
  SUBMIT, CANCEL and ADD ANOTHER keep their markup and handlers.
- **ENGR-7033 dependency.** The link reuses today's 24-hour reusable code, as PLAY does. If ENGR-7033
  makes codes single-use, both links break together and must be fixed together.

## Notes

- **OQ10 (tablets).** The ticket assumes "phones" means ≤767px, so tablets keep the button. Steve
  Wolfe can correct that.
- **OQ9.** Accepting the third-party-cookie limitation is a launch sign-off question. It does not
  affect this code.
- **No mutation tooling for PHP here** (no Stryker or Infection). Instead, the plan includes a manual
  mutation pass: temporarily break each guard (user id, null check, https check, empty-code check) and
  confirm a test fails. All of these breaks are reverted.
- **Local smoke test.** Add `IHQ_PUBLISHER_URL` to the local `.wp-env.json` (gitignored, never
  committed) and restart with `npx @wordpress/env start`. Locally `IHQ_INFLUENCER_API_KEY` is a
  placeholder, so there is no stored code and no `hqSsoCode` unless a dummy `ihq_sso_code` meta is
  set for the check.

## Verification

1. **Tests and lint.** Run `npm run test:php`, which runs every `tests/*.test.php`, including the new
   one. Run `npm run test:js`, which is unaffected but run as requested. Run `php -l` on
   `inc/ihq-stream-now.php`, `functions.php`, `page-portal-live.php` and
   `template-parts/portal-styles.php`. phpcs is not installed (`vendor/` is absent), so `php -l` is the
   available PHP lint. PHP 8.5 is installed locally, so nothing needs installing.
2. **Mutation pass.** Run it as described in Notes.
3. **wp-env wiring (I run these).**
   - `npx @wordpress/env run cli wp eval` with `ihq_get_stream_now_url( <testinfluencer id> )`.
     Expected: the publisher URL, without `hqSsoCode`.
   - Fetch `/portal-live/` logged out with curl. Expected: no `live-stream-now` in the HTML (AC6).
4. **Your browser checklist at http://localhost:8888/portal-live/ as testinfluencer:**
   - **AC3:** set a dummy code with `npx @wordpress/env run cli wp user meta update <id> ihq_sso_code test-code-7346`,
     reload, and confirm that PLAY and STREAM NOW carry the same `hqSsoCode`. Delete the meta afterwards.
   - **AC4:** without the meta, STREAM NOW has no `hqSsoCode`, and clicking it opens the publisher
     sign-in screen in a new tab while the IHQ tab stays on Live.
   - **AC5:** remove the constant from `.wp-env.json` (or set it to `http://…`) and restart. Expected:
     no STREAM NOW in the DOM, and the page is otherwise identical.
   - **AC6:** log out. Expected: no STREAM NOW in the DOM.
   - **AC7:** in DevTools at 767px the button is hidden; at 768px it is shown. Also check that it looks
     the same as before (no underline, centered text), that the cursor is a pointer, and that Tab
     focus shows the outline.
