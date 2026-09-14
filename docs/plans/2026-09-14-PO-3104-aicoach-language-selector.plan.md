---
name: PO-3104 — Manual language selector (FR-13)
overview: >
  Adds a language selector (globe icon, 7 native-language labels per the ticket's own
  verbatim wording) injected into the shared header's nav row, plus a full data-i18n
  infrastructure that swaps every static on-screen string across the whole flow.
  English is the real, approved copy; the other six languages are deliberately left
  empty (not invented) pending real translations, falling back to English — same
  "never invent unapproved copy" rule this epic has followed throughout. Avatar/voice
  switching (part of Scenario 25) is explicitly NOT done in this PR — see Notes.
todos:
  - id: investigate-existing-header-lang-stub
    content: "Found a pre-existing .header-lang-wrap/#headerLangBtn in template-parts/portal-header.php — confirmed via git blame (Milos, 2026-05-19, \"11labs language\") it belongs to the unrelated ElevenLabs concierge widget, has a mismatched 5-language list (en/es/fr/de/zh), and is display:none/unstyled. Left untouched; built a separate selector instead."
    status: completed
  - id: language-selector-ui
    content: "New selector (button + dropdown, 7 languages, native labels verbatim from the ticket) built in JS and injected into the shared header's .desktop-header-left-items row — safe since js/aicoach-coach-flow.js only ever loads on this one page (is_page_template check in ihq_aicoach_enqueue_coach_flow())"
    status: completed
  - id: i18n-infrastructure
    content: "data-i18n / data-i18n-attr attributes added to every static esc_html_e()/esc_attr_e() string in page-home-aicoach.php (~45 strings) plus the dynamically-rendered tier-item and channel label/inputLabel/placeholder arrays; applyLocale() in aicoach-coach-flow.js walks and swaps them; t() falls back to English for any missing key"
    status: completed
  - id: i18n-english-table
    content: I18N_EN populated with the real, already-approved English copy (mirrors what was already server-rendered); the six other locale tables are empty objects, not invented placeholder text
    status: completed
  - id: verify
    content: php -l, node --check, phpcs (delta proportional to new content, same pre-existing space-indentation debt), live wp-env testing across desktop viewport — selector opens/closes, all 7 native labels render correctly, switching locale updates the current-language indicator and falls back to English text/attributes cleanly with no console errors
    status: completed
---

# PO-3104 Manual language selector — Scenario 24/25 (FR-13)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3104 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Problem

A language selector (globe icon, top nav) must be available on every screen, listing all 7
supported languages in their own native label with the active one indicated. Selecting a language
must immediately switch the avatar/voice and every piece of on-screen text (labels, placeholders,
buttons, tooltips, validation messages).

## Approach

1. **Don't reuse the existing header language stub.** `template-parts/portal-header.php` already
   has `.header-lang-wrap`/`#headerLangBtn` markup, but git history (`6f9ebd3`, "11labs language",
   Milos Martinovic, 2026-05-19) shows it belongs to the site-wide ElevenLabs concierge widget —
   different feature, mismatched language list (en/es/fr/de/zh, not our 7), currently
   `display:none` and entirely unstyled (no CSS anywhere in the repo targets `.header-lang-*`).
   Reusing or editing it risked breaking that other, unrelated feature. Built a fully separate
   `.aicoach-lang-*` selector instead.
