---
name: Connect Gary's banner compositor to the AI Coach page behind a WordPress option (off by default)
overview: >
  Gary built a browser adapter (`createSamiBanner`) that draws a poster on a canvas and composes the
  avatar's video over it, mapped back onto our wide banner scene. This connects it to the page: a
  canvas takes the place of the visible video layer (the scene picture stays behind it), driven by
  the same hidden `<video>` element the flow already uses for clips and the live session. Everything sits behind a WordPress option,
  off by default, so with it off the page is byte-for-byte what it is today. The option can be
  switched in a small Settings screen without WP-CLI or a deploy. It only takes effect when the
  served avatar version has a video profile (v3) with every clip rendered, otherwise the page falls
  back to today's banner. Switching it on in production waits for Ivan's decisions and for the v3 clips.
todos:
  - id: read-module
    content: Read Gary's module end to end (no session, no audio, no network call) and record what it needs (poster, calibration, options, API)
    status: completed
  - id: host-assets
    content: Host the module, the calibration JSON and the 1152x768 poster in the theme, versioned like the banner images; keep them tied to candidate cf6b6e7d (our banner-desktop.webp stays the scene)
    status: completed
  - id: switch
    content: One option (`ihq_aicoach_banner_compositor`, default off) read through one function that also checks the served version has a profile and all clips
    status: completed
  - id: markup
    content: With the switch on, the page prints a canvas after the video and the video stays in the DOM at opacity 0; with it off the markup is unchanged
    status: completed
  - id: flow
    content: A small module wires createSamiBanner to the existing video element; idle (Ask Sami panel open) calls reset() so the poster replaces the breathing portrait; the module's hold replaces nothing we have today; setup errors and wrong-video-dimensions fall back
    status: completed
  - id: live-profile
    content: ihq_coach_handle_open_session() adds video_profile only while the composed banner is enabled
    status: completed
  - id: options
    content: showDisclosure and preserveVideoCorner come from options with the defaults Ivan decides
    status: completed
  - id: admin
    content: Settings screen that shows option, served version, its profile and clips present, and switches the option
    status: completed
  - id: tests
    content: JS tests for the wiring and the fallback, PHP tests for the switch, the markup, the session payload and the settings screen; manual mutations
    status: completed
  - id: device-checks
    content: wp-env checks at desktop 1920, a laptop height (1440x800), landscape and portrait iPad and phone 398, off and on; recordings for Ivan are a separate step
    status: completed
  - id: rollout
    content: dev, then production; switch on only after Ivan's answers and the v3 clips; rollback is turning the option off
    status: pending
---

# [ENGR-7848] Banner canvas from Gary's compositor behind a WordPress option

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7848
**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Follows:** https://avantageusa.atlassian.net/browse/ENGR-7798 (v3 render with the profile)

## Problem
The clips we pre-render today come from the default avatar, so a visible rectangle shows around
her head wherever the frame is placed on the scene. Gary's adapter removes it by drawing the poster
and compositing the video through an affine matrix he measured for one candidate avatar. The page
still has its own banner: a `<picture>` with the desktop and tablet scenes, an idle portrait that
fades out when the status is `live`, and the `<video>` over it. The two cannot both draw the avatar.

## Approach
- **Same video element, new surface.** The flow keeps playing clips and the live session on
  `#aicoach-avatar-video`; it stays in the DOM and hidden. The adapter reads from it and paints the
  canvas, so there is one playback path (Gary's requirement) and the flow's playback code is untouched.
- **The canvas takes the video's place, the scene stays.** Today the `<picture>` (desktop, tablet or
  the 3:2 phone portrait) sits behind a `<video>` that is a 3:2 box in the middle of the banner on
  desktop and tablet and fills the banner on a phone. With the switch on the canvas gets the same
  class and the same placement (`object-fit: cover; object-position: center 10%` works on a canvas),
  the video stays in the DOM at `opacity: 0` (not `display: none`, so frame callbacks keep firing),
  and the scene picture stays. The CSS mask on the video is dropped for the canvas: the adapter
  already feathers the native frame's edges and the poster carries the surrounding scene.
