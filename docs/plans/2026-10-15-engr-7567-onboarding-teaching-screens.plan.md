---
name: Onboarding teaching screens 1, 2, 7-11 and 13
overview: >
  Eight onboarding screens teach rather than ask: The Game of Kings, The Modern Era, the three
  competition formats, Equity with two sample charts, How This Works, and the fixed worked equity
  example. This adds them as one plug-in module that registers with the screen engine (ENGR-7566),
  with every piece of copy in the onboarding string table. Narration (T12) is out of scope.
todos:
  - id: module
    content: js/aicoach/onboarding-screens-teaching.js — copy table, eight renderers, SVG line charts
    status: completed
  - id: css
    content: css/portal-onboarding-teaching.css, no media queries
    status: completed
  - id: tests
    content: copy character for character per screen, chart axes, fixed example, language redraw
    status: completed
  - id: design-values
    content: Chart sample values and the hero images of screens 1 and 2 from Figma 46893:117629
    status: completed
  - id: heroes-7-9
    content: Hero images for screens 7, 8 and 9 (the design reuses screen 2's picture there)
    status: blocked
  - id: design-check
    content: Compare each screen with its frame at 398 px
    status: pending
  - id: verify
    content: test:js, lint on touched files
    status: completed
---

# [ENGR-7567] Teaching screens 1, 2, 7–11 and 13

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7567
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
The engine (ENGR-7566) shows whatever screens are registered; the teaching screens do not exist yet.

## Approach
- One plug-in, `onboarding-screens-teaching.js` (found by the shell's `onboarding-screens-*` pattern):
  `install( api )` adds `TEACHING_STRINGS_EN` to the string table and registers eight definitions.
- Copy is the stories' approved English, character for character (PO-3203, PO-3208, PO-3209,
  PO-3211). Line breaks inside the stories' quotes are Jira wrapping and are joined with one space.
  Every element carries `data-onb-string`, so ENGR-7572's language switch re-labels it.
- Screen 10: two line charts drawn as SVG (`lineChartSvg()`, pure) and shown as images with the
  chart title as alt text; redrawn in the new language on a language change.
- Screen 13: an ordered list of the fixed steps with a CSS downward arrow between them; it never reads
  the follower answer. The story writes the steps as one arrow-separated chain; each arrow-separated
  item is one step, except "COMPANY VALUE: $0.20 → $2.00", which is kept whole because it is one value
  going from one figure to another.
- Hero images come from the shell's `ihq_portal_onboarding_images` filter (`api.imageUrl( stage )`);
  a screen without one shows no image. `inc/portal-onboarding-teaching.php` hooks the filter with the
  heroes of screens 1 and 2 (`images/aicoach/design/onboarding-*.webp`, versioned by file time).
- Screen 10's chart values are the design's: Figma 46893:117629 → "10 Equity 3", read from the drawn
  line (shares SUN..SAT `0, 70, 70, 130, 250, 275, 400`; value of shares START..24 MOS
  `0.2, 0.9, 1.2, 2.2, 4`). The design line is hand-drawn, so the middle points are a reading of it.

## Alternatives considered
- Charts as a canvas or a chart library: a new dependency for two static sample lines.
- Hard-coding copy in the renderers: ENGR-7572 needs it in the string table.

## Blast radius
A new plug-in module and stylesheet; nothing else changes. Nothing renders until ENGR-7564 and the
kill switch let the layer show.

## Notes
- The first build could not read the design (node 46497:79245 returned "node not found"). The current
  design, Figma file 0AHxJw6p2LoHzEiJW8wT2T instance 46893:117629 ("Coaching Center Portal -
  Onboarding Process"), gave the chart values and the heroes of screens 1 and 2 ("Baccarat Oil
  Painting 4" and "Modern pic", drawn 240.6 x 156.3 above the title, object-fit cover). They ship
  as WebP at 482 x 313 (2x); the Modern pic's design crop (top -2.49%, height 102.49%) is baked in.
- Screens 7, 8 and 9 reuse screen 2's picture in the design, which looks like a placeholder: they ship
  without a hero until Design gives them their own.
- Still open: the 398 px visual check per frame, the chart alt text, and the legal review of the copy
  and charts on 7, 10 and 13 (OQ18).
- Copy on 7, 10 and 13 may change after the legal review (OQ18); it lives only in `TEACHING_STRINGS_EN`.
