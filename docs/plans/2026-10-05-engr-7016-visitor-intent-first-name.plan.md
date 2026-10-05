---
name: Use the WordPress username as first name when a registration has none
overview: >
  The visitor-intent registration collects no name, so
  ihq_build_registration_data_from_visitor_intent() passes empty first_name and
  last_name. Start-session then sends firstName "" and referral rejects the
  player, so the influencer is never provisioned. In the shared
  ihq_create_influencer_user_from_registration_data(), a first name that is
  empty after trimming is replaced by the WordPress username the function has
  just created. It is saved to the first_name user meta and sent as
  payload.firstName. last_name stays as given; the account-api companion
  (ENGR-7015) fills a placeholder last name.
todos:
  - id: helper
    content: ihq_registration_first_name_or_username() in inc/email-verification-handler.php returns the given first name unless it is empty after trimming, else the username
    status: completed
  - id: wire
    content: ihq_create_influencer_user_from_registration_data() applies the helper after wp_create_user succeeds, before the first_name meta write and the ihq_register_oauth_user() call
    status: completed
  - id: tests
    content: tests/registration-first-name-fallback.test.php (empty, whitespace, missing key, real name, username de-dup suffix) + scripts/test-registration-name-mutations.py
    status: completed
  - id: verify
    content: Run every tests/*.test.php, php -l on touched files, both mutation scripts, open PR, check CI
    status: in-progress
---

# [ENGR-7016] Send the WordPress username as first name when a registration has none

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7016
**Companion:** https://avantageusa.atlassian.net/browse/ENGR-7015 (account-api placeholder last name)
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem

Referral needs a non-empty first and last name to provision a player. The
visitor-intent modal has no name field, so
`ihq_build_registration_data_from_visitor_intent()`
(`inc/visitor-intent-handler.php`) hardcodes `first_name` and `last_name` to
`''`. The user is created with no name meta, start-session sends
`firstName: ""`, referral returns 400, and the Profile shows "API returned HTTP
404" for both referral links. The Profile's Name row already shows the
WordPress username, which the platform never receives.

## Approach

- Fix it once, in the shared `ihq_create_influencer_user_from_registration_data()`
  (`inc/email-verification-handler.php`), so every nameless path is covered,
  not only visitor-intent.
- A small pure helper, `ihq_registration_first_name_or_username( $first_name, $username )`,
  returns `$first_name` unchanged when it has any non-whitespace content, and
  `$username` otherwise. A real first name is passed through untouched (not
  trimmed), so existing behaviour for named registrations is byte-identical.
- It is applied after `wp_create_user()` succeeds, so `$username` is the final
  name including any de-duplication suffix (`ann1`), which is what WordPress
  shows as the display name.
- The resolved first name is saved to `first_name` meta (so later
  `ihq_refresh_influencer_oauth_tokens()` calls, which read meta, send it too)
  and passed to `ihq_register_oauth_user()`.
- `last_name` is left as given. No UI change.

## Alternatives considered

- **Fill the name in `ihq_build_registration_data_from_visitor_intent()`.** It
  does not know the final username (de-dup suffix is decided later), and it
  would leave every other nameless caller broken.
- **Collect a real name in the visitor-intent modal.** A UI change with copy
  in 7 languages; out of scope per the ticket — a Product decision.
- **Fill last_name too.** The ticket assigns that to account-api (ENGR-7015).

## Blast radius

- Only registrations whose first name is empty or whitespace change. They now
  store `first_name` = username. Readers of that meta: start-session (the
  fix), Braze (already fell back to `display_name`, which is the username, so
  no visible change), the Luna users REST endpoint (now returns the username
  instead of `''`), and challenge/leaderboard name strings in
  `inc/influencer-role.php` (now show the username instead of a blank).
- Existing users created before this change are not backfilled. Their next
  "Request SSO again" still sends their stored (empty) first name.

## Testing

- `tests/registration-first-name-fallback.test.php`, standalone, no WordPress
  bootstrap, in the style of `tests/oauth-start-session-referrer.test.php`.
- `scripts/test-registration-name-mutations.py`, targeted mutations in the
  style of `scripts/test-referral-mutations.py`.
- All existing `tests/*.test.php` re-run.
