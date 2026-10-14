---
agent: Claude Code
model: claude-opus-5-5
---

# ENGR-7511: independent review of influencer-hq PR #117

**PR:** https://github.com/avantageusa/influencer-hq/pull/117
**Reviewed head:** `3d15e382a68820a9990ea1a1be9cbb77493a6ed1`
**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7511 (option B, minimal fix; the long-term sign-in decision is PO-3255)

The PR makes AI Coach `POST /wp-json/ihq/v1/create-account` accept any one channel. When no channel is an email, the account gets `<uuid>@chat.invalid`. The final screen now shows the server's error text.

**Verdict:** no blockers. The stand-in email passes every downstream validator I could read. Four warnings and five nits are listed below. Two of the warnings (duplicate accounts, Braze) are gaps the PR body already accepts; they are here so the follow-up owner has the exact code paths.

## 1. Does downstream accept the stand-in email? YES

Sample: `00432a9c-a39f-44a1-af03-c151dab9a7db@chat.invalid`, 49 characters, `[0-9a-f-]` local part, `.invalid` TLD. I ran each library at the version pinned in that repo's lockfile.

| Hop | Code (origin default branch) | Check | Result |
|---|---|---|---|
| WordPress `is_email()` (`ihq_create_influencer_user_from_registration_data`, Braze sync) | `inc/email-verification-handler.php:254`, `inc/braze-integration.php:143` | Local part charset; domain split on `.`, each label `[a-z0-9-]` | Pass. PHP `FILTER_VALIDATE_EMAIL` also passes. `user_login` becomes the 36-char UUID, under WP's 60-char limit. |
| influencerhq-api `POST /account/oauth/start-session` | `src/config/swagger/swagger.json`: `InfluencerHqPayloadDto.email` is `type: string`, no `format` | none | Pass |
| account-api `POST /oauth/start-session` | `src/oauth/dto/influencerHqPayload.dto.ts`: `email` has no validator, and `payload` is not nested-validated. `ConvertFromInfluencerHqPayload` passes it into the Cognito `signUp` `UserAttributes` (`b2b.players.service.ts:339`) | Cognito email attribute format | Pass. The Cognito username is `influencerhq-wpu-<id>`, so it cannot collide. |
| referral `POST /user` (advocate creation) | `src/external/referral/dtos/createUser.dto.ts:21` `@IsEmail()` (class-validator 0.14.3, validator 13.15.26) | `isEmail` default options | **Pass** (`true`). For comparison: `x@email_telegram.com` fails, and account-api's `telegram-<id>@telegram.com` passes. |
| individual-rankings `authenticatedEmail` / `emailContact` | `src/utils/customJoi.js:49` `email({ tlds: { allow: false } })` (joi 17) | No TLD allowlist | Pass. Note that joi's *default* `.email()` rejects `.invalid` because of its IANA TLD list; no repo I read uses the default. |
| influencerhq-api `wowza/sessions/start` `email` | swagger `format: email` | Generic RFC shape | Pass |

Not verifiable from code: Genius Referrals' own advocate API (third party, called from `referral.service.ts:192`) and Braze `/users/track`. See W2 and W4.

## 2. Is mail sent to the stand-in?

- WordPress core: `wp_create_user()` sends no new-user notification. Nothing in this flow calls `wp_new_user_notification()` or `retrieve_password()`.
- The IHQ login code is only sent on the existing-email branch (`inc/aicoach-register.php:286`). A random UUID never matches there.
- **Braze:** yes. See W2.
- **Profile email change:** `save_settings_field_ajax` (`inc/api-ajax-calls.php:907`) calls `wp_update_user( user_email )`. Core then sends its "email changed" notice to the *old* address, the stand-in, so it bounces at the MTA. Harmless. See N3.
- **Account claim by email:** not possible. The stand-in is random, unguessable and unrelated to the handle. The existing-email branch never issues a session. Its login code goes to the stand-in, which nobody can receive.
- `email_verified` is set to `true` for stand-in accounts. See W2.

## Findings

### Blockers

None.

### Warnings

**W1. A returning chat-only visitor gets a new, duplicate account every time.** `inc/aicoach-register.php:142`
`ihq_aicoach_placeholder_email()` makes a new UUID on every request, so `get_user_by( 'email', … )` at :286 can never match. Each re-registration by the same Telegram/WhatsApp user creates:
- a new WP user,
- a new Cognito B2B user plus players row,
- a new Genius advocate (referral),
- a new Braze profile.

Only the 45 s per-IP throttle limits this. The PR body accepts the gap. Deduplicating on the typed handle would be unsafe, because the handle is unverified and signing in by handle would allow account takeover, so I am not asking for a lookup.
*Suggested fix for the author:* mark the accounts so PO-3255 can find and merge them later. For example, write a `_ihq_placeholder_email` = `1` user meta (or test the domain with an `ihq_is_placeholder_email()` helper). Name the duplicate-account cleanup as an explicit PO-3255 acceptance item on the ticket.

**W2. The stand-in is flagged `email_verified` and pushed to Braze as the profile email.** `inc/email-verification-handler.php:321`, `inc/braze-integration.php:135`
`ihq_create_influencer_user_from_registration_data()` writes `email_verified = true` unconditionally. `ihq_send_influencer_to_braze()` then sends `email: <uuid>@chat.invalid` together with the `influencer_registered` event. If a Braze Canvas or campaign is triggered on that event (welcome mail), it sends to an undeliverable address. That causes hard bounces, and Braze list hygiene and sender reputation count them. The PR body already says these sends "hard-bounce". I could not verify from code how Braze treats `.invalid`.
*Suggested fix:* skip the `email` attribute in the Braze payload when the address is a placeholder, and keep `external_id` and the event. Pass `email_verified` only for a real address. Both are small checks on the helper from W1.

