---
name: PO-3062 — move pre-rendered AI Coach clips off direct Anam calls onto Gary's Coach Video API
overview: >
  Gary's email (2026-09-23) traced the recurring "All engines are currently at capacity" render
  failures on magic_johnson/alix_earle/bts to Anam's render pool being briefly full, unrelated to
  our requests, and asked us to stop calling Anam directly and render through his own Coach API
  instead (POST/GET /coach/v1/videos, GET .../{id}/content) — same signing as every other Coach
  call this theme already makes. The box now pins the same avatar/voice/model the live session
  uses, so this file no longer reads or tracks that config at all, and capacity failures are
  retried automatically server-side under the same job id for up to 2 hours instead of us needing
  a dedicated retry loop.
todos:
  - id: verify-real-contract
    content: Fetched the real coach-api.openapi.json's operations table + error-code enum and
      Gary's own reference client (coach-client.mjs) from docs.gary.club rather than trusting the
      email's paraphrase — then verified every claim live against the real influencerhq box (a
      real create, poll to ready, download, an idempotent replay of the same Idempotency-Key, and
      a deliberate validation error) before writing any code, matching this project's standing
      "never trust the doc/email claim at face value" discipline.
    status: completed
  - id: gary-proxy-extend
    content: "inc/gary-proxy.php: extracted ihq_coach_sign_headers() (the HMAC formula, previously
      inlined in ihq_coach_request()) so it has exactly one implementation; ihq_coach_request()
      gained an optional $extra_headers param (for Idempotency-Key) via array_merge, fully
      backward compatible with every existing call site; added ihq_coach_download(), the binary
      sibling for GET /coach/v1/videos/{id}/content, which returns raw video/mp4 bytes rather than
      JSON — confirmed live."
    status: completed
  - id: aicoach-prerender-rewrite
    content: "inc/aicoach-prerender.php: removed ihq_aicoach_gary_avatar_config() (the throwaway
      session opened purely to read Gary's live avatar_id/avatar_model), GARY_SAMI_ANAM_VOICE_ID,
      and ihq_aicoach_voice_generation_options() entirely — no longer needed, the box pins this
      itself. Replaced ihq_aicoach_anam_create_video()/_get_video()/_retry_video() with
      ihq_aicoach_coach_create_video()/_get_video() against /coach/v1/videos. The old
      Anam-specific retry loop (a dedicated /retry endpoint, its own Idempotency-Key, a 3-attempt
      budget) is gone — the new API has no retry endpoint and doesn't need one; a capacity wait
      shows up as waiting_reason on the same job and resolves on its own. Idempotency-Key is now
      just ihq-seg-{segment_key}-{sha256 prefix}, and the manifest fingerprint is the segment's
      raw sha256 (previously a combined hash folding in avatar_id/model/voice_id, which no longer
      exist as inputs)."
    status: completed
  - id: bounded-poll-pending-status
    content: "ihq_aicoach_prerender_segment() polls for a bounded ~90s window (18 x 5s) per
      invocation instead of the old 5-minute budget, and returns a NEW 'pending' status (distinct
      from 'error') if the job is still queued/rendering when the window elapses. This is safe
      specifically because POSTing again with the same Idempotency-Key is a confirmed-live,
      idempotent replay of the SAME job at its current state — so the next \`wp aicoach
      prerender\` run (or the next visit to the dev SFTP one-off script) just picks the same job
      back up rather than abandoning it or double-rendering. The short window matters because the
      SFTP-only dev trigger script runs inside a single HTTP request with real gateway timeout
      constraints, unlike a WP-CLI process."
    status: completed
  - id: cli-and-diagnostic-script
    content: "IHQ_Aicoach_Prerender_Command::prerender() now reports rendered/cached/pending/error
      counts separately — 'pending' warns but doesn't fail the command, since nothing is actually
      broken, it's just \"still rendering, ask again later.\" Regenerated the SFTP one-off
      diagnostic trigger script (aicoach-prerender-once.php, lives outside git — see its own
      header) to match the new function signature (no avatar_config param) since dev has no
      WP-CLI and this script is the only way to trigger a render there."
    status: completed
  - id: verify
    content: "php -l clean on both files; tests/gary-proxy.test.php's existing 47 cases all still
      pass unchanged after the ihq_coach_request() refactor (none of them inspect the headers
      array, so the additive $extra_headers param is a safe no-op for them). Live end-to-end on
      wp-env against the real influencerhq box: ran \`wp aicoach prerender\`, which rendered all 6
      segments fresh (the fingerprint scheme changed, so the old manifest entries no longer
      matched) — INCLUDING magic_johnson/alix_earle/bts, the three that were stuck under the old
      Anam-direct flow. Re-ran the command immediately after: all 6 correctly hit the manifest
      cache with zero network calls. Fetched 4 of the 6 resulting clip URLs directly and confirmed
      200 / video/mp4 / correct byte sizes."
    status: completed
---

# PO-3062 — move pre-rendered AI Coach clips off direct Anam calls onto Gary's Coach Video API

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude Code (Sonnet 5)

## Problem

Three of the six pre-rendered segments (magic_johnson, alix_earle, bts — the three longest/most
complex scripts) had been failing intermittently for hours with Anam's
`{"code":"session_start_failed","message":"All engines are currently at capacity...","retryable":
true}`, even across multiple retries via Anam's dedicated `/retry` endpoint. Reported to Gary with
the exact real request payloads he asked for. His reply (2026-09-23) narrowed it to a ~9-minute
window (08:27–08:36 UTC) where Anam's own render pool was full — nothing wrong on our side, not
script length — and re-ran one of the stuck scripts himself successfully in 49s. Separately, he
asked us to stop calling Anam directly at all and go through his own Coach API instead, now live
on both his boxes.

## Approach

1. **Verified the real contract before writing any code.** The email's endpoint/field
   descriptions were a paraphrase, not the source of truth — per this project's standing
   discipline (already applied to Anam's swagger.json and Gary's sami-portal-proof.mjs
   elsewhere), fetched the real `coach-api.openapi.json` operations table + stable error-code
   enum and Gary's own reference client (`coach-client.mjs`) from `docs.gary.club`, then ran a
   real create → poll → download → idempotent-replay → deliberate-validation-error cycle against
   the live influencerhq box to confirm the exact response shapes (`status`: queued/rendering/
   ready/failed, `waiting_reason`, `attempts`, `content: {url, type}`, `failure: {code, message}`,
   and the flat `{error, message}` shape for a 4xx) before touching `inc/aicoach-prerender.php`.
2. **Dropped the avatar/voice-matching machinery entirely.** The old flow opened a throwaway Gary
   session just to read `say.video.avatar_id`/`avatar_model` (added in PR #42 to fix a real
   visible avatar mismatch) and pinned a hardcoded Anam voice id + generation options Gary gave us
   directly (PR #44). Both are now Gary's problem, not ours — "the box pins the same ones the live
   session uses" — so `ihq_aicoach_gary_avatar_config()`, `GARY_SAMI_ANAM_VOICE_ID`, and
   `ihq_aicoach_voice_generation_options()` are gone, and `ihq_aicoach_prerender_all()` no longer
   opens an extra session per run just to read config.
3. **New Coach API wrapper functions, reusing `inc/gary-proxy.php`'s existing signed-request
   plumbing** rather than duplicating the HMAC formula a third time: extracted
   `ihq_coach_sign_headers()` out of `ihq_coach_request()`, gave `ihq_coach_request()` an optional
   `$extra_headers` param (for `Idempotency-Key`), and added `ihq_coach_download()` for the one
   binary endpoint (`GET .../content` returns raw `video/mp4` bytes, confirmed live — Gary's own
   `content.url` is a path back into this same signed API, not a public CDN link like Anam's was).
4. **No retry loop.** The old code retried a `retryable:true` failure up to 3 times via Anam's
   dedicated `/retry` endpoint with a fresh Idempotency-Key each time. The new API has no retry
   endpoint at all, by design — Gary's box retries capacity failures itself, under the *same* job
   id, for up to 2 hours, and a job that's still working shows `waiting_reason` while polling.
   Re-POSTing `/coach/v1/videos` with the same key doesn't start a new attempt — confirmed live —
   it idempotently replays whatever the job's current state is, ready or not. That single fact is
   what makes the new bounded-poll-then-`'pending'` design safe: nothing is lost or abandoned by
   giving up early, the next invocation just picks the same job back up.
5. **Shortened the poll budget, added a `'pending'` result.** The old 60×5s (5 min) budget assumed
   a single invocation had to see the job through to a terminal state. The new 18×5s (90s) budget
   is sized for two real execution contexts: a WP-CLI run (fine either way) and the SFTP-only dev
   environment's one-off diagnostic script, which runs inside a single HTTP request subject to
   WP Engine's own gateway timeout — a multi-minute synchronous wait there risks a 502 before our
   own loop even finishes. `'pending'` (not `'error'`) signals "ask again later," and the WP-CLI
   command reports it as a warning, not a failure.

## Blast radius

- `inc/gary-proxy.php`: refactored `ihq_coach_request()` (additive `$extra_headers` param, HMAC
  logic extracted to `ihq_coach_sign_headers()` — behavior-preserving for every existing call
  site), added `ihq_coach_download()`. No route/handler changes.
- `inc/aicoach-prerender.php`: removed 3 Anam-direct functions + 1 constant + 1 helper; added 2
  Coach API functions; rewrote `ihq_aicoach_prerender_segment()`'s poll/download logic and
  `ihq_aicoach_prerender_all()` (avatar-config step removed); WP-CLI command reports a new
  `pending` bucket.
- `tests/gary-proxy.test.php`: no changes needed, all 47 pre-existing cases still pass — none of
  them inspect the outgoing `headers` array.
- The local render manifest's `fingerprint` scheme changed shape (segment sha256 alone, not a
  combined hash with avatar/voice/model) — every existing entry misses on the next run and
  re-renders once. Expected and harmless: confirmed live, all 6 segments rendered clean on the
  first run under the new scheme and the local `aicoach-videos/manifest.json`/`*.mp4` files are
  untracked (gitignored), so this doesn't touch anything committed. Production/dev will do the
  same one-time re-render the next time `wp aicoach prerender` (or the dev one-off script) runs.
- The SFTP-only dev one-off trigger script (`aicoach-prerender-once.php`, intentionally never
  committed — see its own header) needed regenerating to match the new function signature; done,
  handed to the user to re-upload for dev.

## Notes

- Reply to Gary once dev is confirmed on the new path: "Once your three segments are through this
  route, let me know and we will retire the direct access" — dev's `wp-config.php` still has no
  `ANAM_API_KEY`/Anam credentials in play for this path (never did; the live avatar session's
  separate Anam usage in `inc/anam-proxy.php` is untouched by this change).
