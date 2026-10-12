---
name: Show the Copy buttons for the Profile referral links
overview: >
  The Follower Referral link and Influencer Referral link rows on the Profile page each have a
  Copy button whose click handler copies the displayed URL, but the PO-3061 Figma-aligned
  restyle put the button in the same visually-hidden selector list as the username Save button,
  so the user sees no Copy at all. PO-3368 AC1 requires a visible Copy that copies exactly the
  displayed link. This removes the Copy button from that visually-hidden rule and changes
  nothing else: the Save button stays hidden exactly as it is, the click handler and the copied
  text are untouched, and no breakpoint or pixel width is added.
todos:
  - id: css
    content: Drop `#portal-content .sett-account-card .sett-referral-copy-btn` from the visually-hidden rule in page-portal-profile.php; keep `#portal-username-save-btn` hidden
    status: completed
  - id: layout
    content: Confirm the existing `.sett-referral-copy-btn` style (border, flex-shrink 0, margin-left) and the flex `.sett-row-val` row lay the button out next to the URL without new CSS
    status: completed
  - id: verify
    content: php -l, npm run test:js, npm run test:php, diff stat against origin/main
    status: completed
---

# [ENGR-7355] Show the Copy buttons for the Profile referral links

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7355 (under epic PO-3367)
**Drafted by:** Claude Code

## Problem
The Profile page account card renders a `copy` button after each referral URL
(`page-portal-profile.php`, the Follower and Influencer referral rows), and the click handler
copies `textContent` of the displayed URL to the clipboard, which is what PO-3368 AC1 asks for.
The PO-3061 "Figma-aligned Profile page" change hid the username Save button with a
visually-hidden rule (1px box, `clip: rect(0 0 0 0)`) and put the Copy button in the same
selector list, so both referral rows show the link with no Copy next to it.

## Approach
- Remove `#portal-content .sett-account-card .sett-referral-copy-btn` from the visually-hidden
  selector list. The rule keeps `#portal-content .sett-account-card #portal-username-save-btn`
  with the same declarations, so the Save button behaves exactly as before.
- No new CSS. The generic `#portal-content .sett-referral-copy-btn` rule already gives the button
  its gold outline, `flex-shrink: 0` and `margin-left: 8px`; the row value is a flex container
  aligned to the end, and the account-card `.sett-row-val > *` rule keeps the URL on one line
  with an ellipsis, so the button sits to the right of the URL and the URL truncates first.
- No handler or copy-text changes. The button copies exactly the displayed link already and
  does nothing until the link has loaded (`sett-referral-url--has-value`).

## Alternatives considered
- **Delete the visually-hidden rule entirely:** would also show the username Save button, which
  PO-3061 hid on purpose (the username field saves on change). Out of scope.
- **A new rule or width for the button:** not needed, and the coding standard forbids a new
  breakpoint or one-off pixel value.

## Blast radius
One CSS selector in `page-portal-profile.php`. The only visible change is that the two Copy
buttons appear in the account card; the username Save button, the referral URL loading and the
clipboard behaviour are unchanged. On a narrow row the URL truncates with an ellipsis sooner,
because the button now takes its width.

## Verification
- `php -l page-portal-profile.php`.
- `npm run test:js` and `npm run test:php` (no suite covers the Profile page markup; run for
  the CI gate).
- `git diff --stat origin/main...HEAD`.
- Not verified: the page in a browser (no WordPress instance in this session).
