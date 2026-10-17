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
    content: Hero images for screens 7, 8 and 9 — settled 2026-10-16, no hero (design decision 4)
    status: completed
  - id: design-check
    content: Compare screens 1, 2 and 7-11 with their frames at 398 px — done in f572d5d (see Notes)
    status: completed
  - id: design-check-13
    content: Compare screen 13 with its frame at 398 px
    status: pending
  - id: design-decisions
    content: Cross-screen differences in the design — settled by the dev on 2026-10-16 (see Notes)
    status: completed
  - id: advance
    content: Teaching screens advance when their narration ends, or after their reading time while they have no script — 7b67f1f
    status: completed
  - id: chart-alt-text
    content: Each chart's alt text describes its plotted values (design decision 12) — 0c1e21b
    status: completed
  - id: legal-review
    content: Legal review of the copy and charts on screens 7, 10 and 13 (OQ18)
    status: blocked
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
- No CONTINUE button. None of the definitions declares an `advanceMode`, so each screen advances the
  engine's default way: when the coach's narration ends. Until a screen has a narration script
  (OQ1), the engine waits for the reading time of the screen's own on-screen text, then advances
  (design decision 1, 7b67f1f). Real scripts take over once they exist.
- Screen 10: two line charts drawn as SVG (`lineChartSvg()`, pure) and shown as images, redrawn in the
  new language on a language change. Each chart's alt text is built from its data and its translated
  axis labels (`chartAltText()`, 0c1e21b), e.g. "Earned Equity Shares: 0 on SUN, 70 on MON, …, 400 on
  SAT" and "Value of Shares: $0.20 at START, …, $4.00 at 24 MOS". It describes the plotted values
  only and adds no claim. Every word of it comes from the string table.
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
- Screens 7, 8 and 9 reuse screen 2's picture in the design. They have no hero (design decision 4):
  showing the same image four times is worse than none.
- 398 px check, done in f572d5d (screens 1, 2, 7-11, against the frames of Figma 46893:117629 with the
  132 px Safari bar taken off; the CSS header names the frames). Screen 13 is not
  checked yet (todo `design-check-13`). The PR description's "398 px match: not verified" predates
  f572d5d. Measured:
  titles are Cinzel Bold 17 px white with a 242 px gold rule under them; body copy is Be Vietnam Pro
  SemiBold 12 px on an 18 px line in a 240 px column, an empty line between paragraphs; heroes sit
  35 px under the commentary box; screen 11's bullets are centred; screen 10's charts are drawn as the
  design's 198.5 x 149.8 boxes (dark plot, dashed grid, white axes and line, ticks evenly spaced so
  $.20 to $1 is as tall as $1 to $2), 7 px apart. Screen 13 keeps its own layout (no rule) until its
  own check.
- Design decisions, settled by the dev on 2026-10-16. The Figma file is an approximation, and where
  the written story and the mock disagree, the story wins:
  - No CONTINUE button: teaching screens advance by narration, or by reading time while they have
    no script (decision 1).
  - No hero on screens 7, 8 and 9 (decision 4).
  - One type scale: titles 17 px Cinzel Bold on every teaching screen (decision 5); screen 8 has the
    same layout as 7 and 9, and the mock's 1.35x copy is ignored (decision 6); body 12 px on 18 px
    lines, 240 px wide, everywhere, including screen 11 (decision 7).
  - One vertical rhythm: with no hero, the title sits 20 px under the commentary box, on 10 and 11
    alike (decision 8).
  - Be Vietnam Pro, the theme font; the mock's "Be Vietnam" is retired (decision 9).
  - Chart axis labels follow story PO-3209: TUE and $.20 (decision 11).
  - Chart alt text generated from the chart data (decision 12, above).
  The charts keep the design's 5 px axis labels, drawn inside the 198.5 x 149.8 chart box.
- Still open: the legal review of the copy and charts on 7, 10 and 13 (OQ18).
- Copy on 7, 10 and 13 may change after the legal review (OQ18); it lives only in `TEACHING_STRINGS_EN`.
