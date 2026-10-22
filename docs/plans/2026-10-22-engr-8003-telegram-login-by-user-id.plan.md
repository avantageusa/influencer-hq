---
title: "ENGR-8003: IHQ Telegram login matches the Telegram ID, not the @username"
ticket: https://avantageusa.atlassian.net/browse/ENGR-8003
date: 2026-10-22
todos:
  - id: lookup
    content: "inc/telegram-login-handler.php ihq_handle_login_telegram_user_ajax: get_users by meta_key telegram_user_id = (int) session id, number => 2, instead of communication_username = session @username"
    status: completed
  - id: guards
    content: "Refuse an empty, 0 or negative session id (session-expired message); keep 'No account is linked…' for no match; refuse two matches and log it without ids"
    status: completed
  - id: tests
    content: "tests/telegram-login-by-user-id.test.php: matching id signs in; username match with a different or no id refused; empty / 0 id refused; two users with one id refused"
    status: completed
---

# ENGR-8003: Telegram login by Telegram user ID

## Problem

`ihq_handle_login_telegram_user_ajax()` signs in the first WordPress user whose
`communication_username` meta equals the verified session's Telegram @username.
`communication_username` is free text typed at registration, and a Telegram username can be
changed or taken by someone else, so whoever holds that username on Telegram gets the account.

## Change

Look the user up by `telegram_user_id`, the numeric Telegram ID from the verified ID token,
which Telegram registration has stored since e39ef5c (2026-05-28). Fetch at most two users and
sign in only when exactly one matches. An empty or non-positive session id is refused before
any lookup. No match keeps the existing "No account is linked…" error; two matches is refused
and logged with no ids in the line.

## Alternatives considered

- Match on both id and username: rejects a real owner who changed their Telegram username, and
  the id alone already identifies the Telegram account.
- Fall back to the username when no id matches: re-opens the takeover for every account
  without an id.

## Risks

- Influencers with no `telegram_user_id` (admin-created, or registered before 2026-05-28) lose
  Telegram login until linked. Open question for Steve / Nikola, to count on prod before deploy.
- influencer-hq#156 (ENGR-8014) adds a gate in the same function; rebase onto whichever merges
  first.

## Not in scope

The ENGR-7830 replacement login, linking existing accounts to a Telegram id, and the
username-based checks elsewhere (registration's username/session comparison).
