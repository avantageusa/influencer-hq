---
name: PO-3067 + PO-3066 — extract environment config out of the theme, rotate committed credentials
overview: >
  The influencer-hq theme hardcodes the QC API Gateway, the QC game portal and four live
  credentials in source, so every WP Engine instance running it talks to QC with the same key.
  Introduce one config module (inc/ihq-env.php) that reads every environment-specific value
  from wp-config.php constants with a getenv() fallback and fails loudly when a required value
  is missing, replace every hardcoded literal with a lookup, and remove the credential literals
  from the repo. Rotation of the leaked credentials (QC x-api-key, Turnstile pair, ElevenLabs)
  is the second half and happens after the code lands on all three instances. Out of scope:
  retiring the per-user hq_game_url / start-session overrides (PO-3073 part 2, separate PR),
  removing or gating the debug pages and the ihq_api_proxy relay, git history rewrite, and the
  CI/CD pipeline (ENGR-6662).
todos:
  - id: env-module
    content: Add inc/ihq-env.php — ihq_env_get()/ihq_env_require() readers (constant → getenv → error), constants list, admin notice + wp_die on missing required keys, require it first in functions.php
    status: pending
  - id: replace-api-base
    content: Replace the three duplicate API-base literals (inc/api-ajax-calls.php:81, functions.php:1020, page-portal-testapi.php:127) and the start-session URL (inc/email-verification-handler.php:1079) with lookups; INFLUENCER_API_BASE becomes derived from config
    status: pending
  - id: replace-portal-base
    content: "Replace the QC portal literals (functions.php:317, template-parts/portal-header.php:61, test-form.php:14) with the config lookup; portal-header uses ihq_get_hq_game_portal_base_url(). page-portal-challenges.php:28 is already replaced by PR #24 (PO-2901) — do not touch, rebase after it merges. page-portal-profile.php:619 waits for PR #21 (PO-3061, 1260-line rewrite of that file) — edit after it merges" 
    status: pending
  - id: remove-secret-literals
    content: Delete the define() fallbacks for IHQ_INFLUENCER_API_KEY, CF_TURNSTILE_SITE_KEY, CF_TURNSTILE_SECRET_KEY and the inline ElevenLabs key; read all four via the env module
    status: pending
  - id: docs
    content: Document the wp-config block per instance (README + docs/knowledge/GOTCHAS.md) with the three real value sets; add wp-config.example.php snippet
    status: pending
  - id: verify
    content: php -l on every touched file, wp-env smoke with and without the constants (must fail loudly), lint:js unaffected
    status: pending
  - id: wp-config-all-envs
    content: "Ops: write the wp-config block on dev, QA and PROD WPE envs BEFORE the theme deploys (values table in Approach); add both wpenginepowered hostnames to the Turnstile widget"
    status: blocked
  - id: rotate-qc-api-key
    content: "Ops: after deploy, rotate the QC key via terraform -replace on account-api-tf dev_qc (aws_api_gateway_api_key.influencer_hq_sso), then update the dev env wp-config from SSM"
    status: blocked
  - id: rotate-turnstile-elevenlabs
    content: "Ops (NeedsHuman): rotate Turnstile secret in Cloudflare and the ElevenLabs API key; update all three wp-configs"
    status: blocked
---

# [PO-3067] Extract environment config out of the theme / [PO-3066] Rotate committed credentials

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3067 · https://avantageusa.atlassian.net/browse/PO-3066
**Epic:** https://avantageusa.atlassian.net/browse/PO-2913
**Drafted by:** Claude Code (claude-fable-5-1)

## Problem

Three WP Engine environments now exist (dev, QA, and the live influencerhq.co) but the theme is
hardwired to QC: the API base is defined in three places, the game portal base in five, and the
`x-api-key`, both Turnstile keys and an ElevenLabs key are `define()`d as literals and committed to
`origin/main`. Every instance therefore calls QC with the same key, which makes the epic's
"one instance per environment, PROD has its own key" impossible, and the committed key is a live
credential in a git repo. The QC key was created 2026-06-12 and has never been rotated.

## Approach

### 1. One config surface: `inc/ihq-env.php`

New file, required first thing in `functions.php` (before any `inc/` that reads config).
Pattern is the one already in `inc/anam-proxy.php:60-78`: constant first, `getenv()` second,
explicit failure third.

```php
function ihq_env_get( $name, $default = null ) {
	if ( defined( $name ) && constant( $name ) !== '' ) {
		return constant( $name );
	}
	$env = getenv( $name );
	if ( $env !== false && $env !== '' ) {
		return $env;
	}
	return $default;
}

function ihq_env_require( $name ) {
	$value = ihq_env_get( $name );
	if ( $value === null ) {
		ihq_env_missing( $name );   // wp_die on front end, admin notice in wp-admin
	}
	return $value;
}
```

