---
name: Stop Profile referral URLs overlapping their titles
overview: >
  On the Profile account card, Follower and Influencer Referral link titles overflow
  their flex boxes and paint over the URLs. Keep the existing side-by-side row (title,
  truncated URL, Copy) and stop the overflow by not shrinking the title and by letting
  the URL ellipsize. No new breakpoint, no markup change beyond a row class.
todos:
  - id: css
    content: Add sett-row--referral and CSS so titles do not shrink/overflow and URLs ellipsize
    status: completed
  - id: verify
    content: php -l page-portal-profile.php; check the two rows at desktop and at the existing 900px wrap
    status: completed
---

# [ENGR-7510] Referral links overlap their titles

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-7510
**Drafted by:** Cursor (Grok 4.6)

## Problem
The Profile page shows "Follower Referral link:" and "Influencer Referral link:" overlapping
the URL that sits in the same row. QA expects each link next to or below its title, with no overlap.

## Reproduction
1. Open Profile (`/portal/account` → Profile).
2. Look at the two referral rows in ACCOUNT INFORMATION.

## Approach
Account-card labels use `white-space: nowrap` and `flex: 0 1 auto` with visible overflow, so
the title box shrinks and the text paints over the URL. The URL span is `flex: 1` without
`min-width: 0`, so it will not ellipsize. `justify-content: flex-end` then overflows left.

In `page-portal-profile.php`:
- Mark the two rows `sett-row--referral`.
- Do not shrink those titles (`flex: 0 0 auto`).
- Give `.sett-referral-url` `min-width: 0` so the existing ellipsis works.
- Put `min-width: 0` on `.sett-referral-inline .sett-referral-url` in `template-parts/portal-styles.php`
  so the flex child can shrink.

Reuse the file's existing `max-width: 900px` wrap; do not add a breakpoint or a magic min-width.

## Alternatives considered
- **Always stack title above URL:** allowed by the ticket, but the Figma-aligned mock and
  ENGR-7355 keep one line with truncation. Fix truncation instead.
- **nowrap off on every account label:** would also wrap "Profile Photo or Avatar"; out of scope.

## Blast radius
Profile account-card referral rows only. Name, Username, Email and the Copy handler stay as they are.
On a narrow row the URL truncates earlier; the Copy button stays visible.
