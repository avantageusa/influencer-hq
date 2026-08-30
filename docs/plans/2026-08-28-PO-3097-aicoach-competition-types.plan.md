---
name: PO-3097 — Competition types explained, no selection (FR-06)
overview: >
  Adds three coach-narrated screens (World, Community, Private) explaining the competition types
  available on InfluencerHQ, queued right after the FR-05 equity examples for the 5- and 10-minute
  tiers only — the 2-minute tier skips this section entirely (Scenario 9). No selection is
  requested or captured on any of the three screens, per the ticket's explicit instruction to
  remove the previously-approved Private script's closing "let's choose one" line.
todos:
  - id: trophy-icon
    content: Add a placeholder trophy icon SVG (same flat-line style as the belief-coin/chart icons) — no such asset exists in this repo
    status: completed
  - id: three-competition-panels
    content: Add competition-world / competition-community / competition-private panels — headline, trophy icon, type name, participant-format text, caption
    status: completed
  - id: competition-screens-data
    content: COMPETITION_SCREENS map + getCompetitionScreensForTier(tierMinutes) — empty for '2', all three in order for '5'/'10'
    status: completed
  - id: queue-after-equity
    content: Tier click handler now queues both FR-05 equity screens AND FR-06 competition screens in one push, reusing the same sequence/resume machinery from PO-3096
    status: completed
  - id: verify
    content: php -l, composer lint:php (only the known unrelated failure), phpcs (570 vs 494 baseline, same pre-existing convention, proportional to the added markup), live wp-env testing — confirmed 10-min tier reaches competition-world with correct content, and 2-min tier correctly stops at equity-bts without ever queuing competition screens
    status: completed
---

# PO-3097 Competition types explained — Scenario 9 (FR-06)

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3097 (epic https://avantageusa.atlassian.net/browse/PO-3062)
**Drafted by:** Claude Code (Sonnet 5)

## Problem

After the FR-05 equity examples, the 5- and 10-minute tiers need three more coach-narrated screens
explaining World, Community, and Private competitions — explanation only, no selection screen, no
competition preference captured anywhere. The 2-minute tier skips this section entirely and goes
straight to whatever comes next (FR-07, not yet built).

## Approach

1. **Same queuing pattern as PO-3096.** `getCompetitionScreensForTier(tierMinutes)` resolves the
   Scenario 9 branching (empty for `'2'`, all three for `'5'`/`'10'`); the tier-click handler now
   pushes both the equity screens and the competition screens onto `SCREENS` in one call. No new
   runner, no new resume logic needed — PO-3096 already made the queue/resume mechanism generic
   over "whatever's been appended."
2. **Trophy icon placeholder.** Ticket calls for a "trophy icon" that doesn't exist anywhere in
   this repo — added `icon-trophy.svg` in the same flat-line style as the existing belief-coin/
   belief-chart icons, reused across all three screens (differentiated by name/format text, not by
   icon variation — the ticket doesn't describe distinct icons per type).
3. **Private screen's closing line — used verbatim, not invented.** The ticket explicitly says the
   previously-approved closing ("Now that you've seen all three options… let's choose the one
   you'd like to start with.") must be removed since no selection is requested, and that "revised
   closing copy requires approval." The script text given in the ticket (ending on "...whether
   this is the right place to begin.") is used exactly as written — no transition sentence was
   invented to fill the gap left by the removed line. Flagging this as pending copy approval,
   same category of gap as PO-3092's video asset, PO-3093's belief icons, and PO-3096's Alix Earle
   photo.

## Alternatives considered

- **Distinct icon per competition type** (e.g. globe for World, people for Community, two-trophy
  for Private): not specified in the ticket beyond "trophy icon" (singular concept); adding
  invented visual distinctions not asked for would be scope creep on a placeholder asset that's
  going to be replaced by real design anyway.
- **Writing a new Private closing/transition line**: rejected — the ticket is explicit that this
  copy needs approval; inventing one would risk it reading as if it were already signed off.

## Blast radius

- `page-home-aicoach.php`: three new panels appended after `equity-bts`; new `.aicoach-competition*`
  CSS block; one new `$aicoach_img` entry (`trophy`).
- `js/aicoach-coach-flow.js`: additive — `COMPETITION_SCREENS`/`getCompetitionScreensForTier`, and
  one line changed in the tier-click handler's `SCREENS.push(...)` call to include both FR-05 and
  FR-06 screens together.
- New file: `images/aicoach/icon-trophy.svg`.

## Notes

- **Private screen's closing copy is pending approval** — see Approach above. Do not treat the
  current ending as final without checking back once the team signs off on replacement copy.
- **Trophy icon is a placeholder**, same caveat class as the belief icons and Alix Earle photo.
- Same known-interim-architecture note as PO-3092–96 (direct Anam integration, approved temporary
  by Filip Milinković pending a separate engineer's ("Gary") agent API).
- Live-tested in the local wp-env instance (not just static mockups): confirmed the 10-minute tier
  correctly reaches `competition-world` with the right headline/format text, and the 2-minute tier
  correctly stops at `equity-bts` without the competition screens ever being queued.
