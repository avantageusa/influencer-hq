---
name: Render the pre-rendered AI Coach clips with Gary's sami-framing video profile (avatar version v3)
overview: >
  Gary's Coach API can now render with a candidate avatar whose frame the page composes onto its
  wide banner. The existing clips cannot be composed with that candidate (their sea and sky are a
  different crop of the scene), so the pre-render needs to ask for the profile. This adds the
  profile to a new avatar version, v3: its render sends `video_profile`, `lang` and
  `presentation`, the original set and v2 send exactly what they always did, and nothing changes
  for visitors until v3 is switched on. It renders one probe clip and leaves the other clips, the
  front-end integration and the disclosure decision for later.
todos:
  - id: profile-map
    content: A constant maps an avatar version to its Coach API video profile (v3 to sami-framing-20261018) and one function reads it
    status: completed
  - id: request-body
    content: ihq_aicoach_coach_create_video() adds lang, presentation and video_profile only when the version has a profile
    status: completed
  - id: tests
    content: PHP tests for the map, for the body of the original set, v2 and v3, for another language, for the key and manifest of v3 and for the cached second run
    status: completed
  - id: probe
    content: Render bts in English into v3 and compose it with Gary's calibration
    status: completed
  - id: rest
    content: The other clips (9 segments in 7 languages, 62 more), live sessions with the profile, the page integration, the disclosure - separate steps
    status: pending
---

# [ENGR-7798] Render the pre-rendered clips with Gary's sami-framing profile

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7798

## Problem
The banner composition Gary built (a canvas that maps the avatar's native frame back onto our wide
scene) is calibrated for one candidate avatar. The clips our pre-render makes today come from the
default avatar, whose background is a different crop of the scene, so a visible rectangle remains
around her head (measured on the dev clips: the sea and sky inside the clip do not match the still).
Gary confirmed on 2026-10-19 that the candidate is selected per request with
`video_profile: "sami-framing-20261018"`, and asked for `lang` and `presentation: "sami"` to be sent
with it and for a new Idempotency-Key per replacement clip.

## Approach
- The profile belongs to the avatar version (`IHQ_AICOACH_AVATAR_VERSION_PROFILES`), never to a
  single request. A version is then one avatar from its first render to its last, and a changed
  profile cannot reuse an old key (Gary answers 409 `idempotency_conflict`): v3 has its own key
  ending, manifest and file names already (#109).
- `ihq_aicoach_coach_create_video()` sends `lang`, `presentation` and `video_profile` only for a
  version that has a profile. The original set and v2 send the script and the metadata, so their
  request hashes do not change and nothing rendered before is touched.
- Visitors keep being served the active version (`ihq_aicoach_avatar_version`). v3 is not served
  until `wp aicoach activate-avatar v3` is run on purpose (and the page can compose it).

## Alternatives considered
- A `--video-profile` option on the command: a profile that can differ from the version it renders
  into lets a version mix two avatars; rejected.
- Putting the profile in a WordPress option: the version already is the switch, and a second one
  could disagree with it.

## Blast radius
Only a render run with `--avatar-version=v3` changes; the existing versions and the page are untouched.
Each render uses provider credits (one in this task; 62 more later).

## Probe (wp-env, 2026-10-19)
- `bts`, English, v3 rendered on the first poll round (about 40 s): `bts.v3.mp4`, 37.7 s,
  1152x768, with audio; the v3 manifest holds it; the active version is unchanged.
- Composed with Gary's calibration and poster on the real clip: outside the video region the canvas
  is identical to the poster; at the region's edges the mean difference is 7 to 14 of 255 (the chair
  weave and the waves are smoother in the video than in the poster), and the speaking face moves.
  At 2x a soft vertical edge shows on the beach to the right of the chair; at the banner's real size
  it is faint. Ivan should judge it on a real screen.

## Test plan
- [x] `php tests/aicoach-prerender.test.php` and `npm run test:php`
- [x] Four manual mutations of the new code (always send the profile, wrong language, wrong version,
      no presentation) each fail a check
- [x] One real render in wp-env and the composite on that clip
- [ ] The other 62 clips, live sessions and the page integration (not part of this task)
