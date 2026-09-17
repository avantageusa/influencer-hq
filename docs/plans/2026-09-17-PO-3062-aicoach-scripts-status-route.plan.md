---
name: PO-3062 — expose GET /coach/scripts (registration script approval status)
overview: >
  Adds a new passthrough route, GET /wp-json/ihq/v1/coach/scripts, mirroring
  inc/gary-proxy.php's existing GET /coach/health pattern — forwards to Gary's
  GET /coach/v1/registration/scripts and returns the response unchanged. Lets
  anyone check the registration script's per-segment approval status directly
  (which stage is "held"/"approved"/"superseded" and why) without opening a
  real session and reading only the current stage's status off the session
  envelope.
todos:
  - id: add-route
    content: Added ihq_coach_handle_scripts() (GET /coach/v1/registration/scripts
      passthrough) and registered GET /ihq/v1/coach/scripts, same permission
      pattern as every other route in this file
    status: completed
  - id: verify
    content: "php -l; live-tested against local wp-env — returns the full
      manifest: version, locales, per-segment status/hold_reason/sha256, and
      story_region sequences. Confirmed most segments are already 'approved'
      (we_believe_1/2, time_selection, magic_johnson, alix_earle, bts,
      competitions) — only intro, channels and complete are 'held', each with
      a specific, named reason, not a blanket review-pending state"
    status: completed
---

# PO-3062 — expose GET /coach/scripts (registration script approval status)

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude Code (Sonnet 5)

## Problem

Checking whether Gary's registration script had been approved meant opening a real
session and reading `structured.script_status`/`review_required` off the envelope —
that only reports the *current stage's* status (always "intro" on a fresh session), not
the whole script. Gary's own docs (`docs.gary.club/clients/influencerhq/`) describe a
dedicated `GET /coach/v1/registration/scripts` endpoint that returns the full manifest —
this wasn't exposed through our proxy yet.

## Approach

`ihq_coach_handle_scripts()` — a one-to-one passthrough, identical shape to the existing
`ihq_coach_handle_health()`: no input, calls `ihq_coach_request('GET',
'/coach/v1/registration/scripts', null)`, returns the body/status unchanged. Registered
as `GET /ihq/v1/coach/scripts` under the same `ihq_coach_permission_check` nonce gate as
every other route here.

## What checking it revealed (2026-09-17)

Contrary to the impression given by every session-open response so far (all showing the
intro stage as `held`), most of the script is already **approved**: `we_believe_1`,
`we_believe_2`, `time_selection`, `magic_johnson`, `alix_earle`, `bts`, and `competitions`
all show `status: "approved"`. Only three segments are actually held, each for a specific,
named reason rather than a generic "pending review":

- **`intro`** — held: "Promises meetings not implemented by this API; confirm replacement
  copy or the actual integration." (the current copy says "meet with you whenever you'd
  like," which nothing backs today)
- **`channels`** — held: "Promises outbound answers, reminders and reports without a
  verified delivery integration."
- **`complete`** — held: "Requires confirmed IHQ account creation and portal transfer;
  this API cannot attest to either."

`world`, `community`, and `private` show `status: "superseded"` — old 3-way
competition-choice copy, replaced by the single `competitions` segment per "Master
specification sections 12-18." `identity` is intentionally held with empty text — a
form-only screen, no narration expected.

This reframes the earlier "script is held" understanding: it isn't a single blanket gate,
it's three specific, addressable gaps — worth relaying to Filip/Ivan as concrete,
actionable items rather than "waiting on Gary."

## Alternatives considered

- **Polling `/coach/session` and inspecting only the intro stage's status**: this is what
  we did before this route existed — technically works but only ever surfaces the intro
  segment's state, hiding that most of the rest is already approved.

## Blast radius

- `inc/gary-proxy.php`: one new function + one new route, both read-only passthroughs.
  Nothing else changed.
