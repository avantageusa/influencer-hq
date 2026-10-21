---
name: Onboarding commentary box shows Sami's line as fading phrase groups with the previous phrase kept
overview: >
  The onboarding commentary box shows one sentence at a time with no transition. Following
  Marcus's commentary delivery instructions, the line is split into short natural phrases
  (the script's line breaks and "…" first, then sentence and clause ends), timed to the clip
  or to reading pace with a minimum time on screen, and shown with a gentle fade and the
  previous phrase above it in a softer style. Out of scope: word timestamps from Gary's API,
  the headline display items Marcus lists per screen, and the landing flow's commentary.js.
todos:
  - id: phrase-cues
    content: onboarding-subtitles.js — splitPhrases (line breaks, "…", sentences, clauses, long-phrase split), buildPhraseCues (weight + pause at breaks, optional minPhraseMs), captionAt ({ current, previous })
    status: completed
  - id: narrator
    content: onboarding-narrator.js — use phrase cues and captionAt; captions mode honours MIN_PHRASE_MS; finish() emits an empty caption; phraseIndexAt for the stall fallback
    status: completed
  - id: caption-box
    content: New onboarding-caption-box.js — renders { current, previous } into the commentary box, fades out then in on change, no fade under prefers-reduced-motion, ignores repeats of the same caption
    status: completed
  - id: wiring
    content: onboarding-main.js wires the caption box; shell drops showSubtitle; template comment updated
    status: completed
  - id: css
    content: Commentary box CSS — two steady lines (previous softer), larger regular-weight current phrase, fixed box height so the headline below does not move, fade transition with a reduced-motion override; no new breakpoints
    status: completed
  - id: tests
    content: Tests for splitting, cue timing (clip and reading pace), previous-phrase display, fade and reduced motion; update narrator, shell and engine tests whose intent changed
    status: completed
  - id: verify
    content: npm run test:js, npm run test:php, break key guards to see tests fail, check on the local wp-env site; lint not installed locally
    status: completed
---

# [ENGR-7891] Onboarding commentary box: phrase groups that fade, paced to Sami, previous line kept

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7891
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
The commentary box under Sami (`.ihq-onb__subtitle`) shows her line a whole sentence at a time
and swaps sentences with no transition (ENGR-7565). Marcus's delivery instructions (PO-3201) ask
for short natural phrase groups, gentle fades, pacing that follows Sami, a steady and readable
box that never competes with the headline, and a way to catch up, such as the previous line in a
softer style.

## Approach
- **Splitting** (`js/aicoach/onboarding-subtitles.js`, pure). `splitPhrases(text)`:
  1. the script's own line breaks;
  2. after each "…" (or "...") pause;
  3. any piece still longer than `MAX_PHRASE_CHARS` splits at sentence ends (existing
     `splitSentences`, Intl.Segmenter where available), then at clause punctuation
     (`, ; : —` and CJK `，、；：`) as long as both sides keep `MIN_PHRASE_CHARS`, then at the
     space nearest the middle. Never one word per phrase: a split that leaves a side shorter than
     `MIN_PHRASE_CHARS` is not made. CJK characters count double toward the length, as they
     are about twice as wide on screen. A long CJK piece with no punctuation is left whole and
     wraps.
- **Timing.** `buildPhraseCues(text, durationMs, { minPhraseMs })` spreads the phrases over the
  duration by length (letters, spaces ignored) plus a pause weight after a break (larger after a
  line break, "…" or sentence end, smaller after a clause), so longer phrases stay longer and the
  text holds through Sami's pauses. Cues are back to back. With a clip there is no minimum: the
  clip's clock wins so the text stays in sync. In the reading-pace fallback the narrator passes
  `MIN_PHRASE_MS` (1000 ms, about the usual subtitle floor), and a phrase below it is
  lengthened (the line takes longer; say() still settles when the last phrase ends).
- **Previous phrase.** `captionAt(cues, timeMs)` returns `{ current, previous }`, derived from
  the cue index (no stored state), so it is right after a stall fallback or a restart. Both are
  empty before the line starts and after it ends; a new line starts with no previous phrase.
- **Narrator** (`onboarding-narrator.js`): same engine contract (play() settles when the line
  ends or is stopped; a language switch calls play() again, which stops and restarts). Emits
  captions instead of strings; `sentenceIndexAt` becomes `phraseIndexAt`.
- **Caption box** (new `onboarding-caption-box.js`, auto-registered as a core module by
  `inc/portal-onboarding-shell.php`): builds two lines inside `[data-onb="subtitle"]`, the
  previous phrase and the current one. On a change it fades both out (`FADE_OUT_MS`), swaps the
  text, and fades in; the previous line settles at a lower opacity. Repeats of the same caption
  (the narrator reports on every tick and timeupdate) do nothing, so the fade does not restart.
  Under `prefers-reduced-motion: reduce` it swaps at once and never sets the fading class, so the
  opacity never changes and nothing transitions. No CSS media query is needed (the stylesheet's
  guard test allows only the two portal width queries). The screen-reader transcript
  (`.ihq-onb__sr`) is unchanged: the whole line, once.
- **CSS** (`css/portal-onboarding.css`): the box keeps a fixed height that fits a one- to
  two-line previous phrase and a two-line current phrase, so its size does not change while Sami
  speaks and the headline in the content area below never moves or overlaps. Current phrase
  16 px regular (was 14 px light) on the existing dark box; previous phrase smaller and softer,
  #b3b3b3, about 6.7:1 on the box. No new media query.

## Alternatives considered
- Keep the "previous" phrase as state in the shell: rejected, it is derivable from the cue index
  and would go stale on a fallback or restart.
- Reuse commentary.js's word-by-word `buildCues` / `visibleTextAt`: it reveals words one at a
  time, which the instructions rule out, and it ignores the script's line breaks.
- Fade with a single element and CSS `@keyframes` restart: needs a forced reflow and cannot fade
  the outgoing text; fade-out, swap, fade-in on a container is simpler and testable with the fake
  clock.
- Lengthen phrases to a minimum in clip mode too: it would drift from the clip.

## Blast radius
- Every onboarding screen with narration (none in production until the narration table is
  filled; testable locally through the `ihq_portal_onboarding_narration_scripts` filter).
- Reading-pace lines can take a little longer than `estimateDurationMs` when they have many short
  phrases, so screens that advance when Sami finishes advance slightly later.
- `createShell` loses `showSubtitle` (only onboarding-main.js used it). Tests whose intent changed
  are updated: narrator (captions are objects, phrase timing), shell (showSubtitle moved),
  engine (narrator dependency names). Called out in the PR.
- The commentary box gets taller (fixed height), so the content area is a little shorter on
  small phones; the content-min-height fallback (layer scrolls) still applies.

## Notes
- Gary's API still returns no word timestamps; timing remains proportional. `buildPhraseCues`
  can take real timestamps later without touching the narrator or the box.
- Marcus's per-screen "display" items (e.g. "500 Years" appearing as the words are said) are a
  separate change.

## Verification (2026-10-21)
- `npm run test:js` 735/735, `npm run test:php` all pass. Lint not run: ESLint is not installed
  locally.
- 17 guards broken one at a time (repeat-caption skip, reduced motion, one fade timer, line ends
  with its last phrase, reading minimum, no minimum with a clip, short-piece joining, middle-split
  minimum, "…" break, previous phrase, CJK join, pause kinds, line breaks, wide width): each made
  tests fail. A "whole sentence fits" shortcut survived as an equivalent mutant and was removed.
- Local wp-env, 375 px, Marcus's screen 1–2 copy through the narration filter (caption-only
  fallback, no clips): phrases and previous line as split above; the box stays at the same top
  and height (102 px) and the current line does not move through every change; the next screen
  starts with no previous line; a switch to Mandarin mid-line restarts it in Mandarin phrases.
  Not seen: the fade itself (the browser pane was hidden, so CSS transitions did not run there),
  reduced motion in a browser, and a real clip (none rendered yet).