**W3. Errors that need a fix on an earlier screen leave the visitor stuck on the final screen.** `inc/aicoach-register.php:133`, `js/aicoach-coach-flow.js:2376`
The final screen now shows, for example, "Please enter a valid email address." or the username-taken message. But it has only the error `<p>` and the Continue button (`page-home-aicoach.php:412-414`), with no way back to the channels or identity screen. Retrying sends the same payload and gets the same 400. The client email check (`js/aicoach/screens.js:108`, `/^[^\s@]+@[^\s@]+\.[^\s@]+$/`) accepts values that WP `is_email()` rejects, such as `ann@exa_mple.com`, so the invalid-email 400 is reachable through the UI. The triage comment called out PO-3100 Scenario 16 ("can correct the error and resubmit without restarting the flow"), and it is still not met.
*Suggested fix:* return a field or screen key with each 400, e.g. `{ error, field: 'email' | 'username' }`, and have the final screen re-open that panel. At minimum, make the client email regex reject what `is_email()` rejects (no `_` in the domain, no empty labels).

**W4. No tests for the JS changes, and Genius acceptance is unverified.** `js/ihq-aicoach-events.js:162`, `js/aicoach-coach-flow.js:2356-2361`
`hasAnyChannel()` and the `error`/`message`/generic selection on the final screen have no tests. Both files are untested IIFE/DOM code, but the selection logic is pure and could be extracted, e.g. `js/aicoach/register-error.js` with a `node --test` file, like the existing `js/aicoach/*.test.js`. The coding standards need mutation-tested coverage for behaviour changes.
Separately, Genius Referrals (third party) receives the stand-in on advocate creation, and its validation is not in our code.
*Suggested fix:* extract the selection logic and add tests. During the QC retest, confirm a Telegram-only registration gets a referral link: referral `GET /user/wpu-<id>/link` returns 200 and `referral` logs show no `ERROR_CREATING_EXTERNAL_SERVICE_USER`.

### Nits

- **N1.** `inc/aicoach-register.php:452-453`: the `=>` alignment in the `i18n` array broke when `missingEmail` was renamed to the longer `missingChannel`. WordPress PHPCS array-alignment rules will flag it.
- **N2.** The stand-in is shown to the influencer as their Email in several places:
  - the profile Account card (`page-portal-profile.php:1099`),
  - the welcome panel (`page-portal-home.php:90`),
  - the `hqEmail` game-portal URL parameter (`template-parts/portal-header.php:85`).
  wp-admin "Signed up via" (`inc/influencer-role.php:458`) reports these accounts as `email`. Suggest showing "—" and a chat-app label for the placeholder domain.
- **N3.** When a stand-in user sets a real email in Profile, WP core mails the "email changed" notice to the stand-in (see §2). This is harmless, but an `send_email_change_email` filter returning `false` for placeholder addresses would avoid the bounce.
- **N4.** `js/aicoach-coach-flow.js:2360`: WordPress's fatal-error REST response has `message` as HTML (`<p>There has been a critical error…</p>`). With `textContent` it renders as literal tags. This is safe (no XSS, and the debug detail sits in `data.error`, not `message`) but ugly. Suggest using the server message only when the response has the theme's `success: false` shape, or stripping tags.
- **N5.** Server-side channel validation only exists for `email`. Telegram/KakaoTalk/WeChat/Zalo accept any non-empty string. SMS/WhatsApp phone format and the LINE `U…` id are only checked in the browser (`js/aicoach/screens.js:105-115`), so a direct POST can store `telegram: "x"`. This is pre-existing behaviour and not a regression. Mirroring `isValidPhoneNumber` in PHP for `sms`/`whatsapp` would be cheap.

## Checked and fine

- **Error display (item 4):** every `error` string the handler returns is a curated `__()` message or a WP core `WP_Error` message ("Sorry, that username already exists!", etc.). None has a stack trace, upstream URL, token or PII. The start-session failure details stay in `error_log` and user meta and never reach the response. The text is written with `textContent`, so there is no XSS.
- **Validation (item 5):** all-empty, whitespace-only and unknown-key channels return a clean 400 ("Please select at least one communication method.") with no account, no sign-in and no throttle (tested). An invalid Email channel is rejected rather than masked by the stand-in (tested). The client gate counts any non-empty value, and the server drops unknown keys and returns the same 400.
- **UUID:** `wp_generate_uuid4()` is random per call and not derived from the handle, so it neither reveals nor collides with a handle.
- **Tests (item 6):** `tests/aicoach-register-channels.test.php` passes 23/23 checks locally (PHP 8.3). It asserts return values and side effects (created registrations, sign-ins, lookups, throttle) on the failure paths as well as the happy path. All 16 `tests/*.test.php` pass, and `node --test js/aicoach/*.test.js` gives 183/183. Gap: `is_email` is stubbed with `filter_var`. I checked WP's real `is_email()` logic by hand for the stand-in shape, and it passes.

## Follow-ups for the coordinator

- influencer-hq: tag placeholder-email accounts (meta or helper) so PO-3255 can merge the duplicates W1 creates.
- influencer-hq: Braze sync should drop `email` and `email_verified` for placeholder addresses (W2).
