---
name: PO-3343 — eliminate the AI Coach page's phantom scroll + believe-1's icon trio
overview: >
  Filip reported the AI Coach registration page still scrolls on mobile even
  after the header was stripped down, and separately that the old ElevenLabs
  PoC widget was still visible (PO-3342, handled separately). Investigating
  the scroll turned up a real root cause unrelated to any single screen's
  text length: .aicoach-stage never clipped its own inactive (but
  position:absolute) panels, so the page's scrollable height always matched
  whichever panel in the WHOLE sequence was tallest, regardless of which
  screen was actually showing. Fixed with one CSS property. Also restructures
  believe-1 to show all three belief icons together first (matching Figma
  "Belief 7"), per direction from Ivan/Dejan's 2026-09-29 Teams thread.
todos:
  - id: root-cause-scroll
    content: ".aicoach-stage had position:relative but no overflow:hidden.
      Inactive .aicoach-panel children are position:absolute (so they don't
      stretch the stage's own auto height) but without clipping, their
      bottom edges still extended the page's scrollable area to whichever
      panel was tallest overall (measured live: the comm-channels form,
      ~700px) — on EVERY screen, not just the tall ones. Added
      overflow:hidden to .aicoach-stage."
    status: completed
  - id: believe-1-icon-trio
    content: "believe-1 now shows all three belief icons (coin, chart,
      certificate) together above the 'We Believe' title on load, caption
      blank — matching Figma 'Belief 7'. After a fixed 7.2s dwell
      (startBelieveOneIntro() in js/aicoach-coach-flow.js; not the 3.5s first
      tried — see Notes), icons hide and the existing believe-1 caption text
      takes over, minus the opening two sentences already spoken during the
      icon phase. Explicitly the fallback Ivan signed off on instead of
      syncing text reveal to the actual seconds in we_believe_1.mp4."
    status: completed
  - id: new-assets
    content: "Re-exported the coin and certificate icons from Figma to match
      the filled illustration style now that they sit together
      (icon-belief-coin-v2.png, icon-belief-certificate.png). The chart icon
      is NOT re-exported yet — repeated Copy as SVG/PNG attempts on that one
      layer came back with an empty clipboard for reasons that were never
      resolved, even in a fresh tab; it stays the old outline-style icon as
      a placeholder (see Notes)."
    status: completed
  - id: verify
    content: "php -l / node --check clean. php tests/gary-proxy.test.php
      unaffected (untouched by this branch). Verified live on wp-env at
      375x812: believe-2 (the exact screen in Filip's screenshot) went from
      a real overflow to 812/812 (zero). believe-1's icon phase is 812/812;
      its text phase is 841/812 (~29px) — down from ~350-400px before this
      fix, and confirmed by hand that nothing is actually clipped (the
      panel's natural content height was already smaller than the stage
      before any of the extra tightening tried and reverted below).
      equity-magic/equity-bts/comm-channels still genuinely overflow
      (338px/203px/388px) — real content-heavy screens, a separate,
      legitimate scope from what this ticket was reported against."
    status: completed
---

# PO-3343 — eliminate the AI Coach page's phantom scroll + believe-1's icon trio

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude Code (Sonnet 5)

## Problem

Filip: "The scroll needs to go out of this page for real... Header was
removed, but the scroll is still there." Screenshotted specifically the
believe-2 ("We Believe") screen.

First measured this as a text-length problem — believe-1/believe-2's captions
are long, and a mobile viewport is short. That framing was wrong. Live
measurement showed believe-2 (a single short-ish caption, one icon) already
overflowed by ~350px on its own — far more than its actual content could
account for.

## Approach

Instrumented the DOM directly rather than guessing: measured every
`.aicoach-panel`'s real bounding rect regardless of which one was
`.is-active`, and found several **inactive** panels (`comm-channels`, the
equity story panels) extending well past the visible fold — up to 714px on
a 375-wide viewport. `.aicoach-panel:not(.is-active)` is `position:absolute`
(so it doesn't stretch `.aicoach-stage`'s own auto-height), but nothing
clipped those absolutely-positioned elements' overflow, so the **page's**
scrollable area still had to accommodate them. Every screen — even a
one-line caption — inherited scroll room sized to the single tallest panel
in the entire sequence.

Fix: `overflow: hidden` on `.aicoach-stage`. Confirmed live this doesn't
clip any *active* panel's own real content — `.aicoach-believe`'s natural
content height was already smaller than the stage's rendered height before
this change, with or without the extra font/spacing tightening tried below.

Separately, restructured believe-1 per Ivan's direction (Teams,
2026-09-29): show the coin/chart/certificate trio together first (matching
the Figma "Belief 7" frame), then switch to the existing full caption text
once the opening line has had time to be said.

