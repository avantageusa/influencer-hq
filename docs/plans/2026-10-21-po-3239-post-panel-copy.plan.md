---
name: Post page Copy copies that panel's draft
overview: >
  The Post page Copy buttons are markup-only. Selecting Copy should put that
  panel's draft text on the clipboard, confirm, and leave date, platform label,
  and markup out. Copy stays disabled on a default panel (FR-07). Share and
  Posting Now are out of scope.
todos:
  - id: plan
    content: Write this plan
    status: completed
  - id: js
    content: js/portal-post-copy.js copies .post-panel-draft textContent, confirms, disables default panels
    status: completed
  - id: wire
    content: Enqueue on the Post template; enable Copy; pointer cursor when enabled
    status: completed
  - id: tests
    content: Unit tests for draft-only copy, confirmation, and FR-07 disable
    status: completed
  - id: verify
    content: npm run test:js for the new tests
    status: completed
---

# [PO-3239] Post page Copy copies that panel's draft

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3239
**Drafted by:** Cursor Grok 4.6

## Problem

Influencers need one tap to copy a panel's draft so they can paste it into the platform app. `.post-panel-copy` exists on `page-portal-post.php` but does nothing (`tabindex="-1"`, `cursor: default`, no script).

## Approach

- Isolated module `js/portal-post-copy.js`, loaded only on the Post template.
- Click copies `textContent` of that panel's `.post-panel-draft` via `clipboard.writeText` (same-gesture, Safari-safe). Not the date, `aria-label`, or markup.
- Button text becomes "Copied" for `COPY_CONFIRMATION_MS` (2s), then reverts. A refused clipboard shows the same failure copy as onboarding posts.
- A panel is copyable only with `data-has-draft` and non-empty draft text (FR-07: placeholder panels copy nothing). The static mock marks the three panels as having drafts so Copy can be used with the current stand-in text.
- Share stays non-functional.

## Alternatives considered

- Reuse onboarding `createCopyShare` — that screen's panel map and string table are not the Post page.
- Inline script in `page-portal-post.php` — untested, and the rest of the portal extracts this kind of behaviour.

## Blast radius

Post template only. Share, Posting Now, and onboarding posts are untouched.