Keys (all read from `wp-config.php`):

| Constant | Required | Meaning |
|---|---|---|
| `IHQ_ENVIRONMENT` | yes | `dev` / `qa` / `prod` — logging and diagnostics only, never branches behaviour |
| `IHQ_API_BASE_URL` | yes | influencerhq-api gateway base incl. stage, no trailing slash |
| `IHQ_GAME_PORTAL_BASE_URL` | yes | portal base incl. `/av-baccarat`, no trailing slash |
| `IHQ_INFLUENCER_API_KEY` | yes | `x-api-key` relayed to account-api `/oauth/start-session` |
| `CF_TURNSTILE_SITE_KEY` | no | Turnstile widget; absent = Turnstile off (existing behaviour, deliberate) |
| `CF_TURNSTILE_SECRET_KEY` | no | as above |
| `IHQ_ELEVENLABS_API_KEY` | no | absent = Talk Now returns a config error instead of a signed URL |

`wp_die` on a missing required key is the fail-loud rule from the ticket. It is safe because
the wp-config block is written on all three instances before this deploys (see Rollout).
The message names the missing constant and nothing else.

### 2. Replace every literal

| Site | Today | After |
|---|---|---|
| `inc/api-ajax-calls.php:81` | `define INFLUENCER_API_BASE = …/qc` | `define( 'INFLUENCER_API_BASE', ihq_env_require( 'IHQ_API_BASE_URL' ) )` — keeps the 10 existing callers untouched |
| `functions.php:1020` (`ihq_api_proxy`) | `$api_base = '…/qc'` | `INFLUENCER_API_BASE` |
| `page-portal-testapi.php:127` | JS `const BASE='…/qc'` | `const BASE=<?php echo wp_json_encode( INFLUENCER_API_BASE ); ?>` |
| `inc/email-verification-handler.php:1079` | start-session literal | `INFLUENCER_API_BASE . '/account/oauth/start-session'` |
| `inc/email-verification-handler.php:1054` | `define IHQ_INFLUENCER_API_KEY = literal` | removed; `ihq_oauth_start_session_request_headers()` reads `ihq_env_require( 'IHQ_INFLUENCER_API_KEY' )` |
| `functions.php:317` | portal default literal | `ihq_env_require( 'IHQ_GAME_PORTAL_BASE_URL' )` |
| `page-portal-profile.php:619` | portal default literal | `ihq_get_hq_game_portal_base_url()` default path |
| `template-parts/portal-header.php:61-63` | own copy of default + meta lookup | `ihq_get_hq_game_portal_base_url( $uid )` |
| `page-portal-challenges.php:28`, `test-form.php:14` | `…/av-baccarat/external/leaderboards` | `ihq_get_hq_game_portal_base_url() . '/external/leaderboards'` |
| `inc/turnstile-verify.php:15-21` | two `define()` fallbacks with literals | removed; `ihq_turnstile_is_configured()` unchanged in meaning (both present → on) |
| `functions.php:1096` | inline ElevenLabs key | `ihq_env_get( 'IHQ_ELEVENLABS_API_KEY' )`, `wp_send_json_error` when null |

The per-user overrides (`hq_game_url`, `ihq_oauth_start_session_url`) stay for now — decision
2026-09-15 — but their *fallback default* becomes the instance value, so a user with no
override lands on the right environment. Retiring them is PO-3073 part 2.

### 3. Rollout order (ops, gated on this PR but not in it)

Values per instance, all verified live 2026-09-14/15:

| Instance | `IHQ_ENVIRONMENT` | `IHQ_API_BASE_URL` | `IHQ_GAME_PORTAL_BASE_URL` | Key source (SSM `SecureString`) |
|---|---|---|---|---|
| `influenchqdev.wpenginepowered.com` | `dev` | `https://02nvfvonol.execute-api.eu-west-2.amazonaws.com/qc` | `https://qc-game-portal-client-tf-b2c.dev.ae.games/av-baccarat` | `/qc/account-api-tf-api/INFLUENCER_HQ_SSO_API_KEY` in 468872710644 eu-west-2 |
| `influencerhqqa.wpenginepowered.com` | `qa` | `https://nxyd4exz24.execute-api.eu-west-2.amazonaws.com/main` | `https://main-game-portal-client-tf-b2c.qa.ae.games/av-baccarat` | `/main/account-api-tf-api/INFLUENCER_HQ_SSO_API_KEY` in 610791800521 eu-west-2 |
| `influencerhq.co` (PROD → bet5 / Singapore) | `prod` | `https://0cn4xq456d.execute-api.ap-southeast-1.amazonaws.com/main` | `https://play.bet5games.com/av-baccarat` (alias of `main-game-portal-client-tf-b2c.bet5.ae.games`) | `/main/account-api-tf-api/INFLUENCER_HQ_SSO_API_KEY` in 381492014723 ap-southeast-1 |

