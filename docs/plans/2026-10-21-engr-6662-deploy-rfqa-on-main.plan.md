---
name: Upload RFQA on merge when the PR has deploy-rfqa
overview: >
  Tag deploy-rfqa on a PR commit. When that PR merges to main, SFTP the files
  in the merge — not the pre-merge branch, and not every merge. Out of scope:
  QC/prod, deleting remote files, tagging after merge.
todos:
  - id: trigger
    content: Run on pull_request_target closed to main when merged
    status: completed
  - id: gate
    content: Upload only if deploy-rfqa points at a commit in that PR
    status: completed
  - id: files
    content: Checkout the merge commit and upload git diff HEAD^ HEAD
    status: completed
  - id: verify
    content: YAML structure; no SFTP in this change
    status: completed
---

# [ENGR-6662] Upload RFQA on merge when the PR has deploy-rfqa

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-6662
**Drafted by:** Cursor Grok 4.6

## Problem

Tagging a feature branch uploaded unmerged files. Tagging after merge was extra work. We want: tag the commit, merge the PR, RFQA gets the merged files.

## Approach

- `pull_request_target` on close to `main` (workflow always from main) with `merged == true`.
- Checkout `merge_commit_sha` so the upload is what landed, including squash.
- Fetch `deploy-rfqa`. Upload only when that tag is an ancestor of the PR head and not of the PR base (it was on this PR, not leftover on main).
- Existing script uploads `git diff HEAD^ HEAD`, skipping docs/tests/CI paths.

## Alternatives considered

- Tag-create after merge — a second manual step.
- Upload every merge to main — no opt-in.
- Upload the tagged SHA's tree — squash/rebase would miss what actually landed on main.

## Blast radius

Untagged merges skip upload. A stale `deploy-rfqa` still pointing at main does not re-upload. Secrets run only after merge, against the merge commit.
