---
name: Show accordion Previous/Next on all screen widths
overview: >
  FAQ accordion Previous/Next buttons were only injected and styled above
  1024px. Remove that gate so they appear on mobile and tablet too (ENGR-6750).
todos:
  - id: js
    content: Stop skipping initAccordionNavButtons when width <= 1024
    status: completed
  - id: css
    content: Move accordion-nav button styles out of the min-width 1025px media query
    status: completed
  - id: verify
    content: Expand a Coach/Equity FAQ on mobile and tablet; confirm Previous/Next show
    status: pending
---

# [ENGR-6750] Coach FAQ Previous/Next on mobile and tablet

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-6750
**Drafted by:** Cursor (Composer)

## Problem
Expanding a FAQ accordion on the Coach page (and other `custom-accordion` /
`#equityAccordion` groups) shows Previous/Next only on desktop. On mobile and
tablet the buttons never appear.

## Reproduction
1. Open the Coach page on a viewport ≤ 1024px.
2. Expand any FAQ item.
3. Previous/Next are missing.

## Approach
- `template-parts/portal-header.php`: remove `if (window.innerWidth <= 1024) return;`
  from `initAccordionNavButtons()` so the buttons are appended at all widths.
- `template-parts/portal-styles.php`: lift `.accordion-nav-btns` / prev / next
  rules out of `@media (min-width: 1025px)` so they style everywhere; slightly
  tighter gap under 1024px.

## Alternatives considered
- Duplicate the buttons in PHP markup — rejected; JS already owns injection.
- Keep CSS desktop-only and only fix JS — rejected; unstyled links would look wrong.

## Blast radius
Any page that loads `portal-header.php` and uses `.accordion.custom-accordion`
or `#equityAccordion` gets the nav buttons on small screens too (intended).

## Notes
Manual verify on Coach FAQ mobile/tablet still pending.
