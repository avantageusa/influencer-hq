---
name: Use the exact Figma banner images for the AI Coach and version them
overview: >
  The desktop banner still was cut from a rendered frame and is 11 px shorter than the design's
  banner (376 instead of 387), and the tablet banner is only 744 px wide, soft on a retina iPad. This
  replaces the desktop still with the exact Figma export, adds two larger tablet versions picked by the
  browser through srcset, and puts the file's modification time in the three banner addresses so a copy
  cached for a year is not reused. No layout or flow change.
todos:
  - id: desktop-still
    content: Replace banner-desktop.webp with the Figma export of node 46893:116000 (1920x387)
    status: completed
  - id: tablet-sizes
    content: Export the iPad mini banner at 3x (node 46893:117019) and save 1488 and 2232 px versions next to the 744 px one
    status: completed
  - id: srcset
    content: Give the 600 px source a srcset with the three widths and sizes="100vw"
    status: completed
  - id: versions
    content: Add ?ver=filemtime to the desktop and the three tablet addresses
    status: completed
  - id: verify
    content: Tests, and the file the browser picks at 744/834/1024 px at 1x and 2x, 1920 px, and phone
    status: completed
---

# [PO-3062] Exact Figma banner images, versioned

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3062

## Problem
The desktop still was cut from a screenshot of the frame (1920x376), while the banner is 1920x387 in the
design. The tablet banner (744x340) is exported at 1x. The two files are replaced in place, and the
theme's images are cached for a year by browsers and the edge, so a replaced file can stay invisible.

## Approach
- `banner-desktop.webp`: the Figma export of the banner instance, 1920x387, saved as WebP (quality 88).
- `banner-tablet-1488.webp` and `banner-tablet-2232.webp`: the same banner exported at 3x, resized for
  the middle one (quality 82). The existing `banner-tablet.webp` (744 px) stays.
- The tablet `<source>` uses `srcset` with width descriptors and `sizes="100vw"`: the banner is as wide
  as the window, so the browser picks by width and pixel density. A 1x iPad gets the 744 px file (84 KB)
  and not the 320 KB one.
- A small closure in the template appends `?ver=<filemtime>` (pattern of the scripts in
  `inc/anam-proxy.php`); without the file it prints the plain address.
- The repo's earlier approach for replaced pictures was a new file name; here the version in the address
  does the same job and keeps the names stable.

## Alternatives considered
- New file names instead of a version: no template helper, but every replacement renames files and
  touches the PHP.
- One 2232 px tablet file for all: simpler, but every 1x iPad would download 320 KB.

## Blast radius
Only the banner pictures on 600 px and wider. The phone loads the same picture as before. The edge must
treat `?ver=` as part of the cache key (the default); not checked on dev. Total new weight: 179 KB and
320 KB, fetched only by screens that need them.

## Test plan
- [x] `npm run test:js` (308) and `npm run test:php`, `php -l`
- [x] Headless Chromium: 744/834/1024 px at 1x and 2x, 1920 px and 398 px pick the expected file
- [ ] Check on dev that a replaced file is served fresh through the edge (not run)
