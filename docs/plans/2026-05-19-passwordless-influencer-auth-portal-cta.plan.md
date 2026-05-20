---
name: Passwordless influencer auth + portal CTA
overview: >
  Migrate influencer registration and login off user-chosen passwords: server-generated passwords for
  WP users, email 6-digit codes for sign-in, dedicated ihq_* AJAX with Turnstile when configured,
  OAuth refresh after code login, legacy influencer_* AJAX redirects to OTP messaging, and portal
  landing CTAs that link logged-in users to /portal/portal-home/. This plan is written as work to
  execute against the pre-change theme (password fields and password-based AJAX still in use).
todos:
  - id: email-verification-handler
    content: Add login code send/verify AJAX, constants, role gate, hashing, throttle, OAuth refresh, cleanup, auto password when empty in ihq_create_influencer_user_from_registration_data
    status: pending
  - id: influencer-auth-handler
    content: Replace influencer_register_ajax / influencer_login_ajax / influencer_login_portal_ajax success paths with OTP-only errors; remove password debug logging
    status: pending
  - id: page-portal-home-claude
    content: Nonces + optional Turnstile; modal reg without password + login email/code calling ihq_*; hero and final CTA is_user_logged_in() branch to Go to portal link
    status: pending
  - id: page-portal-login
    content: Login and register tabs two-step OTP (ihq_* actions, nonces, Turnstile when keys set)
    status: pending
  - id: page-home
    content: Login tab ihq_send_login_code + ihq_verify_login_code; remove password from genius/send_verification_email client payload as needed
    status: pending
  - id: verify
    content: Manual QA per test plan; php -l touched PHP; sync FileZilla temp copies if used for deploy
    status: pending
---

# Passwordless influencer authentication and portal CTA

**Ticket:** _(add Jira ID)_  
**Drafted by:** Cursor (Composer)

**Note:** Plans live under `docs/plans/` (see `docs/plans/README.md`).

## Problem

1. Influencer flows today rely on **user-chosen passwords** in forms and/or **legacy AJAX handlers** that validate email + password. WordPress still needs a `user_pass`, but end users should not pick or type one for these flows.
2. There is **no first-class email OTP login** for existing influencers that ties into the same verification stack as registration codes.
3. The **portal marketing template** (`page-portal-home-claude.php`) always opens the contact modal for “start the conversation” — **logged-in users** should go straight to the app surface at **`/portal/portal-home/`**.

## Baseline (before changes — assume this state)

Assuming files **as they were prior** to this effort:

| Area | Baseline expectation |
| --- | --- |
| `inc/email-verification-handler.php` | Verification email / registration pending options exist; **no** `ihq_send_login_code` / `ihq_verify_login_code`, **no** `pending_login_code_%` lifecycle, **`ihq_create_influencer_user_from_registration_data` requires or receives a caller-supplied password** without generating one when empty. |
| `inc/influencer-auth-handler.php` | **`influencer_register_ajax`** and **`influencer_login_ajax`** (and possibly **`influencer_login_portal_ajax`**) perform or attempt **password-based** auth; debug logging may echo sensitive POST fields. |
| `page-portal-home-claude.php` | Modal registration includes a **password** field; overlay login expects **password**; hero + `#finalBtn` are **buttons** calling `openModal()` only; no **`ihq_login_code_nonce`**-driven OTP UI. |
| `page-portal-login.php` | **Email + password** (or legacy shape) login/register POST to **`influencer_*_ajax`** rather than OTP **`ihq_*`** actions end-to-end. |
| `page-home.php` | Login wired to **`influencer_login_ajax`** with password; genius / verification section may POST **`password`** with `send_verification_email`. |

## Approach

### 1. `inc/email-verification-handler.php`

- Add **`IHQ_LOGIN_CODE_EXPIRY_SECONDS`** (e.g. 900, match registration code TTL).
- Add **`ihq_user_has_influencer_role( $user )`** — restrict login OTP to **`influencer`** role (adjust role slug if production differs).
- Ensure **`ihq_verify_turnstile_or_error_for_ajax()`** exists and is invoked on **send-code** paths when Turnstile is configured (`cf-turnstile-response` via `ihq_turnstile_verify_response` pattern already used for registration).
- **`ihq_create_influencer_user_from_registration_data()`:** if **`password`** is empty string after normalization, set **`wp_generate_password( 32, true, true )`** before `wp_create_user` (keep accepting optional non-empty password for legacy pending rows).
- **Register `wp_ajax_nopriv` + `wp_ajax` hooks** for:
  - **`ihq_send_login_code`** → `ihq_handle_send_login_code_ajax`
  - **`ihq_verify_login_code`** → `ihq_handle_verify_login_code_ajax`
- **Send login code handler**
  - Verify nonce **`ihq_login_code_nonce`**.
  - Run Turnstile gate when configured.
  - Validate email.
  - **Anti-enumeration:** if user missing or not influencer, **`wp_send_json_success`** with **generic message**, **`skipped: true`**, **`signup_token` ''**, plausible **`expires_minutes`** — **do not send email**.
  - Else: throttle resend (e.g. transient per email hash), invalidate prior `pending_login_code_*` for same email via `ihq_pending_login_email_*` map, generate opaque token + **6-digit code**, store **only HMAC hash** (`hash_hmac` with `wp_salt( 'ihq_login_code' )` + token), persist `pending_login_code_{token}` + email map, **wp_mail** HTML message, return **`signup_token`** + expiry.
