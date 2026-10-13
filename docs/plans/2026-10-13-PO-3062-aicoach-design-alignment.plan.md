---
name: Align the AI Coach flow with the updated Figma design (banner, commentary, screens)
overview: >
  Ivan updated the design for the whole Landing flow: Sami is a full-width banner instead of a
  circle, the words she speaks appear in a commentary box under it and clear as she goes, and
  every screen was redrawn around that frame (header, selection, equity, competitions, identity,
  channels, Time is up and appointment). This plan builds the shared frame once, then moves each
  screen onto it in the order the flow runs, reusing the existing sequence, clip and form logic.
  Screens whose design is not delivered yet (final screen, appointment done and link page, error
  states, tablet and desktop, the Magic Johnson frame) are listed and left on today's look until
  they arrive. It does not change copy, scripts, clips, tiers, validation or any server code.
todos:
  - id: design-inputs
    content: Collect the missing design inputs listed in Notes (Magic Johnson frame, final screen, appointment done and link page, error states, tablet and desktop)
    status: pending
  - id: fonts-tokens
    content: Load Cinzel for the two small-caps headings, add the design tokens (yellow, green, commentary grey) as CSS custom properties on the AI Coach section
    status: pending
  - id: header
    content: Header per design - hamburger left, "influencer headquarters" + ih logo + "ihq.global" centred, sound and globe right - inside the existing portal header and its menu
    status: pending
  - id: banner
    content: Replace the circular avatar with the full-width banner (video, portrait and idle state keep their ids, data-status and is-idle behaviour); face stays in frame at 398x182
    status: pending
  - id: commentary
    content: One commentary box under the banner, driven by a tested pure module that splits the script into phrases and reveals them as the clip plays (live intro and fallback paced by estimated reading time)
    status: pending
  - id: screens-start
    content: Intro, We Believe as one screen with the three icons, and the Time ask rows (yellow checkbox, Cinzel title, bullet list)
    status: pending
  - id: screens-equity-competition
    content: BTS, Alix Earle and Magic Johnson (same layout), World, Community and Private Challenges (title, gold flag, three lines)
    status: pending
  - id: screens-forms
    content: Identity (inline labels, green CONTINUE), channels (two-column checklist, green fill) and the Connect rows
    status: pending
  - id: screens-timeup
    content: Time is up and the appointment choices, Other fields and green Copy Your Appointment Link button
    status: pending
  - id: verify
    content: Run tests, lint, and CI gate before merge
    status: pending
---

# [PO-3062] Align the AI Coach flow with the updated Figma design

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude (claude-sonnet-5-5)

## Problem
The flow Ivan reviewed on dev no longer matches the design: Sami is a 280 px circle, her words
appear all at once as a block of text, and the screens use the older layout. The new design
(Figma file "2026-Current-In-progress (Wolfe)", board "Influencer Onboarding Flow", tracks A, B
and C and the three shared screens ABC1 to ABC3) puts her in a full-width banner with a
commentary box that shows what she is saying as she says it. Every screen has to look like the
design from first screen to last.

## Approach

### The design, as measured (mobile frame 398 px wide)
- **Header:** 70 px. Hamburger at 22 px left, sound and globe at 22 px right, the text
  "influencer headquarters" (Inter SemiBold 15 px) and "ihq.global" either side of the red-lines
  "ih" logo. The Safari address bar in the frames is a mock-up of the browser and is not built.
- **Banner:** 398 x 182 (about 2.19:1), full width, directly under the header.
- **Commentary box:** 316 x 86, centred, 9 px under the banner, fill `#2d2b2b`, 1 px dashed white
  border, text Inter Light Italic 13.8 px, line height 24 px, centred, white.
- **Colours:** yellow `#fdd65b` (titles, borders, selected checkbox), green `#148942` (CONTINUE,
  Connect, Copy Your Appointment Link, selected channel checkbox), field fill `#161612`.
- **Type:** Cinzel Bold 22.7 px for "2 minutes" / "WE BELIEVE" (the lower-case letters are
  small capitals), Be Vietnam Pro SemiBold 16 px for lists and prompts, Inter Black 28.5 px
  yellow capitalised for "Let's Start The Conversation" and the competition names.

