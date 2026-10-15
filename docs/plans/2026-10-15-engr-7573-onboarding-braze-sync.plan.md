---
name: Sync onboarding answers to Braze
overview: >
  Onboarding answers must reach Braze as each one is stored, not only at completion, through the
  existing server-side /users/track integration, and a Braze failure must never block onboarding. This
  adds a listener on the account store's save and completion actions (ENGR-7563) that maps each save to
  Braze custom attributes and sends them for the Influencer's external_id. Segments and campaigns are
  out of scope.
todos:
  - id: module
    content: inc/portal-onboarding-braze.php — attribute mapping, send, hooks on saved/completed
    status: completed
  - id: tests
    content: tests/portal-onboarding-braze.test.php — exact payload per answer, platforms/handles, language, completion, failure paths
    status: completed
  - id: verify
    content: test:js; PHP tests in CI
    status: completed
---

# [ENGR-7573] Sync onboarding answers to Braze

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7573
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
Product wants every onboarding answer in Braze for segmentation, including from Influencers who drop
out part-way. Only `language_preference` reaches Braze today, at registration.

## Approach
- `ihq_portal_onboarding_braze_attributes( $partial, $state )` (pure): answers map to attributes of the
  same name with the store's enum values (null for a cleared sub-answer, which unsets it in Braze);
  a save touching platforms or handles sends `social_platforms` and `social_handle_<platform>` for all
  19 platforms (null where there is no handle, so a removed handle is removed in Braze); a language
  sends `language_preference`. A stage-only save sends nothing.
- `ihq_portal_onboarding_braze_send()`: POST to `ihq_braze_rest_endpoint() . '/users/track'` with
  `ihq_braze_track_api_key()`, the user's `wp_influencer_guid` as `external_id`, a 5-second timeout.
  A missing external_id is logged and skipped (no id is minted here; registration owns that). A
  `WP_Error` or non-2xx is logged and returns false; nothing throws.
- Hooked at priority 20 on `ihq_portal_onboarding_saved` and `ihq_portal_onboarding_completed`; the
  store fires completion once, so `onboarding_completed: true` is sent once.

## Alternatives considered
- Sending everything at completion: drop-outs' answers would never reach Braze (the ticket's point).
- A non-blocking request (`blocking => false`): faster, but failures could not be logged.
- WP-Cron: the save would not wait, but delivery would depend on site traffic; the 5-second cap keeps
  the wait bounded instead.

## Blast radius
One more `/users/track` call per answer save. The Influencer's save waits up to 5 s if Braze is slow.

## Notes
- OQ17 is pending Product, and Jenna is to confirm privacy for sending handles to Braze; the handle
  attributes are in this change as the ticket specifies.
- The Braze API keys are hard-coded fallbacks in `inc/braze-integration.php`; that is pre-existing and
  out of this ticket.
