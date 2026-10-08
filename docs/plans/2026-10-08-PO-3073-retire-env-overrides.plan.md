---
name: PO-3073 — retire the per-user game-portal and start-session URL overrides
overview: >
  Before PO-3067 the environment was chosen per WordPress user: two user-meta values
  (hq_game_url, ihq_oauth_start_session_url) let an individual point their session at a
  different game portal or a different account-api. Environment is now a property of the
  instance, read from wp-config by inc/ihq-env.php, so those overrides are redundant and
  a stale value silently sends one user to the wrong environment. Remove both overrides,
  their admin and portal-profile UI, their AJAX and POST handlers, and collapse the two
  resolver functions to return the instance value. Out of scope: deleting the existing
  user-meta rows (harmless once nothing reads them), and the dev-tooling lockdown in
  PO-3286.
todos:
  - id: portal-base-resolver
    content: Collapse ihq_get_hq_game_portal_base_url() to return the configured instance portal, keeping its signature so the three callers are untouched
    status: pending
  - id: remove-admin-field
    content: Remove the wp-admin "Game Portal Settings" profile field, its save handler and the four add_action hooks from functions.php
    status: pending
  - id: remove-ajax
    content: Remove avantage_save_hq_game_url() and its wp_ajax_save_hq_game_url hook
    status: pending
  - id: start-session-resolver
    content: Collapse ihq_get_oauth_start_session_url_for_user() to the instance URL and drop ihq_oauth_start_session_url_meta_key()
    status: pending
  - id: profile-ui
    content: Remove both override forms and their POST handlers from page-portal-profile.php, keeping the read-only resolved-URL diagnostics
    status: pending
  - id: verify
    content: php -l on every touched file, grep proves no override references remain, tests/ihq-env.test.php still green
    status: pending
---

# [PO-3073] Retire the per-user environment overrides

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3073
**Epic:** https://avantageusa.atlassian.net/browse/PO-2913
**Drafted by:** Claude Code (claude-opus-5)

## Problem

Environment used to be selected per user. `hq_game_url` overrode the game portal base and
`ihq_oauth_start_session_url` overrode the account-api start-session endpoint, both stored
in user meta and editable from the portal profile page (and, for the portal URL, from
wp-admin). Since PO-3067 the environment is a property of the instance — dev, QA and prod
each read their own values from `wp-config.php`.

That makes the overrides worse than redundant. All three instances were cloned from
production, so they carry whatever override values existed at clone time. A user with a
stale `hq_game_url` on the QA instance is quietly sent to the QC portal, and the bug looks
like an environment problem rather than a data problem. PO-3067 already narrowed the
start-session override to the configured API origin, which blunted the credential-leak
risk but left the wrong-environment behaviour intact.

## Approach

Both resolver functions keep their names and signatures, so callers do not change. They
simply stop consulting user meta.

| Location | Change |
|---|---|
| `functions.php` `ihq_get_hq_game_portal_base_url()` | return `ihq_env_require_url( 'IHQ_GAME_PORTAL_BASE_URL' )`; `$user_id` kept for signature compatibility and ignored |
| `functions.php` `influencer_hq_game_url_profile_field()` + `influencer_hq_save_game_url_profile_field()` | removed, with their four `add_action` registrations |
| `functions.php` `avantage_save_hq_game_url()` | removed, with `wp_ajax_save_hq_game_url` |
| `inc/email-verification-handler.php` `ihq_get_oauth_start_session_url_for_user()` | returns `ihq_oauth_start_session_default_url()` |
| `inc/email-verification-handler.php` `ihq_oauth_start_session_url_meta_key()` | removed |
| `page-portal-profile.php` | both POST handlers and both override forms removed |

The three callers of the portal resolver (`functions.php` embed builder,
`template-parts/portal-header.php`, `test-form.php`) and the three callers of the
start-session resolver are left as they are.

`page-portal-profile.php` currently shows the resolved start-session URL as a diagnostic
alongside the editable field. The read-only diagnostic stays; only the form goes.

## Alternatives considered

- **Keep the overrides but ignore them when a wp-config value is set.** Leaves dead UI
  that appears to work, which is how the stale values arose in the first place.
- **Delete the user-meta rows as part of this change.** A destructive data migration for
  no behavioural gain once nothing reads them. If they ever need clearing, that is a
  one-line WP-CLI command, not theme code.
- **Keep the admin field as an ops escape hatch.** The escape hatch is wp-config, which is
  per instance and already the documented mechanism.

## Blast radius

- Any user currently relying on an override silently moves to their instance's configured
  environment. That is the intended correction, but it changes what those users see.
- The `wp_ajax_save_hq_game_url` endpoint disappears. Grep shows no JavaScript caller in
  the theme; a stale cached script posting to it would get WordPress's `0` response rather
  than an error.
- Stale `hq_game_url` and `ihq_oauth_start_session_url` meta rows remain in the database,
  read by nothing.
- `page-portal-profile.php` is heavily edited by other in-flight work; this touches only
  the two override blocks.

## Notes

- Deploy ordering is unchanged — nothing here depends on wp-config gaining new values.
- Related: PO-3067 (the config extraction this completes), ENGR-6662 (pipeline),
  PO-3286 (dev-tooling lockdown, separate).
