---
name: PO-2901 review follow-ups — World, Leagues, portal base
overview: >
  PR #24 left the World tab on a broken inline iframe and never rendered the
  Leagues embed, and the profile page still showed a hardcoded QC portal host.
  Route both tabs through the shared embed so they use IHQ_GAME_PORTAL_BASE_URL,
  and show that same constant as the profile default. Out of scope: removing
  hqSsoCode from the iframe URL (needs a game-portal POST bootstrap) and
  PO-2894 My Referrals.
todos:
  - id: world-shared-embed
    content: Replace the World inline iframe with template-parts/portal-external-embed and $portal_embed_urls['world']
    status: completed
  - id: leagues-shared-embed
    content: Render the Leagues panel through the shared embed using $portal_embed_urls['leagues']
    status: completed
  - id: portal-base-from-env
    content: Show IHQ_GAME_PORTAL_BASE_URL as the profile and wp-admin Game Portal URL default
    status: completed
  - id: sso-query-handoff
    content: Move hqSsoCode off the iframe URL once the game portal accepts a POST bootstrap
    status: blocked
  - id: verify
    content: Verify World and Leagues embeds on influencerhq.co — local hosts are not in the iframe allowlist
    status: pending
---

# [PO-2901] Embed review follow-ups

**Ticket:** https://avantageusa.atlassian.net/browse/PO-2901
**PR:** https://github.com/avantageusa/influencer-hq/pull/24
**Drafted by:** Cursor (Grok 4.7)
**Parent plan:** [2026-09-15-PO-2901-ihq-modals-iframe.plan.md](2026-09-15-PO-2901-ihq-modals-iframe.plan.md)

## Problem

CodeRabbit's 21 Sep review on PR #24 still found the World and Leagues panels outside the shared embed contract. World rendered an inline iframe whose `src` was `$portal_leaderboards_iframe_url`, a variable nothing sets, so the frame was empty and had no fallback. Leagues built `$portal_embed_urls['leagues']` and never rendered it. The profile "Default" line still hardcoded the QC game-portal host, so QA and prod would display the wrong base even though embeds already read `IHQ_GAME_PORTAL_BASE_URL`.

## Approach

Shipped in `b3d4efb` (`fix: [PO-2901] route World and Leagues embeds through the shared portal iframe`).

World's leaderboard section and the Leagues standings block (`#leagues-results`) both call `template-parts/portal-external-embed` with the URLs already built in `$portal_embed_urls`. Those URLs come from `ihq_build_hq_game_portal_external_url()`, which takes its base from `ihq_get_hq_game_portal_base_url()` → `IHQ_GAME_PORTAL_BASE_URL`. The theme does not branch on `IHQ_ENVIRONMENT`; each WP Engine instance supplies its own constant.

The profile placeholder and the wp-admin Game Portal URL description use `ihq_env_require_url( 'IHQ_GAME_PORTAL_BASE_URL' )` instead of a QC literal.

| Tab | Path | Where it renders |
| --- | --- | --- |
| World | `/external/leaderboards` | `page-portal-challenges.php`, wrap `#world-leaderboard-iframe` |
| Leagues | `/external/leagues-slider` | `page-portal-challenges.php`, under `#leagues-results`, wrap `#leagues-standings` |

## Alternatives considered

- **Stripping `hqSsoCode` in the theme.** The portal identifies the user from that query parameter. Removing it loads the embeds logged out. A POST into the iframe that the portal exchanges for an HttpOnly cookie is the real fix, and it needs a portal route this theme does not have.
- **Ignoring `hq_game_url` so embeds always use wp-config.** A saved per-user Game Portal URL still overrides the constant. Retiring that override is PO-3073, not this follow-up.
- **Adding a My Referrals iframe.** The 15 Sep plan records PO-2894 as aborted and folded into the Equity table.

## Blast radius

- `page-portal-challenges.php` — World no longer has its own iframe markup. `#world-leaderboards` (the mock "See My Results" dropdown and its registry gate) is unchanged. The menu item "Results & Leaderboards" still scrolls there, not to the live embed.
- `page-portal-profile.php` and the wp-admin user profile field — display only. Saving a Game Portal URL still overrides the constant for every embed and the header "Go to game" link. Blank still means the wp-config value.
- Private, Community, and Equity embeds were already on the shared template and were not rewritten.

## Notes

- Already on the branch before this follow-up, from `c6a9410`: frames use `data-src` until `js/portal-external-embed.js` binds load/error, and `[data-ihq-external-embed] iframe[hidden]` hides a failed frame. Do not redo those.
- Verification is live-only. DevOps allowlisted `https://influencerhq.co` to frame these routes (ENGR-5022). A local host will not show the portal inside the frame.
- `hqSsoCode` stays on the iframe URL and on the header "Go to game" link until the game portal can consume the code from a POST body, set its own cookie, and redirect to a clean path. The stored `ihq_sso_code` user meta has no expiry.
