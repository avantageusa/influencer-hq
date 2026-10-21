---
name: SFTP the tagged commit onto the RFQA theme
overview: >
  Pushing the git tag deploy-rfqa should SFTP only the files in that commit onto
  the WP Engine theme at /wp-content/themes/Influencer-hq. Host, user, password,
  port and remote path come from existing GitHub Actions secrets. Out of scope:
  QA/prod, full-tree sync, deleting remote files, and putting credentials in the
  theme.
todos:
  - id: workflow
    content: Add .github/workflows/deploy-rfqa.yml on tag deploy-rfqa
    status: completed
  - id: upload
    content: Upload added/modified files from the tagged commit; skip tests/docs/CI paths
    status: completed
  - id: verify
    content: YAML/python sanity check; first real run is after merge plus a tag push
    status: completed
---

# [ENGR-6662] SFTP the tagged commit onto the RFQA theme

**Ticket:** https://avantageusa.atlassian.net/browse/ENGR-6662
**Drafted by:** Cursor Grok 4.6

## Problem

FileZilla uploads of individual theme files after a merge are easy to miss. We want a tag on a commit to publish that commit's files to the already-populated RFQA theme.

## Approach

- Workflow triggers on `push` of the exact tag `deploy-rfqa`.
- Checkout that commit with `fetch-depth: 2` so `HEAD^` exists, then list `git diff --name-only --diff-filter=ACMR HEAD^ HEAD`.
- Skip `.github/`, `.claude/`, `.cursor/`, `docs/`, `tests/`, `scripts/`. Do not delete remote files.
- SFTP the rest into `DEV_SFTP_PATH` with `DEV_SFTP_HOST` / `USER` / `PASSWORD` / `PORT`, preserving folders.

## Alternatives considered

- Upload the whole theme every time — slower and more chance of clobbering server-only files; the theme is already on RFQA.
- Diff against the previous `deploy-rfqa` tag — the tag name is reused, so there is no durable previous tag.
- Refresh inside a PHP page on the server — credentials and git would live on WP Engine.

## Blast radius

Only the files in the tagged commit reach RFQA. Tagging HEAD after five untagged commits uploads the last commit only. The workflow file must already be on the tagged revision (merge this to `main` before the first tag). Reusing the tag means deleting the remote tag and the local tag, then tagging again.

## Notes

First slice of ENGR-6662 only: a reusable `deploy-rfqa` tag SFTPs the files in that commit. Merge-to-main auto-deploy, QC → QA → PROD promotion, WP Engine's rsync action, and a running-commit stamp are later work on the same epic.

Secrets are already on the repo except `DEV_SFTP_HOST_KEY` (SHA256 fingerprint or `ssh-keyscan` public key line). The upload refuses to send the password until that secret is set. Do not put the password in git or in chat.
