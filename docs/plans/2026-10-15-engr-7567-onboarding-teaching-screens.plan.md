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
  - id: design-check
    content: Compare each screen with its frame at 398 px; read the chart sample values and hero images off the design
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
- Screen 10: two line charts drawn as SVG (`lineChartSvg()`, pure) and shown as images with the
  chart title as alt text; redrawn in the new language on a language change.
- Screen 13: an ordered list of the fixed steps with a CSS downward arrow between them; it never reads
  the follower answer. The story writes the steps as one arrow-separated chain; each arrow-separated
  item is one step, except "COMPANY VALUE: $0.20 → $2.00", which is kept whole because it is one value
  going from one figure to another.
- Hero images come from the shell's `ihq_portal_onboarding_images` filter (`api.imageUrl( stage )`);
  a screen without one shows no image.

## Alternatives considered
- Charts as a canvas or a chart library: a new dependency for two static sample lines.
- Hard-coding copy in the renderers: ENGR-7572 needs it in the string table.

## Blast radius
A new plug-in module and stylesheet; nothing else changes. Nothing renders until ENGR-7564 and the
kill switch let the layer show.

## Notes
- The current design (node 46497:79245 and the frames listed in the ticket) returned "node not found"
  through the Figma MCP in this session. Not done from the design, and still needed:
  the hero images (none are in the repo), the chart sample values (placeholders in `EQUITY_CHARTS.values`,
  marked as such), and a visual check at 398 px.
- Copy on 7, 10 and 13 may change after the legal review (OQ18); it lives only in `TEACHING_STRINGS_EN`.
