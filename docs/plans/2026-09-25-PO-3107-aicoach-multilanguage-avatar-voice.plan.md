---
name: PO-3107 (FR-16) — one avatar/voice, pre-rendered clips in all 7 languages
overview: >
  Confirmed with product (Ivan/Filip, 2026-09-25): FR-16 is a SINGLE avatar and voice
  for all seven languages, not a different avatar per language — removing the open
  product decision this ticket was blocked on. What was left was purely technical: the
  PO-3062 pre-rendered pipeline (POST /coach/v1/videos et al) only ever rendered one
  clip per segment, always English, with no language axis at all. Verified live
  (2026-09-25) with a short real render that the single avatar/voice sounds right
  speaking a non-English language through this same pipeline before building the rest.
todos:
  - id: verify-voice-quality
    content: "Rendered a short 3-sentence Japanese test clip through the real
      /coach/v1/videos pipeline (same avatar/voice as English) and sent it to the user
      to judge, since audio quality can't be assessed from this session. Confirmed:
      'glas zvuci super' (voice sounds great)."
    status: completed
  - id: backend-per-language-storage
    content: "inc/aicoach-prerender.php: manifest/file naming gets a language axis.
      English keeps the exact original bare segment_key (no suffix) so already-rendered/
      deployed English clips are never touched or re-rendered by this change; other
      languages get a segment_key__language manifest key / segment_key-language.mp4
      filename. ihq_aicoach_prerender_get_urls() now returns segment_key -> language ->
      url (was segment_key -> url). ihq_aicoach_prerender_segment() takes a $language
      param threaded through the Idempotency-Key, manifest key and filename."
    status: completed
  - id: translated-scripts-source
    content: "New inc/aicoach-segment-translations.php — OUR OWN content (Gary's
      registration/scripts manifest is English-only, confirmed live:
      registration_languages: [\"en\"]), one function returning segment_key -> language
      -> text. ihq_aicoach_prerender_all() renders English from Gary's manifest as
      before, then loops this file's translations per segment, hashing the translated
      text itself for the fingerprint/Idempotency-Key (no Gary sha256 exists for
      non-English)."
    status: completed
  - id: source-the-translations
    content: "The Google Sheet linked on the epic was incomplete when first checked
      (Mandarin/Cantonese empty for 9 of 11 tabs) — waited for it to be completed rather
      than build on partial data. Once the user sent the full .xlsx, parsed it
      programmatically (openpyxl in a venv, not by hand — LibreOffice was available but
      only converts the active sheet; Drive's own file-reader tool truncates long cells
      with its own summarization, confirmed by comparing its output against the real
      cell content via the sheet's own formula bar) into per-tab CSVs, then
      PROGRAMMATICALLY matched each segment's row range by reconstructing the English
      column and diffing it byte-for-byte against the approved string already in
      SCREENS/EQUITY_SCREENS (js/aicoach-coach-flow.js) — not hand-transcribed, to
      eliminate transcription risk on text that gets spoken aloud. 4 of 5 segments
      matched exactly; magic_johnson's sheet English has a harmless \"or the first
      time…\" vs the approved \"for the first time…\" (a typo in the sheet's own English
      column, not a translation error — every language's actual translation still says
      \"for the first time\")."
    status: completed
  - id: time-selection-gap
    content: "time_selection has no translation entry, and isn't a silent gap: the
      sheet's 'Time Ask' tab turned out to only hold the tier-card UI labels (2/5/10
      minutes, step lists), not the spoken 'Now it's your turn…' narration — that
      translation doesn't exist anywhere yet. Documented in the new file's own
      docblock so it isn't lost; getPrerenderedUrl() already falls back to the English
      clip for it like any other not-yet-rendered case."
    status: completed
  - id: frontend-locale-aware-playback
    content: "js/aicoach-coach-flow.js: getPrerenderedUrl(panelKey, locale) now looks up
      the nested structure and falls back to English if the current locale has no clip
      for that segment yet — same 'missing just means not ready' degrade as the rest of
      this feature. The one call site (runFallback()'s loop) now passes currentLocale."
    status: completed
  - id: verify
    content: "php -l / node --check clean on every changed/new file. tests/gary-proxy.test.php
      (untouched by this change) still all-pass. Live end-to-end on wp-env: \`wp aicoach
      prerender\` rendered all 30 new segment+language combinations (5 segments x 6
      languages) with zero errors, on top of the 6 already-cached English clips —
      confirmed via ihq_aicoach_prerender_get_urls()'s real output shape. Seeded progress
      to resume directly into the Korean flow and confirmed via the actual page (not a
      direct fetch) that the video element's real src is the Korean clip for TWO
      consecutive segments (we_believe_1-ko.mp4 then we_believe_2-ko.mp4), proving
      locale-aware selection isn't a one-off fluke."
    status: completed
---

# PO-3107 (FR-16) — one avatar/voice, pre-rendered clips in all 7 languages

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude Code (Sonnet 5)

## Problem

FR-16 was blocked on a product decision ("each language has its own avatar with its
own voice" — does that mean a visually different avatar per language, or the same one
with a different voice?). Ivan/Filip resolved it on a call, 2026-09-25: one avatar, one
voice, for all seven languages. That leaves a purely technical gap: the PO-3062
pre-rendered pipeline never had a language axis at all — every segment rendered exactly
one clip, always from Gary's English-only registration/scripts manifest.

## Approach

1. **Verify the actual voice quality first**, before building anything, since a single
   avatar/voice speaking six unfamiliar languages through TTS is a real open question
   the decision itself doesn't answer. Rendered a short real clip through the live
   pipeline; the user confirmed it sounds right.
2. **English stays byte-identical in shape** — same manifest key, same filename, same
   Idempotency-Key — so this change is purely additive to already-deployed content;
   nothing gets re-rendered or orphaned. Non-English gets a `__{language}` / `-{language}`
   suffix throughout.
3. **Translated scripts are OUR OWN content**, not Gary's — his manifest has no
   non-English text to fetch (confirmed live: `registration_languages: ["en"]`). A new
   file holds them, hashing the text itself for idempotency since there's no Gary sha256
   for a translation.
4. **Sourced the actual translations from the real Google Sheet, verified
   programmatically, not by hand.** The sheet was incomplete on first check (see
   PO-3106/3114 notes) — waited rather than build on partial/guessed content. Once
   complete, parsed the real .xlsx (not screenshots, not the Drive reader tool's own
   truncated summaries) and matched every segment's exact row range by diffing the
   reconstructed English against the approved script already in the codebase, so a
   mismatch would surface as a diff failure instead of silently shipping wrong text to
   a text-to-speech pipeline.

## Known gap

`time_selection` (the "Now it's your turn…" tier-selection narration) has no
translation anywhere yet — the sheet's "Time Ask" tab is the tier-card UI labels only.
Not fixed here; flagged in the new file's own docblock and via
`getPrerenderedUrl()`'s existing English fallback, same as any other not-yet-rendered
segment/language.

## Blast radius

- `inc/aicoach-prerender.php`: manifest/file-naming scheme gains a language axis
  (English unchanged); `ihq_aicoach_prerender_get_urls()`'s return shape changes from
  flat to nested (its only consumer, `inc/anam-proxy.php`'s localize call, just passes
  the array through untouched — no PHP-side reader to update).
- New `inc/aicoach-segment-translations.php`, required in `functions.php` before
  `aicoach-prerender.php`.
- `js/aicoach-coach-flow.js`: `getPrerenderedUrl()` gains a `locale` param; one call
  site updated.
- No changes to Gary's Coach API usage, no changes to the live intro path (still
  English-only, unaffected — that's a separate Gary-side `registration_languages` gate,
  not something this ticket touches).
