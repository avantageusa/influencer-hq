---
name: Refresh iframe SSO codes when the platform token expires
overview: >
  Game-portal frames keep using the login-time ssoCode after the Cognito tokens
  stored with that code expire (~1 hour). Refresh already re-runs start-session
  but the iframe URL builder never triggers it, and the new ssoCode is not
  treated as part of the refresh contract. Before reading the stored code for
  an embed (or PLAY), refresh through the ENGR-7017 lock/backoff path when the
  token is expired or within 15 minutes of expiry; persist the new ssoCode;
  keep the stored code if refresh fails. Out of scope: a tab left open past
  token lifetime, and any account-api change.
todos:
  - id: persist-sso
    content: Persist ssoCode from start-session in ihq_refresh_influencer_oauth_tokens
    status: completed
  - id: embed-refresh
    content: Refresh via ENGR-7017 helpers before iframe/PLAY SSO read, 15 min margin, no loop
    status: completed
  - id: tests
    content: Cover persist, 15 min window, skip when plenty of time left, failed refresh keeps old code, one start-session
    status: completed
  - id: verify
    content: Run tests and php -l on touched files
    status: completed
---

# [ENGR-7508] Refresh iframe SSO codes when the platform token expires

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7508
**Drafted by:** Cursor Grok 4.6

## Problem

Competition and Equity iframes (and PLAY) pass the stored `ihq_sso_code` into game-portal. `session-from-code` still redeems that code for 24h, but the tokens saved with it expire after ~1 hour. World then looks logged out and Private hangs on the skeleton. A fresh IHQ login fixes it for the next hour.

## Reproduction

Log in, wait until `ihq_token_expires` is in the past (or set it in the past), open Competition > World without logging out. World shows the logged-out view. Logging out and back in restores the logged-in view.

## Approach

- In `ihq_refresh_influencer_oauth_tokens()` (`inc/email-verification-handler.php`), after a successful `start-session`, also store `ssoCode` via `ihq_extract_sso_code_from_start_session_data()`. Do not clear the old code when the response has none.
- Add `IHQ_PLATFORM_EMBED_SSO_EXPIRY_MARGIN_SECONDS` (900) and `ihq_hq_sso_code_for_embed_url()` in `inc/ihq-platform-token.php`. If `ihq_token_expires` is past or within 15 minutes, call `ihq_refresh_platform_id_token()` once (existing lock, backoff, wait). Then return `ihq_get_hq_sso_code_for_user()`. Missing/non-numeric expiry does not refresh.
- Use that helper in `ihq_build_hq_game_portal_external_url()` and in the header PLAY link. Do not put a refresh side-effect on the generic getter.
- Failed refresh leaves the stored code on the URL. Never retry in a loop. Concurrent iframe builds share the ENGR-7017 lock, so a page load makes at most one `start-session`.

## Alternatives considered

- Refresh inside `ihq_get_hq_sso_code_for_user()` — would also fire on profile dumps and STREAM NOW; the ticket names the embed URL builder.
- Use the existing 60 s API-call margin for embeds — a frame loaded 50 s before expiry would still die about a minute later; 15 minutes is what the ticket asks for.
- Change account-api `redeemCode` to mint fresh tokens — conflicts with ENGR-7033.

## Blast radius

Every Competition/Equity iframe and PLAY after an hour-old login. A slow or failing `start-session` can delay page render by the existing lock wait (~10 s) once per user, then falls back to the old code. Profile/challenge API refresh (ENGR-7017, 60 s margin) is unchanged.

## Notes

A frame left open for more than ~1 hour still loses its in-browser session; that is a separate account-client / pool issue, called out on the ticket.
