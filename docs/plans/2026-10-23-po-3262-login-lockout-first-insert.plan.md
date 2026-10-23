---
name: Fix one-strike login-code lockout
overview: >
  ihq_login_verify_increment_failures() (4c02ce7) read the new counter value from
  $wpdb->insert_id. On the first INSERT of a counter row MySQL reports the options.option_id
  auto-increment there (5814 on IHQ dev), not LAST_INSERT_ID(1), so the first wrong code locked
  the IP and the header Login refused the first code for a fresh fingerprint. The raw INSERT also
  left the key in the object cache's notoptions, so get_option() never saw the counter and the
  10/day cap and the burn-after-3 never fired. Read the value back from the row after the atomic
  upsert, and clear the key from notoptions as well as from the options cache. Out of scope: the
  `skipped:true` account-existence leak (ENGR-7830 family), raw mailer error text (ENGR-8156),
  ihq_issue_and_send_login_code_for_influencer() (ENGR-8022 is editing it).
todos:
  - id: increment
    content: "inc/email-verification-handler.php ihq_login_verify_increment_failures(): keep the single-statement upsert (no LAST_INSERT_ID), read the counter back with SELECT, ignore insert_id"
    status: completed
  - id: cache
    content: "Same function: after the raw write, drop the key from the notoptions cache and delete its options cache entry, the way add_option() does"
    status: completed
  - id: tests
    content: "tests/visitor-code-verify.test.php: FakeWpdb returns the auto-increment id on a first insert; get_option/delete_option model notoptions; new cases for first insert, 2nd/3rd failure lock, cycle restart, notoptions, daily cap and burn-after-3"
    status: completed
  - id: verify-dev
    content: "After merge and the IHQ dev deploy (Primary Coordinator): 3 wrong codes lock on the Login page, modal and header Login; the first right header code signs in"
    status: pending
---

# [PO-3262] Fix one-strike login-code lockout

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3262
**Found by:** PO-3255 verification on IHQ dev, 2026-10-23 (`work/handoff/po3255-verify-2026-10-23.md`)
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem

`INSERT ... VALUES (%s, LAST_INSERT_ID(1), 'no') ON DUPLICATE KEY UPDATE option_value =
LAST_INSERT_ID(n + 1)` relies on `insert_id` echoing the `LAST_INSERT_ID(expr)` value. That holds
on the update path, but when the statement inserts a row, the client reports the auto-increment id
the insert generated. The first wrong code therefore read as thousands of failures, locked the IP,
and the lock deleted the row, so every cycle was one strike.

The write bypasses the options API, so a persistent object cache still holds the key in
`notoptions` (left there by an earlier `get_option()` miss or `delete_option()`), and every
`get_option()` on the counter returns the default.

## Change

- Keep the upsert as one statement, so concurrent requests cannot lose an increment.
- Read the value back with `SELECT CAST(option_value AS UNSIGNED)` on the same connection.
  Under concurrency the readback can only see a value at least as high as this request's own
  increment (the counter only grows until it is deleted), so it fails closed. The per-IP path also
  holds the per-IP mutex. A row deleted in between reads as 0 and is reported as 1, as before.
- Remove the key from `notoptions` and delete its `options` cache entry, as `add_option()` does,
  so every reader of these counters sees the written value.

## Alternatives considered

- **Stop the auto-increment leaking into insert_id** (e.g. treat `rows_affected === 1` as "first
  insert, value 1"): exact and one round trip fewer, but it depends on the driver's
  affected-rows and insert-id semantics, which is the class of assumption that broke here.
- **Write through `update_option()`**: read-modify-write, the race 4c02ce7 removed.
