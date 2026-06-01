---
name: Auth cookie 24h for influencers
overview: >
  Cap WordPress login cookie lifetime to 24 hours for users with the influencer role.
  Uses the auth_cookie_expiration filter (no PHP sessions). Main login paths already call
  wp_set_auth_cookie with remember=true (~14 days today); the filter overrides TTL on issue.
  Admins and other roles keep WordPress defaults. Does not revoke existing cookies until they expire.
todos:
  - id: auth-session-module
    content: Add inc/auth-session-lifetime.php with DAY_IN_SECONDS filter scoped to influencer role
    status: completed
  - id: wire-functions
    content: require auth-session-lifetime.php from functions.php after email-verification-handler
    status: completed
  - id: align-set-cookie
    content: Pass remember=false in ihq/telegram wp_set_auth_cookie calls for intent clarity
    status: completed
  - id: verify
    content: php -l; log in via OTP and Telegram; confirm cookie exp ~24h in dev tools; admin login unchanged
    status: pending
---

# Auth cookie 24h (influencer role)

**Ticket:** _(add Jira ID if applicable)_  
**Drafted by:** Cursor (Composer)

## Problem

Influencer sign-in (email OTP, Telegram login, legacy handlers) calls `wp_set_auth_cookie( $user_id, true )`, which issues **~14-day** cookies. Product wants logged-in influencers to re-authenticate after **24 hours**.

WordPress does not use server-side PHP sessions for front-end auth; lifetime is controlled when auth cookies are created via the **`auth_cookie_expiration`** filter.

## Approach

1. Add **`inc/auth-session-lifetime.php`**:
   - Constant **`IHQ_INFLUENCER_AUTH_COOKIE_SECONDS`** = `DAY_IN_SECONDS` (86400).
   - Filter **`auth_cookie_expiration`**: if `ihq_user_has_influencer_role( $user )`, return 24h; else return WordPress default `$expiration`.
2. **`functions.php`**: `require_once` the module after `email-verification-handler.php` (so `ihq_user_has_influencer_role` exists).
3. **`inc/email-verification-handler.php`** and **`inc/telegram-login-handler.php`**: change `wp_set_auth_cookie( ..., true )` → `false` so “remember me” is not requested (filter still sets 24h).

## Alternatives considered

| Option | Why not (for v1) |
| --- | --- |
| Site-wide 24h for all roles | Would shorten wp-admin / editor sessions; scoped to `influencer` only. |
| `init` hook + `login_at` meta + forced logout | Stronger enforcement; more edge cases; cookie TTL sufficient for v1. |
| Plugin | Unnecessary dependency for one filter. |

## Blast radius

- **Influencers** logging in after deploy get 24h cookies; existing sessions keep old expiry until cookie expires or user logs out.
- **Non-influencer** users unaffected.
- **Legacy page templates** (`page-home-fold.php`, etc.) with local `auth_cookie_expiration` filters: global filter runs at priority 10; influencer users still get 24h when our filter runs (same priority — order is load order; our module loads from `functions.php` early enough).

## Out of scope

- Revoking all active influencer sessions on deploy.
- OAuth / `ihq_token_expires` API tokens (separate ~1h lifecycle).
- UI “session expired” messaging (browser shows logged-out state on next request).

## Test plan

- [ ] Influencer: login via email code → DevTools → auth cookie `Expires` ~24h from now.
- [ ] Influencer: Telegram login → same.
- [ ] Administrator (or non-influencer): login → cookie not forced to 24h (if test account exists).
- [ ] `php -l` on `inc/auth-session-lifetime.php` and touched handlers.
