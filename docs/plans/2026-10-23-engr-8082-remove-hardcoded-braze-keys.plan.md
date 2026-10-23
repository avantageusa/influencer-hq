---
name: Remove IHQ's hardcoded Braze REST keys
overview: >
  Two Braze REST keys were committed in functions.php (commented-out legacy code) and are
  also the live fallbacks returned by ihq_braze_export_api_key() / ihq_braze_track_api_key()
  when wp-config doesn't define IHQ_BRAZE_EXPORT_API_KEY / IHQ_BRAZE_TRACK_API_KEY. Remove
  the fallbacks so the keys come only from wp-config, log a missing constant by name, and
  delete the legacy block. Out of scope: rotating keys (done in Braze and wp-config, not in
  code), other committed secrets (reported as findings), guarding callers against an empty key.
todos:
  - id: getters
    content: "inc/braze-integration.php: ihq_braze_api_key($constant) returns the constant or '' and error_logs the missing name once per request; export/track getters delegate to it; no key literal left"
    status: completed
  - id: legacy
    content: "functions.php: delete the commented-out check_braze_user_exists_influencer() and Genius Referrals set_user_role block that held both keys (and a Genius X-Auth-Token)"
    status: completed
  - id: readme
    content: "README per-instance block: add IHQ_BRAZE_EXPORT_API_KEY / IHQ_BRAZE_TRACK_API_KEY with the Braze permission each needs"
    status: completed
  - id: tests
    content: "tests/braze-api-key.test.php: undefined and empty constants return '' and log once; defined constant returned; no UUID-shaped literal in braze-integration.php or functions.php"
    status: completed
  - id: env
    content: "Set both constants in dev, QA and prod wp-config before merge (Steve / Milos), verified per env with a no-write Braze auth probe"
    status: in-progress
---

# [ENGR-8082] Remove IHQ's hardcoded Braze REST keys

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-8082
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem

The theme ships two Braze REST keys as fallbacks for the `IHQ_BRAZE_*_API_KEY` constants, and the
same keys sit in commented-out code in `functions.php`. They are public to anyone with repo access
and have been revoked, so every environment without the constants now gets 401 from Braze.

## Change

The key getters read only the wp-config constants. A missing or empty constant returns `''`, so
Braze answers 401 as it does today, and the error log names the constant once per request instead
of the failure being silent. The commented-out legacy block is deleted. Callers are unchanged: they
already log the Braze response code on failure.

## Merge gate

Merge only after both constants are set in every IHQ environment (dev, QA, prod). On 2026-10-23
the leaked track key still authenticated (HTTP 201 from a no-write probe on dev), so an environment
without `IHQ_BRAZE_TRACK_API_KEY` would lose users/track the moment this lands.
