---
name: PO-3101 — Flow is strictly forward-moving (FR-10)
overview: >
  Verification-only story: confirmed both AC requirements (no back button in the UI, browser back
  has no effect on flow progression) are already satisfied by the existing architecture from
  PO-3092 onward. No code changes were needed or made.
todos:
  - id: no-back-button-check
    content: Confirm no back-button UI exists anywhere in the flow markup
    status: completed
  - id: browser-back-check
    content: Live-test browser back navigation against the running flow to confirm it exits the flow rather than reverting to an earlier screen
    status: completed
---

# PO-3101 Flow is strictly forward-moving — Scenario 19 (FR-10)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3101 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Problem / question

FR-10 requires: no back button anywhere in the flow, and browser back navigation has no effect
on flow progression (visitor can only move forward). Given every prior story (PO-3092–3100) built
this flow as a single page with pure JS/DOM-state panel switching, the question was whether this
requirement was already satisfied or needed active work.

## Findings

1. **No back-button UI exists.** `grep -in "back" page-home-aicoach.php` (excluding false
   positives like `backdrop-filter` and the word "history" inside a spoken script) returns
   nothing — there has never been a back control anywhere in this flow.
2. **No browser history/hash manipulation exists in the JS.** `js/aicoach-coach-flow.js` never
   calls `history.pushState`/`replaceState`, never listens for `popstate`/`hashchange`, and never
   touches `location.hash`. Every screen transition (`showPanel()`) is pure DOM class/attribute
   manipulation — the browser's history stack is never touched at all by this flow.
3. **Live-tested browser back against the running flow** (local wp-env): navigated to a different
   page first, then to the AI Coach page, let it progress a couple of screens (reached
   `believe-2`), then triggered browser back. Result: the tab left the AI Coach page entirely and
   returned to the page visited beforehand — it did **not** revert to an earlier screen within the
   flow (there was no such history state to revert to in the first place). This is exactly what
   the AC asks for: back has no effect on *flow progression* specifically, because the flow never
   created any history entries for back to act on.

## Conclusion

**No code changes needed.** The requirement is a natural consequence of how every prior story in
this epic was already built (panel visibility toggled via classes/attributes, no
history/hash/routing layer at all) — not something that needed separate implementation. This
plan documents the verification for the record, same as every other FR in this epic gets a plan.

## Blast radius

None — no files changed.
