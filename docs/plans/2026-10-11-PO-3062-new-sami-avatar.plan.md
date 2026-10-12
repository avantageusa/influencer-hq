---
name: Prepare the switch to Sami's new avatar so every clip and the portrait change together
overview: >
  Gary is switching the influencerhq box to Sami's new avatar (the same avatar and the same
  voice in every language). The live intro and Ask Sami answers change the moment he
  switches, but the 63 pre-rendered clips (9 segments in 7 languages) and the still portrait
  are the old face, and the clip renderer skips any segment whose text has not changed. This
  adds an avatar version to the pre-render: a new version renders into its own files and
  manifest under its own idempotency keys while visitors keep the current clips, and one
  step (checked: it refuses unless every clip exists) makes the new version the one served,
  together with the new still portrait. Rolling back is the same step. Out of scope: asking
  Gary to switch (done by email), the one-off render trigger for a server with no WP-CLI
  (a separate script, not committed), and any change to the live avatar code.
todos:
  - id: versioned-prerender
    content: Make the manifest, the file names and the idempotency key of inc/aicoach-prerender.php depend on an avatar version ('' keeps today's names, so nothing already rendered is touched)
    status: completed
  - id: active-version
    content: An option holds the version served to visitors; ihq_aicoach_prerender_get_urls() reads it; an activation function refuses unless every clip of the live set exists in the new version
    status: completed
  - id: portrait
    content: The still portrait follows the active version (coach-portrait-<version>.webp, falling back to today's file), in the AI Coach page and the appointment link page; the new portrait is made from Gary's 3:2 framing
    status: completed
  - id: cli
    content: "wp aicoach prerender --avatar-version=<v>, wp aicoach activate-avatar <v|original>, wp aicoach avatar-status"
    status: completed
  - id: tests
    content: PHP tests for the naming, the keys, the served set, the activation check and a full render with stubbed Coach API calls, with manual mutation checks
    status: completed
  - id: verify
    content: Run tests, lint, and CI gate before merge
    status: pending
---

# [PO-3062] Prepare the switch to Sami's new avatar

**Ticket:** https://avantageusa.atlassian.net/browse/PO-3062
**Drafted by:** Claude (claude-sonnet-5-5)

## Problem
Gary (the influencerhq box, 2026-10-11) is switching Sami to a new avatar. What he told us:
- Our code sends no avatar, voice or model ids (the box pins them), so the switch is on his
  side and takes about 15 minutes. We agree the time first.
- The switch is visible as `say.video.avatar_id` changing in a turn asked with
  `want: ["text","video"]`. Today it is `3cb8331c-95d6-41d4-bb2f-bbc687581357`.
- A render job is matched only on our `Idempotency-Key`, so a new key always makes a new
  render, and nothing needs clearing on his side.
- **The face is fixed when a job starts rendering**, so the re-render must be queued only
  after he confirms the switch. A job queued earlier may come out with today's face.
- The box takes 300 renders per UTC day, one at a time (about 48 seconds each).

What this means for the page:
- The live intro and Ask Sami answers change at the switch. The pre-rendered clips do not.
  There are 9 segments (`we_believe_1`, `we_believe_2`, `time_selection`, `magic_johnson`,
  `alix_earle`, `bts`, `world`, `community`, `private`) in 7 languages: **63 clips**, about
  50 minutes, well inside one day's limit (Gary counted 65 English texts the box has ever
  rendered, which is not our set, so his estimate of 455 renders does not apply).
- `ihq_aicoach_prerender_segment()` skips a segment when the manifest already holds this
  exact text hash and the file exists, and the idempotency key is built from the same hash.
  Re-running the command after the switch would change nothing.
- Re-rendering in place would show a mix of old and new faces while it runs (a clip at a
  time), and every clip a visitor loads would change under them.
- The still portrait (`images/aicoach/coach-portrait.webp`, shown while the live avatar
  connects and when it is paused) is the old face too.

## Approach
- **An avatar version.** `''` is today's set: `manifest.json`, `<segment>.mp4` and
  `<segment>-<language>.mp4`, and the keys built today, so nothing already rendered is
  touched or orphaned. A version such as `v2` has its own `manifest-v2.json`, files named
  `<segment>.v2.mp4` and `<segment>-<language>.v2.mp4`, and an idempotency key with `-v2`
  at the end, so Gary always makes a new job. A version must match `v` and one to three
  digits, because it ends up in file names.
- **One version is served.** The option `ihq_aicoach_avatar_version` holds it (empty means
  today's set). `ihq_aicoach_prerender_get_urls()` reads that manifest only, so rendering a
  new version changes nothing visitors see.
- **Switching is one checked step.** `ihq_aicoach_avatar_activate( $version )` compares the
  new version with what is served now: every clip the served manifest has must exist, as a
  file, in the new version's manifest. If one is missing it changes nothing and lists them.
  Otherwise it sets the option. Activating `''` is the way back (the old files are never
  deleted), and it makes the same check.
- **The still portrait follows the version.** `coach-portrait-<version>.webp` when that file
  exists, else today's file, in both templates that show it (the AI Coach page and the
  appointment link page), with the same file-time cache-busting as today. The new portrait
  is made from Gary's framing: the avatar renders at 3:2 (1152 x 768), his crop is the
  centre 3:2 (1092 x 728) of our export, and the current portrait is already 600 x 400 (3:2),
  shown in a circle with `object-fit: cover`.
- **Commands** (`wp aicoach`): `prerender --avatar-version=<v>` renders into a version,
  `activate-avatar <v|original>` switches, `avatar-status` shows the served version and how
  complete each version is.
- **A server with no WP-CLI (dev).** The list of what to render is one function,
  `ihq_aicoach_prerender_work_list()` (the approved segments in English and in every
  translation of ours: 63 items, checked live against Gary's scripts), used by the command and
  by `ihq_aicoach_avatar_render_step( $version, $index )`, which renders one item and says how it
  went. A request is cut off at about a minute, so a step waits only 5 polls (the command waits
  18); an item still rendering is reported as `pending` and asked for again (Gary's job is
  replayed under the same key, not started again). A small web page, kept outside git and deleted
  from the server after use, walks through the items one request at a time, shows the status
  table and has the switch and the way back; it is open to a logged-in administrator only and
  every action carries a nonce.
- **Order of the switch** (the plan the dev follows): 1) agree the time with Gary (Monday
  2026-10-12, 15:00 CEST, 13:00 UTC); 2) he switches, checks the face on a live call and
  emails the new `avatar_id`, and we see `say.video.avatar_id` move off
  `3cb8331c-95d6-41d4-bb2f-bbc687581357`; 3) render version `v2` **on one site**; 4) check the
  clips and the portrait; 5) copy that version's files to the other site; 6) activate `v2` on
  each site; 7) purge the cache and check the AI Coach page in every language.

