---
name: Wait for the other request's platform token refresh (ENGR-7017 reopen)
overview: >
  QA reopened ENGR-7017: after a fresh login over a stale stored platform
  token, Profile showed "API returned HTTP 401" on both referral links. The
  profile page fires get_referral_link and ihq_get_player_me at the same time.
  Both see the expired token; one takes the per-user refresh lock and runs
  start-session; the other found the lock taken, read the token from its own
  request's user-meta cache (still the old one), sent the expired token, and,
  because a refresh had been "attempted", never retried. Fix: a request that
  finds the lock taken waits (up to 10 s) for the holder to release it, drops
  its user-meta cache, and uses the token the holder stored.
todos:
  - id: diagnose
    content: QC logs (dev account, eu-west-2) for 2026-10-08 06:32Z, user wpu-78 — accountProxy start-session 06:32:48.6–49.4 then players/me 200; referralProxy three 401s at 06:32:49.8 (one round, no retry). Same pattern 09:58:42 and 2026-10-07 13:55/13:58. No start-session at the login before 06:32.
    status: completed
  - id: wait
    content: inc/ihq-platform-token.php — ihq_platform_refresh_lock_is_held() (SELECT on wp_options), ihq_platform_refresh_pause() (pluggable usleep), ihq_platform_wait_for_other_refresh() (≤ 40 polls × 250 ms, wp_cache_delete user_meta, then the valid stored token); ihq_refresh_platform_id_token() calls it when the lock is taken
    status: completed
  - id: tests
    content: tests/platform-id-token-refresh.test.php — per-request user-meta cache in the stubs, FakeWpdb::get_var, the wait unit cases, and the reopen scenarios (link waits for player, player waits for link, link 401 while player refreshes, other refresh fails, expired id + bad refresh token); scripts/test-platform-token-mutations.py — 9 new mutations
    status: completed
  - id: verify
    content: All tests/*.test.php pass; php -l clean; mutation scripts all killed; QA re-test on dev IHQ after the coordinator deploys
    status: pending
---

# [ENGR-7017] Wait for the other request's platform token refresh

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7017 (reopened by QA 2026-10-08)
**Previous plan:** `docs/plans/2026-10-05-engr-7017-refresh-platform-id-token.plan.md` (influencer-hq#88)
**Drafted by:** Claude Code (claude-opus-5-5)

## Why the reopen happened

The refresh lock from #88 lets one request per user run start-session. The
request that lost the lock returned "the stored token if it is still valid,
else ''". Two problems made that '' in practice:

1. It did not wait. start-session takes about a second; the loser checked
   straight away, while the winner was still running.
2. Even after the winner finished, the loser read `ihq_id_token` through its
   own request's user-meta cache, filled when the request started, so it saw
   the old token.

The loser then sent the expired token. `refreshed` was already true, so neither
the 401 retry nor the share link's retry ran, and the browser got
"API returned HTTP 401".

Profile always loads the share link and the player at once, so any visit with
an expired stored token could hit this. After the passwordless code login the
token is fresh, so the visit does not race; it happens when the login did not
run start-session (Telegram login, an existing visitor-intent user, or a
still-valid WordPress cookie) or when an hour has passed since login.

## Candidates ruled out

- **Refresh token expired / invalid** — IHQ never uses the Cognito refresh
  token. Its refresh is a new start-session (account-api has no refresh
  endpoint), so the refresh token's validity cannot matter.
- **Tokens per user, not per session** — both devices read the token from user
  meta on every request, server side, so the newest one wins for both. Device 1
  made no calls (no polling in the theme).
- **Backoff** — set only when start-session itself fails, which is the outage
  case. Lock contention never sets it. Kept as is.
- **A different code path** — both referral links come from one
  `get_referral_link` call, which already goes through the helper.

## Approach

When `ihq_platform_refresh_lock_acquire()` fails, `ihq_refresh_platform_id_token()`
now calls `ihq_platform_wait_for_other_refresh()`: poll the lock row (direct
SELECT, not the options cache) every 250 ms, at most 40 times; when it is gone,
or the 10 s run out, delete this request's `user_meta` cache for the user and
return the stored token if it is valid. If the holder failed, it has set the
backoff and the token is still expired, so the handler returns today's error.
The loser never runs start-session itself, so there is still one start-session
per user at a time.

## Alternatives considered

- **Start a platform session on every login path** (Telegram, visitor intent).
  Would remove the stale token at login, but adds start-session latency to
  those logins, and the hourly expiry would still race on Profile. Not done;
  noted as a finding.
- **Serialise the two AJAX calls in the profile page.** Fixes only this page;
  other pages that load several platform calls would still race.
- **Let the loser run its own start-session.** Two start-sessions per page load,
  which the lock was added to prevent.

## Blast radius

`inc/ihq-platform-token.php` only. A request that loses the lock can now take up
to 10 s longer (normally about 1 s) instead of failing straight away. Requests
with a valid token are unchanged.
