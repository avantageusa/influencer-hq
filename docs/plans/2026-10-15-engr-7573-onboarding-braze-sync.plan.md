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
  - id: review-fixes
    content: PR #130 review — send on shutdown after fastcgi_finish_request(), log status code only, assert log lines, named constants, idempotent completion documented and tested
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
  `WP_Error` or non-2xx is logged (status code only, never Braze's response body, which could echo
  handles) and returns false; nothing throws.
- Hooked at priority 20 on `IHQ_PORTAL_ONBOARDING_SAVED_ACTION` and
  `IHQ_PORTAL_ONBOARDING_COMPLETED_ACTION`. The listeners only queue the attributes. On `shutdown`
  (last, at `PHP_INT_MAX`) the queue is sent in order, after `fastcgi_finish_request()` where it exists,
  so under PHP-FPM the Influencer's save response does not wait for Braze. A request that queued
  nothing does not end the response early.
- Completion is idempotent: it sets `onboarding_completed: true` and nothing that changes between
  calls (no timestamp, event or counter). The store fires completion once per user in normal use, but
  two concurrent completions can both fire it (user meta has no compare-and-set); Braze then receives
  the same value twice and ends in the same state.

## Alternatives considered
- Sending everything at completion: drop-outs' answers would never reach Braze (the ticket's point).
- A non-blocking request (`blocking => false`): faster, but failures could not be logged.
- WP-Cron: the save would not wait, but delivery would depend on site traffic.
- A synchronous send inside the save (the first version): simplest, but each answer save could wait
  up to 5 s on a slow Braze (PR #130 review). Sending on `shutdown` after `fastcgi_finish_request()`
  keeps the logging and takes the wait off the response.

## Blast radius
One more `/users/track` call per answer save, made after the response. Under PHP-FPM the PHP worker
stays busy up to 5 s more when Braze is slow, but the Influencer does not wait. Without
`fastcgi_finish_request()` (mod_php, CLI) the call still runs at shutdown and the response can wait up
to 5 s, as before. Delivery is best effort: a failed send is logged, not retried.

## Notes
- OQ17 is pending Product, and Jenna is to confirm privacy for sending handles to Braze; the handle
  attributes are in this change as the ticket specifies.
- The Braze API keys are hard-coded fallbacks in `inc/braze-integration.php`; that is pre-existing and
  out of this ticket.