1. Write the wp-config block on all three WPE environments. Add both `*.wpenginepowered.com`
   hostnames to the Turnstile widget's allowed domains.
2. Deploy the theme (dev → QA → PROD). PROD flips from QC to bet5 at this step — see Notes.
3. Rotate the QC key: `terraform apply -replace=module.account_api.aws_api_gateway_api_key.influencer_hq_sso`
   in `account-api-tf/tf/envs/api/dev_qc`. The module also owns the usage-plan association and the
   SSM parameter, so both follow. Copy the new value from SSM into the dev env wp-config only —
   QA and PROD never held the QC key once step 2 is done.
4. Rotate the Turnstile secret (Cloudflare dashboard) and the ElevenLabs key (their dashboard);
   update all three wp-configs. Needs whoever owns those accounts — `NeedsHuman` on PO-3066.

## Alternatives considered

- **Separate `wp-content/ihq-env.php` include outside the theme.** Survives rsync deploys without
  touching WPE's wp-config, but nonstandard and a second mechanism next to the existing
  `ANAM_*` constants. Rejected 2026-09-15 in favour of wp-config constants.
- **Keep QC fallback defaults in the theme for a softer rollout.** Leaves QC URLs in source and
  hides a misconfigured instance. Rejected — fail loud, pre-populate wp-config instead.
- **Branch on `IHQ_ENVIRONMENT` inside the theme to pick URLs.** Puts the environment table
  back in source. Rejected; the env name is for logs only.
- **Rewrite git history to purge the literals.** Force-push is forbidden and rotation makes the
  old values worthless. Not doing it.

## Blast radius

- Every server-side call to influencerhq-api (challenges, referral link, players/me, fullname,
  start-session) and every portal iframe/redirect URL now depends on wp-config being present.
  A missing constant takes the whole front end down by design. The wp-config step must
  precede the deploy on each instance.
- `page-portal-testapi.php` and `ihq_api_proxy` (a `nopriv` AJAX action that forwards any
  endpoint to the gateway with the key attached) keep working, now against the instance's
  gateway. Kept by decision 2026-09-15; the open-relay exposure is unchanged and is noted
  below as a follow-up.
- PROD moves from QC to bet5 in the same deploy. Influencer accounts created via IHQ so far
  were started against QC account-api; on bet5 they resolve only if the same IHQ user ids exist
  there. That is a product/data question for PO-3072, not a code change here.
- Turnstile: the new hostnames fail the challenge until added to the widget. Registration and
  portal login on dev/QA are gated by it.
- `hq_game_url` / start-session user-meta overrides copied from prod into dev/QA still win over
  the instance value for the users that have them. Known, accepted until PO-3073 part 2.

## Notes

- **In-flight PR check (2026-09-15):** nobody else has started PO-3066/3067 — no branch, no
  commits, tickets To Do with no dev links. Overlaps: PR #24 (PO-2901) replaces the
  `page-portal-challenges.php:28` literal with `ihq_build_hq_game_portal_external_url()`, which
  routes through `ihq_get_hq_game_portal_base_url()` — so this plan's functions.php change covers
  it and the challenges.php edit is dropped. PR #21 (PO-3061) rewrites `page-portal-profile.php`
  wholesale — do the :619 edit after it merges. PRs #22/#25 (`inc/gary-proxy.php`) and #26
  (`inc/harness-auth-bridge.php`, own `IHQ_HARNESS_*` wp-config constants) each add one
  `require_once` line next to ours in functions.php (trivial) and already use the
  constant→getenv pattern; both could switch to `ihq_env_get()` afterwards.

- **Follow-up (not this PR):** `ihq_api_proxy` is reachable unauthenticated and relays the API
  key. Raise a ticket to require `manage_options` or delete it, alongside removing
  `page-portal-testapi.php` / `test-form.php`.
- The `Authorization: milos_testing` header on start-session is untouched.
- Deployment to the three instances is manual (SFTP/FileZilla) until ENGR-6662 lands; this PR
  does not change `AGENTS.md`'s deploy notes beyond adding the wp-config requirement.
- PO-3069 becomes "copy three SSM values into three wp-configs" once this merges.
