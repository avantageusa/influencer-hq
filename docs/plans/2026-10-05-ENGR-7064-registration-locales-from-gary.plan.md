---
name: Read the registration locales Gary has approved instead of a hardcoded English-only clamp
overview: >
  ihq_coach_handle_open_session() forces the Gary session locale to en for
  anything except en/en-us/en-gb. That was right while Gary rejected every
  other locale, but it means this theme would keep sending en on the day Gary
  approves registration copy for another language. This reads the approved
  lists from Gary's own manifest (GET /coach/v1/registration/scripts: locales
  and free_text_locales), caches them, and lets the session handler send the
  visitor's locale when it is on the list. While Gary reports only ["en"]
  nothing changes. Server side only: no frontend, no Ask Sami, no session
  switching on language change — those follow once a real language is approved.
todos:
  - id: fetch-helper
    content: Add a cached reader for Gary's approved locale lists (transient, last-good fallback, short negative cache, short fetch timeout), filtered to the theme's 7 codes
    status: completed
  - id: session-handler
    content: Replace the hardcoded en/en-us/en-gb allowlist in ihq_coach_handle_open_session() with the approved list, keeping every currently tested behaviour for English
    status: completed
  - id: tests
    content: Stub-based tests for the lists, cache, failure modes and the handler's locale choice; update the existing clamp tests; run every PHP suite
    status: completed
  - id: verify
    content: One live call against Gary confirming non-English still opens an en session today, plus lint
    status: completed
---

# [ENGR-7064] Read approved registration locales from Gary

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7064
**Drafted by:** Claude (claude-sonnet-5-5)

## Problem
`ihq_coach_handle_open_session()` (`inc/gary-proxy.php`) clamps the session
`locale` to `en`, `en-us` or `en-gb` before calling Gary, because Gary's
registration surface answered 422 `registration_locale_unapproved` for every
other locale (confirmed 2026-09-16, and still true: `registration_languages`
and the manifest's `locales` are both `["en"]`). Gary's reply of 2026-10-02
says a language "opens on its own the day its approved copy is loaded, with
nothing to change on your side". For this theme that is false: the clamp would
keep sending `en` until someone edits the list and deploys.

## Approach
- **Source of truth is Gary's manifest.** `GET /coach/v1/registration/scripts`
  already carries `locales` (accepted for registration sessions) and
  `free_text_locales` (accepted for free-text questions). One call gives both,
  and it is the endpoint this theme already uses for the prerender pipeline.
  `/health`'s `registration_languages` currently agrees with `locales`; the
  ticket flags confirming that with Gary.
- **Cache.** `ihq_coach_approved_locales()` returns
  `array( 'registration' => [...], 'free_text' => [...] )`, each filtered to the
  theme's 7 codes (`en zh yue ja ko th vi`; anything else Gary returns is
  ignored). A transient holds it for 10 minutes (overridable through the
  `ihq_coach_locales_cache_ttl` option, the same pattern as the Q&A history
  cap). Every successful fetch also writes a non-expiring last-good option.
  When Gary fails (transport error, non-2xx, or a body without a `locales`
  array) the last good value is served, or `['en']` if there never was one, and
  that fallback is itself cached for 60 s so a Gary outage does not put a
  failed fetch in front of every visitor's session open.
- **Short fetch timeout.** The lookup sits on the visitor-facing session-open
  path, so it uses a 5 s timeout instead of `ihq_coach_request()`'s 15 s — an
  optional `$timeout` parameter whose default keeps every existing caller
  unchanged.
- **Session handler.** `ihq_coach_resolve_registration_locale()` lower-cases and
  validates the visitor's locale (`^[a-z]{2,3}(-[a-z0-9]{1,8})*$`, else `en`)
  and returns it when it, or its primary subtag, is on the approved list. A
  regional variant of an approved language therefore passes through as given,
  which is exactly what happens today for `en-us`/`en-gb`; everything else
  becomes `en`. The frontend only ever sends the 7 base codes, so the variant
  path is reachable only by a direct API caller. Whether Gary accepts a
  non-English regional variant is unverified; if it does not, the session open
  fails the same way any other Gary failure already does (static fallback).

## Alternatives considered
- **Read `registration_languages` from `/health`.** Same data today, but the
  manifest also carries `free_text_locales`, which the Ask Sami follow-up needs.
- **Make the clamp list a `wp_option`.** Still needs someone to notice Gary's
  approval and flip it; the point is that it should open on its own. (A
  separate "we have enabled this language" switch on top of Gary's approval is
  worth having for the visible exposure, and belongs to the frontend ticket.)
- **No cache, fetch per session.** Adds a Gary round trip to every visitor's
  session open and makes a Gary outage a hard failure of a path that currently
  has no extra dependency.
- **Normalise regional variants to the base code.** Would change what is sent
  for `en-us`/`en-gb` today; no reason to alter behaviour that works.

## Blast radius
Only `inc/gary-proxy.php` (session handler, new helpers, an optional parameter
on `ihq_coach_request()`) and its test. While Gary reports `["en"]` every
visitor still opens an `en` session — the same value as before. New failure
mode: the lookup itself; it can only ever degrade to `['en']`, never block the
session open, and costs at most one 5 s attempt per cache window during an
outage. `ihq_coach_request()`'s default timeout is unchanged. No frontend,
REST route, progress-record, or prerender change; no overlap with ENGR-7051.

## Verification
- `tests/gary-proxy.test.php`: 110 checks, all pass (Gary stubbed, no network):
  today's `["en"]` manifest, a newly approved language opening, variants,
  malformed and hostile locale values, filtering to the 7 codes, the separate
  free-text list, cache hit / expiry, the TTL option and invalid values, an
  outage with and without a last-good copy, non-2xx and malformed bodies, a
  corrupt last-good option, and the unchanged 15 s default timeout for every
  other caller. Seven deliberate mutations of the new code (primary-subtag
  match, de-duplication, the failure cache, the short timeout, the TTL guard,
  the last-good write, the locale format check) each made checks fail.
- Every PHP suite in `tests/` passes; `php -l` clean.
- Live (wp-env, real Gary): the manifest parses to `registration ["en"]`,
  `free_text ["en"]`, the transient and last-good option are written, and
  sessions asked for `zh`, `ja` and `en` all open (HTTP 201) with `en`
  resolved; the test sessions were closed.

## Notes
- Nothing here can be exercised against a real approved non-English language
  until Gary approves one; those paths are covered with stubbed manifests.
- Gary opens a language only when every segment is translated (12, including
  `intro`, `channels`, `complete`, which are still held on their side), so
  Gary can start accepting a language before the visitor-facing flow is fully
  localised. Ask Sami gating, `SpeechRecognition.lang`, the panel's English-only
  strings, session language on a mid-flow switch and the `/message` timeout
  against Gary's per-language deadlines are out of scope (follow-up tickets).
- Supersedes the clamp described in
  `2026-09-17-PO-3103-followup-registration-locale-clamp.plan.md`.