## Alternatives considered

- **Shrink fonts/avatar/margins further to force every screen under one
  viewport height.** Tried first, before finding the real cause — measured
  the believe-1 text-phase at ~400px of real overflow and started
  compensating with a smaller avatar (120px), smaller icons (48px), smaller
  caption font (0.92rem) and tighter line-height. Once `overflow:hidden`
  alone brought that same screen down to ~29px, this extra compaction was
  reverted — it bought less than the real fix and cost readability for no
  remaining benefit.
- **Sync believe-1's icon-to-text switch to the actual second in
  we_believe_1.mp4 where the opening line ends, per-locale.** This is the
  "correct" version and is exactly what Ivan called out as the real fallback
  target. Not fully done here — what shipped instead is a single constant
  (7.2s) measured off the *English* clip only (see Notes), not a real
  per-segment timestamp source covering every language. Good enough for the
  fallback Ivan agreed to ship first; still simpler than true per-locale
  sync.
- **Force the chart icon into the new filled style via a manual redraw**
  instead of leaving it as the old outline icon. Rejected for now — the
  Figma re-export kept failing for reasons never root-caused (every other
  icon on the same frame exported fine via the identical steps), and
  guessing at a redraw risks shipping something that doesn't match the
  Figma source once it's actually available.

## Blast radius

- `page-home-aicoach.php`: one new CSS property (`overflow: hidden` on
  `.aicoach-stage`) that affects every screen; believe-1's markup gains an
  icons wrapper (was a single `<img>`); two new image assets referenced.
  Mobile `@media` block is otherwise unchanged from before this ticket.
- `js/aicoach-coach-flow.js`: one new self-contained function
  (`startBelieveOneIntro()`) and the two existing per-screen caption-set
  call sites in `runFallback()` now skip believe-1 (that function owns it
  instead). Nothing else in the sequencing loop changes.
- `images/aicoach/`: two new PNGs added; the four existing belief/check/x
  icon files are untouched (chart icon still points at the old SVG).

## Notes

**Still a real, separate gap:** equity-magic, equity-bts, and comm-channels
all still genuinely overflow a 375×812 viewport (338px/203px/388px) — these
have enough real content (images, story points, 8 channel options) that no
amount of the kind of tightening this ticket did would fix them without
either redesigning those screens or accepting scroll on them specifically.
Not attempted here; flagging as its own follow-up rather than silently
leaving it undiscovered.

**Chart icon** stays the old outline-style SVG for now — see the
"alternatives considered" note above. Swap `belief-chart` in
`page-home-aicoach.php`'s `$aicoach_img` array to a new filled-style export
once one exists; nothing else needs to change.

**believe-1's dwell was originally a 3.5s guess** — caught live (Dejan,
2026-09-29): "quickly switches to the next part and doesn't finish the
opening sentence." Re-measured directly off the real `we_believe_1.mp4`
(English) with Web Audio: decoded the clip and scanned RMS volume in 100ms
windows for the silence gaps between sentences. The two opening sentences
run to ~7.1s, confirmed independently by their ~17% share of the script's
word count landing at the same point in the clip's ~31.8s of speech. Bumped
to 7200ms. Only measured for English — every other locale's clip has its
own pacing and still uses this same constant, worth a real per-locale audio
check before calling this fully done everywhere, not just for English.
