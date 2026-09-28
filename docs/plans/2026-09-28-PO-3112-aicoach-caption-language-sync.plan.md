---
name: PO-3112 (NFR-03) — on-screen caption matches the avatar's actual language
overview: >
  Live verification on dev surfaced a real gap: for every pre-rendered
  segment (we_believe_1/2, magic_johnson, alix_earle, bts), the avatar video
  already plays in the visitor's selected language (PO-3107), but the
  on-screen caption underneath it stayed English regardless — a direct
  Scenario 35 violation ("content matches the approved script for that
  language"). The translated text already existed server-side
  (inc/aicoach-segment-translations.php, built for PO-3107's video
  rendering); it just wasn't exposed to the frontend caption display.
todos:
  - id: expose-segment-scripts
    content: "inc/anam-proxy.php's AICOACH_SAMI localization gains
      segmentScripts (segment key -> language -> text), sourced from the same
      ihq_aicoach_segment_translations() PO-3107 already uses to render the
      clips — no new content, just exposing what already exists."
    status: completed
  - id: caption-lookup
    content: "js/aicoach-coach-flow.js: new getCaptionScript(panelKey, locale),
      mirroring getPrerenderedUrl()'s shape but without an 'en' fallback
      inside the data itself (English lives in SCREENS/EQUITY_SCREENS's own
      .script) — the caller falls back to .script when this returns null,
      same 'missing means not ready yet' degrade as the video."
    status: completed
  - id: wire-into-runfallback
    content: "runFallback()'s caption assignment now calls
      getCaptionScript(screen.panel, currentLocale) || screen.script instead
      of the bare screen.script. Re-resolved after panelReady (not just at
      the top of the loop iteration), matching the same re-resolve-after-await
      fix already applied to the clip URL in PR #55 — a locale change during
      showPanel()'s fade window must not leave a stale-language caption any
      more than a stale-language clip."
    status: completed
  - id: wire-into-restart
    content: "restartCurrentClipForLocale() (PO-3105) now also updates the
      caption text right after swapping the video, so a language change
      mid-screen keeps both in sync — not just a fresh screen load."
    status: completed
  - id: verify
    content: "node --check / php -l clean. Verified live on wp-env: (1) fresh
      load into believe-2/ko showed the correct Korean caption text matching
      we_believe_2-ko.mp4. (2) Mid-flight language switch on equity-magic
      (English -> Korean) correctly swapped both video.currentSrc to
      magic_johnson-ko.mp4 AND the caption text to the matching Korean
      script, confirmed together in one check. No console errors."
    status: completed
---

# PO-3112 (NFR-03) — on-screen caption matches the avatar's actual language

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude Code (Sonnet 5)

## Problem

NFR-03 (Scenario 35) requires the on-screen text to match the approved
script "word for word" for whatever language is playing. Live-testing PO-3104/
3105 on dev turned up a real, previously-unnoticed miss: the avatar video
correctly plays in the visitor's language (PO-3107), but the caption
underneath it was hardcoded to `screen.script` — always English — for every
locale. A Korean-speaking visitor watching `we_believe_2-ko.mp4` saw an
English paragraph under it.

## Approach

The fix is additive, not new content: `inc/aicoach-segment-translations.php`
already has exactly the text each non-English clip says (it's literally what
was rendered into the clip). Exposed it to the frontend the same way
`prerenderedVideos` already is, added a lookup mirroring
`getPrerenderedUrl()`, and wired it into both places a caption gets set —
the normal screen-load path and PO-3105's mid-flight restart path — so video
and caption always agree on which language they're in.

## Alternatives considered

- **Render the caption text server-side into the page markup per locale.**
  Would need a full page reload per language instead of the existing
  client-side instant switch every other piece of FR-13/FR-14 already does;
  rejected for breaking that pattern for no benefit.
- **Store translated captions as a separate copy inside
  aicoach-coach-flow.js** (duplicating aicoach-segment-translations.php's
  text). Rejected — two copies of TTS-spoken text drifting apart is exactly
  the transcription-risk category this epic has avoided everywhere else;
  reusing the single PHP source of truth means a script correction only
  needs to happen once.

## Blast radius

- `inc/anam-proxy.php`: one new key in the existing `AICOACH_SAMI`
  localization, no new endpoint.
- `js/aicoach-coach-flow.js`: one new pure lookup function, two call sites
  changed from a bare property read to `lookup(...) || screen.script`. No
  change to `SCREENS`/`EQUITY_SCREENS`/`COMPETITION_SCREENS` themselves.

## Notes

Still not covered, because no translation exists anywhere yet (same gap
already tracked for FR-15/NFR-05): `time_selection`'s spoken narration, and
all three competition screens (world/community/private) — those captions
stay English until that content is sourced. NFR-03 also formally requires
"any proposed script change requires product owner sign-off before
implementation" — our translated scripts came from the shared Google Sheet,
not a formal PO sign-off workflow; that's a process gap, not something this
change addresses.
