---
name: Add time_selection translations to the AI Coach segment file
overview: >
  The "Time Ask" tab of the translations sheet only held tier-card UI labels
  when inc/aicoach-segment-translations.php was first written, so the
  time_selection segment shipped with an empty translations array and a
  top-of-file note flagging the gap. The sheet has since been filled in with
  the actual spoken narration in all six non-English languages. This adds
  that data using the same process already used for every other segment in
  this file (download via the sheet's own UI, verify the English column
  against the exact approved string Gary's API returns, reconstruct each
  language's concatenated string, insert into the array) and removes the
  now-stale gap note. No code path changes — ihq_aicoach_prerender_panel_map()
  already mapped 'home' => 'time_selection' before this change.
todos:
  - id: verify-english-alignment
    content: Confirm the sheet's English column (rows 15-26) reconstructs to exactly Gary's approved time_selection string in js/aicoach-coach-flow.js
    status: completed
  - id: add-translations
    content: Replace the empty time_selection array in inc/aicoach-segment-translations.php with the six language strings
    status: completed
  - id: remove-stale-note
    content: Remove the top-of-file comment block describing time_selection as missing
    status: completed
  - id: verify
    content: Lint the changed file and run the existing test suite
    status: completed
---

# PO-3114 Add time_selection translations

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3114
**Drafted by:** Claude (claude-sonnet-5)

## Problem
`inc/aicoach-segment-translations.php` ships `time_selection` as an empty
array because the sheet's "Time Ask" tab didn't have the real narration text
yet when the file was written — only tier-card UI labels, already covered
elsewhere. A colleague has since confirmed the tab was filled in for all
languages.

## Approach
Same process as every other segment in this file:
1. Downloaded the "Time Ask" tab via the sheet's own File > Download menu
   (never hand-transcribe CJK/Thai/Vietnamese text that feeds paid TTS).
2. Rows 1-13 are tier-card UI labels (already in I18N_EN); rows 15-26 are the
   actual narration. Reconstructed the English column from those rows and
   diffed it against the exact approved string at
   `js/aicoach-coach-flow.js:550` — it matches exactly.
3. Reconstructed each language's space-joined string from rows 15-26 and
   inserted it as the `time_selection` entry, replacing the empty array.
4. Removed the top-of-file paragraph that described this as a known gap.

No change to `inc/aicoach-prerender.php` — the `home => time_selection`
panel mapping already existed.

## Alternatives considered
N/A — this follows the established pattern from the `world`/`community`/
`private` segments added earlier for the same file.

## Blast radius
Only `inc/aicoach-segment-translations.php` changes. Affects the `home`
panel's non-English audio/video rendering via
`ihq_aicoach_prerender_get_urls()`; English is unaffected (it never reads
this file). A segment with no entry for a language falls back to the English
clip, so this is additive — no existing behavior regresses if a string here
turns out wrong, it just degrades to the pre-existing fallback.

## Notes
No test file exists for this data file specifically (it's a plain data
array); ran the existing `tests/gary-proxy.test.php` suite to confirm
nothing else in the AI Coach test coverage regressed. wp-env wasn't running
locally at the time of this change, so this wasn't exercised through a live
render; verification here is the same text-match-against-Gary's-approved-
string method used for every other segment in this file, not a full TTS
render.
