---
name: PO-3113 — IHQ design system consistency audit (NFR-04)
overview: >
  Verification-only ticket, no new code. Audited the AI Coach flow's full color palette (via a
  complete grep of every hex color in page-home-aicoach.php) and confirmed visually across intro,
  believe, equity-magic, and identity screens that the flow already complies with every element
  of the IHQ design system this ticket lists — dark background, gold accent headlines/names,
  white body text, circular avatar, always-visible IHQ nav bar, and green tick/red cross icons for
  example screens. No bespoke or conflicting styles found anywhere in the file.
todos:
  - id: full-color-palette-audit
    content: "grep every hex color used in page-home-aicoach.php (1100+ lines) — found exactly 8 colors total, each mapping cleanly to one design-system role: #fdd65b (gold, headlines/names only — confirmed by checking every color:#fdd65b selector's context, all are bold/large-font heading rules), #fff (white body text), #12131a/#1b1c24 (dark backgrounds), #eb0000 (red/error), #3a3b47/#a9a9b3/#7a7b87 (borders/muted text). No stray or bespoke colors outside this palette."
    status: completed
  - id: verify-check-x-icon-colors
    content: "icon-check.svg uses stroke=\"#00E900\" (green), icon-x.svg uses stroke=\"#EB0000\" (red) — matches \"green tick / red cross format\" exactly"
    status: completed
  - id: verify-circular-avatar-and-navbar
    content: ".aicoach-avatar-wrap has border-radius:50%/overflow:hidden (circular, confirmed during PO-3111 work); the shared IHQ nav bar (template-parts/portal-header.php) renders on every page load, not conditional on flow state"
    status: completed
  - id: visual-screenshot-verification
    content: Live wp-env screenshots of intro/believe/equity-magic (name overlay + check/x icons)/identity form — all match the palette and design language with no rendering surprises
    status: completed
---

# PO-3113 IHQ design system consistency — Scenario 36 (NFR-04)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3113 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Problem

Every screen must use the IHQ design system consistently: dark background, gold accent headlines/
names, white body text, circular avatar, always-visible IHQ nav bar, green tick/red cross for
example screens — no bespoke or conflicting styles.

## Approach

Audit, not implementation — same pattern as PO-3095. Two passes:

1. **Static**: `grep -oE "#[0-9a-fA-F]{3,6}"` across the entire `page-home-aicoach.php` file
   returned exactly 8 distinct colors (one false positive from an HTML entity, `&#128264;`,
   filtered out). Checked the context of every `color: #fdd65b` usage specifically — all 7 are on
   `font-weight: 800`+ heading-style selectors (screen titles, story names, identity title), never
   misapplied to body copy. No color outside this 8-value set appears anywhere in the file,
   including the PO-3108 time-check overlay and PO-3110/3111 changes from this same epic.
2. **Visual**: live wp-env screenshots of the intro screen (nav, avatar, belief text), the
   equity-magic screen (gold "MAGIC JOHNSON" name overlay, green check / red X against the
   claim list), and the identity form (gold title, dark-themed input fields — not
   browser-default white inputs). All match the design language with nothing surprising.

## Alternatives considered

- **Cross-checking against the actual Figma file's variable definitions** for extra certainty:
  attempted via the Figma MCP connection, but `get_variable_defs` requires an active layer
  selection in the Figma desktop app, which wasn't available in this session. Skipped per
  explicit direction — the code-level audit alone was judged strong enough (a small, fully
  self-consistent palette with zero stray values is hard to explain as accidental compliance).

## Blast radius

None — no files changed. This PR/plan doc exists purely as verification evidence.

## Notes

- **"Full design sign-off obtained before release"** (part of the ticket's own acceptance
  criteria) is a human/process step, not something resolvable by code review — flagging this
  explicitly rather than claiming the ticket is 100% closed. Everything technically verifiable
  from the codebase passes; the design sign-off itself is for Filip/design team to give.
- Same reasoning as PO-3111's header-overlap fix: the shared site chrome (nav bar, footer) is
  a separate component outside "the flow" this ticket describes — noted but not audited in
  detail here (e.g. the site footer uses a maroon/dark-red panel distinct from the main dark
  palette, visible when scrolling past the flow — that's shared across the whole site, not
  scoped to this ticket).
- No new PR for this ticket, same as PO-3095 — nothing to merge.
