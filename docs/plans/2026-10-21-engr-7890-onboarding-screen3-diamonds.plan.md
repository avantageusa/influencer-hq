---
name: Onboarding screen 3 without XP diamonds, question at the answers' type size
overview: >
  Marcus's copy review (relayed by Ivan 2026-10-21) says screen 3's red XP diamonds imply one
  baccarat answer is best. Remove them (markup, option data, constants, CSS) and make the
  question and the answers read one shared type size token, so the two cannot drift at 398 px
  or on desktop. Answer layout, selection and the other question screens are unchanged; no new
  breakpoints.
todos:
  - id: plan
    content: Write this plan
    status: completed
  - id: diamonds
    content: Remove the diamonds from js/aicoach/onboarding-screens-questions.js (MAX_DIAMONDS, DIAMOND_GLYPH, option.diamonds, the markup) and css/portal-onboarding-questions.css
    status: completed
  - id: type-size
    content: One --ihq-onb-q-font-size / --ihq-onb-q-line-height pair read by .ihq-onb-q__question and .ihq-onb-q__option
    status: completed
  - id: tests
    content: Screen 3 test asserts no diamonds; drop the glyph test; add a stylesheet test for the shared size and no media queries
    status: completed
  - id: verify
    content: npm run test:js, npm run test:php; check rendered sizes at 398 px and desktop
    status: completed
---

# [ENGR-7890] Onboarding screen 3: no XP diamonds, question at the answers' size

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7890
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
Screen 3 ("What is your experience with Baccarat?") shows 3/2/1 red ih diamonds under its
answers, which reads as a ranking. Product wants them gone and the question at the same type
size as the answers.

## Approach
- `optionButton` stops drawing a level; the baccarat options lose `diamonds`; `MAX_DIAMONDS`
  and `DIAMOND_GLYPH` go. The `.ihq-onb-q__diamonds` / `.ihq-onb-q__diamond` rules go.
  `images/logo-hero-red-small.png` stays (the Equity page uses it).
- Both rules already declare 14 px / 18 px. They now read `--ihq-onb-q-font-size` and
  `--ihq-onb-q-line-height` so the match is one value, not two copies. The file has no media
  queries and the column only widens on desktop (768/1025 px), so the size is the same at
  398 px and on desktop.

## Blast radius
Screens 3, 4, 5 and 12 share `.ihq-onb-q__option`. Screens 15 and the appointment screens reuse
it and override size/case with higher-specificity rules, so they are unaffected. Stored answer
values do not change.
