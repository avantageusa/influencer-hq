---
name: Make the IHQ portal installable (Save IHQ to Home Screen)
overview: >
  The onboarding's SAVE IHQ TO HOME SCREEN button needs a behaviour, and the portal needs to be
  installable for Android's native prompt to exist. This adds a web app manifest (served per site),
  icons, the head tags on portal pages, and one script that handles every Save button: the native
  prompt on Android Chrome, an instruction sheet elsewhere, and hiding when installed or dismissed.
  No service worker or offline mode.
todos:
  - id: manifest
    content: inc/portal-install.php — manifest REST route, head tags, enqueue on portal pages
    status: completed
  - id: icons
    content: images/pwa/ihq-icon-192.png and -512.png
    status: completed
  - id: behaviour
    content: js/aicoach/save-home.js (+ save-home-page.js) — prompt / sheet / hide
    status: completed
  - id: tests
    content: JS platform branches and hide conditions; PHP manifest, route, head, enqueue
    status: completed
  - id: design-icons
    content: Replace the interim icons with Design's app icon
    status: pending
  - id: verify
    content: test:js, lint, CI PHP job
    status: completed
---

# [ENGR-7575] Make the portal installable (Save IHQ to Home Screen)

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7575
**Drafted by:** Claude Code (claude-opus-5-5)

## Problem
The portal has no manifest, so browsers cannot install it, and the Save button does nothing.

## Approach
- Manifest from `GET /wp-json/ihq/v1/portal-manifest` (`application/manifest+json`), built with
  `home_url()` so start URL (`/portal/portal-home/`) and scope (`/`) are right on every environment.
  Name "Influencer HQ", short name "IHQ", standalone, black theme, 192/512 PNG icons.
- `wp_head` (which `header.php` calls) prints, on portal pages only (`ihq_is_portal_page_template()`):
  the manifest link, `theme-color`, the iOS `apple-touch-icon`, `apple-mobile-web-app-capable` and title.
- `js/aicoach/save-home.js`: one handler on the document for every `[data-ihq-save-home]`, so buttons
  rendered later (onboarding screens) work. With a captured `beforeinstallprompt` → `prompt()`;
  otherwise a small dialog: iOS (incl. iPadOS posing as a Mac) "Tap the Share button, then Add to Home
  Screen."; other browsers, the browser-menu instruction. A prompt the browser refuses (stale event)
  falls back to the sheet. Accepting hides the buttons and is remembered (`ihq_save_home_installed`, also
  on `appinstalled`); dismissing the prompt or closing the sheet (Close, or Escape anywhere on the page)
  hides them and is remembered (`ihq_save_home_dismissed`). The sheet is a non-modal dialog.
  Standalone display mode (or iOS `navigator.standalone`) hides them from the start. Hiding is one class
  on `<html>`, so later buttons are hidden too. Storage failures never throw.
- `save-home-page.js` calls `mountSaveHome()`, which injects the sheet's styles and passes in the page's
  localStorage.
- This PR renders no button: the `data-ihq-save-home` buttons come from ENGR-7565 (onboarding frame,
  PR #123) and ENGR-7571 (closing screen, PR #128).

## Alternatives considered
- A static `manifest.json` in the theme: start URL and scope would be hard-coded per environment, and
  the server's MIME type for it is unknown.
- Binding each button directly: buttons added by onboarding screens after load would miss it.

## Blast radius
Every portal page gains the manifest link and a small script module. Chrome may now show its own
install affordances on portal pages (the mini-infobar is suppressed by `preventDefault()` on
`beforeinstallprompt`, so the Save button is the one entry point).

## Notes
- Icons are interim: the white "influencer headquarters" mark (`images/new-logo-small.png`) centred on
  black. Design should supply the real app icon.
- Chrome's installability check needs a real browser on the deployed site; it was not run here.
