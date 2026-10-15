---
name: Account-scoped portal onboarding progress store
overview: >
  The portal onboarding (PO-3063) needs to remember where an Influencer is and what they answered,
  on any device. This adds a user-meta record with GET/POST REST routes under ihq/v1/onboarding/progress,
  a strict per-field allowlist of snake_case enum values, and a small PHP API (get state, merge,
  mark complete, is complete) with actions other tasks hook into. No UI and no Braze sync here.
todos:
  - id: store-module
    content: inc/portal-onboarding-progress.php with enums, validation, merge, completion and REST routes
    status: completed
  - id: loader
    content: Load inc/portal-onboarding-*.php from functions.php (same block as the epic's other base-main tickets)
    status: completed
  - id: tests
    content: tests/portal-onboarding-progress.test.php covering every field and value, every rejection, 401/403, merge and completion
    status: completed
  - id: verify
    content: npm run test:js, npm run test:php and php -l on the changed PHP files (PHP 8.3 via the php:8.3-cli Docker image, and in CI); npm run lint:js is broken on main (ESLint cannot parse ES2020), so it was not run
    status: completed
  - id: review-fixes
    content: PR #121 review - completion in its own meta key, merge() validates, handle for an unselected platform is a 400, action names as constants
    status: completed
---

# [ENGR-7563] Store portal onboarding progress on the Influencer's account

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7563
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
Onboarding must resume where the Influencer left it, from any browser, and its answers must reach
the profile and Braze. The landing flow's store (`inc/aicoach-progress.php`) is keyed by a cookie and
cannot do that.

## Approach
- One user meta record, `_ihq_portal_onboarding`: `stage`, `answers`, `platforms`, `handles`,
  `language`. Completion lives in its own key, `_ihq_portal_onboarding_completed_at` (ISO-8601 UTC),
  written only by `mark_complete()`, so a save that read the record before completion cannot write
  it back as not complete. `get_state()` returns both as one state with `completed` and
  `completed_at`. No expiry.
- `GET`/`POST /wp-json/ihq/v1/onboarding/progress`. `permission_callback`: 401 when logged out, 403
  without a valid `wp_rest` nonce. Responses are `no-store, private`.
- POST is validated as a whole before anything is written; one unknown key or value is a 400 and
  nothing is stored. `completed` is not writable from the browser.
- Merge: answers per field (null clears one, for a hidden sub-option), platforms and handles replace,
  and only handles of selected platforms are kept (unticking a platform drops its handle). A posted
  handle for a platform that is not selected after the update is a 400. A language write also updates `_ihq_aicoach_language`
  so the registration preference and Braze's `language_preference` stay one value.
- PHP API: `ihq_portal_onboarding_get_state()`, `ihq_portal_onboarding_merge()`,
  `ihq_portal_onboarding_mark_complete()`, `ihq_portal_onboarding_is_complete()`; actions
  `ihq_portal_onboarding_saved` and `ihq_portal_onboarding_completed` (fired once), named by
  `IHQ_PORTAL_ONBOARDING_SAVED_ACTION` / `IHQ_PORTAL_ONBOARDING_COMPLETED_ACTION`. `merge()` applies
  the same allowlist as the route and returns a 400 `WP_Error` for a rejected update (nothing stored
  or fired); a valid update returns the full state as before.

### Stored values (EQ1)

| Field | Values |
| --- | --- |
| `stage` | `game_of_kings`, `modern_era`, `baccarat_experience`, `streaming_experience`, `equity_knowledge`, `social_platforms`, `world_championship`, `world_tour`, `classic`, `equity`, `how_this_works`, `active_followers`, `equity_example`, `draft_posts`, `post_action`, `closing` |
| `baccarat_experience` | `played_a_lot`, `played_some`, `new_player` |
| `streaming_experience` | `streamed_a_lot`, `streamed_some`, `new_to_streaming` |
| `streaming_learning_intent` | `new_ready_to_learn`, `new_maybe_later` |
| `equity_knowledge` | `owns_equity`, `understands_equity`, `interested_in_equity` |
| `equity_management` | `owns_self_managed`, `owns_managed_by_other` |
| `active_follower_count` | `100k_plus`, `50k_plus`, `25k_plus`, `10k_plus`, `under_10k` |
| `post_action_choice` | `post_now`, `edit_together`, `edit_later` |
| `platforms` / `handles` keys | `instagram`, `facebook`, `tiktok`, `youtube`, `bilibili`, `douyin`, `kakaotalk`, `line`, `linkedin`, `pinterest`, `qq`, `reddit`, `rednote`, `snapchat`, `telegram`, `wechat`, `weibo`, `whatsapp`, `x` |
| `language` | `en`, `zh`, `yue`, `ja`, `ko`, `th`, `vi` |

The sub-option fields follow the Braze attribute names in ENGR-7573 (`streaming_learning_intent`,
`equity_management`), and their values are the examples the tickets give (`new_ready_to_learn`,
`owns_self_managed`).

## Alternatives considered
- Extending `inc/aicoach-progress.php`: it is cookie-keyed and anonymous by design; mixing an
  account-keyed record into it would blur both.
- One meta key per field: more rows, and a partial write could leave a half-updated record.
- Silently dropping unknown values (as the landing store does): the ticket asks for 400.

## Blast radius
New REST namespace path `ihq/v1/onboarding/progress` and two new user meta keys. The only existing data
written is `_ihq_aicoach_language`, and only when a language is posted.

## Notes
- No rate limit: unlike the landing store, every write needs a logged-in account and writes only that
  account's one record.
- Drafts (screen 14) are added with ENGR-7570, which is on hold.
- Enum names are pending Product confirmation with the evaluation's OQ answers.
