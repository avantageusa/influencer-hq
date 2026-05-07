---
name: Turnstile — portal login page only
overview: >
  Add Cloudflare Turnstile (invisible) to the login form on page-portal-login.php only. Server-side verification must not break
  other templates that still POST to influencer_login_ajax (page-home.php, page-portal-home-claude.php). Recommended approach:
  a dedicated AJAX action for portal-login + shared verification helper. Secrets via wp-config constants; no new Composer deps.
todos:
  - id: dedicated-ajax-action
    content: Register wp_ajax_nopriv_influencer_login_portal_ajax (and logged-in mirror if needed) that duplicates influencer_login_ajax flow after Turnstile gate; keep influencer_login_ajax unchanged for other callers
    status: pending
  - id: turnstile-helper
    content: Add inc helper POST siteverify (secret, response, remoteip), timeout, return named shape { success, error_codes }; load from functions.php or influencer-auth-handler include chain
    status: pending
  - id: wp-config-docs
    content: Document CF_TURNSTILE_SITE_KEY + CF_TURNSTILE_SECRET_KEY (wp-config.php); behaviour when undefined (skip verification vs fail closed — recommend fail closed only on portal action when keys set)
    status: pending
  - id: page-portal-login-ui
    content: Enqueue Turnstile api.js; invisible widget; on submit run execute then append cf-turnstile-response to FormData; call influencer_login_portal_ajax; reset widget on error; handle non-JSON 403 if server sends it
    status: pending
  - id: verify-lint
    content: Lint touched PHP
    status: pending
---

# Turnstile on Portal Login template only

**Ticket:** _(link full Turnstile epic / ticket if tracked separately)_  
**Drafted by:** Cursor (Composer)

## Problem

We want bot resistance on the **Portal Login** screen (`page-portal-login.php`) via Cloudflare Turnstile invisible mode. The shared handler `influencer_login_ajax` is also used from **`page-home.php`** and **`page-portal-home-claude.php`**. Turning on mandatory Turnstile inside `influencer_login_ajax` would break those flows unless every caller gains a widget.

## Reproduction

_N/A — feature._ Today all three templates POST to `action=influencer_login_ajax` with nonce only.

## Approach

### 1. Dedicated AJAX action (recommended)

- Add **`influencer_login_portal_ajax`** (nopriv + priv) that runs the **same login logic** as `influencer_login_ajax` after checks.
- **`page-portal-login.php`** switches its `fetch` action to `influencer_login_portal_ajax` and sends `cf-turnstile-response`.
- **`influencer_login_ajax`** remains unchanged so home / other templates keep working.

_Alternative (not recommended):_ single handler + optional Turnstile — attackers omit the token. Single handler + “always verify when keys set” breaks non-portal pages.

### 2. Configuration

- `CF_TURNSTILE_SITE_KEY` — public, output on portal-login template only for the widget.
- `CF_TURNSTILE_SECRET_KEY` — `wp-config.php` / env only.

When **both** are defined: portal login action **requires** valid `cf-turnstile-response` (after nonce). On missing/invalid token: **`wp_send_json_error( ..., 403 )`** with a stable message for QA automation.

When keys are **undefined** (local dev): **skip** Turnstile verification for `influencer_login_portal_ajax` only if team agrees; otherwise developers must use Cloudflare test keys. _(Pick one policy in implementation; default recommendation: require test keys in dev so behaviour matches prod.)_

### 3. Server verification

- Small PHP function (e.g. `ihq_turnstile_verify( $token, $remote_ip )`) using `wp_remote_post` to  
  `https://challenges.cloudflare.com/turnstile/v0/siteverify`.
- Call it at the start of **`influencer_login_portal_ajax`** immediately after `check_ajax_referer` for a dedicated nonce **`influencer_login_portal_ajax`** (separate nonce action name avoids caching/reuse confusion with other pages).

### 4. Front-end (`page-portal-login.php`)

- Load Turnstile script with `render=explicit` or invisible integration per Cloudflare docs.
- On login submit: prevent default → `turnstile.execute` → append token → POST to `admin-ajax.php`.
- On failure: show message, `turnstile.reset`, re-enable button.

### 5. Out of scope (this plan)

- Register tab, other portal forms, `send_verification_email`, challenge AJAX — unchanged.

## Alternatives considered

- **One handler, Turnstile optional:** insecure (bots skip token).
- **Cloudflare WAF rule on URL only:** does not prove per-form token; different acceptance story.
- **Add Turnstile to all login UIs at once:** user explicitly scoped to portal-login template only.

## Blast radius

- **Low:** one new AJAX endpoint + one template + helper include.
- **Risk:** forgetting to deploy wp-config keys in staging → portal login fails until keys added (mitigate with test keys or documented skip policy).

## Notes

- After implementation, Steve/QA can extend Playwright to POST without token to `influencer_login_portal_ajax` and expect **403**.
- Epic plan [`2026-05-07-cloudflare-turnstile-portal-forms.plan.md`](./2026-05-07-cloudflare-turnstile-portal-forms.plan.md) remains the broader inventory; this plan is the **narrow first slice**.