- **Idle.** While Ask Sami's panel is open the flow pauses the video and today swaps in the breathing
  portrait (`.is-idle`). With the canvas that state calls the adapter's `reset()`, which draws the
  poster; the breathing animation does not apply to a canvas (decision for Ivan: static poster is
  what Gary's adapter gives us).
- **Between clips.** The adapter holds the last composition while `src` changes (`holding` status),
  so no handling is needed in the flow; only `reset()` and `destroy()` return to the poster.
- **One switch, one reader.** A WordPress option, default off, read through one function. The
  function returns true only if the option is on, the served avatar version has a video profile and
  its manifest has every clip. A version without a profile (original, v2) can never be composed:
  the calibration is only valid for the candidate avatar.
- **Live session.** `ihq_coach_handle_open_session()` adds `video_profile` to the open-session
  payload only while the function above is true, so live Sami and the clips come from the same
  avatar. A session opened before the switch keeps its profile.
- **Options for Ivan's decisions.** `showDisclosure` and `preserveVideoCorner` are passed from options
  (defaults set once Ivan answers), so changing them is not a code change.
- **Fallback.** `createSamiBanner` throws when the poster is not loaded, the avatar id differs from
  the calibration or the calibration is invalid, and it silently stops drawing video when a frame is
  not exactly 1152x768 (`wrong-video-dimensions`). The wiring catches the throw, watches that status
  (a live-session frame of another size would otherwise leave a poster with a talking Sami missing),
  and in either case shows the video layer again and removes the canvas, so the visitor sees
  today's banner and no error.
- **Admin screen.** Settings, AI Coach: shows the option, the served avatar version, its profile and
  whether all clips are present, and has the toggle. It replaces `wp option update` for whoever
  switches it. Capability `manage_options`, nonce on save.
- **Adapter is third-party code.** Read in full on 2026-10-20 (46 lines): it creates one offscreen
  canvas, reads the video element, listens to `loadeddata` and `emptied`, and schedules frame
  callbacks; it makes no network request, creates no session and plays no audio. It is hosted
  unmodified in `js/vendor/sami-banner/` (outside the linted paths, so a new version from Gary is
  a file swap), versioned with `filemtime`, and loaded with a dynamic `import()` by our own wiring
  module `js/aicoach/banner-compositor.js`. Only the wiring module is in the flow's registry, so the
  adapter is never fetched while the switch is off.
- **Scene.** Gary's `scene.webp` is our `banner-desktop.webp` re-encoded (mean difference 2.2 of 255,
  largest 16), so the existing scene stays and only the module, the calibration and the poster
  (1152x768) are added.

## Findings from reading the adapter
- Setup throws on an unloaded poster, an avatar id other than `cf6b6e7d-be88-4b9b-a31d-5f065b90cd9a`
  and an invalid calibration; the wiring must not let that break the flow.
- Every video frame redraws the poster, a 1152x768 layer and the composite; this is the cost on a
  phone and an old iPad to measure (frame time, battery) before the switch goes on.
- The "AI avatar" box is drawn by the module in English with `system-ui`, and the canvas gets the
  English `aria-label` "Sami, an AI avatar". The page has seven languages, so if Ivan keeps the box
  the label needs to come from us (override the `aria-label` after setup; the drawn text needs either
  a change from Gary or `showDisclosure: false` plus our own translated DOM label).
- The module gives no way to resize: it is a 1152x768 canvas scaled by CSS.
- `preserveVideoCorner` copies the renderer's 150x65 corner unchanged, so whatever mark the renderer
  puts there stays visible; with the option off the corner is composed like the rest.

