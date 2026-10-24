---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-8003 Telegram login refreshes platform tokens (influencer-hq#162)
date: 2026-10-24
---

# Code Review: ENGR-8003 Telegram login refreshes platform tokens (influencer-hq#162)

Independent review of PR #162 (`fix/ENGR-8003-telegram_login_refreshes_tokens` @ a5d2a37 into `main`),
against ENGR-8003 comment 138440 (the scope this follow-up takes on), the repo's
`.claude/rules/coding-standards.md` and the pr-review checklist.

## Spec check

- Comment 138440 asks for `ihq_refresh_influencer_oauth_tokens()` after each `wp_set_auth_cookie()`
  in the Telegram handler. The PR adds it after the existing-account login
  (`inc/telegram-login-handler.php:593`) and leaves the new-account path alone. Confirmed that the
  new-account path already runs start-session: `ihq_handle_register_telegram_user_ajax()` goes through
  `ihq_create_influencer_user_from_registration_data()`, which calls `ihq_register_oauth_user()`
  (`inc/email-verification-handler.php:335`). Leaving it alone is right.
- Using the stored `ihq_oauth_country_iso` matches the lazy refresh (`inc/ihq-platform-token.php:251-252`)
  and the "Request SSO again" handler (`inc/email-verification-handler.php:1816`). A failed refresh
  returns `false` before any `update_user_meta`, so the stored tokens are kept, as the log line says.
- Nothing outside that scope is changed.

## Blockers

None.

## Warnings

None.

## Nits

- `inc/telegram-login-handler.php:530`: the docblock says the lazy refresh (ENGR-7017) "retries once
  the tokens expire". That only holds for a user who already has an `ihq_id_token`.
  `ihq_platform_session_begin()` returns early when no token is stored (`inc/ihq-platform-token.php:283-285`),
  so a user with no stored token whose login refresh fails gets no retry until their next sign-in.
  Suggested fix: reword it, for example "the lazy refresh (ENGR-7017) retries once stored tokens expire;
  a user with no stored token is retried at the next sign-in."
- `inc/telegram-login-handler.php:538-539`: "read the stored country, fall back to `''` when it is not a
  string, then refresh" is now written in a third place (`inc/ihq-platform-token.php:251-252`,
  `inc/email-verification-handler.php:1814-1819`). Optional: extract a small helper next to
  `ihq_refresh_influencer_oauth_tokens()`, such as `ihq_stored_oauth_country_iso( $user_id )`, and use
  it here. Fine to leave as it is for a P1 fix.

## Checks run

- Read the full diff (`git diff origin/main...HEAD`) and the ticket with all comments.
- `npm run test:js`: 744 pass, 0 fail (not affected by this PR).
- `npm run test:php`: **not run locally** (no PHP CLI or Docker on this machine). CI "PHP tests" and
  "JS tests" pass on a5d2a37.
- `npx tsc --noEmit`: not applicable (no TypeScript in the repo).
- `git merge-tree`: the head merges cleanly with `origin/main` (7a2ec3d) and with #156's head (9e7ea07).
- Read the new test: the events and the log are checked with exact `===` comparisons, the
  refresh-result `1` case covers the strict `true !==`, and the non-string-country case covers the
  `is_string` guard. The `function_exists` guard's false branch is not exercised; it is the same
  defensive guard as `aicoach-register.php:183`, so this is not a finding.

## Summary

A small, targeted change that does what comment 138440 asks: the existing-account Telegram login now
re-runs start-session with the stored country, does not fail the login when the refresh fails, and logs
without the user id. The new-account path is correctly left alone because registration already runs
start-session. Tests are exact and cover success, failure, non-`true` results, missing or odd stored
country, a refused login and the new-account path. The only risk is the one already stated: the login
answer now waits on one start-session call, as the email-code login does. Two nits, both optional.
Recommendation: **merge**.
