---
name: Pre-render requests name their language and presentation
overview: >
  POST /coach/v1/videos was sent only the script and metadata, so Gary's box rendered every
  clip as lang "en", presentation "sami"; the non-English landing clips were spoken without
  their language's configuration. Every request now sends lang and presentation "sami".
  English keeps its Idempotency-Keys and fingerprints, so its clips stay cached and a retry
  replays. Other languages get a new key and fingerprint, so their clips render again and are
  served under a new URL. A 409 idempotency_conflict is logged as its own error. Running the
  re-render against the live box is out of scope until the dev approves the render spend.
todos:
  - id: request-lang-presentation
    content: Send lang (the work item's language) and presentation "sami" in ihq_aicoach_coach_create_video(); refuse a language outside IHQ_COACH_THEME_LOCALES before any request
    status: completed
  - id: keys-and-fingerprints
    content: Keep English keys and fingerprints; end non-English keys with the language-render mark and give non-English clips a fingerprint carrying language and mark (ihq_aicoach_prerender_fingerprint())
    status: completed
  - id: onboarding-served-check
    content: Make ihq_portal_onboarding_narration_clips() compare against the same fingerprint, so a re-rendered onboarding clip is served
    status: completed
  - id: conflict-409
    content: Return 409 idempotency_conflict as coach_video_idempotency_conflict with the key, and log it as a conflict in ihq_aicoach_prerender_segment()
    status: completed
  - id: tests
    content: Extend tests/aicoach-prerender.test.php and tests/portal-onboarding-narration.test.php; break each new guard and confirm a test fails
    status: completed
  - id: rerender-approval
    content: Get the dev's go-ahead before any `wp aicoach prerender` run; it re-renders 54 landing clips (9 per language × zh, yue, ja, ko, th, vi) per avatar set rendered
    status: blocked
  - id: verify
    content: Run npm run test:php, php -l on touched files, and CI before merge
    status: pending
---

# [PO-3107] Pre-render requests name their language and presentation

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3107
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem

`ihq_aicoach_coach_create_video()` (inc/aicoach-prerender.php) sent `{ script, metadata }` and
nothing else. Gary's team (Luna, email of 2026-10-19) clarified that the box then uses lang
"en" and presentation "sami" and does not detect the language from the script, so every
non-English clip (the landing translations, and any onboarding line in another language) was
rendered through the multilingual voice without its language's configuration.

## Approach

- **Request.** The body is `{ script, lang, presentation: "sami", metadata: { segment } }`.
  `lang` is the work item's language, threaded from `ihq_aicoach_prerender_segment()`. A
  language outside `IHQ_COACH_THEME_LOCALES` (inc/gary-proxy.php) is refused before any
  request, so a typo in a table of lines cannot spend render budget.
- **English stays as it is.** Gary matches a key over the trimmed script, resolved
  presentation, normalized language and metadata. Sending lang "en" and presentation "sami"
  resolves to what was used when they were left out, so English keeps its keys: a retry
  replays (`idempotent_replayed: true`). The English fingerprint is still the text hash, so
  every English clip already rendered stays cached and no request goes out for it.
- **Other languages render again.** Sending the real lang with an old key would answer 409.
  `ihq_aicoach_prerender_idempotency_key()` ends a non-English key with
  `IHQ_AICOACH_PRERENDER_LANGUAGE_RENDER_MARK` (`-lang`, before any version suffix).
  `ihq_aicoach_prerender_fingerprint()` is the text hash for English and
  `sha256(text_hash|language|lang)` otherwise. The manifest records that fingerprint, so:
  - the cache check sees an old non-English entry as stale and renders it again;
  - the old clip keeps being served (`ihq_aicoach_prerender_get_urls()` does not check
    fingerprints) until the new download is validated and renamed over it, and the manifest
    entry is updated only after that;
  - the new fingerprint changes the clip URL's `?v=`, so Cloudflare fetches the new bytes
    instead of serving the cached old ones under the unchanged file name.
- **Onboarding clips.** `ihq_portal_onboarding_narration_clips()` serves a clip only when the
  manifest fingerprint matches the current line. It now compares against
  `ihq_aicoach_prerender_fingerprint()`, or a re-rendered non-English onboarding clip would
  never be served. An onboarding clip in another language rendered before this change is not
  served until it is rendered again; the table ships empty, so none exists on main.
- **409 idempotency_conflict.** `ihq_aicoach_coach_create_video()` returns it as
  `coach_video_idempotency_conflict`, naming the key, and `ihq_aicoach_prerender_segment()`
  logs an `IDEMPOTENCY CONFLICT` line. Nothing is polled, downloaded or retried, and the old
  clip and the manifest stay as they were. A 409 with another code, or the code under another
  status, stays a generic `coach_video_create_failed`.

## What will re-render

On the next `wp aicoach prerender` (or render-step run) for an avatar set:

| Language | Landing clips | Onboarding clips |
|---|---|---|
| en | 0 (cached; a new or changed segment replays/renders as before) | 0 |
| zh, yue, ja, ko, th, vi | 9 each, 54 in all | 0 (table is empty) |

The 9 are we_believe_1, we_believe_2, time_selection, magic_johnson, alix_earle, bts, world,
community and private, as long as Gary still lists them as approved. That count applies to
each avatar set that gets rendered: the served set, plus any `manifest-v*.json` version if it
is rendered again (`wp aicoach avatar-status` lists them).

## Alternatives considered

- **Leave the fingerprint alone and only change the key.** The cache check would treat the
  old clip as current and never send the new key, so nothing would re-render.
- **Add a separate `request` field to the manifest entry.** It would work, but the cache check,
  the `?v=` cache-buster and the onboarding served check would each have to read it. One
  fingerprint keeps the single source of truth the manifest already has.
- **Put the mark into the work item's sha256.** That changes the key without a readable
  suffix, and the onboarding work items would hash something other than the text.
- **Change the English key as well.** That would re-render 9 English clips for no change in
  output.

## Blast radius

- inc/aicoach-prerender.php: request body, keys, fingerprints, language guard, 409 handling.
  This also covers the version render-step page (`ihq_aicoach_avatar_render_step()`).
- inc/portal-onboarding-narration.php: the served-clip fingerprint check.
- Existing tests whose expectations change because the behaviour was meant to change (flagged
  per the coding standards): the non-English key checks, and one version key and one `?v=` in
  tests/aicoach-prerender.test.php; the Japanese onboarding fingerprint fixture in
  tests/portal-onboarding-narration.test.php.
- Render spend: 54 clips per set rendered, once, after approval.
- Rollback: reverting this change makes old keys current again. The manifest's new
  fingerprints then mismatch, the old key replays the old job, and the old clip downloads,
  with no new render. This follows from Luna's contract but has not been tried live.

## Notes

- **Overlap with PR #142 (ENGR-7798, open).** It changes the same function:
  `ihq_aicoach_coach_create_video( $script, $idempotency_key, $segment_key, $video_profile = '', $language = 'en' )`
  sends `lang`, `presentation` and `video_profile` only for a version that has a profile (v3),
  and keeps the original set and v2 unchanged. This fix sends `lang` and `presentation` for
  every version. Whichever merges second rebases onto the other. The merged result should send
  lang and presentation always and `video_profile` only for a profiled version, with this
  plan's key and fingerprint rules applying to v3 as well (v3 English keys are new anyway).

- Not verified against the live box: the 409 body is assumed to be the flat
  `{ error, message }` 4xx shape recorded in 2026-09-23-PO-3062-aicoach-coach-video-api.plan.md;
  the English replay with explicit lang/presentation is per Luna's email.
- Job IDs are not recorded in the manifest, so an old clip that has been overwritten locally is
  found again on Gary's side through GET /coach/v1/videos and its `metadata.segment`.
- No phpcs in the repo or on PATH; touched files were checked with `php -l`.
