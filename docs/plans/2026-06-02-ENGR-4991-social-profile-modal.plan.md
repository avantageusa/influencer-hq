---
name: ENGR-4991 — registration social grid and profile editor
overview: >
  Extends ENGR-4991 work with registration-modal social platform selection (3-column grid,
  teal toggle, per-platform inputs below) and a matching editable section on the portal profile
  that reads/writes user meta `platform_handle`. Saves on profile use the existing settings AJAX
  handler with a new account field mapping.
todos:
  - id: modal-social-grid-ui
    content: Replace inline checkbox social rows with 3-column grid and below-the-fold inputs on portal home modal
    status: completed
  - id: modal-social-grid-css
    content: Add homepage-style-claude.css rules for social grid, teal selection, and input rows
    status: completed
  - id: profile-platform-handle-section
    content: Parse platform_handle on profile and render editable social grid with pre-filled selections
    status: completed
  - id: profile-platform-handle-save
    content: Save profile edits via save_settings_field (account/platform_handle) in api-ajax-calls.php
    status: completed
  - id: profile-social-styles
    content: Add sett-social-* styles in template-parts/portal-styles.php
    status: completed
  - id: verify
    content: Run tests, lint, and CI gate before merge
    status: blocked
---

# [ENGR-4991] Registration social grid + profile `platform_handle` editor

**Ticket:** ENGR-4991  
**Drafted by:** Cursor (Codex 5.3)  
**Prior commit on main:** `c305132` (auth session lifetime / cookie 24h)

## Problem

Registration collects social platforms into a single `platform_handle` user meta string (`Label: value | …`), but the conversation modal UI did not match design (grid + teal selection + inputs below), and influencers could not view or edit that data on the profile page after signup.

## Approach

### Registration modal (`page-portal-home-claude.php`, `css/homepage-style-claude.css`)

- Reordered `$ihq_modal_social_platforms` for 3-column grid layout (Figma order).
- Replaced checkbox/inline-input rows with:
  - `.social-grid` clickable platform buttons (teal + underline when selected).
  - `.social-inputs` rows shown below the grid when a platform is selected.
- Added `ihqToggleSocialPlatform()` / `ihqResetModalSocialPlatforms()`; `ihqModalGetPlatformHandle()` builds the stored string.

### Profile page (`page-portal-profile.php`, `template-parts/portal-styles.php`)

- Added `ihq_parse_platform_handle_pairs()` to load existing `platform_handle` meta.
- New collapsible section **Social Media You Post On** with the same 15 platforms.
- Pre-selects platforms that have saved values; inputs pre-filled.
- Auto-saves on blur via `save_settings_field` (`group: account`, `field: platform_handle`).

### API (`inc/api-ajax-calls.php`)

- Mapped `platform_handle` in the account settings field map.
- Uses `sanitize_textarea_field` for the combined handle string.

## Data storage

| Stage | Location |
|-------|----------|
| Pending registration | `pending_reg_code_*` option → `platform_handle` key |
| After verify | User meta `platform_handle` |
| Profile edit | Same meta, updated via AJAX |

## Alternatives considered

- Store per-platform keys in `_ihq_social_handles` — rejected to avoid migration; keep single `platform_handle` string from registration flow.
- Single textarea on profile — rejected; grid matches registration UX and Figma.

## Blast radius

- Portal home conversation modal (step 1) social UX only.
- Portal profile page new section; no change to Email/Telegram contact rows.
- `save_settings_field` accepts one new account field; no change to allowed social contact keys (`email`, `telegram`).

## Notes

- `verify` remains blocked (no full CI run in this step).
- FileZilla temp copies updated separately under `fz3temp-8` for deploy staging.