## Alternatives considered
- **Re-render in place under the same names with a new key:** a visitor mid-flow gets a
  mixed set, and a failure halfway leaves old and new faces together. A separate version
  switches all at once.
- **A fingerprint that includes the avatar id read from Gary:** automatic, but it means
  reading the avatar id from a live session on every run, and the face of a queued job is
  fixed when it starts, so the check would have to be exact. An explicit version, chosen by
  the person who knows the switch has happened, is simpler and safer.
- **Replacing `coach-portrait.webp` in place:** a manual step that has to coincide with the
  clips, and the file is cached for a year on the edge. The version picks both together.
- **Deleting the old clips after the switch:** keeping them is the rollback; they can be
  removed later.

## Blast radius
`inc/aicoach-prerender.php` (every function that builds a path or a key gains an optional
version, defaulting to today's behaviour), `page-home-aicoach.php` and `page-appointment.php`
(which portrait file they use), one new image, and the tests. With no version set, the page
behaves exactly as today. What can go wrong that is not obvious: a version that is activated
while a job for it was queued before Gary's switch would keep the old face (so the dev must
queue it after he confirms), the cache on the edge keeps old clips for a year under unchanged
URLs (the served URLs carry the fingerprint, which differs between versions, and the new
files have other names), and rendering 63 clips takes about 50 minutes, so a trigger that
runs inside one HTTP request needs the existing pending-and-resume behaviour.

## Notes
- **One manifest per version, written one clip at a time.** `ihq_aicoach_prerender_segment()`
  used to read the manifest when it started and write it back when it finished, after the
  polling and the download. Two renders running at once (the web step runner and the command,
  or two browser tabs) would each write back the manifest they had read, and the later write
  dropped the other's clip; the clip file stayed on disk but was no longer served, and the
  switch would then be refused as incomplete. The write is now `ihq_aicoach_prerender_record_clip()`:
  it takes an exclusive `flock` on `<manifest>.lock`, reads the manifest again, sets that one
  entry and saves through a temporary file and `rename()`, so a reader never sees half a file.
  If the lock cannot be taken (a file system without it) the re-read still shrinks the window
  from minutes to the moment between read and write. The rename is made only after the whole
  text was written (CodeRabbit, second round): a full disk or a quota gives a short or empty
  temporary file, and renaming that would empty the manifest and hide every clip of the set.
  A failed encode, write or rename removes the temporary file, leaves the manifest as it was and
  returns `false`; `ihq_aicoach_prerender_segment()` then reports an `error` for that clip
  instead of `rendered`, so the render page and the command show it and ask again.
- The set to render is the served manifest's set. A segment added later is rendered by the
  normal command and has to be rendered in the active version too.