2. **Inject via JS, not by editing the shared header template.** `js/aicoach-coach-flow.js` only
   ever loads on this one page (`ihq_aicoach_enqueue_coach_flow()`'s `is_page_template` check), so
   appending the new selector into the header's existing `.desktop-header-left-items` row at
   runtime is safe — zero risk to `portal-header.php` itself, and it still visually lands in the
   ticket's required "top navigation bar" location.
3. **Native labels are the ticket's own text, verbatim** — "English | 普通话 | 廣東話 | 日本語 |
   한국어 | ไทย | Tiếng Việt" — not invented. Locale codes (`en/zh/yue/ja/ko/th/vi`) match Gary's
   `approved_languages` exactly (confirmed via `GET /coach/v1/health`, PO-3062 memory notes).
4. **`data-i18n` / `data-i18n-attr` attributes + `applyLocale()`.** Every static translatable
   string in `page-home-aicoach.php` got a `data-i18n="key"` (text content) or
   `data-i18n-attr="attr:key"` (for `placeholder`/`aria-label`) attribute — including the
   dynamically-rendered tier-item bullets and the 8 comm-channel labels/input-labels/placeholders,
   keyed programmatically off their existing `$tier['key']`/`$channel['key']` PHP array values
   rather than hand-typed once per string. `applyLocale(locale)` in the JS walks both attribute
   selectors and sets text/attributes from `I18N_TRANSLATIONS[locale]`, falling back to
   `I18N_EN` via `t()` for any missing key.
5. **English is real; the other six are empty on purpose.** `I18N_EN` has the actual approved copy
   (identical to what was already hardcoded in the PHP). `I18N_TRANSLATIONS.zh/yue/ja/ko/th/vi` are
   all `{}` — not machine-translated or invented placeholder text. Selecting any of those languages
   updates the selector's own "current" indicator correctly but every string falls back to English
   until real, approved translations are supplied — exactly the same handling this epic has used
   for every other TBD piece of content (FR-08's channel formats, FR-17's threshold/copy).

## Alternatives considered

- **Editing `template-parts/portal-header.php` directly** to add the real selector there instead
  of injecting via JS: rejected — that file is shared across every portal page and actively touched
  by Milos's own ongoing work; a JS-injected, page-scoped approach achieves the same visual result
  with zero shared-file risk.
- **Machine-translating or drafting plausible copy for the other 6 languages**: rejected outright,
  per explicit direction and this epic's established rule — an invented-but-plausible translation
  risks quietly surviving unreviewed once real copy is supplied, and the ticket's own language list
  is explicitly marked "subject to localisation review" even for the native *labels*, let alone the
  rest of the UI text.
- **Generating the channel/tier i18n keys from a single shared PHP↔JS data source** (to avoid
  hand-duplicating the channel list's English text into `I18N_EN`): would be cleaner long-term, but
  a bigger refactor than this story needs; noted as a minor follow-up, not blocking.

## Blast radius

- `page-home-aicoach.php`: `data-i18n`/`data-i18n-attr` attributes added throughout (no visual/
  behavioral change to the existing English render — same strings, same source), plus one new CSS
  block for `.aicoach-lang-*`.
- `js/aicoach-coach-flow.js`: new `SUPPORTED_LOCALES`/`I18N_EN`/`I18N_TRANSLATIONS`/`t()`/
  `applyLocale()` plus the selector-building/wiring code, all additive — no existing function
  (`showPanel`, `finishSequence`, `runFallback`, etc.) was changed.
- `template-parts/portal-header.php`: **not touched.**

## Notes — explicitly NOT done in this PR

Scenario 25 requires selecting a language to also switch "the avatar and voice." **This PR does
not do that.** The avatar/video is still driven entirely by the original interim direct-Anam-SDK
integration (PO-3092/3093), which has no concept of locale at all. Making language selection
actually change the avatar/voice requires reconnecting that avatar driver to `inc/gary-proxy.php`'s
`/coach/session` (Gary's API is locale-aware; the interim Anam one isn't) — a separate, larger
architectural swap flagged back when `inc/gary-proxy.php` was built (PR #22) and intentionally not
folded into this story. Tracked as the next piece of work on this epic.

Also out of scope for this PR, flagged rather than silently skipped:
- **`AICOACH_SAMI.i18n`** (the JS validation-message strings — `usernameTaken`, `identitySaved`,
  `channelInvalid`, `accountCreateErr`, localized server-side in `inc/anam-proxy.php`) stays
  English-only regardless of selected locale. Wiring these into the same `I18N_TRANSLATIONS` table
  is straightforward follow-up, not done here to keep this PR's diff to the core mechanism.
- Decorative/accessibility `alt` text (Magic Johnson, BTS, Alix Earle photos, the "AI Coach" section
  `aria-label`) isn't covered by `data-i18n` yet — lower priority than visible on-screen copy.
- FR-14 (PO-3105, preserving progress + restarting the current screen's video on language change)
  is explicitly the *next* ticket's job, not this one's — selecting a language here does not
  attempt to replay or restart whatever screen is currently active.

Same known-interim-architecture note as every prior story in this epic — this PR doesn't touch the
Anam integration at all, so it's unaffected either way.
