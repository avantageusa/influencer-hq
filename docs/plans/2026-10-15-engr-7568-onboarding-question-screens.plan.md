---
name: Onboarding question screens 3, 4, 5 and 12 with conditional sub-options
overview: >
  Four single-choice onboarding questions: baccarat experience (with diamond XP indicators), streaming
  experience and equity knowledge (each with a conditional, required pair of sub-options) and active
  followers. Answers are saved to the account store as they are given, as enum values. Built as one
  plug-in for the screen engine, on ENGR-7566 with ENGR-7563 (the store) merged in. The appointment
  branch from "I'M READY TO LEARN" (T16) is out of scope.
todos:
  - id: module
    content: js/aicoach/onboarding-screens-questions.js — questions as data, pure pick/validation helpers, renderer
    status: completed
  - id: css
    content: css/portal-onboarding-questions.css, no media queries
    status: completed
  - id: tests
    content: every option and stored value, reveal and clear, required sub-option, resume, enum cross-check against the PHP store
    status: completed
  - id: design-check
    content: Compare with frames 46497:79353, :79698, :79730, :79465 at 398 px
    status: blocked
  - id: verify
    content: test:js, lint on touched files
    status: completed
  - id: review-fixes
    content: PR #126 review — serial answer-save queue, radio keyboard pattern, sub group named by its revealing option, no write on re-pick, named diamond token and glyph, failed-save test
    status: completed
---

# [ENGR-7568] Question screens 3, 4, 5 and 12 with conditional sub-options

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7568
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
The engine needs the four question screens, and their answers need to reach the Influencer's account
(and from there Braze) as soon as they are given.

## Approach
- `QUESTIONS` holds each screen as data: stored field, question key, options with stored values, and
  for 4 and 5 the revealing option with its sub-field (`streaming_learning_intent`,
  `equity_management`) and sub-options. Values are the store's (ENGR-7563, EQ1); a test parses
  `ihq_portal_onboarding_answer_values()` from the PHP and compares field by field, and another
  compares the engine's sequence with `ihq_portal_onboarding_stages()`.
- Pure helpers: `pickMain()` (switching away from the revealing option clears the sub-answer and
  saves it as null), `pickSub()` (only a sub-option of the picked answer), `missingAnswer()`
  (what blocks CONTINUE), `storedAnswer()` (resume).
- The renderer draws a radio group of buttons (`role="radio"`, `aria-checked`) named by the question,
  the sub-group under it (hidden until revealed), and an `role="alert"` message shown when CONTINUE
  is pressed without a complete answer. Each pick calls `api.save( { answers } )` at once.
- Screen 3's options show 3, 2 and 1 lit diamonds out of three (decorative, `aria-hidden`).
- Screen 12's last option reads "Under 10,000" and stores `under_10k` (OQ16).

## Alternatives considered
- Native radio inputs: equivalent; buttons with radio roles match the landing flow's option buttons
  and the design's full-width tappable rows.
- Saving answers only on CONTINUE: the ticket asks for answers to be written as they are given.

## Blast radius
A new plug-in and stylesheet. The branch also carries ENGR-7563 (merged in), so it depends on #121.

## Notes
- The two validation messages ("Choose an answer to continue.", "Choose one of the options below your
  answer to continue.") are new copy; they are in the string table for review and translation.
- The design frames could not be opened through the Figma MCP in this session; visual match is
  unchecked.