### What changes in the code
- `page-home-aicoach.php` holds the markup and one large `<style>` block; the screens, ids and
  `data-i18n` keys that `js/aicoach-coach-flow.js` and the tests rely on **stay**. The change is
  to the structure around them (banner instead of the circle wrapper, one shared commentary box
  instead of a `.aicoach-caption` inside every panel) and to the CSS.
- **Banner.** `.aicoach-avatar-wrap` keeps its id, `data-status` and `is-idle` states (live,
  connecting, idle breathing, the portrait swap from PR #109) but becomes a full-width box at
  the banner's proportion instead of a circle. The clips are 3:2 and the banner is about 2.2:1,
  so the video is cropped top and bottom (`object-fit: cover`); the vertical position is chosen
  from a real v2 frame so her head stays inside it (see Notes).
- **Commentary.** A new pure module `js/aicoach/commentary.js` (registered in
  `inc/aicoach-modules.php`, tests beside it) splits a script into short phrases at punctuation,
  gives each phrase a start time proportional to its weight within the clip's length, and
  returns the phrase and revealed words for a given time. The flow feeds it the playing clip's
  `currentTime` and `duration`; a phrase is cleared when the next begins and the box is empty
  between clips. Where there is no duration (the live intro, a clip that failed and fell back
  to the caption dwell) the same module is paced by an estimated speaking rate. The existing
  full-text assignments through `getCaptionEl()` become calls to one `commentary` object, so
  `restartCurrentClipForLocale()` and the translated captions keep working.
- **Accessibility.** The word-by-word text is not a live region (it would be read out hundreds of
  times); the full script of the current clip is put once, visually hidden, in an `aria-live`
  region. With `prefers-reduced-motion` the phrases change without a fade.
- **We Believe is one screen.** The design shows one "WE BELIEVE" screen (title and the three
  icons) for both narrations. Today believe-1 shows the icons first and swaps to text, and
  believe-2 has its own single-icon layout (PO-3343). With the commentary box the icon phase is
  not needed: both panels show the same icons while the two clips play one after the other, and
  the "text phase" code for believe-1 is removed.
- **Screens.** Each is moved onto the frame with the measurements above, taken per screen from
  `get_design_context` at implementation time: Time ask (yellow checkbox, filled when chosen),
  equity (yellow name, photo, green tick and red cross with bold uppercase bullets), the three
  competitions, identity (label inside the field, green CONTINUE), channels (two columns, white
  boxes, green when ticked, Connect rows), Time is up and the appointment choices.
- **Fonts.** Cinzel Bold, Be Vietnam Pro up to SemiBold and Inter up to SemiBold are already loaded
  by `template-parts/portal-styles.php`; the design also uses Be Vietnam Pro ExtraBold and Inter
  ExtraBold and Black, so one more Google Fonts stylesheet with those three weights is added
  (`font-display: swap`).
- **Breakpoints.** Mobile-first at the design width; wider screens keep the design's proportions
  inside a centred column. No custom breakpoint is introduced: only the standard named ones, and
  a layout the design does not cover on tablet or desktop goes back to design (coding standards).

### Order
1. Fonts and tokens, header, banner (everything else sits on them).
2. Commentary module with tests, then wiring it into the flow, then removing the per-panel
   captions.
3. Start screens, equity and competitions, forms, Time is up and appointment.
4. Compare each screen with its Figma frame at 398 px and at tablet and desktop widths on
   wp-env and on dev; run the suites.

## Alternatives considered
- **Keep a caption inside each panel and only restyle it:** least change, but the design places
  the box between the banner and the content on every screen, so the text would have to be
  repositioned per panel; one shared box is simpler and keeps the position fixed.
- **Word timestamps from Gary for the commentary:** exact, but the Coach API does not return them
  today (no caption timing) and a request is a dependency we do not control; proportional timing
  from the clip's duration needs nothing from him and can be swapped for real timestamps later
  through the same module.
- **Reusing the portal header unchanged:** keeps one header, but its layout (logo and links)
  does not match the new one; the design header is built in the same template part and shares
  the menu, language and sound controls.

## Blast radius
- **Touched:** `page-home-aicoach.php` (markup and CSS), `js/aicoach-coach-flow.js` (caption calls,
  We Believe intro logic, banner/idle wiring), `js/aicoach/` (the new module and its test),
  `inc/aicoach-modules.php` (module list must equal what the entry imports),
  `template-parts/portal-header.php` (the AI Coach header variant), and the screens' CSS.
- **Pages that share parts:** `page-appointment.php` (the link page) uses the portrait and the
  same header; its look is left as it is until the design for it arrives, and a test or manual
  check confirms it does not break.
- **Not obvious:** the live intro still arrives through Anam and has no end-of-speech event, so
  the commentary there is an estimate; the 3:2 clips cropped to the banner may cut the top of her
  head on some frames; the identity labels (9.8 px) and placeholders (6.5 px) in the design are
  below the size most accessibility reviews accept, and are reproduced as drawn unless design
  changes them; tests that assert on caption text (`js/aicoach/*.test.js`, the PHP page tests)
  will need updating, which is a change to tests and is done openly, not to make them pass.

## Notes

### What was built (2026-10-13)
Done and checked in the browser at 398 px against the Figma frames (positions measured, within a few
pixels): the header, banner, commentary box with the words appearing as she speaks (live intro and
clips), We Believe as one screen, the time ask, BTS, Alix Earle, Magic Johnson, the three
competitions, identity, the channels list, the final button and the green CONTINUE. Time is up and
the appointment screens keep their PO-3109 layout inside the new frame.

Decisions taken while building, all easy to change:
- The banner sits 14 px under the header and the box 6 px under the banner (the frames of tracks A
  and ABC differ by 3 px; one value was chosen). The 3:2 clips and portrait are cropped to the
  banner with the head kept in (`object-position: center 10%`).
- **The hamburger is back** in the header, as drawn. Ivan had it hidden on 2026-09-23; it opens the
  portal menu, which on a page for a visitor who has not registered yet may not be wanted. One line
  of CSS hides it again.
- **The Ask Sami button sits on the banner**, bottom right: the design has no place for it.
- Identity is drawn with the label inside the field and the input as the dashed box; the label is 11 px
  and the input 16 px (the design's 9.8 px and 6.5 px are not readable, and below 16 px iOS zooms the
  page when a field is focused). CONTINUE is dimmed, not grey, while it is disabled.
- The channel list is two columns in the order of the existing eight channels (Email, KakaoTalk /
  Line, SMS / ...), because the design shows six (no Email or SMS). A field that opens under a
  ticked channel takes a full row below the list; the text-only inputs are unchanged.
- When a clip cannot play, the commentary is paced at a reading speed and the screen stays long
  enough to read it (at least the old 9 s), instead of compressing the script into 9 s.
- The old screen images are removed; the new ones are in `images/aicoach/design/` under new names.

Not built, waiting for design: the final screen, the appointment confirmation and link page,
error states, tablet and desktop frames (the column is capped at 480 px and centred),
and the channels step with "Connect with ..." buttons and "Forgot One?". Ivan's answer: the
arrow back is intended, and Connect opens a link to the messenger (for example Zalo or
KakaoTalk); the links themselves, and what happens to Email and SMS (not in the design), are
still open.

### Design inputs still missing
1. **Magic Johnson:** `45086-26146` is only the "Screen BG" layer of the frame, not the frame;
   the frame's own link is needed.
2. **Final screen** (after channels, with "Let's Continue" to the portal).
3. **Appointment done** (after Copy Your Appointment Link) and the **appointment link page** (six
   states).
4. **Error states** (empty or taken username, invalid channel value, link refused).
5. **Tablet and desktop frames**, or a decision that the mobile layout is centred and scaled.
6. **Intro:** the frame for ABC1 shows "TEXT TBD" under the commentary box; what, if anything,
   goes there.

### Questions for design (Ivan)
- The channels frame with Connect buttons has a "Forgot One?" arrow pointing back; the flow is
  forward-only (PO-3101). Is the arrow intended, and where does it go?
- What do "Connect with KakaoTalk" and "Connect with Zalo" do (open a field, a hand-off to the
  app, a verification)? Today each channel takes a typed value.
- Does the 2.2:1 banner crop of a 3:2 clip match what design expects, or should the banner be
  3:2?

### Related
`2026-10-11-PO-3062-new-sami-avatar.plan.md` (portrait and clip versions),
`2026-10-08-PO-3109-times-up-and-appointment-scheduling.plan.md` (Time is up and appointment),
`2026-10-12-PO-3063-aicoach-portal-onboarding.plan.md` (portal onboarding, separate epic).
