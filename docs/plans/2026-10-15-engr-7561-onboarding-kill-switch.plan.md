---
name: Kill switch for the portal onboarding auto-launch
overview: >
  PO-3063 auto-launches Sami's onboarding on the Coach page for new Influencers. This adds a
  WordPress option, ihq_portal_onboarding_enabled, read through ihq_portal_onboarding_enabled(),
  that is off when absent so a deploy changes nothing until QA signs off. The entry gate that
  calls it is ENGR-7564; per-user targeting is out of scope.
todos:
  - id: switch-module
    content: Add inc/portal-onboarding-switch.php with the option name and a fail-closed reader, loaded from functions.php by an explicit require_once
    status: completed
  - id: tests
    content: Add tests/portal-onboarding-switch.test.php covering absent, on and off values
    status: completed
  - id: verify
    content: Run test:js, lint:js; PHP tests cannot run on this host (no PHP) and are reported as not run
    status: completed
---

# [ENGR-7561] Kill switch for onboarding auto-launch

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7561
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
The onboarding launches by itself for every eligible Influencer once the epic ships. Product
needs a way to keep it dark after deploy and switch it on per environment after QA.

## Approach
- `inc/portal-onboarding-switch.php`: `IHQ_PORTAL_ONBOARDING_ENABLED_OPTION = 'ihq_portal_onboarding_enabled'`
  and `ihq_portal_onboarding_enabled()`. Only explicit on values (`1`, `true`, `yes`, `on`) count;
  a missing option or any other value is off, so a mistyped WP-CLI command fails closed.
- Loaded by one explicit `require_once` in `functions.php`, like every other module there. Each later
  ticket of the epic adds its own `require_once` line, after any module it depends on. (A
  `glob( inc/portal-onboarding-*.php )` loop was tried first and dropped in review: it made load order
  depend on file names and would run any stray file matching the pattern.)
- ENGR-7564 (entry gate) checks `function_exists( 'ihq_portal_onboarding_enabled' ) && ihq_portal_onboarding_enabled()`,
  so either PR can merge first and the gate stays closed until both are in and the option is set.

## Alternatives considered
- GrowthBook flag: the theme reads none today (EQ3); adding the SDK is out of proportion.
- A PHP constant in wp-config: needs a config deploy per environment instead of one WP-CLI command.

## Blast radius
New file plus one `require_once` line in `functions.php`. Nothing reads the function until ENGR-7564 lands.

## Notes
DEPLOYMENT NOTE (from the ticket): leave the switch off on deploy; turn it on per environment
only after QA signs off the Verify story for that environment.
