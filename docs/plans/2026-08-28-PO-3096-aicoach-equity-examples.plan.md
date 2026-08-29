---
name: PO-3096 — Equity examples per time tier (FR-05)
overview: >
  Replaces the old static, tier-key-indexed demo panels (data-panel="2"/"5"/"10", which didn't
  actually match FR-05's real per-tier content rules — "5" showed Magic Johnson, not BTS) with
  three correctly-scripted, coach-narrated equity screens (Magic Johnson, Alix Earle, BTS), queued
  dynamically after the FR-03 tier confirm: 10-minute tier gets all three in order, 5- and 2-minute
  tiers get BTS only. Reuses the existing SCREENS/runSequence/runFallback machinery rather than a
  new code path — the tier click appends the right screens and the same loop consumes them.
todos:
  - id: remove-stale-panels
    content: Delete the old data-panel="2"/"5"/"10" static demo panels — their content didn't match FR-05 and would be confusing dead code once real equity screens exist
    status: completed
  - id: three-equity-panels
    content: Add equity-magic / equity-alix / equity-bts panels with name-in-gold + image + check/x comparison (matching the existing established pattern) + a caption element each
    status: completed
  - id: alix-placeholder-asset
    content: No licensed Alix Earle photo exists anywhere in this repo — added a flat placeholder SVG (silhouette + "PHOTO PENDING" label), not a fabricated likeness
    status: completed
  - id: equity-screens-data
    content: EQUITY_SCREENS map (magic/alix/bts scripts) + getEquityScreensForTier(tierMinutes) resolving the Scenario 7/8 branching
    status: completed
  - id: dynamic-queue-on-confirm
    content: Tier click now pushes the resolved equity screens onto SCREENS instead of jumping straight to a static panel, letting the existing sequence loop pick them up
    status: completed
  - id: resume-after-loop-exit
    content: Found via live testing that pushing onto SCREENS only works while the loop is still active; added explicit resume (re-invoking runSequence/runFallback) for the case where the visitor takes long enough to confirm that the loop already exited naturally
    status: completed
  - id: one-time-confirm-guard
    content: tierConfirmed flag prevents a second tier click (e.g. a fast double-click on a different tier) from queuing a second, redundant batch of equity screens
    status: completed
  - id: verify
    content: php -l, composer lint:php (only the known unrelated failure), phpcs (494 vs 497 baseline, same pre-existing convention), and — critically — live end-to-end testing in a local wp-env WordPress instance rather than a static mockup, including catching and fixing the resume bug above
    status: completed
---

# PO-3096 Equity examples per tier — Scenario 7/8 (FR-05)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3096 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Problem

The page already had three static "story" panels left over from early mockup work, keyed directly
to the tier value (`data-panel="2"/"5"/"10"`) — but their content didn't actually implement FR-05's
real rule (5-minute tier showed Magic Johnson, when the spec says BTS-only for both 5- and 2-minute
tiers; there was no Alix Earle screen at all; none of them were spoken by the coach or captioned).
FR-05 needs: 10-minute tier → Magic Johnson, then Alix Earle, then BTS, each coach-narrated with
synced captions; 5- and 2-minute tiers → BTS only.

## Approach

1. **Removed the stale panels entirely** rather than leaving them alongside the real ones — they
   were wrong-content placeholders for exactly this FR, not a separate concern to preserve.
2. **Three new panels** (`equity-magic`, `equity-alix`, `equity-bts`), reusing the established
   name-in-gold / image / check-cross-comparison layout (kept from the old Magic Johnson/BTS
   panels, since that part of the layout was already correct), each with an added
   `.aicoach-caption` for synced narration text.
3. **Tier-dependent queuing, not a new code path.** `getEquityScreensForTier(tierMinutes)` resolves
   the Scenario 7/8 branching; the FR-03 tier-click handler pushes the result onto the shared
   `SCREENS` array. The existing `runSequence`/`runFallback` loops just keep consuming `SCREENS` —
   no separate "equity examples" runner was needed.
4. **Alix Earle placeholder.** No licensed photo of her exists anywhere in this repo (checked).
   Fabricating a photorealistic image of a real named person isn't something to do quietly (or at
   all) — added a simple flat silhouette-card SVG labeled "PHOTO PENDING" instead, same honesty
   standard as PO-3092's video gap and PO-3093's belief icons. Swap `$aicoach_img['alix']` for the
   real asset once design/legal supplies one.

## A real bug found and fixed via live testing

Initially, queuing worked by only pushing onto `SCREENS` and relying on the in-flight loop to
notice the new items on its next iteration. **Live testing in a local wp-env instance** (not a
static mockup) caught a real gap: if the visitor takes long enough to confirm a tier that the
loop has *already exited* (natural end of the FR-03 line, `finishSequence()` already ran), nothing
is left running to pick up the newly-queued screens — they'd silently never play. Fixed by
capturing `sequenceFinished` right before the push (`loopAlreadyExited`) and, if true, explicitly
re-invoking `runSequence(activeClient)` or `runFallback()` (tracked via new `activeClient` /
`usingFallback` state) to resume. Verified both branches live with instrumented timestamps
(`performance.now()`) before removing the debug logging — confirmed the natural 9000ms-per-screen
fallback pacing is exact, and both the in-flight and already-exited resume paths correctly reach
the right equity screens for both the 10-minute (all three, in order) and 5-minute (BTS only) tiers.

Also added a `tierConfirmed` one-shot guard — without it, a fast second click on a different tier
before the panel visually transitions away would queue a second, redundant batch of screens.

## Alternatives considered

- **A dedicated "equity examples" runner function**, separate from `runSequence`/`runFallback`:
  rejected — the existing loops are already generic over "whatever's in SCREENS"; a second runner
  would duplicate the caption-building/tap-to-advance/fallback-resume logic for no real benefit.
- **Keeping the old static panels around** for some other future use: rejected — their content is
  simply wrong for FR-05, not a different concern; keeping mismatched dead code invites confusion.

## Blast radius

- `page-home-aicoach.php`: three panels replaced (net markup change, no other panels touched),
  one new image array entry (`alix`, placeholder).
- `js/aicoach-coach-flow.js`: additive except for the tier-click handler, which now queues instead
  of directly calling `showPanel`; `finishSequence()` no longer forces `showPanel('home')` (that
  destination stopped making sense once tier-specific content follows it — see Notes).
- New file: `images/aicoach/alix-earle-placeholder.svg`.

## Notes

- **`finishSequence()` no longer navigates anywhere.** It used to snap back to `home` once the
  original fixed 4-screen sequence ended. Now that confirming a tier appends more content, `home`
  is no longer a sensible "end of sequence" destination — the flow simply stops on whatever the
  last available screen is. FR-06 (competition types) or FR-07 (identity capture) will extend
  `SCREENS` the same way this story does, once those stories exist; nothing to build here yet.
- Same **known-interim-architecture** note as PO-3092/93/94 — still driving Sami directly via
  Anam, approved as temporary by Filip Milinković pending a separate engineer's ("Gary") agent API.
- **Alix Earle image is a placeholder** — flag for design/legal to supply a real licensed asset.
