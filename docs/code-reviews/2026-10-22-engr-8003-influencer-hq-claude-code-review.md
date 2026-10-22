---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-8003 influencer-hq PR 157, Telegram login by telegram_user_id
date: 2026-10-22
---

# Code Review: ENGR-8003 influencer-hq PR 157, Telegram login by telegram_user_id

Reviewed head: 118cb73 (branch `fix/ENGR-8003-telegram_login_by_user_id`, base `main` @ 93038e4).
Spec: ENGR-8003 Jira body and comments (triage fix: look up by `telegram_user_id`, `number => 2`, exactly one match, guard empty/0 id, keep the "No account is linked…" error).

## Blockers

None.

## Warnings

- `inc/telegram-login-handler.php:562`: the new two-match refusal locks out every Telegram user who already owns two accounts, and the code still lets them create such accounts. Neither registration path checks whether the `telegram_user_id` is already stored: the email flow with Telegram as the first method (`inc/email-verification-handler.php:825-918`) and the Telegram register handler (`inc/telegram-login-handler.php:476-510`, where `ihq_telegram_build_fake_email()` adds a numeric suffix so a second account is always possible) both write the meta (`inc/email-verification-handler.php:308-309`). Before this PR such a user signed in to the first account matched by username. After it, they get "…linked to more than one account. Please contact support." The refusal is the right behaviour, but the PR's pre-deploy open question only counts accounts with **no** ID. Fix: add the duplicate count to that pre-deploy prod check (for example `SELECT meta_value, COUNT(*) FROM <prefix>usermeta WHERE meta_key = 'telegram_user_id' GROUP BY meta_value HAVING COUNT(*) > 1`) and list it in the PR body's Open question, so support knows how many people will hit the new message. The registration-side guard is listed under "Outside this PR".

## Nits

- `docs/plans/2026-10-22-engr-8003-telegram-login-by-user-id.plan.md:2`: the frontmatter uses `title` / `ticket` / `date`. The plan skill template and the other plans in `docs/plans/` use `name` and `overview` (plus `todos`). Rename `title` to `name` and add an `overview` paragraph.

## Checked and found correct

- The lookup is `meta_key => telegram_user_id`, `meta_value => (int)` session id, `number => 2` (named constant), and sign-in happens only on exactly one match. That is what the ticket's Expected Result and the triage fix ask for. There is no username fallback.
- The session id comes from the verified ID token (`claims['id']`, cast to int and rejected when `<= 0` at `:392-395`), so the attacker can't choose it. An empty, 0, negative or missing session id is refused before any lookup.
- `meta_value` as an int becomes a CHAR `=` comparison against the stored `'424242'` string. The registration paths store the same int, so the two match.
- The existing no-match error text is kept word for word. The new log line contains no IDs. Nonce and no-session behaviour are unchanged.
- Tests: the new standalone test stubs WordPress, asserts the JSON response, the `get_users` arguments, `wp_set_current_user`, `wp_set_auth_cookie` and the log line with strict `===` whole-structure comparisons. It covers happy, mismatch, no-id, bad-id (six variants), missing key, duplicate, no-session and bad-nonce cases. Scope stays on the one function the ticket names.

## Checks run

- Read the ticket (description and all comments), the full diff `origin/main...HEAD`, and the surrounding handler and registration code.
- Could not run `npm run test:php` or `php tests/telegram-login-by-user-id.test.php`: this machine has no PHP CLI and no Docker. I rely on the author's reported run (14/14, 36 suites) and on reading the test.
- `npx tsc --noEmit`: not applicable (PHP theme, no TypeScript in the diff). phpcs not installed.

## Outside this PR

- Registration lets one Telegram account own several WP users (no `telegram_user_id` uniqueness check in `ihq_create_influencer_user_from_registration_data()`, `inc/email-verification-handler.php:248-309`). A follow-up should refuse a second registration for an already-linked ID, or send it to login.
- `ihq_handle_verify_telegram_id_token_ajax()` still refuses a Telegram account with no @username (`inc/telegram-login-handler.php:380-388`), and the login handler still requires `telegram_username` in the session (`:539`). Now that login matches by ID, a user who has removed their Telegram username can't sign in. This is pre-existing and ENGR-7830 replaces this login.

## Summary

The PR fixes the account takeover the ticket describes. The login now matches only the verified numeric Telegram ID, refuses ambiguous and invalid sessions, and is well tested. There are no blockers. The one warning is operational: the new duplicate-ID refusal will hit users who registered twice, so the prod pre-deploy check should count duplicates as well as missing IDs. Recommended action: merge, after the PR body's pre-deploy question also covers duplicates.
