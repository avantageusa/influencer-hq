---
agent: Claude Code
model: claude-opus-5-5
subject: ENGR-7569 onboarding social platforms screen and Profile alignment (PR #127)
date: 2026-10-15
---

# Code Review: ENGR-7569 onboarding social platforms (PR #127)

Independent review of PR #127 (`feat/ENGR-7569-onboarding_social_platforms` into
`feat/ENGR-7566-onboarding_screen_engine`), head `423658e`, against ticket ENGR-7569 and
`.claude/rules/coding-standards.md`. The diff against the base also carries ENGR-7563's
store commit (#121, merged in); that code is reviewed on #121 and only read here where
this PR depends on it (`ihq_portal_onboarding_saved`, handle validation).

## Blockers

- `inc/portal-onboarding-social.php:94` (with `inc/api-ajax-calls.php:942-948`): Profile now
  shows a saved platform outside the 19, but Profile does not keep it. The ticket says such a
  platform "is kept and still shown", and the PR body says "nothing disappears". Registration
  writes keys that are outside the allowlist: `naver-blog`, `kakao-business`,
  `telegram-channel` and `ameba` (`page-portal-home-claude.php:55-70`, `page-lander.php:127+`,
  collected by `js/ihq-visitor-intent.js:171`). `ihq_profile_social_platform_rows()` now
  renders them, and the PHP test covers that with `naver-blog`. But `save_settings_field_ajax`
  still runs `array_intersect_key( $handles, array_flip( $allowed_keys ) )` on every social
  save. So the first Profile edit of any platform deletes all of these handles. Editing the
  extra row itself returns 400 "Invalid social field", because its key is not in
  `ihq_allowed_contact_keys()`. As a result the row is shown but can't be edited, and it is
  lost on the next unrelated Profile save.
  **Suggested fix:** in `save_settings_field_ajax`, stop dropping stored keys outside the
  allowlist. Accept an edit when `$field` is in the allowlist **or** is already a key in the
  stored `_ihq_social_handles`. Add a PHP test: a stored `naver-blog` handle survives a save to
  `x`, and an edit to `naver-blog` is accepted.

## Warnings

- `inc/portal-onboarding-social.php:141` (and `:136`): the mirror writes **every** onboarding
  handle on every onboarding save that touches platforms or handles, not only the handles that
  changed. Two paths lose a handle the user changed later on Profile:
  - Overwrite: onboarding sets X to `@a`, Profile then changes it to `@b`, then any later
    onboarding tick or handle change (resume, or the ENGR-7566 edit-return branch) writes `@a`
    back. A handle cleared on Profile also comes back.
  - Delete: unticking a mirrored platform in onboarding deletes whatever is now saved for it,
    including a value that was edited on Profile.

  The guarantee in the file docblock ("never a handle from registration or Profile") does not
  hold for keys that were edited after mirroring. **Suggested fix:** store the mirrored
  `key => value` map instead of only the keys. Overwrite or unset a key only when the
  `_ihq_social_handles` value still equals the value onboarding last wrote, and only write
  handles whose onboarding value changed. Add tests for both paths.

## Nits

- `inc/api-ajax-calls.php:867`: the 19 keys are now kept by hand in four lists:
  `ihq_portal_onboarding_platforms()`, `ihq_portal_onboarding_platform_labels()`,
  `ihq_allowed_contact_keys()` and the JS `PLATFORMS`. The allowlist is guarded only by a
  test that regex-parses the PHP source text (`tests/portal-onboarding-social.test.php:99-108`).
  **Suggested fix:** build the allowlist as
  `array_merge( ihq_portal_onboarding_platforms(), array_keys( ihq_profile_legacy_platform_labels() ) )`,
  and test the function's return value instead of its source text.
- `inc/portal-onboarding-social.php:94`: `ihq_portal_onboarding_platform_labels()` is rebuilt on
  every loop iteration. **Suggested fix:** hoist it into a local before the loop, as is already
  done for `$legacy`.
- `page-portal-profile.php:811`: the ticket names `$ihq_profile_social_platforms` (line 768) as
  "Profile's platform list". The PR correctly changes `$contact_platforms` instead, but leaves
  the dead 14-entry array in place, so the next reader will likely edit the wrong list.
  **Suggested fix:** delete the unused array, or add a one-line comment that it is unused.

## Checked and fine

- Screen 6 lists the 19 in the ticket's order with none ticked, has no Connect buttons, and is
  not pre-filled from registration.
- Tick shows and focuses the field. Untick hides and clears it.
- CONTINUE is blocked with an inline error, `aria-invalid` and focus on the first missing
  handle. A blank-only handle does not count.
- Only non-blank handles are sent, matching the store's validation.
- No nested ternaries, tuples or custom breakpoints. The CSS has no `@media`.
- Tests use strict deep equality and assert both the DOM state and the posted payloads.

## Checks run

- `npm run test:js`: 325 pass, 0 fail.
- `npm run test:php`: all files pass, including `tests/portal-onboarding-social.test.php` and
  `tests/portal-onboarding-progress.test.php`. The PR body lists the PHP tests as not run
  locally; they pass here (PHP 8 CLI).
- `php -l` on `inc/portal-onboarding-social.php`, `inc/api-ajax-calls.php` and
  `page-portal-profile.php`: clean.
- Not run: ESLint and stylelint (`wp-scripts` is not installed, and the npm registry returned
  401). There is no `tsconfig.json`, so no `tsc`. No manual check on a live Profile page. The
  visual match to Figma frame 46497:79558 was not checked.

## Summary

The onboarding screen itself is solid and well tested, and it meets the ticket's screen
Done-when items. The Profile side does not yet meet "a platform already saved there but not
among the 19 is kept": registration's off-list handles are now displayed, but the first
Profile save deletes them and they can't be edited (blocker). The handle mirror can also
overwrite or delete handles the user changed later on Profile (warning). Recommended action:
**revise**. Fix the Profile save path and make the mirror value-aware, each with a test, then
merge.