## Built (2026-10-20)
- `inc/aicoach-banner-compositor.php`: the options (`ihq_aicoach_banner_compositor`,
  `ihq_aicoach_banner_disclosure`, `ihq_aicoach_banner_video_corner`), `ihq_aicoach_banner_compositor_enabled()`
  (switch on, served version's profile is `sami-framing-20261018`, hosted files present), the config
  handed to the flow (`AICOACH_SAMI.bannerCompositor`, `false` when off) and the status the settings
  screen shows. Box and corner default on (Gary's defaults) until Ivan decides.
- `inc/gary-proxy.php`: `video_profile` in the open-session payload while enabled.
- `js/aicoach/banner-compositor.js`: loads the adapter, poster and calibration, hands the adapter the
  shared video, calls `reset()` when the wrapper goes `is-idle` and `draw()` when it leaves it, shows
  the video again (`is-composite-bypass`) while the adapter reports a frame of the wrong size, and
  leaves the page on its video if anything fails to load or the adapter throws.
- `page-home-aicoach.php`: the canvas and a small style block, printed only while enabled. The scene
  picture stays; the breathing animation of the idle portrait is turned off while composed, because
  the poster is still and the scene behind it would drift against it.
- `inc/aicoach-banner-compositor-admin.php`: Settings, AI Coach (administrators): state, clips per
  version, the three checkboxes. Saved by WordPress's own `options.php`.
- `js/vendor/sami-banner/`: Gary's adapter, calibration and poster, unmodified, with a README.

## Local checks (wp-env, headless Chromium, v3 served with the one probe clip)
- Desktop 1920, laptop 1440x800, landscape iPad 1024, portrait iPad 744 and phone 398: the canvas
  takes the video's box (581x387 centred on desktop, the whole banner on the others), the video stays
  at opacity 0, playing draws the composition, idle returns to the poster and leaving idle draws the
  paused frame again. No console errors.
- Blocking the adapter, the calibration or the poster leaves the page on its video and logs one warning.
- Switch off, or v3 not served: no canvas, no style block, `bannerCompositor` is empty.
- The Settings screen showed the state, saved the three options and changed what the page prints.
- Observed for Ivan: Gary's "AI avatar" box sits at the canvas's bottom right and the commentary box
  overlaps the banner's bottom edge there, so on desktop the box is partly covered; a soft vertical
  edge shows on the beach to the right of the chair on a portrait iPad.

## Alternatives considered
- **Always on after merge, no switch:** rejected. Ivan has not answered the three questions (soft
  edge, "AI avatar" box, portrait iPad scene), and only one of 63 v3 clips exists.
- **GrowthBook flag:** the theme reads no GrowthBook flags; the existing switches
  (`inc/portal-onboarding-switch.php`) are WordPress options, so this follows them.
- **A per-request profile option instead of reading the version:** same reasoning as ENGR-7798, the
  version is the single source of truth for which avatar a clip belongs to.

## Blast radius
- Switch off: nothing changes for visitors (markup, CSS, behaviour); the new files are not loaded.
- Switch on without v3 complete: the function returns false, so nothing changes either.
- Switch on with v3: the banner on every device, the idle state, between-clip behaviour, Ask Sami
  interrupt and answer, language switch and sound toggle all go through the canvas. These are the
  paths to check on real devices.
- The Settings screen only exists in wp-admin for administrators.
- Needs no WP-CLI step after deploy while the option is off.

## Open questions
- Soft edge around her, the "AI avatar" box and the copied corner, portrait iPad scene (Ivan).
- Whether the framing profile also applies to the spoken `complete` passage (Gary, with Part B).
- Whether the adapter's calibration is final or will be re-measured (Gary).

## Test plan
- [x] `npm run test:js` (671 passing, 16 new for the wiring)
- [x] `npm run test:php` (switch values, enabled with each condition failing, config, status, blocker,
      open-session payload with and without `video_profile`, the Settings screen, the module registry)
- [x] Manual mutations of the new JS and PHP: over 50 mutants. Four survived (a redundant line, a
      redundant cast, no test for another version's profile, no check for PHP warnings); the two
      redundant lines were removed and the two missing tests added, and all four are now killed
- [x] wp-env: desktop 1920, laptop 1440x800, landscape and portrait iPad, phone 398, off and on;
      idle and leaving idle; fallback with each asset blocked; Settings screen saved and read back
- [ ] Not exercised: Ask Sami interrupt with a live stream, language switch and the sound toggle on the
      composed banner (they do not touch the canvas, but nothing ran them here), the live session with
      `video_profile` against Gary, real iPad and phone playback, and ESLint (no `node_modules` here)
