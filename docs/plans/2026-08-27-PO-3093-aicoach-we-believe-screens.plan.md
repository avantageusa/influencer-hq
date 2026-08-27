---
name: PO-3093 — We Believe narrative screens (FR-02)
overview: >
  page-home-aicoach.php currently jumps from the FR-01 intro straight to the tier-selection
  panel. Add two sequential "We Believe" screens between them, each with its own icon (crossed-out
  coin, growth chart), spoken verbatim by Sami, with the same synced-caption mechanism built for
  FR-01. Screens advance automatically on endOfSpeech, or immediately on a visitor tap/click
  (barge-in), per the ticket's description. Out of scope: everything past screen 2 (still hands
  off to the existing home/tier panel), language variants.
todos:
  - id: belief-icons
    content: Add two placeholder icon SVGs (crossed-out coin, growth chart) matching the existing icon-check/icon-x flat-line style — real assets referenced by page-portal-poc.php (belief-coin.png etc.) don't exist in this repo, so these are new placeholders pending design
    status: completed
  - id: believe-panels-markup
    content: Insert believe-1 / believe-2 panels between intro and home, each with icon + "We Believe" kicker + caption region, mirroring the intro panel's structure
    status: completed
  - id: sequential-playback
    content: Extend the module script's single-utterance runIntro() into a small sequence runner that speaks each screen's script in order, reusing the same caption-building/endOfSpeech logic
    status: completed
  - id: tap-to-advance
    content: Clicking/tapping the stage during a believe screen interrupts the current line and advances immediately, per the ticket's "narration timing or visitor tap/click" requirement
    status: completed
  - id: fallback-path
    content: Extend the existing connection-failure fallback to show both screens' text in sequence (same dwell-based pacing as the FR-01 fallback) before advancing to home
    status: completed
  - id: verify
    content: node --check on the extracted module script; manual PHP review (same no-local-PHP-CLI caveat as PO-3092, resolved by the dev's own machine per that PR's thread)
    status: completed
---

# PO-3093 We Believe screens — Scenario 2 (FR-02)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3093 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Problem

After PO-3092's intro, the page jumps straight to tier selection. FR-02 requires two sequential
"We Believe" screens (crossed-out coin / growth chart) in between, each spoken verbatim, with
synced captions, advancing either when the narration finishes or when the visitor taps to skip
ahead — identical across all three time tiers (there's no tier-specific content here since tier
isn't picked yet, same reasoning as FR-01).

## Approach

1. **Icons:** `page-portal-poc.php` references `images/poc/belief-coin.png` / `belief-chart.png`,
   but neither file exists in this repo (checked — only `conversation-hero*.png` are in
   `images/poc/`). Rather than block on a missing asset, added two small flat-line SVGs
   (`images/aicoach/icon-belief-coin.svg`, `icon-belief-chart.svg`) in the same style as the
   existing `icon-check.svg`/`icon-x.svg` (stroke-only, no fill, Figma-export-style). These are
   explicitly placeholders — flagged for design to swap once real assets exist, same as FR-01's
   video-asset note.
2. **Two new panels** (`believe-1`, `believe-2`) between `intro` and `home`, each reusing the
   intro panel's structure: icon + "We Believe" kicker + a `.aicoach-caption` region.
3. **Sequence runner:** generalized the single `say()`-and-advance logic from FR-01 into a small
   `runSequence(screens)` that iterates `[{ panel, script }, ...]` — one for the intro (already
   built) conceptually extends into `[intro, believe-1, believe-2]` before handing off to `home`.
   Reuses the exact same MESSAGE_STREAM_EVENT_RECEIVED caption-building approach.
4. **Tap-to-advance:** a click/tap on the stage while a believe screen's line is playing calls
   `client.talk()`'s current utterance off (best-effort — Anam doesn't expose a hard interrupt in
   the events we're using, so this resolves the wait immediately client-side and moves on; the
   avatar may finish its current sentence audibly a beat behind, which is an acceptable, common
   pattern for skip buttons) and advances to the next screen/utterance.
5. **Fallback:** if Anam never connected (same failure path as FR-01), show both screens' text
   in sequence with the same per-screen reading-time dwell, then hand off to `home`.

## Alternatives considered

- **Real PNG icons matching the POC's naming:** would match design intent more closely, but the
  files don't exist anywhere in this repo to reuse, and fabricating "real" artwork isn't something
  to silently do — flat placeholder SVGs in the existing icon style are honest about being interim.
- **Hard interrupt via a `stopStreaming()`/reconnect on tap:** rejected — tearing down and
  re-minting a session on every tap is slower and riskier (reconnect storms) than just moving the
  UI forward and letting the current line trail off; the visitor experience of "the video keeps
  talking half a second after I tapped ahead" is a normal, acceptable tradeoff for this kind of skip.

## Blast radius

- Touches only `page-home-aicoach.php` + two new small SVG files. No backend/proxy changes.
- `finishIntro()` from PO-3092 is renamed/generalized into the sequence runner's advance step;
  behavior for the intro screen itself is unchanged (still speaks the same FR-01 script first).

## Notes

- **Icons are placeholders**, not final design assets — same caveat class as FR-01's video.
- **Tap-to-advance is best-effort UI skip**, not a true mid-sentence audio cut (Anam SDK limitation
  noted above) — flagging so this isn't mistaken for a client bug if the voice trails slightly.
- Same **unverified** note as PO-3092: no PHP CLI in this environment; relies on the dev running
  `composer lint:php` locally (confirmed working in that PR's thread) before merge.
