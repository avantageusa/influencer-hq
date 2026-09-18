---
name: PO-3062 — release the "identity" held registration stage via Gary's new /attest route
overview: >
  Gary published a new mechanism (2026-09-18, docs.gary.club/clients/influencerhq/) for
  releasing a held registration script stage per-session: POST /coach/v1/session/{id}/attest
  with a claim signed by a SEPARATE credential, COACH_REGISTRATION_SECRET, distinct from the
  per-request Coach signing secret. Of the four releasable stages (intro, identity, channels,
  complete), only "identity" has a fact we can honestly attest right now without a product/
  business decision: narration_not_required — always true, since that screen is our own form
  and was never meant to be spoken. inc/gary-proxy.php now exposes a narrow, hardcoded-to-
  identity-only route for this; intro/channels/complete are deliberately NOT touched here —
  their facts (meetings_available, delivery_verified, account_created) need real, verified,
  per-visitor confirmation that only Filip/product and later backend integration can supply.
todos:
  - id: registration-secret
    content: Added ihq_coach_registration_secret() (resolves COACH_REGISTRATION_SECRET from
      wp-config.php/env, same pattern as the existing key/secret resolvers) — a deliberately
      SEPARATE credential from GARY_COACH_KEY/SECRET, matching Gary's own security rationale
      (this secret asserts facts about a real visitor; the ordinary request secret only proves
      a call came from our backend)
    status: completed
  - id: sign-stage-release
    content: Added ihq_coach_base64url_encode() and ihq_coach_sign_stage_release() — reimplements
      Gary's reference signer (sami-portal-proof.mjs's createSamiStageRelease) in PHP exactly,
      same claim shape (v, aud sami:registration_stage, key_id, player_ref, session_id, stage,
      the stage's facts, iat, exp = iat+60, jti) and same base64url(json).base64url(HMAC-SHA256)
      encoding
    status: completed
  - id: attest-identity-route
    content: "Added ihq_coach_handle_attest_identity() and POST /ihq/v1/coach/{session_id}/
      attest-identity. Deliberately narrow: the ONLY fact it ever signs is
      narration_not_required for the identity stage — it takes session_id/player_ref as input
      but never a caller-supplied stage or fact, so it can't be repurposed to attest something
      unverified for intro/channels/complete without a real code change"
    status: completed
  - id: verify
    content: "php -l; 9 new unit tests in tests/gary-proxy.test.php (no WordPress/network
      dependency) verifying the signed token's exact shape/claims/signature and the handler's
      request/response — all pass alongside the pre-existing 15. Live-verified end to end
      against the real Gary API on wp-env: opened a session, called the new route with its
      session_id/player_ref, got back released: true, stage: identity, and the session's own
      registration.released_stages now lists identity with release_requires down to just
      meetings_available for intro"
    status: completed
---

# PO-3062 — release the "identity" held registration stage via Gary's new /attest route

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude Code (Sonnet 5)

## Problem

Gary's registration manifest (`GET /coach/v1/registration/scripts`) marks `intro`, `identity`,
`channels`, and `complete` as held — Gary's API can't independently verify the facts those
passages' copy assumes, so it won't speak them. Asked Gary directly how to release them; the
answer (2026-09-18, in both a Teams reply and the now-published contract) is a new endpoint,
`POST /coach/v1/session/{id}/attest`, that accepts a per-stage claim signed with a *separate*
credential (`COACH_REGISTRATION_SECRET`) held only by backend code that can actually see the
asserted fact is true.

`identity` was a hold we hadn't even known about before this reply — it sits between `channels`
and `complete` in every sequence, so it would have blocked the whole released-stage chain
regardless of the other three.

## Approach

1. **New, separate credential.** `ihq_coach_registration_secret()` mirrors the existing
   `ihq_coach_key()`/`ihq_coach_secret()` pattern but reads `COACH_REGISTRATION_SECRET` — kept
   distinct on purpose, matching Gary's stated rationale: this secret asserts real facts about a
   real visitor (an account was created, a portal was entered), and folding that into the
   ordinary per-request signing secret would let anything able to make an API call also make
   those assertions for free.
2. **Signing, ported from the reference implementation.** Fetched Gary's actual reference
   signer (`sami-portal-proof.mjs`, downloadable from their docs portal) rather than guessing
   the claim shape from the email/contract prose alone. `ihq_coach_sign_stage_release()`
   reproduces `createSamiStageRelease()` exactly: claims `{v:1, aud:'sami:registration_stage',
   key_id, player_ref, session_id, stage, ...facts, iat, exp: iat+60, jti}`, encoded as
   `base64url(json)` + `.` + `base64url(HMAC-SHA256(secret, encoded_json))`.
3. **Only `identity` is wired up, and deliberately not generalized.** Its fact,
   `narration_not_required: true`, is always true by construction (that screen has no Sami
   narration at all — it's our own form, per its own `hold_reason`: "IHQ owns form validation").
   `intro`'s `meetings_available`, `channels`' `delivery_verified` (+ which channels), and
   `complete`'s `account_created`/`account_ref`/`portal_transfer_ready` all require confirming a
   real, possibly per-visitor fact — that's a product call (does a "meetings" feature actually
   exist?) and/or a backend-integration call (does account creation succeed for *this* visitor?)
   that this PR does not attempt. `ihq_coach_handle_attest_identity()` takes only
   `session_id`/`player_ref` as input — never a stage or fact from the caller — so it physically
   cannot be pointed at the other three without an actual code change, not just a bad request.
4. **`key_id` resolved live, not hardcoded.** The handler calls `GET /coach/v1/health` first and
   reads `registration.key_id` from the response, per Gary's own docs ("you are looking for...").
   Avoids hardcoding a value that could change if the Coach key is ever rotated.

## Known limitation — no visible behavior change yet

`js/aicoach-coach-flow.js` doesn't call `/narrate` or `/advance` at all today — every screen
after the intro is still our own static `SCREENS` caption, entirely client-side. Releasing
`identity` in Gary's system has no visible effect until that separate integration exists. This
is deliberate prep, not a regression or a wasted step: `identity`'s release is a hard
prerequisite in the sequence regardless of when `/narrate`/`/advance` land, and verifying the
attestation mechanism now — while the reference signer and claim shape are fresh — is cheaper
than re-deriving it later.

## Alternatives considered

- **A generic `/attest` proxy taking `stage` from the request body**: rejected. Gary's own docs
  say plainly "recording an assertion is not verifying it" — a generic passthrough would let a
  future caller (or a copy-pasted example) attest `channels`/`complete`/`intro` without anyone
  having actually confirmed those facts. The narrow, single-purpose route is the safer shape for
  a mechanism whose entire point is "only sign what you've verified."

## Blast radius

- `inc/gary-proxy.php`: two new small functions (secret resolver, base64url helper), one signer,
  one handler, one route. Nothing else changed.
- `tests/gary-proxy.test.php`: 9 new cases, no WordPress/network dependency.
- `.wp-env.json` (untracked, not committed): added the real `COACH_REGISTRATION_SECRET` for
  local testing, from `docs.gary.club/clients/influencerhq/credentials.html`.

## Notes

- `intro`/`channels`/`complete` remain open questions for Filip (does IHQ have anything
  "meetings_available" could honestly mean today? what does "delivery_verified" require for
  `channels`?) and for backend integration (`complete` needs the account-creation code path —
  likely Milos's PO-3257 work — to call this same attest mechanism at the moment an account is
  actually confirmed created).
