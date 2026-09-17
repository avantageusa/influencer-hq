---
name: PO-3103 follow-up — clamp registration locale to Gary's accepted set
overview: >
  Fixes a real regression risk in PO-3103 (auto-detected browser locale, PR #30,
  merged 2026-09-16): Gary's now-published registration surface contract
  (docs.gary.club/clients/influencerhq/, 2026-09-16) states POST /coach/v1/session
  accepts only en/en-us/en-gb on the registration surface and returns 422 for
  anything else. PO-3103 made currentLocale (and therefore the locale sent to
  Gary's session-open call) vary by the visitor's real browser locale — a
  non-English visitor could now make the whole session-open call fail outright,
  not just see untranslated text. inc/gary-proxy.php now clamps whatever locale
  the FE sends to Gary's accepted set before forwarding it, independent of what
  the visitor-facing UI shows.
todos:
  - id: clamp-locale
    content: "ihq_coach_handle_open_session() now lowercases the incoming locale and
      clamps anything outside { en, en-us, en-gb } to 'en' before building Gary's
      session-open payload — previously only an empty string defaulted to 'en',
      any other value (ja, ko, zh, fr, ...) passed through unchanged"
    status: completed
  - id: verify
    content: "php -l; live-tested via curl against local wp-env with locale values
      '', 'en', 'EN-US', 'fr', 'ja' — confirmed the session opens successfully in
      every case and Gary's response echoes back session.locale/structured.locale
      as 'en' or 'en-us' as appropriate, never anything Gary would reject"
    status: completed
---

# PO-3103 follow-up — clamp registration locale to Gary's accepted set

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude Code (Sonnet 5)

## Problem

PO-3103 (merged 2026-09-16, PR #30) made the AI Coach flow auto-detect the visitor's
browser locale and use it as `currentLocale` from page load — including as the `locale`
sent to `openGarySession()`, which forwards it verbatim to Gary's `/coach/v1/session`
via `inc/gary-proxy.php`.

On 2026-09-17, Gary published a hosted, versioned contract for the registration surface
(`docs.gary.club/clients/influencerhq/`) that states plainly: *"Registration accepts only
reviewed English (`en`, `en-us`, `en-gb`). Other languages return 422 until translations
are approved and released."* Gary's direct answer to a question in the shared Teams thread
confirmed this is enforced now, not a future plan.

Before this fix, `inc/gary-proxy.php` only substituted `'en'` for an *empty* locale param —
any other value (`ja`, `ko`, `zh`, `fr`, anything) was forwarded to Gary unchanged. For a
non-English visitor, PO-3103's auto-detection would now make the very first
`POST /coach/v1/session` call fail with a 422 — not merely show untranslated text, but
break session opening entirely, forcing the no-connection fallback path for every visitor
outside the reviewed-English set.

## Approach

`ihq_coach_handle_open_session()` now lowercases the locale param and clamps it to Gary's
accepted set (`en`, `en-us`, `en-gb`) before building the payload — everything else
(missing, empty, or any of the other 6 supported UI locales) falls back to `'en'`, exactly
like the pre-PO-3103 behavior for a missing locale.

This is deliberately a proxy-layer fix, not a frontend one: `currentLocale` (detected
browser locale, or a visitor's manual selection) keeps driving the UI text via
`applyLocale()`/`t()` exactly as before — only what we tell *Gary* is constrained. The
visitor still sees the page in their detected/selected locale (falling back to English
copy for the 6 untranslated ones, same as always); Gary's session just always opens as
English underneath, since that's the only version of the script it currently has
approved.

## Alternatives considered

- **Fixing this in the frontend** (`aicoach-coach-flow.js`, e.g. clamping
  `currentLocale` before calling `openGarySession()`): rejected — `currentLocale` also
  drives the visible language selector state and UI text; clamping it there would make
  the selector itself lie about which language is "current." The proxy is the right
  place to separate "what the visitor sees" from "what Gary is told."
- **Returning a clear client-facing error instead of silently forcing English**: rejected
  — silently falling back to English matches this epic's existing behavior for every
  other "unsupported/unapproved" case (untranslated locales, held scripts, etc.) rather
  than introducing a new kind of visible failure for something the visitor did nothing
  wrong to trigger.

## Blast radius

- `inc/gary-proxy.php`: one function, `ihq_coach_handle_open_session()` — locale
  normalization/clamping only. No route, payload shape, or other handler changed.
- No JS changes. No change to `currentLocale`, `applyLocale()`, `selectLocale()`, or the
  language selector.

## Notes

- Separate, larger, NOT addressed here: Gary's same 2026-09-16 contract confirms
  `say.video` is always `null` on the registration surface — no live avatar token is or
  will be issued there. That's a bigger architectural question (whether/how to pursue
  Gary's suggested "pre-recorded video assets + synchronized text" approach for PO-3092)
  raised separately to Filip, not part of this fix.
