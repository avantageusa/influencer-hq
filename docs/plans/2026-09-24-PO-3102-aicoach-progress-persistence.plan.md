---
name: PO-3102 — save AI Coach registration progress and resume on return, no expiry
overview: >
  FR-11's ticket description says persistence "is implemented using Luna's memory
  capabilities (owned by Gary)" — verified that's not accurate for either Gary's documented
  Coach API (cross_session_memory is explicitly excluded on the registration surface, and
  capped at 30 days anyway) or this repo's actual Luna integration (inc/luna-users-rest.php
  is a one-way COMPLETED-registration export Gary's team pulls, authenticated as WP user
  "gary" — nothing about in-progress state). This ticket needs its own persistence layer:
  a stable pseudonymous visitor ref (cookie, used only to key our own local progress
  record — Gary's own player.ref, sent with every Coach API call, stays exactly what it
  was: a fresh per-session UUID, never this cookie's value), our own storage (wp_options,
  this theme has no custom DB tables anywhere and this doesn't need one), and a
  resume-on-load path through js/aicoach-coach-flow.js's panel/SCREENS state machine.
todos:
  - id: scope-decisions
    content: "Two open questions resolved with the user before implementation: (1) Scenario
      21 wants the coach's dialogue to 'reflect this is a continuation' on return — no
      approved copy exists anywhere (not in Gary's registration/scripts manifest, not
      written by product) for that line, so v1 does a SILENT resume (exact stage, pre-filled
      data, no replay) using each panel's existing script/caption, with the new dialogue
      added later once product approves copy. (2) Stable visitor identification is a
      long-lived cookie — same-browser/same-device only for v1; cross-device resume (e.g. an
      emailed resume link) is out of scope, flagged as a known v1 limitation."
    status: completed
  - id: backend-storage
    content: "New inc/aicoach-progress.php: a stable ihq_aicoach_ref cookie (UUID, HttpOnly,
      2-year expiry, generated on first read if absent) identifies the visitor's local
      progress record only; player.ref sent to Gary remains a fresh per-session UUID. Storage
      is wp_options keyed
      ihq_aicoach_progress_{ref}, autoload=no — this theme has zero custom DB tables
      anywhere (checked), and a single per-visitor JSON blob with no querying/reporting need
      doesn't justify being the first. GET/POST /ihq/v1/aicoach/progress (nonce-protected,
      same pattern as every other ihq/v1 route in this theme)."
    status: completed
  - id: clear-on-complete
    content: "inc/aicoach-register.php's ihq_aicoach_handle_create_account() clears the
      progress record on successful account creation — the visitor is a real WP user at that
      point, an orphaned draft record serves no purpose. Verified directly (save then clear)
      rather than running a full account-creation flow, to avoid creating real test users."
    status: completed
  - id: frontend-save-points
    content: "js/aicoach-coach-flow.js: fire-and-forget saveProgress() calls at showPanel()
      (current stage, every transition), the tier click handler (tier), identityForm submit
      (identity fields), channelsForm submit (channels), and selectLocale() (language) — never
      blocks the flow if the save fails. Found and fixed live: two saves fired close together
      (identity capture immediately followed by its own stage-transition save) raced on the
      backend's read-merge-write and silently dropped the identity field — saveProgress() now
      chains every call onto one promise so they're serialized client-side."
    status: completed
  - id: frontend-resume
    content: "On load, before start() would normally open a fresh live Gary session for the
      intro: fetch saved progress. If a stage beyond 'intro' exists, skip start() entirely —
      reconstruct the SCREENS queue for the saved tier, pre-fill identity/channel form fields
      and captured* state, set sequenceIndex to the saved panel's position (or
      sequenceFinished=true + sequenceIndex=SCREENS.length for the post-SCREENS identity/
      comm-channels/final-continue panels — found and fixed live: without pinning
      sequenceIndex there too, a later runFallback() resume, e.g. from submitting channels
      after resuming straight to comm-channels, replayed the entire SCREENS sequence from
      index 0), and jump straight to showPanel(savedStage)/runFallback(false). Language
      resume calls selectLocale() (found and fixed live: an earlier version only called
      applyLocale(), which updates the page text but not the language dropdown's own
      is-current marker, leaving it showing the browser-guessed language as 'current')."
    status: completed
  - id: verify
    content: "php -l / node --check clean on every changed file. Live end-to-end on wp-env:
      progressed through the real flow (tap-through + tier click + identity submit + channels
      submit), confirmed the option row's exact contents at each step, reloaded and confirmed
      resume to comm-channels and final-continue with correct pre-filled DOM (verified via
      window.ihqCoachEvents.collect() — what account creation actually sends — not just visual
      inspection). Seeded progress directly via wp eval for the two states impractical to
      reach interactively in this sandbox (live Gary session hangs here — a known, pre-existing
      environment limitation, not a regression): resuming mid-sequence (equity-alix, 10-min
      tier) plays the pre-rendered clip for that panel and continues on to competition
      screens without replaying believe-1/believe-2/home/equity-magic; resuming with a saved
      language (ko) correctly re-applies it. Confirmed ihq_aicoach_progress_save()/_clear()
      directly. All test option rows cleaned up afterward."
    status: completed
