---
name: IHQ modals iframe — SSO-authenticated game-portal embeds
overview: >
 The Competition tabs either had no game-portal embed at all (World, Leagues) or
 pointed at a hardcoded, unauthenticated leaderboards URL (Community, Private),
 so the iframes carried no hqSsoCode and showed the unfiltered board. Route every
 embed through ihq_build_hq_game_portal_external_url() using the per-tab paths the
 game-portal team published, and add the AC#5 "temporarily unavailable" fallback via
 a shared template part. Out of scope: hiding competition filters (done game-portal
 side, AC#6) and PO-2894 My Referrals (aborted — merged into the Equity table).
todos:
 - id: shared-embed-part
 content: Add template-parts/portal-external-embed.php + js/portal-external-embed.js watchdog
 status: completed
 - id: competition-tabs
 content: Wire World, Community, Private and Leagues embeds on page-portal-challenges.php
 status: completed
 - id: equity-fallback
 content: Move the Equity embed onto the shared part so it gains the fallback
 status: completed
 - id: fallback-styles
 content: Style .portal-embed-fallback for both portal templates
 status: completed
 - id: verify
 content: Verify on influencerhq.co — local hosts are not in the iframe allowlist
 status: pending
---

# [PO-2901] IHQ Modals iframe

**Ticket:** https://avantageusa.atlassian.net/browse/PO-2901
(children: [PO-2897](https://avantageusa.atlassian.net/browse/PO-2897),
[PO-2898](https://avantageusa.atlassian.net/browse/PO-2898),
[PO-2899](https://avantageusa.atlassian.net/browse/PO-2899),
[PO-2600](https://avantageusa.atlassian.net/browse/PO-2600),
[PO-2893](https://avantageusa.atlassian.net/browse/PO-2893))
**Drafted by:** Claude (Opus 5), Cursor

## Problem

Each Competition tab and the Equity page is meant to display the matching game-portal
modal in an iframe, authenticated by the SSO code the portal backend obtains at
login. In practice only the Equity page did this. The Challenges page hardcoded
`https://qc-game-portal-client-tf-b2c.dev.ae.games/av-baccarat/external/leaderboards`
for both the Community and Private tabs, which meant no `hqSsoCode`, no
`influencerHqAuth` flag, no respect for the user's own `hq_game_url` meta, and the
unfiltered leaderboard on both tabs. The World and Leagues tabs had no iframe at all.

## Approach

`ihq_build_hq_game_portal_external_url()` in `functions.php` already appends
`influencerHqAuth=true` plus the `hqSsoCode` read from the `ihq_sso_code` user meta,
so the fix is to use it everywhere with the correct per-tab path. Paths come from
Dejan Durlević's 16 Aug 2026 comment on PO-2850 and the route list on ENGR-5022;
the base URL already ends in `/av-baccarat`, so only the suffix is passed:

| Tab | Path passed |
| --- | --- |
| World | `/external/leaderboards` |
| Community | `/external/leaderboards/community` |
| Private | `/external/leaderboards/private` |
| Leagues | `/external/leagues-slider` |
| Equity | `/external/equity` (unchanged) |

All five embeds now render through `template-parts/portal-external-embed.php`, which
owns the iframe markup and the AC#5 fallback paragraph. `js/portal-external-embed.js`
reveals that paragraph when a frame fails to load.

## Alternatives considered

- **`/external/leagues/:league` for the Leagues tab.** Both PO-2850 and ENGR-5022 write
 `:league` as a bare placeholder and no ticket defines a value or an enumeration. The
 sibling `/external/leagues-slider` route needs no identifier and satisfies PO-2600's
 ACs, which never mention selecting a specific league.
- **Keeping per-page inline iframe markup.** Would have meant writing the AC#5 fallback
 five times.
- **Detecting failure by reading frame content.** Not possible cross-origin.

## Blast radius

- `page-portal-challenges.php` — the `$portal_leaderboards_iframe_url` variable is gone,
 replaced by the `$portal_embed_urls` map. The World tab's existing `#world-leaderboards`
 "See My Results" dropdown is untouched: `js/ihq-registry-gates.js` binds a gate to that
 id, so the new World embed uses `#world-leaderboards-embed` instead.
- The portal menu anchors (`#private-leaderboards`, `#community-leaderboards`) are
 preserved as wrapper ids on the new embeds.
- `page-portal-equity.php` — logged-out users still get the empty
 `#equity-external-embed` wrapper, as before.

## Notes

- **Verification is live-only.** Per sanjeevtamang on ENGR-5022, DevOps allowlisted
 `https://influencerhq.co` to frame these routes, so the embeds will not render from a
 local host.
- A frame refused by `X-Frame-Options` still fires `load` in Chrome, so the watchdog
 cannot catch that case; it catches network failure and a frame that never loads.
- Open question for the team: the World tab's menu entry "Results & Leaderboards" still
 scrolls to the mock `#world-leaderboards` dropdown rather than the new live embed.
 Repointing it is a UX decision beyond PO-2897's ACs.
