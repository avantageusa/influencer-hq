---
name: deploy-rfqa only after the commit is on main
overview: >
  Tagging deploy-rfqa on a feature branch tried to SFTP unmerged files. Keep the
  tag as the trigger, but refuse unless that commit is already on main, then
  upload the files in that merged commit. Out of scope: auto-deploy on every
  merge, QC/prod, deleting remote files.
todos:
  - id: gate
    content: Fail the job unless the tagged commit is already on origin/main
    status: completed
  - id: docs
    content: Document merge-then-tag (reuse the tag on that main commit)
    status: completed
  - id: verify
    content: YAML sanity; no SFTP in this change
    status: completed
---

# [ENGR-6662] deploy-rfqa only after the commit is on main

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-6662
**Drafted by:** Cursor Grok 4.6

## Problem

`deploy-rfqa` on a feature branch uploads that branch's files before merge. RFQA should get the merged commit's files.

## Approach

- Trigger stays `push` of tag `deploy-rfqa`.
- After checkout, fetch `origin/main` and require `HEAD` is an ancestor of `origin/main`.
- Then the existing script uploads `git diff HEAD^ HEAD` (the files in that merge), skipping docs/tests/CI paths.

Sequence: merge the PR to `main`, then point `deploy-rfqa` at that merge commit and push the tag.

## Alternatives considered

- Upload on every merge to main — user kept the tag as the opt-in.
- Compare HEAD to the current main tip only — a tag on an older merged commit would be refused even though it is on main.

## Blast radius

Tagging a branch that is not on main fails the job and uploads nothing. After this workflow is itself on main, the next `deploy-rfqa` tag uses the gate.
