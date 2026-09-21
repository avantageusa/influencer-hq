---
name: PO-3103 — Language auto-detected from browser locale, with English fallback
overview: >
  Adds automatic initial-locale detection to the AI Coach flow (js/aicoach-coach-flow.js),
  built on top of PO-3104's existing data-i18n/currentLocale infrastructure. On page load,
  currentLocale is now set from the visitor's browser locale (navigator.languages, matched
  against SUPPORTED_LOCALES) instead of always starting at 'en' — driving the language
  selector's initial state, the on-screen text (applyLocale), and the very first Gary
  session's locale param (openGarySession(currentLocale) in start()). Falls back to English
  with no error for any unsupported or missing locale. Explicitly NOT implemented: the
  ticket's first-priority detection source, "saved Luna preference from a previous session"
  — Luna session persistence (PO-3102) doesn't exist yet, so there is nothing to read.
todos:
  - id: detect-function
    content: Added detectInitialLocale() — walks navigator.languages (falling back to
      navigator.language) in order, matches each tag's primary subtag against
      SUPPORTED_LOCALES' codes, with a specific zh-HK/zh-MO → yue (Cantonese) carve-out
      since real browsers essentially never report a bare "yue" primary subtag; returns
      'en' if nothing matches or the list is empty
    status: completed
  - id: wire-into-init
    content: "currentLocale now initializes from detectInitialLocale() instead of a
      hardcoded 'en'; added one applyLocale(currentLocale) call right after the language
      selector is built, so detected-locale text actually reaches the DOM on load (a
      no-op today since all 6 non-English I18N_TRANSLATIONS tables are still empty, but
      correct and future-proof once real translations land)"
    status: completed
  - id: verify
    content: "node --check; unit-verified detectInitialLocale()'s matching logic in isolation
      against 14 representative navigator.languages inputs (en-US, ja-JP, ko-KR, zh-CN,
      zh-TW, zh-HK, zh-MO, bare zh, th-TH, vi-VN, yue-HK, unsupported-only, unsupported-then-
      supported, empty) — all resolved correctly; live-tested on wp-env with the browser's
      real (English) locale: Gary session opened with locale:'en', dropdown correctly marked
      English current; manually re-verified the existing manual selector (click → 日本語)
      still switches correctly, confirming no regression from the initialization change"
    status: completed
---

# PO-3103 Language auto-detected from browser locale, with English fallback

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude Code (Sonnet 5)

## Problem

FR-12 (Scenario 22/23) requires the flow to open in the visitor's own language automatically,
detected in priority order: saved Luna preference → browser locale → English. Since PO-3104
(the manual language selector, PR #23), `currentLocale` has always hardcoded to `'en'` at
init — nothing read the browser's actual locale.

## Approach

1. **`detectInitialLocale()`** — a pure function (no DOM dependency) added next to the other
   i18n helpers. Walks `navigator.languages` in the order the browser reports them (falling
   back to the single `navigator.language` on older browsers), and for each tag checks its
   primary BCP-47 subtag against `SUPPORTED_LOCALES`' codes. One deliberate special case:
   Cantonese (`yue`) is a valid primary subtag but browsers essentially never report it in
   practice — Hong Kong/Macau visitors report `zh-HK`/`zh-MO`. Those two region tags map to
   `yue`; every other `zh-*` region (or bare `zh`) maps to Mandarin (`zh`), matching how
   `SUPPORTED_LOCALES` already splits the two. Returns `'en'` if nothing in the list matches
   a supported code — satisfies Scenario 23's "no error or empty state" directly, since
   English always has real content.
2. **Wired into init, not into `selectLocale()`.** `let currentLocale = 'en'` became
   `let currentLocale = detectInitialLocale()`. This one change is enough for the rest of the
   already-existing PO-3104 machinery to pick it up correctly: `buildLanguageSelector()` (runs
   right after) marks the detected locale's option `is-current` from that same variable, and
   `start()`'s `openGarySession(currentLocale)` (runs later, same closure) now opens the very
   first Gary session in the detected locale — satisfying FR-12's "the avatar, voice... load
   in the detected language" without touching `start()` at all.
3. **One `applyLocale(currentLocale)` call added**, right after the language-selector IIFE, so
   detected-locale text actually reaches `data-i18n`/`data-i18n-attr` elements on load — before
   this PR nothing called `applyLocale()` until a visitor manually picked a language from the
   dropdown. Today this is a no-op for every locale but English (all 6 translation tables are
   still empty, `t()` falls back to `I18N_EN` either way) — but it's the technically correct
   behavior, and needs no further change once real translations land.

## Known, deliberate scope gap — flagged, not built around

**Priority #1 of FR-12's detection order ("saved language preference from a previous session,
retained indefinitely via Luna") is not implemented.** Luna session persistence is PO-3102,
still "To Do" — there is no persisted-preference store to read from yet. Detection here only
covers priority #2 (browser locale) and #3 (English fallback). Revisit once PO-3102 ships;
the natural hook is to check the saved preference first inside `detectInitialLocale()`,
before falling through to the browser-locale loop.

This was raised and confirmed with Dejan before writing any code, same "don't build around a
missing dependency silently" pattern as everywhere else in this epic.

## Alternatives considered

- **IP-based geolocation as a detection signal**: explicitly forbidden by the ticket itself
  ("IP-based geolocation is not used") — not considered.
- **Matching only the exact `navigator.language` tag** (first entry only) rather than walking
  the full `navigator.languages` preference list: rejected — a visitor whose top preference is
  an unsupported language (e.g. `fr-FR`) but who has a supported one further down their list
  (e.g. `en-US`) should get that, not fall straight to English; `navigator.languages` exists
  for exactly this.

## Blast radius

- `js/aicoach-coach-flow.js`: one new pure function (`detectInitialLocale`), one line changed
  (`currentLocale` init), one new `applyLocale()` call at init. `selectLocale()`,
  `buildLanguageSelector()`, `start()`/`openGarySession()` all unchanged — they already read
  `currentLocale` from the shared closure variable, so detection "just works" through them.
- No PHP changes.

## Notes

- Same "never invent unapproved copy" rule as PO-3104: detecting a non-English locale today
  still renders English text/avatar responses, because that's genuinely the only approved
  content that exists — this is correct behavior, not a bug, until real translations and
  Gary-side locale support (currently `supported_locales: ["en"]` per every live Gary
  response) arrive.
