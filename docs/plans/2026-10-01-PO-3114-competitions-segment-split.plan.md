---
ticket: PO-3114 (NFR-05 — complete avatar/asset/translation parity across 7 languages)
date: 2026-10-01
status: implemented, render not yet triggered
---

## Problem

`inc/aicoach-prerender.php` deliberately excluded the three competition-type
screens (`competition-world`/`competition-community`/`competition-private`)
from pre-rendering: Gary's registration script had one combined
`competitions` segment, this page shows three separate panels, and there was
no single clip that fit all three. That gap blocked PO-3114 regardless of
translation completeness — no video asset could exist for these screens in
any language, including English.

## Approach

Checked Gary's live script manifest (`GET /coach/v1/registration/scripts`)
directly rather than assume the old gap still held. Confirmed: the old
combined `competitions` segment is now `status: superseded`, replaced by
three separate `approved` segments (`world`, `community`, `private`) whose
English text is verbatim-identical to this page's own
`COMPETITION_SCREENS` in `js/aicoach-coach-flow.js`. The product-side
decision this gap was waiting on had already been made upstream.

Checked the Google Sheet's own World/Community/Private tabs the same way —
not assumed complete because a colleague said translations were "done."
Confirmed all three tabs carry complete translations across all 7 languages,
text matching Gary's approved English exactly row for row. Downloaded each
tab as CSV via the Sheet's own File > Download menu (not hand-transcribed —
same reasoning as the existing top-of-file note in
`aicoach-segment-translations.php`: a wrong transcription in a script that
gets spoken aloud via lip-synced TTS is a real risk for CJK/Thai/Vietnamese
text, not just a stylistic concern).

Changes:
- `inc/aicoach-prerender.php`'s `ihq_aicoach_prerender_panel_map()` and
  `js/aicoach-coach-flow.js`'s `PRERENDERED_PANEL_MAP` (the two hand-synced
  copies of the same mapping) both gained the three new panel → segment-key
  entries.
- `inc/aicoach-segment-translations.php` gained `world`/`community`/`private`
  entries, each segment's text reconstructed from the sheet's narration rows
  only (every tab also carries non-narration UI/card-label rows at the top,
  which aren't part of the spoken segment).
- Private's sheet tab carries two extra trailing rows ("Now that you've seen
  all three options... let's choose the one you'd like to start with.") that
  are NOT part of Gary's approved text — the ticket's own closing-line
  removal, still pending a replacement (see the NOTE already above
  `COMPETITION_SCREENS`). Excluded from the translation, matching what the
  English script already excludes.
- Two source-sheet oddities carried through verbatim rather than silently
  "fixed," flagged in a code comment instead (same convention as the existing
  magic_johnson typo note): Community's Mandarin row 14 contains a Cantonese
  possessive particle ("嘅") instead of Mandarin "的", and Korean's "encourage
  engagement…" row has a stray trailing "t".

## Verified

- `php -l` / `node --check` clean on all three touched files.
- `wp eval` (no Gary API calls) confirms `ihq_aicoach_prerender_panel_map()`
  now includes all three new entries and `ihq_aicoach_segment_translations()`
  returns all 7-minus-English languages for each.
- Did NOT run `wp aicoach prerender` — that hits Gary's real Coach API and
  creates real render jobs (3 segments × 7 languages = up to 21), a cost/time
  decision left to whoever deploys this, not something to trigger
  speculatively while prepping the code.

## Blast radius

Additive only — no existing panel/segment mapping changed, no existing
translation entry touched. A visitor on any tier that reaches the
competition screens today falls back to the existing static caption (no
pre-rendered clip exists yet); after this merges AND `wp aicoach prerender`
actually runs, those same screens get real lip-synced video, same as every
other pre-rendered segment already does.

## Notes / remaining PO-3114 gap

`time_selection` (the "home"/tier-selection screen's own narration) is still
missing entirely — confirmed live against both Gary's script manifest (which
now DOES have approved English text for it, unlike before) and the sheet
(which does not yet have that specific new text translated in any language,
confirmed via a full-document search, not just the "Time Ask" tab). That's a
genuine upstream content gap, not something this change touches — flagged
separately.
