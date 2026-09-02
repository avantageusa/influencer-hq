---
name: PO-3110 — Mobile loading performance targets for Asia (NFR-01)
overview: >
  Reduces the AI Coach landing page's initial image payload and defers the heaviest equity-example
  images (Magic Johnson, BTS) until their screen is actually reached, instead of all being fetched
  eagerly on first paint regardless of which tier/screens the visitor ever gets to. Independent of
  the Gary API migration and Luna-dependent tickets (PO-3102/3103/3106) currently blocked — this
  ticket touches only image assets and generic panel-display code already in this repo.
todos:
  - id: convert-images-to-webp
    content: Convert coach-portrait.png, bts.jpg, magic-johnson.png to WebP (67-89% smaller each), remove the superseded originals
    status: completed
  - id: defer-heavy-equity-images
    content: "magic-johnson.webp and bts.webp switched from eager src to data-src, hydrated into a real src only when their panel is actually shown (new hydratePanelImages() helper called from showPanel())"
    status: completed
  - id: prioritize-lcp-image
    content: Added fetchpriority=\"high\" to the coach portrait image (the only image visible on first paint, so the actual LCP candidate) — left eager, not deferred
    status: completed
  - id: verify
    content: php -l, node --check, phpcs (negligible delta, same pre-existing space-indentation debt), live wp-env testing confirming the deferred images are absent from the initial page load's network requests and load correctly once their screen is reached
    status: completed
---

# PO-3110 Mobile loading performance for Asia — Scenario 33 (NFR-01)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3110 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Problem

Every screen, across all three time tiers, must meet LCP ≤ 2.5s and FCP ≤ 1.8s on mobile from
Asian geographies (4G/WiFi) — a market where round-trip latency and bandwidth are both worse than
the US/EU. The AI Coach page currently ships every screen's images in the initial page load
regardless of whether the visitor ever reaches that screen, and one of them (`magic-johnson.png`,
567KB) is badly oversized for its 596×844 display size.

## Approach

1. **Convert the three raster images to WebP.** `coach-portrait.png` (79KB→20KB, the LCP
   candidate — the only image visible before any interaction), `bts.jpg` (133KB→88KB), and
   `magic-johnson.png` (567KB→61KB, the biggest single win — it was PNG for a photograph, no
   transparency actually in use). Total `images/aicoach/` payload: ~779KB → ~169KB. The small
   icon SVGs (belief/check/x/trophy, all under 1KB) weren't worth touching.