- **Verify login code handler**
  - Same nonce; validate token + 6 numeric digits; load option; check expiry; **`hash_equals`** on hash; resolve **influencer** user; delete pending + map; **`wp_set_auth_cookie`**; call **`ihq_refresh_influencer_oauth_tokens( $user_id )`** (new helper: wrap existing **`ihq_register_oauth_user`** + meta updates mirroring registration success path); return **`redirect_url`** (POST override or default **`home_url( '/portal/portal-home/' )`**).
- **Extend `cleanup_expired_registrations()`** query/delete for **`pending_login_code_%`** and related **`ihq_pending_login_email_%`** when **`expires`** passed.

### 2. `inc/influencer-auth-handler.php`

- **`influencer_register_ajax`:** after nonce check, **`wp_send_json_error`** with copy directing users to the **registration + email code** flow (no user creation via this action).
- **`influencer_login_ajax`** and **`influencer_login_portal_ajax`:** after nonce check, **`wp_send_json_error`** directing users to **request a 6-digit email code** (do not authenticate by password here).
- Remove any **`error_log` / dump** of **`$_POST`** fields that include passwords.

### 3. `page-portal-home-claude.php`

- At template top: create nonces **`ihq_reg_code_nonce`**, **`ihq_login_code_nonce`**; enqueue Turnstile script when **`ihq_turnstile_is_configured()`** and **`CF_TURNSTILE_SITE_KEY`** defined (match portal-login pattern).
- **Registration modal:** drop password field from markup and from JS **`FormData`** for initial send; rely on **`ihq_handle_send_registration_code_ajax`** (or existing registration-code action) already paired with Turnstile when present.
- **Login overlay:** replace password UI with **email → send code → enter code**; POST **`ihq_send_login_code`** / **`ihq_verify_login_code`** with nonce + **`signup_token`** + optional **`cf-turnstile-response`** + **`redirect_url`** as needed.
- **Hero + final band:** wrap CTAs in **`<?php if ( is_user_logged_in() ) : ?>`** — output **`<a class="btn-gold" href="<?php echo esc_url( home_url( '/portal/portal-home/' ) ); ?>">Go to portal</a>`**; else keep **`Yes — Let's Start the Conversation`** buttons (`openModal()` / `openModal('cfinal')`). Preserve **`id="finalBtn"`** and **`drift-on-scroll`** on the bottom CTA element for logged-in (**`<a>`**) and logged-out (**`<button>`**) variants.

### 4. `page-portal-login.php`

- Replace login tab with **send code + verify code** steps; **`fetch`** **`admin-ajax.php`** with **`ihq_send_login_code`** / **`ihq_verify_login_code`** and **`ihq_login_code_nonce`**.
- Replace register tab with **registration code** flow using **`ihq_reg_code_nonce`** and existing registration AJAX actions from `email-verification-handler.php`.
- When Turnstile site key available: render widgets, append **`cf-turnstile-response`** on submit, reset widget on error (mirror existing portal Turnstile JS patterns).

### 5. `page-home.php`

- Login tab: **`ihq_send_login_code`** / **`ihq_verify_login_code`** + nonce (no Turnstile widget required if server skips verification when keys unset).
- Genius / **`send_verification_email`:** stop collecting or sending **user password** in JS; server will generate password when field absent/empty once `ihq_create_influencer_user_from_registration_data` is updated.

## Security and UX requirements (implement to match)

| Topic | Requirement |
| --- | --- |
| Enumeration | Login send-code returns **same success copy** whether the account exists; only influencers receive mail; **`signup_token`** empty when skipped. |
| Storage | Persist **hashed** code only; TTL **15 minutes** (or constant). |
| Abuse | Short **resend throttle** per email. |
| Bots | Turnstile on send-code when configured. |
| Post-login | Refresh IHQ OAuth user meta after successful verify. |

## Options / transients (implementation reference)

- `pending_login_code_{token}` — email, user_id, code_hash, expires, timestamp.
- `ihq_pending_login_email_{md5(lower(email))}` — current token for that inbox.
- `ihq_login_send_{md5(lower(email))}` — throttle transient (exact TTL is implementation choice; ~45s is reasonable).

## Test plan

1. **Registration:** complete modal / portal register code path; email link or completion creates user; confirm **no user-facing password** required.
2. **Login OTP:** happy path, wrong code, expired code, resend throttle, unknown email (generic response, **no** second step if front-end checks empty `signup_token`).
3. **Turnstile:** with keys defined, send-code fails without token; with token succeeds.
4. **`influencer_login_ajax`:** returns error directing to OTP (no password success).
5. **Portal landing:** logged out → modal; logged in → **Go to portal** → **`/portal/portal-home/`**.
6. **`php -l`** on every touched PHP file.

## Alternatives considered

- **Dual password + OTP:** higher support burden and weaker “passwordless” story — not chosen.
- **Bake Turnstile into `influencer_login_portal_ajax` only:** insufficient once login moves to **`ihq_*`**; Turnstile belongs on **send-code** handlers used by portal + modal.
- **Different success for unknown email on login:** leaks registered addresses — not chosen.

## Blast radius

- Any external or stale front-end still calling **`influencer_login_ajax`** / **`influencer_register_ajax`** for real auth will **break** until updated to **`ihq_*`** OTP — coordinate release.
- Standard **`wp-login.php`** and admin flows are unchanged unless separately edited.
- Email delivery is on the critical path for all OTP flows.

## Notes

- Sync **FileZilla temp** staging copies with repo files when deploying from those paths (`AGENTS.md`).
- Reconcile or remove **`page-portal-home-claude-bkp.php`** so it does not diverge from the live template.
