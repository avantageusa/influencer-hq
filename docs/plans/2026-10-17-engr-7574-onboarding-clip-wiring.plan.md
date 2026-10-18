---
name: Wire Sami's onboarding clips into the pre-render pipeline
overview: >
  The onboarding already plays a clip per screen and language when the page's clips list has
  one, and falls back to captions at reading pace when it does not. Nothing fills that list
  and no screen has a script. This adds one table of narration scripts per screen and
  language (empty until Product approves them, OQ1), renders every filled line through the
  existing `wp aicoach prerender` run, serves the rendered clips to the onboarding, and shows
  the same text as the captions, so the clip and its subtitles always come from one script.
todos:
  - id: plan
    content: Write this plan
    status: completed
  - id: narration-module
    content: Add inc/portal-onboarding-narration.php (script table, segment keys, work items, clips from the manifest)
    status: completed
  - id: prerender
    content: Append the onboarding work items to ihq_aicoach_prerender_work_list()
    status: completed
  - id: config
    content: Send the scripts to the browser in the shell config and add them to the string table
    status: completed
  - id: engine
    content: Engine reads a screen's script from the narration key of its stage when the screen names none
    status: completed
  - id: tests
    content: PHP tests for the narration module and the work list; JS tests for the string helper and the engine default key
    status: completed
  - id: verify
    content: npm run test:js and test:php pass; lint not run (no @wordpress/scripts installed locally); local run with a test script and a dummy clip not done
    status: in-progress
---

# [ENGR-7574] Wire Sami's onboarding clips into the pre-render pipeline

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7574
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
Every onboarding screen should have Sami saying her line, as a pre-rendered clip in the
Influencer's language with subtitles that match it word for word (PO-3201, PO-3225). The
player is built (ENGR-7565, ENGR-7566): `config.clips[stage][locale]` plays with subtitles,
anything missing falls back to captions. What is missing is the supply side: no scripts, no
render, nothing filling `ihq_portal_onboarding_clips`. The approved scripts themselves are
still open (OQ1), so this ticket builds everything except the words.

## Approach
- **One source for the words.** `inc/portal-onboarding-narration.php` holds the scripts as
  `stage => [ locale => text ]`, empty until OQ1 is answered. The table is passed through the
  `ihq_portal_onboarding_narration_scripts` filter (so a local site can try a line before it
  is approved) and cleaned: only the 16 stages, only the seven onboarding languages, only
  non-blank text.
- **Rendering.** Each filled line becomes a work item in `ihq_aicoach_prerender_work_list()`,
  segment key `onboarding_<stage>`, hashed from its own text like the existing translations.
  `wp aicoach prerender` renders and downloads them through Gary's Coach API, idempotently, with
  the same manifest, avatar versions and cache-busting as the landing clips. Gary renders our
  own text already (every non-English landing clip is our translation, not his script).
- **Serving.** A filter on `ihq_portal_onboarding_clips` turns the served manifest into
  `stage => locale => url`. A clip is only served while its fingerprint equals the hash of the
  current script, so after a script changes the screen shows the new captions instead of a
  clip saying the old words, until the next render.
- **Captions.** The shell config carries the scripts as `narration`; the entry adds them to the
  string table under `narration.<stage>`. The engine uses that key when a screen names no
  `narration` key of its own, so screens need no change and t()'s English fallback applies.
  With an empty table every screen behaves exactly as today (reading-time advance).

## Alternatives considered
- **Scripts in Gary's registration/scripts endpoint**, like the landing segments. That gives
  Gary's approval status and hash, but the onboarding copy is ours and is translated by us;
  the landing flow's translations already bypass his endpoint for the same reason.
- **Scripts in the JS string tables.** The render runs in PHP, so the words would live in two
  places and drift; one PHP table sent to the browser keeps clip and captions identical.
- **The resume ("welcome back") and screen-1 continuity lines** named in the ticket are left
  out: the engine has no place to play them yet, and they have no script. Follow-up once the
  scripts exist.

## Blast radius
- `ihq_aicoach_prerender_work_list()` grows by the filled onboarding lines. With an empty table
  nothing changes. Once filled, a new avatar version must also render them before it can be
  switched on (`ihq_aicoach_avatar_version_missing()` compares manifests), which is intended.
- Engine: a screen with no `narration` key now looks up `narration.<stage>`; with no such
  string it is '' and the behaviour is unchanged.
- No screen, style or REST change.

## Notes
DEPLOYMENT NOTE (from the ticket): after deploy, run `wp aicoach prerender` on each environment
and check the manifest lists every onboarding segment in all seven languages before switching
the onboarding on.
