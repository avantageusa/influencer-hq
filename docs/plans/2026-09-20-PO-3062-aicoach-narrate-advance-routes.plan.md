---
name: PO-3062 — expose /narrate and /advance proxy routes
overview: >
  Adds the two remaining Coach API proxy routes needed for real, verbatim,
  server-driven registration narration: POST /coach/{id}/narrate (speak the
  current stage's approved passage) and POST /coach/{id}/advance (move to the
  next stage, optimistic concurrency via expected_stage/expected_revision).
  Same thin-passthrough pattern as every other route in inc/gary-proxy.php.
  Also fixed the same URL-vs-body session_id bug CodeRabbit found on PR #35
  in the two pre-existing routes that had it (message, close), for
  consistency and because it's a real, if low-severity, correctness issue in
  both.
todos:
  - id: narrate-route
    content: Added ihq_coach_handle_narrate() + POST /ihq/v1/coach/{session_id}/narrate
      — forwards script_id (required) and version (optional) to Gary
    status: completed
  - id: advance-route
    content: Added ihq_coach_handle_advance() + POST /ihq/v1/coach/{session_id}/advance
      — forwards expected_stage/expected_revision (required) and tier (optional,
      only when 2/5/10 — silently dropped otherwise rather than forwarding
      something Gary would reject)
    status: completed
  - id: session-id-fix
    content: ihq_coach_handle_message() and ihq_coach_handle_close() now read
      session_id via get_url_params() instead of get_param(), matching the
      fix already applied to attest_identity on PR #35 — same bug, just not
      caught then because those two handlers weren't in that diff
    status: completed
  - id: verify
    content: "php -l; 43 tests total (27 previous + 16 new) covering narrate's
      required-field validation and payload forwarding, advance's tier
      validation (valid tier forwarded, invalid/absent tier dropped, never
      sent malformed), 409 passthrough, and the message/close URL-params
      regression — all pass. Live-verified against the real Gary API: opened
      a session, narrated the held intro stage (confirmed it returns the
      generic degraded response, not the held copy — matches Gary's
      documented behavior), and attempted to advance past it — confirmed
      Gary refuses with script_review_required, i.e. a held current stage
      cannot advance at all"
    status: completed
---

# PO-3062 — expose /narrate and /advance proxy routes

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude Code (Sonnet 5)

## Problem

Real, verbatim, server-driven narration for the registration flow needs two Coach API
operations our proxy didn't expose yet: `/narrate` (speak the current stage's exact
approved text) and `/advance` (move the flow forward, with optimistic concurrency so two
concurrent requests can't silently clobber each other's progress). This PR adds both,
purely as engineering scaffolding — no frontend rewiring yet.

## Approach

Both handlers follow the exact shape every other route in this file already uses:
validate required params, build a payload, call `ihq_coach_request()`, pass Gary's
status/body straight back. `ihq_coach_handle_advance()` additionally guards `tier` —
only forwards it when it's exactly 2, 5, or 10 (Gary's enum), silently dropping anything
else rather than sending a value the API would reject anyway.

While in this file, fixed `ihq_coach_handle_message()` and `ihq_coach_handle_close()` to
read `session_id` from `get_url_params()` instead of `get_param()` — the exact bug
CodeRabbit caught on PR #35's `attest_identity`, just never flagged on these two because
they weren't touched in that PR's diff. Same root cause (POST body/JSON parameters take
precedence over URL route parameters in `WP_REST_Request::get_param()`), same fix.

## A real finding from live-testing, not just code review

Opened a real session (`intro` stage, revision 0) and tried `/advance` with
`expected_stage: intro, expected_revision: 0` — Gary refused with
`script_review_required`, confirming the contract's own line: **"a held current stage
cannot advance."** This isn't a bug in our proxy (it forwarded the request correctly and
returned Gary's real response) — it's a hard architectural fact: nothing past `intro` is
reachable through `/advance` at all until `intro` itself is released. Releasing
`identity`/`channels`/`complete` (or building the rest of this integration) doesn't help
until `intro` specifically is resolved — either Filip confirms `meetings_available` is a
real, true fact we can attest, or Gary/content supplies replacement copy that doesn't
promise it.

## Alternatives considered

- **Skipping the tier validation and forwarding whatever the caller sends**: rejected —
  Gary's schema is a strict enum (`2 | 5 | 10`); forwarding an invalid value would just
  trade a clear 400 from our own validation for a less clear error from Gary, or worse,
  silently misbehave if Gary's validation is looser than the published schema implies.

## Blast radius

- `inc/gary-proxy.php`: two new handler/route pairs, plus a two-line fix each in two
  existing handlers (`message`, `close`). No other routes changed.
- `tests/gary-proxy.test.php`: 16 new cases, no WordPress/network dependency.

## Notes

- No frontend work here. `js/aicoach-coach-flow.js` still drives every screen after intro
  from the static `SCREENS` array — wiring it to `/advance` + `/narrate` (with revision
  tracking, 409/conflict handling per Gary's "don't blindly retry, re-read the session
  first" guidance, and an audio playback story for non-intro stages since there's no
  video) is a separate, larger follow-up, and per the finding above, blocked on `intro`
  being released regardless of how much of that FE work gets done first.
- `story_region` is still never sent on session-open. Per Gary's own docs, omitting it
  behaves identically to `"unknown"` (BTS-only story sequence in every tier) — so this
  isn't a regression or a broken default, just an unmade product decision (which
  countries count as `"asia"`/`"other"`) that doesn't block anything built here.
