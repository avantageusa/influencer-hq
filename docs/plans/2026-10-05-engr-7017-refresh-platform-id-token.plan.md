---
name: Refresh the platform ID token when it expires
overview: >
  ihq_id_token (Cognito ID token from account-api start-session) lasts one
  hour, but nothing refreshes it after login, so profile and challenge calls
  to influencerhq-api return 401 an hour after login. A small helper in
  inc/ihq-platform-token.php refreshes the token before the call when
  ihq_token_expires has passed (60 s margin), and on a 401 refreshes once and
  retries once. The refresh re-runs start-session via the existing
  ihq_refresh_influencer_oauth_tokens(), which now returns whether it worked.
  A request refreshes at most once; a failed refresh returns today's error.
todos:
  - id: helper
    content: inc/ihq-platform-token.php — ihq_platform_id_token_is_expired(), ihq_refresh_platform_id_token(), ihq_platform_session_begin(), ihq_platform_send_with_401_retry(); required in functions.php before api-ajax-calls.php
    status: completed
  - id: refresh-result
    content: ihq_refresh_influencer_oauth_tokens() returns true when tokens were stored, false otherwise (existing callers ignore the result)
    status: completed
  - id: wire
    content: Use the helper in ihq_get_player_me_ajax, ihq_update_fullname_ajax, create_challenge_ajax, get_challenges_for_player_ajax, get_challenge_details_ajax, join_challenges_ajax, and get_referral_link_ajax (proactive refresh; keep its non-200 retry unless start-session already ran this request)
    status: completed
  - id: tests
    content: tests/platform-id-token-refresh.test.php + scripts/test-platform-token-mutations.py
    status: completed
  - id: lock-backoff
    content: Per-user refresh lock (INSERT IGNORE on wp_options; add_option() is not atomic; 30 s stale takeover; release only our own lock) so concurrent requests run one start-session; 60 s transient backoff after a failed refresh so a start-session outage isn't hit by every request; a 401 is not resent with the same token (Steve, 2026-10-06)
    status: completed
  - id: verify
    content: Run every tests/*.test.php, php -l on touched files, the mutation scripts; QA on dev IHQ per the ticket
    status: pending
---

# [ENGR-7017] Refresh the platform ID token when it expires

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7017
**Related:** ENGR-7004 (influencerhq-api authorizer), ENGR-7016 (start-session first name), ENGR-7033 (SSO session codes are reusable for 24 h and store the full token set)
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem

`ihq_id_token` is refreshed only at login and in the share-link retry.
`ihq_token_expires` is stored but never read. WordPress sessions last days,
so most portal visits send an expired token. individual-rankings already
rejects it on `/rankings/*`, and since ENGR-7004 the `/account/players/*`
proxy does too, so the Profile shows "API returned HTTP 401".

## Approach

- `ihq_platform_session_begin( $wp_user_id )` reads the stored token. If
  `ihq_token_expires` is within 60 s of now (or past), it re-runs start-session
  and uses the new token. It returns `{ user_id, id_token, refreshed }`;
  `refreshed` is true once a refresh was attempted, successful or not.
- `ihq_platform_send_with_401_retry( $session, $send )` calls `$send( $token )`.
  On a 401, and only if this request has not refreshed yet, it refreshes once
  and calls `$send` once more with the new token. Any other status, a
  transport error, or a failed refresh returns the first response, so the
  handler reports exactly what it reports today.
- The refresh is `ihq_refresh_influencer_oauth_tokens()` with the stored
  `ihq_oauth_country_iso`, the same call the share-link retry already made.
  It now returns a bool so the helper can tell success from failure.
- Missing or non-numeric `ihq_token_expires` is not treated as expired; the
  401 retry still covers those users.
- Share link: `get_referral_link_ajax` calls `ihq_platform_session_begin()`
  first (the fetch reads the token from meta). Its existing retry on any
  non-200 (the new-influencer 404 → start-session → retry path, PO-3368) is
  kept, and also covers a 401. It is skipped when start-session already ran
  at the top of the request, so the request never refreshes twice.

## Alternatives considered

- **Use the stored Cognito refresh token (`ihq_refresh_token`).** Cheaper than
  start-session and has no provisioning side effects, but no account-api
  endpoint for an IHQ refresh-token exchange was found, and the theme has no
  Cognito client config. It would need an account-api route (or Cognito app
  client details) first. Start-session is what the ticket specifies and what
  the share-link path already relies on.
- **Refresh on every 401 only (no proactive check).** Doubles the API calls
  for every visit after the first hour.
- **A per-request static "already refreshed" flag.** Hidden state; the
  explicit session array is easier to test.

## Blast radius

- Every handler listed above may now make one start-session call
  (30 s timeout) before or during the request when the token is expired.
  Start-session also re-provisions referral / Genius records and refreshes
  the stored SSO response dump (`ihq_oauth_start_session_last`) — same as the
  existing share-link retry and "Request SSO again".
- Start-session sends the `ihq_ref` cookie's referrer code if the browser
  still has one, and clears it on success (ENGR-6966 behaviour, unchanged).
- If `ihq_oauth_country_iso` is empty, the refresh sends the normalised
  fallback country and stores it, as the share-link retry already did.
- `ihq_refresh_influencer_oauth_tokens()` callers (login, AI Coach register,
  "Request SSO again") ignore the new return value.

## Testing

- `tests/platform-id-token-refresh.test.php`, standalone, no WordPress
  bootstrap, in the style of `tests/registration-first-name-fallback.test.php`:
  expiry rule, refresh result, session begin, and every handler for valid,
  expired, 401, 401-twice, failed refresh, non-401 error, transport error,
  no token, and the share-link new-influencer 404 path.
- `scripts/test-platform-token-mutations.py`, targeted mutations.
- All existing `tests/*.test.php` re-run.
