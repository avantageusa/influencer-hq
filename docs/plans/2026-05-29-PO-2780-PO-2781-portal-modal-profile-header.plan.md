---
name: PO-2780, PO-2781 — portal modal, profile contacts, header login
overview: >
  Shipped in commit 2be8544: refresh the "Let's Start the Conversation" modal on the portal home
  template to match Figma (copy, Email/Telegram cards, expanded social platform list, updated CSS);
  narrow profile Username or Contact to Email and Telegram only with API allow-list alignment;
  style the portal header Login button as a gold pill and swap header logo to logo-home-claude.jpg.
todos:
  - id: conversation-modal-figma
    content: Update ms1 modal markup and homepage-style-claude.css for Figma conversation modal
    status: completed
  - id: profile-contact-methods
    content: Restrict profile Username or Contact to Email + Telegram; update ihq_allowed_contact_keys
    status: completed
  - id: portal-header-login-logo
    content: Gold Login button styles in portal-styles.php; logo-home-claude.jpg in portal-header.php
    status: completed
  - id: verify
    content: Manual QA on portal home modal, profile contact save/toggles, header login; php -l touched PHP
    status: pending
---

# PO-2780, PO-2781 — Portal modal, profile contacts, header login

**Ticket:** PO-2780, PO-2781  
**Drafted by:** Cursor (Composer)  
**Shipped:** `2be8544` on `main` (message: `PO-2780,PO-2781`)

**Note:** Plans live under `docs/plans/` (see `docs/plans/README.md`).

## Problem

1. **PO-2780 — Conversation modal:** The "Yes — Let's Start the Conversation" flow on `page-portal-home-claude.php` needed updated copy, layout, and styling per Figma (node `37728:18268`): centered gold title, communication lede, simplified Email/Telegram cards, teal "Favorite Social Media" section, and a full platform list with per-row dashed inputs.
2. **PO-2781 — Profile contacts:** The profile **Username or Contact** section listed legacy channels (KakaoTalk, KICK, Line, TikTok, Twitch, WeChat, WhatsApp). Product asked to show only **Email** and **Telegram**, with saves validated server-side.
3. **Portal header:** Login control should read as a gold CTA (not plain text), and the header logo should use `logo-home-claude.jpg` for parity with the portal home template.

## What changed (commit `2be8544`)

### `page-portal-home-claude.php`

- Added `$ihq_modal_social_platforms` (15 platforms: Ameba, Bilibili, Facebook, Instagram, Kakao Business, Kick, Line, Naver Blog, Reddit, Telegram channel link, TikTok, Twitch, X, Rednote, YouTube) with Figma-aligned placeholders.
- **ms1 modal:** Removed "Get in Touch" eyebrow, old `m-sub` / `m-benefit`; added `m-title--conversation`, `m-lede`, simplified Email/Telegram cards (no SVG icons), `modal-social-section` with heading + lede, foreach-driven social rows.
- Removed duplicate modal-comm inline CSS (moved to stylesheet).
- **`ihqModalGetPlatformHandle()`:** Selected social values saved as `Platform: value` joined by ` | `.

### `css/homepage-style-claude.css`

- Conversation modal tokens: gold title (`#fdd65b`), white lede, `#0b0b0a` cards, `#53cea4` social heading, 43px checkboxes, dashed white inputs, Continue/footer tweaks for `#ms1`.

### `page-portal-profile.php`

- `$contact_platforms` → `[ 'Email', 'Telegram' ]` only.
- Pre-fill **Email** from `$user_email` and **Telegram** from `communication_username` when `_ihq_social_handles` is empty.

### `inc/api-ajax-calls.php`

- **`ihq_allowed_contact_keys()`** → `[ 'email', 'telegram' ]` so `save_settings_field` / `save_settings_toggle` reject removed keys and prune stale prefs on save.

### `template-parts/portal-styles.php`

- **Login button:** Gold background `#EECD5D`, black uppercase text, `6px 18px` padding, `5px` radius; `button.header-login-link` no longer resets to transparent; mobile `4px 12px` / `12px` font.

### `template-parts/portal-header.php`

- Logo `src` changed from `logo-tm.png` to `logo-home-claude.jpg`.

## Out of scope (this push)

- Telegram instant registration / portal auth modal Telegram login (prior commits, e.g. `PO-2771`).
- `page-lander.php` modal parity (not in this commit).
- Deletion of `images/concierge.png` (left unstaged locally; not in `2be8544`).

## Blast radius

- **Profile users** with handles stored under removed keys (kakaotalk, kick, etc.) no longer see those rows; data remains in meta until next contact save, then `array_intersect_key` prunes inactive keys.
- **Modal registration** still uses existing Telegram/email flows; expanded social list only affects `platform_handle` string composition on signup.
- **Header logo** change applies wherever `portal-header.php` is loaded.

## Test plan

- [ ] Open portal home logged out → **Yes — Let's Start the Conversation** → verify title, lede, Email/Telegram cards, 15 social rows, Continue → registration step.
- [ ] Profile → **Username or Contact** → only Email and Telegram; edit values; toggle **Communicate with Me**; confirm AJAX saves succeed.
- [ ] Portal header → Login button gold pill; click opens auth modal; logo displays `logo-home-claude.jpg`.
- [ ] `php -l` on touched PHP files.

## Notes

- FileZilla temp copies under `fz3temp-8` were updated separately during development; deploy repo paths on server (`template-parts/`, `inc/`, theme root).
- Figma reference: `2025-Work--Wolfe-` file, node `37728:18268` (Communication — Modal).
