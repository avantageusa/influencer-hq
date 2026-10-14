---
name: AI Coach desktop and tablet layout from the Figma frames
overview: >
  Ivan delivered the desktop (1920) and iPad frames (mini, iPad 9, Pro 11, portrait and landscape) for
  the AI Coach flow. This plan adds the wide layout on top of the phone one without touching the
  flow logic, scripts, clips or server code. Landscape iPads (1080-1194 px) are a scaled copy of the
  desktop frames, so they take the desktop layout; portrait iPads (744-834 px) are the phone
  structure in a centred column under a full-width banner. Open design questions (banner video
  framing, Connect links, final and appointment screens) stay on today's look.
todos:
  - id: header
    content: Shared wide header from 600 px - hamburger, brand row (name, mark, domain), sound and globe, no Login/Register/My Contest/Help (Ivan, 2026-10-14)
    status: completed
  - id: desktop
    content: Desktop layout (1025 px and up) - banner, commentary box, time ask, BTS/Alix/Magic, competitions, We Believe, identity, channel tiles - in a 1920-unit column that scales with the width and the height of the window
    status: completed
  - id: tablet-portrait
    content: Portrait layout (600-1024 px) from the iPad mini frames, scaled to 810 and 834 - banner picture, commentary box, centred screens
    status: completed
  - id: verify
    content: JS and PHP tests, screenshots at 744, 810, 1024, 1133, 1280, 1905x897, 1920 and 2560
    status: completed
  - id: open-design
    content: Banner video framing (Filip), Connect links and stored data, final and appointment screens, tablet Alix/Magic/Time is up frames
    status: pending
---

# [PO-3062] AI Coach desktop and tablet layout

## Problem

The flow was drawn for a 398 px phone. Ivan's desktop and iPad frames use the same screens in a wide
header, a full-width banner and columns, so the phone layout (a 480 px column) looks wrong on them.

## Approach

- **One unit, no new breakpoints for the desktop.** Sizes are written as `N * var(--px)`, one unit
  being 1 px at 1920 wide. 1025 px (desktop) and 600 px (the lower end of the iPad portrait range;
  `max-width: 600px` / `min-width: 600px` already exist in the portal CSS) are the only boundaries.
- **Header from 600 px**: the frames draw the same header on every wide size, scaled.
- **Desktop (1025+)**: positions measured from the 1920 frames. The unit also follows the window
  height (1170 units, floor 0.45 px) so the screens fit a laptop without scrolling, and the header,
  stage and Ask Sami button sit in a centred column. The stage reaches up behind the commentary box
  (negative margin with matching padding) because it clips its children (PO-3343).
- **Tablet portrait (600-1024)**: `--tp` is 1 px at 744 wide. The phone structure is kept; sizes were
  measured from the iPad mini frames, the others are the same design scaled.
- **Images**: `banner-desktop.webp` (cut from the frame) and `banner-tablet.webp` (exported from
  Figma at 744 px) through a `<picture>`; the 3:2 clips are centred with soft edges on the desktop
  until the banner question is answered.
- `js/aicoach-coach-flow.js`: the banner gap under the header can come from `--aicoach-header-gap`.

## Alternatives considered

- **Shrink only the brand row to avoid scrolling**: saves about 60 px, the tallest screens need
  60-310 px, and the large brand is part of the frame. Rejected.
- **CSS `zoom` on the phone layout for tablets**: the iPad frames use different type sizes per
  element (smaller lists, larger titles), so a single factor does not match them.
- **A new breakpoint for the iPad mini (744 px)**: it falls under 768; reusing the existing 600 px
  value avoids a new number but is still a decision for Ivan.

## Blast radius

- Phone layout (below 600 px) is unchanged; checked at 398 px.
- Everything is scoped to `page-template-page-home-aicoach-php`; the shared portal header markup is untouched.
- A phone in landscape (600-1024 px wide) now gets the tablet layout.
- Landscape iPads and small desktops are scaled from the 1920 design; text is about 77% of design
  size on a 897 px high window.

## Test plan

- [x] `npm run test:js` (188 passing) and `npm run test:php`
- [x] Headless Chromium screenshots of each screen at 744, 810, 1024, 1133, 1280, 1905x897, 1920, 2560
- [ ] A real iPad, a real laptop window, and the Connect step on a tablet (not checked)
- [ ] Screens without frames (Time is up, appointment, final) on desktop and tablet
