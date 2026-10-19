---
name: Onboarding draft posts (screen 14) and post action (screen 15) via Gary
overview: >
  Screens 14 and 15 of the portal onboarding are the last two not yet built. Screen 14 asks
  Gary's new copy-and-paste posting routes on Sami's box for one draft per platform the
  Influencer saved on screen 6, shows each with Copy and Share, and lets the Influencer ask for
  a change to one draft by typing or by voice. Screen 15 stores what they will do next, and
  its edit choice returns to 14 with the drafts kept. Drafts are stored on the account so a
  returning Influencer and the Post page (PO-3190) get them back. The Post page itself, any
  publishing to a social account, and Product's final brief and link text are out of scope.
todos:
  - id: plan
    content: Write this plan
    status: completed
  - id: store
    content: Add a validated, server-written `drafts` field (platform => text) to the progress store, in its own user meta
    status: completed
  - id: routes
    content: Add inc/portal-onboarding-drafts.php with POST /ihq/v1/onboarding/drafts and /drafts/revise, proxied to /coach/v1/posting/draft and /posting/revise through ihq_coach_request()
    status: completed
  - id: brief-setting
    content: One filterable brief + link setting with a placeholder brief, pending Product (OQ6)
    status: completed
  - id: config
    content: Send the two route URLs to the browser in the layer context; expose postJson on the onboarding API
    status: completed
  - id: screens
    content: Add js/aicoach/onboarding-screens-posts.js (screen 14), onboarding-screens-post-action.js (screen 15) and css/portal-onboarding-posts.css
    status: completed
  - id: tests
    content: PHP tests for the store field and the routes (Gary mocked); JS tests for both screens with the fake DOM and fake clock
    status: completed
  - id: verify
    content: npm run test:js (625) and test:php pass; 21 guards broken one at a time, all caught; lint not run (no eslint, @wordpress/scripts or phpcs installed locally); no local browser run and no live Gary call yet (dev's go-ahead needed)
    status: in-progress
---

# [ENGR-7570] Onboarding draft posts and post action via Gary

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7570
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
The onboarding skips screens 14 (draft posts) and 15 (post action) because they have no
module (ENGR-7566 passes over a screen that is not installed). Screen 14 needed a drafting
service; the spike ENGR-7562 confirmed Gary's posting routes on Sami's box (contract
confirmed by Gary by email on 2026-10-18 and verified live in Japanese):
`POST /coach/v1/posting/draft` and `POST /coach/v1/posting/revise`, HMAC-signed with the
existing Coach API key, server side only.

## Approach

### Store (inc/portal-onboarding-progress.php)
- New state field `drafts`: `platform => text`. It is kept in its own user meta key
  (`_ihq_portal_onboarding_drafts`), like completion, and is not a key the progress POST
  accepts. A draft request takes seconds; with its own key, its write can never overwrite a
  stage or answer save that landed meanwhile, and the browser cannot write draft text the
  UI says is not editable.
- `ihq_portal_onboarding_store_drafts( $user_id, $drafts )` validates (known platform,
  non-empty string, at most `IHQ_PORTAL_ONBOARDING_DRAFT_MAX_LENGTH` characters counted with
  `mb_strlen`), merges per platform and fires the saved action. `get_state()` returns only
  the drafts of platforms still selected, so unticking a platform hides its draft and
  ticking it again brings it back.

### Routes (new inc/portal-onboarding-drafts.php)
- `POST /ihq/v1/onboarding/drafts` `{ platforms?, lang }`: drafts for the given platforms (a
  subset of the stored selection, at most 8, Gary's per-request limit) or, when left out, the
  first 8 selected platforms without a stored draft. The platforms come from the store, never
  only from the browser, so there is one draft per confirmed platform and none for others.
- `POST /ihq/v1/onboarding/drafts/revise` `{ platform, lang, instruction | audio }`: the
  previous text is the stored draft (not the browser's copy); exactly one of a typed
  instruction (at most 500 characters) or a recording (`{ data: base64, mime }`, allowlisted
  mime, at most 1 MB decoded). The reply replaces only that platform's draft. `heard` (Gary's
  transcription) is passed back.
- Both: logged-in Influencer and the wp_rest nonce (the progress routes' check plus the gate's
  Influencer role check, since each call spends the shared Gary budget). `influencer_id` is the
  user meta `wp_influencer_guid`. When it is empty or is an email address (Braze can store
  one there), a pseudonymous stable id is derived instead, so no email is sent to Gary.
- Gary errors map to user-facing codes: 422 posting_refused → `refused`, 429
  posting_budget_exhausted → `limit`, 503 posting_unavailable, transport errors, missing
  credentials and auth/replay errors → `unavailable`, 502 posting_failed or an unusable reply
  → `failed`. Each failure is logged with Gary's status and error code, never the text.
- The brief and link (open with Product, OQ6) are one setting,
  `ihq_portal_onboarding_draft_brief()` through the `ihq_portal_onboarding_draft_brief`
  filter: a placeholder brief and no link by default. The referral link is the candidate; it
  needs the Genius API call with token refresh (inc/api-ajax-calls.php), so it is not wired
  until Product confirms it.

### Browser
- The layer context gains `draftsUrl` and `draftsReviseUrl`; `api.postJson` exposes
  onboarding-request.js's postJson to plug-ins (they import nothing).
- `js/aicoach/onboarding-screens-posts.js` (screen 14) and
  `js/aicoach/onboarding-screens-post-action.js` (screen 15):
  - 14 `draft_posts` (MANUAL, own CONTINUE like screen 6): a panel per saved platform with
    the platform name, the draft (text, not editable), Copy (clipboard, then a "Copied"
    confirmation) and Share (`navigator.share`, hidden where the browser has none). Missing
    drafts are requested in batches of 8 when the screen opens; stored ones are shown without
    a call. "Ask Sami to change this" opens a field and a mic for that panel; the mic records
    with MediaRecorder (sent to Gary as audio, so any of the seven languages works, unlike the
    landing flow's English-only SpeechRecognition). A failure or the client timeout shows a
    clear message in that panel with Try again, and keeps every existing draft usable.
    Sami's reply to a revision is caption-only (`shell.say` with no clip).
  - 15 `post_action` (PICK): the three choices as a radio group styled as the design's
    checkboxes, saved as `answers.post_action_choice`; `choice()` feeds the existing
    post_action → draft_posts branch, so "Let's edit this together now" returns to 14.
- Strings go through the string table (English only; translations fall back, as for every
  other onboarding string).

## Alternatives considered
- **`drafts` as a POST-writable progress key:** simpler, but lets the browser write draft text
  and races the long draft call against stage saves on one record. Rejected.
- **One Gary call per platform:** independent failures, but up to 19 turns from the shared
  budget per Influencer instead of 3. Batches of 8 (Gary's limit) instead.
- **Browser SpeechRecognition for voice:** what the landing flow uses, but English-only there
  and not available in every browser; Gary transcribes the recording in all seven languages.
- **Reusing the question screens module for screen 15:** would need its controller and
  QUESTIONS list changed (and their tests) for one screen with a branch; kept a separate
  module that reuses the question screens' CSS classes instead.

## Blast radius
- The progress state gains `drafts`; `ihq_portal_onboarding_default_state()` and
  `ihq_portal_onboarding_record()` change, and the store test's expected default state is
  updated for the new field. The Braze sync (#130, not merged) listens to the saved action;
  checked on its branch: a partial with only `drafts` maps to no attributes, so nothing is
  sent.
- The gate's layer context gains two URLs (its test is updated).
- Screens 14 and 15 stop being skipped once the module ships: every Influencer reaching screen
  13 now goes through them and spends Gary budget (one turn per batch, per retry, per
  revision).
- A Gary outage cannot block the flow: CONTINUE on 14 is always available.

## Notes
- Unverified against Gary: whether all 19 platform keys are accepted (the contract example
  shows instagram and x; an unknown one would refuse the whole batch), audio mimes other than
  `audio/webm` (Safari records `audio/mp4`), Gary's limits on brief/instruction length and
  audio size, and the error body's exact shape. No live call made without the dev's go-ahead.
- Requests are built when sent, so a request queued before a language switch goes in the new
  language. A request that times out in the browser may still complete on the server and be
  stored; the Influencer sees it on the next visit.
- DEPLOYMENT NOTE: no new credentials; the routes use the existing GARY_COACH_KEY/SECRET.
- Product (Ivan, OQ6): brief text and link.
