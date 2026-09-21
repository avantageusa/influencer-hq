---
name: Login code lockout after repeated wrong guesses
overview: >
  After three incorrect 6-digit login-code submissions from the same IP, block
  further verify attempts for 15 minutes with a fixed lockout message that does
  not reveal whether an account exists. Implementation is server-side on the
  existing ihq_verify_login_code AJAX path. Repeated send-code limits beyond
  what already exists are out of scope for this change.
todos:
  - id: lockout-helpers
    content: Add per-IP fail counter and 15-minute lockout helpers next to the rate-limit IP helper
    status: completed
  - id: wire-verify
    content: Gate ihq_handle_verify_login_code_ajax on lockout; count hash mismatches; clear on success
    status: completed
  - id: atomic-counter
    content: Replace transient RMW with MySQL atomic increment so concurrent guesses cannot skip lockout
    status: completed
  - id: per-ip-mutex
    content: Serialize lock check + compare + failure transition with MySQL GET_LOCK per IP
    status: completed
  - id: global-cleanup
    content: Sweep expired fail/fail_timeout options from the scheduled cleanup job
    status: completed
  - id: verify
    content: Submit three wrong codes on portal login, confirm lockout message and blocked fourth try
    status: pending
---

# [PO-3262] Scenario 6 — Repeated failures are blocked without giving anything away

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3262
**Drafted by:** Cursor (Composer)

## Problem
Visitors can keep guessing 6-digit login codes with no temporary lockout. The
ticket requires a lockout after repeated incorrect codes, with a message that
says when they can retry and never leaks whether an account exists for the
identifier used.

## Approach
Extend `inc/email-verification-handler.php` only:

- Constants `IHQ_LOGIN_CODE_MAX_FAILURES` (3) and `IHQ_LOGIN_CODE_LOCKOUT_SECONDS` (900).
- Per-IP fail counter stored in `wp_options` and incremented with a MySQL
  `INSERT ... ON DUPLICATE KEY UPDATE` + `LAST_INSERT_ID()` so concurrent wrong
  guesses cannot skip the threshold via a transient read-modify-write race.
- Lockout flag remains a 15-minute transient (`set_transient` is idempotent).
- At the start of `ihq_handle_verify_login_code_ajax()`, reject locked IPs with
  `You are temporarily locked out, try again in 15 minutes`.
- On a wrong code hash, atomically increment; at 3, set the lockout and clear
  the counter. On successful verify, clear counter + lockout.

All existing login UIs (portal login, auth modal, home) already surface the
AJAX `message` field, so no front-end change is required.

## Alternatives considered
- Lock by email/token instead of IP — rejected for this ask; product specified
  IP lockout so a brute-force client is stopped even across tokens.
- Also throttle `ihq_send_login_code` here — deferred; send already has a
  per-email 45s throttle, and this change targets incorrect code entry.
- Client-only disable of the verify button — rejected; must be enforced server-side.

## Blast radius
Only the passwordless login **verify** AJAX path. Registration code verify is
unchanged. Shared IPs (NAT / office) can lock each other out for 15 minutes
after three wrong guesses from that network.

## Notes
- Manual verify step is still pending against a running site.
- Ticket AC also mentions repeated *requests* of codes; existing send throttle
  covers a narrow case — widen that in a follow-up if product wants a matching
  15-minute send lockout.
- CodeRabbit: transient read-modify-write was racy under concurrency; counter is
  now a MySQL atomic increment on `wp_options`.
- CodeRabbit follow-up: verify path holds a per-IP `GET_LOCK` across lock check,
  hash compare, and failure/lockout transition; expired fail counters are swept
  in `cleanup_expired_registrations()` via `ihq_cleanup_expired_login_verify_failures()`.
