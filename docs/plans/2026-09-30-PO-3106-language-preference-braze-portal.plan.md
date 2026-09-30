---
name: PO-3106 (FR-15) — language preference carried into Braze and the portal
overview: >
  The AI Coach flow already captures the visitor's language choice and saves
  it as user meta on registration (_ihq_aicoach_language), and already
  resumes the flow in that saved language on a return visit (PO-3102/FR-12's
  loadProgress() → selectLocale()). What's missing, and what this ticket
  actually delivers: the preference reaching Braze as language_preference,
  and the portal reflecting it via the page's language attribute. Along the
  way, found and fixed a real ordering bug — the shared registration
  function already syncs to Braze internally, before aicoach-register.php
  ever got a chance to write the language meta, so the Braze payload would
  always have seen it empty even after just adding the field naively.
todos:
  - id: ordering-fix
    content: "inc/email-verification-handler.php's
      ihq_create_influencer_user_from_registration_data() now accepts an
      optional 'language' key and writes _ihq_aicoach_language BEFORE its
      existing internal call to ihq_send_influencer_to_braze() (previously
      at lines ~295-298, now with the meta write ordered ahead of it).
      inc/aicoach-register.php passes payload.language into the $registration
      array instead of writing the meta itself after the call returns (the
      old post-hoc update_user_meta() call is removed, not duplicated)."
    status: completed
  - id: braze-field
    content: "inc/braze-integration.php's ihq_send_influencer_to_braze() reads
      _ihq_aicoach_language and adds it to the Braze attributes payload as
      language_preference, only when non-empty — every other
      registration path (visitor-intent-handler, telegram-login-handler,
      email-verification-handler's own direct calls) never sets this meta,
      so their Braze payloads are completely unchanged, not sent with an
      empty field."
    status: completed
  - id: portal-lang-attribute
    content: "New locale filter (inc/aicoach-register.php) maps the 7
      AI-Coach locale codes to real WP locale strings and hooks WordPress's
      'locale' filter for a logged-in portal page view, so header.php's
      existing language_attributes() call (already used site-wide) reflects
      the visitor's saved preference. Scope note: no .mo/.po translations
      exist for the theme in any language yet (confirmed — only the .pot
      template), so this does not visibly translate any on-screen text; it
      sets the correct <html lang> and makes the mechanism forward-compatible
      the moment real translations land under NFR-05. Full UI translation is
      NOT part of this ticket — see Notes."
    status: completed
  - id: verify
    content: "php -l on all touched files. New isolated test
      (tests/aicoach-language-braze.test.php, no WP bootstrap, same pattern
      as tests/gary-proxy.test.php) stubbing wp_remote_post/get_user_meta/
      update_user_meta: asserts the language meta is written and readable
      BEFORE the Braze call fires, asserts language_preference is present
      in the Braze payload when a language was captured, and asserts it's
      absent (not present as an empty string) when it wasn't — covering a
      non-AI-Coach registration path unaffected by this change. Did NOT
      exercise a real end-to-end registration on wp-env — braze-integration.php
      has no test-mode flag and its default API keys are the real ones, so a
      live create-account call would hit production Braze with fake data.
      Verified the locale-filter/<html lang> piece live on wp-env instead
      (no Braze involved), confirming it reads a test user's meta correctly
      and leaves the attribute untouched for a user with no saved
      preference."
    status: completed
---

# PO-3106 (FR-15) — language preference carried into Braze and the portal

**Epic:** https://avantageusa.atlassian.net/browse/PO-3062
**Ticket:** https://avantageusa.atlassian.net/browse/PO-3106
**Drafted by:** Claude Code (Sonnet 5)

## Problem

Scenario 27's AC has three parts. Checking each against the code as it stood:

1. *"When they leave and return... the flow resumes in their saved
   language."* Already done — `loadProgress()` calls `selectLocale(
   progress.language )` on resume (PO-3102/FR-12 work, unrelated to this
   ticket).
2. *"When they complete registration successfully... the preference is
   stored against their IHQ portal account and written to Braze as
   language_preference."* Half-true: `_ihq_aicoach_language` user meta IS
   written on successful registration (`inc/aicoach-register.php`) — but
   nothing ever reads it again, and it never reaches Braze. Worse: the
   shared `ihq_create_influencer_user_from_registration_data()` function
   `aicoach-register.php` calls already triggers
   `ihq_send_influencer_to_braze()` *internally*, and returns, before
   `aicoach-register.php` gets to write the language meta afterward. Simply
   adding a `language_preference` read to `ihq_send_influencer_to_braze()`
   without fixing this ordering would have shipped a field that's always
   empty in practice.
3. *"...the IHQ portal opens in that language on arrival."* Nothing reads
   the saved preference anywhere in the portal. There's also no portal
   translation content to open *into* yet — confirmed no `.mo`/`.po` files
   exist for the theme in any language (only the English `.pot` template),
   which is NFR-05's ongoing content gap (separately confirmed against the
   live Google Sheet translation source — see that ticket's own notes).

## Approach

- Move the `_ihq_aicoach_language` meta write inside the shared
  `ihq_create_influencer_user_from_registration_data()`, ahead of its
  existing Braze call, via a new optional `language` key on the
  `$registration_data` array — every other caller of that shared function
  simply doesn't pass it, so their behavior is untouched.
- Add `language_preference` to the Braze attributes payload, read from that
  same meta, only when it's non-empty.
- For the portal: rather than inventing translated copy that doesn't exist,
  wire the one honestly-available piece — WordPress's own `locale` filter,
  which the theme's existing `language_attributes()` call in `header.php`
  already renders into `<html lang="...">`. This is forward-compatible:
  once real translation files exist (NFR-05), logged-in portal pages will
  start actually localizing through this exact same mechanism with no
  further code changes, rather than needing a second pass to wire it up
  later.

## Alternatives considered

- **Have `ihq_send_influencer_to_braze()` read a different source (e.g. a
  request parameter) instead of user meta.** Rejected — user meta is
  already the single source of truth `aicoach-register.php` writes to, and
  every other registration path that might also want to set a language
  preference later can just write the same meta key rather than plumbing a
  new parameter through several call signatures.
- **Reorder by moving the Braze call in
  `ihq_create_influencer_user_from_registration_data()` instead of adding a
  parameter.** The call's current position is already correct and safe for
  its other 3 callers (visitor-intent-handler, telegram-login-handler,
  email-verification-handler) — the actual bug is that AI Coach's
  language write happened in the *caller*, after this function already
  returned. Fixing it by passing the value in, rather than restructuring a
  shared function 3 other flows depend on, is the smaller and safer change.
- **Build out real portal translations as part of this ticket** to make
  "opens in that language" fully true. Rejected — that's NFR-05's scope
  (avatar/asset/translation parity), which is a content gap, not a code gap
  (confirmed live against the Figma/Sheet sources); duplicating that
  investigation or attempting placeholder translations here would just
  create content to throw away once the real, approved translations land.
- **Skip the portal piece entirely, ship only the Braze fix.** Considered,
  since the `<html lang>` change has no visible effect without real
  translations. Kept it in — it's the honest, buildable slice of "opens in
  that language" available today, low-risk, and removes a whole
  implementation step from whoever eventually finishes NFR-05.

## Blast radius

- `inc/email-verification-handler.php`:
  `ihq_create_influencer_user_from_registration_data()` gains one new,
  optional array key and one conditional `update_user_meta()` call ahead of
  its existing Braze call. No change to its return value, its 3 other
  callers, or any behavior when `language` isn't passed.
- `inc/aicoach-register.php`: one line moved (the language meta write goes
  into the `$registration` array passed in, rather than a separate call
  after) plus the new locale filter. `_ihq_aicoach_duration`'s write is
  untouched.
- `inc/braze-integration.php`: one new conditional key on the existing
  attributes array in `ihq_send_influencer_to_braze()`. No other field, no
  other caller path, changes.
- No JS changes. No changes to the resume-locale behavior (already correct,
  untouched).

## Notes

**Full portal UI translation is explicitly out of scope here** — it's
blocked on content (approved translated copy + the theme's own `.mo`/`.po`
files), which is NFR-05/PO-3114's job, not this ticket's. What ships here is
the part of "opens in that language" that's actually buildable without that
content: correct `<html lang>`, and a mechanism that starts working for real
the moment that content exists.

**Not live-tested end to end against real Braze** — `inc/braze-integration.php`
has no sandbox/test-mode switch, and its default API keys (hardcoded
fallbacks in the file) are real production Braze keys. Running an actual
`create-account` call against wp-env would have sent a fake registration to
production Braze. Verified via the isolated unit test instead (same
reasoning `tests/gary-proxy.test.php` already established for Gary's API),
and confirmed the ordering fix and payload shape there rather than live.
