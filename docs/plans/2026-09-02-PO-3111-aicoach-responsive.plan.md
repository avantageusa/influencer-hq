---
name: PO-3111 — Fully responsive across mobile/tablet/desktop (NFR-02)
overview: >
  Audits the AI Coach flow across the three required breakpoints (360px mobile, 768px tablet,
  1280px desktop) and fixes one real bug found during that audit: a hardcoded 185px top padding
  that overlapped the page's own content once the shared site header wraps onto multiple lines on
  narrow screens. The rest of the flow's CSS was already built with flexible/relative units and
  passed the audit cleanly.
todos:
  - id: audit-all-panels-all-breakpoints
    content: "Checked all 13 .aicoach-panel screens at 360px/768px/1280px for horizontal overflow, clipped content, and interactive-element tap-target size — zero overflow found anywhere; tap targets meet or exceed WCAG AA's 24px minimum"
    status: completed
  - id: fix-header-overlap-on-mobile
    content: "Found via visual screenshot testing (missed by the overflow-only DOM check, since it's a vertical/z-index issue not a horizontal one): .aicoach's hardcoded padding-top:185px broke on mobile once the shared sticky-header's nav wraps onto 3 lines and grows taller than 185px, overlapping the page's own intro text"
    status: completed
  - id: wire-into-dynamic-header-offset
    content: "Added id=\"portal-content\" to the page's outer container (matching every other portal-* page template) and removed the now-redundant hardcoded top padding, so the existing adjustContentPadding() mechanism in portal-header.php — which measures the sticky header's real rendered height — handles clearance correctly at every breakpoint instead of a magic number"
    status: completed
  - id: verify
    content: php -l, phpcs (negligible delta, same pre-existing space-indentation debt), live wp-env visual screenshots at 360px/768px/1280px confirming the fix and no regressions, DOM-based overflow/tap-target sweep across all 13 panels
    status: completed
---

# PO-3111 Fully responsive across devices — Scenario 34 (NFR-02)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3111 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Problem

The entire AI Coach flow must render correctly and be fully usable at mobile (360px+), tablet
(768px+), and desktop (1280px+) — no overflow, no truncation, all interactive elements reachable.

## Approach

1. **Structural audit first.** Every `.aicoach-panel` (13 total: intro, believe-1/2, home,
   equity-magic/alix/bts, competition-world/community/private, identity, comm-channels,
   final-continue) was force-activated one at a time at each of the three required viewport
   widths, checking for horizontal overflow (`getBoundingClientRect().right` past the viewport),
   clipped content (`overflow:hidden` elements whose `scrollWidth`/`scrollHeight` exceeds their
   own box), and interactive-element size against WCAG AA's 24px minimum tap target. Also checked
   the comm-channels panel with all 8 channel fields revealed simultaneously, the most complex
   dynamic layout state in the flow. Result: **zero overflow anywhere**, tap targets already at or
   above the 24px minimum (tier radios: 320×36, channel checkboxes: 284×24). A `grep` for
   hardcoded pixel widths (excluding `min-width`/`max-width`) also came back empty — the flow's
   CSS was already built with flexible units throughout.
2. **Visual screenshot testing caught what the structural check couldn't.** The overflow check
   only looks at horizontal (`right` edge) overflow — it's blind to vertical/z-index overlap.
   A screenshot at 360px showed the page's own "We believe conversations should be easy." text
   partially hidden behind the site's shared nav, which wraps onto 3 lines at that width.
3. **Root cause: a hardcoded magic number, not a missing responsive rule.**
   `template-parts/portal-header.php` already has a JS `adjustContentPadding()` function that
   measures the sticky header's actual rendered height (`getBoundingClientRect().bottom`) and sets
   `padding-top` on `#portal-content` accordingly — exactly the mechanism needed for a header whose
   height varies by breakpoint. But `page-home-aicoach.php` never gave its content wrapper the
   `id="portal-content"` that mechanism looks for, so it silently no-ops on this page, leaving the
   page reliant entirely on its own `.aicoach { padding: 185px 0 ... }` — a value that happened to
   clear the single-line desktop nav but not the 3-line mobile one.
4. **Fix: join the existing pattern instead of inventing a page-specific one.** Every other
   `page-portal-*.php` template already puts `id="portal-content"` on its outer container and
   carries no extra hardcoded top padding of its own — confirmed by checking
   `page-portal-equity.php` as a reference. Added the same `id` to this page's outer container and
   removed the hardcoded `185px` (kept the `40px` bottom padding, which wasn't related to the
   header-clearance problem).

## Alternatives considered

- **A page-specific `@media` rule reducing the 185px at narrow widths**: rejected — that's
  patching a magic number with another magic number; the header's height isn't just "different on
  mobile," it's dynamic (depends on exactly how the nav links wrap, which itself could change if
  nav items are ever added/removed). The existing JS mechanism already solves this properly and
  is used by every other portal page; not using it here looks like an oversight from PO-3092
  (the very first story in this epic) rather than a deliberate choice.
- **Fixing the shared header's own nav-wrapping behavior** (e.g., making the nav collapse into
  the hamburger menu instead of wrapping across 3 lines at 360px): rejected — that's a change to
  `template-parts/portal-header.php`, a component shared across the entire site, not scoped to
  "the flow" this ticket is about. Out of scope for PO-3111; if it's worth fixing, it's a separate,
  site-wide ticket.

## Blast radius

- `page-home-aicoach.php`: one attribute added (`id="portal-content"` on the existing outer
  container), one CSS rule's top-padding value removed (185px → 0, bottom padding untouched).
  No other page/template touched — `#portal-content` is additive (the JS looks for it and no-ops
  if absent, which is exactly what let this page skip it silently until now).

## Notes

- **Visual testing (not just structural DOM checks) is what caught the real bug here** — worth
  remembering for any future NFR-02-adjacent work on this flow: an overflow/clipping sweep alone
  isn't sufficient, vertical overlap needs an actual rendered screenshot.
- Verified via live wp-env screenshots at all three required widths (360/768/1280) after the fix:
  intro text fully visible with no overlap at every width, avatar circle scales sensibly (~62% of
  viewport width on mobile), equity-magic's image + name overlay renders legibly, comm-channels'
  hint-message state renders without layout issues.
- Same known-interim-architecture note as every prior story in this epic — unaffected, this change
  doesn't touch the Anam integration.
- "All seven languages" (part of NFR-02's full scope) isn't verifiable yet, same caveat as
  PO-3110 — FR-13's language selector (PO-3104) isn't built.
