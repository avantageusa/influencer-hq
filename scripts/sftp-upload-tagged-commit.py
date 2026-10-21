#!/usr/bin/env python3
"""Upload files changed in HEAD (vs its first parent) over SFTP.

Used by .github/workflows/deploy-rfqa.yml. Credentials come from the
environment so they never sit in the theme. Deleted files are skipped: the
RFQA theme is already populated and we do not remove server files from CI.
"""

from __future__ import annotations

import base64
import hashlib
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


def paths_from_nul_names(payload: bytes) -> list[str]:
    """Split `git diff -z --name-only` output into paths, unchanged."""
    names = []
    for raw in payload.split(b"\0"):
        if raw == b"":
            continue
        names.append(raw.decode("utf-8"))
    return names


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
        ["git", "diff", "-z", "--name-only", "--diff-filter=ACMR", "HEAD^", "HEAD"],
        check=True,
        capture_output=True,
    )
    files = []
    for path in paths_from_nul_names(result.stdout):
        if should_skip(path) or not os.path.isfile(path):
            continue
        files.append(path)
    return files


def should_skip(path: str) -> bool:
    """True when the path is repo-only and must not be copied onto the theme."""
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


def sha256_fingerprint(key) -> str:
    """OpenSSH SHA256 fingerprint for a Paramiko host key (`ssh-keygen -lf`)."""
    digest = hashlib.sha256(key.asbytes()).digest()
    return "SHA256:" + base64.b64encode(digest).decode("ascii").rstrip("=")


def host_key_matches(remote_key, expected: str) -> bool:
    """True when expected is this key's SHA256 fingerprint or OpenSSH public line."""
    expected = expected.strip()
    if expected == "":
        return False
    fingerprint = sha256_fingerprint(remote_key)
    if expected == fingerprint or expected == fingerprint[len("SHA256:") :]:
        return True
    parts = expected.split()
    if len(parts) >= 2 and parts[0] == remote_key.get_name():
        presented = base64.b64encode(remote_key.asbytes()).decode("ascii")
        return parts[1] == presented
    return False


def require_host_key() -> str:
    """Trusted host key from DEV_SFTP_HOST_KEY; empty means we must not authenticate."""
    return os.environ.get("DEV_SFTP_HOST_KEY", "").strip()


def upload(files: list[str]) -> None:
    """Open SFTP after verifying the host key, then put each local path under DEV_SFTP_PATH."""
    host = os.environ["DEV_SFTP_HOST"]
    user = os.environ["DEV_SFTP_USER"]
    password = os.environ["DEV_SFTP_PASSWORD"]
    port = int(os.environ.get("DEV_SFTP_PORT") or "22")
    remote_root = os.environ["DEV_SFTP_PATH"].rstrip("/")
    expected_key = require_host_key()
    if expected_key == "":
        raise RuntimeError(
            "DEV_SFTP_HOST_KEY is unset; refusing to send the SFTP password to an unverified host."
        )

    import paramiko

    transport = paramiko.Transport((host, port))
    try:
        transport.start_client(timeout=30)
        remote_key = transport.get_remote_server_key()
        if not host_key_matches(remote_key, expected_key):
            raise RuntimeError("SFTP host key mismatch; refusing to authenticate.")
        transport.auth_password(user, password)
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
    """Entry: list the tagged commit's theme files and upload them, or no-op."""
    files = git_changed_files()
    if not files:
        print("No theme files in this commit to upload.")
        return 0
    print(f"Uploading {len(files)} file(s).")
    upload(files)
    return 0


if __name__ == "__main__":
    sys.exit(main())