---

# PO-3102 — save AI Coach registration progress and resume on return, no expiry

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude Code (Sonnet 5)

## Problem

FR-11's acceptance criteria (Scenario 20/21): progress is saved continuously with no expiry,
and a returning visitor — regardless of elapsed time — resumes from the exact stage they left,
with all previously entered data pre-filled and no completed screen replayed. The ticket
attributes this to "Luna's memory capabilities (owned by Gary)," which doesn't hold up under
inspection: Gary's own `GET /coach/v1/health` reports `memory_excluded_surfaces: ["registration"]`
and even where available it's a 30-day, 24-message buffer, not indefinite; and this repo's actual
"Luna" integration (`inc/luna-users-rest.php`, `js/ihq-aicoach-events.js`) is a one-way export of
*completed* registrations that Gary's team pulls (Basic Auth as WP user `gary`), not something we
can query for in-progress state. Separately, `inc/gary-proxy.php`'s `player.ref` is a fresh random
UUID on every session open by design ("never tied to any WP user or identity captured later") —
so even if a memory system existed on Gary's side, we never send the same ref twice to let it
recognize a returning visitor.

## Approach

1. **Stable visitor ref, our own storage.** A long-lived `ihq_aicoach_ref` cookie (UUID) replaces
   nothing on Gary's side — `player.ref` sent to Gary can stay however it's generated today, since
   Gary's registration surface doesn't retain anything for us to resume anyway. This cookie is
   purely for our own `wp_options`-keyed record (`ihq_aicoach_progress_{ref}`, autoload=no — no
   custom table, matching this theme's existing all-WP-core-storage pattern).
2. **Save at every real transition, not just the 3 form submits.** Scenario 20 says "current
   stage" is saved continuously — `showPanel()` is the one function every single panel change
   already goes through, so a fire-and-forget save there covers stage-level granularity for free.
   Tier/identity/channels/language get attached at their own natural capture points.
3. **Resume bypasses `start()`'s live-intro path entirely.** `start()` always opens a fresh Gary
   session and speaks the intro live — there's no approved "welcome back" line to speak instead
   (see the scope decision below), and replaying the intro on every return would itself violate
   "completed screens are not replayed." Resuming to a saved non-intro stage skips straight to
   reconstructing the SCREENS queue (same tier-dependent push a real tier click does) and calling
   `showPanel(savedStage)` directly — the existing fade transition handles the intro→resume-panel
   visual instead of a special case.
4. **Clear on completion.** Once `inc/aicoach-register.php` actually creates the WP account, the
   draft progress record has done its job — deleted, not left to accumulate forever for visitors
   who did complete.

## Scope decisions (resolved with the user before starting)

- **No new spoken "resume" dialogue for v1.** Silent resume — exact stage, pre-filled data, no
  replay — using whatever the destination panel already shows. Revisit once product/Gary approves
  copy for "this is a continuation" dialogue.
- **Same-browser/device only for v1**, via a long-lived cookie. Cross-device resume (e.g. an
  emailed link) is explicitly out of scope here.

## Blast radius

- New file `inc/aicoach-progress.php`.
- `inc/aicoach-register.php`: one addition (clear progress on successful account creation).
- `js/aicoach-coach-flow.js`: additive save calls at existing mutation points + a resume check
  ahead of `start()`. No change to the live-intro path for a genuinely fresh visitor.
- No changes to Gary's Coach API usage, no changes to `inc/luna-users-rest.php`.
