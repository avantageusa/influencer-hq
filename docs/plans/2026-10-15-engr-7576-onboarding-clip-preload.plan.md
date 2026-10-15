---
name: Preload the next onboarding clip; baseline Coach page LCP/FCP
overview: >
  Two enablers for PO-3223 (fast loading from Asia). While a screen's clip plays, the next screen's
  clip (active language) and hero image are preloaded, and again in the new language after a switch.
  And a lab baseline of today's Coach page LCP/FCP on dev (mid-range Android, 4G, Singapore), recorded
  in the ticket and PR. Datadog/RUM is out of scope.
todos:
  - id: preloader
    content: js/aicoach/onboarding-preload.js + onboarding-addon-preload.js (plug-in for the onboarding entry)
    status: completed
  - id: tests
    content: next clip and image on screen start, language switch, release of the previous clip, once per URL
    status: completed
  - id: baseline
    content: Lighthouse/WebPageTest run of today's Coach page on dev from Singapore, numbers and settings in the ticket and PR
    status: blocked
  - id: device-network-log
    content: Done-when 1 on iOS Safari and Android Chrome (Data Saver on and off) - network log shows the next clip's request starting during the current screen, and the player served from cache or a 206 with no second full download
    status: blocked
  - id: preload-timing
    content: In the same lab run, compare starting the preload on 'screen' (today) with starting it once the current clip plays through, on 4G; keep the faster start-up
    status: blocked
  - id: verify
    content: test:js, lint on touched files
    status: completed
---

# [ENGR-7576] Preload the next onboarding clip; baseline Coach page LCP/FCP

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7576
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
Each onboarding screen plays a clip; fetching it only when the screen opens adds a network round trip
per screen, worst on 4G in Asia. And there is no measurement to compare PO-3223's targets against.

## Approach
- `createPreloader( { api, document } )` listens to the onboarding events: on `screen` (emitted by the
  engine, ENGR-7566, with `{ stage, next }`) it requests `api.clipUrl( next )` in the active language
  and `api.imageUrl( next )`; on `locale` it requests the next clip in the new language.
- A clip is preloaded with a detached, muted `<video preload="auto">`, so the request is the same kind
  the player makes and the cache serves it. Only the latest preload keeps loading: the previous one is
  released (`src` removed, `load()`), so preloads never pile up on a phone. A clip is skipped only
  while it is the one loading, so switching back to a language requests its clip again (a released
  clip stopped downloading); a hero image is requested once.
- Plug-in `onboarding-addon-preload.js`, picked up by the shell's `onboarding-addon-*` pattern
  (ENGR-7565). This PR is based on `main`, where the shell does not exist yet, so it is inert until
  ENGR-7565/7566 are merged.
- `js/aicoach/fake-dom.js` is ENGR-7565's test helper, copied byte for byte so the test runs on
  `main`; identical content merges cleanly.

## Alternatives considered
- `<link rel="preload" as="video">`: not supported for video in most browsers.
- `fetch()` of the whole file: the player's range requests may not reuse it, doubling the download.

## Blast radius
New plug-in files only; nothing runs until the onboarding layer renders.

## Notes
- No onboarding clips exist yet (T12 waits on OQ1), so on dev today every screen is caption-only and
  nothing is preloaded; the network-log check needs clips.
- Baseline not recorded: it needs a run against dev from Singapore (WebPageTest's Singapore location,
  or Lighthouse from a Singapore host), which this build session cannot do. The settings to use:
  mid-range Android emulation (Moto G Power class), 4G throttling, Singapore, Coach page
  `/portal/portal-home/` while logged in, median of 3 runs, LCP and FCP.
- ENGR-7576 cannot close on this PR alone: Done-when 1 (device network log) and Done-when 3
  (baseline) are owed, see the `device-network-log`, `preload-timing` and `baseline` todos. The
  preload starts on `screen`, right after the current clip starts; whether it then competes with the
  current clip on 4G is to be measured in the same lab run before changing the order.
