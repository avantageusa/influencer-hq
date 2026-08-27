---
name: PO-3092 — AI Coach intro video on Landing Page arrival
overview: >
  page-home-aicoach.php currently opens straight on the 2/5/10-minute tier picker; there is no
  coach intro at all. Add a first "intro" panel that auto-connects to the existing Anam avatar
  (same session-token proxy already used by page-portal-poc.php) on page load, has Sami speak the
  FR-01 approved script verbatim via talk(), and renders synced on-screen captions built from
  MESSAGE_STREAM_EVENT_RECEIVED chunks per Anam's own captioning guidance. On endOfSpeech (or a
  connection-failure fallback) the page auto-advances into the existing tier-selection panel — no
  tap required, since the AC requires the video to begin automatically, unlike the poc page which
  is deliberately tap-to-start. Out of scope: language selection/detection (FR-12/13/16), We
  Believe screens (FR-02), and anything past the intro — those are separate stories in the epic.
todos:
  - id: intro-panel-markup
    content: Add an "intro" panel (active by default, before "home") with a caption region; extend the shared avatar/portrait area with a video element + connecting state, mirroring poc-avatar
    status: completed
  - id: anam-session-bootstrap
    content: Reuse inc/anam-proxy.php's session-token + persona-preview REST endpoints (no backend changes needed) to mint a session and stream to the video element on load
    status: completed
  - id: verbatim-script-playback
    content: Drive a single talk() call with the FR-01 approved script text (constant, not paraphrased) and resolve on endOfSpeech
    status: completed
  - id: synced-captions
    content: Build on-screen text by appending MESSAGE_STREAM_EVENT_RECEIVED role=persona content chunks in arrival order (Anam's documented captioning pattern), not a fixed timer
    status: completed
  - id: autoplay-fallback
    content: Start the Anam video muted (browser autoplay policies block unmuted autoplay without a gesture) with a visible unmute control; captions carry the message either way
    status: completed
  - id: connection-failure-fallback
    content: If session-token mint or streaming fails, fall back to displaying the full script as static text after a short beat, then auto-advance — page must never dead-end
    status: completed
  - id: auto-advance
    content: On finishing the intro (spoken or fallback), transition to the existing home/tier panel automatically; identical across all 3 tiers since no tier is chosen yet
    status: completed
  - id: verify
    content: Manual review for PHP/JS syntax correctness (no local PHP CLI available to run phpcs/parallel-lint in this environment — flagged as unverified) + exercise happy path and forced-failure path in browser
    status: completed
---

# PO-3092 AI Coach intro video — Scenario 1 (FR-01)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3092 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Problem

`page-home-aicoach.php` renders the tier-selection screen immediately; there's no AI Coach intro.
FR-01 requires a lip-synced coach video that starts automatically on page load, speaks an approved
script verbatim, shows synced on-screen text, and behaves identically across all three time tiers,
before the time-selection screen.

## Reproduction (n/a — new screen)

Visiting the AI Coach template today shows the tier picker directly; there is no intro state.

## Approach

1. **Reuse the existing Anam plumbing.** `inc/anam-proxy.php` already exposes
   `/wp-json/anam/v1/session-token` and `/persona-preview`, used today by
   `page-portal-poc.php`'s "Sami" avatar. No backend changes — same REST base + nonce pattern.
2. **New `intro` panel**, inserted before the existing `home` panel and active by default. The
   existing `.aicoach-portrait-wrap` becomes a small avatar stage (portrait image + `<video>` +
   connecting spinner), matching `.poc-avatar`'s established idle/connecting/live states, so the
   same visual language carries from the POC into production.
3. **Auto-start, not tap-to-start.** Unlike the POC (deliberately gated behind a tap), the AC
   requires the video to "begin automatically" on load, so `start()` fires from an IIFE instead of
   a click handler.
4. **Verbatim script.** A single `talk()` call with the exact FR-01 approved script text (kept as
   one named constant, not the paraphrased POC greeting) — "no LLM rephrasing," same as the POC's
   documented approach.
5. **Synced captions from the SDK's own event stream**, not a fixed per-character timer: Anam's
   docs describe exactly this pattern — each persona `MESSAGE_STREAM_EVENT_RECEIVED` carries a
   `content` delta for the current `utteranceId`; appending deltas in arrival order **is** the
   documented way to build live captions. `endOfSpeech` on that event marks completion.
6. **Auto-advance.** On `endOfSpeech` (or the fallback below), swap to the `home` panel using the
   page's existing `showPanel()` — no product-visible difference between tiers since the tier
   hasn't been picked yet.
7. **Fallback on failure.** If token-minting or streaming fails (no `ANAM_API_KEY` locally, network
   error, etc.), show the full script as static text after a short beat and still auto-advance —
   never leave the visitor stuck on a blank/broken intro.

## Alternatives considered

- **True literal pre-recorded `.mp4` per language:** matches the ticket's literal wording ("pre-
  recorded lip-synced video") most closely, but no video asset pipeline/CDN exists for this yet,
  and the repo already has a live Anam avatar (real-time TTS + lip-sync) wired up and in production
  use on the POC page for this exact script style. Reusing it delivers the same visitor experience
  (a lip-synced coach video reciting an approved script) today; swapping to literal pre-rendered
  files later, if the business wants that, is a drop-in replacement of the video source, not a
  redesign of this panel.
- **Fixed-duration timer for captions** (character-count based, like the POC's `estMs`): rejected —
  Anam's own docs describe streaming `content` deltas specifically for synced captions; a timer is
  a worse approximation of something the SDK already gives us directly.
- **Require a tap before playing (POC's pattern):** rejected — AC explicitly says the video begins
  "automatically" on page load, not on interaction.

## Blast radius

- Touches only `page-home-aicoach.php`. No changes to `inc/anam-proxy.php`, `functions.php`, or any
  shared template part — the session-token/persona-preview endpoints are consumed, not modified.
- No new dependency: the Anam JS SDK is loaded via the same `jsdelivr` ESM CDN import already used
  on `page-portal-poc.php`, not a new package.
- Every visitor to this template now mints an Anam session token on load instead of on tap — higher
  baseline Anam usage/cost than the POC page. Worth flagging to product/eng once this hits real
  traffic (rate limiting is still just the WP REST nonce check, same as the POC — noted as a POC-era
  gap in `anam-proxy.php`'s own comments, not something this change introduces or fixes).

## Notes

- **Autoplay-with-sound risk:** browsers block unmuted autoplay without a user gesture. The video
  starts muted with a visible unmute control; the AC's literal wording ("video begins automatically
  ... on-screen text appears in sync") is satisfied without sound, and captions carry the message
  regardless. Flagging this as an assumption rather than deciding it silently.
- **Language:** hardcoded to the existing default Anam persona (English) for this story. Per-
  language avatars/voices are FR-16, browser-locale detection is FR-12 — both separate stories in
  this epic; this panel doesn't block either.
- **Unverified:** no PHP CLI is available in this environment, so `composer lint:php` / `phpcs`
  could not be run against the touched file — reviewed by hand against the existing, deployed
  `page-portal-poc.php` patterns instead. Dev should run the repo's normal lint gate before merge.
