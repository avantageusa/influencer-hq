#!/usr/bin/env python3
"""Upload files changed in HEAD (vs its first parent) over SFTP.

Used by .github/workflows/deploy-rfqa.yml. Credentials come from the
environment so they never sit in the theme. Deleted files are skipped: the
RFQA theme is already populated and we do not remove server files from CI.
"""

from __future__ import annotations

import os
import posixpath
import subprocess
import sys

# These paths are in git but are not the live WordPress theme.
SKIP_PREFIXES = (
    ".github/",
    ".claude/",
    ".cursor/",
    "docs/",
    "tests/",
    "scripts/",
)


def git_changed_files() -> list[str]:
    """Files added, copied, modified or renamed in HEAD vs its first parent."""
    try:
        subprocess.run(
            ["git", "rev-parse", "--verify", "HEAD^"],
            check=True,
            capture_output=True,
        )
    except subprocess.CalledProcessError:
        print("Tagged commit has no parent; nothing to upload.", file=sys.stderr)
        return []

    result = subprocess.run(
        ["git", "diff", "--name-only", "--diff-filter=ACMR", "HEAD^", "HEAD"],
        check=True,
        capture_output=True,
        text=True,
    )
    files = []
    for line in result.stdout.splitlines():
        path = line.strip().replace("\\", "/")
        if path == "" or should_skip(path) or not os.path.isfile(path):
            continue
        files.append(path)
    return files


def should_skip(path: str) -> bool:
    return any(path == prefix.rstrip("/") or path.startswith(prefix) for prefix in SKIP_PREFIXES)


def mkdir_p(sftp, remote_dir: str) -> None:
    """Create remote_dir and its parents. SFTP has no mkdir -p."""
    parts = []
    remaining = remote_dir.strip("/")
    prefix = "/" if remote_dir.startswith("/") else ""
    for part in remaining.split("/"):
        if part == "":
            continue
        parts.append(part)
        current = prefix + "/".join(parts)
        try:
            sftp.stat(current)
        except OSError:
            sftp.mkdir(current)


def upload(files: list[str]) -> None:
    host = os.environ["DEV_SFTP_HOST"]
    user = os.environ["DEV_SFTP_USER"]
    password = os.environ["DEV_SFTP_PASSWORD"]
    port = int(os.environ.get("DEV_SFTP_PORT") or "22")
    remote_root = os.environ["DEV_SFTP_PATH"].rstrip("/")

    import paramiko

    transport = paramiko.Transport((host, port))
    try:
        # WP Engine host keys rotate; the host itself is a secret, so we accept
        # whatever key it presents rather than pinning one in the workflow.
        transport.connect(username=user, password=password)
        sftp = paramiko.SFTPClient.from_transport(transport)
        if sftp is None:
            raise RuntimeError("SFTP client was not created")
        try:
            for path in files:
                remote = posixpath.join(remote_root, path)
                mkdir_p(sftp, posixpath.dirname(remote))
                sftp.put(path, remote)
                print(f"uploaded {path} -> {remote}")
        finally:
            sftp.close()
    finally:
        transport.close()


def main() -> int:
    files = git_changed_files()
    if not files:
        print("No theme files in this commit to upload.")
        return 0
    print(f"Uploading {len(files)} file(s).")
    upload(files)
    return 0


if __name__ == "__main__":
    sys.exit(main())
