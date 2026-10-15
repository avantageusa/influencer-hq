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
    content: Compare with instance 46893:117629 frames 3, 4, 5 and 12 at 398 px; restyle to the design (white 14 px ExtraBold, green squares, red ih diamonds); open design questions in Notes
    status: completed
  - id: verify
    content: test:js, lint on touched files
    status: completed
  - id: review-fixes
    content: PR #126 review — answer saves in click order on the engine's save queue (ENGR-7566 000fa74; the screen-level queue was dropped), radio keyboard pattern, sub group named by its revealing option, no write on re-pick, named diamond token and glyph, failed-save test
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
- 398 px design check (Figma instance 46893:117629, frames "3 XP level", "4 Streaming Experience",
  "5 Streaming Experience", "12 Your Followers"), CSS only: question and answers in white Be Vietnam
  Pro ExtraBold 14 px (answers uppercase); each answer a 20 px square with a 2 px green border that
  fills green when picked, not a bordered row; answers 88 px from the edge, 56 px apart; sub-answers
  under their parent's label; screen 3's levels drawn as the red ih diamond
  (images/logo-hero-red-small.png, 26 x 32, 41 px apart) with only the lit ones shown. The unlit
  diamonds and the glyph stay in the markup, so the tests and data hooks are unchanged.
- Open for design: screen 3's first answer is 23 px higher than on 4 and 5 (frame 4 used); screen 12's
  question is 12 px SemiBold and screen 5's answers 11 to 13 px with uneven spacing (14 px ExtraBold
  and frame 4's spacing used); screen 5 draws the sub-answers under I CURRENTLY OWN EQUITY, but they
  are a separate radio group after the three answers, so they show after the third (moving them needs
  a markup and ARIA decision); the design has no CONTINUE on these screens, and the engine's CONTINUE
  pushes the Save button below the fold.
