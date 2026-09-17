"""Focused PHP boundary mutations in an isolated temporary theme copy."""
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
mutations = [
    ("$expiry <= $now", "$expiry < 0"),
    ("$expiry > $now + 300", "$expiry > $now + 3000"),
    ("! hash_equals( hash_hmac", "hash_equals( hash_hmac"),
    ("$status < 200 || $status >= 300", "$status < 0"),
    ("$body['success'] !== true", "$body['success'] === true"),
    ("'redirection' => 0", "'redirection' => 5"),
    ("'sslverify' => true", "'sslverify' => false"),
    ("'wpu-' . $user->ID", "'wpu-99'"),
]
with tempfile.TemporaryDirectory(prefix="ihq-bridge-mutations-") as folder:
    target = Path(folder)
    shutil.copytree(root / "inc", target / "inc")
    shutil.copytree(root / "tests", target / "tests")
    source = (root / "inc/harness-auth-bridge.php").read_text()
    def run():
        return subprocess.run(["docker", "run", "--rm", "-v", f"{target}:/app:ro", "-w", "/app", "php:8.3-cli", "php", "tests/harness-auth-bridge.test.php"], capture_output=True).returncode
    assert run() == 0, "Baseline failed"
    for before, after in mutations:
        assert source.count(before) == 1, before
        (target / "inc/harness-auth-bridge.php").write_text(source.replace(before, after))
        assert run() != 0, f"Surviving mutation: {before}"
print(f"Killed {len(mutations)}/{len(mutations)} PHP boundary mutations")
