---
name: Portal onboarding shell (header, Sami banner, commentary box, Save CTA)
overview: >
  Every onboarding screen renders inside one frame: a header (hamburger, logo, sound, globe), Sami's
  full-width banner with the video slot, the commentary box for her subtitles, the content area and the
  SAVE IHQ TO HOME SCREEN button. This builds that frame as a template part over the Coach page, the JS
  that plays Sami's line with sentence-level subtitles (caption-only when there is no clip), the mute
  control, and the module/event plumbing the later onboarding tasks plug into. Screen content, the
  language selector's behaviour and the install behaviour of the CTA are out of scope.
todos:
  - id: pure-modules
    content: onboarding-subtitles.js, onboarding-narrator.js, onboarding-strings.js, onboarding-events.js with node tests
    status: completed
  - id: shell-dom
    content: onboarding-shell.js (DOM glue) and onboarding-main.js (entry that installs the plug-in modules)
    status: completed
  - id: php
    content: inc/portal-onboarding-shell.php renders template-parts/portal-onboarding.php on ihq_portal_onboarding_render_layer and registers the script modules
    status: completed
  - id: css
    content: css/portal-onboarding.css, centred column at the existing 768/1025 px queries, no new breakpoint
    status: completed
  - id: verify
    content: test:js, lint on touched files, CI PHP job
    status: completed
---

# [ENGR-7565] Onboarding shell: header, Sami banner, commentary box, Save CTA

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7565
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
The onboarding screens (ENGR-7566 onwards) need a shared frame that matches the current design, plays
Sami's clip or, when there is none, shows her words at reading pace, and lets the Influencer mute her.

## Approach

### How the layer gets on the page
- The entry gate (ENGR-7564) fires `ihq_portal_onboarding_render_layer` with
  `{ state, progressUrl, completeUrl, nonce }` on the Coach page. `inc/portal-onboarding-shell.php`
  hooks it, adds `clips`, `images` (both filters, empty until clips are rendered, T12), `logoutUrl` and
  `modules`, and renders `template-parts/portal-onboarding.php`. Until ENGR-7564 is merged nothing fires
  the action, so this PR is inert on `main`.
- The layer is a fixed, full-screen dialog over the Coach page with its own header. Removing it
  (`shell.close()`) leaves the Coach page as it was, scrolled to the top.

### Script modules
- Every `js/aicoach/onboarding-*.js` is registered as a WordPress script module `@ihq/aicoach/<name>`,
  versioned by modification time (same arrangement as `inc/aicoach-modules.php`). The entry
  `onboarding-main` lists the core modules and the landing modules it reuses (`commentary`,
  `stall-watchdog`) as static dependencies.
- Plug-ins are found by file name: `onboarding-screens-*.js` and `onboarding-addon-*.js` are registered
  as dynamic dependencies and their ids go into `config.modules`. The entry imports each and calls its
  `install( api )`. The later tasks (screens, language selector, preload) add a file instead of editing
  a shared list, so their sibling branches do not conflict.
- Modules never import each other. Dependencies are passed in by the entry, so each is tested with
  fakes under `node --test`, and a relative import cannot bypass the versioned import map.

### Pieces
- `onboarding-subtitles.js`: sentence cues spread over a clip (Intl.Segmenter sentence granularity),
  `subtitleAt()` empty before the first and after the last sentence (OQ13: sentence level, clears
  when the clip ends).
- `onboarding-narrator.js`: plays a line. With a clip, cues follow the clip's clock; on an `error`, a
  failed `play()` or a stall (the landing `createStallWatchdog`), it falls back to captions at reading
  pace (`estimateDurationMs` from `commentary.js`). Mute sets `video.muted` only; subtitles continue.
  When autoplay with sound is refused, the clip plays muted and the sound button shows muted.
- `onboarding-strings.js`: the string table (`add`, `t` with English fallback). The shell's strings
  live here; ENGR-7572 adds translations and the selector.
- `onboarding-events.js`: the emitter plug-ins talk through.
- `onboarding-shell.js`: DOM glue for the header controls, the commentary box and the content area.

### Layout
Mobile layout everywhere, as a centred column: full width below 768 px, a fixed-width column from the
existing `(min-width: 768px)` and `(min-width: 1025px)` queries (the complements of the portal's
`max-width: 767px` / `max-width: 1024px`). No new pixel value in a media query.

## Alternatives considered
- Reusing `js/aicoach-coach-flow.js`: it is the landing flow's engine, tied to that page's markup and
  Gary session; EQ4 keeps it untouched. Only its pure modules are reused.
- Phrase-level commentary (`commentary.js` cues): OQ13 asks for sentence level.
- One shared list of screen modules: every screen ticket would edit it and the siblings would conflict.

## Blast radius
New files only, plus the identical `inc/portal-onboarding-*.php` loader block in `functions.php`. The
layer renders only when ENGR-7564's action fires.

## Notes
- The current design node (46497:79245) could not be opened through the Figma MCP from this session
  ("node not found"; only the file's index page is visible). Sizes and colours follow the landing
  flow's implementation of the same frame (PO-3062 design alignment, `page-home-aicoach.php`): banner
  398:182, dashed commentary box on `#2d2b2b`, green `#148942` CTA, Be Vietnam Pro / Inter. A design
  check against frame 46497:79246 at 398 px is still needed.
- The globe button is rendered; its behaviour is ENGR-7572. The Save button carries `data-ihq-save-home`;
  its behaviour is ENGR-7575, which binds every element with that attribute.