2. **Defer the two heaviest images until their screen is actually reached.** All `.aicoach-panel`
   screens exist in the DOM from page load and are toggled via `opacity`/`visibility` (not
   `display:none` — that's what makes the 800ms fade transition possible), so the browser's native
   `loading="lazy"` never actually defers anything here: a `visibility:hidden` element is still
   laid out in the viewport, so browsers fetch its images immediately regardless of the `loading`
   attribute. Worked around this by switching `magic-johnson.webp`/`bts.webp` to `data-src` instead
   of `src`, and adding a small `hydratePanelImages()` helper — called at the top of the existing
   `showPanel()` in `js/aicoach-coach-flow.js` — that swaps `data-src` into a real `src` the first
   time a panel is actually requested. Both a visitor on the 2-minute tier (which per the existing
   `getEquityScreensForTier()` logic only ever shows the BTS example) and one on a longer tier now
   only pay for the images their own path through the flow actually uses.
3. **Keep the portrait image eager, and mark it high priority.** It's the only image visible
   before any interaction (the idle avatar state), so it's the actual LCP candidate — added
   `fetchpriority="high"` rather than deferring it.
4. **Left the avatar video alone.** `#aicoach-avatar-video` has no `src` in markup at all — it's
   populated at runtime by the Anam SDK's `streamToVideoElement()` once a live session connects.
   It isn't a static asset this repo hosts or could put behind our own CDN, so the ticket's "avatar
   video assets must be optimized and delivered via CDN" requirement doesn't apply to the current
   interim architecture — see Notes.

## Alternatives considered

- **Native `loading="lazy"` on the `<img>` tags**: tried first, rejected once live-testing showed
  it has no effect here — confirmed by checking the panel CSS (`opacity`/`visibility`, not
  `display:none`) and cross-checking against actual network requests, which still fetched
  everything eagerly with `loading="lazy"` present. The `data-src` + JS-hydration approach was
  necessary given this page's fade-transition architecture.
- **Changing hidden panels to `display:none`** (which *would* make native lazy-loading work):
  rejected — that's the exact CSS property the existing 800ms fade transition depends on
  (`transition: opacity 0.4s ease, visibility 0.4s ease`), used by every screen in this epic since
  PO-3092. Changing it risks regressing the fade animation across the whole flow for a performance
  ticket that doesn't need to touch it.
- **Resizing `magic-johnson.png`'s pixel dimensions down further**: not needed — 900×1272 native
  for a 596×844 display size is already a reasonable ~1.5x density, not oversized once the format
  switched from PNG to WebP; the format was the actual problem, not the resolution.

## Blast radius

- `images/aicoach/`: three files converted png/jpg → webp, originals removed (confirmed via grep
  that nothing else in the repo referenced them — the one other `bts.jpg` hit found was an
  unrelated file at `images/bts.jpg` used by `page-lander.php`).
- `page-home-aicoach.php`: `$aicoach_img` array paths updated to the new `.webp` filenames;
  `magic-johnson`/`bts` `<img>` tags changed from `src` to `data-src`; portrait `<img>` gained
  `fetchpriority="high"`.
- `js/aicoach-coach-flow.js`: one new generic helper (`hydratePanelImages()`) called once at the
  top of `showPanel()` — touches shared code, but is purely additive (a no-op for any panel with
  no `data-src` images) and verified the existing tier-2/BTS-only flow still works correctly.

## Notes

- **"Avatar video assets... delivered via CDN" is not applicable to the current build.** The
  avatar is a live Anam-streamed video (WebRTC-style, minted via a session token), not a file this
  repo serves — there's nothing to put behind a CDN on our side today. This part of NFR-01 seems to
  target the eventual pre-rendered-video architecture from the earlier Confluence spike (ENGR-5831)
  or whatever Gary's real Coach API integration ends up delivering, not the current interim direct
  Anam SDK integration. Flagging this gap explicitly rather than silently treating the ticket as
  fully done — worth raising with Filip whether this sub-requirement is deferred along with the
  rest of the Gary API migration, or considered out of scope for the interim build entirely.
- **"All seven languages" isn't verifiable yet** — only English exists in the flow today (FR-13's
  language selector, PO-3104, isn't built). This PR's optimizations apply to whatever content
  exists regardless of language, but full per-language verification has to wait for that work.
- **LCP/FCP weren't independently measured against the ticket's numeric targets in this session.**
  Verified directly: the byte-size reduction (via file sizes) and the deferred-loading behavior
  itself (via live wp-env network-request inspection — confirmed `magic-johnson.webp`/`bts.webp`
  are absent from the initial page load's requests, and `bts.webp` loads correctly once its screen
  is reached). Did not get reliable synthetic LCP/FCP numbers from this local environment (browser
  caching and dev-server quirks made the Performance API readings unusable for a clean before/after
  comparison) — a real Lighthouse mobile-throttled run or a WebPageTest pass from an Asia test
  location is needed before this ticket can be called fully verified against Scenario 33's actual
  numeric targets. Flagging this rather than claiming a target hit without real measurement.
- Found and flagged separately (not fixed here, out of scope for a performance ticket): clicking a
  time-tier radio extremely early — before the intro/believe screens finish — appears to skip the
  equity-magic screen from the queued sequence for longer tiers, while equity-bts (queued right
  after it) still plays. Pre-existing gap in the tier-click handler's `SCREENS.push()` timing, not
  introduced by this change. Spawned as a separate follow-up task.
- Same known-interim-architecture note as every prior story in this epic — unaffected either way,
  this change doesn't touch the Anam integration at all.