- The two Sami versions on the Anam account and which one Gary deletes are the dev's and
  Marcus's decision, not part of this change.
- Related: `2026-09-23-PO-3062-aicoach-coach-video-api.plan.md` (the renderer),
  `2026-09-25-PO-3107-aicoach-multilanguage-avatar-voice.plan.md` (one avatar and one voice
  in every language).

## Verification
- `npm run test:php`: 14 suites, all pass; the new `tests/aicoach-prerender.test.php` has 139
  checks (what can be a version, the version served, names, manifests and keys of the original
  set and of a version, a full render into a version with stubbed Coach API calls: the files,
  the manifest and the key sent, the cached case, `ihq_aicoach_prerender_all()` passing the
  version for English and for translations, the served URLs, the checked switch in every
  direction, the status and the portrait). `php -l` is clean on every PHP file touched.
- It caught one bug of mine: the version pattern ended with `$`, which lets a trailing newline
  through, so "v2\n" would have been a valid version (it ends up in file names). It ends with
  `\z` now.
- The overlap is tested by writing another clip into the same manifest from inside the stubbed
  Coach API call, while a render is under way, and checking that both clips are served. Four
  mutations (no re-read, no atomic rename, the entry not set, the wrong lock file) were killed,
  and eight more on the checked save (no encode check, no rename check, no temporary-file
  cleanup, always `true`, the record and the segment ignoring a failure, the error text); the
  one that survived is the length check, because a short write needs a full disk or a quota to
  happen. Not covered: the `flock` call itself, because one process cannot
  observe it; a wider `glob` for the status is an equivalent mutation (the version check
  already rejects `v2.json.lock`).
- Manual mutation checks (Stryker is deferred): 29 mutations (the pattern, the validation,
  each place that takes the version into the manifest path, the file name, the key, the cache
  check, the saving, the served set and the rendering of English and translations, the
  servable check, the missing list, each check in the switch, the option write, the status and
  the portrait), all killed after one test was added for an entry whose file is gone. One mutation
  I wrote was a no-op and proved nothing.
- wp-env (WP-CLI there; Gary is never called): `wp aicoach avatar-status` showed the original
  set (36 clips). A simulated `v2` made by copying the clips, with one left out:
  `activate-avatar v2` refused with "Clips missing from the version: we_believe_2" and the
  status still served the original. With all 36: the switch was made, the AI Coach page offered
  `.v2.mp4` clips and no original names, the portrait of both pages was
  `coach-portrait-v2.webp`, and `activate-avatar original` brought the original clips and
  portrait back. The simulated files and the option were removed afterwards.
- **The new portrait** (`images/aicoach/coach-portrait-v2.webp`, 600 x 400) is made from our
  export of the Figma node, cropped to the centre 3:2 (1092 x 728 of 1592 x 728) as Gary
  describes. His own crop file was not on this machine, so it is a reproduction of his framing,
  not his file; if he confirms a different frame, only this image changes.
- The render step and the page for a server with no WP-CLI (wp-env, no clip rendered): not
  logged in, the page and its calls answer 403; a call with no nonce or a wrong one answers 403;
  the status call lists the versions; a step past the end reads Gary's real script list and
  answers `done` with `total: 63` and renders nothing; the switch with nothing rendered is refused;
  with a simulated complete `v2` the switch is made, repeated it says nothing changed, the AI Coach
  page serves `.v2.mp4` clips, and the way back restores the original. The loop in the page was
  driven with a replaced request function through rendered, cached, pending (asked again), a
  gateway error (tried again, then stopped after four), a clip that fails (stops and says so) and
  the end of the list. That run caught a bug in the page: a pending step carries a message, which
  the first version took for an error and stopped on. 24 more mutations of the work list and the
  step, all killed after two tests were added (the default poll counts, by reflection); the one
  that survived changes a negative poll count, which behaves like zero.
- Not verified: a real render against Gary's box (it costs 63 renders and the face of a job
  is fixed when it starts, so it has to wait for his confirmation of the switch), the commands
  on a server with no WP-CLI (they need the one-off trigger script, updated separately), the
  cache on the edge after the switch, and the look of the new portrait on a phone.

## How the switch is run
1. Agree the time with Gary; he deletes the avatar the dev chose and switches (about 15
   minutes) and sends the new avatar id. Check `say.video.avatar_id` changes from
   `3cb8331c-95d6-41d4-bb2f-bbc687581357`.
2. Only then: `wp aicoach prerender --avatar-version=v2` (63 clips, about 50 minutes; run it
   again until nothing is pending).
3. `wp aicoach avatar-status`, then play a few of the new clips in each language (their files end in `.v2.mp4`).
4. `wp aicoach activate-avatar v2` (refused unless complete), then purge the edge cache and
   check the AI Coach page in every language.
5. Back: `wp aicoach activate-avatar original`.
