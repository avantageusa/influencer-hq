---
name: Seven-language onboarding strings and the in-flow language selector
overview: >
  The onboarding has to open in the Influencer's language (registration language, else browser, else
  English) and let them switch among seven languages from the header globe, which re-labels every
  onboarding string, restarts the screen's clip in the new language, keeps the position and answers,
  and saves the choice. This adds the language logic, the globe list and the translation table on top
  of the shell's string table. No approved translations of the onboarding copy exist yet, so every key
  falls back to English except CONTINUE, whose approved translations the landing flow already has.
todos:
  - id: language-module
    content: js/aicoach/onboarding-language.js — initialLocale, translations, globe list, installLanguage
    status: completed
  - id: addon
    content: js/aicoach/onboarding-addon-language.js wires it to locales.js, navigator.languages and the store
    status: completed
  - id: php
    content: shell config carries savedLanguage (_ihq_aicoach_language); locales.js registered as a shared module
    status: completed
  - id: translations
    content: Add approved zh/yue/ja/ko/th/vi copy for the onboarding keys when supplied
    status: blocked
  - id: verify
    content: test:js, lint on touched files, CI PHP job
    status: completed
---

# [ENGR-7572] Seven-language onboarding strings and in-flow language selector

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7572
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
The shell (ENGR-7565) has a string table with English fallback, but nothing picks the language,
offers a switch, or saves the choice.

## Approach
- `initialLocale()`: `_ihq_aicoach_language` (passed as `config.savedLanguage`; the store's `language`
  as a second source), else `detectLocale( navigator.languages )` from `locales.js`, else `en`.
- `createLanguageSelector()`: fills the globe's list with the seven `SUPPORTED_LOCALES`, each in its own
  language (`lang` attribute set), marks the active one (`aria-current`, a tick), and on a choice calls
  `api.setLocale()` and saves `{ language }` to the account store (which also writes
  `_ihq_aicoach_language`, ENGR-7563). The 'locale' event does the rest: the shell re-labels the frame,
  each screen re-labels itself, and the engine (ENGR-7566) restarts the screen's clip in the new
  language. Nothing is re-rendered, so position and answers stay.
- `ONBOARDING_TRANSLATIONS`: only approved text, as `locales.js` does. Today that is the landing flow's
  approved `continue` in all six languages, reused for the engine's CONTINUE. Every other key falls
  back to English per key; `fallbackKeys()` lists them, and the tests assert each falls back explicitly.
- The plug-in wrapper imports `locales.js` by bare specifier, so the shell registers `locales` as a
  shared module.

## Alternatives considered
- Machine translations of the onboarding copy: the repo's rule (locales.js) is never to ship
  unapproved translations; missing keys fall back to English and are listed in the PR.
- A separate language endpoint: the store already validates and saves `language`.

## Blast radius
Shell PHP config (one more field), the shared-module list, the frame stylesheet (language list styles,
placed mid-file) and the shell's PHP test. Nothing renders until ENGR-7564 fires the layer.

## Notes
- Keys falling back to English in all six languages at the time of this PR: every shell key
  (`menuOpen`, `menuClose`, `logOut`, `soundMute`, `soundUnmute`, `languageSelect`, `saveToHomeScreen`)
  and every screen key added by ENGR-7567, ENGR-7568, ENGR-7569 and ENGR-7571. Only `continue` is
  translated.
- One avatar and voice for all languages (OQ10): clips per language come from the shell's clip map.
