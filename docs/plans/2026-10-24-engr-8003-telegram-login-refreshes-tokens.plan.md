---
name: IHQ Telegram login refreshes the influencer's platform tokens
overview: >
  Follow-up to influencer-hq#157 for Steve Wolfe's comment 138440 on ENGR-8003. The
  existing-account Telegram login signs the influencer in without re-running start-session,
  so stored platform tokens and synced profile data stay stale. Call
  ihq_refresh_influencer_oauth_tokens() right after that sign-in, as the email-code and
  AI Coach logins do. The new-account path already runs start-session at registration and
  is left as it is.
todos:
  - id: refresh
    content: "inc/telegram-login-handler.php ihq_handle_login_telegram_user_ajax: after wp_set_auth_cookie, refresh tokens with the stored ihq_oauth_country_iso (the request sends no country); function_exists guard; a failed refresh is logged without the user id and the login still succeeds"
    status: completed
  - id: tests
    content: "tests/telegram-login-refreshes-tokens.test.php: existing login refreshes once with the user id and stored country; refresh failure still signs in and logs; refused login refreshes nobody; new-account path never calls the refresh"
    status: completed
---

# [ENGR-8003] Telegram login refreshes platform tokens

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-8003 (comment 138440)
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem

`ihq_handle_login_telegram_user_ajax()` calls `wp_set_auth_cookie()` and returns. Every other
influencer sign-in then calls `ihq_refresh_influencer_oauth_tokens()` (email code,
`inc/email-verification-handler.php:1494`; AI Coach, `inc/aicoach-register.php:184`), which
re-runs start-session, stores fresh tokens and syncs the profile. A Telegram login keeps
whatever tokens were stored last. Pre-existing: the handler did not refresh before #157 either.

## Change

A small helper, `ihq_telegram_login_refresh_platform_tokens( $user_id )`, called right after
the existing-account `wp_set_auth_cookie()`. It reads the country stored at the last
start-session (`ihq_oauth_country_iso`), because the Telegram login request sends none, the
same choice the lazy refresh in `inc/ihq-platform-token.php` makes. It is guarded with
`function_exists` as in `aicoach-register.php`. A refresh that does not return `true` is
logged with no user id and the login answer is unchanged.

The new-account path (`ihq_handle_register_telegram_user_ajax`) is untouched:
`ihq_create_influencer_user_from_registration_data()` already runs start-session
(`inc/email-verification-handler.php:335`), so a second call would hit start-session twice.

## Alternatives considered

- Use the request's country like the email path: the Telegram login form posts none, and
  passing `''` would overwrite the stored country with the fallback on every Telegram login.
- A `wp_login` / `set_auth_cookie` hook that refreshes for every login: wider blast radius
  and would double-refresh the paths that already call it.

## Risks

- The login answer now waits for one start-session call, as the email-code login already does.
- influencer-hq#156 (ENGR-8014) edits the same handler; the branches merge cleanly and the
  new test stubs #156's gate so it passes in either merge order.
