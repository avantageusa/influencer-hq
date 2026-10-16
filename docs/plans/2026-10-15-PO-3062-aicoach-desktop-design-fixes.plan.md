---
name: AI Coach desktop design fixes after the PR #118 review on dev
overview: >
  After #118 reached dev, three screens differed from Ivan's updated desktop frames: the 10-minute row
  of the time ask was raised above the 2- and 5-minute rows, the "Let's Continue" button was cut off on
  the final screen, and on Community Competition the text on the right ran behind the commentary box.
  This plan lines them up with the frames. CSS only, scoped to 1025 px and wider.
todos:
  - id: time-ask
    content: Three columns of 508 units at x 141, 706 and 1271, all rows on one line (Time Ask - ABC3 - Desktop - NEW)
    status: completed
  - id: final-button
    content: "Let's Continue" on one line with the width it needs, so it is not wrapped and cut
    status: completed
  - id: community
    content: COMMUNITY COMPETITION in one line, the sentence in two lines 400 units wide under the right column (Community - Desktop - NEW)
    status: completed
  - id: verify
    content: JS and PHP tests, screenshots at 1920x1026 and 1905x897 of the three screens
    status: completed
---

# [PO-3062] AI Coach desktop design fixes

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3062

## Problem
Reported on dev after #118 with screenshots: the time ask rows were staggered (the old frame), the
final button text wrapped inside a fixed-height button, and the Community sentence was one long line
that spilled left under the commentary box.

## Approach
- Time ask: the grid columns and the rows' top margin come from Ivan's updated frame; the previous
  stagger and its offsets are removed.
- Final screen: `.aicoach-final .aicoach-form-continue` is `width: auto` with a minimum, no wrapping and
  a height that scales with the unit.
- Community: the name does not wrap (it is wider than its column, the neighbouring columns leave room)
  and the sentence has its own width and offset; World and Private keep their three-line layout.

## Blast radius
Only the desktop block (1025 px and wider) of `page-home-aicoach.php`. Phone and portrait iPad layouts
are unchanged.

## Test plan
- [x] `npm run test:js`, `npm run test:php`, `php -l`
- [x] Headless screenshots of the three screens at 1920x1026 and 1905x897
- [ ] A real laptop window and a landscape iPad (not checked)
