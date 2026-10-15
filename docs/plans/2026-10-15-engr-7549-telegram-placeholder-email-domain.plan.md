---
title: "ENGR-7549 (influencer-hq part): Telegram stand-in email on the reserved .invalid domain"
ticket: https://avantageusa.atlassian.net/browse/ENGR-7549
date: 2026-10-15
todos:
  - id: writer
    content: "inc/telegram-login-handler.php ihq_telegram_build_fake_email: <handle>@ + IHQ_PLACEHOLDER_EMAIL_DOMAIN (chat.invalid) instead of @email_telegram.com"
    status: completed
  - id: reader
    content: "inc/influencer-role.php ihq_resolve_user_registration_method: unchanged; docblock says new Telegram accounts are recognised by telegram_user_id"
    status: completed
  - id: tests
    content: "tests/telegram-placeholder-email.test.php: writer (domain, fallback, index loop, old-domain twin) and registration-method reader"
    status: completed
---

# ENGR-7549, influencer-hq part

## Problem

IHQ Telegram registration gives the WordPress user `<handle>@email_telegram.com`. That domain is
not reserved, and no placeholder check recognises it, so the address is treated as real:
`email_verified` is set, it is sent to Braze as the profile email, and the Profile page shows it.

## Change

Build the address on `IHQ_PLACEHOLDER_EMAIL_DOMAIN` (`chat.invalid`, inc/placeholder-email.php),
the stand-in domain ENGR-7511 introduced. Same handle, same uniqueness loop, same `tg<id>`
fallback. `ihq_is_placeholder_email()` then recognises new Telegram accounts, so they are not
marked email_verified, their stand-in is not sent to Braze, and ENGR-7611 hides it on Profile.

## Reader

`ihq_resolve_user_registration_method()` keeps its `email_telegram.com` check for older accounts.
It is not extended to `chat.invalid`, because chat-only AI Coach registrations (LINE, WhatsApp…)
share that domain; new Telegram accounts always carry `telegram_user_id`, which is checked first.

## Not in scope

- Existing `@email_telegram.com` accounts are not migrated, and `ihq_is_placeholder_email()` is
  not widened to the old domain (its ENGR-7511 test asserts the old address is not a stand-in).
- account-api's Telegram writer is in account-api#564; the guest placeholder waits for PO-3155.
